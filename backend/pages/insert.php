<?php
/**
 * ====== monitor.php (Single File) ======
 * - Buka di browser: otomatis cek & kirim notif tiap 2 detik TANPA refresh halaman (AJAX).
 * - Jalankan via CLI: `php monitor.php` -> loop tiap 2 detik di terminal.
 *
 * 📌 Ganti TOKEN_BOT dan CHAT_ID di bawah.
 * 📌 Threshold bisa kamu atur di bagian $THRESHOLDS.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========== KONFIG TELEGRAM ==========
$token   = "";    // <-- ganti
$chat_id = "";      // <-- ganti

// ========== LOKASI CACHE ==========
$status_file = __DIR__ . '/status_cache.json';

// ========== KONFIG INTERVAL & THRESHOLDS ==========
$POLL_INTERVAL_SECONDS = 2;          // cek tiap 2 detik
$COOLDOWN_SECONDS      = 0;          // jeda minimal antar notif per kategori (0 = nonaktif)

// Hysteresis thresholds (ON = trigger kirim, OFF = kondisi pulih)
$THRESHOLDS = [
    // "di atas ambang" = bahaya
    'suhu' => [
        'mode' => 'above',   // nilai tinggi berbahaya
        'on'   => 22,        // kirim jika suhu >= 32
        'off'  => 31,        // pulih jika suhu <= 31
        'label'=> '🔥 SUHU TERLALU TINGGI'
    ],
    // Kelembapan udara tinggi
    'kelembaban' => [
        'mode' => 'above',
        'on'   => 68,        // kirim jika kelembapan >= 68
        'off'  => 60,        // pulih jika kelembapan <= 60
        'label'=> '💦 KELEMBAPAN UDARA TERLALU TINGGI'
    ],
    // "di bawah ambang" = bahaya
    'tanah' => [
        'mode' => 'below',   // nilai rendah berbahaya
        'on'   => 50,        // kirim jika kelembapan tanah < 50
        'off'  => 52,        // pulih jika kelembapan tanah >= 52
        'label'=> '🌱 KELEMBAPAN TANAH RENDAH'
    ],
];

// ===================== UTIL =====================
function read_status($file) {
    $raw = @file_get_contents($file);
    $data = $raw ? json_decode($raw, true) : [];
    if (!is_array($data)) $data = [];
    // Back-compat: jika sebelumnya boolean, ubah ke bentuk baru
    foreach (['suhu','kelembaban','tanah'] as $k) {
        if (!isset($data[$k])) {
            $data[$k] = ['active' => false, 'last_sent' => 0];
        } elseif (!is_array($data[$k])) {
            $data[$k] = ['active' => (bool)$data[$k], 'last_sent' => 0];
        } else {
            $data[$k]['active']    = isset($data[$k]['active']) ? (bool)$data[$k]['active'] : false;
            $data[$k]['last_sent'] = isset($data[$k]['last_sent']) ? (int)$data[$k]['last_sent'] : 0;
        }
    }
    return $data;
}
function write_status($file, $data) {
    $tmp = $file . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    rename($tmp, $file);
}
function send_telegram($token, $chat_id, $text) {
    $url  = "https://api.telegram.org/bot{$token}/sendMessage";
    $post = ['chat_id' => $chat_id, 'text' => $text];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => http_build_query($post),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return [$res, $err];
}
function fetch_sensor() {
    $ch = curl_init("http://103.31.38.11:5000/sensor");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if (!$response || $httpCode !== 200) {
        return [null, "Gagal ambil data dari API (HTTP {$httpCode})" . ($err ? " - {$err}" : "")];
    }
    $data = json_decode($response, true);
    if (!is_array($data)) {
        return [null, "Data API tidak valid"];
    }
    // mapping sesuai struktur JSON
    $suhu       = isset($data['rata_rata']['suhu']) ? floatval($data['rata_rata']['suhu']) : null;
    $rh         = isset($data['rata_rata']['kelembapan']) ? floatval($data['rata_rata']['kelembapan']) : null;
    $soil       = isset($data['soil_moisture_2']['persen']) ? floatval($data['soil_moisture_2']['persen']) : null;

    return [[
        'suhu'       => $suhu,
        'kelembaban' => $rh,
        'tanah'      => $soil
    ], null];
}

/**
 * Jalankan 1x siklus cek -> kirim notif jika perlu.
 * Return array ringkas untuk UI/JSON.
 */
