<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: pengguna.php?status=error&message=' . urlencode('ID tidak valid'));
    exit;
}

// Cegah menghapus akun yang sedang login
if ($id === (int)($_SESSION['user_id'] ?? 0)) {
    header('Location: pengguna.php?status=error&message=' . urlencode('Tidak dapat menghapus akun sendiri'));
    exit;
}

$stmt = $koneksi->prepare('DELETE FROM tb_pengguna WHERE id_pengguna = ?');
if ($stmt) {
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: pengguna.php?status=deleted');
        exit;
    } else {
        header('Location: pengguna.php?status=error&message=' . urlencode('Gagal menghapus data'));
        exit;
    }
    $stmt->close();
} else {
    header('Location: pengguna.php?status=error&message=' . urlencode('Gagal menyiapkan query'));
    exit;
}