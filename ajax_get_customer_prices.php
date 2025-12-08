<?php
require_once __DIR__ . '/helper.php';
require_login();
$cid = intval($_GET['customer_id'] ?? 0);
$stmt = pdo()->prepare("SELECT item_id, price FROM customer_item_price WHERE customer_id = ?");
$stmt->execute([$cid]);
$res = [];
while($r = $stmt->fetch()) {
  $res[$r['item_id']] = (int)$r['price'];
}
header('Content-Type: application/json');
echo json_encode($res);
