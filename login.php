<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    $role = isset($_SESSION['role']) ? trim((string)$_SESSION['role']) : 'User';
    if ($role === 'Admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

$error_message = '';
$success_message = '';
$needs_setup = false;

$countQuery = $koneksi->query('SELECT COUNT(*) AS total FROM tb_pengguna');
if ($countQuery) {
    $countData = $countQuery->fetch_assoc();
    $needs_setup = ((int) $countData['total']) === 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setup_admin') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($nama === '' || $username === '' || $password === '' || $password_confirm === '') {
        $error_message = 'Semua kolom wajib diisi.';
        $needs_setup = true;
    } elseif ($password !== $password_confirm) {
        $error_message = 'Konfirmasi password tidak cocok.';
        $needs_setup = true;
    } else {
        $checkStmt = $koneksi->prepare('SELECT 1 FROM tb_pengguna WHERE username = ? LIMIT 1');
        if ($checkStmt) {
            $checkStmt->bind_param('s', $username);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $error_message = 'Username sudah digunakan. Gunakan username lain.';
                $needs_setup = true;
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $role = 'Admin';
                $status = 'Aktif';
                $insertStmt = $koneksi->prepare('INSERT INTO tb_pengguna (nama, username, password, role, status) VALUES (?, ?, ?, ?, ?)');
                if ($insertStmt) {
                    $insertStmt->bind_param('sssss', $nama, $username, $hashedPassword, $role, $status);
                    if ($insertStmt->execute()) {
                        $success_message = 'Akun administrator berhasil dibuat. Silakan login menggunakan kredensial baru.';
                        $needs_setup = false;
                    } else {
                        $error_message = 'Gagal menyimpan akun administrator. Silakan coba lagi.';
                        $needs_setup = true;
                    }
                    $insertStmt->close();
                } else {
                    $error_message = 'Terjadi kesalahan pada server. Silakan coba lagi.';
                    $needs_setup = true;
                }
            }
            $checkStmt->close();
        } else {
            $error_message = 'Terjadi kesalahan pada server. Silakan coba lagi.';
            $needs_setup = true;
        }
    }
}

