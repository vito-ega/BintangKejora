<?php
require_login();

$id = intval($_GET['id'] ?? 0);

// =========================
// AMBIL TRANSACTION HEADER
// =========================
$stmt = pdo()->prepare("
  SELECT 
    t.*, 
    c.name AS customer_name,
    c.address,
    u.fullname
  FROM transactions t
  JOIN customers c ON t.customer_id = c.id
  JOIN users u ON t.user_id = u.id
  WHERE t.id = ?
");
$stmt->execute([$id]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trx) {
  echo "Transaction tidak ditemukan";
  exit;
}

// =========================
// AMBIL TRANSACTION ITEMS
// =========================
$stmtItems = pdo()->prepare("
  SELECT 
    ti.*, 
    i.name
  FROM transaction_items ti
  JOIN items i ON ti.item_id = i.id
  WHERE ti.transaction_id = ?
");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <div class="card-body">

    <h5>Transaction <?= htmlspecialchars($trx['transaction_no']) ?></h5>

    <div class="mb-3">
      <strong>Customer:</strong> <?= htmlspecialchars($trx['customer_name']) ?><br>
      <strong>Tanggal:</strong> <?= $trx['created_at'] ?><br>
      <strong>Created by:</strong> <?= htmlspecialchars($trx['fullname']) ?>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Item</th>
          <th>Qty</th>
          <th>Harga</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= htmlspecialchars($it['item_name'] ?? $it['name']) ?></td>
            <td><?= (int)$it['qty'] ?></td>
            <td>Rp. <?= number_format($it['price'], 0, ',', '.') ?></td>
            <td>Rp. <?= number_format($it['qty'] * $it['price'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>

        <tr class="fw-bold">
          <td colspan="3" class="text-end" style="border-top:2px solid #0000001e;">
            Total
          </td>
          <td style="border-top:2px solid #0000001e;">
            Rp. <?= number_format($trx['total'], 0, ',', '.') ?>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="mt-3">
      <!-- kalau nanti mau PDF khusus transaction -->
      <!-- <a href="transaction_pdf?id=<?= $trx['id'] ?>" class="btn btn-primary">Download PDF</a> -->

      <a href="transactions" class="btn btn-secondary">Kembali ke Transactions</a>
    </div>

  </div>
</div>

<?php include 'footer.php'; ?>
