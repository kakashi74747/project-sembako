<?php
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// Inisialisasi variabel dengan nilai default
$tanggal_mulai = date('Y-m-01');
$tanggal_selesai = date('Y-m-t');
$total_pendapatan = 0;
$total_modal = 0;
$total_laba = 0;
$total_transaksi = 0;
$total_produk_terjual = 0;

// Jika form filter tanggal disubmit, perbarui tanggal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filter_tanggal'])) {
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
}

// Query untuk menghitung KPI utama (pendapatan, modal, total transaksi)
$queryLaporan = "
    SELECT 
        SUM(pj.total_harga) AS total_pendapatan,
        COUNT(DISTINCT pj.id_penjualan) AS total_transaksi,
        SUM(dp.jumlah * p.harga_beli) AS total_modal,
        SUM(dp.jumlah) as total_produk_terjual
    FROM penjualan pj
    LEFT JOIN detail_penjualan dp ON pj.id_penjualan = dp.id_penjualan
    LEFT JOIN produk p ON dp.id_produk = p.id_produk
    WHERE DATE(pj.waktu_transaksi) BETWEEN '$tanggal_mulai' AND '$tanggal_selesai'
";
$resultLaporan = mysqli_query($koneksi, $queryLaporan);
if($dataLaporan = mysqli_fetch_assoc($resultLaporan)){
    $total_pendapatan = $dataLaporan['total_pendapatan'] ?? 0;
    $total_modal = $dataLaporan['total_modal'] ?? 0;
    $total_transaksi = $dataLaporan['total_transaksi'] ?? 0;
    $total_produk_terjual = $dataLaporan['total_produk_terjual'] ?? 0;
    $total_laba = $total_pendapatan - $total_modal;
}

