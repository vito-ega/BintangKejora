<?php
require_once 'config.php';
require_once 'helper.php';

if (is_logged_in()) {
    header('Location: ' . $base_url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $stmt = pdo()->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $u = $stmt->fetch();

    if ($u && password_verify($password, $u['password'])) {
        $_SESSION['user_id'] = $u['id'];

        if ($remember) {
            $token = bin2hex(random_bytes(32)); // 64-char secure token
            $expires = date('Y-m-d H:i:s', time() + 86400 * 30); // 30 hari
            $stmt = pdo()->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $u['id']]);
            setcookie('remember_me', $u['id'] . ':' . $token, time() + 86400 * 30, '/', '', true, true);

        }

        flash_set('success','Login sukses.');
        header('Location: ' . $base_url);
        exit;
    } else {
        flash_set('error','Username / password salah.');
        header('Location: ' . $base_url . 'login');
        exit;
    }
}
?>

<style>
/* Remove original body and login-card styling to use the new layout */
body {
    /* 1. Full viewport height and simple background for texturing */
    height: 100vh;
    margin: 0;
    font-family: 'Arial', sans-serif; /* Example font */
    background-color: #212529; /* Dark base color */
}

.login-page-container {
    height: 100vh;
    display: flex; /* Enable flexbox for two-column layout */
    overflow: hidden; /* Prevent scroll */
}

/* -------------------------------------- */
/* 2. Textured Background Effect (on the whole container) */
/* Using a radial gradient and linear gradient for a modern, subtle texture */
.login-page-container {
    background-image: 
        radial-gradient(circle at 100% 100%, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
        radial-gradient(circle at 0% 0%, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
        linear-gradient(135deg, #1c2331 0%, #2b3a4a 100%);
    background-size: 20px 20px, 20px 20px, cover;
}

/* -------------------------------------- */
/* 3. Left Side: Logo/Branding Area (Occupies the Left Space) */
.branding-area {
    flex: 1; /* Takes up the available space on the left */
    display: flex;
    align-items: center;
    justify-content: right;
    padding: 50px;
    text-align: center;
}

.logo-box {
    /* Add some animation or specific styling if needed */
    animation: fadeIn 1s ease-out;
}

/* -------------------------------------- */
/* 4. Right Side: Login Card Wrapper (Holds the card and positions it) */
.login-card-wrapper {
    flex:1;
    height: 100vh;
    display: flex;
    justify-content: center; /* Center horizontally */
    align-items: center; /* Center vertically */
    justify-content: flex-start; /* Align the card to the right edge of its wrapper */
    padding-right: 50px; /* Space from the right edge of the screen */
    padding-left: 50px; /* Space from the left edge of the card */
}

/* -------------------------------------- */
/* 5. Login Card (Transparency and Blur) */
.login-card {
    max-width: 380px;
    width: 100%;
    border: none;
    border-radius: 16px;
    padding: 30px 25px;

    /* Glassmorphism Effect */
    background: rgba(255, 255, 255, 0.15); /* Semi-transparent background */
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    backdrop-filter: blur(10px); /* Apply the background blur */
    -webkit-backdrop-filter: blur(10px); /* For Safari support */
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #fff; /* Ensure text is readable on the dark background */
}

.login-card h4 {
    text-align: center;
    font-weight: 600;
    margin-bottom: 20px;
    color: #fff; /* Keep title white */
}

/* Adjust form elements for the dark/transparent card */
.form-label, .form-check-label {
    font-size: 0.9rem;
    color: #e9ecef; /* Light gray text for labels */
}

.form-control {
    background-color: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #fff;
}

.form-control::placeholder {
    color: #ced4da;
    opacity: 0.7;
}

.form-control:focus {
    background-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); /* Primary color focus glow */
    color: #fff;
}

.btn-login {
    font-weight: 500;
    padding: 10px;
    border: none;
    box-shadow: 1px 2px 6px rgba(0,0,0,0.2);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

.btn-login:hover {
    background-color: #0b5ed7; /* Darker primary on hover */
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.footer-text {
    text-align: center;
    font-size: 0.85rem;
    color: #ced4da; /* Light gray for footer text */
    margin-top: 15px;
}

/* Optional: Animation for a smoother look */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.login-card {
    animation: fadeIn 0.8s ease-out;
}

/* Custom style for the full-screen login page when layout wrappers are skipped */
.login-full-screen {
    /* Ensure it takes up the full viewport height and width */
    min-height: 100vh;
    width: 100vw;
    /* Apply the dark background color from your screenshot */
    background-color: #1c2331; 
    /* Center the login form and logo content */
    display: flex;
    flex-direction: column;
    align-items: center; /* horizontal center */
    justify-content: center; /* vertical center */
    padding: 0; /* Remove default main padding */
}

/* Ensure the body has no conflicting styles for login page */
body {
    /* Reset height to allow login-full-screen to control it */
    min-height: 100vh; 
    overflow: hidden; /* Prevent scrollbars on login page */
}
</style>

<div class="login-page-container">
    <div class="branding-area">
        <div class="logo-box">
            <i class="fa-solid fa-cloud fs-1 mb-3 text-white"></i>
            <h1 class="text-white">Bintang Kejora 88</h1>
            <p class="text-white-50">Welcome, please login to use this application</p>
        </div>
    </div>
    
    <div class="login-card-wrapper">
        <div class="login-card">
            <h4><i class="fa-solid fa-lock me-2 text-primary"></i>Login</h4>

            <?php if (flash_has('error')): ?>
              <div class="alert alert-danger py-2"><?= flash_show('error') ?></div>
            <?php endif; ?>
            <?php if (flash_has('success')): ?>
              <div class="alert alert-success py-2"><?= flash_show('success') ?></div>
            <?php endif; ?>

            <form method="post" action="<?= $base_url ?>login">
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input name="username" required class="form-control" placeholder="Enter username">
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input name="password" type="password" required class="form-control" placeholder="Enter password">
              </div>
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
              </div>
              <button class="btn btn-primary w-100 btn-login">Login</button>
            </form>

            <div class="footer-text">
              <i class="fa-solid fa-circle-info me-1"></i>Use your credentials to login.
            </div>
        </div>
    </div>
</div>