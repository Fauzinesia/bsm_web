<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kendaraan.php');
    exit;
}

// Validasi ID
if (!isset($_POST['id_kendaraan']) || empty($_POST['id_kendaraan'])) {
    header('Location: kendaraan.php?error=invalid_id');
    exit;
}

$id_kendaraan = (int)$_POST['id_kendaraan'];

// Validasi input required
$nomor_polisi = trim($_POST['nomor_polisi'] ?? '');
$nama_kendaraan = trim($_POST['nama_kendaraan'] ?? '');

if ($nomor_polisi === '' || $nama_kendaraan === '') {
    header("Location: edit.php?id=$id_kendaraan&error=required");
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

// Get old file names
$foto_kendaraan = $_POST['foto_kendaraan_lama'] ?? null;
$dokumen_stnk = $_POST['dokumen_stnk_lama'] ?? null;
$dokumen_bpkb = $_POST['dokumen_bpkb_lama'] ?? null;

// Handle file uploads
$uploadDir = dirname(__DIR__, 2) . '/uploads/kendaraan/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Upload foto kendaraan (replace old one if exists)
if (isset($_FILES['foto_kendaraan']) && $_FILES['foto_kendaraan']['error'] === UPLOAD_ERR_OK) {
    $fileInfo = pathinfo($_FILES['foto_kendaraan']['name']);
    $fileExt = strtolower($fileInfo['extension']);
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($fileExt, $allowedExt) && $_FILES['foto_kendaraan']['size'] <= 2097152) {
        $newFileName = 'foto_' . uniqid() . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['foto_kendaraan']['tmp_name'], $uploadPath)) {
            // Delete old file
            if (!empty($foto_kendaraan) && file_exists(dirname(__DIR__, 2) . '/' . $foto_kendaraan)) {
                unlink(dirname(__DIR__, 2) . '/' . $foto_kendaraan);
            }
            $foto_kendaraan = 'uploads/kendaraan/' . $newFileName;
        }
    }
}

// Upload dokumen STNK (replace old one if exists)
if (isset($_FILES['dokumen_stnk']) && $_FILES['dokumen_stnk']['error'] === UPLOAD_ERR_OK) {
    $fileInfo = pathinfo($_FILES['dokumen_stnk']['name']);
    $fileExt = strtolower($fileInfo['extension']);
    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (in_array($fileExt, $allowedExt) && $_FILES['dokumen_stnk']['size'] <= 2097152) {
        $newFileName = 'stnk_' . uniqid() . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['dokumen_stnk']['tmp_name'], $uploadPath)) {
            // Delete old file
            if (!empty($dokumen_stnk) && file_exists(dirname(__DIR__, 2) . '/' . $dokumen_stnk)) {
                unlink(dirname(__DIR__, 2) . '/' . $dokumen_stnk);
            }
            $dokumen_stnk = 'uploads/kendaraan/' . $newFileName;
        }
    }
}

// Upload dokumen BPKB (replace old one if exists)
if (isset($_FILES['dokumen_bpkb']) && $_FILES['dokumen_bpkb']['error'] === UPLOAD_ERR_OK) {
    $fileInfo = pathinfo($_FILES['dokumen_bpkb']['name']);
    $fileExt = strtolower($fileInfo['extension']);
    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (in_array($fileExt, $allowedExt) && $_FILES['dokumen_bpkb']['size'] <= 2097152) {
        $newFileName = 'bpkb_' . uniqid() . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['dokumen_bpkb']['tmp_name'], $uploadPath)) {
            // Delete old file
            if (!empty($dokumen_bpkb) && file_exists(dirname(__DIR__, 2) . '/' . $dokumen_bpkb)) {
                unlink(dirname(__DIR__, 2) . '/' . $dokumen_bpkb);
            }
            $dokumen_bpkb = 'uploads/kendaraan/' . $newFileName;
        }
    }
}

// Update database
$sql = "UPDATE tb_kendaraan SET 
    nomor_polisi = ?, 
    nama_kendaraan = ?, 
    merk = ?, 
    tipe = ?, 
    tahun = ?, 
    warna = ?,
    nomor_rangka = ?, 
    nomor_mesin = ?, 
    nomor_bpkb = ?,
    masa_berlaku_stnk = ?, 
    masa_berlaku_pajak = ?, 
    masa_berlaku_asuransi = ?,
    status_operasional = ?, 
    foto_kendaraan = ?, 
    dokumen_stnk = ?, 
    dokumen_bpkb = ?, 
    keterangan = ?
WHERE id_kendaraan = ?";

$stmt = $koneksi->prepare($sql);

if ($stmt) {
    $stmt->bind_param(
        'ssssissssssssssssi',
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
        $keterangan,
        $id_kendaraan
    );
    
    if ($stmt->execute()) {
        $stmt->close();
        $koneksi->close();
        header("Location: edit.php?id=$id_kendaraan&status=updated");
        exit;
    } else {
        $stmt->close();
        $koneksi->close();
        header("Location: edit.php?id=$id_kendaraan&error=database");
        exit;
    }
} else {
    $koneksi->close();
    header("Location: edit.php?id=$id_kendaraan&error=prepare");
    exit;
}
