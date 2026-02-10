<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
$page_title = 'Maintenance Kendaraan (User)';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function is_valid_date($date) {
    if (! $date) return false;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Filters
$filterStatus = $_GET['filter_status'] ?? 'all';
$filterSearch = $_GET['search'] ?? '';
$filterDateStart = $_GET['date_start'] ?? '';
$filterDateEnd   = $_GET['date_end'] ?? '';

$sqlList = 'SELECT m.id_maintenance, m.id_kendaraan, m.tanggal_service, m.jenis_service, m.deskripsi, m.biaya, m.status, k.nomor_polisi, k.nama_kendaraan FROM tb_maintenance m LEFT JOIN tb_kendaraan k ON m.id_kendaraan = k.id_kendaraan WHERE 1=1';
$params = [];
$types = '';

if ($filterStatus !== 'all') {
    $sqlList .= ' AND m.status = ?';
    $params[] = $filterStatus;
    $types .= 's';
}
if ($filterSearch !== '') {
    $sqlList .= ' AND (m.jenis_service LIKE ? OR m.deskripsi LIKE ? OR k.nomor_polisi LIKE ? OR k.nama_kendaraan LIKE ?)';
    $searchParam = '%' . $filterSearch . '%';
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    $types .= 'ssss';
}
if ($filterDateStart !== '' && is_valid_date($filterDateStart)) {
    $sqlList .= ' AND m.tanggal_service >= ?';
    $params[] = $filterDateStart;
    $types .= 's';
}
if ($filterDateEnd !== '' && is_valid_date($filterDateEnd)) {
    $sqlList .= ' AND m.tanggal_service <= ?';
    $params[] = $filterDateEnd;
    $types .= 's';
}

$sqlList .= ' ORDER BY m.tanggal_service DESC, m.id_maintenance DESC';

$maintenance = [];
if (!empty($params)) {
    $stmt = $koneksi->prepare($sqlList);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $maintenance[] = $row;
        }
        $stmt->close();
    }
} else {
    if ($result = $koneksi->query($sqlList)) {
        while ($row = $result->fetch_assoc()) {
            $maintenance[] = $row;
        }
        $result->free();
    }
}

// Summary counts
$totalMaintenance = count($maintenance);
$statusCounts = [
    'Dijadwalkan' => 0,
    'Proses' => 0,
    'Selesai' => 0,
];
foreach ($maintenance as $item) {
    $st = $item['status'] ?? 'Dijadwalkan';
    if (isset($statusCounts[$st])) $statusCounts[$st]++;
}
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Maintenance Kendaraan</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item" aria-current="page">Maintenance</li>
            </ul>
          </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Total Maintenance</p>
                    <h3 class="mb-0"><?php echo number_format($totalMaintenance); ?></h3>
                  </div>
                  <span class="rounded-full bg-primary-100 text-primary-600 p-3">
                    <i data-feather="tool" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Riwayat perawatan kendaraan.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Dijadwalkan</p>
                    <h3 class="mb-0"><?php echo number_format($statusCounts['Dijadwalkan'] ?? 0); ?></h3>
                  </div>
                  <span class="rounded-full bg-warning-100 text-warning-600 p-3">
                    <i data-feather="calendar" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Maintenance yang akan dilaksanakan.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Proses</p>
                    <h3 class="mb-0"><?php echo number_format($statusCounts['Proses'] ?? 0); ?></h3>
                  </div>
                  <span class="rounded-full bg-success-100 text-success-600 p-3">
                    <i data-feather="settings" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Maintenance yang sedang dikerjakan.</p>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="card">
              <div class="card-header flex justify-between items-center">
                <h5 class="mb-0">Daftar Maintenance</h5>
                <div class="flex gap-2">
                  <a href="tambah.php" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Tambah Maintenance
                  </a>
                </div>
              </div>
              <div class="card-body">
                <?php if (isset($_GET['status']) && isset($_GET['message'])): ?>
                  <div class="alert alert-<?php echo ($_GET['status'] === 'error') ? 'danger' : 'success'; ?>" role="alert">
                    <?php echo e($_GET['message']); ?>
                  </div>
                <?php endif; ?>

                <form method="GET" class="mb-4">
                  <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-4">
                      <input type="text" name="search" class="form-control" placeholder="Cari jenis/deskripsi/kendaraan..." value="<?php echo e($filterSearch); ?>">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <select name="filter_status" class="form-select">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="Dijadwalkan" <?php echo $filterStatus === 'Dijadwalkan' ? 'selected' : ''; ?>>Dijadwalkan</option>
                        <option value="Proses" <?php echo $filterStatus === 'Proses' ? 'selected' : ''; ?>>Proses</option>
                        <option value="Selesai" <?php echo $filterStatus === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                      </select>
                    </div>
                    <div class="col-span-12 md:col-span-2">
                      <input type="date" name="date_start" class="form-control" value="<?php echo e($filterDateStart); ?>" placeholder="Mulai">
                    </div>
                    <div class="col-span-12 md:col-span-2">
                      <input type="date" name="date_end" class="form-control" value="<?php echo e($filterDateEnd); ?>" placeholder="Selesai">
                    </div>
                    <div class="col-span-12 md:col-span-2">
                      <button type="submit" class="btn btn-primary w-full"><i class="ti ti-filter me-1"></i>Filter</button>
                      <?php if ($filterSearch !== '' || $filterStatus !== 'all' || $filterDateStart !== '' || $filterDateEnd !== ''): ?>
                        <a href="maintenance.php" class="btn btn-outline-secondary w-full mt-2"><i class="ti ti-x me-1"></i>Reset</a>
                      <?php endif; ?>
                    </div>
                  </div>
                </form>

                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Kendaraan</th>
                        <th>Jenis</th>
                        <th>Tanggal</th>
                        <th>Biaya</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($maintenance) === 0): ?>
                        <tr>
                          <td colspan="5" class="text-center text-muted">Belum ada data maintenance.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($maintenance as $item): ?>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <div>
                                  <div class="font-medium"><?php echo e($item['nama_kendaraan'] ?? '-'); ?></div>
                                  <div class="text-muted text-sm"><?php echo e($item['nomor_polisi'] ?? '-'); ?></div>
                                </div>
                              </div>
                            </td>
                            <td><?php echo e($item['jenis_service'] ?? '-'); ?></td>
                            <td><?php echo $item['tanggal_service'] ? date('d M Y', strtotime($item['tanggal_service'])) : '-'; ?></td>
                            <td><?php echo is_null($item['biaya']) ? '-' : 'Rp ' . number_format((float)$item['biaya'], 0, ',', '.'); ?></td>
                            <td>
                              <span class="badge <?php
                                echo ($item['status'] === 'Selesai') ? 'bg-success-500' : (($item['status'] === 'Proses') ? 'bg-warning-500' : 'bg-primary-500');
                              ?> text-white">
                                <?php echo e($item['status'] ?? '-'); ?>
                              </span>
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