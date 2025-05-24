<?php
session_start();
include('includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: feed.php");
    exit();
}

// Validate and sanitize input
$currentUserId = $_SESSION['user_id'];
$followedId = filter_input(INPUT_POST, 'followed_id', FILTER_VALIDATE_INT);

if (!$followedId || $followedId <= 0) {
    $_SESSION['error'] = "Invalid user ID";
    header("Location: feed.php");
    exit();
}

// Prevent self-following
if ($currentUserId === $followedId) {
    $_SESSION['error'] = "You cannot follow yourself";
    header("Location: feed.php");
    exit();
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $followedId);
$stmt->execute();
$userExists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$userExists) {
    $_SESSION['error'] = "User not found";
    header("Location: feed.php");
    exit();
}

// Check current follow status
$stmt = $conn->prepare("SELECT follower_id FROM follows WHERE follower_id = ? AND followed_id = ? LIMIT 1");
$stmt->bind_param("ii", $currentUserId, $followedId);
$stmt->execute();
$isFollowing = $stmt->get_result()->num_rows > 0;
$stmt->close();

// Toggle follow status
try {
    if ($isFollowing) {
        // Unfollow
        $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $action = 'unfollowed';
    } else {
        // Follow
        $stmt = $conn->prepare("INSERT INTO follows (follower_id, followed_id, created_at) VALUES (?, ?, NOW())");
        $action = 'followed';
    }
    
    $stmt->bind_param("ii", $currentUserId, $followedId);
    $stmt->execute();
    $stmt->close();
    
    // Create notification if following (not for unfollowing)
    if (!$isFollowing) {
        $message = "started following you";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, trigger_user_id, type, message, link) VALUES (?, ?, 'follow', ?, ?)");
        $link = "profile.php?user_id=".$currentUserId;
        $stmt->bind_param("iiss", $followedId, $currentUserId, $message, $link);
        $stmt->execute();
        $stmt->close();
    }
    
    $_SESSION['success'] = "Successfully ".$action." user";
    
} catch (Exception $e) {
    $_SESSION['error'] = "Database error: ".$e->getMessage();
}

// Redirect back with success/error message
header("Location: ".$_SERVER['HTTP_REFERER']);
exit();
?>