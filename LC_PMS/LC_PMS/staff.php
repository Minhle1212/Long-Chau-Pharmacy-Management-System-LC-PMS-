<?php
require_once 'settings.php';   // define $host, $user, $pass, $db

$conn = @mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    error_log('DB connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    echo "Sorry, we’re having some technical difficulties. Please try again later.";
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

// Helper: sanitize & trim
function clean($s) {
    return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
}

// Build base query + parameters
$where = [];
$params = [];
$types  = '';

// Search by key
if (!empty($_GET['q_key'])) {
    $where[]    = "LC_products.sku = ?";
    $params[]   = $_GET['q_key'];
    $types     .= 's';
}

// Search by name 
if (!empty($_GET['q_name'])) {
    $where[]    = "LC_products.name LIKE ?";
    $params[]   = '%' . $_GET['q_name'] . '%';
    $types     .= 's';
}

// Filter by category
if (!empty($_GET['q_cat'])) {
    $where[]    = "LC_products.category_id = ?";
    $params[]   = $_GET['q_cat'];
    $types     .= 's';
}

// Compose
$sql = "SELECT LC_products.sku, LC_products.name, LC_products.price, LC_category.category_name
        FROM LC_products
        JOIN LC_category ON LC_products.category_id = LC_category.id";

if (count($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY LC_products.name";

// Prepare & execute
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch categories for dropdown
$catRes = $conn->query("SELECT id AS category_id, category_name FROM LC_category ORDER BY category_name");
$categories = $catRes->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Long Chau Pharmacy</title>

    <link rel="stylesheet" href="./styles/staff.css" class="css">
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
    <div class="content">
        <h1>ALL PRODUCTS</h1>
        <div class="search-container">
            <form method="get" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="q_key">Product Key:</label>
                        <input type="text" id="q_key" name="q_key" value="<?= clean($_GET['q_key'] ?? '') ?>" placeholder="Enter SKU">
                    </div>
                    
                    <div class="form-group">
                        <label for="q_name">Product Name:</label>
                        <input type="text" id="q_name" name="q_name" value="<?= clean($_GET['q_name'] ?? '') ?>" placeholder="Enter product name">
                    </div>
                    
                    <div class="form-group">
                        <label for="q_cat">Category:</label>
                        <select id="q_cat" name="q_cat">
                            <option value="">— All Categories —</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c['category_id'] ?>" <?= ($_GET['q_cat'] ?? '') === $c['category_id'] ? 'selected' : '' ?>>
                                    <?= clean($c['category_name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="staff.php" class="btn-reset">
                            <i class="fas fa-refresh"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <!-- Results Table -->
        <table>
            <thead>
            <tr>
                <th>Key</th><th>Name</th><th>Price</th><th>Category</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= clean($row['sku']) ?></td>
                    <td><?= clean($row['name']) ?></td>
                    <td><?= number_format($row['price'], 2) ?></td>
                    <td><?= clean($row['category_name']) ?></td>
                </tr>
                <?php endwhile ?>
            <?php else: ?>
                <tr><td colspan="4">No products found.</td></tr>
            <?php endif ?>
            </tbody>
        </table>

    </div>
</body>
</html>
