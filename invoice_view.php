<?php
require_login();
$id = intval($_GET['id'] ?? 0);
$stmt = pdo()->prepare("SELECT i.*, c.name as customer_name, c.address, u.fullname FROM invoices i JOIN customers c ON i.customer_id=c.id JOIN users u ON i.user_id=u.id WHERE i.id = ?");
$stmt->execute([$id]);
$inv = $stmt->fetch();
if (!$inv) { echo "Invoice tidak ditemukan"; exit; }

$items = pdo()->prepare("SELECT ii.*, it.name FROM invoice_items ii JOIN items it ON ii.item_id = it.id WHERE ii.invoice_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
?>
<div class="card">
  <div class="card-body">
    <h5>Invoice <?=$inv['invoice_no']?></h5>
    <div class="mb-3">
      <strong>Customer:</strong> <?=htmlspecialchars($inv['customer_name'])?><br>
      <strong>Tanggal:</strong> <?=$inv['created_at']?><br>
      <strong>Created by:</strong> <?=htmlspecialchars($inv['fullname'])?>
    </div>
    <table class="table">
      <thead><tr><th>Item</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
      <tbody>
        <?php foreach($items as $it): ?>
        <tr>
          <td><?=htmlspecialchars($it['item_name'])?></td>
          <td><?=$it['qty']?></td>
          <td>Rp. <?=number_format($it['price'],0,',','.')?></td>
          <td>Rp. <?=number_format($it['qty'] * $it['price'],0,',','.')?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="fw-bold">
          <td class="text-end" colspan="3" style="border-top:2px solid #0000001e;">Total</td>
          <td style="border-top:2px solid #0000001e;">Rp. <?=number_format($inv['total'],0,',','.')?></td>
        </tr>
      </tbody>
    </table>
    <div class="mt-3">
      <a href="invoice_pdf?id=<?=$inv['id']?>" class="btn btn-primary">Download PDF</a>
      <a href="invoices_new" class="btn btn-secondary">Buat Invoice Baru</a>
    </div>
  </div>
</div>

<?php
include 'footer.php';
?>