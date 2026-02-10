<?php
session_start();

// Pastikan pengguna sudah login
if (! isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/koneksi.php';

// ===== Deteksi role & izin akses =====
$userRole = isset($_SESSION['role']) ? trim((string)$_SESSION['role']) : 'User';
$allowedRoles = ['Admin', 'User'];
if (! in_array($userRole, $allowedRoles, true)) {
    $userRole = 'User';
}

// Jika Admin mencoba mengakses dashboard user, arahkan ke dashboard admin
if ($userRole === 'Admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}

// Muat konfigurasi role dari JSON (fallback ke default jika tidak ada)
$roleConfigPath = dirname(__DIR__) . '/config/role_config.json';
$defaultRoleConfig = [
    'roles' => [
        'Admin' => ['dashboard','kendaraan','pengguna','penggunaan','maintenance','operasional'],
        'User'  => ['dashboard','penggunaan']
    ]
];
$roleConfig = $defaultRoleConfig;
if (file_exists($roleConfigPath)) {
    $json = file_get_contents($roleConfigPath);
    $decoded = json_decode($json, true);
    if (is_array($decoded) && isset($decoded['roles'])) {
        $roleConfig = $decoded;
    }
}

function role_can(string $module, string $userRole, array $roleConfig): bool {
    if ($userRole === 'Admin') return true; // Admin akses penuh (meski sudah di-redirect di atas)
    $roles = $roleConfig['roles'] ?? [];
    $allowed = $roles[$userRole] ?? [];
    return in_array($module, $allowed, true);
}

$page_title = 'Dashboard';
include '../includes/header.php';
include '../includes/sidebar.php';

// ===== Query hanya untuk modul yang diizinkan =====
$totalKendaraan = 0;
if (role_can('kendaraan', $userRole, $roleConfig)) {
    if ($result = $koneksi->query('SELECT COUNT(*) AS total FROM tb_kendaraan')) {
        $row = $result->fetch_assoc();
        $totalKendaraan = (int) $row['total'];
        $result->free();
    }
}

$kendaraanAktif = 0;
if (role_can('kendaraan', $userRole, $roleConfig)) {
    if ($result = $koneksi->query("SELECT COUNT(*) AS total FROM tb_kendaraan WHERE status_operasional = 'Aktif'")) {
        $row = $result->fetch_assoc();
        $kendaraanAktif = (int) $row['total'];
        $result->free();
    }
}

$penggunaAktif = 0;
if (role_can('pengguna', $userRole, $roleConfig)) {
    if ($result = $koneksi->query("SELECT COUNT(*) AS total FROM tb_pengguna WHERE status = 'Aktif'")) {
        $row = $result->fetch_assoc();
        $penggunaAktif = (int) $row['total'];
        $result->free();
    }
}

$penggunaanBerjalan = 0;
if (role_can('penggunaan', $userRole, $roleConfig)) {
    if ($result = $koneksi->query("SELECT COUNT(*) AS total FROM tb_penggunaan WHERE status = 'Berjalan'")) {
        $row = $result->fetch_assoc();
        $penggunaanBerjalan = (int) $row['total'];
        $result->free();
    }
}

$maintenanceTerjadwal = 0;
if (role_can('maintenance', $userRole, $roleConfig)) {
    if ($result = $koneksi->query("SELECT COUNT(*) AS total FROM tb_maintenance WHERE status = 'Dijadwalkan'")) {
        $row = $result->fetch_assoc();
        $maintenanceTerjadwal = (int) $row['total'];
        $result->free();
    }
}

$maintenanceProses = 0;
if (role_can('maintenance', $userRole, $roleConfig)) {
    if ($result = $koneksi->query("SELECT COUNT(*) AS total FROM tb_maintenance WHERE status = 'Proses'")) {
        $row = $result->fetch_assoc();
        $maintenanceProses = (int) $row['total'];
        $result->free();
    }
}

$biayaOperasionalBulan = 0.0;
if (role_can('operasional', $userRole, $roleConfig)) {
    $sqlBiaya = "SELECT COALESCE(SUM(nominal), 0) AS total FROM tb_biaya_operasional WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())";
    if ($result = $koneksi->query($sqlBiaya)) {
        $row = $result->fetch_assoc();
        $biayaOperasionalBulan = (float) $row['total'];
        $result->free();
    }
}

$maintenanceUpcoming = [];
if (role_can('maintenance', $userRole, $roleConfig)) {
    $sqlMaintenance = "SELECT m.tanggal_service, m.jenis_service, m.status, k.nama_kendaraan, k.nomor_polisi FROM tb_maintenance m LEFT JOIN tb_kendaraan k ON k.id_kendaraan = m.id_kendaraan ORDER BY m.tanggal_service ASC LIMIT 5";
    if ($result = $koneksi->query($sqlMaintenance)) {
        while ($row = $result->fetch_assoc()) {
            $maintenanceUpcoming[] = $row;
        }
        $result->free();
    }
}

$penggunaanTerbaru = [];
if (role_can('penggunaan', $userRole, $roleConfig)) {
    $sqlPenggunaan = "SELECT p.tanggal_mulai, p.tanggal_selesai, p.keperluan, p.status, k.nama_kendaraan, k.nomor_polisi, u.nama AS nama_pengguna FROM tb_penggunaan p LEFT JOIN tb_kendaraan k ON k.id_kendaraan = p.id_kendaraan LEFT JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna ORDER BY p.tanggal_mulai DESC LIMIT 5";
    if ($result = $koneksi->query($sqlPenggunaan)) {
        while ($row = $result->fetch_assoc()) {
            $penggunaanTerbaru[] = $row;
        }
        $result->free();
    }
}

$penggunaTerbaru = [];
if (role_can('pengguna', $userRole, $roleConfig)) {
    $sqlPengguna = "SELECT nama, username, role, status, created_at FROM tb_pengguna ORDER BY created_at DESC LIMIT 5";
    if ($result = $koneksi->query($sqlPengguna)) {
        while ($row = $result->fetch_assoc()) {
            $penggunaTerbaru[] = $row;
        }
        $result->free();
    }
}
?>
    <div class="pc-container" aria-label="Halaman Dashboard User">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Ringkasan Armada & Operasional</h5>
              <p class="text-muted text-sm mt-1" aria-live="polite">Role: <?php echo htmlspecialchars($userRole); ?></p>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item" aria-current="page">Ringkasan</li>
            </ul>
          </div>
        </div>
        <div class="grid grid-cols-12 gap-6" aria-label="Kartu Ringkasan">
          <?php if (role_can('kendaraan', $userRole, $roleConfig)): ?>
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Total Kendaraan</p>
                    <h3 class="mb-0"><?php echo number_format($totalKendaraan); ?></h3>
                  </div>
                  <span class="rounded-full bg-primary-100 text-primary-600 p-3">
                    <i data-feather="truck" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Aktif: <?php echo number_format($kendaraanAktif); ?> unit</p>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <?php if (role_can('penggunaan', $userRole, $roleConfig)): ?>
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Penggunaan Berjalan</p>
                    <h3 class="mb-0"><?php echo number_format($penggunaanBerjalan); ?></h3>
                  </div>
                  <span class="rounded-full bg-success-100 text-success-600 p-3">
                    <i data-feather="clipboard" class="w-6 h-6"></i>
                  </span>
                </div>
                <?php if (role_can('pengguna', $userRole, $roleConfig)): ?>
                <p class="text-sm text-muted mt-3">Pengguna aktif: <?php echo number_format($penggunaAktif); ?> orang</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <?php if (role_can('maintenance', $userRole, $roleConfig)): ?>
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Maintenance Terjadwal</p>
                    <h3 class="mb-0"><?php echo number_format($maintenanceTerjadwal); ?></h3>
                  </div>
                  <span class="rounded-full bg-warning-100 text-warning-600 p-3">
                    <i data-feather="tool" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Sedang proses: <?php echo number_format($maintenanceProses); ?></p>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <?php if (role_can('operasional', $userRole, $roleConfig)): ?>
          <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-muted mb-1">Biaya Operasional Bulan Ini</p>
                    <h3 class="mb-0">Rp <?php echo number_format($biayaOperasionalBulan, 0, ',', '.'); ?></h3>
                  </div>
                  <span class="rounded-full bg-danger-100 text-danger-600 p-3">
                    <i data-feather="dollar-sign" class="w-6 h-6"></i>
                  </span>
                </div>
                <p class="text-sm text-muted mt-3">Pembukuan otomatis dari transaksi BBM, tol, dan lainnya.</p>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <?php if (role_can('maintenance', $userRole, $roleConfig)): ?>
          <div class="col-span-12 xl:col-span-6">
            <div class="card table-card">
              <div class="card-header">
                <h5>Jadwal Maintenance Terdekat</h5>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Kendaraan</th>
                        <th>Jenis</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($maintenanceUpcoming) === 0): ?>
                        <tr>
                          <td colspan="4" class="text-center text-muted">Belum ada data maintenance yang dijadwalkan.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($maintenanceUpcoming as $item): ?>
                          <tr>
                            <td>
                              <strong><?php echo htmlspecialchars($item['nama_kendaraan'] ?? '-'); ?></strong>
                              <div class="text-muted text-sm"><?php echo htmlspecialchars($item['nomor_polisi'] ?? '-'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($item['jenis_service'] ?? '-'); ?></td>
                            <td><?php echo $item['tanggal_service'] ? date('d M Y', strtotime($item['tanggal_service'])) : '-'; ?></td>
                            <td>
                              <span class="badge <?php echo ($item['status'] === 'Selesai') ? 'bg-success-500' : (($item['status'] === 'Proses') ? 'bg-warning-500' : 'bg-primary-500'); ?> text-white">
                                <?php echo htmlspecialchars($item['status'] ?? '-'); ?>
                              </span>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <?php if (role_can('penggunaan', $userRole, $roleConfig)): ?>
          <div class="col-span-12 xl:col-span-6">
            <div class="card table-card">
              <div class="card-header">
                <h5>Aktivitas Penggunaan Kendaraan</h5>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Kendaraan</th>
                        <th>Pengguna</th>
                        <th>Periode</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($penggunaanTerbaru) === 0): ?>
                        <tr>
                          <td colspan="4" class="text-center text-muted">Belum ada catatan penggunaan.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($penggunaanTerbaru as $item): ?>
                          <tr>
                            <td>
                              <strong><?php echo htmlspecialchars($item['nama_kendaraan'] ?? '-'); ?></strong>
                              <div class="text-muted text-sm"><?php echo htmlspecialchars($item['nomor_polisi'] ?? '-'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($item['nama_pengguna'] ?? '-'); ?></td>
                            <td>
                              <?php
                                $mulai = $item['tanggal_mulai'] ? date('d M Y', strtotime($item['tanggal_mulai'])) : '-';
                                $selesai = $item['tanggal_selesai'] ? date('d M Y', strtotime($item['tanggal_selesai'])) : '-';
                                echo $mulai . ' - ' . $selesai;
                              ?>
                            </td>
                            <td>
                              <span class="badge <?php echo ($item['status'] === 'Selesai') ? 'bg-success-500' : 'bg-primary-500'; ?> text-white">
                                <?php echo htmlspecialchars($item['status'] ?? '-'); ?>
                              </span>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if (role_can('pengguna', $userRole, $roleConfig)): ?>
          <div class="col-span-12">
            <div class="card table-card">
              <div class="card-header flex justify-between items-center">
                <h5>Pengguna Sistem Terbaru</h5>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($penggunaTerbaru) === 0): ?>
                        <tr>
                          <td colspan="5" class="text-center text-muted">Belum ada data pengguna.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($penggunaTerbaru as $user): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                              <span class="badge bg-primary-500 text-white"><?php echo htmlspecialchars($user['role']); ?></span>
                            </td>
                            <td>
                              <span class="badge <?php echo ($user['status'] === 'Aktif') ? 'bg-success-500' : 'bg-danger-500'; ?> text-white">
                                <?php echo htmlspecialchars($user['status']); ?>
                              </span>
                            </td>
                            <td><?php echo $user['created_at'] ? date('d M Y H:i', strtotime($user['created_at'])) : '-'; ?></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

<?php include '../includes/footer.php'; ?>