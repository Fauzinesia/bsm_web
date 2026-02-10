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

$page_title = 'Edit Kendaraan';
include dirname(__DIR__, 2) . '/includes/header.php';
include dirname(__DIR__, 2) . '/includes/sidebar.php';

// Error handling
$errorMessage = '';
$errorType = $_GET['error'] ?? '';
switch ($errorType) {
    case 'required':
        $errorMessage = 'Nomor polisi dan nama kendaraan wajib diisi!';
        break;
    case 'database':
        $errorMessage = 'Gagal mengupdate data ke database. Silakan coba lagi.';
        break;
    case 'prepare':
        $errorMessage = 'Terjadi kesalahan pada sistem. Silakan hubungi administrator.';
        break;
    case 'file_upload':
        $errorMessage = 'Gagal mengupload file. Pastikan file sesuai format dan ukuran.';
        break;
}

// Success handling
$successMessage = '';
if (isset($_GET['status']) && $_GET['status'] === 'updated') {
    $successMessage = 'Data kendaraan berhasil diupdate!';
}
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Edit Data Kendaraan</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item"><a href="kendaraan.php">Data Kendaraan</a></li>
              <li class="breadcrumb-item" aria-current="page">Edit</li>
            </ul>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h5>Informasi Kendaraan</h5>
              </div>
              <div class="card-body">
                <?php if ($errorMessage !== ''): ?>
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ti ti-alert-circle me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
                <?php endif; ?>
                <?php if ($successMessage !== ''): ?>
                  <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ti ti-check me-2"></i><?php echo htmlspecialchars($successMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
                <?php endif; ?>
                <form action="proses_edit.php" method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="id_kendaraan" value="<?php echo htmlspecialchars($kendaraan['id_kendaraan']); ?>">
                  <input type="hidden" name="foto_kendaraan_lama" value="<?php echo htmlspecialchars($kendaraan['foto_kendaraan'] ?? ''); ?>">
                  <input type="hidden" name="dokumen_stnk_lama" value="<?php echo htmlspecialchars($kendaraan['dokumen_stnk'] ?? ''); ?>">
                  <input type="hidden" name="dokumen_bpkb_lama" value="<?php echo htmlspecialchars($kendaraan['dokumen_bpkb'] ?? ''); ?>">
                  
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label" for="nomor_polisi">Nomor Polisi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nomor_polisi" name="nomor_polisi" placeholder="Contoh: B 1234 XYZ" required maxlength="15" value="<?php echo htmlspecialchars($kendaraan['nomor_polisi']); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label" for="nama_kendaraan">Nama Kendaraan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_kendaraan" name="nama_kendaraan" placeholder="Contoh: Avanza Veloz" required maxlength="100" value="<?php echo htmlspecialchars($kendaraan['nama_kendaraan']); ?>">
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="merk">Merk</label>
                        <input type="text" class="form-control" id="merk" name="merk" placeholder="Contoh: Toyota" maxlength="100" value="<?php echo htmlspecialchars($kendaraan['merk'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="tipe">Tipe</label>
                        <input type="text" class="form-control" id="tipe" name="tipe" placeholder="Contoh: 1.5 G MT" maxlength="100" value="<?php echo htmlspecialchars($kendaraan['tipe'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="tahun">Tahun</label>
                        <input type="number" class="form-control" id="tahun" name="tahun" placeholder="Contoh: 2023" min="1900" max="2100" value="<?php echo htmlspecialchars($kendaraan['tahun'] ?? ''); ?>">
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label" for="warna">Warna</label>
                        <input type="text" class="form-control" id="warna" name="warna" placeholder="Contoh: Putih" maxlength="50" value="<?php echo htmlspecialchars($kendaraan['warna'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label" for="status_operasional">Status Operasional</label>
                        <select class="form-select" id="status_operasional" name="status_operasional">
                          <option value="Aktif" <?php echo ($kendaraan['status_operasional'] ?? 'Aktif') === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                          <option value="Perawatan" <?php echo ($kendaraan['status_operasional'] ?? '') === 'Perawatan' ? 'selected' : ''; ?>>Perawatan</option>
                          <option value="Rusak" <?php echo ($kendaraan['status_operasional'] ?? '') === 'Rusak' ? 'selected' : ''; ?>>Rusak</option>
                          <option value="Disewa" <?php echo ($kendaraan['status_operasional'] ?? '') === 'Disewa' ? 'selected' : ''; ?>>Disewa</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <hr class="my-4">
                  <h5 class="mb-3">Dokumen Kendaraan</h5>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="nomor_rangka">Nomor Rangka</label>
                        <input type="text" class="form-control" id="nomor_rangka" name="nomor_rangka" placeholder="Nomor Rangka" maxlength="50" value="<?php echo htmlspecialchars($kendaraan['nomor_rangka'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="nomor_mesin">Nomor Mesin</label>
                        <input type="text" class="form-control" id="nomor_mesin" name="nomor_mesin" placeholder="Nomor Mesin" maxlength="50" value="<?php echo htmlspecialchars($kendaraan['nomor_mesin'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="nomor_bpkb">Nomor BPKB</label>
                        <input type="text" class="form-control" id="nomor_bpkb" name="nomor_bpkb" placeholder="Nomor BPKB" maxlength="50" value="<?php echo htmlspecialchars($kendaraan['nomor_bpkb'] ?? ''); ?>">
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="masa_berlaku_stnk">Masa Berlaku STNK</label>
                        <input type="date" class="form-control" id="masa_berlaku_stnk" name="masa_berlaku_stnk" value="<?php echo htmlspecialchars($kendaraan['masa_berlaku_stnk'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="masa_berlaku_pajak">Masa Berlaku Pajak</label>
                        <input type="date" class="form-control" id="masa_berlaku_pajak" name="masa_berlaku_pajak" value="<?php echo htmlspecialchars($kendaraan['masa_berlaku_pajak'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="masa_berlaku_asuransi">Masa Berlaku Asuransi</label>
                        <input type="date" class="form-control" id="masa_berlaku_asuransi" name="masa_berlaku_asuransi" value="<?php echo htmlspecialchars($kendaraan['masa_berlaku_asuransi'] ?? ''); ?>">
                      </div>
                    </div>
                  </div>

                  <hr class="my-4">
                  <h5 class="mb-3">Upload Dokumen & Foto</h5>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="foto_kendaraan">Foto Kendaraan</label>
                        <?php if (!empty($kendaraan['foto_kendaraan'])): ?>
                          <div class="mb-2">
                            <img src="../../<?php echo htmlspecialchars($kendaraan['foto_kendaraan']); ?>" alt="Foto Kendaraan" class="img-thumbnail" style="max-width: 200px;">
                            <p class="text-muted small mt-1">File saat ini: <?php echo basename($kendaraan['foto_kendaraan']); ?></p>
                          </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="foto_kendaraan" name="foto_kendaraan" accept="image/*">
                        <small class="text-muted">Format: JPG, PNG, max 2MB (Kosongkan jika tidak ingin mengubah)</small>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="dokumen_stnk">Dokumen STNK</label>
                        <?php if (!empty($kendaraan['dokumen_stnk'])): ?>
                          <div class="mb-2">
                            <a href="../../<?php echo htmlspecialchars($kendaraan['dokumen_stnk']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                              <i class="ti ti-file me-1"></i>Lihat Dokumen
                            </a>
                            <p class="text-muted small mt-1">File saat ini: <?php echo basename($kendaraan['dokumen_stnk']); ?></p>
                          </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="dokumen_stnk" name="dokumen_stnk" accept="image/*,.pdf">
                        <small class="text-muted">Format: JPG, PNG, PDF, max 2MB (Kosongkan jika tidak ingin mengubah)</small>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="dokumen_bpkb">Dokumen BPKB</label>
                        <?php if (!empty($kendaraan['dokumen_bpkb'])): ?>
                          <div class="mb-2">
                            <a href="../../<?php echo htmlspecialchars($kendaraan['dokumen_bpkb']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                              <i class="ti ti-file me-1"></i>Lihat Dokumen
                            </a>
                            <p class="text-muted small mt-1">File saat ini: <?php echo basename($kendaraan['dokumen_bpkb']); ?></p>
                          </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="dokumen_bpkb" name="dokumen_bpkb" accept="image/*,.pdf">
                        <small class="text-muted">Format: JPG, PNG, PDF, max 2MB (Kosongkan jika tidak ingin mengubah)</small>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <div class="mb-3">
                        <label class="form-label" for="keterangan">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Keterangan tambahan tentang kendaraan..."><?php echo htmlspecialchars($kendaraan['keterangan'] ?? ''); ?></textarea>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <hr class="my-4">
                      <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-2"></i>Update Data
                      </button>
                      <a href="kendaraan.php" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-2"></i>Kembali
                      </a>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php
$koneksi->close();
include dirname(__DIR__, 2) . '/includes/footer.php';
?>
