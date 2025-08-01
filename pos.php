<?php
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// Logika untuk menyimpan transaksi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selesaikan_transaksi'])) {
    $id_pelanggan = $_POST['id_pelanggan'] ? (int)$_POST['id_pelanggan'] : null;
    $tipe_pembayaran = $_POST['tipe_pembayaran'];
    $total_harga = (float)$_POST['total_harga'];
    $keranjang = json_decode($_POST['keranjang_json'], true);
    $waktu_transaksi = !empty($_POST['waktu_transaksi']) ? $_POST['waktu_transaksi'] : date('Y-m-d H:i:s');
    
    if (empty($keranjang) || !is_array($keranjang)) {
        $_SESSION['pesan_error'] = "Keranjang belanja kosong.";
        header("location: pos.php");
        exit();
    }

    mysqli_begin_transaction($koneksi);
    try {
        if ($tipe_pembayaran == 'Lunas') {
            $uang_bayar = (float)$_POST['uang_bayar'];
            $uang_kembali = $uang_bayar - $total_harga;
            if ($uang_bayar < $total_harga) { throw new Exception("Uang bayar tidak cukup."); }
            $stmt1 = mysqli_prepare($koneksi, "INSERT INTO penjualan (id_pelanggan, tipe_pembayaran, waktu_transaksi, total_harga, uang_bayar, uang_kembali) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt1, "isssdd", $id_pelanggan, $tipe_pembayaran, $waktu_transaksi, $total_harga, $uang_bayar, $uang_kembali);
        } else if ($tipe_pembayaran == 'Utang') {
            if (empty($id_pelanggan)) { throw new Exception("Pilih pelanggan terlebih dahulu untuk transaksi utang."); }
            $uang_bayar = 0; $uang_kembali = 0;
            $stmt1 = mysqli_prepare($koneksi, "INSERT INTO penjualan (id_pelanggan, tipe_pembayaran, waktu_transaksi, total_harga, uang_bayar, uang_kembali) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt1, "isssdd", $id_pelanggan, $tipe_pembayaran, $waktu_transaksi, $total_harga, $uang_bayar, $uang_kembali);
        }
        
        mysqli_stmt_execute($stmt1);
        $id_penjualan_baru = mysqli_insert_id($koneksi);

        if ($tipe_pembayaran == 'Utang') {
            $keterangan_utang = "Utang dari transaksi NOTA-" . $id_penjualan_baru;
            $stmt_utang = mysqli_prepare($koneksi, "INSERT INTO riwayat_utang (id_pelanggan, id_penjualan, jumlah, keterangan) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_utang, "iids", $id_pelanggan, $id_penjualan_baru, $total_harga, $keterangan_utang);
            mysqli_stmt_execute($stmt_utang);
            $stmt_update_utang = mysqli_prepare($koneksi, "UPDATE pelanggan SET total_utang = total_utang + ? WHERE id_pelanggan = ?");
            mysqli_stmt_bind_param($stmt_update_utang, "di", $total_harga, $id_pelanggan);
            mysqli_stmt_execute($stmt_update_utang);
        }

        $stmt_detail = mysqli_prepare($koneksi, "INSERT INTO detail_penjualan (id_penjualan, id_produk, jumlah, subtotal) VALUES (?, ?, ?, ?)");
        $stmt_stok = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
        foreach ($keranjang as $item) {
            $id_produk = (int)$item['id'];
            $jumlah = (int)$item['qty'];
            $subtotal = (float)$item['subtotal'];
            mysqli_stmt_bind_param($stmt_detail, "iiid", $id_penjualan_baru, $id_produk, $jumlah, $subtotal);
            mysqli_stmt_execute($stmt_detail);
            mysqli_stmt_bind_param($stmt_stok, "ii", $jumlah, $id_produk);
            mysqli_stmt_execute($stmt_stok);
        }

        mysqli_commit($koneksi);
        header("location: nota.php?id=" . $id_penjualan_baru);
        exit();
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['pesan_error'] = "Transaksi Gagal: " . $e->getMessage();
        header("location: pos.php");
        exit();
    }
}
// ... Logika PHP lainnya tetap sama ...
if (isset($_POST['tambah_pelanggan'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $query = "INSERT INTO pelanggan (nama_pelanggan, telepon, alamat) VALUES ('$nama', '$telepon', '$alamat')";
    if(mysqli_query($koneksi, $query)){
        $_SESSION['pesan_sukses'] = "Pelanggan baru berhasil ditambahkan.";
    } else {
        $_SESSION['pesan_error'] = "Gagal menambah pelanggan.";
    }
    header("location: pos.php");
    exit();
}
$queryProduk = mysqli_query($koneksi, "SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk ASC");
$queryPelanggan = mysqli_query($koneksi, "SELECT * FROM pelanggan ORDER BY nama_pelanggan ASC");
$pesan = '';
if(isset($_SESSION['pesan_sukses'])){ $pesan = "<div class='alert alert-success' role='alert'>" . $_SESSION['pesan_sukses'] . "</div>"; unset($_SESSION['pesan_sukses']); }
elseif(isset($_SESSION['pesan_error'])){ $pesan = "<div class='alert alert-danger' role='alert'>" . $_SESSION['pesan_error'] . "</div>"; unset($_SESSION['pesan_error']); }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Transaksi (POS) - Kasir Sembako</title>
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
            .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; max-height: 65vh; overflow-y: auto; }
            .product-card { cursor: pointer; border: 1px solid #ddd; border-radius: 0.375rem; padding: 0.5rem; text-align: center; display: flex; flex-direction: column; justify-content: space-between; }
            .product-card:hover { background-color: #f1f1f1; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
            .product-card img { max-width: 100%; height: 80px; object-fit: contain; margin-bottom: 0.5rem; }
            .product-name { font-weight: bold; font-size: 0.9rem; }
            .product-price { color: #007bff; font-size: 0.85rem; }
            #keranjang-total { font-size: 1.5rem; font-weight: bold; }
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
                            <a class="nav-link active" href="pos.php"><div class="sb-nav-link-icon"><i class="fas fa-cash-register"></i></div>Transaksi (POS)</a>
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
                    <div class="sb-sidenav-footer"><div class="small">Logged in as:</div><?= htmlspecialchars($_SESSION['username']); ?></div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Transaksi Penjualan (POS)</h1>
                        <ol class="breadcrumb mb-4"><li class="breadcrumb-item"><a href="dashbord.php">Dashboard</a></li><li class="breadcrumb-item active">POS</li><li class="breadcrumb-item ms-auto"><b>Waktu: </b><span id="waktu-sekarang"></span></li></ol>
                        <?= $pesan; ?>
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" id="searchInput" class="form-control" placeholder="Cari nama produk...">
                                        </div>
                                    </div>
                                    <div class="card-body product-grid">
                                        <?php while($p = mysqli_fetch_array($queryProduk)) { $foto = $p['foto_produk'] ? 'uploads/' . $p['foto_produk'] : 'assets/img/placeholder.png'; ?>
                                            <div class="product-card" onclick="tambahKeKeranjang(<?= $p['id_produk']; ?>, '<?= htmlspecialchars(addslashes($p['nama_produk'])); ?>', <?= $p['harga_jual']; ?>)">
                                                <img src="<?= $foto; ?>" alt="<?= htmlspecialchars($p['nama_produk']); ?>">
                                                <div class="product-name"><?= htmlspecialchars($p['nama_produk']); ?></div>
                                                <div class="product-price">Rp <?= number_format($p['harga_jual']); ?></div>
                                                <div class="small text-muted">Stok: <?= $p['stok']; ?></div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <form method="POST" action="pos.php">
                                    <div class="card mb-4">
                                        <div class="card-header"><i class="fas fa-shopping-cart me-1"></i>Keranjang & Pembayaran</div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-1"><label for="pelanggan" class="form-label">Pelanggan</label><button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tambahPelangganModal"><i class="fas fa-plus"></i></button></div>
                                            <select class="form-select mb-3" name="id_pelanggan" id="pelangganSelect" onchange="cekTipePembayaran()">
                                                <option value="">-- Pelanggan Umum --</option>
                                                <?php while($pl = mysqli_fetch_array($queryPelanggan)) { ?>
                                                    <option value="<?= $pl['id_pelanggan']; ?>"><?= htmlspecialchars($pl['nama_pelanggan']); ?></option>
                                                <?php } ?>
                                            </select>
                                            <div id="keranjang-item" style="max-height: 25vh; overflow-y: auto;"><p class="text-center text-muted">Keranjang masih kosong</p></div><hr>
                                            <div class="d-flex justify-content-between"><span class="fs-5">Total</span><span id="keranjang-total" class="fs-5">Rp 0</span></div>
                                        </div>
                                        <div class="card-footer">
                                            <input type="hidden" id="keranjang_json" name="keranjang_json"><input type="hidden" id="total_harga" name="total_harga">
                                            <div class="mb-3"><label class="form-label">Tipe Pembayaran</label><div><input type="radio" class="btn-check" name="tipe_pembayaran" id="lunas" value="Lunas" checked onchange="toggleUangBayar()"><label class="btn btn-outline-success" for="lunas">Lunas</label><input type="radio" class="btn-check" name="tipe_pembayaran" id="utang" value="Utang" onchange="toggleUangBayar()" disabled><label class="btn btn-outline-danger" for="utang">Utang</label></div></div>
                                            <div id="kolomUangBayar"><div class="mb-3"><label for="uangBayar" class="form-label">Uang Bayar (Rp)</label><input type="number" class="form-control" id="uangBayar" name="uang_bayar" onkeyup="hitungKembalian()"></div><div class="mb-3"><label for="uangKembali" class="form-label">Uang Kembali (Rp)</label><input type="text" class="form-control" id="uangKembali" name="uang_kembali" readonly></div></div>
                                            <div class="mb-3"><input type="datetime-local" class="form-control form-control-sm" name="waktu_transaksi" title="Isi untuk mengatur waktu transaksi manual"><small class="text-muted">Kosongkan untuk waktu sekarang (opsional).</small></div>
                                            <button type="submit" id="tombolSelesaikan" name="selesaikan_transaksi" class="btn btn-success w-100" disabled><i class="fas fa-check"></i> Selesaikan Transaksi</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto"><div class="container-fluid px-4"><div class="d-flex align-items-center justify-content-between small"><div class="text-muted">Copyright &copy; Sembako Kelompok 3 2023</div></div></div></footer>
            </div>
        </div>

        <div class="modal fade" id="tambahPelangganModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Tambah Pelanggan Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="pos.php">
                    <div class="modal-body"><div class="mb-3"><label class="form-label">Nama Pelanggan</label><input type="text" class="form-control" name="nama_pelanggan" required></div><div class="mb-3"><label class="form-label">No. Telepon</label><input type="text" class="form-control" name="telepon"></div><div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat" rows="3"></textarea></div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" name="tambah_pelanggan">Simpan</button></div>
                </form>
            </div></div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script>
            // Fungsi Pencarian Produk (BARU)
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('keyup', function() {
                const filter = searchInput.value.toUpperCase();
                const productCards = document.querySelectorAll('.product-card');
                productCards.forEach(card => {
                    const productName = card.querySelector('.product-name').textContent.toUpperCase();
                    if (productName.includes(filter)) {
                        card.style.display = "";
                    } else {
                        card.style.display = "none";
                    }
                });
            });

            // Semua Javascript dari langkah sebelumnya tetap sama
            let keranjang = [];
            function cekTipePembayaran() { const pelangganSelect = document.getElementById('pelangganSelect'); const utangRadio = document.getElementById('utang'); if (pelangganSelect.value !== "") { utangRadio.disabled = false; } else { utangRadio.disabled = true; document.getElementById('lunas').checked = true; toggleUangBayar(); } }
            function toggleUangBayar() { const kolomUangBayar = document.getElementById('kolomUangBayar'); if (document.getElementById('utang').checked) { kolomUangBayar.style.display = 'none'; document.getElementById('uangBayar').required = false; } else { kolomUangBayar.style.display = 'block'; document.getElementById('uangBayar').required = true; } hitungKembalian(); }
            function tambahKeKeranjang(id, nama, harga) { const itemIndex = keranjang.findIndex(item => item.id === id); if (itemIndex > -1) { keranjang[itemIndex].qty++; } else { keranjang.push({ id: id, nama: nama, harga: harga, qty: 1 }); } renderKeranjang(); }
            function ubahJumlah(index, jumlah) { keranjang[index].qty += jumlah; if (keranjang[index].qty <= 0) { keranjang.splice(index, 1); } renderKeranjang(); }
            function renderKeranjang() { const keranjangDiv = document.getElementById('keranjang-item'); const totalSpan = document.getElementById('keranjang-total'); let totalHarga = 0; if (keranjang.length === 0) { keranjangDiv.innerHTML = '<p class="text-center text-muted">Keranjang masih kosong</p>'; } else { keranjangDiv.innerHTML = ''; keranjang.forEach((item, index) => { item.subtotal = item.harga * item.qty; totalHarga += item.subtotal; keranjangDiv.innerHTML += `<div class="d-flex justify-content-between align-items-center mb-2"><div><div>${item.nama}</div><small class="text-muted">Rp ${item.harga.toLocaleString('id-ID')} x ${item.qty}</small></div><div class="d-flex align-items-center"><button class="btn btn-sm btn-outline-secondary me-2" onclick="ubahJumlah(${index}, -1)">-</button><strong>Rp ${item.subtotal.toLocaleString('id-ID')}</strong><button class="btn btn-sm btn-outline-secondary ms-2" onclick="ubahJumlah(${index}, 1)">+</button></div></div>`; }); } totalSpan.textContent = `Rp ${totalHarga.toLocaleString('id-ID')}`; document.getElementById('total_harga').value = totalHarga; document.getElementById('keranjang_json').value = JSON.stringify(keranjang); hitungKembalian(); }
            function hitungKembalian() { const totalBelanja = parseFloat(document.getElementById('total_harga').value) || 0; const uangBayar = parseFloat(document.getElementById('uangBayar').value) || 0; const uangKembaliInput = document.getElementById('uangKembali'); const tombolSelesaikan = document.getElementById('tombolSelesaikan'); let kembalian = uangBayar - totalBelanja; if (document.getElementById('utang').checked && totalBelanja > 0) { tombolSelesaikan.disabled = false; uangKembaliInput.value = ''; } else if (kembalian >= 0 && totalBelanja > 0 && uangBayar > 0) { uangKembaliInput.value = `Rp ${kembalian.toLocaleString('id-ID')}`; tombolSelesaikan.disabled = false; } else { uangKembaliInput.value = ''; tombolSelesaikan.disabled = true; } }
            function updateWaktu() { const now = new Date(); const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' }; document.getElementById('waktu-sekarang').textContent = now.toLocaleDateString('id-ID', options); }
            document.addEventListener('DOMContentLoaded', () => { renderKeranjang(); updateWaktu(); setInterval(updateWaktu, 1000); });
        </script>
    </body>
</html>