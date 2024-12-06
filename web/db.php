<?php
// db.php
$servername = "localhost:8888";
$username = "root";
$password = "your_db_password"; // Replace with your MySQL password
$dbname = "voice_based_accounting_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
