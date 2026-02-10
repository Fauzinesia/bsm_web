<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

// Validasi parameter ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: kendaraan.php?error=invalid_id');
    exit;
}

$id_kendaraan = (int)$_GET['id'];

// Ambil data kendaraan
$sql = "SELECT * FROM tb_kendaraan WHERE id_kendaraan = ?";
$stmt = $koneksi->prepare($sql);

if (!$stmt) {
    header('Location: kendaraan.php?error=database');
    exit;
}

$stmt->bind_param('i', $id_kendaraan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $koneksi->close();
    header('Location: kendaraan.php?error=not_found');
    exit;
}

$kendaraan = $result->fetch_assoc();
$stmt->close();

// Fungsi untuk cek masa berlaku
function cekMasaBerlaku($tanggal) {
    if (empty($tanggal)) {
        return ['status' => 'none', 'text' => 'Tidak ada data', 'class' => 'text-muted'];
    }
    
    $now = time();
    $expire = strtotime($tanggal);
    $diff = $expire - $now;
    $days = floor($diff / (60 * 60 * 24));
    
    if ($days < 0) {
        return ['status' => 'expired', 'text' => 'Sudah lewat ' . abs($days) . ' hari', 'class' => 'text-danger'];
    } elseif ($days <= 30) {
        return ['status' => 'warning', 'text' => 'Akan habis dalam ' . $days . ' hari', 'class' => 'text-warning'];
    } else {
        return ['status' => 'active', 'text' => 'Masih berlaku', 'class' => 'text-success'];
    }
}

