<?php
require_once 'config.php';
require_once 'helper.php';

if (is_logged_in()) {
    $stmt = pdo()->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

setcookie('remember_me', '', time() - 3600, '/');
session_destroy();
header('Location: ' . $base_url . 'login');
exit;
?>
