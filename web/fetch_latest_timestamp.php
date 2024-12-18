
<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['pid'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require 'db.php';

try {
    $query = "SELECT MAX(createdOn) as latestTimestamp FROM accounting WHERE pid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $_SESSION['pid']);
    $stmt->execute();
    $stmt->bind_result($latestTimestamp);
    $stmt->fetch();
    $stmt->close();

    echo json_encode(['success' => true, 'latestTimestamp' => $latestTimestamp]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching timestamp']);
}

$conn->close();
?>