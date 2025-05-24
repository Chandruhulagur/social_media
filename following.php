<?php
session_start();
include('includes/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];

// Get user ID from query param or fallback to current user
$profileUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentUserId;

// Verify the user exists
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$userResult = $stmt->get_result();
if ($userResult->num_rows === 0) {
    echo "User not found.";
    exit();
}
$profileUser = $userResult->fetch_assoc();
$stmt->close();

// Get following list
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.avatar 
    FROM follows f 
    JOIN users u ON f.followed_id = u.id 
    WHERE f.follower_id = ?
    ORDER BY u.username ASC
");
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$followingResult = $stmt->get_result();
$followingCount = $followingResult->num_rows;
$stmt->close();

// Check which of these users the current user follows
$followingStatus = [];
if ($followingCount > 0) {
    $followingIds = [];
    while ($row = $followingResult->fetch_assoc()) {
        $followingIds[] = $row['id'];
    }
    
    // Reset pointer for the result set
    $followingResult->data_seek(0);
    
    // Check follow status for each followed user
    $placeholders = implode(',', array_fill(0, count($followingIds), '?'));
    $types = str_repeat('i', count($followingIds));
    
    $stmt = $conn->prepare("
        SELECT followed_id 
        FROM follows 
        WHERE follower_id = ? 
        AND followed_id IN ($placeholders)
    ");
    $params = array_merge([$currentUserId], $followingIds);
    $stmt->bind_param(str_repeat('i', count($params)), ...$params);
    $stmt->execute();
    $currentFollowingResult = $stmt->get_result();
    
    while ($row = $currentFollowingResult->fetch_assoc()) {
        $followingStatus[$row['followed_id']] = true;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= htmlspecialchars($profileUser['username']) ?>'s Following</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #fafafa;
    margin: 0; padding: 0;
  }
  .container {
    max-width: 600px;
    margin: 20px auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  .header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
  }
  .back-btn {
    margin-right: 15px;
    font-size: 20px;
    text-decoration: none;
    color: #3498db;
  }
  .title {
    font-size: 20px;
    font-weight: bold;
    margin: 0;
  }
  .user-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .user-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
  }
  .user-item:last-child {
    border-bottom: none;
  }
  .user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 15px;
  }
  .user-info {
    flex-grow: 1;
  }
  .username {
    font-weight: bold;
    margin: 0 0 3px 0;
  }
  .follow-btn {
    padding: 6px 12px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s;
  }
  .follow-btn:hover {
    background: #2980b9;
  }
  .follow-btn.following {
    background: #e74c3c;
  }
  .follow-btn.following:hover {
    background: #c0392b;
  }
  .no-following {
    text-align: center;
    padding: 40px 0;
    color: #888;
  }
</style>
</head>
<body>

<div class="container">
  <div class="header">
    <a href="profile.php?user_id=<?= $profileUserId ?>" class="back-btn">&larr;</a>
    <h1 class="title"><?= htmlspecialchars($profileUser['username']) ?>'s Following</h1>
  </div>

  <ul class="user-list">
    <?php if ($followingCount == 0): ?>
      <div class="no-following">Not following anyone yet</div>
    <?php else: ?>
      <?php while ($followedUser = $followingResult->fetch_assoc()): ?>
        <li class="user-item">
          <img src="<?= htmlspecialchars($followedUser['avatar'] ?: 'default_avatar.png') ?>" alt="avatar" class="user-avatar" />
          <div class="user-info">
            <div class="username"><?= htmlspecialchars($followedUser['username']) ?></div>
          </div>
          
          <?php if ($followedUser['id'] != $currentUserId): ?>
            <form action="follow.php" method="POST">
              <input type="hidden" name="followed_id" value="<?= $followedUser['id'] ?>">
              <button type="submit" class="follow-btn <?= isset($followingStatus[$followedUser['id']]) ? 'following' : '' ?>">
                <?= isset($followingStatus[$followedUser['id']]) ? 'Unfollow' : 'Follow' ?>
              </button>
            </form>
          <?php endif; ?>
        </li>
      <?php endwhile; ?>
    <?php endif; ?>
  </ul>
</div>

</body>
</html>