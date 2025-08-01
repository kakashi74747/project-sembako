<?php
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// =================================================================
// LOGIKA UNTUK PROSES FORM (CREATE, UPDATE, DELETE)
// =================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['tambahstok'])) {
        $id_produk = (int)$_POST['id_produk'];
        $id_supplier = !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;
        $jumlah_masuk = (int)$_POST['jumlah_masuk'];
        $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
        $tanggal_masuk = !empty($_POST['tanggal_masuk']) ? $_POST['tanggal_masuk'] : date('Y-m-d H:i:s');

        if ($id_produk > 0 && $jumlah_masuk > 0) {
            mysqli_begin_transaction($koneksi);
            try {
                $stmt1 = mysqli_prepare($koneksi, "INSERT INTO stok_masuk (id_produk, id_supplier, jumlah_masuk, tanggal_masuk, keterangan) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt1, "iiiss", $id_produk, $id_supplier, $jumlah_masuk, $tanggal_masuk, $keterangan);
                mysqli_stmt_execute($stmt1);

                $stmt2 = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
                mysqli_stmt_bind_param($stmt2, "ii", $jumlah_masuk, $id_produk);
                mysqli_stmt_execute($stmt2);

                mysqli_commit($koneksi);
                $_SESSION['pesan_sukses'] = "Stok berhasil ditambahkan!";
            } catch (mysqli_sql_exception $exception) {
                mysqli_rollback($koneksi);
                $_SESSION['pesan_error'] = "Gagal menambahkan stok: " . $exception->getMessage();
            }
        } else {
            $_SESSION['pesan_error'] = "Produk dan jumlah stok harus diisi dengan benar.";
        }
    }

    elseif (isset($_POST['editstok'])) {
        $id_masuk = (int)$_POST['id_masuk'];
        $id_produk = (int)$_POST['id_produk'];
        $id_supplier = !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;
        $jumlah_baru = (int)$_POST['jumlah_masuk'];
        $jumlah_lama = (int)$_POST['jumlah_lama'];
        $keterangan_baru = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
        $tanggal_baru = $_POST['tanggal_masuk'];
        
        $selisih_stok = $jumlah_baru - $jumlah_lama;

        mysqli_begin_transaction($koneksi);
        try {
            $stmt1 = mysqli_prepare($koneksi, "UPDATE stok_masuk SET id_supplier=?, jumlah_masuk=?, tanggal_masuk=?, keterangan=? WHERE id_masuk=?");
            mysqli_stmt_bind_param($stmt1, "iissi", $id_supplier, $jumlah_baru, $tanggal_baru, $keterangan_baru, $id_masuk);
            mysqli_stmt_execute($stmt1);

            $stmt2 = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
            mysqli_stmt_bind_param($stmt2, "ii", $selisih_stok, $id_produk);
            mysqli_stmt_execute($stmt2);

            mysqli_commit($koneksi);
            $_SESSION['pesan_sukses'] = "Catatan stok berhasil diperbarui!";
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($koneksi);
            $_SESSION['pesan_error'] = "Gagal memperbarui catatan: " . $exception->getMessage();
        }
    }

    elseif (isset($_POST['hapusstok'])) {
        $id_masuk = (int)$_POST['id_masuk'];
        $id_produk = (int)$_POST['id_produk'];
        $jumlah_dihapus = (int)$_POST['jumlah_dihapus'];

        mysqli_begin_transaction($koneksi);
        try {
            $stmt1 = mysqli_prepare($koneksi, "DELETE FROM stok_masuk WHERE id_masuk = ?");
            mysqli_stmt_bind_param($stmt1, "i", $id_masuk);
            mysqli_stmt_execute($stmt1);

            $stmt2 = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
            mysqli_stmt_bind_param($stmt2, "ii", $jumlah_dihapus, $id_produk);
            mysqli_stmt_execute($stmt2);
            
            mysqli_commit($koneksi);
            $_SESSION['pesan_sukses'] = "Catatan stok berhasil dihapus!";
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($koneksi);
            $_SESSION['pesan_error'] = "Gagal menghapus catatan: " . $exception->getMessage();
        }
    }

    header("location: stok_masuk.php");
    exit();
}

