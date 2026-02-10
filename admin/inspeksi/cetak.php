<?php
session_start();
if (! isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
require_once dirname(__DIR__, 2) . '/config/koneksi.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function valid_date($d){ return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d); }

// Filter parameters – seragam dengan maintenance/cetak.php namun relevan utk inspeksi
$filterBan = $_GET['filter_ban'] ?? 'all';
$filterSearch = $_GET['search'] ?? '';
$filterDateStart = $_GET['date_start'] ?? '';
$filterDateEnd   = $_GET['date_end'] ?? '';

$where = [];
$params = [];
$types = '';

if ($filterBan !== 'all') { $where[] = 'i.kondisi_ban = ?'; $types .= 's'; $params[] = $filterBan; }
if ($filterSearch !== '') {
    $where[] = '(k.nomor_polisi LIKE ? OR k.nama_kendaraan LIKE ? OR k.merk LIKE ? OR k.tipe LIKE ? OR i.catatan LIKE ?)';
    $like = '%' . $filterSearch . '%';
    $types .= 'sssss';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($filterDateStart !== '' && valid_date($filterDateStart)) { $where[] = 'i.tanggal >= ?'; $types .= 's'; $params[] = $filterDateStart; }
if ($filterDateEnd !== '' && valid_date($filterDateEnd))   { $where[] = 'i.tanggal <= ?'; $types .= 's'; $params[] = $filterDateEnd; }

$sql = 'SELECT i.id_inspeksi, i.tanggal, i.kondisi_ban, i.kondisi_lampu, i.oli_mesin, i.rem, i.kebersihan, i.catatan,
               k.nomor_polisi, k.nama_kendaraan
        FROM tb_inspeksi i
        JOIN tb_kendaraan k ON k.id_kendaraan = i.id_kendaraan';
if (! empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY i.tanggal DESC, i.id_inspeksi DESC';

$inspeksi = [];
$stmt = $koneksi->prepare($sql);
if ($stmt) {
    if (! empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $inspeksi[] = $row; }
    $stmt->close();
}

// Ringkasan
$banCounts = ['Baik' => 0, 'Perlu dicek' => 0, 'Rusak' => 0];
$uniqueVehicles = [];
foreach ($inspeksi as $row) {
    $cond = $row['kondisi_ban'] ?? 'Baik';
    if (isset($banCounts[$cond])) $banCounts[$cond]++;
    if (isset($row['nama_kendaraan'], $row['nomor_polisi'])) {
        $uniqueVehicles[$row['nama_kendaraan'] . '|' . $row['nomor_polisi']] = true;
    }
}
$totalUniqueVehicles = count($uniqueVehicles);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Data Inspeksi - PT BORNEO MARGASARANA MANDIRI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 12px; line-height: 1.6; padding: 20px; background: #fff; }

        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
        .kop-surat .logo { flex-shrink: 0; }
        .kop-surat .logo img { width: 80px; height: 80px; object-fit: contain; }
        .kop-surat .info { flex-grow: 1; text-align: center; }
        .kop-surat .info h1 { font-size: 20px; font-weight: bold; color: #000; margin-bottom: 5px; text-transform: uppercase; }
        .kop-surat .info h2 { font-size: 16px; font-weight: normal; color: #333; margin-bottom: 3px; }
        .kop-surat .info p { font-size: 11px; color: #555; line-height: 1.4; }

        .judul-laporan { text-align: center; margin: 20px 0; }
        .judul-laporan h3 { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .judul-laporan p { font-size: 11px; color: #666; }

        .info-cetak { margin-bottom: 15px; font-size: 11px; color: #666; }

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

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table thead { background-color: #f5f5f5; }
        table thead th { border: 1px solid #000; padding: 8px 5px; text-align: left; font-weight: bold; font-size: 11px; }
        table tbody td { border: 1px solid #ddd; padding: 6px 5px; font-size: 11px; }
        table tbody tr:nth-child(even) { background-color: #f9f9f9; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-info { background-color: #cff4fc; color: #055160; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-success { background-color: #d4edda; color: #155724; }

        .footer { margin-top: 30px; display: flex; justify-content: space-between; }
        .footer .ttd { text-align: center; width: 200px; }
        .footer .ttd .nama { margin-top: 60px; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; }

        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            @page { margin: 1cm; }
        }

        .btn-print { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .btn-print:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="filter-panel no-print">
        <h4>📊 Filter Data Inspeksi</h4>
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter_ban">Kondisi Ban:</label>
                    <select name="filter_ban" id="filter_ban">
                        <option value="all" <?php echo $filterBan === 'all' ? 'selected' : ''; ?>>Semua</option>
                        <option value="Baik" <?php echo $filterBan === 'Baik' ? 'selected' : ''; ?>>Baik</option>
                        <option value="Perlu dicek" <?php echo $filterBan === 'Perlu dicek' ? 'selected' : ''; ?>>Perlu dicek</option>
                        <option value="Rusak" <?php echo $filterBan === 'Rusak' ? 'selected' : ''; ?>>Rusak</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="date_start">Tanggal Mulai:</label>
                    <input type="date" name="date_start" id="date_start" value="<?php echo e($filterDateStart); ?>">
                </div>
                <div class="filter-group">
                    <label for="date_end">Tanggal Akhir:</label>
                    <input type="date" name="date_end" id="date_end" value="<?php echo e($filterDateEnd); ?>">
                </div>
                <div class="filter-group">
                    <label for="search">Pencarian:</label>
                    <input type="text" name="search" id="search" placeholder="Cari No. Polisi, Nama, Merk/Tipe, Catatan..." value="<?php echo e($filterSearch); ?>">
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">🔍 Terapkan Filter</button>
                    <a href="cetak.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block; line-height:1.5;">🔄 Reset</a>
                    <a href="inspeksi.php" class="btn btn-back" style="text-decoration:none; display:inline-block; line-height:1.5;">⬅️ Kembali</a>
                </div>
            </div>
        </form>
    </div>

    <button onclick="window.print()" class="btn-print no-print">🖨️ Cetak / Print</button>

    <div class="kop-surat">
        <div class="logo"><img src="../../assets/images/bsm.png" alt="Logo BSM"></div>
        <div class="info">
            <h1>PT BORNEO MARGASARANA MANDIRI</h1>
            <h2>Sistem Manajemen Kendaraan</h2>
            <p>
                Jl.Raya Provinsi Km 191 Desa Sumber Baru angsana Tanah Bumbu<br>
                Telp: +62 822-7336-9909 | Email: pt.borneo.sarana.margasana@gmail.com
            </p>
        </div>
    </div>

    <div class="judul-laporan">
        <h3>Laporan Data Inspeksi Kendaraan</h3>
        <p>
            <?php 
            if ($filterBan !== 'all') { echo "Kondisi Ban: " . e($filterBan) . " | "; }
            if ($filterDateStart !== '') { echo "Mulai: " . e($filterDateStart) . " | "; }
            if ($filterDateEnd !== '') { echo "Akhir: " . e($filterDateEnd) . " | "; }
            if ($filterSearch !== '') { echo "Pencarian: " . e($filterSearch) . " | "; }
            echo "Total: " . count($inspeksi) . " records";
            ?>
        </p>
    </div>

    <div class="info-cetak">
        <p>Dicetak pada: <?php echo date('d F Y, H:i'); ?> WIB | Dicetak oleh: <?php echo e($_SESSION['nama'] ?? 'Admin'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">Tanggal</th>
                <th width="20%">Kendaraan</th>
                <th width="10%">Ban</th>
                <th width="10%">Lampu</th>
                <th width="10%">Oli Mesin</th>
                <th width="10%">Rem</th>
                <th width="10%">Kebersihan</th>
                <th width="15%">Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($inspeksi) === 0): ?>
                <tr><td colspan="9" style="text-align:center; color:#999;">Tidak ada data inspeksi</td></tr>
            <?php else: ?>
                <?php $no=1; foreach ($inspeksi as $item): ?>
                    <tr>
                        <td style="text-align:center;"><?php echo $no++; ?></td>
                        <td><?php echo e(date('d-m-Y', strtotime($item['tanggal']))); ?></td>
                        <td><?php echo e($item['nomor_polisi']); ?> - <?php echo e($item['nama_kendaraan']); ?></td>
                        <td><?php echo e($item['kondisi_ban']); ?></td>
                        <td><?php echo e($item['kondisi_lampu']); ?></td>
                        <td><?php echo e($item['oli_mesin']); ?></td>
                        <td><?php echo e($item['rem']); ?></td>
                        <td><?php echo e($item['kebersihan']); ?></td>
                        <td><?php echo nl2br(e($item['catatan'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (count($inspeksi) > 0): ?>
        <div style="margin-top: 20px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd;">
            <strong>Ringkasan:</strong><br>
            Total Inspeksi: <strong><?php echo count($inspeksi); ?></strong> |
            Kendaraan Unik: <strong><?php echo $totalUniqueVehicles; ?></strong> |
            Ban Baik: <strong><?php echo $banCounts['Baik']; ?></strong> |
            Ban Perlu Dicek: <strong><?php echo $banCounts['Perlu dicek']; ?></strong> |
            Ban Rusak: <strong><?php echo $banCounts['Rusak']; ?></strong>
        </div>
    <?php endif; ?>

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

