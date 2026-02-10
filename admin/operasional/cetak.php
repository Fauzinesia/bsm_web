<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function is_valid_date($d) { return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d); }

// Filter parameters (selaras dengan gaya maintenance/cetak.php)
$filterJenis     = $_GET['filter_jenis'] ?? 'all';
$filterSearch    = $_GET['search'] ?? '';
$filterDateStart = $_GET['date_start'] ?? '';
$filterDateEnd   = $_GET['date_end'] ?? '';

$where = [];
$params = [];
$types = '';

if ($filterJenis !== 'all') { $where[] = 'b.jenis_biaya = ?'; $types .= 's'; $params[] = $filterJenis; }
if ($filterSearch !== '') {
    $where[] = '(k.nomor_polisi LIKE ? OR k.nama_kendaraan LIKE ? OR b.jenis_biaya LIKE ? OR b.keterangan LIKE ?)';
    $like = '%' . $filterSearch . '%';
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
if ($filterDateStart !== '') { $where[] = 'b.tanggal >= ?'; $types .= 's'; $params[] = $filterDateStart; }
if ($filterDateEnd !== '')   { $where[] = 'b.tanggal <= ?'; $types .= 's'; $params[] = $filterDateEnd; }

$sql = 'SELECT b.id_biaya, b.tanggal, b.jenis_biaya, b.nominal, b.keterangan, k.nomor_polisi, k.nama_kendaraan
        FROM tb_biaya_operasional b
        JOIN tb_kendaraan k ON k.id_kendaraan = b.id_kendaraan';
if (! empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY b.tanggal DESC, b.id_biaya DESC';

$rows = [];
$stmt = $koneksi->prepare($sql);
if ($stmt) {
    if (! empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $rows[] = $row; }
    $stmt->close();
}

$totalNominal = 0.0;
$jenisCounts = [ 'BBM' => 0, 'Tol' => 0, 'Parkir' => 0, 'Pajak' => 0, 'Asuransi' => 0, 'Lainnya' => 0 ];
foreach ($rows as $r) {
    $totalNominal += (float)($r['nominal'] ?? 0);
    $jb = $r['jenis_biaya'] ?? 'Lainnya';
    if (isset($jenisCounts[$jb])) { $jenisCounts[$jb]++; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Data Biaya Operasional - PT BORNEO MARGASARANA MANDIRI</title>
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
        <h4>📊 Filter Data Biaya Operasional</h4>
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter_jenis">Jenis Biaya:</label>
                    <select name="filter_jenis" id="filter_jenis">
                        <option value="all" <?php echo $filterJenis === 'all' ? 'selected' : ''; ?>>Semua</option>
                        <option value="BBM" <?php echo $filterJenis === 'BBM' ? 'selected' : ''; ?>>BBM</option>
                        <option value="Tol" <?php echo $filterJenis === 'Tol' ? 'selected' : ''; ?>>Tol</option>
                        <option value="Parkir" <?php echo $filterJenis === 'Parkir' ? 'selected' : ''; ?>>Parkir</option>
                        <option value="Pajak" <?php echo $filterJenis === 'Pajak' ? 'selected' : ''; ?>>Pajak</option>
                        <option value="Asuransi" <?php echo $filterJenis === 'Asuransi' ? 'selected' : ''; ?>>Asuransi</option>
                        <option value="Lainnya" <?php echo $filterJenis === 'Lainnya' ? 'selected' : ''; ?>>Lainnya</option>
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
                    <input type="text" name="search" id="search" placeholder="Cari No. Polisi, Nama, Jenis/Keterangan..." value="<?php echo e($filterSearch); ?>">
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">🔍 Terapkan Filter</button>
                    <a href="cetak.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block; line-height: 1.5;">🔄 Reset</a>
                    <a href="operasional.php" class="btn btn-back" style="text-decoration: none; display: inline-block; line-height: 1.5;">⬅️ Kembali</a>
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
        <h3>Laporan Data Biaya Operasional Kendaraan</h3>
        <p>
            <?php 
            if ($filterJenis !== 'all') { echo "Jenis: " . e($filterJenis) . " | "; }
            if ($filterDateStart !== '') { echo "Mulai: " . e($filterDateStart) . " | "; }
            if ($filterDateEnd !== '') { echo "Akhir: " . e($filterDateEnd) . " | "; }
            if ($filterSearch !== '') { echo "Pencarian: " . e($filterSearch) . " | "; }
            echo "Total: " . count($rows) . " records";
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
                <th width="12%">Tanggal</th>
                <th width="22%">Kendaraan</th>
                <th width="15%">Jenis Biaya</th>
                <th width="12%">Nominal</th>
                <th width="34%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="6" style="text-align:center; color:#999;">Tidak ada data biaya operasional</td></tr>
            <?php else: ?>
                <?php $no=1; foreach ($rows as $item): ?>
                    <tr>
                        <td style="text-align:center;"><?php echo $no++; ?></td>
                        <td><?php echo e($item['tanggal'] ? date('d-m-Y', strtotime($item['tanggal'])) : '-'); ?></td>
                        <td><?php echo e($item['nomor_polisi']); ?> - <?php echo e($item['nama_kendaraan']); ?></td>
                        <td><?php echo e($item['jenis_biaya']); ?></td>
                        <td><?php echo 'Rp ' . number_format((float)($item['nominal'] ?? 0), 2, ',', '.'); ?></td>
                        <td><?php echo e($item['keterangan'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Ringkasan -->
    <?php if (count($rows) > 0): ?>
        <div style="margin-top: 20px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd;">
            <strong>Ringkasan:</strong><br>
            Total Transaksi: <strong><?php echo count($rows); ?></strong> |
            Total Nominal: <strong><?php echo 'Rp ' . number_format($totalNominal, 2, ',', '.'); ?></strong><br>
            Distribusi Jenis:
            <?php foreach ($jenisCounts as $jk => $cnt) { echo ' ' . e($jk) . ': ' . number_format($cnt) . ' |'; } ?>
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