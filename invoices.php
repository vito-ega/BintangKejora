<?php
require_once 'config.php';
require_login(); // pastikan user login

// Pagination & search
$perPage = 20;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

if ($q !== '') {
  $like = "%" . $q . "%";
  $countStmt = pdo()->prepare("SELECT COUNT(*) FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id WHERE i.invoice_no LIKE :q OR c.name LIKE :q");
  $countStmt->execute([':q' => $like]);
  $total = $countStmt->fetchColumn();

  $stmt = pdo()->prepare("SELECT i.id, i.invoice_no, i.created_at as date, c.name AS customer_name, i.total FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id WHERE i.invoice_no LIKE :q OR c.name LIKE :q ORDER BY i.created_at DESC LIMIT :limit OFFSET :offset");
  $stmt->bindValue(':q', $like, PDO::PARAM_STR);
} else {
  $countStmt = pdo()->query("SELECT COUNT(*) FROM invoices i");
  $total = $countStmt->fetchColumn();

  $stmt = pdo()->prepare("SELECT i.id, i.invoice_no, i.created_at as date, c.name AS customer_name, i.total FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id ORDER BY i.created_at DESC LIMIT :limit OFFSET :offset");
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = $total ? ceil($total / $perPage) : 1;
?>



    <div class="d-flex justify-content-between mb-3 align-items-center">
      <div>
        <h4>Daftar Invoices</h4>
        <p class="fst-italic text-muted small">Daftar seluruh invoice yang pernah dibuat</p>
      </div>
      <a href="invoices_new" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Tambah Invoices</a>
    </div>

    <div class="mb-3 col-md-6">
      <div class="input-group">
        <span class="input-group-text">
          <i class="fas fa-search"></i>
        </span>
        <input type="text" id="invoiceSearch" name="q" class="form-control" placeholder="Cari nomor invoice atau nama customer..." value="<?=htmlspecialchars($q)?>">
      </div>
  </div>

<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>No Invoice</th>
      <th>Date Time</th>
      <th>Customer</th>
      <th>Total Amount</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($invoices as $inv): ?>
      <tr>
        <td><?= htmlspecialchars($inv['invoice_no']) ?></td>
        <td><?= date('d M Y - H:i', strtotime($inv['date'])) ?></td>
        <td><?= htmlspecialchars($inv['customer_name']) ?></td>
        <td>Rp. <?= number_format($inv['total'], 0, ',', '.') ?></td>
        <td>
            <a href="<?= $base_url ?>invoice_view?id=<?= $inv['id'] ?>" class="btn btn-sm btn-info" title="Lihat">
                <i class="fas fa-eye"></i>
            </a>

            <a href="<?= $base_url ?>invoice_pdf?id=<?= $inv['id'] ?>" class="btn btn-sm btn-info" title="Download PDF">
                <i class="fas fa-file-pdf"></i>
            </a> 

            <a href="<?= $base_url ?>invoice_delete?id=<?= $inv['id'] ?>" 
                class="btn btn-sm btn-danger" 
                onclick="return confirm('Yakin hapus invoice ini?')" 
                title="Hapus">
                <i class="fas fa-trash-alt"></i>
            </a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Pagination -->
<nav aria-label="Page navigation">
  <ul class="pagination justify-content-center">
    <?php
    $baseUrl = 'invoices';
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


<script>
// Debounced search redirect for invoices
(function(){
  const input = document.getElementById('invoiceSearch');
  let timeout = null;
  if(input){
    input.addEventListener('input', function(){
      clearTimeout(timeout);
      timeout = setTimeout(()=>{
        const q = input.value.trim();
        const url = 'invoices?page=1' + (q ? '&q=' + encodeURIComponent(q) : '');
        window.location = url;
      }, 350);
    });
    input.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); clearTimeout(timeout); input.dispatchEvent(new Event('input')); }});
  }
})();
</script>


<?php
include 'footer.php';
?>