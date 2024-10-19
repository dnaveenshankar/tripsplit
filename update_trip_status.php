<?php
session_start();
include 'db_connection.php';

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Check if tripId and status are provided
if (isset($data['tripId']) && isset($data['status'])) {
    $tripId = $data['tripId'];
    $status = $data['status'] === 'on' ? 'on' : 'off'; // Ensure the status is either 'on' or 'off'

    // Prepare the update statement
    $stmt = $conn->prepare("UPDATE trips SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $tripId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
}

$conn->close();
?>
