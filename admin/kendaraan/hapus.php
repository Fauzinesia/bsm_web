<?php
session_start();

if (! isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: kendaraan.php?status=error');
    exit;
}

try {
    $koneksi->begin_transaction();
    $stmt = $koneksi->prepare('DELETE FROM tb_kendaraan WHERE id_kendaraan = ?');
    if (! $stmt) {
        throw new Exception('Gagal menyiapkan query.');
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        $koneksi->commit();
        header('Location: kendaraan.php?status=deleted');
        exit;
    } else {
        $koneksi->rollback();
        header('Location: kendaraan.php?status=error');
        exit;
    }
} catch (Throwable $e) {
    $koneksi->rollback();
    header('Location: kendaraan.php?status=error');
    exit;
}

