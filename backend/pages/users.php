<?php

$koneksi = mysqli_connect("localhost", "root", "", "db_monitoring2");

if (mysqli_connect_errno()) {
    echo "Koneksi database gagal: " . mysqli_connect_error();
    exit;
}

if ($level != 'admin') {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='index.php?p=dashboard';</script>";
    exit;
}

$pesan = '';

if (isset($_POST['aksi'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $tlp = mysqli_real_escape_string($koneksi, $_POST['tlp']);
    $level_user = mysqli_real_escape_string($koneksi, $_POST['level']);
    $password = $_POST['password'];

    if ($_POST['aksi'] == 'tambah') {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO user1 (username, password, nama, alamat, tlp, level) VALUES ('$username', '$hashed_password', '$nama', '$alamat', '$tlp', '$level_user')";
            if (mysqli_query($koneksi, $query)) {
                $pesan = "<div class='alert alert-success'>Pengguna berhasil ditambahkan.</div>";
            } else {
                $pesan = "<div class='alert alert-danger'>Gagal menambahkan pengguna: " . mysqli_error($koneksi) . "</div>";
            }
        } else {
            $pesan = "<div class='alert alert-danger'>Password tidak boleh kosong untuk pengguna baru.</div>";
        }
    } elseif ($_POST['aksi'] == 'edit') {
        $username_lama = mysqli_real_escape_string($koneksi, $_POST['username_lama']);
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE user1 SET username='$username', password='$hashed_password', nama='$nama', alamat='$alamat', tlp='$tlp', level='$level_user' WHERE username='$username_lama'";
        } else {
            $query = "UPDATE user1 SET username='$username', nama='$nama', alamat='$alamat', tlp='$tlp', level='$level_user' WHERE username='$username_lama'";
        }
        if (mysqli_query($koneksi, $query)) {
            $pesan = "<div class='alert alert-success'>Data pengguna berhasil diperbarui.</div>";
        } else {
            $pesan = "<div class='alert alert-danger'>Gagal memperbarui data: " . mysqli_error($koneksi) . "</div>";
        }
    }
}

if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $username_hapus = mysqli_real_escape_string($koneksi, $_GET['username']);
    if ($username_hapus != $_SESSION['user']) {
        $query = "DELETE FROM user1 WHERE username='$username_hapus'";
        if (mysqli_query($koneksi, $query)) {
            $pesan = "<div class='alert alert-success'>Pengguna berhasil dihapus.</div>";
        } else {
            $pesan = "<div class='alert alert-danger'>Gagal menghapus pengguna.</div>";
        }
    } else {
        $pesan = "<div class='alert alert-warning'>Tidak dapat menghapus akun yang sedang login.</div>";
    }
}

$users = mysqli_query($koneksi, "SELECT * FROM user1 ORDER BY nama ASC");
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">User Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Kelola Pengguna Sistem</li>
    </ol>

    <?php echo $pesan; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Data Pengguna
            <button class="btn btn-primary btn-sm float-end" data-bs-toggle="modal" data-bs-target="#userModal" onclick="tambahUser()">
                <i class="fas fa-plus"></i> Tambah Pengguna
            </button>
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Alamat</th>
                        <th>Telepon</th>
                        <th>Level</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['nama']) ?></td>
                        <td><?= htmlspecialchars($user['alamat']) ?></td>
                        <td><?= htmlspecialchars($user['tlp']) ?></td>
                        <td><?= htmlspecialchars($user['level']) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editUser('<?= htmlspecialchars(addslashes($user['username'])) ?>', '<?= htmlspecialchars(addslashes($user['nama'])) ?>', '<?= htmlspecialchars(addslashes($user['alamat'])) ?>', '<?= htmlspecialchars(addslashes($user['tlp'])) ?>', '<?= htmlspecialchars($user['level']) ?>')" data-bs-toggle="modal" data-bs-target="#userModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="index.php?p=users&aksi=hapus&username=<?= urlencode($user['username']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Form Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="index.php?p=users">
                <div class="modal-body">
                    <input type="hidden" name="aksi" id="aksi">
                    <input type="hidden" name="username_lama" id="username_lama">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                     <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted" id="passHelp">Kosongkan jika tidak ingin mengubah password.</small>
                    </div>
                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <input type="text" class="form-control" id="alamat" name="alamat">
                    </div>
                    <div class="mb-3">
                        <label for="tlp" class="form-label">Telepon</label>
                        <input type="text" class="form-control" id="tlp" name="tlp">
                    </div>
                    <div class="mb-3">
                        <label for="level" class="form-label">Level</label>
                        <select class="form-select" id="level" name="level" required>
                            <option value="admin">Admin</option>
                            <option value="pengguna">Pengguna</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', event => {
        const datatablesSimple = document.getElementById('datatablesSimple');
        if (datatablesSimple) {
            new simpleDatatables.DataTable(datatablesSimple);
        }
    });

    function tambahUser() {
        document.getElementById('userModalLabel').innerText = 'Tambah Pengguna Baru';
        document.getElementById('aksi').value = 'tambah';
        document.getElementById('username_lama').value = '';
        document.getElementById('username').value = '';
        document.getElementById('username').readOnly = false;
        document.getElementById('nama').value = '';
        document.getElementById('password').value = '';
        document.getElementById('password').required = true;
        document.getElementById('passHelp').style.display = 'none';
        document.getElementById('alamat').value = '';
        document.getElementById('tlp').value = '';
        document.getElementById('level').value = 'pengguna';
    }

    function editUser(username, nama, alamat, tlp, level) {
        document.getElementById('userModalLabel').innerText = 'Edit Data Pengguna';
        document.getElementById('aksi').value = 'edit';
        document.getElementById('username_lama').value = username;
        document.getElementById('username').value = username;
        document.getElementById('username').readOnly = false; // Bolehkan edit username jika perlu
        document.getElementById('nama').value = nama;
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('passHelp').style.display = 'block';
        document.getElementById('alamat').value = alamat;
        document.getElementById('tlp').value = tlp;
        document.getElementById('level').value = level;
    }
</script>