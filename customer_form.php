<?php
require_once 'config.php';
require_once 'helper.php';
require_login();

// Ambil data lama jika mode edit
$customer = null;
if (isset($_GET['id'])) {
    $stmt = pdo()->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';

    if ($name) {
        if ($id) {
            // MODE EDIT
            $stmt = pdo()->prepare("UPDATE customers SET name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $id]);
        } else {
            // MODE TAMBAH
            $stmt = pdo()->prepare("INSERT INTO customers (name, phone, address) VALUES (?, ?, ?)");
            $stmt->execute([$name, $phone, $address]);
            $id = pdo()->lastInsertId();
        }

        if (isset($_GET['from_modal'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'id' => $id, 'name' => $name]);
            exit;
        }

        header('Location: customers');
        exit;
    }
}
?>

<div class="p-4">
  <h5 class="mb-3"><?= $customer ? 'Edit Customer' : 'Tambah Customer' ?></h5>
  <form id="customerForm" method="post" 
        action="customer_form.php?<?= $customer ? 'id=' . $customer['id'] : 'from_modal=1' ?>">

    <?php if ($customer): ?>
      <input type="hidden" name="id" value="<?= htmlspecialchars($customer['id']) ?>">
    <?php endif; ?>

    <div class="mb-3">
      <label class="form-label">Nama Customer</label>
      <input type="text" name="name" class="form-control" required
             value="<?= htmlspecialchars($customer['name'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">No. Telepon</label>
      <input type="text" name="phone" class="form-control"
             value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control"
             value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Alamat</label>
      <textarea name="address" class="form-control"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
    </div>
    <div class="text-end">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      <button type="submit" class="btn btn-primary">Simpan</button>
    </div>
  </form>
</div>