function run_once(&$status, $THRESHOLDS, $token, $chat_id, $COOLDOWN_SECONDS) {
    $out = [
        'time' => date('Y-m-d H:i:s'),
        'notif_sent' => false,
        'messages' => [],
        'values' => [],
        'errors' => [],
    ];

    list($values, $err) = fetch_sensor();
    if ($err) {
        $out['errors'][] = $err;
        return $out;
    }
    $out['values'] = $values;

    $toSend = [];

    foreach ($THRESHOLDS as $key => $cfg) {
        $v = $values[$key];
        if ($v === null) continue;

        $mode = $cfg['mode']; // 'above' atau 'below'
        $on   = $cfg['on'];
        $off  = $cfg['off'];
        $lbl  = $cfg['label'];

        $active = (bool)$status[$key]['active'];
        $last   = (int)$status[$key]['last_sent'];
        $now    = time();

        if ($mode === 'above') {
            // trigger jika naik >= on
            if (!$active && $v >= $on && ($COOLDOWN_SECONDS === 0 || ($now - $last) >= $COOLDOWN_SECONDS)) {
                $status[$key]['active'] = true;
                $status[$key]['last_sent'] = $now;
                $toSend[] = "{$lbl}: {$v}" . ($key === 'suhu' ? "°C" : "%");
            }
            // reset jika sudah turun <= off
            if ($active && $v <= $off) {
                $status[$key]['active'] = false;
            }
        } else { // 'below'
            // trigger jika turun < on
            if (!$active && $v < $on && ($COOLDOWN_SECONDS === 0 || ($now - $last) >= $COOLDOWN_SECONDS)) {
                $status[$key]['active'] = true;
                $status[$key]['last_sent'] = $now;
                $toSend[] = "{$lbl}: {$v}%";
            }
            // reset jika sudah naik >= off
            if ($active && $v >= $off) {
                $status[$key]['active'] = false;
            }
        }
    }

    if (!empty($toSend)) {
        $text = implode("\n", $toSend);
        list($res, $err) = send_telegram($token, $chat_id, $text);
        if ($err) {
            $out['errors'][] = "Gagal kirim Telegram: {$err}";
        } else {
            $out['notif_sent'] = true;
            $out['messages'][] = $text;
        }
    } else {
        $out['messages'][] = "Tidak ada notifikasi baru.";
    }

    return $out;
}

// ===================== ROUTING =====================
// CLI MODE: loop tanpa henti setiap 2 detik
if (php_sapi_name() === 'cli') {
    echo "Monitoring dimulai (CLI). Interval {$POLL_INTERVAL_SECONDS}s...\n";
    while (true) {
        // lock sederhana untuk mencegah race (jika file dipakai juga oleh web)
        $status = read_status($status_file);
        $out = run_once($status, $THRESHOLDS, $token, $chat_id, $COOLDOWN_SECONDS);
        write_status($status_file, $status);

        $ts = $out['time'];
        if (!empty($out['errors'])) {
            echo "[{$ts}] ERROR: " . implode(" | ", $out['errors']) . "\n";
        } else {
            $msg = $out['notif_sent'] ? ("Notif terkirim: " . implode(" | ", $out['messages'])) : $out['messages'][0];
            echo "[{$ts}] {$msg}\n";
        }
        sleep($POLL_INTERVAL_SECONDS);
    }
    exit;
}

