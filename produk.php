<?php
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// Fungsi untuk menangani upload file
function uploadFoto($file) {
    $namaFile = $file['name'];
    $error = $file['error'];
    $tmpName = $file['tmp_name'];

    if ($error === 4) { return null; }

    $ekstensiValid = ['jpg', 'jpeg', 'png'];
    $namaFileArray = explode('.', $namaFile);
    $ekstensiFile = strtolower(end($namaFileArray));
    if (!in_array($ekstensiFile, $ekstensiValid)) { return false; }

    $namaFileBaru = uniqid() . '.' . $ekstensiFile;
    move_uploaded_file($tmpName, 'uploads/' . $namaFileBaru);
    return $namaFileBaru;
}

// Logika CRUD diperbarui untuk menyertakan 'deskripsi'
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // Aksi: Tambah Produk
    if ($_POST['action'] == 'tambah') {
        $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
        $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);
        $harga_beli = $_POST['harga_beli'];
        $harga_jual = $_POST['harga_jual'];
        $stok = $_POST['stok'];
        
        $foto_produk = uploadFoto($_FILES['foto_produk']);
        if ($foto_produk === false) {
            echo "<script>alert('Ekstensi file tidak valid!'); window.location.href='produk.php';</script>";
            exit();
        }

        $query = "INSERT INTO produk (nama_produk, deskripsi, satuan, harga_beli, harga_jual, stok, foto_produk) VALUES ('$nama_produk', '$deskripsi', '$satuan', '$harga_beli', '$harga_jual', '$stok', '$foto_produk')";
        mysqli_query($koneksi, $query);
    }
    
    // Aksi: Edit Produk
    elseif ($_POST['action'] == 'edit') {
        $id_produk = $_POST['id_produk'];
        $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
        $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);
        $harga_beli = $_POST['harga_beli'];
        $harga_jual = $_POST['harga_jual'];
        $stok = $_POST['stok'];
        $foto_lama = $_POST['foto_lama'];
        
        $foto_produk = $foto_lama;
        if ($_FILES['foto_produk']['error'] !== 4) {
            $foto_produk_baru = uploadFoto($_FILES['foto_produk']);
            if ($foto_produk_baru) {
                $foto_produk = $foto_produk_baru;
                if ($foto_lama && file_exists('uploads/' . $foto_lama)) {
                    unlink('uploads/' . $foto_lama);
                }
            }
        }

        $query = "UPDATE produk SET nama_produk='$nama_produk', deskripsi='$deskripsi', satuan='$satuan', harga_beli='$harga_beli', harga_jual='$harga_jual', stok='$stok', foto_produk='$foto_produk' WHERE id_produk='$id_produk'";
        mysqli_query($koneksi, $query);
    }
    
    // Aksi: Hapus Produk
    elseif ($_POST['action'] == 'hapus') {
        $id_produk = $_POST['id_produk'];
        $q = mysqli_query($koneksi, "SELECT foto_produk FROM produk WHERE id_produk='$id_produk'");
        $data = mysqli_fetch_assoc($q);
        if ($data['foto_produk'] && file_exists('uploads/' . $data['foto_produk'])) {
            unlink('uploads/' . $data['foto_produk']);
        }
        $query = "DELETE FROM produk WHERE id_produk='$id_produk'";
        mysqli_query($koneksi, $query);
    }
    
    header("location: produk.php");
    exit();
}

