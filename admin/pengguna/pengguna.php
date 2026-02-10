<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
$page_title = 'Data Pengguna';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';

$alertType = $_GET['status'] ?? '';
$alertMessage = '';

switch ($alertType) {
    case 'created':
        $alertMessage = 'Data pengguna berhasil ditambahkan.';
        break;
    case 'updated':
        $alertMessage = 'Data pengguna berhasil diperbarui.';
        break;
    case 'deleted':
        $alertMessage = 'Data pengguna berhasil dihapus.';
        break;
    case 'error':
        $alertMessage = 'Terjadi kesalahan. Silakan coba lagi.';
        break;
}

// Filter parameters
$filterRole = $_GET['filter_role'] ?? 'all';
$filterStatus = $_GET['filter_status'] ?? 'all';
$filterSearch = $_GET['search'] ?? '';

// Build SQL query with filters
$sql = 'SELECT id_pengguna, nama, username, role, status, created_at FROM tb_pengguna WHERE 1=1';
$params = [];
$types = '';

if ($filterRole !== 'all') {
    $sql .= ' AND role = ?';
    $params[] = $filterRole;
    $types .= 's';
}

if ($filterStatus !== 'all') {
    $sql .= ' AND status = ?';
    $params[] = $filterStatus;
    $types .= 's';
}

if ($filterSearch !== '') {
    $sql .= ' AND (nama LIKE ? OR username LIKE ?)';
    $searchParam = '%' . $filterSearch . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

$sql .= ' ORDER BY created_at DESC';

$pengguna = [];
if (!empty($params)) {
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $pengguna[] = $row;
        }
        $stmt->close();
    }
} else {
    if ($result = $koneksi->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $pengguna[] = $row;
        }
        $result->free();
    }
}

$totalPengguna = count($pengguna);
$roleCounts = [
    'Admin' => 0,
    'User' => 0,
];
$statusCounts = [
    'Aktif' => 0,
    'Nonaktif' => 0,
];

foreach ($pengguna as $item) {
    $role = $item['role'] ?? 'User';
    $status = $item['status'] ?? 'Aktif';
    
    if (isset($roleCounts[$role])) {
        $roleCounts[$role]++;
    }
    
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}

