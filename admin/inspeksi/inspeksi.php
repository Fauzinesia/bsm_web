<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function is_valid_date($d) { return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d); }

$page_title = 'Manajemen Inspeksi Kendaraan';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';

// Filters (selaras dengan operasional)
$filterSearch    = $_GET['search'] ?? '';
$filterDateStart = $_GET['date_start'] ?? '';
$filterDateEnd   = $_GET['date_end'] ?? '';

// Query list inspeksi
$sqlList = 'SELECT i.id_inspeksi, i.id_kendaraan, i.tanggal, i.kondisi_ban, i.kondisi_lampu, i.oli_mesin, i.rem, i.kebersihan, i.catatan,
                   k.nomor_polisi, k.nama_kendaraan, k.merk, k.tipe
            FROM tb_inspeksi i
            JOIN tb_kendaraan k ON k.id_kendaraan = i.id_kendaraan
            WHERE 1=1';
$params = [];
$types = '';

if ($filterSearch !== '') {
    $sqlList .= ' AND (k.nomor_polisi LIKE ? OR k.nama_kendaraan LIKE ? OR k.merk LIKE ? OR k.tipe LIKE ?)';
    $searchParam = '%' . $filterSearch . '%';
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    $types .= 'ssss';
}
if ($filterDateStart !== '' && is_valid_date($filterDateStart)) {
    $sqlList .= ' AND i.tanggal >= ?';
    $params[] = $filterDateStart;
    $types .= 's';
}
if ($filterDateEnd !== '' && is_valid_date($filterDateEnd)) {
    $sqlList .= ' AND i.tanggal <= ?';
    $params[] = $filterDateEnd;
    $types .= 's';
}

$sqlList .= ' ORDER BY i.tanggal DESC, i.id_inspeksi DESC';

$inspeksi = [];
if (!empty($params)) {
    $stmt = $koneksi->prepare($sqlList);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $inspeksi[] = $row;
        }
        $stmt->close();
    }
} else {
    if ($result = $koneksi->query($sqlList)) {
        while ($row = $result->fetch_assoc()) {
            $inspeksi[] = $row;
        }
        $result->free();
    }
}

// Summary
$totalInspeksi = count($inspeksi);
$uniqueVehicles = [];
$banCounts = [
    'Baik' => 0,
    'Perlu dicek' => 0,
    'Rusak' => 0,
];
foreach ($inspeksi as $row) {
    if (isset($row['id_kendaraan'])) { $uniqueVehicles[$row['id_kendaraan']] = true; }
    $kb = $row['kondisi_ban'] ?? 'Baik';
    if (isset($banCounts[$kb])) $banCounts[$kb]++;
}
$totalVehicles = count($uniqueVehicles);
?>

    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Manajemen Inspeksi Kendaraan</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item" aria-current="page">Inspeksi</li>
            </ul>
          </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Total Inspeksi</p>
                    <h3 class="mb-0"><?php echo number_format($totalInspeksi); ?></h3>
                  </div>
                  <span class="rounded-full bg-primary-100 text-primary-600 p-3">
                    <i data-feather="check-circle" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Rekap inspeksi unit kendaraan.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Unit Terinspeksi (filter)</p>
                    <h3 class="mb-0"><?php echo number_format($totalVehicles); ?></h3>
                  </div>
                  <span class="rounded-full bg-success-100 text-success-600 p-3">
                    <i data-feather="truck" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Jumlah kendaraan yang muncul pada filter.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <p class="text-muted mb-2">Distribusi Kondisi Ban</p>
                <div class="flex flex-wrap gap-2">
                  <?php foreach ($banCounts as $cond => $cnt): ?>
                    <span class="badge bg-secondary-100 text-secondary-700"><?php echo e($cond); ?>: <?php echo number_format($cnt); ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="card">
              <div class="card-header flex justify-between items-center">
                <h5 class="mb-0">Daftar Inspeksi</h5>
                <div class="flex gap-2">
                  <a href="cetak.php" class="btn btn-outline-secondary" target="_blank">
                    <i class="ti ti-printer me-2"></i>Cetak Data
                  </a>
                  <a href="tambah.php" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>Tambah Inspeksi
                  </a>
                </div>
              </div>
              <div class="card-body">
                <form method="GET" class="mb-4">
                  <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-5">
                      <input type="text" name="search" class="form-control" placeholder="Cari nomor polisi/nama/merk/tipe..." value="<?php echo e($filterSearch); ?>">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <input type="date" name="date_start" class="form-control" value="<?php echo e($filterDateStart); ?>">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <input type="date" name="date_end" class="form-control" value="<?php echo e($filterDateEnd); ?>">
                    </div>
                    <div class="col-span-12 md:col-span-1">
                      <button type="submit" class="btn btn-outline-primary w-full">Filter</button>
                    </div>
                  </div>
                </form>

                <div class="table-responsive">
                  <table class="table table-striped align-middle">
                    <thead>
                      <tr>
                        <th>Kendaraan</th>
                        <th>Tanggal</th>
                        <th>Ban</th>
                        <th>Lampu</th>
                        <th>Oli Mesin</th>
                        <th>Rem</th>
                        <th>Kebersihan</th>
                        <th>Catatan</th>
                        <th class="text-end">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($inspeksi) === 0): ?>
                        <tr>
                          <td colspan="9" class="text-center text-muted">Belum ada data inspeksi.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($inspeksi as $item): ?>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <div>
                                  <div class="font-medium"><?php echo e($item['nama_kendaraan'] ?? '-'); ?></div>
                                  <div class="text-muted text-sm"><?php echo e($item['nomor_polisi'] ?? '-'); ?></div>
                                </div>
                              </div>
                            </td>
                            <td><?php echo $item['tanggal'] ? date('d M Y', strtotime($item['tanggal'])) : '-'; ?></td>
                            <td><?php echo e($item['kondisi_ban'] ?? '-'); ?></td>
                            <td><?php echo e($item['kondisi_lampu'] ?? '-'); ?></td>
                            <td><?php echo e($item['oli_mesin'] ?? '-'); ?></td>
                            <td><?php echo e($item['rem'] ?? '-'); ?></td>
                            <td><?php echo e($item['kebersihan'] ?? '-'); ?></td>
                            <td><?php echo nl2br(e($item['catatan'] ?? '-')); ?></td>
                            <td class="text-end">
                              <div class="btn-group">
                                <a href="edit.php?id=<?php echo (int)$item['id_inspeksi']; ?>" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i> Edit</a>
                                <a href="hapus.php?id=<?php echo (int)$item['id_inspeksi']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin hapus data ini?');"><i class="ti ti-trash"></i> Hapus</a>
                                <a href="../maintenance/tambah.php?id_inspeksi=<?php echo (int)$item['id_inspeksi']; ?>&id_kendaraan=<?php echo (int)$item['id_kendaraan']; ?>" class="btn btn-sm btn-outline-success"><i class="ti ti-tools"></i> Buat Maintenance</a>
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
<?php // end file ?>

