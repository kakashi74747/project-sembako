<?php
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// Logika CRUD untuk Supplier
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    // Aksi: Tambah Supplier
    if ($_POST['action'] == 'tambah') {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama_supplier']);
        $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon_supplier']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat_supplier']);
        $query = "INSERT INTO supplier (nama_supplier, telepon_supplier, alamat_supplier) VALUES ('$nama', '$telepon', '$alamat')";
        mysqli_query($koneksi, $query);
    }
    // Aksi: Edit Supplier
    elseif ($_POST['action'] == 'edit') {
        $id = (int)$_POST['id_supplier'];
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama_supplier']);
        $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon_supplier']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat_supplier']);
        $query = "UPDATE supplier SET nama_supplier='$nama', telepon_supplier='$telepon', alamat_supplier='$alamat' WHERE id_supplier='$id'";
        mysqli_query($koneksi, $query);
    }
    // Aksi: Hapus Supplier
    elseif ($_POST['action'] == 'hapus') {
        $id = (int)$_POST['id_supplier'];
        $query = "DELETE FROM supplier WHERE id_supplier='$id'";
        mysqli_query($koneksi, $query);
    }
    header("location: supplier.php");
    exit();
}

$querySupplier = mysqli_query($koneksi, "SELECT * FROM supplier ORDER BY nama_supplier ASC");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Kelola Supplier - Kasir Sembako</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
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
                                    <a class="nav-link active" href="supplier.php">Data Supplier</a>
                                    <a class="nav-link" href="riwayat_transaksi.php">Riwayat Transaksi</a>
                                </nav>
                            </div>
                            <a class="nav-link" href="laporan.php"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Laporan Keuangan</a>
                            <a class="nav-link" href="logout.php"><div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>Logout</a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer"><div class="small">Logged in as:</div><?= htmlspecialchars($_SESSION['username']); ?></div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Kelola Supplier</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="dashbord.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Supplier</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal"><i class="fas fa-plus"></i> Tambah Supplier</button>
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead><tr><th>Nama Supplier</th><th>Telepon</th><th>Alamat</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                        <?php while ($s = mysqli_fetch_array($querySupplier)) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['nama_supplier']); ?></td>
                                            <td><?= htmlspecialchars($s['telepon_supplier']); ?></td>
                                            <td><?= htmlspecialchars($s['alamat_supplier']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id_supplier']; ?>">Edit</button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $s['id_supplier']; ?>">Hapus</button>
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

        <div class="modal fade" id="tambahModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Tambah Supplier Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="supplier.php">
                    <input type="hidden" name="action" value="tambah">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Nama Supplier</label><input type="text" class="form-control" name="nama_supplier" required></div>
                        <div class="mb-3"><label class="form-label">No. Telepon</label><input type="text" class="form-control" name="telepon_supplier"></div>
                        <div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat_supplier" rows="3"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div></div>
        </div>

        <?php mysqli_data_seek($querySupplier, 0); while ($s = mysqli_fetch_array($querySupplier)) { ?>
        <div class="modal fade" id="editModal<?= $s['id_supplier']; ?>" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Data Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="supplier.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id_supplier" value="<?= $s['id_supplier']; ?>">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Nama Supplier</label><input type="text" class="form-control" name="nama_supplier" value="<?= htmlspecialchars($s['nama_supplier']); ?>" required></div>
                        <div class="mb-3"><label class="form-label">No. Telepon</label><input type="text" class="form-control" name="telepon_supplier" value="<?= htmlspecialchars($s['telepon_supplier']); ?>"></div>
                        <div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat_supplier" rows="3"><?= htmlspecialchars($s['alamat_supplier']); ?></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div></div>
        </div>
        <div class="modal fade" id="hapusModal<?= $s['id_supplier']; ?>" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="supplier.php">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id_supplier" value="<?= $s['id_supplier']; ?>">
                    <div class="modal-body"><p>Yakin hapus supplier <strong><?= htmlspecialchars($s['nama_supplier']); ?></strong>?</p></div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Hapus</button></div>
                </form>
            </div></div>
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