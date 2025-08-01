<?php
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// =================================================================
// KUMPULAN DATA UNTUK KARTU INFO
// =================================================================
$query_total_produk = mysqli_query($koneksi, "SELECT COUNT(id_produk) AS jumlah FROM produk");
$total_produk = mysqli_fetch_assoc($query_total_produk)['jumlah'];
$tanggal_hari_ini = date('Y-m-d');
$query_transaksi_hari_ini = mysqli_query($koneksi, "SELECT COUNT(id_penjualan) AS jumlah FROM penjualan WHERE DATE(waktu_transaksi) = '$tanggal_hari_ini'");
$transaksi_hari_ini = mysqli_fetch_assoc($query_transaksi_hari_ini)['jumlah'];
$query_pendapatan_hari_ini = mysqli_query($koneksi, "SELECT SUM(total_harga) AS total FROM penjualan WHERE DATE(waktu_transaksi) = '$tanggal_hari_ini'");
$pendapatan_hari_ini = mysqli_fetch_assoc($query_pendapatan_hari_ini)['total'] ?? 0;
$query_produk_terlaris = mysqli_query($koneksi, "SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual FROM detail_penjualan dp JOIN produk p ON dp.id_produk = p.id_produk GROUP BY dp.id_produk ORDER BY total_terjual DESC LIMIT 1");
$data_produk_terlaris = mysqli_fetch_assoc($query_produk_terlaris);
$produk_terlaris = $data_produk_terlaris ? $data_produk_terlaris['nama_produk'] . " (" . $data_produk_terlaris['total_terjual'] . " terjual)" : "Belum ada";

// =================================================================
// KUMPULAN DATA UNTUK TABEL SEMUA TRANSAKSI & MODAL NOTA
// =================================================================
$urutan = isset($_GET['urutan']) && $_GET['urutan'] == 'terlama' ? 'ASC' : 'DESC';
$query_urutan = "SELECT * FROM penjualan ORDER BY waktu_transaksi $urutan";
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

