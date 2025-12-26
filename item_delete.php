<?php
require_once 'config.php';
require_once 'helper.php';
require_login();

$id = $_GET['id'] ?? 0;

// Pastikan item belum dipakai di invoice manapun
$stmtCheck = pdo()->prepare("SELECT COUNT(*) FROM transaction_items WHERE item_id = ?");
$stmtCheck->execute([$id]);
$count = $stmtCheck->fetchColumn();
if ($count > 0) {
    flash_set('error', 'Item tidak bisa dihapus karena sudah dipakai di invoice.');
    header('Location: ' . $base_url . 'items');
    exit;
}


$stmt = pdo()->prepare("DELETE FROM items WHERE id = ?");
$stmt->execute([$id]);

flash_set('success', 'Item berhasil dihapus.');
header('Location: ' . $base_url . 'items');
exit;
