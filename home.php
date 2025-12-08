<?php
require_once 'config.php';
require_once 'helper.php';
require_login();

// Tanggal hari ini (format YYYY-MM-DD)
$today = date('Y-m-d');

// Ambil data invoice hari ini
$stmt = pdo()->prepare("
    SELECT COUNT(*) AS total_invoice, COALESCE(SUM(total), 0) AS total_nilai
    FROM invoices
    WHERE DATE(created_at) = ?
");
$stmt->execute([$today]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Format tampilan rupiah
function rupiah($angka) {
  return 'Rp ' . number_format($angka, 0, ',', '.');
}

$user = current_user();
?>

<div class="mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Welcome, <?= htmlspecialchars($user['fullname'] ?? 'User') ?></h4>
    <small class="text-muted"><?= date('l, d F Y') ?></small>
  </div>

  <div class="row g-3">
    <div class="col-md-6 col-lg-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h6 class="text-muted mb-2">Today Invoice</h6>
          <h3 class="fw-bold mb-0"><?= $summary['total_invoice'] ?></h3>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h6 class="text-muted mb-2">Today Income</h6>
          <h3 class="fw-bold text-success mb-0"><?= rupiah($summary['total_nilai']) ?></h3>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h6 class="text-muted mb-3">Summary Activity</h6>
          <?php if ($summary['total_invoice'] > 0): ?>
            <p class="mb-0">
              Selamat! Hari ini sudah ada transaksi
            </p>
          <?php else: ?>
            <p class="text-muted mb-0">Belum ada invoice yang dibuat hari ini.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

<div class="mt-5 text-center">
  <h5 class="text-muted mb-4">What do you want to do now?</h5>
  <div class="d-flex flex-wrap quick-action justify-content-center gap-4">

    <a href="invoices" class="text-decoration-none text-dark text-center">
      <div class="d-flex flex-column align-items-center">
        <i class="fas fa-file-invoice fa-2x mb-2"></i>
        <span class="fw-semibold">Manage Invoice</span>
      </div>
    </a>

    <a href="items" class="text-decoration-none text-dark text-center">
      <div class="d-flex flex-column align-items-center">
        <i class="fas fa-box-open fa-2x mb-2"></i>
        <span class="fw-semibold">Manage Item</span>
      </div>
    </a>

    <a href="dashboard" class="text-decoration-none text-dark text-center">
      <div class="d-flex flex-column align-items-center">
        <i class="fas fa-chart-line fa-2x mb-2"></i>
        <span class="fw-semibold">View Dashboard</span>
      </div>
    </a>

    <a href="users" class="text-decoration-none text-dark text-center">
      <div class="d-flex flex-column align-items-center">
        <i class="fas fa-users-cog fa-2x mb-2"></i>
        <span class="fw-semibold">User Management</span>
      </div>
    </a>

    <a href="customers" class="text-decoration-none text-dark text-center">
      <div class="d-flex flex-column align-items-center">
        <i class="fas fa-user-friends fa-2x mb-2"></i>
        <span class="fw-semibold">Manage Customer</span>
      </div>
    </a>

  </div>
</div>

</div>

</div>

<?php
include 'footer.php';
?>