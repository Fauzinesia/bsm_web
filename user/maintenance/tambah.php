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
            $prefill_tanggal_service = $row['tanggal'] ?: '';
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

// Handle POST first to ensure fast redirect without prior output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Release session lock early to avoid blocking
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

    $id_kendaraan = (int)($_POST['id_kendaraan'] ?? 0);
    $tanggal_service = $_POST['tanggal_service'] ?? '';
    $jenis_service = trim($_POST['jenis_service'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $biaya = $_POST['biaya'] ?? '';
    // Status ditetapkan oleh user saat input: selalu 'Dijadwalkan'. Admin yang akan mengubah status selanjutnya.
    $status = 'Dijadwalkan';

    if ($id_kendaraan <= 0) $errors[] = 'Kendaraan wajib dipilih.';
    if (! is_valid_date($tanggal_service)) $errors[] = 'Tanggal service tidak valid (YYYY-mm-dd).';
    if ($jenis_service === '') $errors[] = 'Jenis service tidak boleh kosong.';
    if ($deskripsi === '') $errors[] = 'Deskripsi tidak boleh kosong.';
    if ($biaya !== '' && ! is_numeric($biaya)) $errors[] = 'Biaya harus berupa angka.';

    if (empty($errors)) {
        try {
            $koneksi->begin_transaction();
            $stmt = $koneksi->prepare("INSERT INTO tb_maintenance (id_kendaraan, tanggal_service, jenis_service, deskripsi, biaya, status) VALUES (?, ?, ?, ?, ?, ?)");
            if (! $stmt) throw new Exception('Gagal menyiapkan query.');
            $biaya_param = ($biaya !== '') ? (float)$biaya : null;
            $deskripsi_final = $deskripsi;
            if ($prefill_id_inspeksi > 0) { $deskripsi_final .= "\nRef Inspeksi #" . $prefill_id_inspeksi; }
            $stmt->bind_param('isssds', $id_kendaraan, $tanggal_service, $jenis_service, $deskripsi_final, $biaya_param, $status);
            $stmt->execute();
            $stmt->close();
            $koneksi->commit();
            header('Location: maintenance.php?status=success&message=' . urlencode('Pengajuan maintenance berhasil dikirim dan dijadwalkan.')); 
            exit;
        } catch (Throwable $e) {
            if ($koneksi->errno) { $koneksi->rollback(); }
            // Jika gagal, fallback ke tampilan dengan pesan error melalui query string
            header('Location: maintenance.php?status=error&message=' . urlencode('Gagal menyimpan data: ' . $e->getMessage()));
            exit;
        }
    } else {
        // Kirim kembali ke list dengan pesan error sederhana
        header('Location: maintenance.php?status=error&message=' . urlencode(implode(' ', $errors)));
        exit;
    }
}

// GET: render form
$page_title = 'Tambah Maintenance (User)';

// Fetch kendaraan options
$kendaraanOptions = [];
if ($res = $koneksi->query("SELECT id_kendaraan, nomor_polisi, nama_kendaraan FROM tb_kendaraan ORDER BY nama_kendaraan ASC")) {
    while ($row = $res->fetch_assoc()) { $kendaraanOptions[] = $row; }
    $res->free();
}

include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';
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
              <h5 class="mb-0">Pengajuan Maintenance</h5>
              <p class="text-sm text-muted mb-0">Status awal pengajuan adalah <strong>Dijadwalkan</strong>. Admin akan memproses dan memperbarui status.</p>
            </div>
            <div class="card-body">
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
                  <input type="date" name="tanggal_service" class="form-control" value="<?php echo e($prefill_tanggal_service); ?>" required>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Jenis Service</label>
                  <input type="text" name="jenis_service" class="form-control" required>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Biaya (Rp)</label>
                  <input type="number" step="0.01" min="0" name="biaya" class="form-control" placeholder="Opsional">
                </div>
                <div class="col-span-12">
                  <label class="form-label">Deskripsi</label>
                  <textarea name="deskripsi" class="form-control" rows="3" required><?php echo e($prefill_deskripsi); ?></textarea>
                </div>
                <div class="col-span-12 md:col-span-9 text-end">
                  <a href="maintenance.php" class="btn btn-outline-secondary me-2">Batal</a>
                  <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
