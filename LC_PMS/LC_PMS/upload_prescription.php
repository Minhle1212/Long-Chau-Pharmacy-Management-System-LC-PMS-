<?php
session_start();
require 'settings.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
// Connect
$conn = @mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    error_log('DB connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    echo "Sorry, we’re having some technical difficulties. Please try again later.";
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

// Verify user & order
$order_id = (int)($_GET['order_id']);
$uid      = $_SESSION['user_id'];
$res = mysqli_query($conn,
  "SELECT status FROM LC_orders
   WHERE order_id=$order_id AND user_id=$uid"
);
$order = mysqli_fetch_assoc($res) ?: header("Location: index.php");
if ($order['status'] !== 'waiting_prescription') {
  die("No prescription required");
}

// Fetch the lines needing Rx
$res = mysqli_query($conn,
  "SELECT ol.sku, p.name, p.image_path
     FROM LC_order_lines ol
     JOIN LC_products p ON ol.sku=p.sku
    WHERE ol.order_id=$order_id
      AND p.requires_prescription=1"
);
$lines = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Render form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Prescription</title>

    <link rel="stylesheet" href="./styles/upload_prescription.css" class="css">
    <link rel="stylesheet" href="./styles/style.css" class="css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php 
        include_once("header.inc");
    ?>
    
    <div class="prescription-main">
        <h1 class="prescription-title">Upload Prescription for Order #<?= $order_id ?></h1>
        
        <!-- Products requiring prescription -->
        <div class="prescription-products">
            <div class="prescription-products-header">
                Products Requiring Prescription
            </div>
            
            <?php if (empty($lines)): ?>
                <div class="prescription-empty">
                    <i class="fas fa-prescription-bottle"></i>
                    <p>No prescription required items found.</p>
                </div>
            <?php else: ?>
                <div class="prescription-products-list">
                    <?php foreach ($lines as $l): ?>
                        <div class="prescription-product-item">
                            <?php if (!empty($l['image_path'])): ?>
                                <img src="<?=htmlspecialchars($l['image_path'])?>" alt="<?=htmlspecialchars($l['name'])?>">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background-color: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #999;">
                                    <i class="fas fa-pills"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="prescription-product-info">
                                <div class="prescription-product-sku">SKU: <?=htmlspecialchars($l['sku'])?></div>
                                <div class="prescription-product-name"><?=htmlspecialchars($l['name'])?></div>
                                <div class="prescription-required-badge">
                                    <i class="fas fa-prescription"></i> Prescription Required
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Upload form -->
        <div class="prescription-upload-section">
            <h2 class="prescription-upload-title">Upload Your Prescription</h2>
            
            <div class="prescription-instructions">
                <h4><i class="fas fa-info-circle"></i> Instructions:</h4>
                <ul>
                    <li>Upload a clear photo or scan of your prescription</li>
                    <li>Accepted formats: PDF, JPG, PNG</li>
                    <li>Make sure all text is readable and the doctor's signature is visible</li>
                </ul>
            </div>
            
            <form action="save_prescription.php" method="post" enctype="multipart/form-data" class="prescription-form">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                
                <div class="prescription-file-group">
                    <label for="prescription_file" class="prescription-file-label">
                        <i class="fas fa-upload"></i> Prescription Image/PDF:
                    </label>
                    <input 
                        type="file" 
                        id="prescription_file"
                        name="prescription_file" 
                        accept=".pdf,image/*" 
                        required
                        class="prescription-file-input"
                    >
                    <div class="prescription-file-info">
                        Drag and drop your file here or click to browse
                    </div>
                </div>
                
                <button type="submit" class="prescription-submit-btn">
                    <i class="fas fa-check"></i> Upload & Continue
                </button>
            </form>
        </div>
    </div>
    
    <?php 
        include_once("footer.inc");
    ?>
</body>
</html>