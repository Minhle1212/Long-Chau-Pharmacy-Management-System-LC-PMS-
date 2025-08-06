<?php
// 0) START SESSION & LOAD CONFIG
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'settings.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$uid = (int)$_SESSION['user_id'];


function sanitizeInput($data) {
    if ($data === null || $data === '') {
        return '';
    }
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Validate phone as 9–11 digits
function validPhone($num) {
    $cleaned = preg_replace('/[^0-9]/', '', $num);
    return preg_match('/^\d{9,11}$/', $cleaned);
}

// Validate text length with proper multibyte support
function validTextLength($text, $minLength) {
    return mb_strlen(trim($text), 'UTF-8') >= $minLength;
}


$fieldErrors = [];
$old = [
    'fulfillment'     => '',
    'deliver_name'    => '',
    'deliver_phone'   => '',
    'deliver_city'    => '',
    'deliver_district'=> '',
    'deliver_address' => '',
    'pickup_name'     => '',
    'pickup_phone'    => '',
    'pickup_branch'   => '',
    'payment_method'  => '',
    'card_number'     => ''
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Sanitize all fields with proper null checking
    $old['fulfillment']     = sanitizeInput($_POST['fulfillment'] ?? '');
    $old['payment_method']  = sanitizeInput($_POST['payment_method'] ?? '');
    $old['card_number']     = preg_replace('/\D/', '', $_POST['card_number'] ?? '');  // strip non-digits
    $old['deliver_name']    = sanitizeInput($_POST['deliver_name'] ?? '');
    $old['deliver_phone']   = sanitizeInput($_POST['deliver_phone'] ?? '');
    $old['deliver_city']    = sanitizeInput($_POST['deliver_city'] ?? '');
    $old['deliver_district']= sanitizeInput($_POST['deliver_district'] ?? '');
    $old['deliver_address'] = sanitizeInput($_POST['deliver_address'] ?? '');
    $old['pickup_name']     = sanitizeInput($_POST['pickup_name'] ?? '');
    $old['pickup_phone']    = sanitizeInput($_POST['pickup_phone'] ?? '');
    
    // Validate and sanitize pickup_branch as integer
    $pickup_branch_raw = $_POST['pickup_branch'] ?? '';
    if (is_numeric($pickup_branch_raw)) {
        $old['pickup_branch'] = (int)$pickup_branch_raw;
    } else {
        $old['pickup_branch'] = NULL;
    }

    // 2) Validate fulfillment & payment with strict whitelist
    $valid_fulfillment = ['delivery', 'pickup'];
    $valid_payment = ['card', 'cash'];
    
    if (!in_array($old['fulfillment'], $valid_fulfillment, true)) {
        $fieldErrors['fulfillment'] = "Please choose Delivery or Pick-Up.";
    }
    if (!in_array($old['payment_method'], $valid_payment, true)) {
        $fieldErrors['payment_method'] = "Please choose a payment method.";
    }

    // 3) Validate based on fulfillment type
    if ($old['fulfillment'] === 'delivery') {
        if (!validTextLength($old['deliver_name'], 3)) {
            $fieldErrors['deliver_name'] = "Delivery name must be at least 3 characters.";
        } elseif (mb_strlen($old['deliver_name'], 'UTF-8') > 100) {
            $fieldErrors['deliver_name'] = "Delivery name cannot exceed 100 characters.";
        }
        
        if (!validPhone($old['deliver_phone'])) {
            $fieldErrors['deliver_phone'] = "Phone number must be 9–11 digits.";
        }
        
        if (!validTextLength($old['deliver_city'], 2)) {
            $fieldErrors['deliver_city'] = "City must be at least 2 characters.";
        } elseif (mb_strlen($old['deliver_city'], 'UTF-8') > 50) {
            $fieldErrors['deliver_city'] = "City name cannot exceed 50 characters.";
        }
        
        if (!validTextLength($old['deliver_district'], 2)) {
            $fieldErrors['deliver_district'] = "District must be at least 2 characters.";
        } elseif (mb_strlen($old['deliver_district'], 'UTF-8') > 50) {
            $fieldErrors['deliver_district'] = "District name cannot exceed 50 characters.";
        }
        
        if (!validTextLength($old['deliver_address'], 5)) {
            $fieldErrors['deliver_address'] = "Street address must be at least 5 characters.";
        } elseif (mb_strlen($old['deliver_address'], 'UTF-8') > 200) {
            $fieldErrors['deliver_address'] = "Street address cannot exceed 200 characters.";
        }
        
    } elseif ($old['fulfillment'] === 'pickup') {
        if (!validTextLength($old['pickup_name'], 3)) {
            $fieldErrors['pickup_name'] = "Pick-up name must be at least 3 characters.";
        } elseif (mb_strlen($old['pickup_name'], 'UTF-8') > 100) {
            $fieldErrors['pickup_name'] = "Pick-up name cannot exceed 100 characters.";
        }
        
        if (!validPhone($old['pickup_phone'])) {
            $fieldErrors['pickup_phone'] = "Phone number must be 9–11 digits.";
        }
        
        if ($old['pickup_branch'] < 1) {
            $fieldErrors['pickup_branch'] = "Please select a valid store branch.";
        }
    }

    if ($old['payment_method'] === 'card') {
        if (!preg_match('/^\d{16}$/', $old['card_number'])) {
            $fieldErrors['card_number'] = "Please enter a valid 16-digit card number.";
        }
    }

    if (empty($fieldErrors)) {
        // Store validated data in session for place_order.php
        $_SESSION['checkout_data'] = $old;
        
        // Store loyalty redemption info if applicable
        if (isset($_POST['redeem_points']) && $_POST['redeem_points'] === '1') {
            $_SESSION['redeem_points'] = true;
        } else {
            $_SESSION['redeem_points'] = false;
        }
        
        header('Location: place_order.php');
        exit;
    }
}

$conn = @mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    error_log('DB connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    echo "Sorry, we're having some technical difficulties. Please try again later.";
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');


$stmt = mysqli_prepare($conn, "
    SELECT ci.quantity, p.sku, p.name, p.price, p.image_path AS image, p.requires_prescription
    FROM LC_cart_items ci
    JOIN LC_carts c ON ci.cart_id = c.cart_id
    JOIN LC_products p ON ci.sku = p.sku
    WHERE c.user_id = ?
");

if (!$stmt) {
    die('Prepare failed: ' . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

// Load into PHP array
$items = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $items[] = $row;
    }
    mysqli_free_result($res);
}
mysqli_stmt_close($stmt);


$branches = [];
$brStmt = mysqli_prepare($conn, "SELECT id, branch_name, location FROM LC_branches ORDER BY location, branch_name");
if ($brStmt) {
    mysqli_stmt_execute($brStmt);
    $brRes = mysqli_stmt_get_result($brStmt);
    if ($brRes) {
        while ($b = mysqli_fetch_assoc($brRes)) {
            $branches[] = $b;
        }
        mysqli_free_result($brRes);
    }
    mysqli_stmt_close($brStmt);
}

// 3) Compute summary
$totalSkus = count($items);               
$totalCost = 0.0;
foreach ($items as $it) {
    $totalCost += (float)$it['price'] * (int)$it['quantity'];
}

// Fetch current points
$acct = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT points FROM LC_loyaltyaccount WHERE user_id = $uid"
));
$currentPoints = (int)$acct['points'];
// Decide redemption rate
$pointValue   = 0.01; // 1 point = $0.01
$maxRedeem    = $currentPoints * $pointValue;

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carts</title>

    <link rel="stylesheet" href="./styles/cart.css" class="css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
</head>
<body>
    <?php 
        include_once("header.inc");
    ?>
    <div class="return">
        <a href="products.php">
            <span class="fa-solid fa-arrow-left"></span>
            <span>&nbsp;Continue shopping</span>
        </a>
    </div>
    <div class="main">        
        <section class="cart-section">
            <?php if (empty($items)): ?>
                <section class="main-cart">
                    <div class="list-cart">
                        <div class="header-cart">
                            <p>Total:&nbsp;<span> <?= $totalSkus ?></span></p>
                            <p>Name</p>
                            <p>Price</p>
                            <p>Quantity</p>
                            <p>Require Prescription</p>
                        </div>
                    </div>
                </section>
            <?php else: ?>
            <section class="main-cart">
                <div class="list-cart">
                    <div class="header-cart">
                        <p>Total:&nbsp;<span> <?= $totalSkus ?></span></p>
                        <p>Name</p>
                        <p>Price</p>
                        <p>Quantity</p>
                        <p>Require Prescription</p>
                    </div>
                    <?php foreach ($items as $r): ?>
                    <div class="cart-item">
                        <img src="<?= htmlspecialchars($r['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="cart-item-name"><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="cart-item-price">$<?= number_format((float)$r['price'], 2) ?></div>
                        <div class="cart-item-quantity"><span class="value"><?= (int)$r['quantity'] ?></span></div>
                        <div class="cart-item-prescription">
                            <?php if ((int)$r['requires_prescription'] === 1): ?>
                                <span class="visually-hidden">&nbsp;RX required</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- ORDER DETAILS FORM -->
            <section class="order-details">
                <div class="delivery-method">
                    <p>Choose delivery method</p>
                    <div class="delivery-group <?= isset($fieldErrors['fulfillment']) ? 'has-error' : '' ?>">
                        <input type="radio" id="delivery" name="fulfillment" value="delivery" form="checkout-form" 
                               <?= ($old['fulfillment'] === 'pickup') ? '' : 'checked' ?> required />
                        <label for="delivery">Delivery to your door</label>

                        <input type="radio" id="pickup" name="fulfillment" value="pickup" form="checkout-form" 
                               <?= ($old['fulfillment'] === 'pickup') ? 'checked' : '' ?> />
                        <label for="pickup">Pick up at store</label>
                    </div>
                    <?php if (isset($fieldErrors['fulfillment'])): ?>
                        <span class="field-error"><?= htmlspecialchars($fieldErrors['fulfillment'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" method="post" id="checkout-form">
                    <!-- Delivery fields -->
                    <div class="fulfillment-form" id="form-delivery">
                        <h3>
                            <span class="fa-solid fa-user"></span>
                            Delivery Information
                        </h3>
                        <div class="form-group <?= isset($fieldErrors['deliver_name']) ? 'has-error' : '' ?>">
                            <label for="deliver_name">Your Name</label>
                            <input type="text" id="deliver_name" name="deliver_name" 
                                   value="<?= htmlspecialchars($old['deliver_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="100" required>
                            <?php if (isset($fieldErrors['deliver_name'])): ?>
                                <span class="field-error"><?= htmlspecialchars($fieldErrors['deliver_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group <?= isset($fieldErrors['deliver_phone']) ? 'has-error' : '' ?>">
                            <label for="deliver_phone">Phone Number</label>
                            <input type="tel" id="deliver_phone" name="deliver_phone" 
                                   value="<?= htmlspecialchars($old['deliver_phone'], ENT_QUOTES, 'UTF-8') ?>" 
                                   pattern="[0-9\-\+\s\(\)]{9,15}" maxlength="15" required>
                            <?php if (isset($fieldErrors['deliver_phone'])): ?>
                                <span class="field-error"><?= htmlspecialchars($fieldErrors['deliver_phone'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group <?= isset($fieldErrors['deliver_city']) ? 'has-error' : '' ?>">
                            <label for="deliver_city">City</label>
                            <input type="text" id="deliver_city" name="deliver_city" 
                                   value="<?= htmlspecialchars($old['deliver_city'], ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="50" required>
                            <?php if (isset($fieldErrors['deliver_city'])): ?>
                                <span class="field-error"><?= htmlspecialchars($fieldErrors['deliver_city'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group <?= isset($fieldErrors['deliver_district']) ? 'has-error' : '' ?>">
                            <label for="deliver_district">District</label>
                            <input type="text" id="deliver_district" name="deliver_district" 
                                   value="<?= htmlspecialchars($old['deliver_district'], ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="50" required>
                            <?php if (isset($fieldErrors['deliver_district'])): ?>
                                <span class="field-error"><?= htmlspecialchars($fieldErrors['deliver_district'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group <?= isset($fieldErrors['deliver_address']) ? 'has-error' : '' ?>">
                            <label for="deliver_address">Street Address</label>
                            <input type="text" id="deliver_address" name="deliver_address" 
                                   value="<?= htmlspecialchars($old['deliver_address'], ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="200" required>
                            <?php if (isset($fieldErrors['deliver_address'])): ?>
                                <span class="field-error"><?= htmlspecialchars($fieldErrors['deliver_address'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pick-Up fields -->
                    <div class="fulfillment-form" id="form-pickup" style="display:none;">
                        <h3>
                            <span class="fa-solid fa-user"></span>
                            Pick-Up Information
                        </h3>
                        <div class="form-group <?= isset($fieldErrors['pickup_name']) ? 'has-error' : '' ?>">
                            <label for="pickup_name">Your Name</label>
                            <input type="text" id="pickup_name" name="pickup_name" 
                                   value="<?= htmlspecialchars($old['pickup_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="100">
                            <?php if (isset($fieldErrors['pickup_name'])): ?>
                                <span class="field-error"><?= htmlspecialchars($fieldErrors['pickup_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group <?= isset($fieldErrors['pickup_phone']) ? 'has-error' : '' ?>">
                            <label for="pickup_phone">Phone Number</label>
                            <input type="tel" id="pickup_phone" name="pickup_phone" 
                                   value="<?= htmlspecialchars($old['pickup_phone'], ENT_QUOTES, 'UTF-8') ?>" 
                                   pattern="[0-9\-\+\s\(\)]{9,15}" maxlength="15">
                            <?php if (isset($fieldErrors['pickup_phone'])): ?>
                                <span class="field-error"><?= htmlspecialchars($fieldErrors['pickup_phone'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group <?= isset($fieldErrors['pickup_branch']) ? 'has-error' : '' ?>">
                            <label for="pickup_branch">
                                Select Store
                            </label>
                            <select id="pickup_branch" name="pickup_branch">
                                <option value="">-- Choose a store to pickup --</option>
                                <?php foreach ($branches as $b): ?>
                                <option value="<?= (int)$b['id'] ?>" 
                                        <?= ($old['pickup_branch'] == $b['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['location'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($b['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($fieldErrors['pickup_branch'])): ?>
                                <span class="field-error"><?= htmlspecialchars($fieldErrors['pickup_branch'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-group">
                        <h3>
                            <span class="fa-solid fa-money-check-dollar"></span>
                            Payment Method
                        </h3>
                        <div class="payment-options <?= isset($fieldErrors['payment_method']) ? 'has-error' : '' ?>">
                            <div class="payment-option">
                                <input type="radio" id="payment_card" name="payment_method" value="card" 
                                       <?= ($old['payment_method'] === 'card') ? 'checked' : '' ?> required>
                                <label for="payment_card">Card Payment</label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" id="payment_cash" name="payment_method" value="cash" 
                                       <?= ($old['payment_method'] === 'cash') ? 'checked' : '' ?> required>
                                <label for="payment_cash">Cash on Delivery / Pickup</label>
                            </div>
                        </div>
                        <?php if (isset($fieldErrors['payment_method'])): ?>
                            <span class="field-error"><?= htmlspecialchars($fieldErrors['payment_method'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group <?= isset($fieldErrors['card_number']) ? 'has-error' : '' ?>" id="card-details">
                        <label for="card_number">Card Number (16 digits)
                            <input type="text" name="card_number" id="card_number"
                                value="<?= htmlspecialchars($old['card_number'], ENT_QUOTES, 'UTF-8') ?>"
                                pattern="\d{16}" maxlength="16" required>
                        </label>
                        <?php if (isset($fieldErrors['card_number'])): ?>
                            <span class="field-error"><?= htmlspecialchars($fieldErrors['card_number'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Hidden field for loyalty redemption -->
                    <input type="hidden" name="redeem_points" id="redeem-points-hidden" value="0">
                </form>
            </section>
            <?php endif; ?>
        </section>
        
        <section class="cart-summary">
            <div class="loyalty-section">
                <div class="loyalty-header">
                    <span class="fa-solid fa-star loyalty-icon"></span>
                    <span class="loyalty-title">Loyalty Points</span>
                </div>
                <div class="loyalty-info">
                    <p class="points-available">You have <strong><?= $currentPoints ?></strong> points</p>
                    <p class="points-value">(worth up to $<?= number_format($maxRedeem, 2) ?>)</p>
                </div>
                <?php if ($currentPoints > 0): ?>
                <div class="loyalty-checkbox">
                    <label class="checkbox-container">
                        <input type="checkbox" id="redeem-points" name="redeem_points" value="1">
                        <span class="checkmark"></span>
                        <span class="checkbox-text">Use points for discount</span>
                    </label>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="price-breakdown">
                <div class="price-row">
                    <span class="price-label">Subtotal</span>
                    <span class="price-value">$<?= number_format($totalCost, 2) ?></span>
                </div>
                <div class="price-row discount-row" id="discount-row" style="display: none;">
                    <span class="price-label">Loyalty Discount</span>
                    <span class="price-value discount-value" id="redeem-amount">-$0.00</span>
                </div>
                <div class="price-row">
                    <span class="price-label">Shipping</span>
                    <span class="price-value shipping-free">FREE</span>
                </div>
                <!-- <div class="price-divider"></div> -->
                <div class="price-row total-row">
                    <span class="price-label">Total</span>
                    <span class="price-value total-price" id="final-total">$<?= number_format($totalCost, 2) ?></span>
                </div>
            </div>
            
            <?php if (!empty($items)): ?>
            <button type="submit" form="checkout-form" class="order-btn">
                <span class="fa-solid fa-shopping-cart"></span>
                Proceed to Checkout
            </button>
            <?php else: ?>
            <div class="empty-cart-message">
                <p>Your cart is empty</p>
                <a href="products.php" class="continue-shopping-btn">Continue Shopping</a>
            </div>
            <?php endif; ?>
        </section>
    </div>
    <?php 
        include_once("footer.inc");
    ?>
</body>
<script src="./list_cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const redeemCheckbox = document.getElementById('redeem-points');
    const redeemAmountEl = document.getElementById('redeem-amount');
    const finalTotalEl = document.getElementById('final-total');
    const discountRow = document.getElementById('discount-row');
    const hiddenInput = document.getElementById('redeem-points-hidden');
    
    const originalTotal = <?= $totalCost ?>;
    const maxRedeem = <?= $maxRedeem ?>;
    const currentPoints = <?= $currentPoints ?>;
    
    if (redeemCheckbox) {
        redeemCheckbox.addEventListener('change', function() {
            if (this.checked && currentPoints > 0) {
                // Show discount row
                discountRow.style.display = 'flex';
                
                // Calculate actual discount (don't exceed total cost)
                const actualDiscount = Math.min(maxRedeem, originalTotal);
                const newTotal = Math.max(0, originalTotal - actualDiscount);
                
                redeemAmountEl.textContent = '-$' + actualDiscount.toFixed(2);
                finalTotalEl.textContent = '$' + newTotal.toFixed(2);
                hiddenInput.value = '1';
            } else {
           
                discountRow.style.display = 'none';
                finalTotalEl.textContent = '$' + originalTotal.toFixed(2);
                hiddenInput.value = '0';
            }
        });
    }
});
</script>
</html>