// =================================================================
// KUMPULAN DATA UNTUK GRAFIK PENDAPATAN
// =================================================================
$tanggal_mulai_chart = date('Y-m-01');
$tanggal_selesai_chart = date('Y-m-t');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filter_chart'])) {
    $tanggal_mulai_chart = $_POST['tanggal_mulai_chart'];
    $tanggal_selesai_chart = $_POST['tanggal_selesai_chart'];
}
$query_chart = mysqli_query($koneksi, "SELECT DATE(waktu_transaksi) as tanggal, SUM(total_harga) as total FROM penjualan WHERE DATE(waktu_transaksi) BETWEEN '$tanggal_mulai_chart' AND '$tanggal_selesai_chart' GROUP BY DATE(waktu_transaksi) ORDER BY tanggal ASC");
$chart_labels = []; $chart_data = [];
if ($query_chart) { while($row_chart = mysqli_fetch_assoc($query_chart)){ $chart_labels[] = date('d M', strtotime($row_chart['tanggal'])); $chart_data[] = $row_chart['total']; } }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Dashboard - Kasir Sembako</title>
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
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
                            <a class="nav-link active" href="dashbord.php"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Dashboard</a>
                            <a class="nav-link" href="pos.php"><div class="sb-nav-link-icon"><i class="fas fa-cash-register"></i></div>Transaksi (POS)</a>
                            <a class="nav-link" href="stok_masuk.php"><div class="sb-nav-link-icon"><i class="fas fa-plus-square"></i></div>Stok Masuk</a>
                            <a class="nav-link" href="pelanggan.php"><div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>Kelola Pelanggan</a>
                            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseData" aria-expanded="true"><div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>Kelola Data<div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div></a>
                            <div class="collapse show" id="collapseData" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="produk.php">Data Produk</a>
                                    <a class="nav-link" href="supplier.php">Data Supplier</a>
                                    <a class="nav-link" href="riwayat_transaksi.php">Riwayat Transaksi</a>
                                </nav>
                            </div>
                            <a class="nav-link" href="laporan.php"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Laporan Keuangan</a>
                            <a class="nav-link" href="logout.php"><div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>Logout</a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer"><div class="small">Logged in as:</div><?= ($_SESSION['username']); ?></div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Dashboard</h1>
                        <ol class="breadcrumb mb-4"><li class="breadcrumb-item active">Selamat datang, <?php echo ($_SESSION['nama_lengkap']); ?>!</li></ol>
                        <div class="row">
                            <div class="col-xl-3 col-md-6"><div class="card bg-success text-white mb-4"><div class="card-body d-flex justify-content-between align-items-center"><div>Total Produk</div><i class="fas fa-box fa-2x"></i></div><div class="card-footer"><h4 class="text-white mb-0"><?= $total_produk; ?> Jenis Barang</h4></div></div></div>
                            <div class="col-xl-3 col-md-6"><div class="card bg-success text-white mb-4"><div class="card-body d-flex justify-content-between align-items-center"><div>Transaksi Hari Ini</div><i class="fas fa-shopping-cart fa-2x"></i></div><div class="card-footer"><h4 class="text-white mb-0"><?= $transaksi_hari_ini; ?> Transaksi</h4></div></div></div>
                            <div class="col-xl-3 col-md-6"><div class="card bg-success text-white mb-4"><div class="card-body d-flex justify-content-between align-items-center"><div>Pendapatan Hari Ini</div><i class="fas fa-money-bill-wave fa-2x"></i></div><div class="card-footer"><h4 class="text-white mb-0">Rp <?= number_format($pendapatan_hari_ini); ?></h4></div></div></div>
                            <div class="col-xl-3 col-md-6"><div class="card bg-success text-white mb-4"><div class="card-body d-flex justify-content-between align-items-center"><div>Produk Terlaris</div><i class="fas fa-star fa-2x"></i></div><div class="card-footer"><h6 class="text-white mb-0" style="font-size: 0.9rem;"><?= htmlspecialchars($produk_terlaris); ?></h6></div></div></div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-receipt me-1"></i>Semua Transaksi</span>
                                <form method="GET" action="dashbord.php" class="d-flex align-items-center m-0">
                                    <label for="urutan" class="form-label me-2 mb-0 small">Urutkan:</label>
                                    <select name="urutan" id="urutan" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                        <option value="terbaru" <?= ($urutan == 'DESC') ? 'selected' : ''; ?>>Terbaru</option>
                                        <option value="terlama" <?= ($urutan == 'ASC') ? 'selected' : ''; ?>>Terlama</option>
                                    </select>
                                </form>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover">
                                    <thead><tr><th>No. Nota</th><th>Waktu</th><th>Total</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                        <?php if(empty($semua_transaksi)): ?>
                                            <tr><td colspan="4" class="text-center">Belum ada transaksi.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($semua_transaksi as $t) { ?>
                                            <tr>
                                                <td>NOTA-<?= $t['id_penjualan']; ?></td>
                                                <td><?= date('d M Y, H:i', strtotime($t['waktu_transaksi'])); ?></td>
                                                <td>Rp <?= number_format($t['total_harga']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#notaModal<?= $t['id_penjualan']; ?>">Lihat Nota</button>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header"><i class="fas fa-chart-line me-1"></i>Statistik Pendapatan</div>
                            <div class="card-body">
                                <form method="POST" action="index.php" class="row g-2 mb-3">
                                    <div class="col-md-5"><label class="small mb-1">Dari Tanggal</label><input type="date" class="form-control form-control-sm" name="tanggal_mulai_chart" value="<?= $tanggal_mulai_chart; ?>"></div>
                                    <div class="col-md-5"><label class="small mb-1">Sampai Tanggal</label><input type="date" class="form-control form-control-sm" name="tanggal_selesai_chart" value="<?= $tanggal_selesai_chart; ?>"></div>
                                    <div class="col-md-2 d-grid align-items-end"><button type="submit" name="filter_chart" class="btn btn-primary btn-sm">Filter</button></div>
                                </form>
                                <canvas id="myAreaChart" width="100%" height="100"></canvas>
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
        <?php } ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script>
            Chart.defaults.global.defaultFontFamily = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
            Chart.defaults.global.defaultFontColor = '#292b2c';
            var ctx = document.getElementById("myAreaChart");
            var myLineChart = new Chart(ctx, {
              type: 'line',
              data: { labels: <?= json_encode($chart_labels); ?>, datasets: [{ label: "Pendapatan", lineTension: 0.3, backgroundColor: "rgba(2,117,216,0.2)", borderColor: "rgba(2,117,216,1)", data: <?= json_encode($chart_data); ?>, }], },
              options: { scales: { xAxes: [{ gridLines: { display: false }, ticks: { maxTicksLimit: 7 } }], yAxes: [{ ticks: { min: 0, maxTicksLimit: 5, callback: function(value) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); } }, gridLines: { color: "rgba(0, 0, 0, .125)" } }], }, legend: { display: false } }
            });
        </script>
    </body>
</html>