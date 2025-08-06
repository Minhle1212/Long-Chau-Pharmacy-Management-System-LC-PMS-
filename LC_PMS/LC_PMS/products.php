<?php
    require 'settings.php';

    $conn = mysqli_connect($host, $user, $password, $database);
    if (!$conn) {
        error_log('DB connection failed: ' . mysqli_connect_error());
        http_response_code(503);
        echo "Sorry, we’re having some technical difficulties. Please try again later.";
        exit;
    }
    mysqli_set_charset($conn, 'utf8mb4');

    // Fetch products
    $sql    = "SELECT `sku`,`name`,`description`,`price`,`image_path`,`requires_prescription`
            FROM `LC_products`";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    $productsForJs = [];

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>

    <link rel="stylesheet" href="./styles/products.css" class="css">
    <link rel="stylesheet" href="./styles/style.css" class="css">

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
    <section class="categories">
        <h1>SHOP OUR CATEGORIES</h1>
        <div class="seller-content">
            <?php while ($p = mysqli_fetch_assoc($result)): ?>
            <?php
            // add to JS array
            $productsForJs[] = [
                'sku'    => $p['sku'],            
                'name'  => $p['name'],
                'price' => (float)$p['price'],
                'image_path' => $p['image_path']
            ];
            ?>
            <div class="content-item">
                <img src="<?php echo htmlspecialchars($p['image_path'], ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>">
                <?php if ($p['requires_prescription']): ?>
                    <div class="RX-banner">RX Only</div>
                <?php endif; ?>
                <p class="item-name"><?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?></p>
                <div class="item-price">
                    $<?php echo number_format((float)$p['price'], 2); ?>
                </div>
                <div class="item-icons">
                    <div class="icon-slide" onclick="addCart('<?php echo $p['sku']; ?>')">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.41442 6H3.75V4.5H6.58558L7.33558 7.5H18.935L17.2321 15.1627L16.5 15.75H8.25L7.51786 15.1627L6.02 8.42233L5.41442 6ZM7.68496 9L8.85163 14.25H15.8984L17.065 9H7.68496ZM10.5 18C10.5 18.8284 9.82843 19.5 9 19.5C8.17157 19.5 7.5 18.8284 7.5 18C7.5 17.1716 8.17157 16.5 9 16.5C9.82843 16.5 10.5 17.1716 10.5 18ZM15 19.5C15.8284 19.5 16.5 18.8284 16.5 18C16.5 17.1716 15.8284 16.5 15 16.5C14.1716 16.5 13.5 17.1716 13.5 18C13.5 18.8284 14.1716 19.5 15 19.5Z" fill="#080341"/>
                        </svg>
                    </div>
                    <div class="icon-slide">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M11.993 5.09691C11.0387 4.25883 9.78328 3.75 8.40796 3.75C5.42122 3.75 3 6.1497 3 9.10988C3 10.473 3.50639 11.7242 4.35199 12.67L12 20.25L19.4216 12.8944L19.641 12.6631C20.4866 11.7172 21 10.473 21 9.10988C21 6.1497 18.5788 3.75 15.592 3.75C14.2167 3.75 12.9613 4.25883 12.007 5.09692L12 5.08998L11.993 5.09691ZM12 7.09938L12.0549 7.14755L12.9079 6.30208L12.9968 6.22399C13.6868 5.61806 14.5932 5.25 15.592 5.25C17.763 5.25 19.5 6.99073 19.5 9.10988C19.5 10.0813 19.1385 10.9674 18.5363 11.6481L18.3492 11.8453L12 18.1381L5.44274 11.6391C4.85393 10.9658 4.5 10.0809 4.5 9.10988C4.5 6.99073 6.23699 5.25 8.40796 5.25C9.40675 5.25 10.3132 5.61806 11.0032 6.22398L11.0921 6.30203L11.9452 7.14752L12 7.09938Z" fill="#080341"/>
                        </svg>
                    </div>
                    <div class="icon-slide">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M15 10.5C15 12.9853 12.9853 15 10.5 15C8.01472 15 6 12.9853 6 10.5C6 8.01472 8.01472 6 10.5 6C12.9853 6 15 8.01472 15 10.5ZM14.1793 15.2399C13.1632 16.0297 11.8865 16.5 10.5 16.5C7.18629 16.5 4.5 13.8137 4.5 10.5C4.5 7.18629 7.18629 4.5 10.5 4.5C13.8137 4.5 16.5 7.18629 16.5 10.5C16.5 11.8865 16.0297 13.1632 15.2399 14.1792L20.0304 18.9697L18.9697 20.0303L14.1793 15.2399Z" fill="#080341"/>
                        </svg>
                    </div>
                </div>
            </div>
            <?php endwhile; 
            mysqli_free_result($result);
            mysqli_close($conn);
            ?>
        </div>
        <script>
            const products = <?= json_encode($productsForJs, JSON_UNESCAPED_SLASHES) ?>;
        </script>
    </section>
    <?php 
        include_once("footer.inc");
    ?>
</body>
<script src="./cart.js"></script>
</html>