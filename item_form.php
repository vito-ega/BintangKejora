<?php
require_login();
$id = $_GET['id'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name']; $price = intval($_POST['price']);
  if (!empty($_POST['id'])) {
    $stmt = pdo()->prepare("UPDATE items SET name=?, price=? WHERE id=?");
    $stmt->execute([$name,$price,$_POST['id']]);
    flash_set('success','Item diperbarui.');
  } else {
    $stmt = pdo()->prepare("INSERT INTO items (name,price) VALUES (?,?)");
    $stmt->execute([$name,$price]);
    flash_set('success','Item ditambah.');
  }
  header('Location: items'); exit;
}
$item = null;
if ($id) {
  $item = pdo()->prepare("SELECT * FROM items WHERE id = ?");
  $item->execute([$id]);
  $item = $item->fetch();
}
?>
<div class="card">
  <div class="card-body">
    <h5><?= $item ? 'Edit Item' : 'Tambah Item' ?></h5>
    <form method="post" action="<?php echo $base_url; ?>item_form">
      <input type="hidden" name="id" value="<?=htmlspecialchars($item['id'] ?? '')?>">
      <div class="mb-3"><label>Nama</label><input name="name" required class="form-control" value="<?=htmlspecialchars($item['name'] ?? '')?>"></div>
      <div class="mb-3"><label>Harga (IDR)</label><input name="price" type="number" required class="form-control" value="<?=htmlspecialchars($item['price'] ?? 0)?>"></div>
      <button class="btn btn-success"><?= $item ? 'Update' : 'Simpan' ?></button>
      <a href="items" class="btn btn-secondary">Batal</a>
    </form>
  </div>
</div>

<?php
include 'footer.php';
?>