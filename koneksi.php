<?php
// TAMBAHKAN DUA BARIS INI
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Konfigurasi koneksi database
$host = "localhost";    // Nama host database (biasanya 'localhost')
$user = "root";         // Username database
$pass = "";             // Password database (kosongkan jika tidak ada)
$db   = "db_sembako_kelompok3"; // Nama database

// Membuat koneksi ke database
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Memeriksa apakah koneksi berhasil
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Mengaktifkan session
session_start();
?>