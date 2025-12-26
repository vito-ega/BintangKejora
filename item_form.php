<?php
require_login();

$id = $_GET['id'] ?? null;

// ==========================
// HANDLE SAVE / UPDATE
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $name            = trim($_POST['name'] ?? '');
  $price           = intval($_POST['price'] ?? 0); // harga per unit dasar
  $unit_name       = trim($_POST['unit_name'] ?? 'pcs');
  $unit_multiplier = intval($_POST['unit_multiplier'] ?? 1);

  // basic validation
  if ($unit_multiplier < 1) $unit_multiplier = 1;
  if ($price < 0) $price = 0;

  if (!empty($_POST['id'])) {
    // UPDATE
    $stmt = pdo()->prepare("
      UPDATE items
      SET name = ?, price = ?, unit_name = ?, unit_multiplier = ?
      WHERE id = ?
    ");
    $stmt->execute([
      $name,
      $price,
      $unit_name,
      $unit_multiplier,
      $_POST['id']
    ]);

    flash_set('success', 'Item diperbarui.');
  } else {
    // INSERT
    $stmt = pdo()->prepare("
      INSERT INTO items (name, price, unit_name, unit_multiplier)
      VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
      $name,
      $price,
      $unit_name,
      $unit_multiplier
    ]);

    flash_set('success', 'Item ditambah.');
  }

  header('Location: items');
  exit;
}

// ==========================
// LOAD DATA EDIT
// ==========================
$item = null;
if ($id) {
  $stmt = pdo()->prepare("SELECT * FROM items WHERE id = ?");
  $stmt->execute([$id]);
  $item = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="card">
  <div class="card-body">

    <h5><?= $item ? 'Edit Item' : 'Tambah Item' ?></h5>

    <form method="post" action="<?= $base_url ?>item_form">
      <input type="hidden" name="id"
             value="<?= htmlspecialchars($item['id'] ?? '') ?>">

      <!-- NAMA -->
      <div class="mb-3">
        <label class="form-label">Nama Item</label>
        <input name="name" required class="form-control"
               value="<?= htmlspecialchars($item['name'] ?? '') ?>">
      </div>

      <!-- HARGA DASAR -->
      <div class="mb-3">
        <label class="form-label">
          Harga Dasar (per 1 unit terkecil)
        </label>
        <input name="price" type="number" min="0" required
               class="form-control"
               value="<?= htmlspecialchars($item['price'] ?? 0) ?>">
        <div class="form-text fst-italic">
          Contoh: harga per pcs
        </div>
      </div>

      <!-- SATUAN -->
      <div class="mb-3">
        <label class="form-label">Satuan</label>
        <input name="unit_name" class="form-control"
               placeholder="pcs / dus / box"
               value="<?= htmlspecialchars($item['unit_name'] ?? 'pcs') ?>">
      </div>

      <!-- PENGALI -->
      <div class="mb-3">
        <label class="form-label">Pengali</label>
        <input name="unit_multiplier" type="number" min="1"
               class="form-control"
               value="<?= htmlspecialchars($item['unit_multiplier'] ?? 1) ?>">
        <div class="form-text fst-italic">
          Harga final = harga dasar × pengali
        </div>
      </div>

      <!-- PREVIEW HARGA -->
      <div class="mb-3">
        <label class="form-label">Preview Harga Satuan</label>
        <input type="text" class="form-control" disabled
               id="pricePreview">
      </div>

      <button class="btn btn-success">
        <?= $item ? 'Update' : 'Simpan' ?>
      </button>
      <a href="items" class="btn btn-secondary">Batal</a>

    </form>
  </div>
</div>

<script>
// ==========================
// LIVE PREVIEW HARGA
// ==========================
function updatePricePreview() {
  const price = parseInt(document.querySelector('[name="price"]').value || 0);
  const mult  = parseInt(document.querySelector('[name="unit_multiplier"]').value || 1);
  const unit  = document.querySelector('[name="unit_name"]').value || '';

  const final = price * mult;
  document.getElementById('pricePreview').value =
    'Rp. ' + new Intl.NumberFormat('id-ID').format(final) +
    (unit ? ' / ' + unit : '');
}

document.addEventListener('DOMContentLoaded', function () {
  updatePricePreview();

  document.querySelector('[name="price"]').addEventListener('input', updatePricePreview);
  document.querySelector('[name="unit_multiplier"]').addEventListener('input', updatePricePreview);
  document.querySelector('[name="unit_name"]').addEventListener('input', updatePricePreview);
});
</script>

<?php include 'footer.php'; ?>
