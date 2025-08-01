<?php
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// Logika untuk menghapus transaksi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['hapus_transaksi'])) {
    $id_penjualan_hapus = $_POST['id_penjualan'];
    $query_hapus_detail = "DELETE FROM detail_penjualan WHERE id_penjualan = '$id_penjualan_hapus'";
    mysqli_query($koneksi, $query_hapus_detail);
    $query_hapus_penjualan = "DELETE FROM penjualan WHERE id_penjualan = '$id_penjualan_hapus'";
    if (mysqli_query($koneksi, $query_hapus_penjualan)) {
        $_SESSION['pesan_sukses'] = "Transaksi berhasil dihapus.";
    } else {
        $_SESSION['pesan_error'] = "Gagal menghapus transaksi.";
    }
    header("Location: riwayat_transaksi.php");
    exit();
}

// Logika untuk filter urutan
$urutan = isset($_GET['urutan']) && $_GET['urutan'] == 'terlama' ? 'ASC' : 'DESC';
$query_urutan = "SELECT * FROM penjualan ORDER BY waktu_transaksi $urutan";

// Mengambil semua data penjualan beserta detailnya
$semua_transaksi = [];
$queryPenjualan = mysqli_query($koneksi, $query_urutan);
while($row = mysqli_fetch_assoc($queryPenjualan)){
    $id_penjualan = $row['id_penjualan'];
    $detail_pembelian = [];
    $query_detail = mysqli_query($koneksi, "SELECT dp.*, p.nama_produk FROM detail_penjualan dp JOIN produk p ON dp.id_produk = p.id_produk WHERE dp.id_penjualan='$id_penjualan'");
    while($detail_row = mysqli_fetch_assoc($query_detail)){
        $detail_pembelian[] = $detail_row;
    }
    $row['details'] = $detail_pembelian;
    $semua_transaksi[] = $row;
}

