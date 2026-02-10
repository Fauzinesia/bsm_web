<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

$userName = isset($_SESSION['nama']) ? $_SESSION['nama'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Pengguna');
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <head>
    <title>Logout | Sistem Monitoring Kendaraan</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Konfirmasi logout Sistem Monitoring dan Maintenance Kendaraan PT Borneo Sarana Margasana." />
    <meta name="keywords" content="logout, monitoring kendaraan, maintenance kendaraan" />
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
        width: min(100%, 760px);
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
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 32px;
        align-items: center;
      }

      .auth-brand {
        color: #fff;
        text-align: center;
      }

      .auth-brand img {
        max-width: 150px;
        margin-bottom: 20px;
        filter: drop-shadow(0 6px 20px rgba(0, 0, 0, 0.3));
      }

      .auth-brand h2 {
        font-weight: 600;
        font-size: 1.6rem;
        margin-bottom: 12px;
      }

      .auth-brand p {
        font-size: 0.95rem;
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
        font-size: 1.45rem;
        font-weight: 600;
        margin-bottom: 16px;
        text-align: center;
        color: #103c66;
      }

      .auth-form-card p {
        color: #51607a;
        text-align: center;
        margin-bottom: 24px;
        line-height: 1.5;
      }

      .btn-outline-secondary,
      .btn-danger {
        border-radius: 12px;
        padding: 12px 20px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }

      .btn-outline-secondary:hover,
      .btn-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(12, 111, 180, 0.2);
      }

      .btn-danger {
        background: linear-gradient(135deg, #f05454, #d72323);
        border: none;
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
          max-width: 120px;
        }
      }
    </style>
  </head>
  <body>
    <div class="auth-wrapper">
      <div class="auth-card">
        <div class="auth-brand">
          <img src="assets/images/bsm.png" alt="PT Borneo Sarana Margasana" />
          <h2>Sampai Jumpa <?php echo htmlspecialchars($userName); ?>!</h2>
          <p>
            Terima kasih telah menjaga armada PT Borneo Sarana Margasana. Pastikan aktivitas operasional
            dan jadwal maintenance selalu terpantau sebelum keluar dari sistem.
          </p>
        </div>
        <div class="auth-form-card">
          <h4>Konfirmasi Logout</h4>
          <p>Anda yakin ingin mengakhiri sesi saat ini? Semua perubahan yang belum tersimpan akan hilang.</p>
          <form method="post">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <a href="admin/dashboard.php" class="btn btn-outline-secondary w-full">Kembali ke Dashboard</a>
              <button type="submit" class="btn btn-danger w-full">Keluar Sekarang</button>
            </div>
          </form>
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
