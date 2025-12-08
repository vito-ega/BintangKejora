<?php
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: invoices_new'); exit;
}
$current = current_user();
$customer_id = intval($_POST['customer_id'] ?? 0);
$items = $_POST['items'] ?? [];
if (!$customer_id || empty($items)) {
  flash_set('error','Customer atau item kosong.');
  header('Location: invoices_new'); exit;
}
$pdo = pdo();
$pdo->beginTransaction();
try {
  $invoice_no = gen_invoice_no();
  $total = 0;
  foreach ($items as $it) {
    $qty = intval($it['qty']);
    $price = intval(str_replace([',', '.'], '', $it['price']));
    $total += ($qty * $price);
  }
  $stmt = $pdo->prepare("INSERT INTO invoices (invoice_no, customer_id, user_id, total) VALUES (?, ?, ?, ?)");
  $stmt->execute([$invoice_no, $customer_id, $current['id'], $total]);
  $invoice_id = $pdo->lastInsertId();

  $stmtInsertItem = $pdo->prepare("INSERT INTO invoice_items (invoice_id, item_id, qty, price, item_name) VALUES (?, ?, ?, ?, ?)");
  $stmtSelectCustPrice = $pdo->prepare("SELECT price FROM customer_item_price WHERE customer_id = ? AND item_id = ?");
  $stmtUpsertCustPrice = $pdo->prepare("INSERT INTO customer_item_price (customer_id, item_id, price) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price), updated_at = CURRENT_TIMESTAMP");

  foreach ($items as $it) {
    $item_id = intval($it['item_id']);
    $qty = intval($it['qty']);
    $price = intval(str_replace([',', '.'], '', $it['price']));
    $itemName = $it['item_name'];
    $stmtInsertItem->execute([$invoice_id, $item_id, $qty, $price , $itemName]);

    $stmtSelectCustPrice->execute([$customer_id, $item_id]);
    $existing = $stmtSelectCustPrice->fetchColumn();
    if ($existing === false || intval($existing) !== $price) {
      $stmtUpsertCustPrice->execute([$customer_id, $item_id, $price]);
    }
  }

  $pdo->commit();
  flash_set('success', 'Invoice tersimpan.');
  header('Location: invoices');
  exit;
} catch (Exception $e) {
  $pdo->rollBack();
  flash_set('error', 'Gagal menyimpan invoice: ' . $e->getMessage());
  header('Location: invoices_new');
  exit;
}
