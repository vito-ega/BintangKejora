<?php
$user = current_user();
// determine current path (strip query string)
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
// If base href is set to /BintangKejora/, remove it
$base = trim(parse_url((isset($_SERVER['BASE'])?$_SERVER['BASE']:'/BintangKejora/'), PHP_URL_PATH), '/');
if($base && strpos($currentPath, $base) === 0) {
  $currentPath = ltrim(substr($currentPath, strlen($base)), '/');
}

function nav_active($path, $currentPath) {
  // match exact or prefix
  if($path === '') return $currentPath === '' || $currentPath === 'index.php';
  return $currentPath === $path || strpos($currentPath, $path . '/') === 0;
}

?>
<div class="border-end bg-white sidebar-inner" style="min-width:250px">
  <div class="sidebar-heading p-3 d-flex align-items-center justify-content-between">
    <strong>Bintang Kejora 88</strong>
    <button id="menu-toggle-sidebar" class="btn btn-outline-secondary toggle-btn" style="margin-left:6px">
      <i class="fa-solid fa-chevron-left"></i>
    </button>
  </div>
  <div class="list-group list-group-flush">

    <a href="home" class="list-group-item list-group-item-action <?= nav_active('home', $currentPath) ? 'active' : '' ?>">
      <i class="fa-solid fa-house"></i><span class="nav-text">Home</span>
    </a>
    <a href="dashboard" class="list-group-item list-group-item-action <?= nav_active('dashboard', $currentPath) ? 'active' : '' ?>">
      <i class="fa-solid fa-dashboard"></i><span class="nav-text">Dashboard</span>
    </a>

    <a href="invoices" class="list-group-item list-group-item-action <?= nav_active('invoices', $currentPath) ? 'active' : '' ?>">
      <i class="fa-solid fa-file-invoice"></i><span class="nav-text">Invoice</span>
    </a>

      <a href="customers" class="list-group-item list-group-item-action <?= nav_active('customers', $currentPath) ? 'active' : '' ?>">
        <i class="fa-solid fa-user"></i><span class="nav-text">Customer</span>
      </a>
      <a href="items" class="list-group-item list-group-item-action <?= nav_active('items', $currentPath) ? 'active' : '' ?>">
        <i class="fa-solid fa-box"></i><span class="nav-text">Item</span>
      </a>
    

    <?php if($user && $user['role_name'] === 'admin'): ?>
    <a href="users" class="list-group-item list-group-item-action <?= nav_active('users', $currentPath) ? 'active' : '' ?>">
      <i class="fa-solid fa-users-gear"></i><span class="nav-text">User Management</span>
    </a>
    <?php endif; ?>
    <?php if($user): ?>
    <a href="#" 
      class="list-group-item list-group-item-action <?= nav_active('logout', $currentPath) ? 'active' : '' ?>" 
      data-bs-toggle="modal" 
      data-bs-target="#logoutModal">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span class="nav-text">Logout</span>
    </a>
    <?php endif; ?>
  </div>
</div>


