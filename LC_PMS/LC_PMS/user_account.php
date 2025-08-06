<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'settings.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$uid = (int)$_SESSION['user_id'];

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    error_log('DB connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    echo "Sorry, we’re having some technical difficulties. Please try again later.";
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

// Handle username update
$updateMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_username'])) {
    $newName = trim($_POST['new_username']);
    if (mb_strlen($newName,'UTF-8') >= 3 && mb_strlen($newName,'UTF-8') <= 50) {
        // prepare against the existing $conn
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE LC_users SET username = ? WHERE user_id = ?"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $newName, $uid);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['username'] = $newName;
                $updateMsg = 'Username updated.';
            } else {
                $updateMsg = 'Update failed: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $updateMsg = 'Prepare failed: ' . mysqli_error($conn);
        }
    } else {
        $updateMsg = 'Username must be 3–50 characters.';
    }
}

//Fetch user info
$stmt = mysqli_prepare($conn,
  "SELECT username,email FROM LC_users WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt,'i',$uid);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$username,$email);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Fetch loyalty points
$stmt = mysqli_prepare($conn,
  "SELECT points FROM LC_loyaltyaccount WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt,'i',$uid);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$points);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Fetch order history
$orders = [];
$stmt = mysqli_prepare($conn,"
  SELECT order_id,created_at,total_cost,status
    FROM LC_orders
   WHERE user_id = ?
   ORDER BY created_at DESC
");
mysqli_stmt_bind_param($stmt,'i',$uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $orders[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account</title>

    <link rel="stylesheet" href="./styles/user_account.css" class="css">
    <link rel="stylesheet" href="./styles/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
</head>
<body>
    <?php 
        include_once("header.inc");
        include_once("cart.inc")
    ?>

    <main class="account-page">
        
        <!-- Sidebar Navigation -->
        <aside class="account-sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-name"><?= htmlspecialchars($username, ENT_QUOTES) ?></div>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link active" data-section="profile">
                            <i class="fas fa-user"></i>
                            <span>Personal Information</span>
                            <i class="fas fa-chevron-right arrow"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="orders">
                            <i class="fas fa-box"></i>
                            <span>Order History</span>
                            <i class="fas fa-chevron-right arrow"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-key"></i>
                            <span>Log Out</span>
                            <i class="fas fa-chevron-right arrow"></i>
                        </a>
                    </li>
                </div>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="account-content">
            <section class="profile" id="profile-section">
                <h2>Profile</h2>
                <p><strong>Email:</strong> <?= htmlspecialchars($email,ENT_QUOTES) ?></p>
                <form method="post" action="user_account.php" class="username-form">
                    <label for="new_username"><strong>Username:</strong></label>
                    <input 
                    type="text" 
                    id="new_username" 
                    name="new_username" 
                    value="<?= htmlspecialchars($username,ENT_QUOTES) ?>" 
                    minlength="3" maxlength="50" required>
                    <button type="submit">Update</button>
                </form>
                <?php if ($updateMsg): ?>
                    <p class="form-msg"><?= htmlspecialchars($updateMsg,ENT_QUOTES) ?></p>
                <?php endif; ?>
            </section>

            <section class="loyalty" id="loyalty-section">
                <h2>Loyalty Account</h2>
                <p>You have <strong><?= (int)$points ?></strong> points.</p>
            </section>
            <section class="orders" id="orders-section" style="display: none;">
                <h2>Order History</h2>
                <?php if (empty($orders)): ?>
                    <p>No orders yet.</p>
                <?php else: ?>
                    <table class="orders-table">
                    <thead>
                        <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= (int)$o['order_id'] ?></td>
                            <td><?= date('M j, Y H:i', strtotime($o['created_at'])) ?></td>
                            <td>$<?= number_format($o['total_cost'],2) ?></td>
                            <td><?= htmlspecialchars(str_replace('_',' ',$o['status']),ENT_QUOTES) ?></td>
                            <td>
                            <?php if ($o['status']==='waiting_prescription'): ?>
                                <a class="btn" href="upload_prescription.php?order_id=<?= (int)$o['order_id'] ?>">
                                Upload Rx
                                </a>
                            <?php else: ?>
                                <a class="btn" href="processing_order.php?order_id=<?= (int)$o['order_id'] ?>">
                                View
                                </a>
                            <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <?php
        include_once("footer.inc")
    ?>
</body>
<script src="./cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-link[data-section]');
    const profileSection = document.getElementById('profile-section');
    const loyaltySection = document.getElementById('loyalty-section');
    const ordersSection = document.getElementById('orders-section');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            navLinks.forEach(nav => nav.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Get target section
            const targetSection = this.getAttribute('data-section');
            
            if (targetSection === 'profile') {
                profileSection.style.display = 'block';
                loyaltySection.style.display = 'block';
                ordersSection.style.display = 'none';
            } else if (targetSection === 'orders') {
                profileSection.style.display = 'none';
                loyaltySection.style.display = 'none';
                ordersSection.style.display = 'block';
            }
        });
    });
});
</script>
</html>