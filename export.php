<?php
// Memuat file koneksi dan memastikan pengguna sudah login
require 'koneksi.php';
if (!isset($_SESSION['username'])) {
    // Jika tidak ada sesi, hentikan eksekusi
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// Menangkap rentang tanggal dari URL (GET)
$tanggal_mulai = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$tanggal_selesai = isset($_GET['end']) ? $_GET['end'] : date('Y-m-t');

// Menyiapkan header file CSV
$nama_file = "Laporan_Keuangan_" . $tanggal_mulai . "_sd_" . $tanggal_selesai . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $nama_file);

// Membuka output stream PHP untuk menulis file
$output = fopen('php://output', 'w');

// Menulis baris header untuk CSV
fputcsv($output, [
    'Laporan Keuangan Toko Sembako Kelompok 3'
]);
fputcsv($output, [
    'Periode:', 
    date('d M Y', strtotime($tanggal_mulai)) . " - " . date('d M Y', strtotime($tanggal_selesai))
]);
fputcsv($output, []); // Baris kosong sebagai pemisah

// Menulis header tabel untuk Performa Produk
fputcsv($output, [
    'Nama Produk', 
    'Jumlah Terjual', 
    'Total Pendapatan', 
    'Total Modal', 
    'Total Laba'
]);

// Query untuk mengambil data performa produk (sama seperti di laporan.php)
$queryPerformaProduk = mysqli_query($koneksi, "
    SELECT 
        p.nama_produk, 
        SUM(dp.jumlah) AS jumlah_terjual,
        SUM(dp.subtotal) AS total_pendapatan_produk,
        SUM(dp.jumlah * p.harga_beli) AS total_modal_produk,
        (SUM(dp.subtotal) - SUM(dp.jumlah * p.harga_beli)) AS total_laba_produk
    FROM detail_penjualan dp
    JOIN produk p ON dp.id_produk = p.id_produk
    JOIN penjualan pj ON dp.id_penjualan = pj.id_penjualan
    WHERE DATE(pj.waktu_transaksi) BETWEEN '$tanggal_mulai' AND '$tanggal_selesai'
    GROUP BY p.id_produk
    ORDER BY total_laba_produk DESC
");

// Menulis setiap baris data produk ke file CSV
while ($row = mysqli_fetch_assoc($queryPerformaProduk)) {
    fputcsv($output, [
        $row['nama_produk'],
        $row['jumlah_terjual'],
        $row['total_pendapatan_produk'],
        $row['total_modal_produk'],
        $row['total_laba_produk']
    ]);
}

// Menutup file stream
fclose($output);
exit();

?>