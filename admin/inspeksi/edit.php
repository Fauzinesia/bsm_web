<?php
session_start();
if (! isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
require_once dirname(__DIR__, 2) . '/config/koneksi.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function valid_date($d){ return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d); }
function valid_enum_field($field, $value){
    $allowed = [
        'kondisi_ban' => ['Baik','Perlu dicek','Rusak'],
        'kondisi_lampu' => ['Baik','Rusak'],
        'oli_mesin' => ['Baik','Kurang','Harus ganti'],
        'rem' => ['Baik','Perlu diperhatikan','Rusak'],
        'kebersihan' => ['Bersih','Cukup','Kotor'],
    ];
    return isset($allowed[$field]) && in_array($value, $allowed[$field], true);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: inspeksi.php'); exit; }

// Ambil data inspeksi
$stmt = $koneksi->prepare('SELECT * FROM tb_inspeksi WHERE id_inspeksi = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (! $data) { header('Location: inspeksi.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'] ?? '';
    $kondisi_ban = $_POST['kondisi_ban'] ?? '';
    $kondisi_lampu = $_POST['kondisi_lampu'] ?? '';
    $oli_mesin = $_POST['oli_mesin'] ?? '';
    $rem = $_POST['rem'] ?? '';
    $kebersihan = $_POST['kebersihan'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');

    if (! valid_date($tanggal)) $errors[] = 'Tanggal tidak valid.';
    foreach ([
        ['kondisi_ban',$kondisi_ban],
        ['kondisi_lampu',$kondisi_lampu],
        ['oli_mesin',$oli_mesin],
        ['rem',$rem],
        ['kebersihan',$kebersihan]
    ] as $pair) {
        if (! valid_enum_field($pair[0], $pair[1])) $errors[] = ucfirst(str_replace('_',' ', $pair[0])) . ' tidak valid.';
    }

    if (empty($errors)) {
        $sql = 'UPDATE tb_inspeksi SET tanggal=?, kondisi_ban=?, kondisi_lampu=?, oli_mesin=?, rem=?, kebersihan=?, catatan=? WHERE id_inspeksi=?';
        $stmt = $koneksi->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sssssssi', $tanggal, $kondisi_ban, $kondisi_lampu, $oli_mesin, $rem, $kebersihan, $catatan, $id);
            $stmt->execute();
            $stmt->close();
            session_write_close();
            header('Location: inspeksi.php?status=updated');
            exit;
        } else {
            $errors[] = 'Gagal menyimpan perubahan.';
        }
    }
}

// Ambil data kendaraan terkait
$kendaraan = null;
$stmt = $koneksi->prepare('SELECT nomor_polisi, nama_kendaraan FROM tb_kendaraan WHERE id_kendaraan = ?');
$stmt->bind_param('i', $data['id_kendaraan']);
$stmt->execute();
$kendaraan = $stmt->get_result()->fetch_assoc();
$stmt->close();

$page_title = 'Edit Inspeksi Kendaraan';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';
?>
<div class="pc-container">
  <div class="pc-content">
    <div class="page-header">
      <div class="page-block">
        <div class="page-header-title"><h5 class="mb-0">Edit Inspeksi</h5></div>
        <ul class="breadcrumb">
          <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="inspeksi.php">Inspeksi</a></li>
          <li class="breadcrumb-item" aria-current="page">Edit</li>
        </ul>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header"><h5 class="mb-0">Form Edit Inspeksi</h5></div>
          <div class="card-body">
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label">Kendaraan</label>
              <input type="text" class="form-control" value="<?php echo e(($kendaraan['nama_kendaraan'] ?? '-') . ' - ' . ($kendaraan['nomor_polisi'] ?? '-')); ?>" disabled>
            </div>

            <form method="post">
              <div class="mb-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?php echo e($data['tanggal']); ?>" required>
              </div>

              <?php
              // Field dengan opsi berbeda-beda sesuai database
              $fieldsWithOptions = [
                'kondisi_ban' => ['label' => 'Kondisi Ban', 'options' => ['Baik','Perlu dicek','Rusak']],
                'kondisi_lampu' => ['label' => 'Kondisi Lampu', 'options' => ['Baik','Rusak']],
                'oli_mesin' => ['label' => 'Oli Mesin', 'options' => ['Baik','Kurang','Harus ganti']],
                'rem' => ['label' => 'Rem', 'options' => ['Baik','Perlu diperhatikan','Rusak']],
              ];
              foreach ($fieldsWithOptions as $name => $config): ?>
                <div class="mb-3">
                  <label class="form-label"><?php echo e($config['label']); ?></label>
                  <select name="<?php echo $name; ?>" class="form-select" required>
                    <?php foreach ($config['options'] as $opt): ?>
                      <option value="<?php echo $opt; ?>" <?php echo ($data[$name] === $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php endforeach; ?>

              <div class="mb-3">
                <label class="form-label">Kebersihan</label>
                <select name="kebersihan" class="form-select" required>
                  <?php foreach (['Bersih','Cukup','Kotor'] as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo ($data['kebersihan'] === $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="catatan" rows="3" class="form-control"><?php echo e($data['catatan']); ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-2"></i>Simpan Perubahan</button>
                <a href="inspeksi.php" class="btn btn-secondary"><i class="ti ti-arrow-left me-2"></i>Batal</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
