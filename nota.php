<?php
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// Cek apakah ID penjualan ada di URL
if (!isset($_GET['id'])) {
    header("location: riwayat_transaksi.php");
    exit();
}

$id_penjualan = $_GET['id'];

// Ambil data penjualan dari database
$queryPenjualan = mysqli_query($koneksi, "SELECT * FROM penjualan WHERE id_penjualan='$id_penjualan'");
$penjualan = mysqli_fetch_assoc($queryPenjualan);

// Ambil data detail penjualan (item yang dibeli)
$queryDetail = mysqli_query($koneksi, "SELECT dp.*, p.nama_produk FROM detail_penjualan dp JOIN produk p ON dp.id_produk = p.id_produk WHERE dp.id_penjualan='$id_penjualan'");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Transaksi - NOTA-<?= $id_penjualan; ?></title>
    <link href="css/styles.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Courier New', Courier, monospace;
        }
        .nota-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: #fff;
            border: 1px dashed #000;
        }
        .nota-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .nota-header h3, .nota-header p {
            margin: 0;
        }
        .nota-body table {
            width: 100%;
            border-collapse: collapse;
        }
        .nota-body th, .nota-body td {
            padding: 5px 0;
        }
        .item-row td {
            border-bottom: 1px dashed #ccc;
        }
        .text-right {
            text-align: right;
        }
        .nota-footer {
            margin-top: 1.5rem;
        }
        .nota-footer .total-row {
            font-weight: bold;
        }
        .nota-thanks {
            text-align: center;
            margin-top: 1.5rem;
        }
        .action-buttons {
            text-align: center;
            margin-top: 1rem;
        }
        @media print {
            body { background-color: #fff; }
            .action-buttons { display: none; }
            .nota-container { margin: 0; border: none; }
        }
    </style>
</head>
<body>

    <div class="nota-container">
        <div class="nota-header">
            <h3>Toko Sembako Kelompok 3</h3>
            <p>Jl. Raya Tlogomas No. 246, Malang</p>
            <p>---------------------------------</p>
        </div>

        <div class="nota-info">
            <table>
                <tr>
                    <td>No. Nota</td>
                    <td class="text-right">NOTA-<?= $id_penjualan; ?></td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td class="text-right"><?= date('d/m/Y H:i', strtotime($penjualan['waktu_transaksi'])); ?></td>
                </tr>
                 <tr>
                    <td>Kasir</td>
                    <td class="text-right"><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></td>
                </tr>
            </table>
        </div>

        <div class="nota-body">
            <p>---------------------------------</p>
            <table>
                <?php while ($item = mysqli_fetch_assoc($queryDetail)) { ?>
                <tr class="item-row">
                    <td colspan="2"><?= htmlspecialchars($item['nama_produk']); ?></td>
                </tr>
                <tr>
                    <td><?= $item['jumlah']; ?> x <?= number_format($item['subtotal'] / $item['jumlah']); ?></td>
                    <td class="text-right"><?= number_format($item['subtotal']); ?></td>
                </tr>
                <?php } ?>
            </table>
            <p>---------------------------------</p>
        </div>

        <div class="nota-footer">
            <table class="total-row">
                <tr>
                    <td>TOTAL</td>
                    <td class="text-right">Rp <?= number_format($penjualan['total_harga']); ?></td>
                </tr>
                <tr>
                    <td>BAYAR</td>
                    <td class="text-right">Rp <?= number_format($penjualan['uang_bayar']); ?></td>
                </tr>
                <tr>
                    <td>KEMBALI</td>
                    <td class="text-right">Rp <?= number_format($penjualan['uang_kembali']); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="nota-thanks">
            <p>--- Terima Kasih ---</p>
        </div>
    </div>

    <div class="action-buttons">
        <button class="btn btn-secondary" onclick="window.location.href='pos.php'">Kembali ke POS</button>
        <button class="btn btn-primary" onclick="window.print()">Cetak Nota</button>
    </div>

</body>
</html>