<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

$action = $_GET['action'] ?? '';


// Helper: sanitize output
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Helper: validate date (Y-m-d)
function is_valid_date($date) {
    if (! $date) return false;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Feedback
$alertType = $_GET['status'] ?? '';
$alertMessage = '';
switch ($alertType) {
    case 'created':
        $alertMessage = 'Data penggunaan berhasil ditambahkan.';
        break;
    case 'updated':
        $alertMessage = 'Data penggunaan berhasil diperbarui.';
        break;
    case 'deleted':
        $alertMessage = 'Data penggunaan berhasil dihapus.';
        break;
    case 'error':
        $alertMessage = e($_GET['message'] ?? 'Terjadi kesalahan. Silakan coba lagi.');
        break;
}

// Fetch options for select fields
$kendaraanOptions = [];
if ($res = $koneksi->query("SELECT id_kendaraan, nomor_polisi, nama_kendaraan FROM tb_kendaraan ORDER BY nama_kendaraan ASC")) {
    while ($row = $res->fetch_assoc()) {
        $kendaraanOptions[] = $row;
    }
    $res->free();
}

$penggunaOptions = [];
if ($res = $koneksi->query("SELECT id_pengguna, nama, username FROM tb_pengguna ORDER BY nama ASC")) {
    while ($row = $res->fetch_assoc()) {
        $penggunaOptions[] = $row;
    }
    $res->free();
}



// Filters
$filterStatus = $_GET['filter_status'] ?? 'all';
$filterSearch = $_GET['search'] ?? '';
$filterDateStart = $_GET['date_start'] ?? '';
$filterDateEnd   = $_GET['date_end'] ?? '';

$sqlList = 'SELECT p.id_penggunaan, p.id_kendaraan, p.id_pengguna, p.tanggal_mulai, p.tanggal_selesai, p.keperluan, p.status, k.nomor_polisi, k.nama_kendaraan, u.nama AS nama_pengguna, u.username FROM tb_penggunaan p LEFT JOIN tb_kendaraan k ON p.id_kendaraan = k.id_kendaraan LEFT JOIN tb_pengguna u ON p.id_pengguna = u.id_pengguna WHERE 1=1';
$params = [];
$types = '';

if ($filterStatus !== 'all') {
    $sqlList .= ' AND p.status = ?';
    $params[] = $filterStatus;
    $types .= 's';
}
if ($filterSearch !== '') {
    $sqlList .= ' AND (p.keperluan LIKE ? OR k.nomor_polisi LIKE ? OR k.nama_kendaraan LIKE ? OR u.nama LIKE ? OR u.username LIKE ?)';
    $searchParam = '%' . $filterSearch . '%';
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    $types .= 'sssss';
}
if ($filterDateStart !== '' && is_valid_date($filterDateStart)) {
    $sqlList .= ' AND p.tanggal_mulai >= ?';
    $params[] = $filterDateStart;
    $types .= 's';
}
if ($filterDateEnd !== '' && is_valid_date($filterDateEnd)) {
    $sqlList .= ' AND (p.tanggal_selesai IS NULL OR p.tanggal_selesai <= ?)';
    $params[] = $filterDateEnd;
    $types .= 's';
}

$sqlList .= ' ORDER BY p.tanggal_mulai DESC, p.id_penggunaan DESC';

$penggunaan = [];
if (!empty($params)) {
    $stmt = $koneksi->prepare($sqlList);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $penggunaan[] = $row;
        }
        $stmt->close();
    }
} else {
    if ($result = $koneksi->query($sqlList)) {
        while ($row = $result->fetch_assoc()) {
            $penggunaan[] = $row;
        }
        $result->free();
    }
}

// Summary counts
$totalPenggunaan = count($penggunaan);
$statusCounts = [
    'Berjalan' => 0,
    'Selesai' => 0,
];
foreach ($penggunaan as $item) {
    $st = $item['status'] ?? 'Berjalan';
    if (isset($statusCounts[$st])) $statusCounts[$st]++;
}

