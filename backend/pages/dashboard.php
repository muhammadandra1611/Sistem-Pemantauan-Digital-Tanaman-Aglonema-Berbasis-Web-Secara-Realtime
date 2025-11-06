<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  .hero-wrap{
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0,0,0,.06);
    padding: 28px 28px;
  }
  .hero-title{
    font-weight: 800;
    font-size: clamp(28px, 3.6vw, 40px);
    line-height: 1.15;
    margin-bottom: 10px;
  }
  .brand-accent{ color:#ff0000; } /* 🔴 sudah diganti jadi merah */
  .hero-desc{ color:#50545b; text-align: justify; }
  .hero-img{
    width:100%; height: 280px; object-fit: cover;
    border-radius: 26px; box-shadow: 0 8px 24px rgba(0,0,0,.08);
  }

  .section-title{ font-weight: 800; color:#4d3d3a; }
  .section-subtitle{ color:#7a8089; }

  .param-card{
    border: 0; border-radius: 18px; box-shadow: 0 8px 24px rgba(0,0,0,.06);
    padding: 18px 18px; height: 100%; background: #fff;
  }
  .param-icon{
    width: 42px; height: 42px; display: inline-flex; align-items:center; justify-content:center;
    border-radius: 12px; margin-right: 10px;
  }
  .param-title{ margin:0; font-weight:700; }
  .param-text{ color:#626970; margin:6px 0 0; }

  .i-temp{ background:#e9f2ff; color:#246bff;}
  .i-turb{ background:#eaf7ef; color:#2eb872;}
  .mb-24{ margin-bottom:24px;}
</style>

<div class="container-fluid px-4">
  <!-- HERO -->
  <div class="hero-wrap mb-24">
    <div class="row g-4 align-items-start">
      <div class="col-lg-7">
        <h1 class="hero-title">Selamat Datang di <span class="brand-accent"><br>Monitoring Tanaman Aglonema</span></h1>
        <p class="hero-desc">
          Sistem <strong>Monitoring Tanaman Aglonema</strong> merupakan website berbasis IoT
          yang dirancang untuk membantu pemilik tanaman dalam memantau kondisi lingkungan
          secara real-time. Dengan sistem ini, parameter penting seperti suhu dan kelembapan
          udara dapat terpantau otomatis, sehingga pemeliharaan tanaman menjadi lebih efektif.
        </p>
        <p class="hero-desc">
          Tujuan utama dari sistem ini adalah menjaga lingkungan tumbuh yang optimal agar
          tanaman aglonema tetap sehat, tumbuh subur, serta memiliki kualitas hias yang baik.
          Data yang terkumpul dapat dilihat melalui antarmuka website secara mudah, kapan saja
          dan di mana saja.
        </p>
      </div>
      <div class="col-lg-5">
        <img class="hero-img" src="../assets/img/bg-login.jpg" alt="Tanaman Aglonema">
      </div>
    </div>
  </div>

  <!-- PARAMETER -->
  <div class="text-center mb-2">
    <h3 class="section-title">Parameter</h3>
    <p class="section-subtitle">Parameter utama yang dipantau dalam sistem Monitoring Tanaman Aglonema.</p>
  </div>

  <div class="row g-3 justify-content-center">
    <!-- Suhu -->
    <div class="col-md-6 col-xl-4">
      <div class="param-card">
        <div class="d-flex align-items-center">
          <span class="param-icon i-temp"><i class="fa-solid fa-temperature-half"></i></span>
          <h5 class="param-title">Suhu</h5>
        </div>
        <p class="param-text">
          Suhu optimal untuk pertumbuhan aglonema berada pada kisaran <strong>25–32°C</strong>.
          Jika terlalu rendah atau terlalu tinggi, dapat menghambat pertumbuhan dan menimbulkan stres pada tanaman.
        </p>
      </div>
    </div>

    <!-- Kelembapan Udara -->
    <div class="col-md-6 col-xl-4">
      <div class="param-card">
        <div class="d-flex align-items-center">
          <span class="param-icon i-turb"><i class="fa-solid fa-water"></i></span>
          <h5 class="param-title">Kelembapan Udara</h5>
        </div>
        <p class="param-text">
          Kelembapan udara yang ideal berkisar antara <strong>60–80%</strong>.
          Rentang ini menjaga kondisi lingkungan tetap stabil, sehingga tanaman aglonema terhindar dari kekeringan maupun kelembapan berlebih.
        </p>
      </div>
    </div>
  </div>
</div>
