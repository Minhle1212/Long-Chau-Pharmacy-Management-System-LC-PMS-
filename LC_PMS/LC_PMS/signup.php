<?php 
include_once("settings.php");
session_start();

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = sanitizeInput($_POST['SignupUsername']);
    $email    = sanitizeInput($_POST['SignupEmail']);
    // Hash the password
    $password = password_hash($_POST['SignupPassword'], PASSWORD_DEFAULT);
    $position = 0; // default role

    // Check if email exists
    $check = mysqli_prepare($conn, "SELECT 1 FROM LC_users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    if (mysqli_stmt_num_rows($check) > 0) {
        $signup_message = "Email is already registered.";
        mysqli_stmt_close($check);
    } else {
        mysqli_stmt_close($check);

        // Insert new user
        $insert = mysqli_prepare($conn, "
            INSERT INTO LC_users
              (username,email,password,position)
            VALUES (?,?,?,?)
        ");
        mysqli_stmt_bind_param($insert, "sssi", $username, $email, $password, $position);

        if (mysqli_stmt_execute($insert)) {
            $user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($insert);

            // Create loyalty account
            $stmt2 = mysqli_prepare($conn,
              "INSERT INTO LC_loyaltyaccount (user_id) VALUES (?)"
            );
            mysqli_stmt_bind_param($stmt2, 'i', $user_id);
            mysqli_stmt_execute($stmt2);
            $acct_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt2);

            // Redirect
            header("Location: login.php?signup=success");
            exit();
        } else {
            $signup_message = "Signup failed. Try again.";
            mysqli_stmt_close($insert);
        }
    }
}

mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <link rel="stylesheet" href="styles/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="./images/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <a class="home" href="index.php">
        <span class="fas fa-arrow-left"></span>
        Back to Home
    </a>
    <div class="container">
        <div class="sign-in">
            <div class="logo">
                <img src="./images/logo.png" alt=""> 
            </div>
            <form method="post" action="signup.php" id="form">
                <div class="sign-in-box">
                    <div class="text-box">
                        <input type="text" name="SignupUsername" required>
                        <span></span>
                        <label>Username</label>
                    </div>
                    <div class="text-box">
                        <input type="text" name="SignupEmail" required>
                        <span></span>
                        <label>Email</label>
                    </div>
                    <div class="text-box">
                        <input type="password" name="SignupPassword" required>
                        <span></span>
                        <label>Password</label>
                    </div>
                </div>
                <div class="button-row">
                    <input type="submit" id="login" name="signup" value="Sign up">
                    <p class="member">Already a member?
                        <a href="login.php">Login here</a>
                    </p>
                </div>
                <?php 
                    if (!empty($signup_message)) {
                        echo "<p class='alert'>$signup_message</p>";
                    }
                ?>
            </form>
        </div>
    </div>
</body>
</html>
