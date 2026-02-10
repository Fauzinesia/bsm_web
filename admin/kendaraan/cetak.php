<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

// Filter parameters (sama seperti di kendaraan.php)
$filterStatus = $_GET['filter_status'] ?? 'all';
$filterSearch = $_GET['search'] ?? '';
$filterMerk = $_GET['filter_merk'] ?? 'all';

// Build SQL query with filters
$sql = 'SELECT * FROM tb_kendaraan WHERE 1=1';
$params = [];
$types = '';

if ($filterStatus !== 'all') {
    $sql .= ' AND status_operasional = ?';
    $params[] = $filterStatus;
    $types .= 's';
}

if ($filterSearch !== '') {
    $sql .= ' AND (nomor_polisi LIKE ? OR nama_kendaraan LIKE ? OR merk LIKE ?)';
    $searchParam = '%' . $filterSearch . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if ($filterMerk !== 'all') {
    $sql .= ' AND merk = ?';
    $params[] = $filterMerk;
    $types .= 's';
}

$sql .= ' ORDER BY nomor_polisi ASC';

$kendaraan = [];
if (!empty($params)) {
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $kendaraan[] = $row;
        }
        $stmt->close();
    }
} else {
    if ($result = $koneksi->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $kendaraan[] = $row;
        }
        $result->free();
    }
}

