<?php
session_start();
include('config.php');

$user_id = $_SESSION['user_id'];

// Fetch new notifications
$query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode([
    'new_notifications' => count($notifications) > 0,
    'notifications' => $notifications
]);
?>
