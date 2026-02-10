<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $koneksi->prepare('DELETE FROM tb_biaya_operasional WHERE id_biaya = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            header('Location: operasional.php?status=success&message=' . urlencode('Biaya operasional berhasil dihapus'));
            exit;
        } else {
            header('Location: operasional.php?status=error&message=' . urlencode('Gagal menghapus data'));
            exit;
        }
        $stmt->close();
    } else {
        header('Location: operasional.php?status=error&message=' . urlencode('Gagal menyiapkan query'));
        exit;
    }
} else {
    header('Location: operasional.php?status=error&message=' . urlencode('ID tidak valid'));
    exit;
}