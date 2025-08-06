<?php
require_once("settings.php");
session_start();

if (!isset($_SESSION['position']) || $_SESSION['position'] != 3) {
    header("Location: login.php");
    exit();
}

$conn = @mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    error_log('DB connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    echo "Sorry, we’re having some technical difficulties. Please try again later.";
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];

    error_log("Action: $action, Order ID: $order_id"); 

    if ($action === "approve") {
        $stmt = mysqli_prepare($conn, "UPDATE LC_orders SET status = 'processing' WHERE order_id = ?");
    } elseif ($action === "decline") {
        $stmt = mysqli_prepare($conn, "UPDATE LC_orders SET status = 'waiting_prescription' WHERE order_id = ?");
    }

    if (isset($stmt)) {
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        if (!mysqli_stmt_execute($stmt)) {
            die("Update failed: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    }
}

$query = "
    SELECT 
        p.order_id,
        p.prescription_data,
        p.prescription_type,
        o.status 
      FROM LC_prescriptions p
      JOIN LC_orders       o ON p.order_id = o.order_id
     WHERE o.status IN ('waiting_approval')
";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacist</title>

    <link rel="stylesheet" href="./styles/style.css" class="css">
    <link rel="stylesheet" href="./styles/pharmacist.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>
<body>
    <?php 
        include_once("header.inc");
    ?>
    <div class="main">
        <h2 class="page-title">Prescriptions Requiring Review</h2>
        
        <div class="prescriptions-container">
            <div class="table-header">
                <div>Order ID</div>
                <div>Prescription</div>
                <div>Status</div>
                <div>Actions</div>
            </div>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="prescription-row">
                        <div class="order-id">#<?= htmlspecialchars($row['order_id']) ?></div>
                        <div class="prescription-image-container">
                            <?php if (!empty($row['prescription_data'])): 
                                // Inline the blob ──
                                $b64  = base64_encode($row['prescription_data']);
                                $mime = htmlspecialchars($row['prescription_type'], ENT_QUOTES);
                                $src  = "data:$mime;base64,$b64";
                            ?>
                                <img 
                                src="<?= $src ?>" 
                                alt="Prescription" 
                                class="prescription-image"
                                onclick="openModal('<?= $src ?>')"
                                >
                            <?php else: ?>
                                <div class="no-image">
                                <i class="fas fa-image"></i> No image
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="status-badge status-waiting-approval"><?= htmlspecialchars($row['status']) ?></span>
                        </div>
                        <div class="action-buttons">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-approve">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                <button type="submit" name="action" value="decline" class="btn btn-decline">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-prescription-bottle"></i>
                    <h3>No prescriptions to review.</h3>
                    <p>All prescriptions have been processed. Check back later for new submissions.</p>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal()"><span>x</span></button>
            <img id="modalImage" class="modal-image" src="" alt="Enlarged prescription">
        </div>
    </div>

    <script>
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImage.src = imageSrc;
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

    </script>

</body>
</html>