$page_title = 'Detail Kendaraan';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Detail Kendaraan</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item"><a href="kendaraan.php">Data Kendaraan</a></li>
              <li class="breadcrumb-item" aria-current="page">Detail</li>
            </ul>
          </div>
        </div>

        <div class="row">
          <!-- Foto Kendaraan -->
          <div class="col-lg-4">
            <div class="card">
              <div class="card-body text-center">
                <?php if (!empty($kendaraan['foto_kendaraan']) && file_exists(dirname(__DIR__, 2) . '/' . $kendaraan['foto_kendaraan'])): ?>
                  <img src="../../<?php echo htmlspecialchars($kendaraan['foto_kendaraan']); ?>" alt="Foto Kendaraan" class="img-fluid rounded mb-3" style="max-height: 300px; object-fit: cover;">
                <?php else: ?>
                  <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height: 300px;">
                    <div class="text-center">
                      <i class="ti ti-photo" style="font-size: 80px; color: #ccc;"></i>
                      <p class="text-muted mt-2">Foto tidak tersedia</p>
                    </div>
                  </div>
                <?php endif; ?>
                <h4 class="mb-1"><?php echo htmlspecialchars($kendaraan['nomor_polisi']); ?></h4>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($kendaraan['nama_kendaraan']); ?></p>
                <?php
                  $status = $kendaraan['status_operasional'] ?? 'Aktif';
                  $badgeClass = 'bg-success';
                  if ($status === 'Perawatan') {
                      $badgeClass = 'bg-warning';
                  } elseif ($status === 'Rusak') {
                      $badgeClass = 'bg-danger';
                  } elseif ($status === 'Disewa') {
                      $badgeClass = 'bg-primary';
                  }
                ?>
                <span class="badge <?php echo $badgeClass; ?> text-white px-3 py-2">
                  <?php echo htmlspecialchars($status); ?>
                </span>
              </div>
            </div>

            <!-- Aksi -->
            <div class="card">
              <div class="card-body">
                <div class="d-grid gap-2">
                  <a href="edit.php?id=<?php echo (int)$kendaraan['id_kendaraan']; ?>" class="btn btn-primary">
                    <i class="ti ti-edit me-2"></i>Edit Data
                  </a>
                  <a href="kendaraan.php" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-2"></i>Kembali ke Daftar
                  </a>
                  <a href="javascript:window.print()" class="btn btn-outline-info">
                    <i class="ti ti-printer me-2"></i>Cetak Detail
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Informasi Detail -->
          <div class="col-lg-8">
            <!-- Informasi Umum -->
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-info-circle me-2"></i>Informasi Umum</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                      <tr>
                        <td class="text-muted" width="40%">Nomor Polisi</td>
                        <td><strong><?php echo htmlspecialchars($kendaraan['nomor_polisi']); ?></strong></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Nama Kendaraan</td>
                        <td><strong><?php echo htmlspecialchars($kendaraan['nama_kendaraan']); ?></strong></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Merk</td>
                        <td><?php echo htmlspecialchars($kendaraan['merk'] ?? '-'); ?></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Tipe</td>
                        <td><?php echo htmlspecialchars($kendaraan['tipe'] ?? '-'); ?></td>
                      </tr>
                    </table>
                  </div>
                  <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                      <tr>
                        <td class="text-muted" width="40%">Tahun</td>
                        <td><?php echo htmlspecialchars($kendaraan['tahun'] ?? '-'); ?></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Warna</td>
                        <td><?php echo htmlspecialchars($kendaraan['warna'] ?? '-'); ?></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Status Operasional</td>
                        <td>
                          <span class="badge <?php echo $badgeClass; ?> text-white">
                            <?php echo htmlspecialchars($status); ?>
                          </span>
                        </td>
                      </tr>
                      <tr>
                        <td class="text-muted">Tanggal Input</td>
                        <td><?php echo $kendaraan['created_at'] ? date('d M Y H:i', strtotime($kendaraan['created_at'])) : '-'; ?></td>
                      </tr>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Dokumen Kendaraan -->
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-file-text me-2"></i>Dokumen Kendaraan</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                      <tr>
                        <td class="text-muted" width="40%">Nomor Rangka</td>
                        <td><?php echo htmlspecialchars($kendaraan['nomor_rangka'] ?? '-'); ?></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Nomor Mesin</td>
                        <td><?php echo htmlspecialchars($kendaraan['nomor_mesin'] ?? '-'); ?></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Nomor BPKB</td>
                        <td><?php echo htmlspecialchars($kendaraan['nomor_bpkb'] ?? '-'); ?></td>
                      </tr>
                    </table>
                  </div>
                  <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                      <tr>
                        <td class="text-muted" width="40%">Terakhir Update</td>
                        <td><?php echo $kendaraan['updated_at'] ? date('d M Y H:i', strtotime($kendaraan['updated_at'])) : '-'; ?></td>
                      </tr>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Masa Berlaku Dokumen -->
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-calendar-event me-2"></i>Masa Berlaku Dokumen</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <div class="border rounded p-3">
                      <h6 class="text-muted mb-2">STNK</h6>
                      <?php if (!empty($kendaraan['masa_berlaku_stnk'])): ?>
                        <h5 class="mb-1"><?php echo date('d M Y', strtotime($kendaraan['masa_berlaku_stnk'])); ?></h5>
                        <?php $check = cekMasaBerlaku($kendaraan['masa_berlaku_stnk']); ?>
                        <small class="<?php echo $check['class']; ?>">
                          <i class="ti ti-alert-circle"></i> <?php echo $check['text']; ?>
                        </small>
                      <?php else: ?>
                        <p class="text-muted mb-0">Tidak ada data</p>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="border rounded p-3">
                      <h6 class="text-muted mb-2">Pajak</h6>
                      <?php if (!empty($kendaraan['masa_berlaku_pajak'])): ?>
                        <h5 class="mb-1"><?php echo date('d M Y', strtotime($kendaraan['masa_berlaku_pajak'])); ?></h5>
                        <?php $check = cekMasaBerlaku($kendaraan['masa_berlaku_pajak']); ?>
                        <small class="<?php echo $check['class']; ?>">
                          <i class="ti ti-alert-circle"></i> <?php echo $check['text']; ?>
                        </small>
                      <?php else: ?>
                        <p class="text-muted mb-0">Tidak ada data</p>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="border rounded p-3">
                      <h6 class="text-muted mb-2">Asuransi</h6>
                      <?php if (!empty($kendaraan['masa_berlaku_asuransi'])): ?>
                        <h5 class="mb-1"><?php echo date('d M Y', strtotime($kendaraan['masa_berlaku_asuransi'])); ?></h5>
                        <?php $check = cekMasaBerlaku($kendaraan['masa_berlaku_asuransi']); ?>
                        <small class="<?php echo $check['class']; ?>">
                          <i class="ti ti-alert-circle"></i> <?php echo $check['text']; ?>
                        </small>
                      <?php else: ?>
                        <p class="text-muted mb-0">Tidak ada data</p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- File Dokumen -->
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-files me-2"></i>Dokumen & File</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Dokumen STNK</label>
                    <?php if (!empty($kendaraan['dokumen_stnk']) && file_exists(dirname(__DIR__, 2) . '/' . $kendaraan['dokumen_stnk'])): ?>
                      <div class="border rounded p-3">
                        <i class="ti ti-file-text" style="font-size: 24px;"></i>
                        <p class="mb-2 mt-2"><?php echo basename($kendaraan['dokumen_stnk']); ?></p>
                        <a href="../../<?php echo htmlspecialchars($kendaraan['dokumen_stnk']); ?>" target="_blank" class="btn btn-sm btn-primary">
                          <i class="ti ti-eye me-1"></i>Lihat Dokumen
                        </a>
                      </div>
                    <?php else: ?>
                      <p class="text-muted">Tidak ada dokumen</p>
                    <?php endif; ?>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Dokumen BPKB</label>
                    <?php if (!empty($kendaraan['dokumen_bpkb']) && file_exists(dirname(__DIR__, 2) . '/' . $kendaraan['dokumen_bpkb'])): ?>
                      <div class="border rounded p-3">
                        <i class="ti ti-file-text" style="font-size: 24px;"></i>
                        <p class="mb-2 mt-2"><?php echo basename($kendaraan['dokumen_bpkb']); ?></p>
                        <a href="../../<?php echo htmlspecialchars($kendaraan['dokumen_bpkb']); ?>" target="_blank" class="btn btn-sm btn-primary">
                          <i class="ti ti-eye me-1"></i>Lihat Dokumen
                        </a>
                      </div>
                    <?php else: ?>
                      <p class="text-muted">Tidak ada dokumen</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <!-- Keterangan -->
            <?php if (!empty($kendaraan['keterangan'])): ?>
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-note me-2"></i>Keterangan</h5>
              </div>
              <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($kendaraan['keterangan'])); ?></p>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Print Styles -->
    <style>
      @media print {
        .page-header, .btn, .card-header .btn-group, nav, footer, .sidebar {
          display: none !important;
        }
        .card {
          border: 1px solid #ddd !important;
          box-shadow: none !important;
          page-break-inside: avoid;
        }
      }
    </style>

<?php
$koneksi->close();
include dirname(__DIR__, 2) . '/includes/footer.php';
?>
