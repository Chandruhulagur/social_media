<?php
session_start();
include('includes/db.php');
include('includes/auth.php');  // Auth should set $_SESSION['user_id']

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id'] ?? 0);

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

// Check if user already liked
$sql_check = "SELECT id FROM likes WHERE user_id = ? AND post_id = ?";
$stmt = $mysqli->prepare($sql_check);
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // User liked, so remove like (unlike)
    $stmt->close();
    $sql_del = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
    $stmt_del = $mysqli->prepare($sql_del);
    $stmt_del->bind_param("ii", $user_id, $post_id);
    $stmt_del->execute();
    $stmt_del->close();

    $action = 'unliked';
} else {
    // User not liked, insert like
    $stmt->close();
    $sql_ins = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
    $stmt_ins = $mysqli->prepare($sql_ins);
    $stmt_ins->bind_param("ii", $user_id, $post_id);
    $stmt_ins->execute();
    $stmt_ins->close();

    $action = 'liked';
}

// Get updated likes count
$sql_count = "SELECT COUNT(*) AS total_likes FROM likes WHERE post_id = ?";
$stmt_count = $mysqli->prepare($sql_count);
$stmt_count->bind_param("i", $post_id);
$stmt_count->execute();
$stmt_count->bind_result($total_likes);
$stmt_count->fetch();
$stmt_count->close();

echo json_encode([
    'success' => true,
    'action' => $action,
    'total_likes' => $total_likes
]);
