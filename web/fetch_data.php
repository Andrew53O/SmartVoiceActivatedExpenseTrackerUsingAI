<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['pid'])) {
    echo json_encode([]);
    exit();
}

require 'db.php';

$user_id = $_SESSION['pid'];
$sql = "SELECT * FROM accounting WHERE pid = ? ORDER BY createdOn DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$data = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);

$stmt->close();
$conn->close();
?>
