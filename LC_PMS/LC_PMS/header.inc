<!-- Header -->
<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$position = $_SESSION['position'] ?? null;
?>
<header class="header">
    <div class="logo">
        <img src="./images/logo.png" alt="">
    </div>
    <ul class="main-menu">
    <?php
    switch ($position) {
        case 0:
            echo "<li class='drop'><a href='index.php'>Home</a></li>";
            echo "<li class='drop'><a href='products.php'>Products</a></li>";
            echo "<li class='drop'><a href='user_account.php'>Account</a></li>";
            echo "<li class='drop'><a href='#'>About us</a></li>";
            break;
        case 1:
            // Admin
            echo "<li class='drop'><a href='manage.php'>Manage</a></li>";
            echo "<li class='drop'><a href='people_manage.php'>Staff & User Manage</a></li>";
            echo "<li class='drop'><a href='orders_list.php'>Order Report</a></li>";
            break;

        case 2:
            break;

        case 3:
            break;

        default:
            // Public (or no session/unknown position)
            echo "<li class='drop'><a href='index.php'>Home</a></li>";
            echo "<li class='drop'><a href='products.php'>Products</a></li>";
            echo "<li class='drop'><a href='#'>About us</a></li>";
            echo "<li class='drop'><a href='#'>Contact</a></li>";
            break;
    }
    ?>
    </ul>
    <div class="icon-control">
        <?php if (!isset($_SESSION['username'])): ?>
            <!-- NOT LOGGED IN: Log in + Cart -->
            <div class="login-box">
                <a class="log-in" href="login.php">
                    <span class="fa-solid fa-lock"></span>
                    <span>Log in</span>
                </a>
            </div>
            <div class="icon-cart">
                <span class="fa-solid fa-cart-shopping">
                    <span class="total-quantity-btn">0</span>
                </span>
                <span>Shopping cart</span>
            </div>

        <?php else: 
            // LOGGED IN
            $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

            if ($position === 0): ?>
                <!-- REGULAR USER: Username + Cart -->
                <div class="login-box">
                    <a class="account" href="user_account.php">
                        <span class="fa-solid fa-user"></span>
                        <span><?= $username ?></span>
                    </a>
                </div>
                <div class="icon-cart">
                    <span class="fa-solid fa-cart-shopping">
                        <span class="total-quantity-btn">0</span>
                    </span>
                    <span>Shopping cart</span>
                </div>
            <?php else: ?>
                <!-- STAFF / ADMIN (2 or 3): Username + Logout -->
                <div class="login-box">
                    <span class="fa-solid fa-user"></span>
                    <span><?= $username ?></span>
                </div>
                <div class="icon-cart">
                    <a class="log-out" href="logout.php">
                        <span class="fa-solid fa-right-from-bracket"></span>
                        <span>Log out</span>
                    </a>
                </div>
            <?php endif; 
        endif;?>
    </div>
</header>

