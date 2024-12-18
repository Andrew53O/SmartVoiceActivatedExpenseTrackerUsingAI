<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['pid'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$id = $_POST['id'] ?? '';
$category = $_POST['category'] ?? '';
$item = $_POST['item'] ?? '';
$cost = $_POST['cost'] ?? '';

if (!$id || !$category || !$item || !$cost) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    $query = "UPDATE accounting SET category = ?, item = ?, cost = ? WHERE aid = ? AND pid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssdis", $category, $item, $cost, $id, $_SESSION['pid']);
    
    $success = $stmt->execute();
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