$koneksi->close();
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Manajemen Data Pengguna</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item" aria-current="page">Pengguna</li>
            </ul>
          </div>
        </div>
        <div class="grid grid-cols-12 gap-6">
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Total Pengguna</p>
                    <h3 class="mb-0"><?php echo number_format($totalPengguna); ?></h3>
                  </div>
                  <span class="rounded-full bg-primary-100 text-primary-600 p-3">
                    <i data-feather="users" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Akun terdaftar di sistem PT BSM.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Administrator</p>
                    <h3 class="mb-0"><?php echo number_format($roleCounts['Admin'] ?? 0); ?></h3>
                  </div>
                  <span class="rounded-full bg-danger-100 text-danger-600 p-3">
                    <i data-feather="shield" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Pengguna dengan akses penuh sistem.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">User / Petugas</p>
                    <h3 class="mb-0"><?php echo number_format($roleCounts['User'] ?? 0); ?></h3>
                  </div>
                  <span class="rounded-full bg-success-100 text-success-600 p-3">
                    <i data-feather="user" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Pengguna dengan akses terbatas.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Nonaktif</p>
                    <h3 class="mb-0"><?php echo number_format($statusCounts['Nonaktif'] ?? 0); ?></h3>
                  </div>
                  <span class="rounded-full bg-warning-100 text-warning-600 p-3">
                    <i data-feather="user-x" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Akun yang dinonaktifkan sementara.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12">
            <div class="card">
              <div class="card-header flex justify-between items-center">
                <h5 class="mb-0">Daftar Pengguna</h5>
                <div class="flex gap-2">
                  <a href="cetak.php" class="btn btn-outline-secondary" target="_blank">
                    <i class="ti ti-printer me-2"></i>Cetak Data
                  </a>
                  <a href="tambah.php" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>Tambah Pengguna
                  </a>
                </div>
              </div>
              <div class="card-body">
                <?php if ($alertMessage !== ''): ?>
                  <div class="alert alert-<?php echo $alertType === 'error' ? 'danger' : 'success'; ?>" role="alert">
                    <?php echo htmlspecialchars($alertMessage); ?>
                  </div>
                <?php endif; ?>
                <form method="GET" class="mb-4">
                  <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-4">
                      <input type="text" name="search" class="form-control" placeholder="Cari nama atau username..." value="<?php echo htmlspecialchars($filterSearch); ?>">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <select name="filter_role" class="form-select">
                        <option value="all" <?php echo $filterRole === 'all' ? 'selected' : ''; ?>>Semua Role</option>
                        <option value="Admin" <?php echo $filterRole === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="User" <?php echo $filterRole === 'User' ? 'selected' : ''; ?>>User</option>
                      </select>
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <select name="filter_status" class="form-select">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="Aktif" <?php echo $filterStatus === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Nonaktif" <?php echo $filterStatus === 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                      </select>
                    </div>
                    <div class="col-span-12 md:col-span-2">
                      <button type="submit" class="btn btn-primary w-full"><i class="ti ti-filter me-1"></i>Filter</button>
                      <?php if ($filterSearch !== '' || $filterRole !== 'all' || $filterStatus !== 'all'): ?>
                        <a href="pengguna.php" class="btn btn-outline-secondary w-full mt-2"><i class="ti ti-x me-1"></i>Reset</a>
                      <?php endif; ?>
                    </div>
                  </div>
                </form>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terdaftar</th>
                        <th class="text-end">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($pengguna) === 0): ?>
                        <tr>
                          <td colspan="6" class="text-center text-muted">Belum ada data pengguna.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($pengguna as $item): ?>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <div class="rounded-full bg-primary-100 text-primary-600 flex items-center justify-center" style="width: 40px; height: 40px;">
                                  <i data-feather="user" class="w-5 h-5"></i>
                                </div>
                                <div>
                                  <strong><?php echo htmlspecialchars($item['nama']); ?></strong>
                                  <div class="text-muted text-sm">ID: #<?php echo (int) $item['id_pengguna']; ?></div>
                                </div>
                              </div>
                            </td>
                            <td>
                              <code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($item['username']); ?></code>
                            </td>
                            <td>
                              <?php
                                $role = $item['role'] ?? 'User';
                                $roleBadgeClass = 'bg-success-500';
                                $roleIcon = 'user';
                                if ($role === 'Admin') {
                                    $roleBadgeClass = 'bg-danger-500';
                                    $roleIcon = 'shield';
                                }
                              ?>
                              <span class="badge <?php echo $roleBadgeClass; ?> text-white">
                                <i data-feather="<?php echo $roleIcon; ?>" class="w-3 h-3 inline"></i>
                                <?php echo htmlspecialchars($role); ?>
                              </span>
                            </td>
                            <td>
                              <?php
                                $status = $item['status'] ?? 'Aktif';
                                $statusBadgeClass = 'bg-success-500';
                                if ($status === 'Nonaktif') {
                                    $statusBadgeClass = 'bg-warning-500';
                                }
                              ?>
                              <span class="badge <?php echo $statusBadgeClass; ?> text-white">
                                <?php echo htmlspecialchars($status); ?>
                              </span>
                            </td>
                            <td>
                              <div class="text-muted text-sm">
                                <?php echo $item['created_at'] ? date('d M Y', strtotime($item['created_at'])) : '-'; ?>
                              </div>
                            </td>
                            <td class="text-end">
                              <div class="btn-group" role="group">
                                <a href="detail.php?id=<?php echo (int) $item['id_pengguna']; ?>" class="btn btn-outline-primary btn-sm" title="Lihat Detail">
                                  <i class="ti ti-eye"></i> Detail
                                </a>
                                <a href="edit.php?id=<?php echo (int) $item['id_pengguna']; ?>" class="btn btn-outline-secondary btn-sm" title="Edit Data">
                                  <i class="ti ti-edit"></i>
                                </a>
                                <?php if ((int) $item['id_pengguna'] !== $_SESSION['user_id']): ?>
                                  <a href="hapus.php?id=<?php echo (int) $item['id_pengguna']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus data pengguna ini?');" title="Hapus Data">
                                    <i class="ti ti-trash"></i>
                                  </a>
                                <?php endif; ?>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
