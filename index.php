<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Sistem Kasir dan Inventaris Warung Sembako Kelompok 3" />
        <meta name="author" content="Kelompok 3" />
        <title>Selamat Datang - Sistem Kasir Sembako</title>
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
            /* Custom CSS for Elegant Theme */
            body {
                background-color: #f8f9fa; /* Latar belakang abu-abu sangat muda */
            }
            .navbar {
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .page-header {
                background-color: #ffffff;
                padding: 4rem 0;
                border-bottom: 1px solid #dee2e6;
                text-align: center;
            }
            .page-header h1 {
                font-size: 3rem;
                font-weight: 700;
                color: #212529; /* Hitam */
            }
            .page-header .lead {
                color: #6c757d; /* Abu-abu (secondary) */
                max-width: 600px;
                margin: 1.5rem auto;
            }
            .section {
                padding: 5rem 0;
            }
            .section-title {
                text-align: center;
                margin-bottom: 4rem;
                font-weight: 700;
                color: #212529;
            }
            .feature-card {
                background-color: #ffffff;
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                padding: 2rem;
                text-align: center;
                height: 100%;
                transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            }
            .feature-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            }
            .feature-card .icon {
                font-size: 3rem;
                color: #212529; /* Ikon warna hitam */
                margin-bottom: 1.5rem;
            }
            .feature-card h3 {
                font-size: 1.25rem;
                font-weight: 600;
                color: #343a40;
            }
            .feature-card p {
                color: #6c757d;
            }
            .cta-section {
                background-color: #343a40; /* Abu-abu gelap */
                color: #f8f9fa;
                padding: 5rem 0;
                text-align: center;
            }
            .footer {
                background-color: #212529; /* Hitam */
                color: #adb5bd;
            }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
            <div class="container px-4">
                <a class="navbar-brand fw-bold" href="index.php">
                    <i class="fas fa-store me-2"></i>
                    Sembako Kelompok 3
                </a>
                <a href="login.php" class="btn btn-dark ms-auto">
                    <i class="fas fa-sign-in-alt me-2"></i>Login Kasir
                </a>
            </div>
        </nav>

        <header class="page-header">
            <div class="container">
                <h1 class="display-5">Solusi Digital untuk Warung Anda</h1>
                <p class="lead">
                    Tingkatkan efisiensi dan maksimalkan keuntungan dengan sistem kasir dan manajemen inventaris yang terintegrasi penuh.
                </p>
            </div>
        </header>

        <section class="section">
            <div class="container px-4">
                <h2 class="section-title">Fitur Unggulan Kami</h2>
                <div class="row gx-4 gy-4">
                    <div class="col-lg-4">
                        <div class="feature-card">
                            <div class="icon"><i class="fas fa-cash-register"></i></div>
                            <h3>Kasir Cepat & Fleksibel</h3>
                            <p>Antarmuka POS yang responsif dengan pencarian produk, pilihan pelanggan, dan kemampuan untuk mencatat pembayaran tunai maupun utang.</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="feature-card">
                            <div class="icon"><i class="fas fa-boxes"></i></div>
                            <h3>Manajemen Inventaris Lengkap</h3>
                            <p>Kelola data produk, supplier, catat stok masuk dengan detail, dan lakukan penyesuaian stok untuk barang rusak atau hilang.</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="feature-card">
                            <div class="icon"><i class="fas fa-users"></i></div>
                            <h3>Manajemen Pelanggan & Utang</h3>
                            <p>Catat data pelanggan setia, lacak riwayat utang mereka, dan kelola pembayaran piutang dengan mudah di satu tempat.</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="feature-card">
                            <div class="icon"><i class="fas fa-chart-bar"></i></div>
                            <h3>Laporan Analitis</h3>
                            <p>Dapatkan wawasan bisnis dari laporan keuangan, performa per produk, produk terlaris, dan visualisasi data melalui grafik interaktif.</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="feature-card">
                            <div class="icon"><i class="fas fa-receipt"></i></div>
                            <h3>Riwayat & Nota Digital</h3>
                            <p>Semua transaksi tercatat rapi. Lihat detail nota dalam bentuk pop-up modern atau cetak struk fisik kapan pun dibutuhkan.</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="feature-card">
                            <div class="icon"><i class="fas fa-file-csv"></i></div>
                            <h3>Ekspor Data</h3>
                            <p>Unduh laporan penjualan dan performa produk Anda dalam format CSV yang rapi untuk diolah lebih lanjut di Excel atau software lainnya.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="container">
                <h2>Siap Mengelola Toko Anda Lebih Baik?</h2>
                <p class="lead my-4">Masuk ke dasbor untuk mulai mencatat penjualan dan mengelola inventaris Anda hari ini.</p>
                <a href="login.php" class="btn btn-light btn-lg">
                    <i class="fas fa-arrow-right me-2"></i>Lanjutkan ke Login
                </a>
            </div>
        </section>

        <footer class="footer py-4">
            <div class="container px-4">
                <p class="m-0 text-center small">Copyright &copy; Sembako Kelompok 3 2023</p>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    </body>
</html>