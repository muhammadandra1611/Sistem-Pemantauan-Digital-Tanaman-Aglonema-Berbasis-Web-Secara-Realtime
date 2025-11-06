<?php
session_start();

// Jika sudah login, langsung masuk dashboard
if (!empty($_SESSION['user'])) {
    header('Location: ./backend/index.php?p=dashboard');
    exit;
}

// Koneksi DB 
include './assets/function.php';
$air = new klass_air;
$koneksi = $air->koneksi();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Login - Website Monitoring</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23); backdrop-filter: blur(10px); background: rgba(255,255,255,0.9); }
        .card-header { background: transparent; border-bottom: none; padding-top: 30px; }
        .card-header h3 { color: #333; font-weight: 600; font-size: 24px; }
        .form-floating input { border-radius: 10px; border: 1px solid #ddd; padding: 15px; transition: all 0.3s ease; }
        .form-floating input:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 10px; padding: 12px 30px; font-weight: 500; transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .alert { border-radius: 10px; margin-bottom: 20px; }
        #layoutAuthentication_footer { position: fixed; bottom: 0; width: 100%; }
        @media (max-width: 576px) { .container { padding: 0 15px; } .card { margin: 15px; } }
    </style>
</head>
<body>
<div id="layoutAuthentication">
    <div id="layoutAuthentication_content">
        <main>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-5">
                        <div class="card shadow-lg border-0 rounded-lg mt-5">
                            <div class="card-header text-center">
                                <i class="fas fa-user-circle fa-3x mb-3" style="color: #667eea;"></i>
                                <h3 class="font-weight-light">Welcome!</h3>
                                <p class="text-muted">Please login to your account</p>
                            </div>
                            <div class="card-body">
                                <?php
                                if (isset($_POST['tombol'])) {
                                    $username = trim($_POST['user'] ?? '');
                                    $password = $_POST['password'] ?? '';

                                    // Prepared statement
                                    $stmt = mysqli_prepare($koneksi, "SELECT username, password, nama, level FROM user1 WHERE username = ? LIMIT 1");
                                    mysqli_stmt_bind_param($stmt, "s", $username);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    $row = mysqli_fetch_assoc($result);

                                    if ($row) {
                                        if (password_verify($password, $row['password'])) {
                                            session_regenerate_id(true);
                                            $_SESSION['user']  = $row['username'];
                                            $_SESSION['nama']  = $row['nama'];
                                            $_SESSION['level'] = $row['level'];

                                            // Redirect sesuai level (ubah target sesuai kebutuhanmu)
                                            if ($row['level'] === 'admin') {
                                                header("Location: ./backend/index.php?p=dashboard");
                                            } else { // pengguna / lainnya
                                                header("Location: ./backend/index.php?p=dashboard");
                                            }
                                            exit;
                                        } else {
                                            echo '<div class="alert alert-danger alert-dismissible fade show">
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                    <i class="fas fa-exclamation-circle me-2"></i>Invalid password. Please try again.
                                                  </div>';
                                        }
                                    } else {
                                        echo '<div class="alert alert-danger alert-dismissible fade show">
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                <i class="fas fa-exclamation-circle me-2"></i>Username not found.
                                              </div>';
                                    }
                                }
                                ?>
                                <form method="post" class="needs-validation" novalidate>
                                    <div class="form-floating mb-4">
                                        <input class="form-control" id="inputUser" type="text" placeholder="Username" name="user" required/>
                                        <label for="inputUser"><i class="fas fa-user me-2"></i>Username</label>
                                    </div>
                                    <div class="form-floating mb-4">
                                        <input class="form-control" id="inputPassword" type="password" placeholder="Password" name="password" required/>
                                        <label for="inputPassword"><i class="fas fa-lock me-2"></i>Password</label>
                                    </div>
                                    <div class="d-grid gap-2 mb-2">
                                        <input type="submit" name="tombol" value="Sign In" class="btn btn-primary btn-lg">
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer text-center py-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div id="layoutAuthentication_footer">
        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">&copy;</div>
                </div>
            </div>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
</body>
</html>
