<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
$page_title = 'Data Kendaraan';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';

$alertType = $_GET['status'] ?? '';
$alertMessage = '';

switch ($alertType) {
    case 'created':
        $alertMessage = 'Data kendaraan berhasil ditambahkan.';
        break;
    case 'updated':
        $alertMessage = 'Data kendaraan berhasil diperbarui.';
        break;
    case 'deleted':
        $alertMessage = 'Data kendaraan berhasil dihapus.';
        break;
    case 'error':
        $alertMessage = 'Terjadi kesalahan. Silakan coba lagi.';
        break;
}

// Filter parameters
$filterStatus = $_GET['filter_status'] ?? 'all';
$filterSearch = $_GET['search'] ?? '';
$filterMerk = $_GET['filter_merk'] ?? 'all';

// Build SQL query with filters
$sql = 'SELECT id_kendaraan, nomor_polisi, nama_kendaraan, merk, tipe, tahun, status_operasional, foto_kendaraan, dokumen_stnk, dokumen_bpkb, created_at, updated_at FROM tb_kendaraan WHERE 1=1';
$params = [];
$types = '';

if ($filterStatus !== 'all') {
    $sql .= ' AND status_operasional = ?';
    $params[] = $filterStatus;
    $types .= 's';
}

if ($filterSearch !== '') {
    $sql .= ' AND (nomor_polisi LIKE ? OR nama_kendaraan LIKE ? OR merk LIKE ?)';
    $searchParam = '%' . $filterSearch . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if ($filterMerk !== 'all') {
    $sql .= ' AND merk = ?';
    $params[] = $filterMerk;
    $types .= 's';
}

$sql .= ' ORDER BY created_at DESC';

$kendaraan = [];
if (!empty($params)) {
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $kendaraan[] = $row;
        }
        $stmt->close();
    }
} else {
    if ($result = $koneksi->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $kendaraan[] = $row;
        }
        $result->free();
    }
}

// Get unique merk for filter dropdown
$merkList = [];
$sqlMerk = 'SELECT DISTINCT merk FROM tb_kendaraan WHERE merk IS NOT NULL AND merk != "" ORDER BY merk';
if ($result = $koneksi->query($sqlMerk)) {
    while ($row = $result->fetch_assoc()) {
        $merkList[] = $row['merk'];
    }
    $result->free();
}

$totalKendaraan = count($kendaraan);
$statusCounts = [
    'Aktif' => 0,
    'Perawatan' => 0,
    'Rusak' => 0,
    'Disewa' => 0,
];

