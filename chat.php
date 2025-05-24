<?php
include('includes/auth.php');
include('includes/db.php');

// Get sender and receiver IDs
$user_id = $_GET['user_id'];
$receiver_id = $_GET['receiver_id'];

// Fetch messages between both users
$query = "
    SELECT * FROM messages
    WHERE (sender_id = $user_id AND receiver_id = $receiver_id)
       OR (sender_id = $receiver_id AND receiver_id = $user_id)
    ORDER BY timestamp ASC
";

$result = $mysqli->query($query);

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);
