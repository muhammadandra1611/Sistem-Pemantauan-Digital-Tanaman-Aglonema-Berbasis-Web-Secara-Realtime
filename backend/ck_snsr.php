<!DOCTYPE html>
<html>
<head>
    <title>Monitor Sensor</title>
    <script>
    function loadData() {
        fetch("cek_sensor.php") // file PHP kamu
            .then(res => res.text())
            .then(data => {
                document.getElementById("result").innerHTML = data;
            });
    }

    setInterval(loadData, 2000); // panggil tiap 2 detik
    window.onload = loadData;
    </script>
</head>
<body>
    <h2>Monitoring Sensor</h2>
    <div id="result">Loading...</div>
</body>
</html>
