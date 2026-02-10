<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

// Validasi parameter ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: pengguna.php?error=invalid_id');
    exit;
}

$id_pengguna = (int)$_GET['id'];

// Ambil data pengguna
$stmt = $koneksi->prepare('SELECT id_pengguna, nama, username, role, status, created_at FROM tb_pengguna WHERE id_pengguna = ?');
if (! $stmt) {
    header('Location: pengguna.php?error=database');
    exit;
}
$stmt->bind_param('i', $id_pengguna);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    header('Location: pengguna.php?error=not_found');
    exit;
}
$pengguna = $result->fetch_assoc();
$stmt->close();

$page_title = 'Detail Pengguna';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Detail Pengguna</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item"><a href="pengguna.php">Data Pengguna</a></li>
              <li class="breadcrumb-item" aria-current="page">Detail</li>
            </ul>
          </div>
        </div>

        <div class="row">
          <!-- Panel profil ringkas -->
          <div class="col-lg-4">
            <div class="card">
              <div class="card-body text-center">
                <div class="rounded-full bg-primary-100 text-primary-600 d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                  <i class="ti ti-user" style="font-size: 48px;"></i>
                </div>
                <h4 class="mb-1"><?php echo htmlspecialchars($pengguna['nama']); ?></h4>
                <p class="text-muted mb-2">@<?php echo htmlspecialchars($pengguna['username']); ?></p>
                <?php
                  $role = $pengguna['role'] ?? 'User';
                  $roleClass = ($role === 'Admin') ? 'bg-danger' : 'bg-success';
                  $status = $pengguna['status'] ?? 'Aktif';
                  $statusClass = ($status === 'Nonaktif') ? 'bg-warning' : 'bg-success';
                ?>
                <div class="d-flex gap-2 justify-content-center">
                  <span class="badge <?php echo $roleClass; ?> text-white px-3 py-2"><?php echo htmlspecialchars($role); ?></span>
                  <span class="badge <?php echo $statusClass; ?> text-white px-3 py-2"><?php echo htmlspecialchars($status); ?></span>
                </div>
              </div>
            </div>

            <!-- Aksi -->
            <div class="card">
              <div class="card-body">
                <div class="d-grid gap-2">
                  <a href="edit.php?id=<?php echo (int)$pengguna['id_pengguna']; ?>" class="btn btn-primary">
                    <i class="ti ti-edit me-2"></i>Edit Pengguna
                  </a>
                  <?php if ((int)$pengguna['id_pengguna'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                    <a href="hapus.php?id=<?php echo (int)$pengguna['id_pengguna']; ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus data pengguna ini?');">
                      <i class="ti ti-trash me-2"></i>Hapus Pengguna
                    </a>
                  <?php endif; ?>
                  <a href="pengguna.php" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-2"></i>Kembali ke Daftar
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Informasi detail -->
          <div class="col-lg-8">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-info-circle me-2"></i>Informasi Pengguna</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                      <tr>
                        <td class="text-muted" width="40%">ID Pengguna</td>
                        <td><strong>#<?php echo (int)$pengguna['id_pengguna']; ?></strong></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Nama Lengkap</td>
                        <td><strong><?php echo htmlspecialchars($pengguna['nama']); ?></strong></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Username</td>
                        <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($pengguna['username']); ?></code></td>
                      </tr>
                    </table>
                  </div>
                  <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                      <tr>
                        <td class="text-muted" width="40%">Role</td>
                        <td><?php echo htmlspecialchars($pengguna['role']); ?></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Status</td>
                        <td><?php echo htmlspecialchars($pengguna['status']); ?></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Terdaftar</td>
                        <td><?php echo $pengguna['created_at'] ? date('d M Y H:i', strtotime($pengguna['created_at'])) : '-'; ?></td>
                      </tr>
                      
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-shield-lock me-2"></i>Catatan Keamanan</h5>
              </div>
              <div class="card-body">
                <ul class="mb-0 ps-3">
                  <li class="mb-2">Username bersifat unik dan tidak dapat diubah.</li>
                  <li class="mb-2">Gunakan password yang kuat dan ubah secara berkala.</li>
                  <li class="mb-2">Akun berstatus Nonaktif tidak dapat login ke sistem.</li>
                  <li>Role Admin memiliki akses penuh; gunakan dengan tanggung jawab.</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>