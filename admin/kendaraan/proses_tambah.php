<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tambah.php');
    exit;
}

// Validasi input required
$nomor_polisi = trim($_POST['nomor_polisi'] ?? '');
$nama_kendaraan = trim($_POST['nama_kendaraan'] ?? '');

if ($nomor_polisi === '' || $nama_kendaraan === '') {
    header('Location: tambah.php?error=required');
    exit;
}

// Get other form data
$merk = trim($_POST['merk'] ?? '');
$tipe = trim($_POST['tipe'] ?? '');
$tahun = !empty($_POST['tahun']) ? (int)$_POST['tahun'] : null;
$warna = trim($_POST['warna'] ?? '');
$nomor_rangka = trim($_POST['nomor_rangka'] ?? '');
$nomor_mesin = trim($_POST['nomor_mesin'] ?? '');
$nomor_bpkb = trim($_POST['nomor_bpkb'] ?? '');
$masa_berlaku_stnk = !empty($_POST['masa_berlaku_stnk']) ? $_POST['masa_berlaku_stnk'] : null;
$masa_berlaku_pajak = !empty($_POST['masa_berlaku_pajak']) ? $_POST['masa_berlaku_pajak'] : null;
$masa_berlaku_asuransi = !empty($_POST['masa_berlaku_asuransi']) ? $_POST['masa_berlaku_asuransi'] : null;
$status_operasional = $_POST['status_operasional'] ?? 'Aktif';
$keterangan = trim($_POST['keterangan'] ?? '');

// Handle file uploads
$uploadDir = dirname(__DIR__, 2) . '/uploads/kendaraan/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$foto_kendaraan = null;
$dokumen_stnk = null;
$dokumen_bpkb = null;

// Upload foto kendaraan
if (isset($_FILES['foto_kendaraan']) && $_FILES['foto_kendaraan']['error'] === UPLOAD_ERR_OK) {
    $fileInfo = pathinfo($_FILES['foto_kendaraan']['name']);
    $fileExt = strtolower($fileInfo['extension']);
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($fileExt, $allowedExt) && $_FILES['foto_kendaraan']['size'] <= 2097152) {
        $newFileName = 'foto_' . uniqid() . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['foto_kendaraan']['tmp_name'], $uploadPath)) {
            $foto_kendaraan = 'uploads/kendaraan/' . $newFileName;
        }
    }
}

// Upload dokumen STNK
if (isset($_FILES['dokumen_stnk']) && $_FILES['dokumen_stnk']['error'] === UPLOAD_ERR_OK) {
    $fileInfo = pathinfo($_FILES['dokumen_stnk']['name']);
    $fileExt = strtolower($fileInfo['extension']);
    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (in_array($fileExt, $allowedExt) && $_FILES['dokumen_stnk']['size'] <= 2097152) {
        $newFileName = 'stnk_' . uniqid() . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['dokumen_stnk']['tmp_name'], $uploadPath)) {
            $dokumen_stnk = 'uploads/kendaraan/' . $newFileName;
        }
    }
}

// Upload dokumen BPKB
if (isset($_FILES['dokumen_bpkb']) && $_FILES['dokumen_bpkb']['error'] === UPLOAD_ERR_OK) {
    $fileInfo = pathinfo($_FILES['dokumen_bpkb']['name']);
    $fileExt = strtolower($fileInfo['extension']);
    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (in_array($fileExt, $allowedExt) && $_FILES['dokumen_bpkb']['size'] <= 2097152) {
        $newFileName = 'bpkb_' . uniqid() . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['dokumen_bpkb']['tmp_name'], $uploadPath)) {
            $dokumen_bpkb = 'uploads/kendaraan/' . $newFileName;
        }
    }
}

// Insert to database
$sql = "INSERT INTO tb_kendaraan (
    nomor_polisi, nama_kendaraan, merk, tipe, tahun, warna,
    nomor_rangka, nomor_mesin, nomor_bpkb,
    masa_berlaku_stnk, masa_berlaku_pajak, masa_berlaku_asuransi,
    status_operasional, foto_kendaraan, dokumen_stnk, dokumen_bpkb, keterangan
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $koneksi->prepare($sql);

if ($stmt) {
    $stmt->bind_param(
        'ssssissssssssssss',
        $nomor_polisi,
        $nama_kendaraan,
        $merk,
        $tipe,
        $tahun,
        $warna,
        $nomor_rangka,
        $nomor_mesin,
        $nomor_bpkb,
        $masa_berlaku_stnk,
        $masa_berlaku_pajak,
        $masa_berlaku_asuransi,
        $status_operasional,
        $foto_kendaraan,
        $dokumen_stnk,
        $dokumen_bpkb,
        $keterangan
    );
    
    if ($stmt->execute()) {
        $stmt->close();
        $koneksi->close();
        header('Location: kendaraan.php?status=created');
        exit;
    } else {
        $stmt->close();
        $koneksi->close();
        header('Location: tambah.php?error=database');
        exit;
    }
} else {
    $koneksi->close();
    header('Location: tambah.php?error=prepare');
    exit;
}
