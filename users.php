<?php
require_login();
require_admin();

// Pagination & search
$perPage = 20;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

if ($q !== '') {
  $like = "%" . $q . "%";
  $countStmt = pdo()->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username LIKE :q OR u.fullname LIKE :q");
  $countStmt->execute([':q' => $like]);
  $total = $countStmt->fetchColumn();

  $stmt = pdo()->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username LIKE :q OR u.fullname LIKE :q ORDER BY u.username LIMIT :limit OFFSET :offset");
  $stmt->bindValue(':q', $like, PDO::PARAM_STR);
} else {
  $countStmt = pdo()->query("SELECT COUNT(*) FROM users");
  $total = $countStmt->fetchColumn();

  $stmt = pdo()->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.username LIMIT :limit OFFSET :offset");
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
        <h4>Daftar Users</h4>
        <p class="fst-italic text-muted small">Daftar seluruh akun yang bisa login</p>
      </div>
      <a href="user_form" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Tambah User</a>
    </div>

    <div class="mb-3 col-md-4">
      <div class="input-group">
        <span class="input-group-text">
          <i class="fas fa-search"></i>
        </span>
        <input type="text" id="userSearch" name="q" class="form-control" placeholder="Cari username..." value="<?=htmlspecialchars($q)?>">
      </div>
  </div>

    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Username</th><th>Fullname</th><th>Role</th><th>Active</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=$r['id']?></td>
          <td><?=htmlspecialchars($r['username'])?></td>
          <td><?=htmlspecialchars($r['fullname'])?></td>
          <td><?=htmlspecialchars($r['role_name'])?></td>
          <td><?=$r['is_active']? 'Yes':'No'?></td>
          <td>
            <a href="user_form?id=<?=$r['id']?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
            <a href="user_delete.php?id=<?=$r['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="fas fa-trash-alt"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        <?php
        $baseUrl = 'users';
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


<script>
// Debounced search redirect for users
(function(){
  const input = document.getElementById('userSearch');
  let timeout = null;
  if(input){
    input.addEventListener('input', function(){
      clearTimeout(timeout);
      timeout = setTimeout(()=>{
        const q = input.value.trim();
        const url = 'users?page=1' + (q ? '&q=' + encodeURIComponent(q) : '');
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