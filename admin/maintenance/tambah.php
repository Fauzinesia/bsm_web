<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function is_valid_date($date) {
  if (! $date) return false;
  $d = DateTime::createFromFormat('Y-m-d', $date);
  return $d && $d->format('Y-m-d') === $date;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kendaraan = (int)($_POST['id_kendaraan'] ?? 0);
    $tanggal_service = $_POST['tanggal_service'] ?? '';
    $jenis_service = trim($_POST['jenis_service'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $biaya = $_POST['biaya'] ?? '';
    $status = $_POST['status'] ?? 'Dijadwalkan';
    $id_inspeksi_ref = isset($_POST['id_inspeksi']) ? (int)$_POST['id_inspeksi'] : 0;

    if ($id_kendaraan <= 0) $errors[] = 'Kendaraan wajib dipilih.';
    if (! is_valid_date($tanggal_service)) $errors[] = 'Tanggal service tidak valid (YYYY-mm-dd).';
    if ($jenis_service === '') $errors[] = 'Jenis service tidak boleh kosong.';
    if ($biaya !== '' && ! is_numeric($biaya)) $errors[] = 'Biaya harus berupa angka.';
    if (! in_array($status, ['Dijadwalkan','Proses','Selesai'], true)) $errors[] = 'Status tidak valid.';

    if (empty($errors)) {
        try {
            $koneksi->begin_transaction();
            $stmt = $koneksi->prepare("INSERT INTO tb_maintenance (id_kendaraan, tanggal_service, jenis_service, deskripsi, biaya, status) VALUES (?, ?, ?, ?, ?, ?)");
            if (! $stmt) throw new Exception('Gagal menyiapkan query.');
            $biaya_param = ($biaya !== '') ? (float)$biaya : null;
            $deskripsi_final = $deskripsi;
            if ($id_inspeksi_ref > 0) { $deskripsi_final .= "\nRef Inspeksi #" . $id_inspeksi_ref; }
            $stmt->bind_param('isssds', $id_kendaraan, $tanggal_service, $jenis_service, $deskripsi_final, $biaya_param, $status);
            $stmt->execute();
            $stmt->close();
            $koneksi->commit();
            header('Location: maintenance.php?status=success&message=' . urlencode('Data maintenance berhasil ditambahkan.'));
            exit;
        } catch (Throwable $e) {
            $koneksi->rollback();
            $errors[] = 'Gagal menambah data: ' . $e->getMessage();
        }
    }
}

$page_title = 'Tambah Maintenance Kendaraan';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';

// Prefill dari Inspeksi (opsional)
$prefill_id_inspeksi = isset($_GET['id_inspeksi']) ? (int)$_GET['id_inspeksi'] : 0;
$prefill_id_kendaraan = isset($_GET['id_kendaraan']) ? (int)$_GET['id_kendaraan'] : 0;
$prefill_tanggal_service = '';
$prefill_deskripsi = '';

if ($prefill_id_inspeksi > 0) {
    $stmt = $koneksi->prepare("SELECT i.id_inspeksi, i.id_kendaraan, i.tanggal, i.kondisi_ban, i.kondisi_lampu, i.oli_mesin, i.rem, i.kebersihan, i.catatan, k.nomor_polisi, k.nama_kendaraan FROM tb_inspeksi i JOIN tb_kendaraan k ON k.id_kendaraan=i.id_kendaraan WHERE i.id_inspeksi=?");
    if ($stmt) {
        $stmt->bind_param('i', $prefill_id_inspeksi);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $prefill_id_kendaraan = (int)$row['id_kendaraan'];
            $prefill_tanggal_service = $row['tanggal'] ?: date('Y-m-d');
            $ringkasan = [
                'Ban' => $row['kondisi_ban'] ?? '-',
                'Lampu' => $row['kondisi_lampu'] ?? '-',
                'Oli' => $row['oli_mesin'] ?? '-',
                'Rem' => $row['rem'] ?? '-',
                'Kebersihan' => $row['kebersihan'] ?? '-',
            ];
            $prefill_deskripsi = 'Rujukan inspeksi #' . (int)$row['id_inspeksi'] . ' (' . ($row['tanggal'] ?: '-') . ') pada ' . ($row['nama_kendaraan'] ?? '-') . ' ' . ($row['nomor_polisi'] ?? '-') . "\n";
            foreach ($ringkasan as $k => $v) { $prefill_deskripsi .= $k . ': ' . $v . "\n"; }
            if (! empty($row['catatan'])) { $prefill_deskripsi .= 'Catatan: ' . $row['catatan']; }
        }
        $stmt->close();
    }
}

// Fetch kendaraan options
$kendaraanOptions = [];
if ($res = $koneksi->query("SELECT id_kendaraan, nomor_polisi, nama_kendaraan FROM tb_kendaraan ORDER BY nama_kendaraan ASC")) {
    while ($row = $res->fetch_assoc()) { $kendaraanOptions[] = $row; }
    $res->free();
}
?>
  <div class="pc-container">
    <div class="pc-content">
      <div class="page-header">
        <div class="page-block">
          <div class="row align-items-center">
            <div class="col-md-12">
              <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="maintenance.php">Maintenance</a></li>
                <li class="breadcrumb-item" aria-current="page">Tambah</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Tambah Maintenance</h5>
            </div>
            <div class="card-body">
              <?php if (! empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                  <?php echo e(implode(' ', $errors)); ?>
                </div>
              <?php endif; ?>

              <form method="POST" class="grid grid-cols-12 gap-3">
                <?php if ($prefill_id_inspeksi > 0): ?>
                  <input type="hidden" name="id_inspeksi" value="<?php echo (int)$prefill_id_inspeksi; ?>">
                <?php endif; ?>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Kendaraan</label>
                  <select name="id_kendaraan" class="form-select" required>
                    <option value="">Pilih Kendaraan</option>
                    <?php foreach ($kendaraanOptions as $opt): ?>
                      <option value="<?php echo (int)$opt['id_kendaraan']; ?>" <?php echo ($prefill_id_kendaraan === (int)$opt['id_kendaraan']) ? 'selected' : ''; ?>>
                        <?php echo e($opt['nomor_polisi']); ?> - <?php echo e($opt['nama_kendaraan']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Tanggal Service</label>
                  <input type="date" name="tanggal_service" class="form-control" value="<?php echo e($prefill_tanggal_service ?: ''); ?>" required>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Jenis Service</label>
                  <input type="text" name="jenis_service" class="form-control" required>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Biaya (Rp)</label>
                  <input type="number" step="0.01" min="0" name="biaya" class="form-control">
                </div>
                <div class="col-span-12">
                  <label class="form-label">Deskripsi</label>
                  <textarea name="deskripsi" class="form-control" rows="3" required><?php echo e($prefill_deskripsi); ?></textarea>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Status</label>
                  <select name="status" class="form-select" required>
                    <option value="Dijadwalkan">Dijadwalkan</option>
                    <option value="Proses">Proses</option>
                    <option value="Selesai">Selesai</option>
                  </select>
                </div>
                <div class="col-span-12 md:col-span-9 text-end">
                  <a href="maintenance.php" class="btn btn-outline-secondary me-2">Batal</a>
                  <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
