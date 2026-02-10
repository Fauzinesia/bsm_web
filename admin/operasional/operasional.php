<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function is_valid_date($d) { return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d); }

$page_title = 'Manajemen Biaya Operasional';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';

// Filters
$filterJenis     = $_GET['filter_jenis'] ?? 'all';
$filterSearch    = $_GET['search'] ?? '';
$filterDateStart = $_GET['date_start'] ?? '';
$filterDateEnd   = $_GET['date_end'] ?? '';

$sqlList = 'SELECT b.id_biaya, b.id_kendaraan, b.tanggal, b.jenis_biaya, b.nominal, b.keterangan, k.nomor_polisi, k.nama_kendaraan
            FROM tb_biaya_operasional b
            LEFT JOIN tb_kendaraan k ON k.id_kendaraan = b.id_kendaraan
            WHERE 1=1';
$params = [];
$types = '';

if ($filterJenis !== 'all') {
    $sqlList .= ' AND b.jenis_biaya = ?';
    $params[] = $filterJenis;
    $types .= 's';
}
if ($filterSearch !== '') {
    $sqlList .= ' AND (b.jenis_biaya LIKE ? OR b.keterangan LIKE ? OR k.nomor_polisi LIKE ? OR k.nama_kendaraan LIKE ?)';
    $searchParam = '%' . $filterSearch . '%';
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    $types .= 'ssss';
}
if ($filterDateStart !== '' && is_valid_date($filterDateStart)) {
    $sqlList .= ' AND b.tanggal >= ?';
    $params[] = $filterDateStart;
    $types .= 's';
}
if ($filterDateEnd !== '' && is_valid_date($filterDateEnd)) {
    $sqlList .= ' AND b.tanggal <= ?';
    $params[] = $filterDateEnd;
    $types .= 's';
}

$sqlList .= ' ORDER BY b.tanggal DESC, b.id_biaya DESC';

$operasional = [];
if (!empty($params)) {
    $stmt = $koneksi->prepare($sqlList);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $operasional[] = $row;
        }
        $stmt->close();
    }
} else {
    if ($result = $koneksi->query($sqlList)) {
        while ($row = $result->fetch_assoc()) {
            $operasional[] = $row;
        }
        $result->free();
    }
}

// Summary
$totalTransaksi = count($operasional);
$totalNominal   = 0.0;
$jenisCounts = [
    'BBM' => 0,
    'Tol' => 0,
    'Parkir' => 0,
    'Pajak' => 0,
    'Asuransi' => 0,
    'Lainnya' => 0,
];
foreach ($operasional as $row) {
    $totalNominal += (float)($row['nominal'] ?? 0);
    $jb = $row['jenis_biaya'] ?? 'Lainnya';
    if (isset($jenisCounts[$jb])) $jenisCounts[$jb]++;
}
?>

    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Manajemen Biaya Operasional</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item" aria-current="page">Operasional</li>
            </ul>
          </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Total Transaksi</p>
                    <h3 class="mb-0"><?php echo number_format($totalTransaksi); ?></h3>
                  </div>
                  <span class="rounded-full bg-primary-100 text-primary-600 p-3">
                    <i data-feather="activity" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Rekap biaya operasional kendaraan.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Total Nominal (filter)</p>
                    <h3 class="mb-0">Rp <?php echo number_format($totalNominal, 0, ',', '.'); ?></h3>
                  </div>
                  <span class="rounded-full bg-success-100 text-success-600 p-3">
                    <i data-feather="credit-card" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Jumlah biaya sesuai filter.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <p class="text-muted mb-2">Distribusi Jenis Biaya</p>
                <div class="flex flex-wrap gap-2">
                  <?php foreach ($jenisCounts as $jk => $cnt): ?>
                    <span class="badge bg-secondary-100 text-secondary-700"><?php echo e($jk); ?>: <?php echo number_format($cnt); ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="card">
              <div class="card-header flex justify-between items-center">
                <h5 class="mb-0">Daftar Biaya Operasional</h5>
                <div class="flex gap-2">
                  <a href="cetak.php" class="btn btn-outline-secondary" target="_blank">
                    <i class="ti ti-printer me-2"></i>Cetak Data
                  </a>
                  <a href="tambah.php" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>Tambah Biaya
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
                      <input type="text" name="search" class="form-control" placeholder="Cari jenis/keterangan/kendaraan..." value="<?php echo e($filterSearch); ?>">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <select name="filter_jenis" class="form-select">
                        <?php $jenisOps = ['all' => 'Semua Jenis','BBM'=>'BBM','Tol'=>'Tol','Parkir'=>'Parkir','Pajak'=>'Pajak','Asuransi'=>'Asuransi','Lainnya'=>'Lainnya']; ?>
                        <?php foreach ($jenisOps as $val => $label): ?>
                          <option value="<?php echo e($val); ?>" <?php echo $filterJenis === $val ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-span-12 md:col-span-2">
                      <input type="date" name="date_start" class="form-control" value="<?php echo e($filterDateStart); ?>">
                    </div>
                    <div class="col-span-12 md:col-span-2">
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
                        <th>Jenis Biaya</th>
                        <th>Tanggal</th>
                        <th>Nominal</th>
                        <th>Keterangan</th>
                        <th class="text-end">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($operasional) === 0): ?>
                        <tr>
                          <td colspan="6" class="text-center text-muted">Belum ada data biaya operasional.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($operasional as $item): ?>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <div>
                                  <div class="font-medium"><?php echo e($item['nama_kendaraan'] ?? '-'); ?></div>
                                  <div class="text-muted text-sm"><?php echo e($item['nomor_polisi'] ?? '-'); ?></div>
                                </div>
                              </div>
                            </td>
                            <td><?php echo e($item['jenis_biaya'] ?? '-'); ?></td>
                            <td><?php echo $item['tanggal'] ? date('d M Y', strtotime($item['tanggal'])) : '-'; ?></td>
                            <td><?php echo is_null($item['nominal']) ? '-' : 'Rp ' . number_format((float)$item['nominal'], 0, ',', '.'); ?></td>
                            <td><?php echo e($item['keterangan'] ?? '-'); ?></td>
                            <td class="text-end">
                              <div class="btn-group">
                                <a href="edit.php?id=<?php echo (int)$item['id_biaya']; ?>" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i> Edit</a>
                                <a href="hapus.php?id=<?php echo (int)$item['id_biaya']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin hapus data ini?');"><i class="ti ti-trash"></i> Hapus</a>
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