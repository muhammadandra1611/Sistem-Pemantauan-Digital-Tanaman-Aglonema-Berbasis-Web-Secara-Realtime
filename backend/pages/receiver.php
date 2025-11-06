<?php
// Koneksi langsung ke database
$koneksi = mysqli_connect("localhost", "user2", "12345678", "db_monitoring2");


// Cek koneksi
if (mysqli_connect_errno()) {
    echo "Gagal koneksi: " . mysqli_connect_error();
    exit();
}

// Ambil data dari URL
$suhu = $_GET['suhu'] ?? null;
$kelembaban = $_GET['kelembaban'] ?? null;
$tanah = $_GET['tanah'] ?? null;

// Validasi
if ($suhu !== null && $kelembaban !== null && $tanah !== null) {
    $query = "INSERT INTO sensor_data (suhu, kelembaban, kelembaban_tanah, waktu) 
              VALUES ('$suhu', '$kelembaban', '$tanah', NOW())";
    mysqli_query($koneksi, $query);

    echo "Data berhasil disimpan";
} else {
    echo "Parameter tidak lengkap";
}
?>
