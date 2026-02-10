<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
$page_title = 'Tambah Kendaraan';
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
        $errorMessage = 'Gagal menyimpan data ke database. Silakan coba lagi.';
        break;
    case 'prepare':
        $errorMessage = 'Terjadi kesalahan pada sistem. Silakan hubungi administrator.';
        break;
}
?>
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Tambah Data Kendaraan</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item"><a href="kendaraan.php">Data Kendaraan</a></li>
              <li class="breadcrumb-item" aria-current="page">Tambah</li>
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
                <form action="proses_tambah.php" method="POST" enctype="multipart/form-data">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label" for="nomor_polisi">Nomor Polisi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nomor_polisi" name="nomor_polisi" placeholder="Contoh: B 1234 XYZ" required maxlength="15">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label" for="nama_kendaraan">Nama Kendaraan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_kendaraan" name="nama_kendaraan" placeholder="Contoh: Avanza Veloz" required maxlength="100">
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="merk">Merk</label>
                        <input type="text" class="form-control" id="merk" name="merk" placeholder="Contoh: Toyota" maxlength="100">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="tipe">Tipe</label>
                        <input type="text" class="form-control" id="tipe" name="tipe" placeholder="Contoh: 1.5 G MT" maxlength="100">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="tahun">Tahun</label>
                        <input type="number" class="form-control" id="tahun" name="tahun" placeholder="Contoh: 2023" min="1900" max="2100">
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label" for="warna">Warna</label>
                        <input type="text" class="form-control" id="warna" name="warna" placeholder="Contoh: Putih" maxlength="50">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label" for="status_operasional">Status Operasional</label>
                        <select class="form-select" id="status_operasional" name="status_operasional">
                          <option value="Aktif" selected>Aktif</option>
                          <option value="Perawatan">Perawatan</option>
                          <option value="Rusak">Rusak</option>
                          <option value="Disewa">Disewa</option>
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
                        <input type="text" class="form-control" id="nomor_rangka" name="nomor_rangka" placeholder="Nomor Rangka" maxlength="50">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="nomor_mesin">Nomor Mesin</label>
                        <input type="text" class="form-control" id="nomor_mesin" name="nomor_mesin" placeholder="Nomor Mesin" maxlength="50">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="nomor_bpkb">Nomor BPKB</label>
                        <input type="text" class="form-control" id="nomor_bpkb" name="nomor_bpkb" placeholder="Nomor BPKB" maxlength="50">
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="masa_berlaku_stnk">Masa Berlaku STNK</label>
                        <input type="date" class="form-control" id="masa_berlaku_stnk" name="masa_berlaku_stnk">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="masa_berlaku_pajak">Masa Berlaku Pajak</label>
                        <input type="date" class="form-control" id="masa_berlaku_pajak" name="masa_berlaku_pajak">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="masa_berlaku_asuransi">Masa Berlaku Asuransi</label>
                        <input type="date" class="form-control" id="masa_berlaku_asuransi" name="masa_berlaku_asuransi">
                      </div>
                    </div>
                  </div>

                  <hr class="my-4">
                  <h5 class="mb-3">Upload Dokumen & Foto</h5>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="foto_kendaraan">Foto Kendaraan</label>
                        <input type="file" class="form-control" id="foto_kendaraan" name="foto_kendaraan" accept="image/*">
                        <small class="text-muted">Format: JPG, PNG, max 2MB</small>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="dokumen_stnk">Dokumen STNK</label>
                        <input type="file" class="form-control" id="dokumen_stnk" name="dokumen_stnk" accept="image/*,.pdf">
                        <small class="text-muted">Format: JPG, PNG, PDF, max 2MB</small>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label class="form-label" for="dokumen_bpkb">Dokumen BPKB</label>
                        <input type="file" class="form-control" id="dokumen_bpkb" name="dokumen_bpkb" accept="image/*,.pdf">
                        <small class="text-muted">Format: JPG, PNG, PDF, max 2MB</small>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <div class="mb-3">
                        <label class="form-label" for="keterangan">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Keterangan tambahan tentang kendaraan..."></textarea>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <hr class="my-4">
                      <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-2"></i>Simpan Data
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
<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
