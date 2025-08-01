<?php
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// =================================================================
// LOGIKA UNTUK PROSES FORM (CREATE, UPDATE, DELETE, BAYAR UTANG)
// =================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // Aksi: Tambah Pelanggan (tetap sama)
    if ($_POST['action'] == 'tambah') {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
        $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
        $query = "INSERT INTO pelanggan (nama_pelanggan, telepon, alamat) VALUES ('$nama', '$telepon', '$alamat')";
        mysqli_query($koneksi, $query);
    }
    
    // Aksi: Edit Pelanggan (tetap sama)
    elseif ($_POST['action'] == 'edit') {
        $id = (int)$_POST['id_pelanggan'];
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
        $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
        $query = "UPDATE pelanggan SET nama_pelanggan='$nama', telepon='$telepon', alamat='$alamat' WHERE id_pelanggan='$id'";
        mysqli_query($koneksi, $query);
    }

    // Aksi: Hapus Pelanggan (tetap sama)
    elseif ($_POST['action'] == 'hapus') {
        $id = (int)$_POST['id_pelanggan'];
        $query = "DELETE FROM pelanggan WHERE id_pelanggan='$id'";
        mysqli_query($koneksi, $query);
    }

    // Aksi BARU: Bayar Utang
    elseif ($_POST['action'] == 'bayar_utang') {
        $id_pelanggan = (int)$_POST['id_pelanggan'];
        $jumlah_bayar = (float)$_POST['jumlah_bayar'];
        $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

        if ($jumlah_bayar > 0) {
            mysqli_begin_transaction($koneksi);
            try {
                // Catat pembayaran di riwayat (sebagai nilai negatif)
                $stmt1 = mysqli_prepare($koneksi, "INSERT INTO riwayat_utang (id_pelanggan, jumlah, keterangan) VALUES (?, ?, ?)");
                $jumlah_bayar_negatif = -$jumlah_bayar;
                mysqli_stmt_bind_param($stmt1, "ids", $id_pelanggan, $jumlah_bayar_negatif, $keterangan);
                mysqli_stmt_execute($stmt1);

                // Kurangi total utang pelanggan
                $stmt2 = mysqli_prepare($koneksi, "UPDATE pelanggan SET total_utang = total_utang - ? WHERE id_pelanggan = ?");
                mysqli_stmt_bind_param($stmt2, "di", $jumlah_bayar, $id_pelanggan);
                mysqli_stmt_execute($stmt2);
                
                mysqli_commit($koneksi);
                $_SESSION['pesan_sukses'] = "Pembayaran utang berhasil dicatat.";
            } catch (mysqli_sql_exception $e) {
                mysqli_rollback($koneksi);
                $_SESSION['pesan_error'] = "Gagal mencatat pembayaran: " . $e->getMessage();
            }
        } else {
            $_SESSION['pesan_error'] = "Jumlah bayar harus lebih dari nol.";
        }
    }

    header("location: pelanggan.php");
    exit();
}

