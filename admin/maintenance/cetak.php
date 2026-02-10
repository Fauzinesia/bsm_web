<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }

// Filter parameters (selaras dengan gaya kendaraan/cetak.php)
$filterStatus = $_GET['filter_status'] ?? 'all';
$filterSearch = $_GET['search'] ?? '';
$filterDateStart = $_GET['date_start'] ?? '';
$filterDateEnd   = $_GET['date_end'] ?? '';

$where = [];
$params = [];
$types = '';

if ($filterStatus !== 'all') { $where[] = 'm.status = ?'; $types .= 's'; $params[] = $filterStatus; }
if ($filterSearch !== '') {
    $where[] = '(k.nomor_polisi LIKE ? OR k.nama_kendaraan LIKE ? OR m.jenis_service LIKE ? OR m.deskripsi LIKE ?)';
    $like = '%' . $filterSearch . '%';
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
if ($filterDateStart !== '') { $where[] = 'm.tanggal_service >= ?'; $types .= 's'; $params[] = $filterDateStart; }
if ($filterDateEnd !== '')   { $where[] = 'm.tanggal_service <= ?'; $types .= 's'; $params[] = $filterDateEnd; }

$sql = 'SELECT m.id_maintenance, m.tanggal_service, m.jenis_service, m.deskripsi, m.biaya, m.status,
               k.nomor_polisi, k.nama_kendaraan
        FROM tb_maintenance m
        JOIN tb_kendaraan k ON k.id_kendaraan = m.id_kendaraan';
if (! empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY m.tanggal_service DESC, m.id_maintenance DESC';

$maintenance = [];
$stmt = $koneksi->prepare($sql);
if ($stmt) {
    if (! empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $maintenance[] = $row; }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Data Maintenance - PT BORNEO MARGASARANA MANDIRI</title>
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

        /* Tabel */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table thead { background-color: #f5f5f5; }
        table thead th { border: 1px solid #000; padding: 8px 5px; text-align: left; font-weight: bold; font-size: 11px; }
        table tbody td { border: 1px solid #ddd; padding: 6px 5px; font-size: 11px; }
        table tbody tr:nth-child(even) { background-color: #f9f9f9; }

        /* Status Badge */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-info { background-color: #cff4fc; color: #055160; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-success { background-color: #d4edda; color: #155724; }

        /* Footer TTD */
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
    </style>
</head>
<body>
    <!-- Filter Panel (tidak ikut cetak) -->
    <div class="filter-panel no-print">
        <h4>📊 Filter Data Maintenance</h4>
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter_status">Status:</label>
                    <select name="filter_status" id="filter_status">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="Dijadwalkan" <?php echo $filterStatus === 'Dijadwalkan' ? 'selected' : ''; ?>>Dijadwalkan</option>
                        <option value="Proses" <?php echo $filterStatus === 'Proses' ? 'selected' : ''; ?>>Proses</option>
                        <option value="Selesai" <?php echo $filterStatus === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
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
                    <input type="text" name="search" id="search" placeholder="Cari No. Polisi, Nama, Jenis/Deskripsi..." value="<?php echo e($filterSearch); ?>">
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">🔍 Terapkan Filter</button>
                    <a href="cetak.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block; line-height: 1.5;">🔄 Reset</a>
                    <a href="maintenance.php" class="btn btn-back" style="text-decoration: none; display: inline-block; line-height: 1.5;">⬅️ Kembali</a>
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
        <h3>Laporan Data Maintenance Kendaraan</h3>
        <p>
            <?php 
            if ($filterStatus !== 'all') { echo "Status: " . e($filterStatus) . " | "; }
            if ($filterDateStart !== '') { echo "Mulai: " . e($filterDateStart) . " | "; }
            if ($filterDateEnd !== '') { echo "Akhir: " . e($filterDateEnd) . " | "; }
            if ($filterSearch !== '') { echo "Pencarian: " . e($filterSearch) . " | "; }
            echo "Total: " . count($maintenance) . " records";
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
                <th width="10%">Tanggal</th>
                <th width="20%">Kendaraan</th>
                <th width="15%">Jenis Service</th>
                <th width="30%">Deskripsi</th>
                <th width="10%">Biaya</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($maintenance) === 0): ?>
                <tr><td colspan="7" style="text-align:center; color:#999;">Tidak ada data maintenance</td></tr>
            <?php else: ?>
                <?php $no=1; $totalBiaya=0.0; foreach ($maintenance as $item): ?>
                    <tr>
                        <td style="text-align:center;"><?php echo $no++; ?></td>
                        <td><?php echo e(date('d-m-Y', strtotime($item['tanggal_service']))); ?></td>
                        <td><?php echo e($item['nomor_polisi']); ?> - <?php echo e($item['nama_kendaraan']); ?></td>
                        <td><?php echo e($item['jenis_service']); ?></td>
                        <td><?php echo e($item['deskripsi']); ?></td>
                        <td><?php 
                            if ($item['biaya'] !== null) { 
                                $totalBiaya += (float)$item['biaya']; 
                                echo 'Rp ' . number_format((float)$item['biaya'], 2, ',', '.'); 
                            } else { echo '-'; }
                        ?></td>
                        <td>
                            <?php 
                                $status = $item['status'];
                                $badgeClass = 'badge-info';
                                if ($status === 'Proses') { $badgeClass = 'badge-warning'; }
                                elseif ($status === 'Selesai') { $badgeClass = 'badge-success'; }
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo e($status); ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Ringkasan -->
    <?php if (count($maintenance) > 0): ?>
        <div style="margin-top: 20px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd;">
            <strong>Ringkasan:</strong><br>
            <?php
                $statusCounts = [ 'Dijadwalkan' => 0, 'Proses' => 0, 'Selesai' => 0 ];
                foreach ($maintenance as $m) { if (isset($statusCounts[$m['status']])) { $statusCounts[$m['status']]++; } }
            ?>
            Total Maintenance: <strong><?php echo count($maintenance); ?></strong> |
            Dijadwalkan: <strong><?php echo $statusCounts['Dijadwalkan']; ?></strong> |
            Proses: <strong><?php echo $statusCounts['Proses']; ?></strong> |
            Selesai: <strong><?php echo $statusCounts['Selesai']; ?></strong> |
            Total Biaya: <strong><?php echo 'Rp ' . number_format((float)$totalBiaya, 2, ',', '.'); ?></strong>
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