// Get unique merk untuk dropdown filter
$merkList = [];
$merkQuery = "SELECT DISTINCT merk FROM tb_kendaraan WHERE merk IS NOT NULL AND merk != '' ORDER BY merk ASC";
if ($result = $koneksi->query($merkQuery)) {
    while ($row = $result->fetch_assoc()) {
        $merkList[] = $row['merk'];
    }
    $result->free();
}

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Data Kendaraan - PT BORNEO MARGASARANA MANDIRI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            padding: 20px;
            background: #fff;
        }
        
        /* Header Kop Surat */
        .kop-surat {
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .kop-surat .logo {
            flex-shrink: 0;
        }
        
        .kop-surat .logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        
        .kop-surat .info {
            flex-grow: 1;
            text-align: center;
        }
        
        .kop-surat .info h1 {
            font-size: 20px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .kop-surat .info h2 {
            font-size: 16px;
            font-weight: normal;
            color: #333;
            margin-bottom: 3px;
        }
        
        .kop-surat .info p {
            font-size: 11px;
            color: #555;
            line-height: 1.4;
        }
        
        /* Judul Laporan */
        .judul-laporan {
            text-align: center;
            margin: 20px 0;
        }
        
        .judul-laporan h3 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .judul-laporan p {
            font-size: 11px;
            color: #666;
        }
        
        /* Info Cetak */
        .info-cetak {
            margin-bottom: 15px;
            font-size: 11px;
            color: #666;
        }
        
        /* Tabel */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table thead {
            background-color: #f5f5f5;
        }
        
        table thead th {
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        
        table tbody td {
            border: 1px solid #ddd;
            padding: 6px 5px;
            font-size: 11px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        /* Status Badge */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-primary {
            background-color: #cfe2ff;
            color: #084298;
        }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        
        .footer .ttd {
            text-align: center;
            width: 200px;
        }
        
        .footer .ttd .nama {
            margin-top: 60px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        
        /* Print Styles */
        @media print {
            body {
                padding: 10px;
            }
            
            .no-print {
                display: none !important;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            @page {
                margin: 1cm;
            }
        }
        
        /* Button Print */
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .btn-print:hover {
            background-color: #0056b3;
        }
        
        /* Filter Panel */
        .filter-panel {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filter-panel h4 {
            margin-bottom: 15px;
            color: #333;
            font-size: 16px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 13px;
            color: #555;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-back {
            background-color: #28a745;
            color: white;
        }
        
        .btn-back:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <!-- Filter Panel (tidak ikut cetak) -->
    <div class="filter-panel no-print">
        <h4>📊 Filter Data Kendaraan</h4>
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter_status">Status Operasional:</label>
                    <select name="filter_status" id="filter_status">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="Aktif" <?php echo $filterStatus === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Perawatan" <?php echo $filterStatus === 'Perawatan' ? 'selected' : ''; ?>>Perawatan</option>
                        <option value="Rusak" <?php echo $filterStatus === 'Rusak' ? 'selected' : ''; ?>>Rusak</option>
                        <option value="Disewa" <?php echo $filterStatus === 'Disewa' ? 'selected' : ''; ?>>Disewa</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter_merk">Merk Kendaraan:</label>
                    <select name="filter_merk" id="filter_merk">
                        <option value="all" <?php echo $filterMerk === 'all' ? 'selected' : ''; ?>>Semua Merk</option>
                        <?php foreach ($merkList as $merk): ?>
                            <option value="<?php echo htmlspecialchars($merk); ?>" 
                                <?php echo $filterMerk === $merk ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($merk); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search">Pencarian:</label>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           placeholder="Cari No. Polisi, Nama, Merk..." 
                           value="<?php echo htmlspecialchars($filterSearch); ?>">
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">🔍 Terapkan Filter</button>
                    <a href="cetak.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block; line-height: 1.5;">🔄 Reset</a>
                    <a href="kendaraan.php" class="btn btn-back" style="text-decoration: none; display: inline-block; line-height: 1.5;">⬅️ Kembali</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Tombol Print -->
    <button onclick="window.print()" class="btn-print no-print">
        🖨️ Cetak / Print
    </button>
    
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
        <h3>Laporan Data Kendaraan</h3>
        <p>
            <?php 
            if ($filterStatus !== 'all') {
                echo "Status: " . htmlspecialchars($filterStatus) . " | ";
            }
            if ($filterMerk !== 'all') {
                echo "Merk: " . htmlspecialchars($filterMerk) . " | ";
            }
            if ($filterSearch !== '') {
                echo "Pencarian: " . htmlspecialchars($filterSearch) . " | ";
            }
            echo "Total: " . count($kendaraan) . " unit";
            ?>
        </p>
    </div>
    
    <!-- Info Cetak -->
    <div class="info-cetak">
        <p>Dicetak pada: <?php echo date('d F Y, H:i'); ?> WIB | Dicetak oleh: <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></p>
    </div>
    
    <!-- Tabel Data -->
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">No. Polisi</th>
                <th width="20%">Nama Kendaraan</th>
                <th width="15%">Merk/Tipe</th>
                <th width="8%">Tahun</th>
                <th width="10%">Warna</th>
                <th width="12%">Status</th>
                <th width="18%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($kendaraan) === 0): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #999;">Tidak ada data kendaraan</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($kendaraan as $item): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $no++; ?></td>
                        <td><strong><?php echo htmlspecialchars($item['nomor_polisi']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['nama_kendaraan']); ?></td>
                        <td>
                            <?php 
                            $merkTipe = trim(($item['merk'] ?? '') . ' ' . ($item['tipe'] ?? ''));
                            echo htmlspecialchars($merkTipe ?: '-');
                            ?>
                        </td>
                        <td style="text-align: center;"><?php echo htmlspecialchars($item['tahun'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($item['warna'] ?? '-'); ?></td>
                        <td>
                            <?php
                                $status = $item['status_operasional'] ?? 'Aktif';
                                $badgeClass = 'badge-success';
                                if ($status === 'Perawatan') {
                                    $badgeClass = 'badge-warning';
                                } elseif ($status === 'Rusak') {
                                    $badgeClass = 'badge-danger';
                                } elseif ($status === 'Disewa') {
                                    $badgeClass = 'badge-primary';
                                }
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $ket = $item['keterangan'] ?? '-';
                            echo htmlspecialchars(strlen($ket) > 50 ? substr($ket, 0, 50) . '...' : $ket);
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Ringkasan -->
    <?php if (count($kendaraan) > 0): ?>
        <div style="margin-top: 20px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd;">
            <strong>Ringkasan:</strong><br>
            <?php
            $statusCounts = [
                'Aktif' => 0,
                'Perawatan' => 0,
                'Rusak' => 0,
                'Disewa' => 0,
            ];
            
            foreach ($kendaraan as $item) {
                $status = $item['status_operasional'] ?? 'Aktif';
                if (isset($statusCounts[$status])) {
                    $statusCounts[$status]++;
                }
            }
            ?>
            Total Kendaraan: <strong><?php echo count($kendaraan); ?> unit</strong> |
            Aktif: <strong><?php echo $statusCounts['Aktif']; ?></strong> |
            Perawatan: <strong><?php echo $statusCounts['Perawatan']; ?></strong> |
            Rusak: <strong><?php echo $statusCounts['Rusak']; ?></strong> |
            Disewa: <strong><?php echo $statusCounts['Disewa']; ?></strong>
        </div>
    <?php endif; ?>
    
    <!-- Footer dengan TTD -->
    <div class="footer">
        <div class="ttd">
            <p>Mengetahui,</p>
            <p style="margin-bottom: 5px;">Kepala Divisi</p>
            <div class="nama">
                ( _________________ )
            </div>
        </div>
        
        <div class="ttd">
            <p>Banjarmasin, <?php echo date('d F Y'); ?></p>
            <p style="margin-bottom: 5px;">Petugas</p>
            <div class="nama">
                ( <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?> )
            </div>
        </div>
    </div>
    
    <script>
        // Auto print saat halaman dimuat (opsional, bisa dihapus jika tidak diperlukan)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>
