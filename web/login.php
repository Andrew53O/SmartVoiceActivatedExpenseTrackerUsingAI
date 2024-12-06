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
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $hashed_password);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                // Password is correct
                $_SESSION['user_id'] = $user_id;
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
    <style>
        /* Basic styling for the form */
        body { font-family: Arial, sans-serif; background-color: #f2f2f2; }
        .container { width: 300px; padding: 20px; background-color: white; margin: auto; margin-top: 100px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input[type=email], input[type=password] { width: 100%; padding: 10px; margin: 5px 0 10px 0; border: 1px solid #ccc; border-radius: 4px; }
        input[type=submit] { width: 100%; padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        input[type=submit]:hover { background-color: #45a049; }
        .message { color: red; }
        a { display: block; text-align: center; margin-top: 10px; color: #4CAF50; text-decoration: none; }
    </style>
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
