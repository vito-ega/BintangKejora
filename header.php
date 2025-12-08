<?php
// header.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helper.php';
$user = current_user();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Invoice App - Bintang Kejora 88</title>
  <base href="/BintangKejora/">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link href="assets/css/poppins.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>






<style>
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f8f9fa;
}
.sidebar {
  width: 250px;
  background: #fff;
  border-right: 1px solid #ddd;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  overflow-y: auto;
  z-index: 1030;
}

/* page content margin is handled in assets/css/style.css */

  .quick-action a {
    transition: transform 0.2s ease, color 0.2s ease;
  }
  .quick-action a:hover {
    transform: translateY(-4px);
    color: #0d6efd;
  }

</style>

</head>
<body class="preload">
<div class="d-flex" id="wrapper">
  <?php if ($user): ?>
  <div id="sidebar-wrapper" class="sidebar bg-white border-end">
    <?php include 'navigation.php'; ?>
  </div>
  <div id="page-content-wrapper" class="w-100">
    <nav class="navbar navbar-light bg-white border-bottom">
      <div class="container-fluid">
  <!-- menu-toggle moved into sidebar for better placement -->
        <div class="ms-auto">
          <?=htmlspecialchars($user['fullname'] ?? $user['username'])?> - <?=htmlspecialchars($user['role_name'])?> 
        </div>
      </div>
    </nav>
    <?php endif; ?>
    
    

    <main class="container-fluid p-4">
      <?php if ($msg = flash_get('success')): ?>
        <div class="alert alert-success"><?=$msg?></div>
      <?php endif; ?>
      <?php if ($msg = flash_get('error')): ?>
        <div class="alert alert-danger"><?=$msg?></div>
      <?php endif; ?>
