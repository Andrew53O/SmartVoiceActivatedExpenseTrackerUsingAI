<?php
// login.php
session_start();
require 'db.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($email) || empty($password)) {
        $message = "All fields are required.";
    } else {
        // Retrieve user from database
        $stmt = $conn->prepare("SELECT pid, password FROM person WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $hashed_password);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                // Password is correct
                $_SESSION['pid'] = $user_id;
                $_SESSION['email'] = $email;
                header("Location: dashboard.php");
                exit();
            } else {
                $message = "Incorrect password.";
            }
        } else {
            $message = "No account found with that email.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Voice Accounting</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php if($message != ''): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Login">
        </form>
        <a href="register.php">Don't have an account? Register here.</a>
    </div>
</body>
</html>
