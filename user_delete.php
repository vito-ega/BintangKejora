<?php
require_once 'config.php';
require_once 'helper.php';
require_login();

$id = $_GET['id'] ?? 0;

// Jangan izinkan user menghapus dirinya sendiri
if ($id == $_SESSION['user_id']) {
    flash_set('error', 'Tidak bisa menghapus akun Anda sendiri.');
    header('Location: ' . $base_url . 'users');
    exit;
}

$stmt = pdo()->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$id]);

flash_set('success', 'User berhasil dihapus.');
header('Location: ' . $base_url . 'users');
exit;
