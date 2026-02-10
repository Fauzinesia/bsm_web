<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// Ambil data pengguna untuk form
$pengguna = null;
if ($id > 0) {
    $stmt = $koneksi->prepare('SELECT id_pengguna, nama, username, role, status FROM tb_pengguna WHERE id_pengguna = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pengguna = $result->fetch_assoc();
        $stmt->close();
    }
}

if (! $pengguna) {
    header('Location: pengguna.php?error=not_found');
    exit;
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim($_POST['nama'] ?? '');
    $role   = $_POST['role'] ?? 'User';
    $status = $_POST['status'] ?? 'Aktif';
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '') {
        $error = 'Nama wajib diisi.';
    }

    if (! in_array($role, ['Admin', 'User'], true)) {
        $role = 'User';
    }
    if (! in_array($status, ['Aktif', 'Nonaktif'], true)) {
        $status = 'Aktif';
    }

    if ($error === '') {
        // Tentukan query update, password opsional
        if ($password !== '') {
            if ($password !== $konfirmasi_password) {
                $error = 'Konfirmasi password tidak cocok.';
            } elseif (strlen($password) < 6) {
                $error = 'Password minimal 6 karakter.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $koneksi->prepare('UPDATE tb_pengguna SET nama = ?, role = ?, status = ?, password = ? WHERE id_pengguna = ?');
                if ($stmt) {
                    $stmt->bind_param('ssssi', $nama, $role, $status, $hash, $id);
                    if ($stmt->execute()) {
                        header('Location: pengguna.php?status=updated');
                        exit;
                    } else {
                        $error = 'Gagal memperbarui data.';
                    }
                    $stmt->close();
                } else {
                    $error = 'Gagal menyiapkan query.';
                }
            }
        } else {
            $stmt = $koneksi->prepare('UPDATE tb_pengguna SET nama = ?, role = ?, status = ? WHERE id_pengguna = ?');
            if ($stmt) {
                $stmt->bind_param('sssi', $nama, $role, $status, $id);
                if ($stmt->execute()) {
                    header('Location: pengguna.php?status=updated');
                    exit;
                } else {
                    $error = 'Gagal memperbarui data.';
                }
                $stmt->close();
            } else {
                $error = 'Gagal menyiapkan query.';
            }
        }
    }
}

$page_title = 'Edit Pengguna';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Edit Data Pengguna</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item"><a href="pengguna.php">Data Pengguna</a></li>
              <li class="breadcrumb-item" aria-current="page">Edit</li>
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
                <?php if ($error !== ''): ?>
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ti ti-alert-circle me-2"></i><?php echo e($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
                <?php endif; ?>

                <form method="POST">
                  <div class="mb-3">
                    <label class="form-label" for="nama">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama" name="nama" required maxlength="100" value="<?php echo e($pengguna['nama'] ?? ''); ?>">
                  </div>

                  <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" value="<?php echo e($pengguna['username'] ?? ''); ?>" disabled>
                    <small class="text-muted">Username tidak dapat diubah.</small>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label" for="role">Role / Hak Akses</label>
                        <select class="form-select" id="role" name="role" required>
                          <option value="User" <?php echo (($pengguna['role'] ?? 'User') === 'User') ? 'selected' : ''; ?>>User</option>
                          <option value="Admin" <?php echo (($pengguna['role'] ?? 'User') === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label" for="status">Status Akun</label>
                        <select class="form-select" id="status" name="status" required>
                          <option value="Aktif" <?php echo (($pengguna['status'] ?? 'Aktif') === 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                          <option value="Nonaktif" <?php echo (($pengguna['status'] ?? 'Aktif') === 'Nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <hr class="my-3">
                  <h6 class="mb-2">Ubah Password (Opsional)</h6>
                  <div class="mb-3">
                    <label class="form-label" for="password">Password Baru</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="6" maxlength="255" placeholder="Kosongkan jika tidak mengubah">
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="konfirmasi_password">Konfirmasi Password Baru</label>
                    <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" minlength="6" maxlength="255" placeholder="Ulangi password baru">
                  </div>

                  <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-2"></i>Simpan Perubahan</button>
                    <a href="pengguna.php" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-2"></i>Kembali</a>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>