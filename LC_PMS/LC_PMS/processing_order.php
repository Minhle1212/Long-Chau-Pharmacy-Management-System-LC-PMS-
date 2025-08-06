<?php
session_start();
require 'settings.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$order_id = isset($_GET['order_id']) 
    ? (int) $_GET['order_id'] 
    : 0;
    
// Connect to database to fetch order details

$conn = mysqli_connect($host, $user, $password, $database);
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
    
    // Fetch order details
    $uid      = $_SESSION['user_id'];
    $order_query = "SELECT * FROM LC_orders WHERE order_id = $order_id AND user_id=$uid";
    $order_result = mysqli_query($conn, $order_query);
    $order = mysqli_fetch_assoc($order_result);
    
    mysqli_close($conn);
}
if (! $order) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Processing</title>

    <link rel="stylesheet" href="./styles/style.css" class="css">
    <link rel="stylesheet" href="./styles/processing_order.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php 
        include_once("header.inc");
        include_once("cart.inc");
    ?>
    
    <div class="processing-main">
        <div class="processing-success-card">
            <!-- Success Icon -->
            <div class="processing-success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <!-- Main Message -->
            <h1 class="processing-title">
                Thank You for Your Order!
            </h1>
            <p class="processing-subtitle">
                Your order <span class="processing-order-number">#<?= $order_id ?></span> has been successfully placed and is now being processed.
            </p>
            
            <!-- Order Status -->
            <div class="processing-status">
                <div class="processing-status-title">
                    <i class="fas fa-clock"></i>
                    Current Status:
                    <?= ucfirst($order['status'] ?? 'processing') ?>
                </div>
                <div class="processing-status-text">
                    We're preparing your order and will notify you once it's ready for shipment or pickup.
                </div>
            </div>
            
            <!-- Order Details (if available) -->
            <?php if (isset($order) && $order): ?>
            <div class="processing-details">
                <div class="processing-details-title">
                    <i class="fas fa-info-circle"></i> Order Details
                </div>
                <div class="processing-detail-item">
                    <span class="processing-detail-label">Order Number:</span>
                    <span class="processing-detail-value">#<?= $order_id ?></span>
                </div>
                <div class="processing-detail-item">
                    <span class="processing-detail-label">Order Date:</span>
                    <span class="processing-detail-value"><?= date('M d, Y', strtotime($order['order_date'] ?? 'now')) ?></span>
                </div>
                <?php if (isset($order['total_cost'])): ?>
                <div class="processing-detail-item">
                    <span class="processing-detail-label">Total Amount:</span>
                    <span class="processing-detail-value">$<?= number_format($order['total_cost'], 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="processing-detail-item">
                    <span class="processing-detail-label">Status:</span>
                    <span class="processing-detail-value"><?= ucfirst($order['status'] ?? 'processing') ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="processing-actions">
                <a href="products.php" class="processing-btn processing-btn-primary">
                    <span class="fas fa-shopping-bag"></span>
                    Continue Shopping
                </a>
                <a href="cart.php" class="processing-btn processing-btn-secondary">
                    <span class="fas fa-arrow-left"></span>
                    Back to Cart
                </a>
            </div>
        </div>
    </div>
    
    <?php 
        include_once("footer.inc");
    ?>
</body>
<script src="./cart.js"></script>
</html>