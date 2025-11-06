<?php
session_start();

//hapus semua session
session_destroy();

//redirect ke halaman login
echo "<script>window.location.replace('../index.php')</script>";
