<?php
require_once 'helper.php';
require_once 'db.php'; // pastikan ada koneksi pdo()

$customer_id = $_POST['customer_id'] ?? null;
$item_id = $_POST['item_id'] ?? null;
$special_price = $_POST['special_price'] ?? null;

if (!$customer_id || !$item_id) {
  echo json_encode(['success' => false]);
  exit;
}

// Cek apakah sudah ada record
$stmt = pdo()->prepare("SELECT id FROM customer_item_price WHERE customer_id=? AND item_id=?");
$stmt->execute([$customer_id, $item_id]);
$existing = $stmt->fetch();

if ($existing) {
  // Update
  $stmt = pdo()->prepare("UPDATE customer_item_price SET price=? WHERE id=?");
  $stmt->execute([$special_price ?: null, $existing['id']]);
} else {
  // Insert baru
  $stmt = pdo()->prepare("INSERT INTO customer_item_price (customer_id, item_id, price) VALUES (?, ?, ?)");
  $stmt->execute([$customer_id, $item_id, $special_price ?: null]);
}

echo json_encode(['success' => true]);
