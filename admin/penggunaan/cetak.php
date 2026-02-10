<?php
// Standalone printable layout (konsisten dengan admin/kendaraan/cetak.php)
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

// Filters (diselaraskan dengan halaman daftar)
$filterStatus    = $_GET['filter_status'] ?? 'all';
$filterSearch    = $_GET['search'] ?? '';
$filterDateStart = $_GET['date_start'] ?? '';
$filterDateEnd   = $_GET['date_end'] ?? '';

$sql = 'SELECT p.id_penggunaan, p.tanggal_mulai, p.tanggal_selesai, p.keperluan, p.status, 
               k.nomor_polisi, k.nama_kendaraan, 
               u.nama AS nama_pengguna, u.username 
        FROM tb_penggunaan p 
        LEFT JOIN tb_kendaraan k ON p.id_kendaraan = k.id_kendaraan 
        LEFT JOIN tb_pengguna u ON p.id_pengguna = u.id_pengguna 
        WHERE 1=1';
$params = [];
$types  = '';

if ($filterStatus !== 'all') {
    $sql .= ' AND p.status = ?';
    $params[] = $filterStatus;
    $types .= 's';
}

if ($filterSearch !== '') {
    $sql .= ' AND (p.keperluan LIKE ? OR k.nomor_polisi LIKE ? OR k.nama_kendaraan LIKE ? OR u.nama LIKE ? OR u.username LIKE ?)';
    $searchParam = '%' . $filterSearch . '%';
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    $types .= 'sssss';
}

if ($filterDateStart !== '' && is_valid_date($filterDateStart)) {
    $sql .= ' AND p.tanggal_mulai >= ?';
    $params[] = $filterDateStart;
    $types .= 's';
}

if ($filterDateEnd !== '' && is_valid_date($filterDateEnd)) {
    $sql .= ' AND (p.tanggal_selesai IS NULL OR p.tanggal_selesai <= ?)';
    $params[] = $filterDateEnd;
    $types .= 's';
}

$sql .= ' ORDER BY p.tanggal_mulai DESC, p.id_penggunaan DESC';

$penggunaan = [];
if (!empty($params)) {
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $penggunaan[] = $row; }
        $stmt->close();
    }
} else {
    if ($res = $koneksi->query($sql)) {
        while ($row = $res->fetch_assoc()) { $penggunaan[] = $row; }
        $res->free();
    }
}

