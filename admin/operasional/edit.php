<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }

// Lepas lock session agar proses POST/DB tidak tertahan
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kendaraan = isset($_POST['id_kendaraan']) ? (int)$_POST['id_kendaraan'] : null;
    $tanggal      = $_POST['tanggal'] ?? '';
    $jenis_biaya  = $_POST['jenis_biaya'] ?? '';
    $nominal      = isset($_POST['nominal']) ? (float)str_replace(['.',','], ['',''], $_POST['nominal']) : 0;
    $keterangan   = $_POST['keterangan'] ?? '';

    if (empty($id_kendaraan) || empty($tanggal) || empty($jenis_biaya)) {
        $error = 'Mohon lengkapi data wajib.';
    } else {
        $stmt = $koneksi->prepare('UPDATE tb_biaya_operasional SET id_kendaraan = ?, tanggal = ?, jenis_biaya = ?, nominal = ?, keterangan = ? WHERE id_biaya = ?');
        if ($stmt) {
            $stmt->bind_param('issdsi', $id_kendaraan, $tanggal, $jenis_biaya, $nominal, $keterangan, $id);
            if ($stmt->execute()) {
                header('Location: operasional.php?status=success&message=' . urlencode('Biaya operasional berhasil diperbarui'));
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

// Ambil data biaya untuk form (GET)
$biaya = null;
if ($id > 0) {
    $stmt = $koneksi->prepare('SELECT * FROM tb_biaya_operasional WHERE id_biaya = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $biaya = $result->fetch_assoc();
        $stmt->close();
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
$page_title = 'Edit Biaya Operasional';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<?php if (!$biaya): ?>
  <div class="pc-container"><div class="pc-content"><div class="alert alert-danger">Data tidak ditemukan.</div></div></div>
  <?php include dirname(__DIR__, 2) . '/includes/footer.php'; exit; ?>
<?php endif; ?>

<div class="pc-container">
  <div class="pc-content">
    <div class="page-header">
      <div class="page-block">
        <div class="page-header-title">
          <h5 class="mb-0 font-medium">Edit Biaya Operasional</h5>
        </div>
        <ul class="breadcrumb">
          <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="operasional.php">Operasional</a></li>
          <li class="breadcrumb-item" aria-current="page">Edit</li>
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
                  <?php foreach ($kendaraanList as $k): ?>
                    <option value="<?php echo (int)$k['id_kendaraan']; ?>" <?php echo ((int)$k['id_kendaraan'] === (int)$biaya['id_kendaraan']) ? 'selected' : ''; ?>><?php echo e($k['nama_kendaraan']); ?> (<?php echo e($k['nomor_polisi']); ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="row">
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label" for="tanggal">Tanggal</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?php echo e($biaya['tanggal']); ?>" required>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label" for="jenis_biaya">Jenis Biaya</label>
                    <?php $ops = ['BBM','Tol','Parkir','Pajak','Asuransi','Lainnya']; ?>
                    <select class="form-select" id="jenis_biaya" name="jenis_biaya" required>
                      <?php foreach ($ops as $o): ?>
                        <option value="<?php echo e($o); ?>" <?php echo ($biaya['jenis_biaya'] === $o) ? 'selected' : ''; ?>><?php echo e($o); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label" for="nominal">Nominal (Rp)</label>
                    <input type="number" class="form-control" id="nominal" name="nominal" min="0" step="1" value="<?php echo (int)($biaya['nominal'] ?? 0); ?>" required>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label" for="keterangan">Keterangan</label>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Opsional"><?php echo e($biaya['keterangan'] ?? ''); ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-2"></i>Simpan Perubahan</button>
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