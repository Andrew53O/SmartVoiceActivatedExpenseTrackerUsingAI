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

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit();
}

try {
    $query = "DELETE FROM accounting WHERE aid = ? AND pid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $id, $_SESSION['pid']);
    
    $success = $stmt->execute();
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
