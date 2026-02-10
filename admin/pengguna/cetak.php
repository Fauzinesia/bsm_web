<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }

// Filter parameters (samakan pola dengan admin/kendaraan/cetak.php)
$filterRole   = $_GET['filter_role'] ?? 'all';
$filterStatus = $_GET['filter_status'] ?? 'all';
$filterSearch = $_GET['search'] ?? '';

// Build SQL query with filters (mirip gaya kendaraan)
$sql = 'SELECT id_pengguna, nama, username, role, status, created_at FROM tb_pengguna WHERE 1=1';
$params = [];
$types = '';

if ($filterRole !== 'all') { $sql .= ' AND role = ?'; $params[] = $filterRole; $types .= 's'; }
if ($filterStatus !== 'all') { $sql .= ' AND status = ?'; $params[] = $filterStatus; $types .= 's'; }
if ($filterSearch !== '') {
    $sql .= ' AND (nama LIKE ? OR username LIKE ?)';
    $like = '%' . $filterSearch . '%';
    array_push($params, $like, $like);
    $types .= 'ss';
}

$sql .= ' ORDER BY created_at DESC, id_pengguna DESC';

$rows = [];
if (! empty($params)) {
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $rows[] = $row; }
        $stmt->close();
    }
} else {
    if ($result = $koneksi->query($sql)) {
        while ($row = $result->fetch_assoc()) { $rows[] = $row; }
        $result->free();
    }
}

