<?php
$koneksi = mysqli_connect("localhost", "user2", "12345678", "db_monitoring2");
if (mysqli_connect_errno()) {
    echo json_encode(["error" => "Koneksi gagal"]);
    exit();
}

$data = [];

// Ambil dari tb_sensor_dht22
$dht22 = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM tb_sensor_dht22 ORDER BY waktu DESC LIMIT 1"));
$data['temperature'] = $dht22['suhu'] ?? null;
$data['air_quality'] = $dht22['kelembaban'] ?? null;

// Ambil dari sensor_data
$soil = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM sensor_data ORDER BY waktu DESC LIMIT 1"));
$data['soil_moisture'] = $soil['kelembaban_tanah'] ?? null;

header('Content-Type: application/json');
echo json_encode($data);
?>
