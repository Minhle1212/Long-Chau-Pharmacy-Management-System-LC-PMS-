<?php
session_start();
require 'settings.php';

if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$order_id = (int)$_POST['order_id'];
if (!isset($_FILES['prescription_file'])) {
  die('No file uploaded');
}

// 1) Read the upload
$tmpName = $_FILES['prescription_file']['tmp_name'];
$fileData = file_get_contents($tmpName);
$fileType = $_FILES['prescription_file']['type']; 

// 2) Connect
$conn = mysqli_connect($host,$user,$password,$database);
mysqli_set_charset($conn,'utf8mb4');

// 3) Insert or update BLOB
$stmt = mysqli_prepare($conn, "
  INSERT INTO LC_prescriptions
    (order_id, prescription_data, prescription_type, status)
  VALUES (?, ?, ?, 'uploaded')
  ON DUPLICATE KEY UPDATE
    prescription_data   = VALUES(prescription_data),
    prescription_type   = VALUES(prescription_type),
    status              = 'uploaded'
");
mysqli_stmt_bind_param($stmt, 'ibs', $order_id, $null, $fileType);
// tells MySQL param 2 is blob
mysqli_stmt_send_long_data($stmt, 1, $fileData);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Mark order processing
mysqli_query($conn, "
  UPDATE LC_orders
     SET status = 'waiting_approval'
   WHERE order_id = $order_id
");

mysqli_close($conn);

// Redirect
header("Location: processing_order.php?order_id=$order_id");
exit;
