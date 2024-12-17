<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require 'db.php';

try {
    if (!isset($_SESSION['pid'])) {
        throw new Exception('No session PID found');
    }

    $query = "SELECT * FROM accounting WHERE pid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $_SESSION['pid']);
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    $results = [];
    
    // Fetch all rows
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    
    // Close the statement
    $stmt->close();
    
    // Ensure proper JSON encoding
    $json = json_encode($results, JSON_PRETTY_PRINT);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON encoding error: ' . json_last_error_msg());
    }
    
    echo $json;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
