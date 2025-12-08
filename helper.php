<?php
require_once __DIR__ . '/db.php';
if(session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    list($user_id, $token) = explode(':', $_COOKIE['remember_me']);

    $stmt = pdo()->prepare("SELECT * FROM users WHERE id = ? AND remember_token = ? AND token_expiry > NOW()");
    $stmt->execute([$user_id, $token]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
    } else {
        // cookie tidak valid, hapus
        setcookie('remember_me', '', time() - 3600, '/');
    }
}


function flash_set($key, $message) {
    $_SESSION['flash'][$key] = $message;
}
function flash_get($key) {
    if (isset($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}

function current_user() {
    if (!empty($_SESSION['user_id'])) {
        $stmt = pdo()->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        flash_set('error', 'Silakan login terlebih dulu.');
        header('Location: login');
        exit;
    }
}

function require_admin() {
    $user = current_user();
    if (!$user || $user['role_name'] !== 'admin') {
        http_response_code(403);
        echo "403 Forbidden — hanya admin.";
        exit;
    }
}

function is_admin() {
    $u = current_user();
    return $u && $u['role_name'] === 'admin';
}

function gen_invoice_no() {
    $date = date('Ymd');
    $rand = strtoupper(substr(md5(uniqid()), 0, 4));
    return "INV-{$date}-{$rand}";
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function flash_has($key) {
    return isset($_SESSION['flash'][$key]);
}