// Ringkasan
$totalPengguna = count($rows);
$roleCounts = [ 'Admin' => 0, 'User' => 0 ];
$statusCounts = [ 'Aktif' => 0, 'Nonaktif' => 0 ];
foreach ($rows as $r) {
    $rRole = $r['role'] ?? 'User';
    $rStatus = $r['status'] ?? 'Aktif';
    if (isset($roleCounts[$rRole])) { $roleCounts[$rRole]++; }
    if (isset($statusCounts[$rStatus])) { $statusCounts[$rStatus]++; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Data Pengguna - PT BORNEO MARGASARANA MANDIRI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 12px; line-height: 1.6; padding: 20px; background: #fff; }

        /* Header Kop Surat */
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
        .kop-surat .logo { flex-shrink: 0; }
        .kop-surat .logo img { width: 80px; height: 80px; object-fit: contain; }
        .kop-surat .info { flex-grow: 1; text-align: center; }
        .kop-surat .info h1 { font-size: 20px; font-weight: bold; color: #000; margin-bottom: 5px; text-transform: uppercase; }
        .kop-surat .info h2 { font-size: 16px; font-weight: normal; color: #333; margin-bottom: 3px; }
        .kop-surat .info p { font-size: 11px; color: #555; line-height: 1.4; }

        /* Judul Laporan */
        .judul-laporan { text-align: center; margin: 20px 0; }
        .judul-laporan h3 { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .judul-laporan p { font-size: 11px; color: #666; }

        /* Info Cetak */
        .info-cetak { margin-bottom: 15px; font-size: 11px; color: #666; }

        /* Tabel */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table thead { background-color: #f5f5f5; }
        table thead th { border: 1px solid #000; padding: 8px 5px; text-align: left; font-weight: bold; font-size: 11px; }
        table tbody td { border: 1px solid #ddd; padding: 6px 5px; font-size: 11px; }
        table tbody tr:nth-child(even) { background-color: #f9f9f9; }

        /* Footer */
        .footer { margin-top: 30px; display: flex; justify-content: space-between; }
        .footer .ttd { text-align: center; width: 200px; }
        .footer .ttd .nama { margin-top: 60px; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; }

        /* Print Styles */
        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            @page { margin: 1cm; }
        }

        /* Button Print */
        .btn-print { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .btn-print:hover { background-color: #0056b3; }

        /* Filter Panel */
        .filter-panel { background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .filter-panel h4 { margin-bottom: 15px; color: #333; font-size: 16px; }
        .filter-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px; color: #555; }
        .filter-group select, .filter-group input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        .filter-group select:focus, .filter-group input:focus { outline: none; border-color: #007bff; }
        .filter-buttons { display: flex; gap: 10px; }
        .btn { padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.3s; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-back { background-color: #28a745; color: white; }
        .btn-back:hover { background-color: #218838; }
    </style>
</head>
<body>
    <!-- Filter Panel (tidak ikut cetak) -->
    <div class="filter-panel no-print">
        <h4>📊 Filter Data Pengguna</h4>
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter_role">Role:</label>
                    <select name="filter_role" id="filter_role">
                        <option value="all" <?php echo $filterRole === 'all' ? 'selected' : ''; ?>>Semua Role</option>
                        <option value="Admin" <?php echo $filterRole === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="User" <?php echo $filterRole === 'User' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter_status">Status:</label>
                    <select name="filter_status" id="filter_status">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="Aktif" <?php echo $filterStatus === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Nonaktif" <?php echo $filterStatus === 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="search">Pencarian:</label>
                    <input type="text" name="search" id="search" placeholder="Cari Nama/Username..." value="<?php echo e($filterSearch); ?>">
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">🔍 Terapkan Filter</button>
                    <a href="cetak.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block; line-height: 1.5;">🔄 Reset</a>
                    <a href="pengguna.php" class="btn btn-back" style="text-decoration: none; display: inline-block; line-height: 1.5;">⬅️ Kembali</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tombol Print -->
    <button onclick="window.print()" class="btn-print no-print">🖨️ Cetak / Print</button>

    <!-- Kop Surat -->
    <div class="kop-surat">
        <div class="logo">
            <img src="../../assets/images/bsm.png" alt="Logo BSM">
        </div>
        <div class="info">
            <h1>PT BORNEO MARGASARANA MANDIRI</h1>
            <h2>Sistem Manajemen Kendaraan</h2>
            <p>
                Jl.Raya Provinsi Km 191 Desa Sumber Baru angsana Tanah Bumbu<br>
                Telp: +62 822-7336-9909 | Email: pt.borneo.sarana.margasana@gmail.com
            </p>
        </div>
    </div>

    <!-- Judul Laporan -->
    <div class="judul-laporan">
        <h3>Laporan Data Pengguna</h3>
        <p>
            <?php 
            if ($filterRole !== 'all') { echo "Role: " . e($filterRole) . " | "; }
            if ($filterStatus !== 'all') { echo "Status: " . e($filterStatus) . " | "; }
            if ($filterSearch !== '') { echo "Pencarian: " . e($filterSearch) . " | "; }
            echo "Total: " . count($rows) . " akun";
            ?>
        </p>
    </div>

    <!-- Info Cetak -->
    <div class="info-cetak">
        <p>Dicetak pada: <?php echo date('d F Y, H:i'); ?> WIB | Dicetak oleh: <?php echo e($_SESSION['nama'] ?? 'Admin'); ?></p>
    </div>

    <!-- Tabel Data -->
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="22%">Nama</th>
                <th width="20%">Username</th>
                <th width="15%">Role</th>
                <th width="12%">Status</th>
                <th width="26%">Dibuat</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="6" style="text-align:center; color:#999;">Tidak ada data pengguna</td></tr>
            <?php else: ?>
                <?php $no=1; foreach ($rows as $item): ?>
                    <tr>
                        <td style="text-align:center;"><?php echo $no++; ?></td>
                        <td><?php echo e($item['nama']); ?></td>
                        <td><?php echo e($item['username']); ?></td>
                        <td><?php echo e($item['role']); ?></td>
                        <td><?php echo e($item['status']); ?></td>
                        <td><?php echo e($item['created_at'] ? date('d-m-Y H:i', strtotime($item['created_at'])) : '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Ringkasan -->
    <?php if (count($rows) > 0): ?>
        <div style="margin-top: 20px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd;">
            <strong>Ringkasan:</strong><br>
            Total Pengguna: <strong><?php echo $totalPengguna; ?> akun</strong><br>
            Role — Admin: <strong><?php echo $roleCounts['Admin']; ?></strong> | User: <strong><?php echo $roleCounts['User']; ?></strong><br>
            Status — Aktif: <strong><?php echo $statusCounts['Aktif']; ?></strong> | Nonaktif: <strong><?php echo $statusCounts['Nonaktif']; ?></strong>
        </div>
    <?php endif; ?>

    <!-- Footer dengan TTD -->
    <div class="footer">
        <div class="ttd">
            <p>Mengetahui,</p>
            <p style="margin-bottom: 5px;">Kepala Divisi</p>
            <div class="nama">( _________________ )</div>
        </div>
        <div class="ttd">
            <p>Banjarmasin, <?php echo date('d F Y'); ?></p>
            <p style="margin-bottom: 5px;">Petugas</p>
            <div class="nama">( <?php echo e($_SESSION['nama'] ?? 'Admin'); ?> )</div>
        </div>
    </div>

    <script>
      // Opsional: auto print saat halaman dimuat
      // window.onload = function(){ window.print(); }
    </script>
</body>
</html>