$pesan = '';
if(isset($_SESSION['pesan_sukses'])){
    $pesan = "<div class='alert alert-success' role='alert'>" . $_SESSION['pesan_sukses'] . "</div>";
    unset($_SESSION['pesan_sukses']);
} elseif(isset($_SESSION['pesan_error'])){
    $pesan = "<div class='alert alert-danger' role='alert'>" . $_SESSION['pesan_error'] . "</div>";
    unset($_SESSION['pesan_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Riwayat Transaksi - Kasir Sembako</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
            /* Style baru untuk modal nota */
            .modal-nota-header { background-color: #0d6efd; color: white; padding: 1rem; }
            .modal-nota-header h5 { margin: 0; }
            .modal-nota-body { padding: 1.5rem; }
            .list-group-item { border: none; padding: 0.75rem 0; }
            .list-group-item .fw-bold { color: #333; }
            .summary-item { display: flex; justify-content: space-between; padding: 0.5rem 0; }
        </style>
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <a class="navbar-brand ps-3" href="dashbord.php">Sembako Kelompok 3</a>
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <ul class="navbar-nav ms-auto me-3 me-lg-4">
                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown"><i class="fas fa-user fa-fw"></i> <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></a><ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown"><li><a class="dropdown-item" href="logout.php">Logout</a></li></ul></li>
            </ul>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Menu</div>
                            <a class="nav-link" href="dashbord.php"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Dashboard</a>
                            <a class="nav-link" href="pos.php"><div class="sb-nav-link-icon"><i class="fas fa-cash-register"></i></div>Transaksi (POS)</a>
                            <a class="nav-link" href="stok_masuk.php"><div class="sb-nav-link-icon"><i class="fas fa-plus-square"></i></div>Stok Masuk</a>
                            <a class="nav-link" href="pelanggan.php"><div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>Kelola Pelanggan</a>
                            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseData" aria-expanded="true"><div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>Kelola Data<div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div></a>
                            <div class="collapse show" id="collapseData" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="produk.php">Data Produk</a>
                                    <a class="nav-link" href="supplier.php">Data Supplier</a>
                                    <a class="nav-link active" href="riwayat_transaksi.php">Riwayat Transaksi</a>
                                </nav>
                            </div>
                            <a class="nav-link" href="laporan.php"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Laporan Keuangan</a>
                            <a class="nav-link" href="logout.php"><div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>Logout</a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer"><div class="small">Logged in as:</div><?= 
                    ($_SESSION['username']); ?></div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Riwayat Transaksi</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="dashbord.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Riwayat Transaksi</li>
                        </ol>
                        <?= $pesan; ?>

                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-history me-1"></i>Semua Transaksi Penjualan</span>
                                <form method="GET" action="riwayat_transaksi.php" class="d-flex align-items-center m-0">
                                    <label for="urutan" class="form-label me-2 mb-0 small">Urutkan:</label>
                                    <select name="urutan" id="urutan" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                        <option value="terbaru" <?= ($urutan == 'DESC') ? 'selected' : ''; ?>>Terbaru</option>
                                        <option value="terlama" <?= ($urutan == 'ASC') ? 'selected' : ''; ?>>Terlama</option>
                                    </select>
                                </form>
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>No. Nota</th>
                                            <th>Waktu</th>
                                            <th>Total</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($semua_transaksi as $t) { ?>
                                        <tr>
                                            <td>NOTA-<?= $t['id_penjualan']; ?></td>
                                            <td><?= date('d M Y, H:i', strtotime($t['waktu_transaksi'])); ?></td>
                                            <td>Rp <?= number_format($t['total_harga']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#notaModal<?= $t['id_penjualan']; ?>">Lihat Nota</button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $t['id_penjualan']; ?>">Hapus</button>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto"><div class="container-fluid px-4"><div class="d-flex align-items-center justify-content-between small"><div class="text-muted">Copyright &copy; Sembako Kelompok 3 2023</div></div></div></footer>
            </div>
        </div>

        <?php foreach ($semua_transaksi as $t) { ?>
        <div class="modal fade" id="notaModal<?= $t['id_penjualan']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-nota-header"><h5 class="modal-title">Detail Transaksi</h5></div><div class="modal-nota-body"><div class="d-flex justify-content-between mb-3"><span class="text-muted">No. Nota</span><span class="fw-bold">NOTA-<?= $t['id_penjualan']; ?></span></div><div class="d-flex justify-content-between mb-4"><span class="text-muted">Tanggal</span><span class="fw-bold"><?= date('d M Y, H:i', strtotime($t['waktu_transaksi'])); ?></span></div><h6 class="text-muted border-bottom pb-2 mb-3">ITEM PEMBELIAN</h6><ul class="list-group list-group-flush"><?php foreach ($t['details'] as $detail) { ?><li class="list-group-item d-flex justify-content-between"><div><div class="fw-bold"><?= htmlspecialchars($detail['nama_produk']); ?></div><small class="text-muted"><?= $detail['jumlah']; ?> x Rp <?= number_format($detail['subtotal'] / $detail['jumlah']); ?></small></div><div class="fw-bold">Rp <?= number_format($detail['subtotal']); ?></div></li><?php } ?></ul><hr><div class="summary-item"><span>Total</span><span class="fw-bold fs-5">Rp <?= number_format($t['total_harga']); ?></span></div><div class="summary-item"><span class="text-muted">Bayar</span><span class="text-muted">Rp <?= number_format($t['uang_bayar']); ?></span></div><div class="summary-item"><span class="text-muted">Kembali</span><span class="text-muted">Rp <?= number_format($t['uang_kembali']); ?></span></div></div><div class="modal-footer"><a href="nota.php?id=<?= $t['id_penjualan']; ?>" target="_blank" class="btn btn-outline-primary"><i class="fas fa-print me-2"></i>Cetak Versi Struk</a><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div>
        </div>
        <div class="modal fade" id="hapusModal<?= $t['id_penjualan']; ?>" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="riwayat_transaksi.php"><div class="modal-body"><p>Yakin hapus nota <strong>NOTA-<?= $t['id_penjualan']; ?></strong> secara permanen?</p><input type="hidden" name="id_penjualan" value="<?= $t['id_penjualan']; ?>"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="hapus_transaksi" class="btn btn-danger">Ya, Hapus</button></div></form></div></div>
        </div>
        <?php } ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script>
            window.addEventListener('DOMContentLoaded', event => {
                const datatablesSimple = document.getElementById('datatablesSimple');
                if (datatablesSimple) { new simpleDatatables.DataTable(datatablesSimple); }
            });
        </script>
    </body>
</html>