// Prefill for edit
$editData = null;
if (($action === 'edit') && isset($_GET['id'])) {
    $idEdit = (int)$_GET['id'];
    if ($idEdit > 0) {
        $stmt = $koneksi->prepare('SELECT id_penggunaan, id_kendaraan, id_pengguna, tanggal_mulai, tanggal_selesai, keperluan, status FROM tb_penggunaan WHERE id_penggunaan = ?');
        if ($stmt) {
            $stmt->bind_param('i', $idEdit);
            $stmt->execute();
            $res = $stmt->get_result();
            $editData = $res->fetch_assoc();
            $stmt->close();
        }
    }
}

$page_title = 'Data Penggunaan Kendaraan';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Manajemen Penggunaan Kendaraan</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item" aria-current="page">Penggunaan</li>
            </ul>
          </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Total Penggunaan</p>
                    <h3 class="mb-0"><?php echo number_format($totalPenggunaan); ?></h3>
                  </div>
                  <span class="rounded-full bg-primary-100 text-primary-600 p-3">
                    <i data-feather="truck" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Riwayat penggunaan kendaraan di PT BSM.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Berjalan</p>
                    <h3 class="mb-0"><?php echo number_format($statusCounts['Berjalan'] ?? 0); ?></h3>
                  </div>
                  <span class="rounded-full bg-success-100 text-success-600 p-3">
                    <i data-feather="play" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Penggunaan aktif saat ini.</p>
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Selesai</p>
                    <h3 class="mb-0"><?php echo number_format($statusCounts['Selesai'] ?? 0); ?></h3>
                  </div>
                  <span class="rounded-full bg-warning-100 text-warning-600 p-3">
                    <i data-feather="check" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Penggunaan yang telah selesai.</p>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="card">
              <div class="card-header flex justify-between items-center">
                <h5 class="mb-0">Daftar Penggunaan</h5>
                <div class="flex gap-2">
                  <a href="cetak.php" class="btn btn-outline-secondary" target="_blank">
                    <i class="ti ti-printer me-2"></i>Cetak Data
                  </a>
                  <a href="tambah.php" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>Tambah Penggunaan
                  </a>
                </div>
              </div>
              <div class="card-body">
                <?php if ($alertMessage !== ''): ?>
                  <div class="alert alert-<?php echo $alertType === 'error' ? 'danger' : 'success'; ?>" role="alert">
                    <?php echo e($alertMessage); ?>
                  </div>
                <?php endif; ?>

                <?php if ($action === 'edit' && $editData): ?>
                  <div class="mb-4">
                    <div class="alert alert-info">Edit data penggunaan #<?php echo (int)$editData['id_penggunaan']; ?></div>
                    <form method="POST" class="grid grid-cols-12 gap-3">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="id" value="<?php echo (int)$editData['id_penggunaan']; ?>">
                      <div class="col-span-12 md:col-span-3">
                        <label class="form-label">Kendaraan</label>
                        <select name="id_kendaraan" class="form-select" required>
                          <option value="">Pilih Kendaraan</option>
                          <?php foreach ($kendaraanOptions as $opt): ?>
                            <option value="<?php echo (int)$opt['id_kendaraan']; ?>" <?php echo ((int)$editData['id_kendaraan'] === (int)$opt['id_kendaraan']) ? 'selected' : ''; ?>>
                              <?php echo e($opt['nomor_polisi']); ?> - <?php echo e($opt['nama_kendaraan']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-span-12 md:col-span-3">
                        <label class="form-label">Pengguna</label>
                        <select name="id_pengguna" class="form-select" required>
                          <option value="">Pilih Pengguna</option>
                          <?php foreach ($penggunaOptions as $opt): ?>
                            <option value="<?php echo (int)$opt['id_pengguna']; ?>" <?php echo ((int)$editData['id_pengguna'] === (int)$opt['id_pengguna']) ? 'selected' : ''; ?>>
                              <?php echo e($opt['nama']); ?> (<?php echo e($opt['username']); ?>)
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-span-12 md:col-span-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="<?php echo e($editData['tanggal_mulai']); ?>" required>
                      </div>
                      <div class="col-span-12 md:col-span-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control" value="<?php echo e($editData['tanggal_selesai']); ?>">
                      </div>
                      <div class="col-span-12">
                        <label class="form-label">Keperluan</label>
                        <textarea name="keperluan" class="form-control" rows="3" required><?php echo e($editData['keperluan']); ?></textarea>
                      </div>
                      <div class="col-span-12 md:col-span-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                          <option value="Berjalan" <?php echo ($editData['status'] === 'Berjalan') ? 'selected' : ''; ?>>Berjalan</option>
                          <option value="Selesai" <?php echo ($editData['status'] === 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                      </div>
                      <div class="col-span-12 md:col-span-9 text-end">
                        <a href="penggunaan.php" class="btn btn-outline-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                      </div>
                    </form>
                  </div>
                <?php endif; ?>

                <form method="GET" class="mb-4">
                  <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-4">
                      <input type="text" name="search" class="form-control" placeholder="Cari keperluan/kendaraan/pengguna..." value="<?php echo e($filterSearch); ?>">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <select name="filter_status" class="form-select">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="Berjalan" <?php echo $filterStatus === 'Berjalan' ? 'selected' : ''; ?>>Berjalan</option>
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
                        <a href="penggunaan.php" class="btn btn-outline-secondary w-full mt-2"><i class="ti ti-x me-1"></i>Reset</a>
                      <?php endif; ?>
                    </div>
                  </div>
                </form>



                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Kendaraan</th>
                        <th>Pengguna</th>
                        <th>Periode</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($penggunaan) === 0): ?>
                        <tr>
                          <td colspan="6" class="text-center text-muted">Belum ada data penggunaan.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($penggunaan as $item): ?>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <div class="rounded-full bg-primary-100 text-primary-600 flex items-center justify-center" style="width: 40px; height: 40px;">
                                  <i data-feather="truck" class="w-5 h-5"></i>
                                </div>
                                <div>
                                  <strong><?php echo e($item['nomor_polisi']); ?></strong>
                                  <div class="text-muted text-sm"><?php echo e($item['nama_kendaraan']); ?></div>
                                </div>
                              </div>
                            </td>
                            <td>
                              <div>
                                <strong><?php echo e($item['nama_pengguna']); ?></strong>
                                <div class="text-muted text-sm">@<?php echo e($item['username']); ?></div>
                              </div>
                            </td>
                            <td>
                              <div class="text-muted text-sm">
                                <?php echo e($item['tanggal_mulai'] ? date('d M Y', strtotime($item['tanggal_mulai'])) : '-'); ?>
                                —
                                <?php echo e($item['tanggal_selesai'] ? date('d M Y', strtotime($item['tanggal_selesai'])) : ''); ?>
                              </div>
                            </td>
                            <td><?php echo e($item['keperluan']); ?></td>
                            <td>
                              <?php
                                $status = $item['status'] ?? 'Berjalan';
                                $badgeClass = ($status === 'Selesai') ? 'bg-success-500' : 'bg-warning-500';
                              ?>
                              <span class="badge <?php echo $badgeClass; ?> text-white"><?php echo e($status); ?></span>
                            </td>
                            <td class="text-end">
                              <div class="btn-group" role="group">
                                <a href="edit.php?id=<?php echo (int)$item['id_penggunaan']; ?>" class="btn btn-outline-secondary btn-sm" title="Edit Data">
                                  <i class="ti ti-edit"></i>
                                </a>
                                <a href="hapus.php?id=<?php echo (int)$item['id_penggunaan']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus data penggunaan ini?');" title="Hapus Data">
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