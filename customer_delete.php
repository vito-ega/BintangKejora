<?php
require_once 'config.php';
require_once 'helper.php';
require_login();

$id = $_GET['id'] ?? 0;

//Pastikan customer belum dipakai di invoice manapun
$stmtCheck = pdo()->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id = ?");
$stmtCheck->execute([$id]);
$count = $stmtCheck->fetchColumn();
if ($count > 0) {
    flash_set('error', 'Customer tidak bisa dihapus karena sudah dipakai di invoice.');
    header('Location: ' . $base_url . 'customers');
    exit;
}

$stmt = pdo()->prepare("DELETE FROM customers WHERE id = ?");
$stmt->execute([$id]);

flash_set('success', 'Customer berhasil dihapus.');
header('Location: ' . $base_url . 'customers');
exit;
