<?php
// Koneksi ke database
$koneksi = mysqli_connect("localhost", "root", "", "db_monitoring2");

// Ambil data dari tabel DHT22 (untuk menampilkan nilai awal di kartu)
$result2 = mysqli_query($koneksi, "SELECT * FROM tb_sensor_dht22 ORDER BY waktu DESC LIMIT 10");
$dht22 = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM tb_sensor_dht22 ORDER BY waktu DESC LIMIT 1"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Sensor</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { margin-bottom: 20px; }
        h2 p { margin: 0; font-size: 20px; }
    </style>
</head>
<body class="container mt-4">
    <h2 class="mb-4 text-center">Monitoring Sensor</h2>
    <hr>

    <div class="row">
        <!-- Kolom 1: Suhu -->
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-header">🌡️ Suhu</div>
                <div class="card-body">
                    <h2 id="temperature"><p><?= $dht22['suhu'] ?? '--'; ?> °C</p></h2>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <canvas id="temperatureChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Kolom 2: Kelembapan Udara -->
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-header">💨 Kelembapan Udara</div>
                <div class="card-body">
                    <h2 id="air-quality"><p><?= $dht22['kelembaban'] ?? '--'; ?> %</p></h2>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <canvas id="airQualityChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

<script>
// Context untuk chart
const temperatureCtx = document.getElementById('temperatureChart').getContext('2d');
const airQualityCtx = document.getElementById('airQualityChart').getContext('2d');

// Chart Suhu
const temperatureChart = new Chart(temperatureCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Suhu (°C)',
            data: [],
            borderColor: 'red',
            backgroundColor: 'rgba(255, 0, 0, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        scales: {
            x: { title: { display: true, text: 'Waktu' }},
            y: { beginAtZero: true }
        }
    }
});

// Chart Kelembapan Udara
const airQualityChart = new Chart(airQualityCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Kelembapan Udara (%)',
            data: [],
            borderColor: 'blue',
            backgroundColor: 'rgba(0, 0, 255, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        scales: {
            x: { title: { display: true, text: 'Waktu' }},
            y: { beginAtZero: true, max: 100 }
        }
    }
});

// Update nilai kartu
function updateSensorValues(data) {
    const suhu = data?.rata_rata?.suhu ?? '--';
    const kelembapan = data?.rata_rata?.kelembapan ?? '--';
    $('#temperature').html(`<p>${suhu} °C</p>`);
    $('#air-quality').html(`<p>${kelembapan} %</p>`);
}

// Update grafik
function updateCharts(data) {
    const time = new Date().toLocaleTimeString();

    // Temperature
    const suhu = data?.rata_rata?.suhu ?? null;
    if (suhu !== null) {
        temperatureChart.data.labels.push(time);
        temperatureChart.data.datasets[0].data.push(suhu);
        if (temperatureChart.data.labels.length > 10) {
            temperatureChart.data.labels.shift();
            temperatureChart.data.datasets[0].data.shift();
        }
        temperatureChart.update();
    }

    // Air Humidity
    const kelembapan = data?.rata_rata?.kelembapan ?? null;
    if (kelembapan !== null) {
        airQualityChart.data.labels.push(time);
        airQualityChart.data.datasets[0].data.push(kelembapan);
        if (airQualityChart.data.labels.length > 10) {
            airQualityChart.data.labels.shift();
            airQualityChart.data.datasets[0].data.shift();
        }
        airQualityChart.update();
    }
}

// Ambil data dari API & perbarui UI
function fetchAndUpdate() {
    fetch("http://103.31.38.11:5000/sensor")
        .then(response => response.json())
        .then(data => {
            updateSensorValues(data);
            updateCharts(data);
        })
        .catch(error => {
            console.error('Gagal mengambil data:', error);
        });
}

$(document).ready(function () {
    fetchAndUpdate();
    setInterval(fetchAndUpdate, 5000);
});
</script>
</body>
</html>
