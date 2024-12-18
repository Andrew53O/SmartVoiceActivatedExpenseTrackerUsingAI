<?php
// filepath: /path/to/web/upload_data.php
// upload_data.php
header('Content-Type: application/json');

require 'db.php';

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

// Extract data
$food = $data['food'];
$price = $data['price'];
$meal_type = $data['meal_type'];

// Set the userID 
$pid = 2;

// Insert data into the database
$stmt = $conn->prepare("INSERT INTO accounting (pid, item, cost, category) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isds", $pid, $food, $price, $meal_type);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>