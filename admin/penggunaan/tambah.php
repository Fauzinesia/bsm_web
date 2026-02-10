<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
$page_title = 'Tambah Penggunaan Kendaraan';
// NOTE: Jangan include header/sidebar sebelum proses POST agar redirect lebih cepat

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function is_valid_date($date) {
    if (! $date) return false;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Fetch options
// Tunda fetch options hingga diperlukan untuk render (GET atau POST error)
$kendaraanOptions = [];
$penggunaOptions = [];

$errors = [];
$old = [
    'id_kendaraan' => '',
    'id_pengguna' => '',
    'tanggal_mulai' => '',
    'tanggal_selesai' => '',
    'keperluan' => '',
    'status' => 'Berjalan',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['id_kendaraan'] = (int)($_POST['id_kendaraan'] ?? 0);
    $old['id_pengguna']  = (int)($_POST['id_pengguna'] ?? 0);
    $old['tanggal_mulai'] = $_POST['tanggal_mulai'] ?? '';
    $old['tanggal_selesai'] = $_POST['tanggal_selesai'] ?? '';
    $old['keperluan'] = trim($_POST['keperluan'] ?? '');
    $old['status'] = $_POST['status'] ?? 'Berjalan';

    if ($old['id_kendaraan'] <= 0) $errors[] = 'Kendaraan wajib dipilih.';
    if ($old['id_pengguna'] <= 0) $errors[] = 'Pengguna wajib dipilih.';
    if (! is_valid_date($old['tanggal_mulai'])) $errors[] = 'Tanggal mulai tidak valid (YYYY-mm-dd).';
    if ($old['tanggal_selesai'] !== '' && ! is_valid_date($old['tanggal_selesai'])) $errors[] = 'Tanggal selesai tidak valid (YYYY-mm-dd).';
    if ($old['tanggal_selesai'] !== '' && $old['tanggal_mulai'] !== '' && strtotime($old['tanggal_selesai']) < strtotime($old['tanggal_mulai'])) $errors[] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    if ($old['keperluan'] === '') $errors[] = 'Keperluan tidak boleh kosong.';
    if (! in_array($old['status'], ['Berjalan','Selesai'], true)) $errors[] = 'Status tidak valid.';

    if (empty($errors)) {
        try {
            // Untuk single INSERT, transaksi tidak wajib; hapus begin_commit agar lebih ringan
            $stmt = $koneksi->prepare("INSERT INTO tb_penggunaan (id_kendaraan, id_pengguna, tanggal_mulai, tanggal_selesai, keperluan, status) VALUES (?, ?, ?, NULLIF(?, ''), ?, ?)");
            if (! $stmt) throw new Exception('Gagal menyiapkan query.');
            $stmt->bind_param('iissss', $old['id_kendaraan'], $old['id_pengguna'], $old['tanggal_mulai'], $old['tanggal_selesai'], $old['keperluan'], $old['status']);
            $stmt->execute();
            $stmt->close();
            header('Location: penggunaan.php?status=created');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}
// Fetch options hanya jika render diperlukan (GET atau POST dengan error)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($errors)) {
    if ($res = $koneksi->query("SELECT id_kendaraan, nomor_polisi, nama_kendaraan FROM tb_kendaraan ORDER BY nama_kendaraan ASC")) {
        while ($row = $res->fetch_assoc()) { $kendaraanOptions[] = $row; }
        $res->free();
    }
    if ($res = $koneksi->query("SELECT id_pengguna, nama, username FROM tb_pengguna ORDER BY nama ASC")) {
        while ($row = $res->fetch_assoc()) { $penggunaOptions[] = $row; }
        $res->free();
    }
}

// Setelah logika POST selesai, baru include header/sidebar untuk render
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Tambah Penggunaan Kendaraan</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item"><a href="penggunaan.php">Penggunaan</a></li>
              <li class="breadcrumb-item" aria-current="page">Tambah</li>
            </ul>
          </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
          <div class="col-span-12">
            <div class="card">
              <div class="card-header flex justify-between items-center">
                <h5 class="mb-0">Form Tambah Penggunaan</h5>
                <div>
                  <a href="penggunaan.php" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-2"></i>Kembali</a>
                </div>
              </div>
              <div class="card-body">
                <?php if (!empty($errors)): ?>
                  <div class="alert alert-danger" role="alert">
                    <?php echo e(implode(' ', $errors)); ?>
                  </div>
                <?php endif; ?>

                <form method="POST" class="grid grid-cols-12 gap-3">
                  <div class="col-span-12 md:col-span-3">
                    <label class="form-label">Kendaraan</label>
                    <select name="id_kendaraan" class="form-select" required>
                      <option value="">Pilih Kendaraan</option>
                      <?php foreach ($kendaraanOptions as $opt): ?>
                        <option value="<?php echo (int)$opt['id_kendaraan']; ?>" <?php echo ((int)$old['id_kendaraan'] === (int)$opt['id_kendaraan']) ? 'selected' : ''; ?>>
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
                        <option value="<?php echo (int)$opt['id_pengguna']; ?>" <?php echo ((int)$old['id_pengguna'] === (int)$opt['id_pengguna']) ? 'selected' : ''; ?>>
                          <?php echo e($opt['nama']); ?> (<?php echo e($opt['username']); ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-span-12 md:col-span-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control" value="<?php echo e($old['tanggal_mulai']); ?>" required>
                  </div>
                  <div class="col-span-12 md:col-span-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control" value="<?php echo e($old['tanggal_selesai']); ?>">
                  </div>
                  <div class="col-span-12">
                    <label class="form-label">Keperluan</label>
                    <textarea name="keperluan" class="form-control" rows="3" required><?php echo e($old['keperluan']); ?></textarea>
                  </div>
                  <div class="col-span-12 md:col-span-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                      <option value="Berjalan" <?php echo ($old['status'] === 'Berjalan') ? 'selected' : ''; ?>>Berjalan</option>
                      <option value="Selesai" <?php echo ($old['status'] === 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                  </div>
                  <div class="col-span-12 md:col-span-9 text-end">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-2"></i>Simpan</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>