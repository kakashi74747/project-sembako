<?php
// Memanggil file koneksi untuk memulai session
require 'koneksi.php';

// Menghancurkan semua data session yang aktif
session_destroy();

// Mengarahkan (redirect) pengguna kembali ke halaman login
header("location: login.php");
exit();
?>