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

// Dynamic ENUM reader to keep options aligned with DB schema
function db_name(mysqli $conn): string {
  $res = $conn->query('SELECT DATABASE() AS db');
  if ($res && ($row = $res->fetch_assoc())) { $res->free(); return (string)$row['db']; }
  return 'bsm_web';
}
function enum_values(mysqli $conn, string $table, string $column): array {
  $db = db_name($conn);
  $stmt = $conn->prepare('SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
  if (! $stmt) return [];
  $stmt->bind_param('sss', $db, $table, $column);
  $stmt->execute();
  $res = $stmt->get_result();
  $vals = [];
  if ($res && ($row = $res->fetch_assoc())) {
    $colType = (string)$row['COLUMN_TYPE'];
    if (preg_match("/^enum\\((.*)\\)$/i", $colType, $m)) {
      $inner = $m[1];
      // split by ',' but respect quotes
      $parts = preg_split("/\s*,\s*/", $inner);
      foreach ($parts as $p) {
        $p = trim($p);
        if (strlen($p) >= 2 && $p[0] === "'" && substr($p, -1) === "'") {
          $vals[] = stripcslashes(substr($p, 1, -1));
        }
      }
    }
  }
  if ($res) $res->free();
  $stmt->close();
  return $vals;
}
function ensure_allowed(string $value, array $allowed): string {
  if (in_array($value, $allowed, true)) return $value;
  return $allowed[0] ?? $value; // fallback to first allowed or original if empty
}

$errors = [];
// Build enum options from DB (fallback to defaults if not defined)
$enumDefaults = [
  'kondisi_ban'   => ['Baik','Perlu dicek','Rusak'],
  'kondisi_lampu' => ['Baik','Rusak'],
  'oli_mesin'     => ['Baik','Kurang','Harus ganti'],
  'rem'           => ['Baik','Perlu diperhatikan','Rusak'],
  'kebersihan'    => ['Bersih','Cukup','Kotor'],
];
$enumMap = [];
foreach ($enumDefaults as $col => $def) {
  $vals = enum_values($koneksi, 'tb_inspeksi', $col);
  $enumMap[$col] = !empty($vals) ? $vals : $def;
}

// Handle POST first
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

    $id_kendaraan   = (int)($_POST['id_kendaraan'] ?? 0);
    $tanggal        = $_POST['tanggal'] ?? '';
    $kondisi_ban    = $_POST['kondisi_ban'] ?? '';
    $kondisi_lampu  = $_POST['kondisi_lampu'] ?? '';
    $oli_mesin      = $_POST['oli_mesin'] ?? '';
    $rem            = $_POST['rem'] ?? '';
    $kebersihan     = $_POST['kebersihan'] ?? '';
    $catatan        = trim($_POST['catatan'] ?? '');

    if ($id_kendaraan <= 0) $errors[] = 'Kendaraan wajib dipilih.';
    if (! is_valid_date($tanggal)) $errors[] = 'Tanggal inspeksi tidak valid (YYYY-mm-dd).';
    // Sanitize to allowed values from DB to avoid enum truncation
    $kondisi_ban   = ensure_allowed($kondisi_ban,   $enumMap['kondisi_ban']);
    $kondisi_lampu = ensure_allowed($kondisi_lampu, $enumMap['kondisi_lampu']);
    $oli_mesin     = ensure_allowed($oli_mesin,     $enumMap['oli_mesin']);
    $rem           = ensure_allowed($rem,           $enumMap['rem']);
    $kebersihan    = ensure_allowed($kebersihan,    $enumMap['kebersihan']);

    if (empty($errors)) {
        try {
            $stmt = $koneksi->prepare("INSERT INTO tb_inspeksi (id_kendaraan, tanggal, kondisi_ban, kondisi_lampu, oli_mesin, rem, kebersihan, catatan) VALUES (?,?,?,?,?,?,?,?)");
            if (! $stmt) throw new Exception('Gagal menyiapkan query.');
            $stmt->bind_param('isssssss', $id_kendaraan, $tanggal, $kondisi_ban, $kondisi_lampu, $oli_mesin, $rem, $kebersihan, $catatan);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) {
                $err = $stmt->error;
                $stmt->close();
                throw new Exception($err ? ('Insert gagal: ' . $err) : 'Insert gagal: tidak ada baris yang ditambahkan.');
            }
            $stmt->close();
            header('Location: inspeksi.php?status=success&message=' . urlencode('Pengajuan inspeksi berhasil dikirim.'));
            exit;
        } catch (Throwable $e) {
            header('Location: inspeksi.php?status=error&message=' . urlencode('Gagal menyimpan data: ' . $e->getMessage()));
            exit;
        }
    } else {
        header('Location: inspeksi.php?status=error&message=' . urlencode(implode(' ', $errors)));
        exit;
    }
}

// GET: render form
$page_title = 'Tambah Inspeksi (User)';

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
                <li class="breadcrumb-item"><a href="inspeksi.php">Inspeksi</a></li>
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
              <h5 class="mb-0">Pengajuan Inspeksi</h5>
              <p class="text-sm text-muted mb-0">Isi kondisi aktual kendaraan untuk pemeriksaan awal.</p>
            </div>
            <div class="card-body">
              <form method="POST" class="grid grid-cols-12 gap-3">
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Kendaraan</label>
                  <select name="id_kendaraan" class="form-select" required>
                    <option value="">Pilih Kendaraan</option>
                    <?php foreach ($kendaraanOptions as $opt): ?>
                      <option value="<?php echo (int)$opt['id_kendaraan']; ?>">
                        <?php echo e($opt['nomor_polisi']); ?> - <?php echo e($opt['nama_kendaraan']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Tanggal</label>
                  <input type="date" name="tanggal" class="form-control" required>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Kondisi Ban</label>
                  <select name="kondisi_ban" class="form-select" required>
                    <?php foreach ($enumMap['kondisi_ban'] as $opt): ?>
                      <option value="<?php echo e($opt); ?>"><?php echo e($opt); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Kondisi Lampu</label>
                  <select name="kondisi_lampu" class="form-select" required>
                    <?php foreach ($enumMap['kondisi_lampu'] as $opt): ?>
                      <option value="<?php echo e($opt); ?>"><?php echo e($opt); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Oli Mesin</label>
                  <select name="oli_mesin" class="form-select" required>
                    <?php foreach ($enumMap['oli_mesin'] as $opt): ?>
                      <option value="<?php echo e($opt); ?>"><?php echo e($opt); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Rem</label>
                  <select name="rem" class="form-select" required>
                    <?php foreach ($enumMap['rem'] as $opt): ?>
                      <option value="<?php echo e($opt); ?>"><?php echo e($opt); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-span-12 md:col-span-3">
                  <label class="form-label">Kebersihan</label>
                  <select name="kebersihan" class="form-select" required>
                    <?php foreach ($enumMap['kebersihan'] as $opt): ?>
                      <option value="<?php echo e($opt); ?>"><?php echo e($opt); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-span-12">
                  <label class="form-label">Catatan</label>
                  <textarea name="catatan" class="form-control" rows="3" placeholder="Opsional"></textarea>
                </div>
                <div class="col-span-12 md:col-span-9 text-end">
                  <a href="inspeksi.php" class="btn btn-outline-secondary me-2">Batal</a>
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
