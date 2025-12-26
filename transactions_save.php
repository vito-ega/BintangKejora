<?php
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: transactions_new');
  exit;
}

$current = current_user();

$customer_id = intval($_POST['customer_id'] ?? 0);
$items = $_POST['items'] ?? [];

if (!$customer_id || empty($items)) {
  flash_set('error', 'Customer atau item kosong.');
  header('Location: transactions_new');
  exit;
}

$pdo = pdo();
$pdo->beginTransaction();

try {
  // =========================
  // GENERATE TRANSACTION NO
  // =========================
  $transaction_no = gen_transaction_no($customer_id, $pdo);
  $total = 0;

  foreach ($items as $it) {
    $qty   = intval($it['qty'] ?? 0);
    $price = intval(str_replace([',', '.'], '', $it['price'] ?? 0));
    $total += ($qty * $price);
  }

  // =========================
  // INSERT TRANSACTION
  // =========================
  $stmt = $pdo->prepare("
    INSERT INTO transactions (transaction_no, customer_id, user_id, total)
    VALUES (?, ?, ?, ?)
  ");
  $stmt->execute([
    $transaction_no,
    $customer_id,
    $current['id'],
    $total
  ]);

  $transaction_id = $pdo->lastInsertId();

  // =========================
  // PREPARED STATEMENTS
  // =========================
  $stmtInsertItem = $pdo->prepare("
    INSERT INTO transaction_items
      (transaction_id, item_id, qty, price, item_name)
    VALUES (?, ?, ?, ?, ?)
  ");

  $stmtSelectCustPrice = $pdo->prepare("
    SELECT price
    FROM customer_item_price
    WHERE customer_id = ? AND item_id = ?
  ");

  $stmtUpsertCustPrice = $pdo->prepare("
    INSERT INTO customer_item_price (customer_id, item_id, price)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
      price = VALUES(price),
      updated_at = CURRENT_TIMESTAMP
  ");

  // =========================
  // INSERT ITEMS
  // =========================
  foreach ($items as $it) {
    $item_id  = intval($it['item_id']);
    $qty      = intval($it['qty']);
    $price    = intval(str_replace([',', '.'], '', $it['price']));
    $itemName = $it['item_name'] ?? '';

    // simpan snapshot item
    $stmtInsertItem->execute([
      $transaction_id,
      $item_id,
      $qty,
      $price,
      $itemName
    ]);

    // update harga khusus customer jika berubah
    $stmtSelectCustPrice->execute([$customer_id, $item_id]);
    $existing = $stmtSelectCustPrice->fetchColumn();

    if ($existing === false || intval($existing) !== $price) {
      $stmtUpsertCustPrice->execute([$customer_id, $item_id, $price]);
    }
  }

  $pdo->commit();

  flash_set('success', 'Transaction berhasil disimpan.');
  header('Location: transactions');
  exit;

} catch (Exception $e) {
  $pdo->rollBack();
  flash_set('error', 'Gagal menyimpan transaction: ' . $e->getMessage());
  header('Location: transactions_new');
  exit;
}
