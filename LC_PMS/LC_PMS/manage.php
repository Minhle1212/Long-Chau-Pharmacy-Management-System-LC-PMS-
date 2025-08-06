<?php 
    require("settings.php");
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Long Chau Pharmacy</title>
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
    //check the position id for logged in account
        include("header.inc");
        if ((isset($_SESSION['position'])) && ($_SESSION['position'] == 1)) {
    ?>
    <main>
        <section class="hero-content">
            <div class="hero-items">
                <h2>Branch Management</h2>
            </div>
        </section>
        <ul class="form-container">
            <!-- List all stock -->
            <li class="form-col">
                <form action="manage.php" method="post" class="list-all">
                    <span>List all current stock</span>
                    <input type="submit" name="list_all_stock" value="Show All Stock">
                </form>
            </li>
            
            <!-- Adjust Stock -->
            <li class="form-col">
                <form action="manage.php" method="post">
                    <label for="search_sku">Search Stock to Edit:</label>
                    <input type="text" name="search_sku" id="search_sku" placeholder="Product SKU" required>
                    <input type="submit" name="search_stock_to_edit" value="Search">
                </form>
            </li>

            <!-- Generate Low Stock Report -->
            <li class="form-col">
                <form action="manage.php" method="post">
                    <label for="report_type">Generate Report:</label>
                    <select id="report_type" name="report_type">
                        <option value="">Please select</option>
                        <option value="low_stock">Low Stock Report</option>
                        <option value="pending_pos">Pending Purchase Orders</option>
                        <option value="pos_history">Purchase Orders History</option>
                    </select>
                    <input type="submit" name="generate_report" value="Generate">
                </form>
            </li>


            

        </ul>   
        <!-- add new stock form -->
        <h2 class="branch-title">Add New Product</h2>
            <form action="manage.php" method="post" enctype="multipart/form-data">
                <ul class="form-container">
                    <li class="form-col">
                        <label for="new_sku">Product SKU:</label>
                        <input type="text" name="new_sku" id="new_sku" required>
                    </li>
                    <li class="form-col">
                        <label for="new_name">Product Name:</label>
                        <input type="text" name="new_name" id="new_name" required>
                    </li>
                    <li class="form-col">
                        <label for="new_description">Description:</label>
                        <input type="text" name="new_description" id="new_description">
                    </li>
                    <li class="form-col">
                        <label for="new_image">Product Image:</label>
                        <input type="file" name="new_image" id="new_image" required>
                    </li>
                    <li class="form-col">
                        <label for="new_price">Price:</label>
                        <input type="number" name="new_price" id="new_price" step="0.01" required>
                    </li>
                    <li class="form-col">
                        <label for="new_category">Category:</label>
                        <select name="new_category_id" id="new_category" required>
                            <option value="">Select Category</option>
                            <?php
                                // Retrive category from category table 
                                $conn_cat = @mysqli_connect($host, $user, $password, $database);
                                if ($conn_cat) {
                                    $cat_sql = "SELECT id, category_name FROM LC_category ORDER BY category_name";
                                    $cat_result = mysqli_query($conn_cat, $cat_sql);
                                    if ($cat_result) {
                                        while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                            echo "<option value='" . $cat_row['id'] . "'>" . htmlspecialchars($cat_row['category_name']) . "</option>";
                                        }
                                    }
                                    mysqli_close($conn_cat);
                                }
                            ?>
                        </select>
                    </li>
                    <li class="form-col">
                        <label>Requires Prescription?</label>
                        <div class="prescription-radio">
                            <input type="radio" name="new_requires_rx" value="1" id="rx_yes"><label for="rx_yes">Yes</label>
                            <input type="radio" name="new_requires_rx" value="0" id="rx_no" checked><label for="rx_no">No</label>
                        </div>
                    </li>


                    <fieldset>
                        <legend> Stock Quantities</legend>
                        <!-- generating quantity input fields for each branch -->
                        <?php
                            $conn_branch = @mysqli_connect($host, $user, $password, $database);
                            if ($conn_branch) {
                                $branch_sql = "SELECT id, branch_name FROM LC_branches ORDER BY branch_name";
                                $branch_result = mysqli_query($conn_branch, $branch_sql);
                                if ($branch_result) {
                                    while ($branch_row = mysqli_fetch_assoc($branch_result)) {
                                        $branch_id = htmlspecialchars($branch_row['id']);
                                        $branch_name = htmlspecialchars($branch_row['branch_name']);
                                        echo "<div class='form-col'><label for='quantity_{$branch_id}'>{$branch_name}:</label>";
                                        echo "<input type='number' name='quantities[{$branch_id}]' id='quantity_{$branch_id}' value='0' min='0' required></div>";
                                    }
                                }
                                mysqli_close($conn_branch);
                            }
                        ?>
                    </fieldset>
                    <li class="form-col">
                        <input type="submit" name="add_product" value="Add Product to Catalog">
                    </li>
                </ul>
            </form>

        <?php
            //the sessio message went redirect back from purchase_order php
            if (isset($_SESSION['message'])) {
                echo "<p class='notify'>" . htmlspecialchars($_SESSION['message']) . "</p>";
                unset($_SESSION['message']);
            }
            if (isset($_SESSION['error_message'])) {
                echo "<p class='alert'>" . htmlspecialchars($_SESSION['error_message']) . "</p>";
                unset($_SESSION['error_message']);
            }
            
        ?>

        <?php
            $conn = @mysqli_connect($host, $user, $password, $database);

            function sanitize_input($data) {
                $data = trim($data);
                $data = stripslashes($data);
                $data = htmlspecialchars($data);
                return $data;
            }

            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            } else {
                if ((isset($_SESSION['position'])) && $_SESSION['position'] == 1) {
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {

                        //List All Stock
                        if (isset($_POST["list_all_stock"])) {
                            // joins 4 tables to get a full picture of the inventory
                            $sql = "SELECT p.sku, p.name, c.category_name, b.branch_name, i.quantity 
                                    FROM LC_inventory i
                                    JOIN LC_products p ON i.product_sku = p.sku
                                    JOIN LC_branches b ON i.branch_id = b.id
                                    LEFT JOIN LC_category c ON p.category_id = c.id
                                    ORDER BY b.branch_name, c.category_name, p.name;";
                            $result = mysqli_query($conn, $sql);
                            if ($result && mysqli_num_rows($result) > 0) {
                                //Grouping the results by branch name 
                                $stockByBranch = [];
                                while($row = mysqli_fetch_assoc($result)) {
                                    $stockByBranch[$row['branch_name']][] = $row;
                                }
                                mysqli_free_result($result);


                                foreach ($stockByBranch as $branchName => $products) {
                                    echo "<h2 class='branch-title'>Stock for: " . htmlspecialchars($branchName) . "</h2>";
                                    echo "<table>";
                                    
                                    echo "<tr>
                                            <th>SKU</th>
                                            <th>Product Name</th>
                                            <th>Product Category</th>
                                            <th>Quantity on Hand</th>
                                         </tr>";

                                    foreach ($products as $product) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($product['sku']) . "</td>";
                                        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($product['category_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($product['quantity']) . "</td>";
                                        echo "</tr>";
                                    }

                                    echo "</table>";
                                }
                            } else{
                                echo "<p class='notify'>No stock information found.</p>";
                            }




                            //Search for a stock item using product sku to edit
                        } elseif(isset($_POST['search_stock_to_edit'])) {
                            $sku_to_search = mysqli_real_escape_string($conn, $_POST['search_sku']);
                            //connect to LC_products and Lc_inventory
                            $sql = "SELECT i.id, p.sku, p.name, b.branch_name, i.quantity 
                                    FROM LC_inventory i
                                    JOIN LC_products p ON i.product_sku = p.sku
                                    JOIN LC_branches b ON i.branch_id = b.id
                                    WHERE i.product_sku = '$sku_to_search';";
                            $result = mysqli_query($conn, $sql);

                            if($result && mysqli_num_rows($result) > 0) {
                                echo "<h2 class='branch-title'>Editing Stock for SKU: " . htmlspecialchars($sku_to_search) . "</h2>";
                                echo "<form action='manage.php' method='post'>"; 
                                echo "<table><tr><th>SKU</th><th>Product Name</th><th>Branch</th><th>Quantity</th></tr>";
                                
                                while($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['sku']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['branch_name']) . "</td>";
                                    echo "<td><input type='number' name='quantities[" . $row['id'] . "]' value='" . $row['quantity'] . "' required></td>";
                                    echo "</tr>";
                                }
                                echo "</table>";
                                echo "<input type='submit' name='save_stock_changes' value='Save Changes'>";
                                echo "</form>";

                            }  else {
                                echo "<p class='alert'>No stock records found for SKU: " . htmlspecialchars($sku_to_search) . "</p>";

                            }
                            if ($result) mysqli_free_result($result);
                            //Save the edited stock quantities
                        } elseif(isset($_POST["save_stock_changes"])) {
                            $quantities = $_POST['quantities'];
                            $all_updates_successful = true;

                            foreach($quantities as $inventory_id => $new_quantity) {
                                $id = (int)$inventory_id;
                                $qty = (int)$new_quantity;
                                //make sure the quantity typed >0
                                if ($qty > 0) {
                                    $sql = "UPDATE LC_inventory SET quantity = $qty WHERE id = $id";
                                    if (!mysqli_query($conn, $sql)) {
                                        echo "<p class='alert'>Error updating record for inventory ID $id: " . mysqli_error($conn) . "</p>";
                                        $all_updates_successful = false;
                                    } 
                                }
                            }
                            if ($all_updates_successful) {
                                echo "<p class='notify'>All stock quantities have been successfully updated.</p>";
                            }
                            //Generate a report 
                        } elseif(isset($_POST["generate_report"]) && isset($_POST['report_type']) && $_POST['report_type'] == 'low_stock') {
                            $low_stock = 50; // Define what low stock means for the system

                            //SQL to find all products with quantity less than 50
                            $sql = "SELECT p.sku, p.name, b.branch_name, i.quantity, i.branch_id 
                                    FROM LC_inventory i
                                    JOIN LC_products p ON i.product_sku = p.sku
                                    JOIN LC_branches b ON i.branch_id = b.id
                                    WHERE i.quantity < $low_stock
                                    ORDER BY b.branch_name, i.quantity ASC;";
                             
                            $result = mysqli_query($conn, $sql);
                            if ($result && mysqli_num_rows($result) > 0) {
                                $lowStockByBranch = [];
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $lowStockByBranch[$row['branch_name']][] = $row;
                                }
                                mysqli_free_result($result);


                                echo "<h2 class='branch-title'>Low Stock Report (Less than $low_stock items)</h2>";
                                //group by branch
                                foreach ($lowStockByBranch as $branchName => $products) {
                                    echo "<h3 class='center-text'>Branch: " . htmlspecialchars($branchName) . "</h3>";
                                    echo "<table><tr><th>SKU</th><th>Product Name</th><th>Quantity on Hand</th><th>Purchase Order</th></tr>";
                                    foreach ($products as $product) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($product['sku']) . "</td>";
                                        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($product['quantity']) . "</td>";

                                        //direct link to create a Purchase Order for the low-stock item to purchase_order php
                                        echo "<td><a href='purchase_order.php?sku=" . urlencode($product['sku']) . "&branch_id=" . $product['branch_id'] . "'>Create PO</a></td>";
                                        echo "</tr>";
                                    }
                                    echo "</table>";
                                }


                            } else {
                                echo "<p class='notify'>No items are currently below the low stock threshold.</p>";
                            }
                           // Receive a Purchase Order
                        } elseif (isset($_POST["receive_po"])){
                            $po_id_to_receive = (int)$_POST['po_id'];
                            mysqli_begin_transaction($conn);
                            // get the details of the PO
                            $sql_get_details = "SELECT po.branch_id, poi.product_sku, poi.quantity_ordered 
                                        FROM LC_purchase_orders po
                                        JOIN LC_purchase_order_items poi ON po.id = poi.po_id
                                        WHERE po.id = $po_id_to_receive";
                            $details_result = mysqli_query($conn, $sql_get_details);

                            if ($details_row = mysqli_fetch_assoc($details_result)) {
                                $branch_id = $details_row['branch_id'];
                                $product_sku = $details_row['product_sku'];
                                $quantity_received = $details_row['quantity_ordered'];
                                
                                //SQL to update the inventory: current quantity + quantity received

                                $sql_update_inv = "UPDATE LC_inventory 
                                                    SET quantity = quantity + $quantity_received 
                                                    WHERE product_sku = '$product_sku' AND branch_id = $branch_id";

                                $sql_update_po = "UPDATE LC_purchase_orders SET status = 'Received' WHERE id = $po_id_to_receive";

                                if (mysqli_query($conn, $sql_update_inv) && mysqli_query($conn, $sql_update_po)) {
                                    mysqli_commit($conn);
                                
                                    echo "Successfully received Purchase Order #$po_id_to_receive and updated stock.";
                                }else{
                                    echo "Successfully received Purchase Order #$po_id_to_receive and updated stock.";
                                }

                            } else {
                                echo "Could not find details for Purchase Order #$po_id_to_receive.";
                            }

                            
                            //Generate other reports Pending PO and PO History 
                        } elseif (isset($_POST["generate_report"]) && isset($_POST['report_type'])){
                            if ($_POST['report_type'] == 'pending_pos') {
                                $sql_pending_pos = "SELECT po.id, po.order_date, b.branch_name, s.supplier_name, poi.product_sku, p.name as product_name, poi.quantity_ordered
                                                    FROM LC_purchase_orders po
                                                    JOIN LC_branches b ON po.branch_id = b.id
                                                    JOIN LC_suppliers s ON po.supplier_id = s.id
                                                    JOIN LC_purchase_order_items poi ON po.id = poi.po_id
                                                    JOIN LC_products p ON poi.product_sku = p.sku
                                                    WHERE po.status = 'Pending Approval'
                                                    ORDER BY b.branch_name, po.order_date ASC";

                                $result_pending_pos = mysqli_query($conn, $sql_pending_pos);

                                if ($result_pending_pos && mysqli_num_rows($result_pending_pos) > 0) {
                                    $poByBranch = [];
                                    while($row = mysqli_fetch_assoc($result_pending_pos)) {
                                        $poByBranch[$row['branch_name']][] = $row;
                                    }
                                    mysqli_free_result($result_pending_pos);

                                    echo "<h2 class='branch-title'>Pending Purchase Orders</h2>";

                                    foreach ($poByBranch as $branchName => $pos) {
                                        echo "<h3 class='center-text'>Branch: " . htmlspecialchars($branchName) . "</h3>";
                                        echo "<table><tr><th>PO ID</th><th>Date</th><th>Supplier</th><th>Product</th><th>Quantity</th><th>Action</th></tr>";
                                        foreach ($pos as $po) {
                                            echo "<tr>";
                                            echo "<td>" . $po['id'] . "</td>";
                                            echo "<td>" . $po['order_date'] . "</td>";
                                            echo "<td>" . htmlspecialchars($po['supplier_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($po['product_name']) . " (" . htmlspecialchars($po['product_sku']) . ")</td>";
                                            echo "<td>" . $po['quantity_ordered'] . "</td>";
                                            echo "<td>
                                                    <form action='manage.php' method='post'>
                                                        <input type='hidden' name='po_id' value='" . $po['id'] . "'>
                                                        <input type='submit' name='receive_po' value='Mark as Received'>
                                                    </form>
                                                </td>";
                                            echo "</tr>";
                                        }
                                        echo "</table>";
                                    }
                                } else {
                                    echo "<p class='notify'>There are no pending purchase orders to receive.</p>";
                                }
                            } elseif ($_POST['report_type'] == 'pos_history'){
                                $sql_po_history = "SELECT po.id, po.order_date, po.status, b.branch_name, s.supplier_name, p.name as product_name, poi.quantity_ordered
                                                    FROM LC_purchase_orders po
                                                    JOIN LC_branches b ON po.branch_id = b.id
                                                    JOIN LC_suppliers s ON po.supplier_id = s.id
                                                    JOIN LC_purchase_order_items poi ON po.id = poi.po_id
                                                    JOIN LC_products p ON poi.product_sku = p.sku
                                                    ORDER BY b.branch_name, po.order_date DESC";

                                $result_pos_history = mysqli_query($conn, $sql_po_history);

                                if ($result_pos_history && mysqli_num_rows($result_pos_history) > 0) {
                                    $poByBranch = [];
                                    while($row = mysqli_fetch_assoc($result_pos_history)) {
                                        $poByBranch[$row['branch_name']][] = $row;
                                    }
                                    mysqli_free_result($result_pos_history);

                                    echo "<h2 class='branch-title'>Purchase Orders History</h2>";

                                    foreach ($poByBranch as $branchName => $pos) {
                                        echo "<h3 class='center-text'>Branch: " . htmlspecialchars($branchName) . "</h3>";
                                        echo "<table><tr><th>PO ID</th><th>Date</th><th>Supplier</th><th>Product</th><th>Qty</th><th>Status</th></tr>";
                                        foreach ($pos as $po) {
                                            echo "<tr>";
                                            echo "<td>" . $po['id'] . "</td>";
                                            echo "<td>" . $po['order_date'] . "</td>";
                                            echo "<td>" . htmlspecialchars($po['supplier_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($po['product_name']) . "</td>";
                                            echo "<td>" . $po['quantity_ordered'] . "</td>";
                                            echo "<td>" . htmlspecialchars($po['status']) . "</td>";
                                            echo "</tr>";
                                        }
                                        echo "</table>";
                                    }
                                } else {
                                    echo "<p class='notify'>Cannot find any purchase order</p>";
                                }
                            }

                            // Add new produuct function
                        } elseif (isset($_POST['add_product'])) {
                            $new_sku = mysqli_real_escape_string($conn, $_POST['new_sku']);
                            $new_name = mysqli_real_escape_string($conn, $_POST['new_name']);
                            $new_desc = mysqli_real_escape_string($conn, $_POST['new_description']);
                            $new_price = (float)$_POST['new_price'];
                            $new_cat_id = (int)$_POST['new_category_id'];
                            $new_req_rx = (int)$_POST['new_requires_rx'];
                            $quantities = $_POST['quantities'];

                            //function to save imaged upload to images folder
                            $image_path = "";
                            if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == UPLOAD_ERR_OK) {
                                $target_dir = "images/"; 
                                if (!is_dir($target_dir)) {
                                    echo "<p class='alert'>Error: The directory '$target_dir' does not exist. Please create it.</p>";
                                } elseif(!is_writable($target_dir)){
                                    echo "<p class='alert'>Error: The directory '$target_dir' is not writable. Please check folder permissions.</p>";
                                }else{
                                
                                    $file_extension = strtolower(pathinfo($_FILES["new_image"]["name"], PATHINFO_EXTENSION));
                                    $target_file = $target_dir . $new_sku . "." . $file_extension;

                                    if (move_uploaded_file($_FILES["new_image"]["tmp_name"], $target_file)) {
                                        $image_path = $target_file;
                                    } else {
                                        echo "<p class='notify'> Error: Could not move the uploaded file. Check folder permissions for '$target_dir'.";
                                        
                                        
                                    }
                                }
                            } else {
                                echo "<p class='notify'>Image upload is required or an error occurred.</p>";
                            }
                            if($image_path !== ""){
                                mysqli_begin_transaction($conn);
                                //insert to LC_products
                                $sql_add_product = "INSERT INTO LC_products (sku, name, description, price, image_path, requires_prescription, category_id) VALUES ('$new_sku', '$new_name', '$new_desc', $new_price, '$image_path', $new_req_rx, $new_cat_id)";

                                if (mysqli_query($conn, $sql_add_product)) {
                                    $all_syncs_successful = true;

                                    // Loop through quantities and insert into LC_inventory
                                    foreach ($quantities as $branch_id => $quantity) {

                                        $b_id = (int)$branch_id;
                                        $qty = (int)$quantity;

                                        if ($qty > 0) {
                                            $sql_sync_inv = "INSERT INTO LC_inventory (product_sku, branch_id, quantity) VALUES ('$new_sku', $b_id, $qty)";

                                            if (!mysqli_query($conn, $sql_sync_inv)){
                                                $all_syncs_successful = false;
                                                break;
                                            }

                                        }
                                    }

                                    if ($all_syncs_successful) {
                                        mysqli_commit($conn);
                                        echo "<p class='notify'>Successfully added product '$new_name' and set initial stock.";
                                    } else {
                                        mysqli_rollback($conn);
                                        //delete the image if the inventory synced fail
                                        if (file_exists($image_path)) {
                                        unlink($image_path);
                                        }
                                        echo "<p class='notify'>Failed to synchronize inventory for all branches. The new product has not been added.";
                                    }
                                } else{
                                   mysqli_rollback($conn);
                                   //Delete the image if the database sync failed
                                    if (file_exists($image_path)) {
                                        unlink($image_path);
                                    }
                                    echo "<p class='alert'>Failed to add product to catalog. Check if SKU already exists. Error: " . mysqli_error($conn) . "</p>";
                                }
                            }
                        }
                            
                        

                    }
                } 
                mysqli_close($conn);
            }
    ?>

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