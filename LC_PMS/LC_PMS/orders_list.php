<?php
    session_start();
    if (!isset($_SESSION['position']) || $_SESSION['position'] != 1) {
            header("Location: index.php");
            exit();
        }
    require_once 'settings.php';

    // Connect using MySQLi
    $conn = new mysqli($host, $user, $password, $database);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Handle deletion
    if (isset($_GET['delete'])) {
        $order_id_to_delete = (int)$_GET['delete_order_id'];

        mysqli_begin_transaction($conn);

        $sql_delete_lines = "DELETE FROM LC_order_lines WHERE order_id = $order_id_to_delete";
        $sql_delete_order = "DELETE FROM LC_orders WHERE order_id = $order_id_to_delete";

        if (mysqli_query($conn, $sql_delete_lines) && mysqli_query($conn, $sql_delete_order)) {
            mysqli_commit($conn);
        } else {
            mysqli_rollback($conn);
        }
    }

    // Get orders with totals based on orderline price
    $orders_sql = "
                    SELECT o.order_id,
                        o.created_at,
                        u.username AS customer_name,
                        o.status,
                        o.fulfillment,
                        COALESCE(ROUND(SUM(ol.quantity * ol.unit_price), 2), 0) AS total_amount
                    FROM LC_orders o
                    JOIN LC_users u ON o.user_id = u.user_id
                    LEFT JOIN LC_order_lines ol ON o.order_id = ol.order_id
                    GROUP BY o.order_id, o.created_at, u.username, o.status, o.fulfillment
                    ORDER BY o.created_at DESC;
                ";
    $orders_result = $conn->query($orders_sql);
    $orders = array();
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
    }

    // Get all order lines
    $lines_sql = "
                    SELECT
                        ol.order_id,
                        p.name AS product_name,
                        ol.sku,
                        ol.quantity,
                        ol.unit_price
                    FROM LC_order_lines ol
                    JOIN LC_products p ON ol.sku = p.sku;
                ";
    $lines_result = mysqli_query($conn, $lines_sql);


    // Group lines by order number
    $allLines = [];
    while ($line = mysqli_fetch_assoc($lines_result)) {
        $allLines[$line['order_id']][] = $line;
    }

    // Get a list of customers for the filter dropdown
    $customers_sql = "SELECT username FROM LC_users WHERE position = 0 ORDER BY username";
    $customers_result = mysqli_query($conn, $customers_sql);
    $customers = [];
    while ($row = mysqli_fetch_assoc($customers_result)) {
        $customers[] = $row['username'];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/orders_list.css">
</head>
<body>
    
    <?php 
        include_once("header.inc"); 
    ?>

    <section class="hero-content">
        <div class="hero-items">
            <h2>Order Report</h2>
        </div>
    </section>
    <div class="content">
        <!-- Filter Container -->
        <div class="filter-container">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="customerFilter">Customer:</label>
                    <select id="customerFilter">
                        <option value="">All</option>
                        <?php foreach($customers as $customer): ?>
                            <option><?= htmlspecialchars($customer) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="dateFrom">Year:</label>
                    <input type="date" id="dateFrom">
                </div>

                <div class="filter-group">
                    <label for="dateFrom">From:</label>
                    <input type="date" id="dateFrom">
                </div>

                <div class="filter-group">
                    <label for="dateTo">To:</label>
                    <input type="date" id="dateTo">
                </div>

                <div class="filter-actions">
                    <button id="clearFilters" class="btn btn-secondary">Clear</button>
                    <button id="deleteBtn" class="btn btn-danger" disabled>Delete Selected</button>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <table id="ordersTable" class="display">
                <thead>
                    <tr>
                        <th></th>
                        <th>Order ID</th>
                        <th>Order Date</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Fulfillment</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $order): ?>
                    <tr data-order-id="<?= $order['order_id'] ?>">
                        <td class="toggle">▶</td>
                        <td><strong>#<?= htmlspecialchars($order['order_id']) ?></strong></td>
                        <td><?= date('M j, Y, g:i a', strtotime($order['created_at'])) ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= htmlspecialchars($order['status']) ?></td>
                        <td><?= htmlspecialchars($order['fulfillment']) ?></td>
                        <td>$<?= number_format($order['total_amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    var allLines = <?= json_encode($allLines) ?>;

    $(document).ready(function(){
        // Initialize DataTable
        var table = $('#ordersTable').DataTable({
            ordering: true,
            order: [[1, 'desc']], // Default sort by Order ID 
            columnDefs: [{ orderable: false, targets: 0 }], // Disable sorting on the toggle column
            pageLength: 50,
            dom: 'lrtip'
        });


        // Custom filtering function
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            var orderDateStr = data[2];
            var orderDate = new Date(orderDateStr);
            var customerName = data[3];
            
            var selectedCustomer = $('#customerFilter').val();
            var fromDateStr = $('#dateFrom').val();
            var toDateStr = $('#dateTo').val();

            if (selectedCustomer && customerName !== selectedCustomer) return false;

            //check date filter
            if (fromDateStr) {
                var fromDate = new Date(fromDateStr);
                fromDate.setHours(0, 0, 0, 0); // Start of the day
                if (orderDate < fromDate) return false;
            }
            if (toDateStr) {
                var toDate = new Date(toDateStr);
                toDate.setHours(23, 59, 59, 999); // End of the day
                if (orderDate > toDate) return false;
            }
            
            return true;
        });

        // Filter change events
        $('#customerFilter, #dateFrom, #dateTo').on('change', function() {
            table.draw();
        });

        // Clear filters button
        $('#clearFilters').click(function() {
            $('#customerFilter').val('');
            $('#dateFrom').val('');
            $('#dateTo').val('');
            table.search('').columns().search('').draw();
        });

        // Row selection for delete
        var selectedOrderId = null;
        $('#ordersTable tbody').on('click', 'tr', function(){
            if ($(this).hasClass('selected')) {
                $(this).removeClass('selected');
                selectedOrderId = null;
                $('#deleteBtn').prop('disabled', true);
            } else {
                table.$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
                selectedOrderId = $(this).data('order-id');
                $('#deleteBtn').prop('disabled', false);
            }
        });

        // Delete button click
        $('#deleteBtn').click(function() {
            if (selectedOrderId) {
                if (confirm('Are you sure you want to delete Order #' + selectedOrderId + '? This cannot be undone.')) {
                    window.location = 'orders_list.php?delete_order_id=' + selectedOrderId;
                }
            }
        });

        // Toggle details row
        $('#ordersTable tbody').on('click', 'td.toggle', function(e){
            e.stopPropagation();
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            var orderId = tr.data('order-id');
            var lines = allLines[orderId] || []
            

            if (row.child.isShown()){
                row.child.hide();
                tr.find('td.toggle').html('▶');
            } else {
                // Show details
                var html = '<table class="child-table">';
                html += '<thead><tr><th>Product Name</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead><tbody>';
                
                for (var i = 0; i < lines.length; i++) {
                    var line = lines[i];
                    var subtotal = (line.quantity * line.unit_price).toFixed(2);
                    html += '<tr>';
                    html += '<td>' + line.product_name + '</td>';
                    html += '<td>' + line.sku + '</td>';
                    html += '<td>' + line.quantity + '</td>';
                    html += '<td>$' + parseFloat(line.unit_price).toFixed(2) + '</td>';
                    html += '<td>$' + subtotal + '</td>';
                    html += '</tr>';
                }
                
                html += '</tbody></table>';
                row.child(html).show();
                tr.find('td.toggle').html('▼');
                
            }
        });
    });
    </script>
</body>
</html>