// Query untuk tabel produk terlaris
$queryProdukLaris = mysqli_query($koneksi, "
    SELECT 
        p.nama_produk, 
        SUM(dp.jumlah) AS jumlah_terjual
    FROM detail_penjualan dp
    JOIN produk p ON dp.id_produk = p.id_produk
    JOIN penjualan pj ON dp.id_penjualan = pj.id_penjualan
    WHERE DATE(pj.waktu_transaksi) BETWEEN '$tanggal_mulai' AND '$tanggal_selesai'
    GROUP BY p.id_produk
    ORDER BY jumlah_terjual DESC
    LIMIT 10
");

// Query untuk Laporan Performa Produk
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

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Laporan Keuangan - Kasir Sembako</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
            @media print {
                body * { visibility: hidden; }
                #areaCetak, #areaCetak * { visibility: visible; }
                #areaCetak { position: absolute; left: 0; top: 0; width: 100%; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark no-print">
            <a class="navbar-brand ps-3" href="dashbord.php">Sembako Kelompok 3</a>
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <ul class="navbar-nav ms-auto me-3 me-lg-4">
                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown"><i class="fas fa-user fa-fw"></i> <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></a><ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown"><li><a class="dropdown-item" href="logout.php">Logout</a></li></ul></li>
            </ul>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav" class="no-print">
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
                                    <a class="nav-link" href="riwayat_transaksi.php">Riwayat Transaksi</a>
                                </nav>
                            </div>
                            <a class="nav-link active" href="laporan.php"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Laporan Keuangan</a>
                            <a class="nav-link" href="logout.php"><div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>Logout</a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer"><div class="small">Logged in as:</div><?= htmlspecialchars($_SESSION['username']); ?></div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div id="areaCetak">
                        <div class="container-fluid px-4">
                            <h1 class="mt-4">Laporan Keuangan</h1>
                            <ol class="breadcrumb mb-4 no-print">
                                <li class="breadcrumb-item"><a href="dashbord.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Laporan Keuangan</li>
                            </ol>
                            
                            <div class="text-center mb-4 d-none d-print-block">
                                <h3>Laporan Keuangan Toko Sembako Kelompok 3</h3>
                                <h5>Periode: <?= date('d M Y', strtotime($tanggal_mulai)); ?> s/d <?= date('d M Y', strtotime($tanggal_selesai)); ?></h5>
                            </div>

                            <div class="row">
                                <div class="col-lg-8"><div class="card mb-4"><div class="card-header"><i class="fas fa-star me-1"></i>10 Produk Terlaris (Berdasarkan Jumlah)</div><div class="card-body"><table class="table table-striped table-sm"><thead><tr><th>No</th><th>Nama Produk</th><th>Jumlah Terjual</th></tr></thead><tbody><?php $i=1; while($p = mysqli_fetch_array($queryProdukLaris)) { ?><tr><td><?= $i++; ?></td><td><?= htmlspecialchars($p['nama_produk']); ?></td><td><?= $p['jumlah_terjual']; ?></td></tr><?php } ?></tbody></table></div></div></div>
                                <div class="col-lg-4"><div class="card mb-4"><div class="card-header"><i class="fas fa-chart-pie me-1"></i>Ringkasan Transaksi</div><div class="card-body"><table class="table table-striped table-sm"><tbody><tr><td><strong>Total Transaksi</strong></td><td><?= number_format($total_transaksi); ?></td></tr><tr><td><strong>Total Item Terjual</strong></td><td><?= number_format($total_produk_terjual); ?></td></tr></tbody></table></div></div></div>
                            </div>
                            
                            <hr class="my-4 no-print">

                            <div class="card mb-4 no-print">
                                <div class="card-header"><i class="fas fa-filter me-1"></i>Filter Laporan</div>
                                <div class="card-body">
                                    <form method="POST" action="laporan.php" class="row g-3 align-items-center">
                                        <div class="col-md-4"><label class="form-label">Dari Tanggal</label><input type="date" class="form-control" name="tanggal_mulai" value="<?= $tanggal_mulai; ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Sampai Tanggal</label><input type="date" class="form-control" name="tanggal_selesai" value="<?= $tanggal_selesai; ?>"></div>
                                        <div class="col-md-2 mt-auto"><button type="submit" name="filter_tanggal" class="btn btn-secondary w-100">Tampilkan</button></div>
                                        <div class="col-md-2 mt-auto"><a href="export.php?start=<?= $tanggal_mulai; ?>&end=<?= $tanggal_selesai; ?>" class="btn btn-success w-100"><i class="fas fa-file-csv me-2"></i>Ekspor</a></div>
                                    </form>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xl-4 col-md-6"><div class="card bg-success text-white mb-4"><div class="card-body">Total Pendapatan</div><div class="card-footer fs-4">Rp <?= number_format($total_pendapatan); ?></div></div></div>
                                <div class="col-xl-4 col-md-6"><div class="card bg-danger text-white mb-4"><div class="card-body">Total Modal Terjual</div><div class="card-footer fs-4">Rp <?= number_format($total_modal); ?></div></div></div>
                                <div class="col-xl-4 col-md-6"><div class="card bg-secondary text-white mb-4"><div class="card-body">Total Laba Kotor</div><div class="card-footer fs-4">Rp <?= number_format($total_laba); ?></div></div></div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header"><i class="fas fa-tags me-1"></i>Laporan Performa per Produk</div>
                                <div class="card-body">
                                    <table id="datatablesSimple" class="table table-striped table-bordered table-hover">
                                        <thead><tr><th>Nama Produk</th><th>Jml Terjual</th><th>Pendapatan</th><th>Modal</th><th>Laba</th></tr></thead>
                                        <tbody>
                                            <?php mysqli_data_seek($queryPerformaProduk, 0); while($perf = mysqli_fetch_array($queryPerformaProduk)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($perf['nama_produk']); ?></td>
                                                <td><?= number_format($perf['jumlah_terjual']); ?></td>
                                                <td>Rp <?= number_format($perf['total_pendapatan_produk']); ?></td>
                                                <td>Rp <?= number_format($perf['total_modal_produk']); ?></td>
                                                <td class="fw-bold">Rp <?= number_format($perf['total_laba_produk']); ?></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header"><i class="fas fa-chart-bar me-1"></i>Grafik Perbandingan Keuangan</div>
                                <div class="card-body"><canvas id="myBarChart" width="100%" height="40"></canvas></div>
                            </div>
                        </div>
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto no-print"><div class="container-fluid px-4"><div class="d-flex align-items-center justify-content-between small"><div class="text-muted">Copyright &copy; Sembako Kelompok 3 2023</div></div></div></footer>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script>
            window.addEventListener('DOMContentLoaded', event => {
                const datatablesSimple = document.getElementById('datatablesSimple');
                if (datatablesSimple) { new simpleDatatables.DataTable(datatablesSimple); }
            });
            // Bar Chart Example
            var ctx = document.getElementById("myBarChart");
            var myBarChart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: ["Total Pendapatan", "Total Modal", "Total Laba"],
                datasets: [{
                  label: "Jumlah (Rp)",
                  backgroundColor: ["#28a745", "#dc3545", "#6c757d"],
                  data: [<?= $total_pendapatan; ?>, <?= $total_modal; ?>, <?= $total_laba; ?>],
                }],
              },
              options: {
                scales: {
                  xAxes: [{ gridLines: { display: false }, ticks: { maxTicksLimit: 6 } }],
                  yAxes: [{
                    ticks: {
                      min: 0,
                      maxTicksLimit: 5,
                      callback: function(value) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); }
                    },
                    gridLines: { display: true }
                  }],
                },
                legend: { display: false }
              }
            });
        </script>
    </body>
</html>