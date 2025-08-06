<?php 
    require("settings.php");
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Purchase Order</title>
    <link rel="stylesheet" href="./styles/style.css">
    <link rel="stylesheet" href="./styles/manage.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="icon" type="image/png" sizes="16x16" href="./images/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php
        include("header.inc");
        if ((isset($_SESSION['position'])) && ($_SESSION['position'] == 1)) {


            $conn = @mysqli_connect($host, $user, $password, $database);
            if (!$conn) {
                die("<main><p class='alert'>Database connection failed: " . mysqli_connect_error() . "</p></main>");
            }


            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_po'])) {
                $po_sku = mysqli_real_escape_string($conn, $_POST['product_sku']);
                $po_branch_id = (int)$_POST['branch_id'];
                $po_supplier_id = (int)$_POST['supplier_id'];
                $po_quantity = (int)$_POST['quantity'];

                if ($po_branch_id > 0 && $po_supplier_id > 0 && $po_quantity > 0 && !empty($po_sku)) {
                    mysqli_begin_transaction($conn);
                    
                    // Create the main purchase order record
                    $sql_po = "INSERT INTO LC_purchase_orders (branch_id, supplier_id, status) VALUES ($po_branch_id, $po_supplier_id, 'Pending Approval')";
                    
                    if (mysqli_query($conn, $sql_po)) {
                        $po_id = mysqli_insert_id($conn); // Get the ID of the new PO

                        // Create the item record for the purchase order
                        $sql_item = "INSERT INTO LC_purchase_order_items (po_id, product_sku, quantity_ordered) VALUES ($po_id, '$po_sku', $po_quantity)";
                        
                        if (mysqli_query($conn, $sql_item)) {
                            mysqli_commit($conn);
                            $_SESSION['message'] = "Successfully created Purchase Order #" . $po_id . ".";                            
                        } else {
                            mysqli_rollback($conn);
                            $_SESSION['error_message'] = "Error creating PO item: " . mysqli_error($conn);
                        }
                    } else {
                        mysqli_rollback($conn);
                        $_SESSION['error_message'] = "Error creating PO: " . mysqli_error($conn);
                    }
                } else {
                    $_SESSION['error_message'] = "Please fill all fields correctly.";                
                }

                header("Location: manage.php");
                exit();
            }

            $product_sku = isset($_GET['sku']) ? mysqli_real_escape_string($conn, $_GET['sku']) : '';
            $branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
            $product_name = '';
            $branch_name = '';

            if ($product_sku && $branch_id > 0) {
                $sql_product = "SELECT name FROM LC_products WHERE sku = '$product_sku'";
                $result_product = mysqli_query($conn, $sql_product);
                if ($row = mysqli_fetch_assoc($result_product)) { $product_name = $row['name']; }

                $sql_branch = "SELECT branch_name FROM LC_branches WHERE id = $branch_id";
                $result_branch = mysqli_query($conn, $sql_branch);
                if ($row = mysqli_fetch_assoc($result_branch)) { $branch_name = $row['branch_name']; }
            } 
    ?>
    <main>
        <section class="hero-content">
            <div class="hero-items">
                <h2>Create Purchase Order</h2>
            </div>
        </section>
        <form action="purchase_order.php" method="post">
            <ul class="form-container">
                <li class="form-col">
                    <label>Branch:</label>
                    <input type="text" value="<?php echo htmlspecialchars($branch_name); ?>" disabled>
                </li>
                <li class="form-col">
                    <label>Product SKU:</label>
                    <input type="text" name="product_sku" value="<?php echo htmlspecialchars($product_sku); ?>" readonly>
                </li>
                <li class="form-col">
                    <label>Product Name:</label>
                    <input type="text" value="<?php echo htmlspecialchars($product_name); ?>" disabled>
                </li>
                <li class="form-col">
                    <label for="supplier_id">Supplier:</label>
                    <select name="supplier_id" id="supplier_id" required>
                        <option value="">Select Supplier</option>
                        <?php
                            
                            $supplier_sql = "SELECT id, supplier_name FROM LC_suppliers ORDER BY supplier_name";
                            $supplier_result = mysqli_query($conn, $supplier_sql);
                            while ($supplier_row = mysqli_fetch_assoc($supplier_result)) {
                                echo "<option value='" . $supplier_row['id'] . "'>" . htmlspecialchars($supplier_row['supplier_name']) . "</option>";
                            }
                        ?>
                    </select>
                </li>
                <li class="form-col">
                    <label for="quantity">Quantity to Order:</label>
                    <input type="number" name="quantity" id="quantity" min="1" required>
                </li>
                <input type="hidden" name="branch_id" value="<?php echo htmlspecialchars($branch_id); ?>">
                <li class="form-col">
                    <input type="submit" name="create_po" id="createPO" value="Create Purchase Order">
                </li>
            </ul>
        </form>
        <br>
        <a href="manage.php" style="display: block; text-align: center; margin-top: 20px;">Back to Stock Management</a>

         
    </main>
<?php 
        include("footer.inc");
    ?>
</body>
</html>

<?php
} 
        
else {
    header("Location: index.php");
    exit();
}
?>