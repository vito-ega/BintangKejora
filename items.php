<?php
require_login();

// Pagination & search (server-side)
$perPage = 20;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

if ($q !== '') {
  $like = "%" . $q . "%";
  $countStmt = pdo()->prepare("SELECT COUNT(*) FROM items WHERE name LIKE :q OR price LIKE :q OR id LIKE :q");
  $countStmt->execute([':q' => $like]);
  $total = $countStmt->fetchColumn();

  $stmt = pdo()->prepare("SELECT * FROM items WHERE name LIKE :q OR price LIKE :q OR id LIKE :q ORDER BY name LIMIT :limit OFFSET :offset");
  $stmt->bindValue(':q', $like, PDO::PARAM_STR);
} else {
  $countStmt = pdo()->query("SELECT COUNT(*) FROM items");
  $total = $countStmt->fetchColumn();

  $stmt = pdo()->prepare("SELECT * FROM items ORDER BY name LIMIT :limit OFFSET :offset");
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

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
    <a href="item_form" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Tambah Barang</a>
    <a href="items_export.php" class="btn btn-primary"><i class="fas fa-file-excel me-2"></i> Export Excel</a>
    <button id="openExcelModal" class="btn btn-primary"><i class="fas fa-file-excel me-2"></i> Import Excel</button> </div>
  </div>

    <div class="mb-3 col-md-4">
      <div class="input-group">
        <span class="input-group-text">
          <i class="fas fa-search"></i>
        </span>
        <input type="text" id="itemSearch" name="q" class="form-control" placeholder="Cari nama barang..." value="<?=htmlspecialchars($q)?>">
      </div>
  </div>

    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Nama</th><th>Harga</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=$r['id']?></td>
          <td><?=htmlspecialchars($r['name'])?></td>
          <td><?=number_format($r['price'],0,',','.')?></td>
          <td>
            <a href="item_form?id=<?=$r['id']?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
            <a href="item_prices?id=<?=$r['id']?>" class="btn btn-sm btn-info"><i class="fas fa-chart-bar"></i></a>
            <?php if(is_admin()): ?>
              <a href="item_delete.php?id=<?=$r['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="fas fa-trash-alt"></i></a>
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
        // build base url with q preserved using pretty route (/items)
        $baseUrl = 'items';
        $qs = [];
        if($q !== '') $qs['q'] = $q;

        $buildLink = function($p) use ($baseUrl, $qs) {
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
        // show window of pages
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        for($p = $start; $p <= $end; $p++):
        ?>
          <li class="page-item <?= $p == $page ? 'active' : '' ?>"><a class="page-link" href="<?= $buildLink($p) ?>"><?= $p ?></a></li>
        <?php endfor; ?>

        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page >= $totalPages ? '#' : $buildLink($next) ?>">Next</a>
        </li>
      </ul>
    </nav>
  </div>
</div>

    </main> <!-- end main -->
  </div> <!-- page content -->
</div> <!-- wrapper -->


<!-- Modal -->
<div class="modal fade" id="excelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body p-0" id="excelModalContent">
        <!-- form akan dimuat di sini -->
        <form action="items_import_process.php" method="post" enctype="multipart/form-data" class="p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Import Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <hr>

          <div class="mt-2 mb-5">
            <label for="excelFile" class="form-label">Upload file Excel (.xlsx / .xls)</label>
            <input type="file" class="form-control" id="excelFile" name="file" accept=".xls,.xlsx" required>
            <div class="form-text text-muted fst-italic">Pastikan file berisi kolom yang sesuai (name, price). Maks 2MB.</div>
          </div>

              <hr>

          <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-file-import me-2"></i> Import</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


<script>
// Debounced search: redirect to server with q param
(function(){
  const input = document.getElementById('itemSearch');
  let timeout = null;
  if(input){
    input.addEventListener('input', function(){
      clearTimeout(timeout);
      timeout = setTimeout(()=>{
        const q = input.value.trim();
        const url = 'items?page=1' + (q ? '&q=' + encodeURIComponent(q) : '');
        window.location = url;
      }, 450);
    });
    // submit on Enter immediately
    input.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); clearTimeout(timeout); input.dispatchEvent(new Event('input')); }});
  }

  document.getElementById('openExcelModal').addEventListener('click', function () {
    var excelModal = new bootstrap.Modal(document.getElementById('excelModal'));
    excelModal.show();
  });
})();
</script>

<?php
include 'footer.php';
?>