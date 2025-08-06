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
        include("header.inc");
        if ((isset($_SESSION['position'])) && ($_SESSION['position'] == 1)) {
    ?>
    <main>
        <section class="hero-content">
            <div class="hero-items">
                <h2>User & Staff Management</h2>
            </div>
        </section>

        <div class="action-buttons">
            <form action="people_manage.php" method="post">
                <input type="submit" name="show_staff" value="Show All Staff">
            </form>
            <form action="people_manage.php" method="post">
                <input type="submit" name="show_users" value="Show All Customers">
            </form>
        </div>

        <h2 class="branch-title">Create New Staff Account</h2>
        <form action="people_manage.php" method="post">
            <ul class="form-container">
                <li class="form-col">
                    <label for="new_username">Username:</label>
                    <input type="text" name="new_username" id="new_username" required>
                </li>
                <li class="form-col">
                    <label for="new_email">Email:</label>
                    <input type="email" name="new_email" id="new_email" required>
                </li>
                <li class="form-col">
                    <label for="new_password">Password:</label>
                    <input type="password" name="new_password" id="new_password" required>
                </li>
                <li class="form-col">
                    <label for="new_position">Role:</label>
                    <select name="new_position" id="new_position" required>
                        <option value="">Select Role</option>
                        <option value="1">Manager</option>
                        <option value="2">Staff</option>
                        <option value="3">Pharmacist</option>
                    </select>
                </li>
                <li class="form-col">
                    <label for="create_staff"></label>
                    <input type="submit" name="create_staff" value="Create Account" id="create_staff">
                </li>
            </ul>
        </form>

        <?php
            $conn = @mysqli_connect($host, $user, $password, $database);
            if (!$conn) {
                die("<p class='alert'>Database connection failed: " . mysqli_connect_error() . "</p>");
            }

            $sql = "";
            $title = "";
            $show_staff_table = false;

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                if (isset($_POST["update_position"])) {
                    $user_id_to_update = (int)$_POST['user_id'];
                    $new_position = (int)$_POST['new_position'];
                    if (in_array($new_position, [1, 2, 3])) {
                        $sql_update = "UPDATE LC_users SET position = ? WHERE user_id = ?";
                        $stmt = mysqli_prepare($conn, $sql_update);
                        mysqli_stmt_bind_param($stmt, "ii", $new_position, $user_id_to_update);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            if (mysqli_stmt_affected_rows($stmt) > 0) {
                                echo "<p class='notify'>Successfully updated position for User ID #$user_id_to_update.";
                            } else {
                                echo "<p class='notify'>User ID #$user_id_to_update not found or position was already set.";
                            }
                        } else {
                            echo "Database error: Failed to update position.";
                        }
                    } else {
                        echo "Invalid position selected.";
                    }
                    
                    
                } elseif (isset($_POST['show_staff'])) {
                    $title = "All Staff Members";
                    $sql = "SELECT user_id, username, email, position FROM LC_users WHERE position IN (1, 2, 3) ORDER BY position, username";
                    $show_staff_table = true;
                } elseif (isset($_POST['show_users'])) {
                    $title = "All Customer Accounts";
                    $sql = "SELECT user_id, username, email, position FROM LC_users WHERE position = 0 ORDER BY username";
                } elseif (isset($_POST["create_staff"])) {
                    $username = mysqli_real_escape_string($conn, $_POST['new_username']);
                    $email = mysqli_real_escape_string($conn, $_POST['new_email']);
                    $password = $_POST['new_password'];
                    $position = (int)$_POST['new_position'];

                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    if (in_array($position, [1, 2, 3])) {
                        $sql_insert = "INSERT INTO LC_users (username, email, password, position) VALUES (?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $sql_insert);
                        mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $password_hash, $position);

                        if (mysqli_stmt_execute($stmt)) {
                            echo "<p class='notify'>Successfully created new staff account for '$username'.";
                        } else {
                            echo "<p class='notify'>Database error: Failed to create account. The username or email might already exist.";
                        }
                    } else {
                        echo "<p class='notify'>Invalid position selected.";
                    }

                }
            }

            if ($sql !== "") {
                $result = mysqli_query($conn, $sql);

                if ($result && mysqli_num_rows($result) > 0) {
                    echo "<h2 class='center-text'>$title</h2>";
                    echo "<table>";
                    echo "<tr><th>User ID</th><th>Username</th><th>Email</th><th>Role</th>" . ($show_staff_table ? "<th>Change Role / Action</th>" : "") . "</tr>";
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        
                        $role = 'Unknown';
                        switch ($row['position']) {
                            case 0: $role = 'Customer'; break;
                            case 1: $role = 'Manager'; break;
                            case 2: $role = 'Staff'; break;
                            case 3: $role = 'Pharmacist'; break;
                        }

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($role) . "</td>";
                        if ($show_staff_table) {
                            echo "<td>
                                    <form action='people_manage.php' method='post' style='display:flex; gap:10px; align-items:center;'>
                                        <input type='hidden' name='user_id' value='" . $row['user_id'] . "'>
                                        <select name='new_position'>
                                            <option value='1'" . ($row['position'] == 1 ? ' selected' : '') . ">Manager</option>
                                            <option value='2'" . ($row['position'] == 2 ? ' selected' : '') . ">Staff</option>
                                            <option value='3'" . ($row['position'] == 3 ? ' selected' : '') . ">Pharmacist</option>
                                        </select>
                                        <input type='submit' name='update_position' value='Update'>
                                    </form>
                                  </td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='notify'>No users found for this category.</p>";
                }
                if ($result) mysqli_free_result($result);
            }
            mysqli_close($conn);

        ?>

        
    </main>
    <?php 
        include("footer.inc");
    ?>
</body>
</html>
<style>
    .drop:nth-child(2)::after{
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 5%;
    background-color: var(--color-secondary);
    }

    .drop:nth-child(1)::after{
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    }
</style>
<?php
} 
else {
    header("Location: index.php");
    exit();
}
?>