<?php
session_start();
require 'settings.php';

// Check authentication
$uid = $_SESSION['user_id'] ?? null;
if (!$uid) {
    header('Location: login.php');
    exit('Login required');
}

if (empty($_SESSION['checkout_data'])) {
    header('Location: cart.php');
    exit('Please complete checkout form');
}

$checkoutData = $_SESSION['checkout_data'];

$fulfill = $checkoutData['fulfillment'] ?? '';
$pay = $checkoutData['payment_method'] ?? '';

// Validate required fields
if (empty($fulfill) || empty($pay)) {
    unset($_SESSION['checkout_data']);
    $_SESSION['error'] = 'Missing required order information. Please try again.';
    header('Location: cart.php');
    exit;
}

// Validate fulfillment and payment method
$valid_fulfillment = ['delivery', 'pickup'];
$valid_payment = ['card', 'cash'];

if (!in_array($fulfill, $valid_fulfillment, true) || !in_array($pay, $valid_payment, true)) {
    unset($_SESSION['checkout_data']);
    $_SESSION['error'] = 'Invalid order options selected.';
    header('Location: cart.php');
    exit;
}

// Extract delivery/pickup fields based on fulfillment type
$dn = $dp = $dc = $dd = $da = null;
$pn = $pp = $pb = null;

if ($fulfill === 'delivery') {
    $dn = $checkoutData['deliver_name'] ?? null;
    $dp = $checkoutData['deliver_phone'] ?? null;
    $dc = $checkoutData['deliver_city'] ?? null;
    $dd = $checkoutData['deliver_district'] ?? null;
    $da = $checkoutData['deliver_address'] ?? null;
    
    if (empty($dn) || empty($dp) || empty($dc) || empty($dd) || empty($da)) {
        unset($_SESSION['checkout_data']);
        $_SESSION['error'] = 'Missing delivery information.';
        header('Location: cart.php');
        exit;
    }
} elseif ($fulfill === 'pickup') {
    $pn = $checkoutData['pickup_name'] ?? null;
    $pp = $checkoutData['pickup_phone'] ?? null;
    $pb = isset($checkoutData['pickup_branch']) ? (int)$checkoutData['pickup_branch'] : null;
    
    // Validate pickup fields are not empty
    if (empty($pn) || empty($pp) || empty($pb) || $pb < 1) {
        unset($_SESSION['checkout_data']);
        $_SESSION['error'] = 'Missing pickup information.';
        header('Location: cart.php');
        exit;
    }
}

