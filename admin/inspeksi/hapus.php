<?php
session_start();
if (! isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
require_once dirname(__DIR__, 2) . '/config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $koneksi->prepare('DELETE FROM tb_inspeksi WHERE id_inspeksi = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}
session_write_close();
header('Location: inspeksi.php?status=deleted');
exit;