// WEB AJAX MODE: ?run=1 -> JSON (dipanggil JS tiap 2 detik)
if (isset($_GET['run'])) {
    header('Content-Type: application/json');
    // pakai file lock untuk keamanan bila request beririsan
    $fp = fopen($status_file . '.lock', 'c+');
    if ($fp) {
        flock($fp, LOCK_EX);
    }
    $status = read_status($status_file);
    $out = run_once($status, $THRESHOLDS, $token, $chat_id, $COOLDOWN_SECONDS);
    write_status($status_file, $status);
    if ($fp) {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

// ===================== WEB UI (single file) =====================
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Monitor Sensor (Auto 2s, Single File)</title>
<style>
    body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#0b1220;color:#e6edf3;margin:0;padding:24px}
    .wrap{max-width:820px;margin:0 auto}
    h1{font-size:22px;margin:0 0 6px}
    .muted{color:#9aa4b2}
    .card{background:#111827;border:1px solid #1f2937;border-radius:16px;padding:16px;margin-top:16px}
    .row{display:flex;gap:12px;flex-wrap:wrap}
    .pill{background:#0e1628;border:1px solid #243247;border-radius:999px;padding:6px 10px;font-size:13px}
    .ok{border-color:#1f6feb}
    .bad{border-color:#f85149}
    button{background:#1f6feb;color:#fff;border:0;border-radius:10px;padding:10px 14px;font-weight:600;cursor:pointer}
    button:disabled{opacity:.6;cursor:not-allowed}
    pre{white-space:pre-wrap;word-break:break-word;font-size:13px;line-height:1.5;margin:0}
    .log{max-height:320px;overflow:auto;background:#0e1628;border:1px solid #243247;border-radius:12px;padding:12px}
    .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
    .kv{background:#0e1628;border:1px solid #243247;border-radius:12px;padding:10px}
    .kv b{display:block;font-size:12px;color:#9aa4b2}
    .kv span{font-size:18px}
</style>
</head>
<body>
<div class="wrap">
    <h1>Monitor Sensor</h1>
    <div class="muted">Otomatis cek & kirim Telegram tiap <b>2 detik</b> tanpa refresh halaman.</div>

    <div class="card">
        <div class="row" style="align-items:center;justify-content:space-between">
            <div class="row">
                <div id="status-pill" class="pill ok">Status: standby</div>
                <div id="time-pill" class="pill">—</div>
            </div>
            <div class="row">
                <button id="btn-toggle">Pause</button>
                <button id="btn-once" style="background:#374151">Cek Sekali</button>
            </div>
        </div>

        <div class="grid" style="margin-top:12px">
            <div class="kv"><b>Suhu (°C)</b><span id="v-suhu">—</span></div>
            <div class="kv"><b>Kelembapan Udara (%)</b><span id="v-rh">—</span></div>
            <div class="kv"><b>Kelembapan Tanah (%)</b><span id="v-soil">—</span></div>
        </div>
    </div>

    <div class="card">
        <div class="muted" style="margin-bottom:8px">Log</div>
        <div class="log" id="log"></div>
    </div>
</div>

<script>
let running = true;
let inFlight = false;
const INTERVAL_MS = <?php echo (int)$POLL_INTERVAL_SECONDS * 1000; ?>;
const el = (id)=>document.getElementById(id);
const logBox = el('log');

function appendLog(text){
    const now = new Date().toLocaleString();
    const line = `[${now}] ${text}\n`;
    logBox.textContent = line + logBox.textContent;
}

async function pollOnce(showOnly=false){
    if(inFlight) return;
    inFlight = true;
    try{
        const res = await fetch('?run=1', {cache:'no-store'});
        const j = await res.json();

        el('time-pill').textContent = j.time || '—';

        if (j.values){
            el('v-suhu').textContent = (j.values.suhu ?? '—');
            el('v-rh').textContent   = (j.values.kelembaban ?? '—');
            el('v-soil').textContent = (j.values.tanah ?? '—');
        }

        if (j.errors && j.errors.length){
            el('status-pill').textContent = 'Status: error';
            el('status-pill').classList.remove('ok');
            el('status-pill').classList.add('bad');
            appendLog('ERROR: ' + j.errors.join(' | '));
        } else {
            el('status-pill').textContent = j.notif_sent ? 'Status: notif terkirim' : 'Status: normal';
            el('status-pill').classList.toggle('bad', !!j.notif_sent);
            el('status-pill').classList.toggle('ok', !j.notif_sent);
            if (j.messages && j.messages.length){
                appendLog(j.messages.join(' | '));
            }
        }
    } catch(e){
        el('status-pill').textContent = 'Status: network error';
        el('status-pill').classList.remove('ok');
        el('status-pill').classList.add('bad');
        appendLog('Network error: ' + e);
    } finally {
        inFlight = false;
    }
}

setInterval(()=>{ if(running) pollOnce(); }, INTERVAL_MS);
pollOnce();

el('btn-toggle').onclick = ()=>{
    running = !running;
    el('btn-toggle').textContent = running ? 'Pause' : 'Resume';
    el('status-pill').textContent = running ? 'Status: running' : 'Status: paused';
};
el('btn-once').onclick = ()=>pollOnce(true);
</script>
</body>
</html>