// =================================================================
// PENGAMBILAN DATA UNTUK TAMPILAN
// =================================================================
$pesan = '';
if(isset($_SESSION['pesan_sukses'])){ $pesan = "<div class='alert alert-success' role='alert'>" . $_SESSION['pesan_sukses'] . "</div>"; unset($_SESSION['pesan_sukses']); }
elseif(isset($_SESSION['pesan_error'])){ $pesan = "<div class='alert alert-danger' role='alert'>" . $_SESSION['pesan_error'] . "</div>"; unset($_SESSION['pesan_error']); }

$queryProduk = mysqli_query($koneksi, "SELECT * FROM produk ORDER BY nama_produk ASC");
$querySupplier = mysqli_query($koneksi, "SELECT * FROM supplier ORDER BY nama_supplier ASC");
$queryRiwayat = mysqli_query($koneksi, "
    SELECT sm.*, p.nama_produk, p.foto_produk, s.nama_supplier 
    FROM stok_masuk sm 
    JOIN produk p ON sm.id_produk = p.id_produk 
    LEFT JOIN supplier s ON sm.id_supplier = s.id_supplier
    ORDER BY sm.tanggal_masuk DESC
");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Stok Masuk - Kasir Sembako</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
            .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
            .product-card { cursor: pointer; border: 1px solid #ddd; border-radius: 0.375rem; padding: 0.5rem; text-align: center; }
            .product-card:hover { background-color: #f1f1f1; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
            .product-card img { max-width: 100%; height: 80px; object-fit: contain; margin-bottom: 0.5rem; }
            .product-name { font-weight: bold; font-size: 0.9rem; }
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
                            <a class="nav-link active" href="stok_masuk.php"><div class="sb-nav-link-icon"><i class="fas fa-plus-square"></i></div>Stok Masuk</a>
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
                    <div class="sb-sidenav-footer"><div class="small">Logged in as:</div><?= htmlspecialchars($_SESSION['username']); ?></div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Stok Masuk</h1>
                        <ol class="breadcrumb mb-4"><li class="breadcrumb-item"><a href="dashbord.php">Dashboard</a></li><li class="breadcrumb-item active">Tambah Stok Masuk</li></ol>
                        <?= $pesan; ?>
                        <div class="card mb-4">
                            <div class="card-header"><i class="fas fa-box-open me-1"></i>Pilih Produk untuk Ditambah Stoknya</div>
                            <div class="card-body product-grid">
                                <?php mysqli_data_seek($queryProduk, 0); while($p = mysqli_fetch_array($queryProduk)) { 
                                    $foto = $p['foto_produk'] ? 'uploads/' . $p['foto_produk'] : 'assets/img/placeholder.png';
                                ?>
                                    <div class="product-card" data-bs-toggle="modal" data-bs-target="#tambahStokModal" data-id="<?= $p['id_produk']; ?>" data-nama="<?= htmlspecialchars($p['nama_produk']); ?>">
                                        <img src="<?= $foto; ?>" alt="<?= htmlspecialchars($p['nama_produk']); ?>">
                                        <div class="product-name"><?= htmlspecialchars($p['nama_produk']); ?></div>
                                        <div class="small text-muted">Stok: <?= $p['stok']; ?></div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header"><i class="fas fa-history me-1"></i>Riwayat Stok Masuk</div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead><tr><th>Foto</th><th>Nama Produk</th><th>Jumlah</th><th>Supplier</th><th>Waktu</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                        <?php while($riwayat = mysqli_fetch_array($queryRiwayat)) { 
                                            $foto = $riwayat['foto_produk'] ? 'uploads/' . $riwayat['foto_produk'] : 'assets/img/placeholder.png';
                                        ?>
                                        <tr>
                                            <td><img src="<?= $foto; ?>" width="50"></td>
                                            <td><?= htmlspecialchars($riwayat['nama_produk']); ?></td>
                                            <td>+ <?= htmlspecialchars($riwayat['jumlah_masuk']); ?></td>
                                            <td><?= htmlspecialchars($riwayat['nama_supplier'] ?? '<i>Tidak ada</i>'); ?></td>
                                            <td><?= date('d M Y, H:i', strtotime($riwayat['tanggal_masuk'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editStokModal<?= $riwayat['id_masuk']; ?>"><i class="fas fa-edit"></i></button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusStokModal<?= $riwayat['id_masuk']; ?>"><i class="fas fa-trash"></i></button>
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

        <div class="modal fade" id="tambahStokModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="tambahStokModalLabel">Tambah Stok untuk...</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="stok_masuk.php"><div class="modal-body"><input type="hidden" name="id_produk" id="modal_id_produk"><div class="mb-3"><label class="form-label">Jumlah Masuk</label><input type="number" class="form-control" name="jumlah_masuk" min="1" required></div><div class="mb-3"><label class="form-label">Supplier (Opsional)</label><select class="form-select" name="id_supplier"><option value="">-- Pilih Supplier --</option><?php mysqli_data_seek($querySupplier, 0); while($s = mysqli_fetch_array($querySupplier)){ echo "<option value='{$s['id_supplier']}'>".htmlspecialchars($s['nama_supplier'])."</option>"; } ?></select></div><div class="mb-3"><label class="form-label">Tanggal Masuk (opsional)</label><input type="datetime-local" class="form-control" name="tanggal_masuk"><small class="text-muted">Kosongkan untuk waktu sekarang</small></div><div class="mb-3"><label class="form-label">Keterangan (opsional)</label><textarea class="form-control" name="keterangan" rows="2" placeholder="Contoh: Stok dari Supplier A"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="tambahstok" class="btn btn-primary">Simpan</button></div></form></div></div>
        </div>

        <?php mysqli_data_seek($queryRiwayat, 0); while($riwayat = mysqli_fetch_array($queryRiwayat)) { ?>
        <div class="modal fade" id="editStokModal<?= $riwayat['id_masuk']; ?>" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Catatan Stok</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="stok_masuk.php">
                    <div class="modal-body">
                        <input type="hidden" name="id_masuk" value="<?= $riwayat['id_masuk']; ?>">
                        <input type="hidden" name="id_produk" value="<?= $riwayat['id_produk']; ?>">
                        <input type="hidden" name="jumlah_lama" value="<?= $riwayat['jumlah_masuk']; ?>">
                        <div class="mb-3"><strong>Produk:</strong> <?= htmlspecialchars($riwayat['nama_produk']); ?></div>
                        <div class="mb-3"><label class="form-label">Jumlah Masuk</label><input type="number" class="form-control" name="jumlah_masuk" value="<?= $riwayat['jumlah_masuk']; ?>" min="1" required></div>
                        <div class="mb-3"><label class="form-label">Supplier (Opsional)</label><select class="form-select" name="id_supplier"><option value="">-- Pilih Supplier --</option><?php mysqli_data_seek($querySupplier, 0); while($s = mysqli_fetch_array($querySupplier)){ $selected = ($s['id_supplier'] == $riwayat['id_supplier']) ? 'selected' : ''; echo "<option value='{$s['id_supplier']}' $selected>".htmlspecialchars($s['nama_supplier'])."</option>"; } ?></select></div>
                        <div class="mb-3"><label class="form-label">Tanggal Masuk</label><input type="datetime-local" class="form-control" name="tanggal_masuk" value="<?= date('Y-m-d\TH:i', strtotime($riwayat['tanggal_masuk'])); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"><?= htmlspecialchars($riwayat['keterangan']); ?></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="editstok" class="btn btn-primary">Simpan</button></div>
                </form>
            </div></div>
        </div>
        <div class="modal fade" id="hapusStokModal<?= $riwayat['id_masuk']; ?>" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Hapus Catatan Stok Masuk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="stok_masuk.php">
                    <div class="modal-body">
                        <input type="hidden" name="id_masuk" value="<?= $riwayat['id_masuk']; ?>">
                        <input type="hidden" name="id_produk" value="<?= $riwayat['id_produk']; ?>">
                        <input type="hidden" name="jumlah_dihapus" value="<?= $riwayat['jumlah_masuk']; ?>">
                        <p>Yakin hapus catatan stok <strong><?= htmlspecialchars($riwayat['nama_produk']); ?></strong> sejumlah <strong><?= $riwayat['jumlah_masuk']; ?></strong>?</p>
                        <p class="text-danger small">Tindakan ini akan mengurangi stok produk saat ini.</p>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="hapusstok" class="btn btn-danger">Ya, Hapus</button></div>
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

                const tambahStokModal = document.getElementById('tambahStokModal');
                tambahStokModal.addEventListener('show.bs.modal', event => {
                    const button = event.relatedTarget;
                    const productId = button.getAttribute('data-id');
                    const productName = button.getAttribute('data-nama');
                    const modalTitle = tambahStokModal.querySelector('.modal-title');
                    const modalProductIdInput = tambahStokModal.querySelector('#modal_id_produk');
                    modalTitle.textContent = `Tambah Stok untuk ${productName}`;
                    modalProductIdInput.value = productId;
                });
            });
        </script>
    </body>
</html>