<?php
require 'koneksi.php';

// Cek apakah user sudah login, jika ya, arahkan ke dashboard.php
if (isset($_SESSION['username'])) {
    header("location: dashbord.php");
    exit();
}

$error = '';
// Cek apakah form telah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $username = mysqli_real_escape_string($koneksi, $username);
        $query = "SELECT * FROM pengguna WHERE username = '$username'";
        $result = mysqli_query($koneksi, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            // Verifikasi password (Di masa depan, ganti ini dengan password_verify)
            if ($password === $user['password']) {
                $_SESSION['id_pengguna'] = $user['id_pengguna'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                header("location: dashbord.php");
                exit();
            } else {
                $error = "Password yang Anda masukkan salah.";
            }
        } else {
            $error = "Username tidak ditemukan.";
        }
    } else {
        $error = "Username dan Password tidak boleh kosong.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Login - Kasir Sembako Kelompok 3</title>
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
            .bg-login-image {
                /* Ganti URL ini dengan gambar pilihan Anda */
                background: url('https://source.unsplash.com/1200x1200/?groceries,market');
                background-position: center;
                background-size: cover;
            }
            .card-login {
                border-radius: 1rem;
                overflow: hidden; /* Agar gambar tidak keluar dari sudut card */
            }
            .login-form-container {
                padding: 3rem;
            }
            .login-branding {
                color: white;
                background: rgba(0, 0, 0, 0.4); /* Overlay gelap agar tulisan terbaca */
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                padding: 2rem;
            }
        </style>
    </head>
    <body style="background-color: #f8f9fa;">
        <div id="layoutAuthentication">
            <div id="layoutAuthentication_content">
                <main>
                    <div class="container">
                        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
                            <div class="col-lg-10 col-xl-9">
                                <div class="card card-login shadow-lg border-0 my-5">
                                    <div class="card-body p-0">
                                        <div class="row g-0">
                                            <div class="col-lg-6 d-none d-lg-block bg-login-image">
                                                <div class="login-branding">
                                                    <i class="fas fa-store fa-3x mb-3"></i>
                                                    <h2 class="fw-bold">Sembako Kelompok 3</h2>
                                                    <p class="mb-0">Aplikasi Kasir dan Inventaris Modern</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="login-form-container">
                                                    <div class="text-center">
                                                        <h1 class="h4 text-gray-900 mb-4">Selamat Datang Kembali!</h1>
                                                    </div>
                                                    
                                                    <?php if (!empty($error)) { ?>
                                                        <div class="alert alert-danger" role="alert">
                                                            <?= $error; ?>
                                                        </div>
                                                    <?php } ?>

                                                    <form action="login.php" method="POST">
                                                        <div class="form-floating mb-3">
                                                            <input class="form-control" id="inputUsername" name="username" type="text" placeholder="Username" required />
                                                            <label for="inputUsername"><i class="fas fa-user me-2"></i>Username</label>
                                                        </div>
                                                        <div class="form-floating mb-3">
                                                            <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Password" required />
                                                            <label for="inputPassword"><i class="fas fa-lock me-2"></i>Password</label>
                                                        </div>
                                                        <div class="d-grid mt-4">
                                                            <button class="btn btn-primary btn-lg" type="submit">Login</button>
                                                        </div>
                                                    </form>
                                                    <hr>
                                                    <div class="text-center">
                                                        <a class="small" href="index.php">Kembali ke Landing Page</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
    </body>
</html>