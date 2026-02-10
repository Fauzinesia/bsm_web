<?php
// Calculate path to assets based on file location
$currentDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
$projectRoot = str_replace('\\', '/', dirname(__DIR__)); // Go up from includes/ to bsm_web/
$relativePath = str_replace($projectRoot, '', $currentDir);
$relativePath = trim($relativePath, '/');
$segments = $relativePath === '' ? [] : explode('/', $relativePath);
$depth = count($segments);
$rootPrefix = $depth > 0 ? str_repeat('../', $depth) : './';
$isAdminRoute = in_array('admin', $segments, true);

$assetBase = $rootPrefix;

// Build robust base paths from project root for admin and user routes
$baseAdmin = $rootPrefix . 'admin/';
$baseUser  = $rootPrefix . 'user/';

// Initialize role early so dashboard link logic can use it safely
$userRole = isset($_SESSION['role']) ? trim((string)$_SESSION['role']) : 'User';
$allowedRoles = ['Admin','User'];
if (! in_array($userRole, $allowedRoles, true)) { $userRole = 'User'; }

// Calculate prefix to reach admin folder from current location
$adminPrefix = '';
if ($isAdminRoute) {
    $adminIndex = array_search('admin', $segments, true);
    $afterAdmin = array_slice($segments, $adminIndex + 1);
    $depthInsideAdmin = count($afterAdmin);
    $adminPrefix = $depthInsideAdmin > 0 ? str_repeat('../', $depthInsideAdmin) : '';
} else {
    $adminPrefix = 'admin/';
}

// Detect user route and compute prefixes
$isUserRoute = in_array('user', $segments, true);
$userPrefix = '';
if ($isUserRoute) {
    $userIndex = array_search('user', $segments, true);
    $afterUser = array_slice($segments, $userIndex + 1);
    $depthInsideUser = count($afterUser);
    $userPrefix = $depthInsideUser > 0 ? str_repeat('../', $depthInsideUser) : '';
} else {
    $userPrefix = 'user/';
}

// Dashboard link based on current route and role (use root-based paths)
if ($isAdminRoute) {
    $dashboardLink = $baseAdmin . 'dashboard.php';
} elseif ($isUserRoute) {
    $dashboardLink = $baseUser . 'dashboard.php';
} else {
    $dashboardLink = (($userRole === 'Admin') ? $baseAdmin : $baseUser) . 'dashboard.php';
}

// Role-based menu: read from session and config
$userRole = isset($_SESSION['role']) ? trim((string)$_SESSION['role']) : 'User';
$allowedRoles = ['Admin','User'];
if (! in_array($userRole, $allowedRoles, true)) { $userRole = 'User'; }

