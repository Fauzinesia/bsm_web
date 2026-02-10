<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: maintenance.php?status=error&message=' . urlencode('ID tidak valid.'));
    exit;
}

try {
    $koneksi->begin_transaction();
    $stmt = $koneksi->prepare('DELETE FROM tb_maintenance WHERE id_maintenance = ?');
    if (! $stmt) throw new Exception('Gagal menyiapkan query.');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $koneksi->commit();
    header('Location: maintenance.php?status=success&message=' . urlencode('Data maintenance berhasil dihapus.'));
    exit;
} catch (Throwable $e) {
    $koneksi->rollback();
    header('Location: maintenance.php?status=error&message=' . urlencode('Gagal menghapus data: ' . $e->getMessage()));
    exit;
}