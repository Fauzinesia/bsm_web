<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
$page_title = 'Tambah Pengguna';

$errorMessage = '';

// Proses form jika metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    $role = $_POST['role'] ?? 'User';
    $status = $_POST['status'] ?? 'Aktif';

    // Validasi input wajib
    if (empty($nama) || empty($username) || empty($password)) {
        $errorMessage = 'Nama, username, dan password wajib diisi!';
    } elseif ($password !== $konfirmasi_password) {
        $errorMessage = 'Konfirmasi password tidak cocok!';
    } else {
        // Validasi role
        if (!in_array($role, ['Admin', 'User'], true)) {
            $role = 'User';
        }

        // Validasi status
        if (!in_array($status, ['Aktif', 'Nonaktif'], true)) {
            $status = 'Aktif';
        }

        // Check apakah username sudah ada
        $sqlCheck = 'SELECT id_pengguna FROM tb_pengguna WHERE username = ?';
        $stmtCheck = $koneksi->prepare($sqlCheck);

        if (!$stmtCheck) {
            $errorMessage = 'Terjadi kesalahan pada sistem. Silakan hubungi administrator.';
        } else {
            $stmtCheck->bind_param('s', $username);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();

            if ($resultCheck->num_rows > 0) {
                $errorMessage = 'Username sudah digunakan. Silakan pilih username lain.';
                $stmtCheck->close();
            } else {
                $stmtCheck->close();

                // Hash password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Insert data
                $sql = 'INSERT INTO tb_pengguna (nama, username, password, role, status) VALUES (?, ?, ?, ?, ?)';
                $stmt = $koneksi->prepare($sql);

                if (!$stmt) {
                    $errorMessage = 'Terjadi kesalahan pada sistem. Silakan hubungi administrator.';
                } else {
                    $stmt->bind_param('sssss', $nama, $username, $passwordHash, $role, $status);

                    if ($stmt->execute()) {
                        $stmt->close();
                        $koneksi->close();
                        header('Location: pengguna.php?status=created');
                        exit;
                    } else {
                        $errorMessage = 'Gagal menyimpan data ke database. Silakan coba lagi.';
                        $stmt->close();
                    }
                }
            }
        }
    }
}

include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Tambah Data Pengguna</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item"><a href="pengguna.php">Data Pengguna</a></li>
              <li class="breadcrumb-item" aria-current="page">Tambah</li>
            </ul>
          </div>
        </div>

        <div class="row">
          <div class="col-12 col-lg-8 col-xl-6">
            <div class="card">
              <div class="card-header">
                <h5>Informasi Pengguna</h5>
              </div>
              <div class="card-body">
                <?php if ($errorMessage !== ''): ?>
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ti ti-alert-circle me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
                <?php endif; ?>
                <form action="" method="POST">
                  <div class="mb-3">
                    <label class="form-label" for="nama">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan nama lengkap" required maxlength="100" autofocus value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">
                    <small class="text-muted">Nama lengkap pengguna yang akan ditampilkan di sistem.</small>
                  </div>

                  <div class="mb-3">
                    <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required maxlength="50" pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    <small class="text-muted">Username untuk login (huruf, angka, dan underscore saja).</small>
                  </div>

                  <div class="mb-3">
                    <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required minlength="6" maxlength="255">
                      <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i data-feather="eye" id="eyeIcon"></i>
                      </button>
                    </div>
                    <small class="text-muted">Minimal 6 karakter. Gunakan kombinasi huruf dan angka.</small>
                  </div>

                  <div class="mb-3">
                    <label class="form-label" for="konfirmasi_password">Konfirmasi Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password" required minlength="6" maxlength="255">
                      <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                        <i data-feather="eye" id="eyeIconConfirm"></i>
                      </button>
                    </div>
                    <small class="text-muted">Ulangi password untuk konfirmasi.</small>
                  </div>

                  <div class="mb-3">
                    <label class="form-label" for="role">Role / Hak Akses <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" name="role" required>
                      <option value="User" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'User') ? 'selected' : ''; ?>>User (Petugas Lapangan)</option>
                      <option value="Admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Admin') ? 'selected' : ''; ?>>Admin (Akses Penuh)</option>
                    </select>
                    <small class="text-muted">
                      <strong>User:</strong> Akses terbatas untuk operasional. 
                      <strong>Admin:</strong> Akses penuh ke semua fitur.
                    </small>
                  </div>

                  <div class="mb-3">
                    <label class="form-label" for="status">Status Akun <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                      <option value="Aktif" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                      <option value="Nonaktif" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                    <small class="text-muted">Akun nonaktif tidak dapat login ke sistem.</small>
                  </div>

                  <hr class="my-4">
                  
                  <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                      <i class="ti ti-device-floppy me-2"></i>Simpan Data
                    </button>
                    <a href="pengguna.php" class="btn btn-outline-secondary">
                      <i class="ti ti-arrow-left me-2"></i>Kembali
                    </a>
                  </div>
                </form>
              </div>
            </div>
          </div>
          
          <div class="col-12 col-lg-4 col-xl-6">
            <div class="card">
              <div class="card-header">
                <h5>Panduan</h5>
              </div>
              <div class="card-body">
                <div class="alert alert-info">
                  <h6 class="alert-heading"><i class="ti ti-info-circle me-2"></i>Informasi Penting</h6>
                  <hr>
                  <ul class="mb-0 ps-3">
                    <li class="mb-2"><strong>Username</strong> harus unik dan tidak bisa diubah setelah dibuat.</li>
                    <li class="mb-2"><strong>Password</strong> akan dienkripsi secara otomatis untuk keamanan.</li>
                    <li class="mb-2"><strong>Role Admin</strong> memiliki akses penuh untuk mengelola data kendaraan, pengguna, dan laporan.</li>
                    <li class="mb-2"><strong>Role User</strong> hanya dapat mengakses fitur operasional kendaraan.</li>
                    <li><strong>Status Nonaktif</strong> akan menonaktifkan akses login tanpa menghapus data.</li>
                  </ul>
                </div>
                
                <div class="alert alert-warning mt-3">
                  <h6 class="alert-heading"><i class="ti ti-shield-lock me-2"></i>Keamanan Password</h6>
                  <hr>
                  <p class="mb-2">Rekomendasi password yang kuat:</p>
                  <ul class="mb-0 ps-3">
                    <li>Minimal 8 karakter</li>
                    <li>Kombinasi huruf besar dan kecil</li>
                    <li>Mengandung angka</li>
                    <li>Tambahkan karakter khusus (!@#$%)</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Toggle password visibility
      document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        
        if (password.type === 'password') {
          password.type = 'text';
          icon.setAttribute('data-feather', 'eye-off');
        } else {
          password.type = 'password';
          icon.setAttribute('data-feather', 'eye');
        }
        feather.replace();
      });

      document.getElementById('togglePasswordConfirm').addEventListener('click', function() {
        const password = document.getElementById('konfirmasi_password');
        const icon = document.getElementById('eyeIconConfirm');
        
        if (password.type === 'password') {
          password.type = 'text';
          icon.setAttribute('data-feather', 'eye-off');
        } else {
          password.type = 'password';
          icon.setAttribute('data-feather', 'eye');
        }
        feather.replace();
      });

      // Password match validation
      document.querySelector('form').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('konfirmasi_password').value;
        
        if (password !== confirm) {
          e.preventDefault();
          alert('Konfirmasi password tidak cocok!');
          document.getElementById('konfirmasi_password').focus();
        }
      });
    </script>
<?php 
$koneksi->close();
include dirname(__DIR__, 2) . '/includes/footer.php'; 
?>
