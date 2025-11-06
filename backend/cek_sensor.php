<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$token   = "TOKEN_BOT";
$chat_id = "CHAT_ID";
$status_file = __DIR__ . '/status_cache.json';

while (true) {
    $status = file_exists($status_file) ? json_decode(file_get_contents($status_file), true) : [];
    if (!is_array($status)) $status = [];

    $notif = "";
    $send  = false;

    // ========== FETCH DATA SENSOR ==========
    $ch = curl_init("http://103.31.38.11:5000/sensor");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $httpCode === 200) {
        $data = json_decode($response, true);

        if (is_array($data)) {
            $suhu             = isset($data['rata_rata']['suhu']) ? floatval($data['rata_rata']['suhu']) : null;
            $kelembaban       = isset($data['rata_rata']['kelembapan']) ? floatval($data['rata_rata']['kelembapan']) : null;
            $kelembaban_tanah = isset($data['soil_moisture_2']['persen']) ? floatval($data['soil_moisture_2']['persen']) : null;

            // ====== CEK SUHU ======
            if ($suhu !== null) {
                if ($suhu >= 22 && empty($status['suhu'])) {
                    $notif .= "🔥 SUHU TERLALU TINGGI: {$suhu}°C\n";
                    $status['suhu'] = true;
                    $send = true;
                } elseif ($suhu <= 31) {
                    $status['suhu'] = false;
                }
            }

            // ====== CEK KELEMBAPAN UDARA ======
            if ($kelembaban !== null) {
                if ($kelembaban >= 30 && empty($status['kelembaban'])) {
                    $notif .= "💦 KELEMBAPAN UDARA TERLALU TINGGI: {$kelembaban}%\n";
                    $status['kelembaban'] = true;
                    $send = true;
                } elseif ($kelembaban <= 68) {
                    $status['kelembaban'] = false;
                }
            }

            // ====== CEK KELEMBAPAN TANAH ======
            if ($kelembaban_tanah !== null) {
                if ($kelembaban_tanah < 50 && empty($status['tanah'])) {
                    $notif .= "🌱 KELEMBAPAN TANAH RENDAH: {$kelembaban_tanah}%\n";
                    $status['tanah'] = true;
                    $send = true;
                } elseif ($kelembaban_tanah >= 52) {
                    $status['tanah'] = false;
                }
            }

            // ====== KIRIM NOTIF JIKA ADA ======
            if ($send && $notif) {
                $url  = "https://api.telegram.org/bot$token/sendMessage";
                $post = [
                    'chat_id' => $chat_id,
                    'text'    => $notif
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $res = curl_exec($ch);
                curl_close($ch);

                echo "✅ Notifikasi dikirim: " . nl2br($notif) . "\n";
            } else {
                echo "⏳ Tidak ada notifikasi baru.\n";
            }

            file_put_contents($status_file, json_encode($status));
        }
    }

    // tunggu 2 detik sebelum cek lagi
    sleep(2);
}
