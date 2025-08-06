<?php
session_start();
require 'settings.php';

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = sanitizeInput($_POST['LoginEmail']);
    $password = $_POST['LoginPassword'];  

    // Fetch user by email
    $stmt = mysqli_prepare($conn,
      "SELECT user_id, username, email, password, position
         FROM LC_users
        WHERE email = ?"
    );
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        // Verify the hash
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['position'] = $user['position'];

            // Redirect based on role
            switch ($user['position']) {
                case 1: header("Location: manage.php");      break;
                case 2: header("Location: staff.php");       break;
                case 3: header("Location: pharmacist.php");  break;
                default: header("Location: index.php");      break;
            }
            exit();
        } else {
            $err_message = "Incorrect email or password.";
        }
    } else {
        $err_message = "Incorrect email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link rel="stylesheet" href="styles/login.css" class="css">

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
            <form method="post" action="login.php" id="form">
                <div class="sign-in-box">
                    <div class="text-box">
                        <input type="text" name="LoginEmail" required id="email">
                        <span></span>
                        <label>Email</label>
                    </div>
                    <div class="text-box">
                        <input type="password" name="LoginPassword" required id="password">
                        <span></span>
                        <label>Password</label>
                    </div>
                    <p class="pass">Forgot Password?</p>
                </div>
                <div class="button-row">
                    <!-- <button class="login" type="button" id="login">Sign in</button> -->
                    <input type="submit" name="login" id="login" value="Sign in">
                    <p class="member">Not a member?
                        <a href="signup.php">Signup here</a>
                    </p>
                </div>
                <?php 
                    if (isset($err_message)) {
                        echo "<p class='alert'>$err_message</p>";
                    }
                ?>
            </form>

        </div> 
    </div> 
</body>
</html>
