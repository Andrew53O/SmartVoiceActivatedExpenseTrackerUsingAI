<?php
// db.php
$servername = "localhost:3306";
$username = "root";
$password = ""; // Replace with your MySQL password
$dbname = "voice_based_accounting_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
