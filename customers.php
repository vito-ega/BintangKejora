<?php
require_login();

// Pagination & search
$perPage = 20;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

if ($q !== '') {
  $like = "%" . $q . "%";
  $countStmt = pdo()->prepare("SELECT COUNT(*) FROM customers WHERE name LIKE :q OR phone LIKE :q OR email LIKE :q");
  $countStmt->execute([':q' => $like]);
  $total = $countStmt->fetchColumn();

  $stmt = pdo()->prepare("SELECT * FROM customers WHERE name LIKE :q OR phone LIKE :q OR email LIKE :q ORDER BY name LIMIT :limit OFFSET :offset");
  $stmt->bindValue(':q', $like, PDO::PARAM_STR);
} else {
  $countStmt = pdo()->query("SELECT COUNT(*) FROM customers");
  $total = $countStmt->fetchColumn();

  $stmt = pdo()->prepare("SELECT * FROM customers ORDER BY name LIMIT :limit OFFSET :offset");
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
        <h4>Daftar Customers</h4>
        <p class="fst-italic text-muted small">Daftar seluruh customers yang pernah dibuat</p>
      </div>
      <button id="addCustomerBtn" class="btn btn-primary">
<i class="fas fa-plus me-2"></i> Tambah Customer</a>
      </button>
    </div>


    <div class="mb-3 col-md-4">
      <div class="input-group">
        <span class="input-group-text">
          <i class="fas fa-search"></i>
        </span>
        <input type="text" id="customerSearch" name="q" class="form-control" placeholder="Cari nama customer..." value="<?=htmlspecialchars($q)?>">
      </div>
  </div>

    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Nama</th><th>Phone</th><th>Email</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=$r['id']?></td>
          <td><?=htmlspecialchars($r['name'])?></td>
          <td><?=htmlspecialchars($r['phone'])?></td>
          <td><?=htmlspecialchars($r['email'])?></td>
          <td>
          <button class="btn btn-sm btn-info editCustomerBtn" data-id="<?= $r['id'] ?>">
            <i class="fas fa-edit"></i>
          </button> 
           <a href="customer_prices?id=<?=$r['id']?>" class="btn btn-sm btn-info"><i class="fas fa-chart-bar"></i></a>
            <?php if(is_admin()): ?>
              <a href="customer_delete.php?id=<?=$r['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="fas fa-trash-alt"></i></a>
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
        $baseUrl = 'customers';
        $qs = [];
        if($q !== '') $qs['q'] = $q;
        $buildLink = function($p) use ($baseUrl, $qs) {
          $params = $qs; $params['page'] = $p; return $baseUrl . '?' . http_build_query($params);
        };
        $prev = max(1, $page - 1); $next = min($totalPages, $page + 1);
        ?>
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page <= 1 ? '#' : $buildLink($prev) ?>">Previous</a>
        </li>
        <?php $start = max(1, $page - 3); $end = min($totalPages, $page + 3); for($p=$start;$p<=$end;$p++): ?>
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
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body p-0" id="customerModalContent">
        <!-- form akan dimuat di sini -->
      </div>
    </div>
  </div>
</div>




<script>
// Debounced search redirect for customers
(function(){
  const input = document.getElementById('customerSearch');
  let timeout = null;
  if(input){
    input.addEventListener('input', function(){
      clearTimeout(timeout);
      timeout = setTimeout(()=>{
        const q = input.value.trim();
        const url = 'customers?page=1' + (q ? '&q=' + encodeURIComponent(q) : '');
        window.location = url;
      }, 350);
    });
    input.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); clearTimeout(timeout); input.dispatchEvent(new Event('input')); }});
  }
})();

document.getElementById('addCustomerBtn').addEventListener('click', function() {
  fetch('customer_form.php')
    .then(res => res.text())
    .then(html => {
      document.getElementById('customerModalContent').innerHTML = html;
      new bootstrap.Modal(document.getElementById('customerModal')).show();
    });
});

document.querySelectorAll('.editCustomerBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    fetch(`customer_form.php?id=${id}`)
      .then(res => res.text())
      .then(html => {
        document.getElementById('customerModalContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('customerModal')).show();
      });
  });
});

// Tangani submit form di modal (AJAX)
document.addEventListener('submit', function (e) {
  if (e.target && e.target.id === 'customerForm') {
    e.preventDefault(); // cegah reload halaman

    const form = e.target;
    const formData = new FormData(form);

    fetch(form.action, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
        modal.hide();
        // Refresh halaman agar daftar customer update
        location.reload();

      } else {
        alert('Gagal menyimpan customer.');
      }
    })
    .catch(err => console.error('Error:', err));
  }
});



</script>

<?php
include 'footer.php';
?>

