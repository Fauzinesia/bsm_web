<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }

// Hindari session lock berkepanjangan pada request POST
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$error = '';

// Prefill dari Maintenance (opsional)
$prefill_id_maintenance = isset($_GET['id_maintenance']) ? (int)$_GET['id_maintenance'] : 0;
$prefill_id_kendaraan = isset($_GET['id_kendaraan']) ? (int)$_GET['id_kendaraan'] : 0;
$prefill_keterangan = '';

if ($prefill_id_maintenance > 0) {
    $stmt = $koneksi->prepare('SELECT m.id_maintenance, m.id_kendaraan, m.tanggal_service, m.jenis_service, m.deskripsi, m.status, k.nama_kendaraan, k.nomor_polisi FROM tb_maintenance m JOIN tb_kendaraan k ON k.id_kendaraan=m.id_kendaraan WHERE m.id_maintenance=?');
    if ($stmt) {
        $stmt->bind_param('i', $prefill_id_maintenance);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $prefill_id_kendaraan = (int)$row['id_kendaraan'];
            $prefill_keterangan = 'Rujukan maintenance #' . (int)$row['id_maintenance'] . ' (' . ($row['tanggal_service'] ?? '-') . ') ' . ($row['jenis_service'] ?? '-') . ' pada ' . ($row['nama_kendaraan'] ?? '-') . ' ' . ($row['nomor_polisi'] ?? '-');
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kendaraan = isset($_POST['id_kendaraan']) ? (int)$_POST['id_kendaraan'] : null;
    $tanggal      = $_POST['tanggal'] ?? '';
    $jenis_biaya  = $_POST['jenis_biaya'] ?? '';
    $nominal      = isset($_POST['nominal']) ? (float)str_replace(['.',','], ['',''], $_POST['nominal']) : 0;
    $keterangan   = $_POST['keterangan'] ?? '';
    $id_maintenance_ref = isset($_POST['id_maintenance']) ? (int)$_POST['id_maintenance'] : 0;

    if (empty($id_kendaraan) || empty($tanggal) || empty($jenis_biaya)) {
        $error = 'Mohon lengkapi data wajib.';
    } else {
        $stmt = $koneksi->prepare('INSERT INTO tb_biaya_operasional (id_kendaraan, tanggal, jenis_biaya, nominal, keterangan) VALUES (?,?,?,?,?)');
        if ($stmt) {
            $keterangan_final = $keterangan;
            if ($id_maintenance_ref > 0) { $keterangan_final .= "\nRef Maintenance #" . $id_maintenance_ref; }
            $stmt->bind_param('issds', $id_kendaraan, $tanggal, $jenis_biaya, $nominal, $keterangan_final);
            if ($stmt->execute()) {
                header('Location: operasional.php?status=success&message=' . urlencode('Biaya operasional berhasil ditambahkan'));
                exit;
            } else {
                $error = 'Gagal menyimpan data.';
            }
            $stmt->close();
        } else {
            $error = 'Gagal menyiapkan query.';
        }
    }
}

// Ambil data kendaraan untuk pilihan
$kendaraanList = [];
if ($result = $koneksi->query('SELECT id_kendaraan, nama_kendaraan, nomor_polisi FROM tb_kendaraan ORDER BY nama_kendaraan ASC')) {
    while ($row = $result->fetch_assoc()) {
        $kendaraanList[] = $row;
    }
    $result->free();
}
?>

<?php
// Setelah proses POST selesai, baru muat header/sidebar agar redirect tidak terblokir
$page_title = 'Tambah Biaya Operasional';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div class="pc-container">
  <div class="pc-content">
    <div class="page-header">
      <div class="page-block">
        <div class="page-header-title">
          <h5 class="mb-0 font-medium">Tambah Biaya Operasional</h5>
        </div>
        <ul class="breadcrumb">
          <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="operasional.php">Operasional</a></li>
          <li class="breadcrumb-item" aria-current="page">Tambah</li>
        </ul>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <?php if ($error): ?>
              <div class="alert alert-danger" role="alert"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="mb-3">
                <label class="form-label" for="id_kendaraan">Kendaraan</label>
                <select class="form-select" id="id_kendaraan" name="id_kendaraan" required>
                  <option value="">-- Pilih Kendaraan --</option>
                  <?php foreach ($kendaraanList as $k): ?>
                    <option value="<?php echo (int)$k['id_kendaraan']; ?>" <?php echo ($prefill_id_kendaraan === (int)$k['id_kendaraan']) ? 'selected' : ''; ?>><?php echo e($k['nama_kendaraan']); ?> (<?php echo e($k['nomor_polisi']); ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="row">
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label" for="tanggal">Tanggal</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label" for="jenis_biaya">Jenis Biaya</label>
                    <select class="form-select" id="jenis_biaya" name="jenis_biaya" required>
                      <option value="BBM">BBM</option>
                      <option value="Tol">Tol</option>
                      <option value="Parkir">Parkir</option>
                      <option value="Pajak">Pajak</option>
                      <option value="Asuransi">Asuransi</option>
                      <option value="Lainnya">Lainnya</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label" for="nominal">Nominal (Rp)</label>
                    <input type="number" class="form-control" id="nominal" name="nominal" min="0" step="1" placeholder="0" required>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label" for="keterangan">Keterangan</label>
                <?php if ($prefill_id_maintenance > 0): ?>
                  <input type="hidden" name="id_maintenance" value="<?php echo (int)$prefill_id_maintenance; ?>">
                <?php endif; ?>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Opsional"><?php echo e($prefill_keterangan); ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-2"></i>Simpan</button>
                <a href="operasional.php" class="btn btn-outline-secondary">Batal</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