if (! $needs_setup && $_SERVER['REQUEST_METHOD'] === 'POST' && (! isset($_POST['action']) || $_POST['action'] !== 'setup_admin')) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error_message = 'Username dan password wajib diisi.';
    } else {
        $stmt = $koneksi->prepare('SELECT id_pengguna, nama, username, password, role, status FROM tb_pengguna WHERE username = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (! $user) {
                $error_message = 'Username atau password salah.';
            } elseif ($user['status'] !== 'Aktif') {
                $error_message = 'Akun Anda tidak aktif. Silakan hubungi administrator.';
            } else {
                $storedPassword = $user['password'];
                $passwordMatches = false;

                if ($storedPassword === '') {
                    $passwordMatches = $password === '';
                } else {
                    if (password_verify($password, $storedPassword)) {
                        $passwordMatches = true;
                    } elseif (hash_equals($storedPassword, $password)) {
                        $passwordMatches = true;
                    } elseif (hash_equals($storedPassword, md5($password))) {
                        $passwordMatches = true;
                    }
                }

                if ($passwordMatches) {
                    $_SESSION['user_id'] = $user['id_pengguna'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['role'] = $user['role'];

                    // Redirect berdasarkan role
                    if ($user['role'] === 'Admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: user/dashboard.php');
                    }
                    exit;
                }

                $error_message = 'Username atau password salah.';
            }
        } else {
            $error_message = 'Terjadi kesalahan pada server. Silakan coba lagi.';
        }
    }
}
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <head>
    <title>Login | Sistem Monitoring Kendaraan</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Login Sistem Monitoring dan Maintenance Kendaraan PT Borneo Sarana Margasana." />
    <meta name="keywords" content="login, monitoring kendaraan, maintenance kendaraan" />
    <meta name="author" content="PT Borneo Sarana Margasana" />
    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="assets/fonts/feather.css" />
    <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="assets/fonts/material.css" />
    <link rel="stylesheet" href="assets/css/style.css" id="main-style-link" />
    <style>
      body {
        background: linear-gradient(135deg, rgba(16, 76, 110, 0.95), rgba(6, 37, 63, 0.95));
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .auth-wrapper {
        backdrop-filter: blur(12px);
        background-color: rgba(255, 255, 255, 0.05);
        border-radius: 24px;
        padding: 40px;
        width: min(100%, 960px);
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
        position: relative;
      }

      .auth-wrapper::after {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 26px;
        padding: 2px;
        background: linear-gradient(135deg, rgba(0, 173, 255, 0.7), rgba(0, 255, 214, 0.4));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
      }

      .auth-card {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 40px;
        align-items: center;
      }

      .auth-brand {
        color: #fff;
        text-align: center;
      }

      .auth-brand img {
        max-width: 180px;
        margin-bottom: 24px;
        filter: drop-shadow(0 6px 20px rgba(0, 0, 0, 0.3));
      }

      .auth-brand h2 {
        font-weight: 600;
        font-size: 1.75rem;
        margin-bottom: 16px;
      }

      .auth-brand p {
        font-size: 1rem;
        line-height: 1.6;
        opacity: 0.85;
      }

      .auth-form-card {
        background: rgba(255, 255, 255, 0.96);
        border-radius: 20px;
        padding: 32px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
      }

      .auth-form-card h4 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 24px;
        text-align: center;
        color: #103c66;
      }

      .form-control {
        border-radius: 12px;
        padding: 14px 16px;
        border: 1px solid rgba(16, 60, 102, 0.2);
        transition: all 0.2s ease-in-out;
      }

      .form-control:focus {
        border-color: rgba(0, 173, 255, 0.7);
        box-shadow: 0 0 0 4px rgba(0, 173, 255, 0.15);
      }

      .btn-primary {
        border-radius: 12px;
        padding: 12px 20px;
        background: linear-gradient(135deg, #0c6fb4, #08a4d6);
        border: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }

      .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(12, 111, 180, 0.3);
      }

      .auth-footer {
        margin-top: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        color: #667085;
      }

      .auth-footer a {
        color: #0c6fb4;
        font-weight: 600;
      }

      @media (max-width: 768px) {
        body {
          background: linear-gradient(160deg, rgba(16, 76, 110, 0.98), rgba(6, 37, 63, 0.98));
          padding: 24px;
        }

        .auth-wrapper {
          padding: 24px;
        }

        .auth-form-card {
          padding: 24px;
        }

        .auth-brand img {
          max-width: 140px;
        }
      }
    </style>
  </head>
  <body>
    <div class="auth-wrapper">
      <div class="auth-card">
        <div class="auth-brand">
          <img src="assets/images/bsm.png" alt="PT Borneo Sarana Margasana" />
          <h2>PT Borneo Sarana Margasana</h2>
          <?php if ($needs_setup): ?>
            <p>
              Selamat datang di Sistem Monitoring dan Maintenance Kendaraan. Sebelum menggunakan dashboard,
              silakan buat akun administrator pertama untuk mengelola armada dan pengguna.
            </p>
          <?php else: ?>
            <p>
              Sistem Monitoring dan Maintenance Kendaraan. Pantau operasional armada,
              jadwal perawatan, serta biaya operasional dalam satu dashboard terpadu.
            </p>
          <?php endif; ?>
        </div>
        <div class="auth-form-card">
          <?php if ($needs_setup): ?>
            <h4>Registrasi Administrator</h4>
            <?php if ($error_message !== ''): ?>
              <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
              </div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
              <input type="hidden" name="action" value="setup_admin" />
              <div class="mb-3">
                <label class="form-label" for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" class="form-control" placeholder="Masukkan nama" required />
              </div>
              <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required />
              </div>
              <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required />
              </div>
              <div class="mb-4">
                <label class="form-label" for="password_confirm">Konfirmasi Password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Ulangi password" required />
              </div>
              <button type="submit" class="btn btn-primary w-full">Simpan dan Lanjutkan</button>
            </form>
            <div class="auth-footer">
              <span>Sudah memiliki instruksi khusus?</span>
              <a href="mailto:support@bsm.co.id">Hubungi Dukungan</a>
            </div>
          <?php else: ?>
            <h4>Masuk ke Dashboard</h4>
            <?php if ($error_message !== ''): ?>
              <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
              </div>
            <?php endif; ?>
            <?php if ($success_message !== ''): ?>
              <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
              </div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
              <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required />
              </div>
              <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required />
              </div>
              <div class="flex justify-between items-center mb-4">
                <div class="form-check">
                  <input class="form-check-input input-primary" type="checkbox" id="rememberMe" name="remember" />
                  <label class="form-check-label text-muted" for="rememberMe">Ingat saya</label>
                </div>
                <a href="#" class="text-primary-500">Lupa Password?</a>
              </div>
              <button type="submit" class="btn btn-primary w-full">Masuk</button>
            </form>
            <div class="auth-footer">
              <span>Butuh bantuan?</span>
              <a href="mailto:support@bsm.co.id">Hubungi Administrator</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <script src="assets/js/plugins/simplebar.min.js"></script>
    <script src="assets/js/plugins/popper.min.js"></script>
    <script src="assets/js/icon/custom-icon.js"></script>
    <script src="assets/js/plugins/feather.min.js"></script>
    <script src="assets/js/component.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/script.js"></script>
    <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]"></div>
  </body>
</html>