foreach ($kendaraan as $item) {
    $status = $item['status_operasional'] ?? 'Aktif';
    if (! isset($statusCounts[$status])) {
        $statusCounts[$status] = 0;
    }
    $statusCounts[$status]++;
}
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Manajemen Data Kendaraan</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item" aria-current="page">Kendaraan</li>
            </ul>
          </div>
        </div>
        <div class="grid grid-cols-12 gap-6">
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Total Kendaraan</p>
                    <h3 class="mb-0"><?php echo number_format($totalKendaraan); ?></h3>
                  </div>
                  <span class="rounded-full bg-primary-100 text-primary-600 p-3">
                    <i data-feather="truck" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Armada terdaftar di sistem PT BSM.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Sedang Operasional</p>
                    <h3 class="mb-0"><?php echo number_format($statusCounts['Aktif'] ?? 0); ?></h3>
                  </div>
                  <span class="rounded-full bg-success-100 text-success-600 p-3">
                    <i data-feather="activity" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Unit yang siap jalan dan dapat digunakan.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Dalam Perawatan</p>
                    <h3 class="mb-0"><?php echo number_format($statusCounts['Perawatan'] ?? 0); ?></h3>
                  </div>
                  <span class="rounded-full bg-warning-100 text-warning-600 p-3">
                    <i data-feather="tool" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Unit yang sedang maintenance terjadwal.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Rusak / Disewa</p>
                    <h3 class="mb-0"><?php echo number_format(($statusCounts['Rusak'] ?? 0) + ($statusCounts['Disewa'] ?? 0)); ?></h3>
                  </div>
                  <span class="rounded-full bg-danger-100 text-danger-600 p-3">
                    <i data-feather="alert-triangle" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Unit yang perlu perhatian khusus.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12">
            <div class="card">
              <div class="card-header flex justify-between items-center">
                <h5 class="mb-0">Daftar Kendaraan</h5>
                <div class="flex gap-2">
                  <a href="cetak.php" class="btn btn-outline-secondary" target="_blank">
                    <i class="ti ti-printer me-2"></i>Cetak Data
                  </a>
                  <a href="tambah.php" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>Tambah Kendaraan
                  </a>
                </div>
              </div>
              <div class="card-body">
                <?php if ($alertMessage !== ''): ?>
                  <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($alertMessage); ?>
                  </div>
                <?php endif; ?>
                <form method="GET" class="mb-4">
                  <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-4">
                      <input type="text" name="search" class="form-control" placeholder="Cari nomor polisi, nama, atau merk..." value="<?php echo htmlspecialchars($filterSearch); ?>">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <select name="filter_status" class="form-select">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="Aktif" <?php echo $filterStatus === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Perawatan" <?php echo $filterStatus === 'Perawatan' ? 'selected' : ''; ?>>Perawatan</option>
                        <option value="Rusak" <?php echo $filterStatus === 'Rusak' ? 'selected' : ''; ?>>Rusak</option>
                        <option value="Disewa" <?php echo $filterStatus === 'Disewa' ? 'selected' : ''; ?>>Disewa</option>
                      </select>
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <select name="filter_merk" class="form-select">
                        <option value="all" <?php echo $filterMerk === 'all' ? 'selected' : ''; ?>>Semua Merk</option>
                        <?php foreach ($merkList as $merk): ?>
                          <option value="<?php echo htmlspecialchars($merk); ?>" <?php echo $filterMerk === $merk ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($merk); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-span-12 md:col-span-2">
                      <button type="submit" class="btn btn-primary w-full"><i class="ti ti-filter me-1"></i>Filter</button>
                      <?php if ($filterSearch !== '' || $filterStatus !== 'all' || $filterMerk !== 'all'): ?>
                        <a href="kendaraan.php" class="btn btn-outline-secondary w-full mt-2"><i class="ti ti-x me-1"></i>Reset</a>
                      <?php endif; ?>
                    </div>
                  </div>
                </form>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Foto</th>
                        <th>No. Polisi</th>
                        <th>Nama Kendaraan</th>
                        <th>Merk/Tipe</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($kendaraan) === 0): ?>
                        <tr>
                          <td colspan="7" class="text-center text-muted">Belum ada data kendaraan.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($kendaraan as $item): ?>
                          <tr>
                            <td>
                              <?php if (!empty($item['foto_kendaraan']) && file_exists('../../' . $item['foto_kendaraan'])): ?>
                                <img src="../../<?php echo htmlspecialchars($item['foto_kendaraan']); ?>" alt="Foto" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                              <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                  <i data-feather="image" class="text-muted" style="width: 24px; height: 24px;"></i>
                                </div>
                              <?php endif; ?>
                            </td>
                            <td>
                              <strong><?php echo htmlspecialchars($item['nomor_polisi']); ?></strong>
                              <div class="text-muted text-sm">Dibuat: <?php echo $item['created_at'] ? date('d M Y', strtotime($item['created_at'])) : '-'; ?></div>
                            </td>
                            <td>
                              <?php echo htmlspecialchars($item['nama_kendaraan']); ?>
                              <div class="text-muted text-sm">Tahun: <?php echo $item['tahun'] ? (int) $item['tahun'] : '-'; ?></div>
                            </td>
                            <td><?php echo htmlspecialchars(trim(($item['merk'] ?? '') . ' ' . ($item['tipe'] ?? ''))); ?></td>
                            <td>
                              <?php
                                $status = $item['status_operasional'] ?? 'Aktif';
                                $badgeClass = 'bg-success-500';
                                if ($status === 'Perawatan') {
                                    $badgeClass = 'bg-warning-500';
                                } elseif ($status === 'Rusak') {
                                    $badgeClass = 'bg-danger-500';
                                } elseif ($status === 'Disewa') {
                                    $badgeClass = 'bg-primary-500';
                                }
                              ?>
                              <span class="badge <?php echo $badgeClass; ?> text-white">
                                <?php echo htmlspecialchars($status); ?>
                              </span>
                            </td>
                            <td class="text-end">
                              <div class="btn-group" role="group">
                                <a href="detail.php?id=<?php echo (int) $item['id_kendaraan']; ?>" class="btn btn-outline-primary btn-sm" title="Lihat Detail">
                                  <i class="ti ti-eye"></i> Detail
                                </a>
                                <a href="edit.php?id=<?php echo (int) $item['id_kendaraan']; ?>" class="btn btn-outline-secondary btn-sm" title="Edit Data">
                                  <i class="ti ti-edit"></i>
                                </a>
                                <a href="hapus.php?id=<?php echo (int) $item['id_kendaraan']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus data kendaraan ini?');" title="Hapus Data">
                                  <i class="ti ti-trash"></i>
                                </a>
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
