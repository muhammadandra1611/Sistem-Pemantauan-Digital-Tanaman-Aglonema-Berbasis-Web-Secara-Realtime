<?php
/******************************************************
 * Monitoring History (auto-log setiap 1 menit)
 * - Filter HANYA berdasarkan TANGGAL (YYYY-MM-DD)
 ******************************************************/
date_default_timezone_set("Asia/Jakarta");

/* ====== DB ====== */
$db_host = 'localhost';
$db_name = 'db_monitoring2';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die("Gagal konek database: " . htmlspecialchars($e->getMessage()));
}

/* ====== Helper ====== */
function toDecimalOrNull($v) {
    if ($v === null) return null;
    if (is_numeric($v)) return number_format((float)$v, 3, '.', '');
    return null;
}
function fmtOut($v) { return ($v === null || $v === '') ? '-' : $v; }

/* ====== (Tetap) Fetch API & simpan ====== */
$ch = curl_init("http://103.31.38.11:5000/sensor");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 5,
]);
$json = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($json && $http === 200) {
    $data = json_decode($json, true);
    if (is_array($data)) {
        $tsRaw = $data['timestamp'] ?? null;
        $ts = ($tsRaw && strtotime($tsRaw) !== false)
            ? date("Y-m-d H:i:s", strtotime($tsRaw))
            : date("Y-m-d H:i:s");

        $suhu  = toDecimalOrNull($data['rata_rata']['suhu'] ?? null);
        $udara = toDecimalOrNull($data['rata_rata']['kelembapan'] ?? null);

        $stmt = $pdo->prepare("
            INSERT INTO sensor_raspi (recorded_at, suhu_c, kelembapan_udara_pct, kelembapan_tanah_pct, raw_json)
            VALUES (:recorded_at, :suhu, :udara, NULL, :raw_json)
            ON DUPLICATE KEY UPDATE
              suhu_c = VALUES(suhu_c),
              kelembapan_udara_pct = VALUES(kelembapan_udara_pct),
              raw_json = VALUES(raw_json)
        ");
        $stmt->execute([
            ':recorded_at' => $ts,
            ':suhu'        => $suhu,
            ':udara'       => $udara,
            ':raw_json'    => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
}

/* ====== FILTER TANGGAL ====== */
$tz = new DateTimeZone('Asia/Jakarta');
$today = (new DateTime('now', $tz))->format('Y-m-d');

/* Ambil tanggal dari query; default = hari ini */
$date = $_GET['date'] ?? $today;

/* Validasi sederhana; jika tidak valid fallback ke hari ini */
if (!$date || strtotime($date) === false) {
    $date = $today;
}

/* Range awal–akhir untuk tanggal terpilih */
$start = date('Y-m-d 00:00:00', strtotime($date));
$end   = date('Y-m-d 00:00:00', strtotime($date . ' +1 day'));

/* ====== Query data untuk tampilan & CSV ====== */
$stmt = $pdo->prepare("
    SELECT recorded_at, suhu_c, kelembapan_udara_pct
    FROM sensor_raspi
    WHERE recorded_at >= :start AND recorded_at < :end
    ORDER BY recorded_at DESC
");
$stmt->execute([':start' => $start, ':end' => $end]);
$rows = $stmt->fetchAll();

/* ====== Export CSV (mengikuti tanggal terpilih) ====== */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=history_'.$date.'.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tanggal','Suhu (°C)','Kelembapan Udara (%)']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['recorded_at'],
            $r['suhu_c'] ?? '',
            $r['kelembapan_udara_pct'] ?? ''
        ]);
    }
    fclose($out); exit;
}

/* Helper URL export agar mempertahankan parameter date */
function exportUrlWithDate($date) {
    return '?'.http_build_query(['date'=>$date, 'export'=>'csv']);
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Monitoring History</title>
  <meta http-equiv="refresh" content="60"> <!-- auto reload tiap 60 detik -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-3">

<h3>Monitoring History (<?= htmlspecialchars($date) ?>)</h3>

<!-- Filter hanya tanggal -->
<form method="get" class="row g-2 align-items-end mb-3">
  <div class="col-sm-3">
    <label class="form-label mb-1">Pilih Tanggal</label>
    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
  </div>
  <div class="col-sm-3 d-flex gap-2">
    <button type="submit" class="btn btn-success">Terapkan</button>
    <a href="?" class="btn btn-outline-secondary">Reset</a>
    <a class="btn btn-primary" href="<?= htmlspecialchars(exportUrlWithDate($date)) ?>">Export CSV</a>
  </div>
</form>

<div class="table-responsive">
<table class="table table-striped table-sm">
  <thead class="table-light">
    <tr>
      <th>Tanggal</th>
      <th>Suhu (°C)</th>
      <th>Kelembapan Udara (%)</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="3" class="text-center text-muted">Belum ada data.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['recorded_at']) ?></td>
      <td><?= htmlspecialchars(fmtOut($r['suhu_c'])) ?></td>
      <td><?= htmlspecialchars(fmtOut($r['kelembapan_udara_pct'])) ?></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>

<p class="text-muted small">Halaman otomatis refresh tiap 1 menit.</p>
</body>
</html>