$queryPelanggan = mysqli_query($koneksi, "SELECT * FROM pelanggan ORDER BY nama_pelanggan ASC");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Kelola Pelanggan - Kasir Sembako</title>
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
                            <a class="nav-link active" href="pelanggan.php"><div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>Kelola Pelanggan</a>
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
                    <div class="sb-sidenav-footer"><div class="small">Logged in as:</div><?= htmlspecialchars($_SESSION['username']); ?></div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Kelola Pelanggan</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="dashbord.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Pelanggan</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header"><button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#tambahModal"><i class="fas fa-plus"></i> Tambah Pelanggan</button></div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead><tr><th>Nama Pelanggan</th><th>Telepon</th><th>Total Utang</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                        <?php mysqli_data_seek($queryPelanggan, 0); while ($p = mysqli_fetch_array($queryPelanggan)) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['nama_pelanggan']); ?></td>
                                            <td><?= htmlspecialchars($p['telepon']); ?></td>
                                            <td class="<?= $p['total_utang'] > 0 ? 'text-danger fw-bold' : ''; ?>">Rp <?= number_format($p['total_utang']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal<?= $p['id_pelanggan']; ?>" title="Lihat Riwayat Utang"><i class="fas fa-history"></i></button>
                                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bayarModal<?= $p['id_pelanggan']; ?>" title="Bayar Utang" <?= $p['total_utang'] <= 0 ? 'disabled' : ''; ?>><i class="fas fa-money-bill-wave"></i></button>
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id_pelanggan']; ?>" title="Edit Pelanggan"><i class="fas fa-edit"></i></button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $p['id_pelanggan']; ?>" title="Hapus Pelanggan"><i class="fas fa-trash"></i></button>
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

        <div class="modal fade" id="tambahModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Tambah Pelanggan Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="pelanggan.php"><input type="hidden" name="action" value="tambah"><div class="modal-body"><div class="mb-3"><label class="form-label">Nama Pelanggan</label><input type="text" class="form-control" name="nama_pelanggan" required></div><div class="mb-3"><label class="form-label">No. Telepon</label><input type="text" class="form-control" name="telepon"></div><div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat" rows="3"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div></div></div>

        <?php mysqli_data_seek($queryPelanggan, 0); while ($p = mysqli_fetch_array($queryPelanggan)) { ?>
        <div class="modal fade" id="editModal<?= $p['id_pelanggan']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Data Pelanggan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="pelanggan.php"><input type="hidden" name="action" value="edit"><input type="hidden" name="id_pelanggan" value="<?= $p['id_pelanggan']; ?>"><div class="modal-body"><div class="mb-3"><label class="form-label">Nama Pelanggan</label><input type="text" class="form-control" name="nama_pelanggan" value="<?= htmlspecialchars($p['nama_pelanggan']); ?>" required></div><div class="mb-3"><label class="form-label">No. Telepon</label><input type="text" class="form-control" name="telepon" value="<?= htmlspecialchars($p['telepon']); ?>"></div><div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat" rows="3"><?= htmlspecialchars($p['alamat']); ?></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div></div></div>
        
        <div class="modal fade" id="hapusModal<?= $p['id_pelanggan']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="pelanggan.php"><input type="hidden" name="action" value="hapus"><input type="hidden" name="id_pelanggan" value="<?= $p['id_pelanggan']; ?>"><div class="modal-body"><p>Yakin hapus pelanggan <strong><?= htmlspecialchars($p['nama_pelanggan']); ?></strong>?</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Hapus</button></div></form></div></div></div>
        
        <div class="modal fade" id="bayarModal<?= $p['id_pelanggan']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Bayar Utang: <?= htmlspecialchars($p['nama_pelanggan']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="pelanggan.php"><input type="hidden" name="action" value="bayar_utang"><input type="hidden" name="id_pelanggan" value="<?= $p['id_pelanggan']; ?>"><div class="modal-body"><div class="alert alert-info">Sisa utang saat ini: <strong>Rp <?= number_format($p['total_utang']); ?></strong></div><div class="mb-3"><label class="form-label">Jumlah Bayar</label><input type="number" class="form-control" name="jumlah_bayar" max="<?= $p['total_utang']; ?>" min="1" required></div><div class="mb-3"><label class="form-label">Keterangan</label><input type="text" class="form-control" name="keterangan" placeholder="Contoh: Pembayaran tunai"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan Pembayaran</button></div></form></div></div></div>

        <div class="modal fade" id="detailModal<?= $p['id_pelanggan']; ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Riwayat Utang: <?= htmlspecialchars($p['nama_pelanggan']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
            <table class="table table-striped"><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Jumlah</th></tr></thead><tbody>
                <?php
                $id_pelanggan_ini = $p['id_pelanggan'];
                $query_riwayat = mysqli_query($koneksi, "SELECT * FROM riwayat_utang WHERE id_pelanggan='$id_pelanggan_ini' ORDER BY tanggal DESC");
                while ($riwayat = mysqli_fetch_assoc($query_riwayat)) {
                    $jumlah_formatted = 'Rp ' . number_format(abs($riwayat['jumlah']));
                    $text_class = $riwayat['jumlah'] < 0 ? 'text-success' : 'text-danger';
                    $icon = $riwayat['jumlah'] < 0 ? '<i class="fas fa-arrow-up"></i> (Bayar)' : '<i class="fas fa-arrow-down"></i> (Utang Baru)';
                ?>
                <tr><td><?= date('d M Y H:i', strtotime($riwayat['tanggal'])); ?></td><td><?= htmlspecialchars($riwayat['keterangan']); ?></td><td class="fw-bold <?= $text_class; ?>"><?= $jumlah_formatted . ' ' . $icon; ?></td></tr>
                <?php } ?>
            </tbody></table>
        </div></div></div></div>
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