$roleConfigPath = dirname(__DIR__) . '/config/role_config.json';
$defaultRoleConfig = [
  'roles' => [
    'Admin' => ['dashboard','kendaraan','pengguna','penggunaan','maintenance','operasional'],
    'User' => ['dashboard','penggunaan']
  ]
];
$roleConfig = $defaultRoleConfig;
if (file_exists($roleConfigPath)) {
  $json = file_get_contents($roleConfigPath);
  $decoded = json_decode($json, true);
  if (is_array($decoded) && isset($decoded['roles'])) { $roleConfig = $decoded; }
}
function role_can_sidebar(string $module, string $userRole, array $roleConfig): bool {
  if ($userRole === 'Admin') return true;
  $roles = $roleConfig['roles'] ?? [];
  $allowed = $roles[$userRole] ?? [];
  return in_array($module, $allowed, true);
}
?>
<nav class="pc-sidebar" aria-label="Navigasi Samping">
  <div class="navbar-wrapper">
    <div class="m-header flex items-center py-4 px-6 h-header-height">
      <a href="<?php echo $dashboardLink; ?>" class="b-brand flex items-center gap-3">
        <img src="<?php echo $assetBase; ?>assets/images/bsm.png" class="img-fluid logo logo-lg" alt="PT Borneo Sarana Margasana" />
        <img src="<?php echo $assetBase; ?>assets/images/bsm.png" class="img-fluid logo logo-sm" alt="PT Borneo Sarana Margasana" />
      </a>
    </div>
    <div class="navbar-content h-[calc(100vh_-_74px)] py-2.5">
      <ul class="pc-navbar" role="menubar">
        <li class="pc-item pc-caption">
          <label>Navigation</label>
        </li>
        <li class="pc-item">
          <a href="<?php echo $dashboardLink; ?>" class="pc-link">
            <span class="pc-micon">
              <i data-feather="home"></i>
            </span>
            <span class="pc-mtext">Dashboard</span>
          </a>
        </li>
        <li class="pc-item pc-caption">
          <label>User Menu (<?php echo htmlspecialchars($userRole); ?>)</label>
          <i data-feather="user"></i>
        </li>
        <li class="pc-item pc-caption">
          <label>Data Master</label>
          <i data-feather="database"></i>
        </li>
        <?php if (role_can_sidebar('kendaraan', $userRole, $roleConfig)): ?>
        <li class="pc-item">
          <a href="<?php echo $isUserRoute ? $baseUser : $baseAdmin; ?>kendaraan/kendaraan.php" class="pc-link" aria-label="Data Kendaraan">
            <span class="pc-micon">
              <i data-feather="truck" aria-hidden="true"></i>
            </span>
            <span class="pc-mtext">Data Kendaraan</span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (role_can_sidebar('pengguna', $userRole, $roleConfig)): ?>
        <li class="pc-item">
          <a href="<?php echo $isUserRoute ? $baseUser : $baseAdmin; ?>pengguna/pengguna.php" class="pc-link" aria-label="Data Pengguna">
            <span class="pc-micon">
              <i data-feather="users" aria-hidden="true"></i>
            </span>
            <span class="pc-mtext">Data Pengguna</span>
          </a>
        </li>
        <?php endif; ?>
        <li class="pc-item pc-caption">
          <label>Operasional</label>
          <i data-feather="activity"></i>
        </li>
        <?php if (role_can_sidebar('penggunaan', $userRole, $roleConfig)): ?>
        <li class="pc-item">
          <a href="<?php echo $isUserRoute ? $baseUser : $baseAdmin; ?>penggunaan/penggunaan.php" class="pc-link" aria-label="Penggunaan Kendaraan">
            <span class="pc-micon">
              <i data-feather="clipboard" aria-hidden="true"></i>
            </span>
            <span class="pc-mtext">Penggunaan Kendaraan</span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (role_can_sidebar('maintenance', $userRole, $roleConfig)): ?>
        <li class="pc-item">
          <a href="<?php echo $isUserRoute ? $baseUser : $baseAdmin; ?>maintenance/maintenance.php" class="pc-link" aria-label="Maintenance Kendaraan">
            <span class="pc-micon">
              <i data-feather="tool" aria-hidden="true"></i>
            </span>
            <span class="pc-mtext">Maintenance Kendaraan</span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (role_can_sidebar('inspeksi', $userRole, $roleConfig)): ?>
        <li class="pc-item">
          <a href="<?php echo $isUserRoute ? $baseUser : $baseAdmin; ?>inspeksi/inspeksi.php" class="pc-link" aria-label="Inspeksi Kendaraan">
            <span class="pc-micon">
              <i data-feather="check-circle" aria-hidden="true"></i>
            </span>
            <span class="pc-mtext">Inspeksi Kendaraan</span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (role_can_sidebar('operasional', $userRole, $roleConfig)): ?>
        <li class="pc-item">
          <a href="<?php echo $isUserRoute ? $baseUser : $baseAdmin; ?>operasional/operasional.php" class="pc-link" aria-label="Biaya Operasional">
            <span class="pc-micon">
              <i data-feather="dollar-sign" aria-hidden="true"></i>
            </span>
            <span class="pc-mtext">Biaya Operasional</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