// ringkasan status
$statusCounts = ['Berjalan' => 0, 'Selesai' => 0];
foreach ($penggunaan as $it) { $st = $it['status'] ?? 'Berjalan'; if (isset($statusCounts[$st])) $statusCounts[$st]++; }

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Data Penggunaan - PT BORNEO MARGASARANA MANDIRI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 12px; line-height: 1.6; padding: 20px; background: #fff; }
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
        .kop-surat .logo img { width: 80px; height: 80px; object-fit: contain; }
        .kop-surat .info { flex-grow: 1; text-align: center; }
        .kop-surat .info h1 { font-size: 20px; font-weight: bold; color: #000; margin-bottom: 5px; text-transform: uppercase; }
        .kop-surat .info h2 { font-size: 16px; font-weight: normal; color: #333; margin-bottom: 3px; }
        .kop-surat .info p { font-size: 11px; color: #555; line-height: 1.4; }

        .judul-laporan { text-align: center; margin: 20px 0; }
        .judul-laporan h3 { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .judul-laporan p { font-size: 11px; color: #666; }

        .info-cetak { margin-bottom: 15px; font-size: 11px; color: #666; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table thead { background-color: #f5f5f5; }
        table thead th { border: 1px solid #000; padding: 8px 5px; text-align: left; font-weight: bold; font-size: 11px; }
        table tbody td { border: 1px solid #ddd; padding: 6px 5px; font-size: 11px; }
        table tbody tr:nth-child(even) { background-color: #f9f9f9; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }

        .filter-panel { background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .filter-panel h4 { margin-bottom: 15px; color: #333; font-size: 16px; }
        .filter-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px; color: #555; }
        .filter-group select, .filter-group input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        .filter-buttons { display: flex; gap: 10px; }
        .btn { padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.3s; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-back { background-color: #28a745; color: white; }
        .btn-print { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .btn-print:hover { background-color: #0056b3; }

        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>
    <!-- Filter Panel (tidak ikut cetak) -->
    <div class="filter-panel no-print">
        <h4>📊 Filter Data Penggunaan</h4>
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter_status">Status:</label>
                    <select name="filter_status" id="filter_status">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Semua</option>
                        <option value="Berjalan" <?php echo $filterStatus === 'Berjalan' ? 'selected' : ''; ?>>Berjalan</option>
                        <option value="Selesai" <?php echo $filterStatus === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="date_start">Tanggal Mulai:</label>
                    <input type="date" name="date_start" id="date_start" value="<?php echo e($filterDateStart); ?>">
                </div>
                <div class="filter-group">
                    <label for="date_end">Tanggal Selesai:</label>
                    <input type="date" name="date_end" id="date_end" value="<?php echo e($filterDateEnd); ?>">
                </div>
                <div class="filter-group">
                    <label for="search">Pencarian:</label>
                    <input type="text" name="search" id="search" placeholder="Cari keperluan/kendaraan/pengguna..." value="<?php echo e($filterSearch); ?>">
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">🔍 Terapkan Filter</button>
                    <a href="cetak.php" class="btn btn-secondary" style="text-decoration:none; line-height:1.5;">🔄 Reset</a>
                    <a href="penggunaan.php" class="btn btn-back" style="text-decoration:none; line-height:1.5;">⬅️ Kembali</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tombol Print -->
    <button onclick="window.print()" class="btn-print no-print">🖨️ Cetak / Print</button>

    <!-- Kop Surat -->
    <div class="kop-surat">
        <div class="logo"><img src="../../assets/images/bsm.png" alt="Logo BSM"></div>
        <div class="info">
            <h1>PT BORNEO MARGASARANA MANDIRI</h1>
            <h2>Sistem Manajemen Kendaraan</h2>
             <p> Jl.Raya Provinsi Km 191 Desa Sumber Baru angsana Tanah Bumbu<br>
                Telp: +62 822-7336-9909 | Email: pt.borneo.sarana.margasana@gmail.com
            </p>
        </div>
    </div>

    <!-- Judul Laporan -->
    <div class="judul-laporan">
        <h3>Laporan Data Penggunaan Kendaraan</h3>
        <p>
            <?php
            if ($filterStatus !== 'all') { echo 'Status: ' . e($filterStatus) . ' | '; }
            if ($filterDateStart !== '') { echo 'Mulai: ' . e($filterDateStart) . ' | '; }
            if ($filterDateEnd !== '') { echo 'Selesai: ' . e($filterDateEnd) . ' | '; }
            if ($filterSearch !== '') { echo 'Pencarian: ' . e($filterSearch) . ' | '; }
            echo 'Total: ' . count($penggunaan) . ' entri';
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
                <th width="20%">Kendaraan</th>
                <th width="18%">Pengguna</th>
                <th width="22%">Periode</th>
                <th width="25%">Keperluan</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($penggunaan) === 0): ?>
                <tr><td colspan="6" style="text-align:center; color:#999;">Tidak ada data penggunaan</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($penggunaan as $item): ?>
                    <tr>
                        <td style="text-align:center;"><?php echo $no++; ?></td>
                        <td>
                            <strong><?php echo e($item['nomor_polisi'] ?: '-'); ?></strong>
                            <div class="text-muted" style="font-size:10px;"><?php echo e($item['nama_kendaraan'] ?: '-'); ?></div>
                        </td>
                        <td>
                            <strong><?php echo e($item['nama_pengguna'] ?: '-'); ?></strong>
                            <div class="text-muted" style="font-size:10px;">@<?php echo e($item['username'] ?: '-'); ?></div>
                        </td>
                        <td>
                            <?php echo e($item['tanggal_mulai'] ? date('d M Y', strtotime($item['tanggal_mulai'])) : '-'); ?>
                            —
                            <?php echo e($item['tanggal_selesai'] ? date('d M Y', strtotime($item['tanggal_selesai'])) : ''); ?>
                        </td>
                        <td>
                            <?php $ket = $item['keperluan'] ?? '-'; echo e(strlen($ket) > 80 ? substr($ket,0,80).'...' : $ket); ?>
                        </td>
                        <td>
                            <?php $st = $item['status'] ?? 'Berjalan'; $cls = ($st === 'Selesai') ? 'badge-success' : 'badge-warning'; ?>
                            <span class="badge <?php echo $cls; ?>"><?php echo e($st); ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Ringkasan -->
    <?php if (count($penggunaan) > 0): ?>
        <div style="margin-top:20px; padding:10px; background:#f5f5f5; border:1px solid #ddd;">
            <strong>Ringkasan:</strong>
            Total Entri: <strong><?php echo count($penggunaan); ?></strong> |
            Berjalan: <strong><?php echo $statusCounts['Berjalan']; ?></strong> |
            Selesai: <strong><?php echo $statusCounts['Selesai']; ?></strong>
        </div>
    <?php endif; ?>

    <script>
        // Auto print saat halaman dimuat (opsional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>