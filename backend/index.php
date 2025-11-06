<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

$nama  = $_SESSION['nama']  ?? $_SESSION['user'];
$level = $_SESSION['level'] ?? '';
$icon  = ($level === 'admin') ? ' 👑' : ' 👤';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Dashboard - Monitoring Aglonema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3 fw-bold" href="index.php">MONITORING<br>AGLONEMA <i class="fas fa-leaf me-2"></i></a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <ul class="navbar-nav ms-auto me-3 me-lg-4">
   
        <li class="nav-item">
            <a class="nav-link" href="logout.php">
                <button class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt me-1"></i> Logout</button>
            </a>
        </li>
    </ul>
</nav>

<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    <div class="sb-sidenav-menu-heading p--0">Menu</div>
                    <a class="nav-link mt-0" href="index.php?p=dashboard">
                        <div class="sb-nav-link-icon"><i class="fas fa-home fa-fw"></i></div>
                        Dashboard
                    </a>
                    <a class="nav-link" href="index.php?p=monitoring">
                        <div class="sb-nav-link-icon"><i class="fas fa-chart-line fa-fw"></i></div>
                        Monitoring
                    </a>
                    <a class="nav-link" href="index.php?p=history">
                        <div class="sb-nav-link-icon"><i class="fas fa-history fa-fw"></i></div>
                        History
                    </a>
                     <?php if ($level == 'admin'): ?>
                            <a class="nav-link" href="index.php?p=users">
                                <div class="sb-nav-link-icon"><i class="fas fa-users-cog fa-fw"></i></div>
                                User Management
                            </a>
                            <?php endif; ?>
                </div>
                     <li class="nav-item me-2 d-none d-md-block" style="margin-top: 435px; padding-left:10px">
                        <span class="navbar-text text-white-50">Logged in as, <?= htmlspecialchars($nama) . $icon; ?></span>
                    </li>
            </div>
            <div class="sb-sidenav-footer">
                <div class="small"><i class="fa-solid fa-user fa-flip text-white"></i> Logged in as: <?= htmlspecialchars($nama); ?></div>
                <div class="small"><i class="fa-solid fa-heart fa-flip text-danger"></i> <?= htmlspecialchars($_SESSION['user']).' ('.htmlspecialchars($level).')'; ?></div>
            </div>
        </nav>
    </div>

    <div id="layoutSidenav_content">
        <main class="p-3">
            <?php
            $page = isset($_GET['p']) ? $_GET['p'] : 'dashboard';
            $file = "pages/{$page}.php";

          
            $adminOnly = ['manage_users']; 
            if (in_array($page, $adminOnly, true) && $level !== 'admin') {
                http_response_code(403);
                echo '<div class="alert alert-warning">Forbidden: Admin only.</div>';
            } else {
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo '<div class="alert alert-info">Welcome to Dashboard.</div>';
                }
            }
            ?>
        </main>
        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; Monitoring Aglonema 2025</div>
                </div>
            </div>
        </footer>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', event => {
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', e => {
            e.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