// Database connection
$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    error_log('DB connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    echo "Sorry, we’re having some technical difficulties. Please try again later.";
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');
mysqli_begin_transaction($conn);


function deductInventory(mysqli $conn, string $sku, int $branchId, int $qty): void {
    $stmt = $conn->prepare(
        "UPDATE LC_inventory
            SET quantity = quantity - ?
          WHERE product_sku = ?
            AND branch_id = ?
            AND quantity >= ?"
    );
    if (!$stmt) {
        throw new Exception("Inv prepare failed: " . $conn->error);
    }
    // no negative
    $stmt->bind_param('issi', $qty, $sku, $branchId, $qty);
    if (! $stmt->execute() || $stmt->affected_rows === 0) {
        exit("Not enough stock for SKU $sku at branch $branchId");
    }
    $stmt->close();
}
try {
    //  Fetch cart items + detect Rx 
    $stmt = $conn->prepare("
        SELECT ci.sku, ci.quantity, p.price, p.requires_prescription
        FROM LC_cart_items ci
        JOIN LC_carts c ON ci.cart_id = c.cart_id
        JOIN LC_products p ON ci.sku = p.sku
        WHERE c.user_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($conn));
    }
    
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $needsRx = false;
    $lines = [];
    
    while ($r = $res->fetch_assoc()) {
        if ($r['requires_prescription']) {
            $needsRx = true;
        }
        $lines[] = $r;
    }
    $stmt->close();
    
    
    if (empty($lines)) {
        throw new Exception('Cart is empty');
    }
    
    
    $totalCost = 0.0;
    foreach ($lines as $l) {
        $totalCost += $l['price'] * $l['quantity'];
    }
    // Fetch the loyalty account_id by user_id
    $stmt = $conn->prepare("SELECT account_id FROM LC_loyaltyaccount WHERE user_id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->bind_result($acctId);
    if (! $stmt->fetch()) {
        // Create loyalty account
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO LC_loyaltyaccount (user_id) VALUES (?)");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $acctId = $stmt->insert_id;
    }
    $stmt->close();
       
    $redeem       = !empty($_SESSION['redeem_points']);
    $currentPoints = 0;
    if ($redeem) {
            $stmt = $conn->prepare("SELECT points FROM LC_loyaltyaccount WHERE user_id = ?");
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $stmt->bind_result($currentPoints);
            $stmt->fetch();
            $stmt->close();
    }
    // If user chose to redeem, calculate discount and deduct
    if ($redeem && $currentPoints > 0) {
        // 1 point = $0.01
        $maxRedeemDollars = $currentPoints * 0.01;
        $discount = min($maxRedeemDollars, $totalCost);
        $totalCost -= $discount;
        $pointsUsed = (int) round($discount / 0.01);
    }

    // Extract last 4 digits of card if paying by card
    $cardLast4 = null;
    if ($pay === 'card' && !empty($checkoutData['card_number'])) {
        $digits = preg_replace('/\D/','', $checkoutData['card_number']);
        $cardLast4 = substr($digits, -4);
    }

    // Deduct points if user redeemed
    if ($pointsUsed > 0) {
        $stmt = $conn->prepare("
          UPDATE LC_loyaltyaccount
            SET points = points - ?
          WHERE account_id = ?
        ");
        $stmt->bind_param('ii', $pointsUsed, $acctId);
        $stmt->execute();
        $stmt->close();
    }

    // Add earned points
    $pointsEarned = (int) floor($totalCost * 0.05);
    if ($pointsEarned > 0) {
        $stmt = $conn->prepare("
          UPDATE LC_loyaltyaccount
            SET points = points + ?
          WHERE account_id = ?
        ");
        $stmt->bind_param('ii', $pointsEarned, $acctId);
        $stmt->execute();
        $stmt->close();
    }
    // Insert order header
    $stmt = $conn->prepare("
    INSERT INTO LC_orders
      (user_id, status, fulfillment, payment_method,
       deliver_name, deliver_phone, deliver_city, deliver_dist, deliver_addr,
       pickup_name, pickup_phone, pickup_branch,
       total_cost, card_last4)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($conn));
    }
    
    // Initial status pending
    $status = 'pending';
    $stmt->bind_param(
    'isssssssssiisd',
    $uid,
    $status,
    $fulfill,
    $pay,
    $dn, $dp, $dc, $dd, $da,
    $pn, $pp, $pb,
    $totalCost,
    $cardLast4
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create order: ' . $stmt->error);
    }
    
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert order lines 
    $stmt = $conn->prepare("
        INSERT INTO LC_order_lines
          (order_id, sku, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($conn));
    }
    
    foreach ($lines as $l) {
        $stmt->bind_param('isid',
            $order_id,
            $l['sku'],
            $l['quantity'],
            $l['price']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to add order item: ' . $stmt->error);
        }

        $branchId = ($fulfill==='pickup') 
                ? $pb            // user’s chosen pickup branch
                : 1;             
        deductInventory($conn, $l['sku'], $branchId, (int)$l['quantity']);
    }
    $stmt->close();

    // Set status waiting_prescription / processing 
    $newStatus = $needsRx ? 'waiting_prescription' : 'processing';
    $stmt = $conn->prepare("UPDATE LC_orders SET status = ? WHERE order_id = ?");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($conn));
    }
    
    $stmt->bind_param('si', $newStatus, $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update order status: ' . $stmt->error);
    }
    $stmt->close();

    // Clear cart
    $stmt = $conn->prepare("
        DELETE ci
        FROM LC_cart_items ci
        JOIN LC_carts c ON ci.cart_id = c.cart_id
        WHERE c.user_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($conn));
    }
    
    $stmt->bind_param('i', $uid);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to clear cart: ' . $stmt->error);
    }
    $stmt->close();

    mysqli_commit($conn);
    unset($_SESSION['checkout_data']);
    unset($_SESSION['redeem_points']);
    
    $_SESSION['success'] = "Order #$order_id created successfully!";

    // Redirect based on prescription requirement
    if ($needsRx) {
        header("Location: upload_prescription.php?order_id=$order_id");
    } else {
        header("Location: processing_order.php?order_id=$order_id");
    }
    exit;
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    
    // Log error (in production, use proper logging)
    error_log("Order creation failed for user $uid: " . $e->getMessage());
    
    // Set user-friendly error message
    $_SESSION['error'] = 'Order failed to process. Please try again or contact support.';
    
    // Redirect back to cart
    header('Location: cart.php');
    exit;
    
} finally {
    mysqli_close($conn);
}
?>