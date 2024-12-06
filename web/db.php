<?php
// db.php
$servername = "localhost";
$username = "root";
$password = "your_db_password"; // Replace with your MySQL password
$dbname = "accounting_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
