<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$page_title = isset($page_title) ? $page_title : 'Dashboard';
$currentUserName = isset($_SESSION['nama']) && $_SESSION['nama'] !== ''
    ? $_SESSION['nama']
    : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Pengguna');
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'Pengguna';

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
$logoutPath = $rootPrefix . 'logout.php';

$adminPrefix = '';
if ($isAdminRoute) {
    $adminIndex = array_search('admin', $segments, true);
    $afterAdmin = array_slice($segments, $adminIndex + 1);
    $depthInsideAdmin = max(count($afterAdmin) - 1, 0);
    $adminPrefix = $depthInsideAdmin > 0 ? str_repeat('../', $depthInsideAdmin) : '';
} else {
    $adminPrefix = 'admin/';
}

$dashboardLink = $isAdminRoute ? $adminPrefix . 'dashboard.php' : 'index.php';
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <head>
    <title><?php echo htmlspecialchars($page_title); ?> | Sistem Monitoring dan Maintenance Kendaraan</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Dashboard Sistem Monitoring dan Maintenance Kendaraan PT Borneo Sarana Margasana." />
    <meta name="keywords" content="monitoring kendaraan, maintenance kendaraan, armada" />
    <meta name="author" content="PT Borneo Sarana Margasana" />
    <link rel="icon" href="<?php echo $assetBase; ?>assets/images/favicon.svg" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $assetBase; ?>assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="<?php echo $assetBase; ?>assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="<?php echo $assetBase; ?>assets/fonts/feather.css" />
    <link rel="stylesheet" href="<?php echo $assetBase; ?>assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="<?php echo $assetBase; ?>assets/fonts/material.css" />
    <link rel="stylesheet" href="<?php echo $assetBase; ?>assets/css/style.css" id="main-style-link" />
  </head>
  <body>
    <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
      <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
        <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
      </div>
    </div>
    <header class="pc-header">
      <div class="header-wrapper flex max-sm:px-[15px] px-[25px] grow">
        <div class="me-auto pc-mob-drp flex items-center gap-4">
          <a href="<?php echo $dashboardLink; ?>" class="hidden lg:flex items-center gap-3 font-semibold text-primary-600">
            <img src="<?php echo $assetBase; ?>assets/images/bsm.png" alt="PT Borneo Sarana Margasana" class="w-10 h-10 object-contain" />
            <span class="text-[18px] leading-tight">BSM Fleet Monitoring</span>
          </a>
          <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
            <li class="pc-h-item pc-sidebar-collapse max-lg:hidden lg:inline-flex">
              <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="sidebar-hide">
                <i data-feather="menu"></i>
              </a>
            </li>
            <li class="pc-h-item pc-sidebar-popup lg:hidden">
              <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="mobile-collapse">
                <i data-feather="menu"></i>
              </a>
            </li>
            <li class="dropdown pc-h-item">
              <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <i data-feather="search"></i>
              </a>
              <div class="dropdown-menu pc-h-dropdown drp-search">
                <form class="px-2 py-1">
                  <input type="search" class="form-control !border-0 !shadow-none" placeholder="Search here. . ." />
                </form>
              </div>
            </li>
          </ul>
        </div>
        <div class="ms-auto">
          <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
            <li class="dropdown pc-h-item">
              <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <i data-feather="sun"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                <a href="#!" class="dropdown-item" onclick="layout_change('dark')">
                  <i data-feather="moon"></i>
                  <span>Dark</span>
                </a>
                <a href="#!" class="dropdown-item" onclick="layout_change('light')">
                  <i data-feather="sun"></i>
                  <span>Light</span>
                </a>
                <a href="#!" class="dropdown-item" onclick="layout_change_default()">
                  <i data-feather="settings"></i>
                  <span>Default</span>
                </a>
              </div>
            </li>
            <li class="dropdown pc-h-item header-user-profile">
              <a class="pc-head-link dropdown-toggle arrow-none me-0" data-pc-toggle="dropdown" href="#" role="button" aria-haspopup="false" data-pc-auto-close="outside" aria-expanded="false">
                <img src="<?php echo $assetBase; ?>assets/images/bsm.png" alt="Logo PT BSM" class="w-9 h-9 rounded-full border border-primary-200" />
              </a>
              <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown p-2 overflow-hidden">
                <div class="dropdown-header flex items-center justify-between py-4 px-5 bg-primary-500">
                  <div class="flex mb-1 items-center">
                    <div class="shrink-0">
                      <img src="<?php echo $assetBase; ?>assets/images/bsm.png" alt="Logo PT BSM" class="w-10 h-10 rounded-full bg-white p-1" />
                    </div>
                    <div class="grow ms-3 text-white">
                      <h6 class="mb-0"><?php echo htmlspecialchars($currentUserName); ?></h6>
                      <span class="text-sm opacity-80"><?php echo htmlspecialchars($currentUserRole); ?></span>
                    </div>
                  </div>
                </div>
                <div class="dropdown-body py-4 px-5">
                  <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 225px)">
                    <div class="dropdown-item text-muted">
                      <i class="ti ti-user-check me-2"></i>
                      <span>Akses dashboard PT Borneo Sarana Margasana</span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo $logoutPath; ?>" class="dropdown-item text-danger-500">
                      <i class="ti ti-power me-2"></i>
                      <span>Logout</span>
                    </a>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </header>