$queryProduk = mysqli_query($koneksi, "SELECT * FROM produk");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Data Produk - Kasir Sembako</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <a class="navbar-brand ps-3" href="dashbord.php">Sembako Kelompok 3</a>
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <ul class="navbar-nav ms-auto me-3 me-lg-4">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i> <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </li>
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
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseData" aria-expanded="true">
                                <div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>Kelola Data<div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse show" id="collapseData" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link active" href="produk.php">Data Produk</a>
                                    <a class="nav-link" href="supplier.php">Data Supplier</a>
                                    <a class="nav-link" href="riwayat_transaksi.php">Riwayat Transaksi</a>
                                </nav>
                            </div>
                            <a class="nav-link" href="laporan.php"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Laporan Keuangan</a>
                            <a class="nav-link" href="logout.php"><div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>Logout</a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Data Produk</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="dashbord.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Data Produk</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header">
                                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#tambahProdukModal"><i class="fas fa-plus"></i> Tambah Produk Baru</button>
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Foto</th>
                                            <th>Nama Produk</th>
                                            <th>Deskripsi</th> <th>Satuan</th>
                                            <th>Stok</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        mysqli_data_seek($queryProduk, 0);
                                        while ($data = mysqli_fetch_array($queryProduk)) {
                                            $id_produk = $data['id_produk'];
                                            $foto = $data['foto_produk'] ? 'uploads/' . $data['foto_produk'] : 'assets/img/placeholder.png';
                                        ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><img src="<?= $foto; ?>" alt="Foto Produk" width="50"></td>
                                            <td><?= htmlspecialchars($data['nama_produk']); ?></td>
                                            <td><?= htmlspecialchars($data['deskripsi']); ?></td> <td><?= htmlspecialchars($data['satuan']); ?></td>
                                            <td><?= htmlspecialchars($data['stok']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editProdukModal<?= $id_produk; ?>">Edit</button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusProdukModal<?= $id_produk; ?>">Hapus</button>
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

        <div class="modal fade" id="tambahProdukModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Tambah Produk Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="produk.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="tambah">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Nama Produk</label><input type="text" class="form-control" name="nama_produk" required></div>
                        <div class="mb-3"><label class="form-label">Deskripsi</label><textarea class="form-control" name="deskripsi" rows="3"></textarea></div>
                        <div class="mb-3"><label class="form-label">Satuan</label><select name="satuan" class="form-control" required><option value="" disabled selected>-- Pilih Satuan --</option><option value="Kg">Kg</option><option value="Pcs">Pcs</option><option value="Liter">Liter</option><option value="Gram">Gram</option></select></div>
                        <div class="mb-3"><label class="form-label">Harga Beli</label><input type="number" class="form-control" name="harga_beli" required></div>
                        <div class="mb-3"><label class="form-label">Harga Jual</label><input type="number" class="form-control" name="harga_jual" required></div>
                        <div class="mb-3"><label class="form-label">Stok Awal</label><input type="number" class="form-control" name="stok" required></div>
                        <div class="mb-3"><label class="form-label">Foto Produk</label><input type="file" class="form-control" name="foto_produk"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div></div>
        </div>

        <?php
        mysqli_data_seek($queryProduk, 0); 
        while ($data = mysqli_fetch_array($queryProduk)) {
            $id_produk = $data['id_produk'];
            $foto = $data['foto_produk'] ? 'uploads/' . $data['foto_produk'] : 'assets/img/placeholder.png';
        ?>
        <div class="modal fade" id="editProdukModal<?= $id_produk; ?>" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Produk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="produk.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id_produk" value="<?= $id_produk; ?>">
                    <input type="hidden" name="foto_lama" value="<?= $data['foto_produk']; ?>">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Nama Produk</label><input type="text" class="form-control" name="nama_produk" value="<?= htmlspecialchars($data['nama_produk']); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Deskripsi</label><textarea class="form-control" name="deskripsi" rows="3"><?= htmlspecialchars($data['deskripsi']); ?></textarea></div>
                        <div class="mb-3"><label class="form-label">Satuan</label><select name="satuan" class="form-control" required><option value="Kg" <?= $data['satuan'] == 'Kg' ? 'selected' : ''; ?>>Kg</option><option value="Pcs" <?= $data['satuan'] == 'Pcs' ? 'selected' : ''; ?>>Pcs</option><option value="Liter" <?= $data['satuan'] == 'Liter' ? 'selected' : ''; ?>>Liter</option><option value="Gram" <?= $data['satuan'] == 'Gram' ? 'selected' : ''; ?>>Gram</option></select></div>
                        <div class="mb-3"><label class="form-label">Harga Beli</label><input type="number" class="form-control" name="harga_beli" value="<?= $data['harga_beli']; ?>" required></div>
                        <div class="mb-3"><label class="form-label">Harga Jual</label><input type="number" class="form-control" name="harga_jual" value="<?= $data['harga_jual']; ?>" required></div>
                        <div class="mb-3"><label class="form-label">Stok</label><input type="number" class="form-control" name="stok" value="<?= $data['stok']; ?>" required></div>
                        <div class="mb-3"><label class="form-label">Foto Produk (Kosongkan jika tidak diubah)</label><br><img src="<?= $foto; ?>" width="100" class="mb-2"><input type="file" class="form-control" name="foto_produk"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div></div>
        </div>
        <div class="modal fade" id="hapusProdukModal<?= $id_produk; ?>" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Hapus Produk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="produk.php"><input type="hidden" name="action" value="hapus"><div class="modal-body"><input type="hidden" name="id_produk" value="<?= $id_produk; ?>"><p>Yakin hapus <strong><?= htmlspecialchars($data['nama_produk']); ?></strong>?</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Hapus</button></div></form></div></div>
        </div>
        <?php } ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script>
            window.addEventListener('DOMContentLoaded', event => {
                const datatablesSimple = document.getElementById('datatablesSimple');
                if (datatablesSimple) { new simpleDatatables.DataTable(datatablesSimple); }
            });
        </script>
    </body>
</html>