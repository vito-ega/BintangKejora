<?php
require_login();

// Pagination & search (server-side)
$perPage = 20;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

if ($q !== '') {
  $like = "%" . $q . "%";
  $countStmt = pdo()->prepare("
    SELECT COUNT(*) 
    FROM items 
    WHERE name LIKE :q OR price LIKE :q OR id LIKE :q
  ");
  $countStmt->execute([':q' => $like]);
  $total = $countStmt->fetchColumn();

  $stmt = pdo()->prepare("
    SELECT * 
    FROM items 
    WHERE name LIKE :q OR price LIKE :q OR id LIKE :q
    ORDER BY name 
    LIMIT :limit OFFSET :offset
  ");
  $stmt->bindValue(':q', $like, PDO::PARAM_STR);
} else {
  $countStmt = pdo()->query("SELECT COUNT(*) FROM items");
  $total = $countStmt->fetchColumn();

  $stmt = pdo()->prepare("
    SELECT * 
    FROM items 
    ORDER BY name 
    LIMIT :limit OFFSET :offset
  ");
}

$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = $total ? ceil($total / $perPage) : 1;
?>

<div class="">
  <div class="card-body">

    <div class="d-flex justify-content-between mb-3 align-items-center">
      <div>
        <h4>Daftar Items</h4>
        <p class="fst-italic text-muted small">Daftar seluruh barang yang pernah dibuat</p>
      </div>

      <div class="d-flex gap-2">
        <a href="item_form" class="btn btn-primary">
          <i class="fas fa-plus me-2"></i> Tambah Barang
        </a>
        <a href="items_export.php" class="btn btn-primary">
          <i class="fas fa-file-excel me-2"></i> Export Excel
        </a>
        <button id="openExcelModal" class="btn btn-primary">
          <i class="fas fa-file-excel me-2"></i> Import Excel
        </button>
      </div>
    </div>

    <div class="mb-3 col-md-4">
      <div class="input-group">
        <span class="input-group-text">
          <i class="fas fa-search"></i>
        </span>
        <input type="text" id="itemSearch" name="q"
               class="form-control"
               placeholder="Cari nama barang..."
               value="<?= htmlspecialchars($q) ?>">
      </div>
    </div>

    <table class="table table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama</th>
          <th>Satuan</th>
          <th>Harga Dasar</th>
          <th>Harga Satuan</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): 
          $multiplier = (int)($r['unit_multiplier'] ?? 1);
          $unitName   = $r['unit_name'] ?? 'pcs';
          $basePrice  = (int)$r['price'];
          $finalPrice = $basePrice * $multiplier;
        ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($unitName) ?> (x<?= $multiplier ?>)</td>
          <td>Rp. <?= number_format($basePrice, 0, ',', '.') ?></td>
          <td class="fw-bold">
            Rp. <?= number_format($finalPrice, 0, ',', '.') ?>
          </td>
          <td>
            <a href="item_form?id=<?= $r['id'] ?>" class="btn btn-sm btn-info">
              <i class="fas fa-edit"></i>
            </a>
            <a href="item_prices?id=<?= $r['id'] ?>" class="btn btn-sm btn-info">
              <i class="fas fa-chart-bar"></i>
            </a>
            <?php if (is_admin()): ?>
              <a href="item_delete.php?id=<?= $r['id'] ?>"
                 class="btn btn-sm btn-danger"
                 onclick="return confirm('Hapus?')">
                <i class="fas fa-trash-alt"></i>
              </a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        <?php
        $baseUrl = 'items';
        $qs = [];
        if ($q !== '') $qs['q'] = $q;

        $buildLink = function ($p) use ($baseUrl, $qs) {
          $params = $qs;
          $params['page'] = $p;
          return $baseUrl . '?' . http_build_query($params);
        };

        $prev = max(1, $page - 1);
        $next = min($totalPages, $page + 1);
        ?>
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page <= 1 ? '#' : $buildLink($prev) ?>">Previous</a>
        </li>

        <?php
        $start = max(1, $page - 3);
        $end   = min($totalPages, $page + 3);
        for ($p = $start; $p <= $end; $p++):
        ?>
          <li class="page-item <?= $p == $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= $buildLink($p) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>

        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page >= $totalPages ? '#' : $buildLink($next) ?>">Next</a>
        </li>
      </ul>
    </nav>

  </div>
</div>
