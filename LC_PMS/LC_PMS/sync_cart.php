<?php
session_start();

header('Content-Type: application/json');
require 'settings.php';

// Check login
if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Not logged in']);
  exit;
}
$user_id = (int)$_SESSION['user_id'];

// Payload
$payload = json_decode(file_get_contents('php://input'), true);
$items   = $payload['items'] ?? [];

// Connect
$conn = mysqli_connect($host,$user,$password,$database);
if (!$conn) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'DB connect failed']);
  exit;
}
mysqli_set_charset($conn,'utf8mb4');

// Ensure cart header
$stmt = mysqli_prepare($conn,
  "INSERT INTO LC_carts (user_id) VALUES (?)
   ON DUPLICATE KEY UPDATE created_at = created_at"
);
mysqli_stmt_bind_param($stmt,'i',$user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Get cart_id
$cart_id = mysqli_query($conn,
  "SELECT cart_id FROM LC_carts WHERE user_id=$user_id"
)->fetch_object()->cart_id;

// Clear old items
mysqli_query($conn,
  "DELETE FROM LC_cart_items WHERE cart_id=$cart_id"
);

// Insert new items
if (count($items) > 0) {
  $values = [];
  foreach ($items as $it) {
    $sku = mysqli_real_escape_string($conn,$it['sku']);
    $qty = (int)$it['quantity'];
    $values[] = "($cart_id,'$sku',$qty)";
  }
  $sql = "INSERT INTO LC_cart_items (cart_id,sku,quantity) VALUES "
       . implode(',',$values);
  mysqli_query($conn,$sql);
}

mysqli_close($conn);
echo json_encode(['success'=>true]);


