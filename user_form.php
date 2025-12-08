<?php
require_login();
require_admin();
$id = $_GET['id'] ?? null;
$roles = pdo()->query("SELECT * FROM roles")->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username']; $fullname = $_POST['fullname']; $role_id = intval($_POST['role_id']); $is_active = isset($_POST['is_active'])?1:0;
  if (!empty($_POST['password'])) {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  }
  if (!empty($_POST['id'])) {
    if (!empty($password)) {
      $stmt = pdo()->prepare("UPDATE users SET username=?, fullname=?, role_id=?, is_active=?, password=? WHERE id=?");
      $stmt->execute([$username,$fullname,$role_id,$is_active,$password,$_POST['id']]);
    } else {
      $stmt = pdo()->prepare("UPDATE users SET username=?, fullname=?, role_id=?, is_active=? WHERE id=?");
      $stmt->execute([$username,$fullname,$role_id,$is_active,$_POST['id']]);
    }
    flash_set('success','User diperbarui.');
  } else {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = pdo()->prepare("INSERT INTO users (username,password,fullname,role_id,is_active) VALUES (?,?,?,?,?)");
    $stmt->execute([$username,$password,$fullname,$role_id,$is_active]);
    flash_set('success','User ditambah.');
  }
  header('Location: users'); exit;
}
$user = null;
if ($id) {
  $user = pdo()->prepare("SELECT * FROM users WHERE id = ?");
  $user->execute([$id]);
  $user = $user->fetch();
}
?>
<div class="card">
  <div class="card-body">
    <h5><?= $user ? 'Edit User' : 'Tambah User' ?></h5>
    <form method="post" action="<?php echo $base_url; ?>user_form">
      <input type="hidden" name="id" value="<?=htmlspecialchars($user['id'] ?? '')?>">
      <div class="mb-3"><label>Username</label><input name="username" required class="form-control" value="<?=htmlspecialchars($user['username'] ?? '')?>"></div>
      <div class="mb-3"><label>Fullname</label><input name="fullname" class="form-control" value="<?=htmlspecialchars($user['fullname'] ?? '')?>"></div>
      <div class="mb-3"><label>Role</label>
        <select name="role_id" class="form-select">
          <?php foreach($roles as $r): ?>
            <option value="<?=$r['id']?>" <?=isset($user) && $user['role_id']==$r['id']? 'selected':''?>><?=htmlspecialchars($r['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3"><label>Password <?= $user ? '(kosongkan jika tidak ingin mengganti)' : '' ?></label><input name="password" type="password" class="form-control"></div>
      <div class="form-check mb-3"><input type="checkbox" name="is_active" class="form-check-input" id="ia" <?=isset($user) && $user['is_active']? 'checked':''?>><label class="form-check-label" for="ia">Active</label></div>
      <button class="btn btn-success"><?= $user ? 'Update' : 'Simpan' ?></button>
      <a href="users" class="btn btn-secondary">Batal</a>
    </form>
  </div>
</div>

<?php
include 'footer.php';
?>