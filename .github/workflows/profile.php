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

// Fetch user info
$stmt = $conn->prepare("SELECT username, email, avatar, bio FROM users WHERE id = ?");
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$userResult = $stmt->get_result();
if ($userResult->num_rows === 0) {
    echo "User not found.";
    exit();
}
$user = $userResult->fetch_assoc();
$stmt->close();

// Check if current user follows this profile
$isFollowing = false;
if ($profileUserId != $currentUserId) {
    $stmt = $conn->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ? LIMIT 1");
    $stmt->bind_param("ii", $currentUserId, $profileUserId);
    $stmt->execute();
    $isFollowing = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

// Get follower count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE followed_id = ?");
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$followerCount = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get following count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$followingCount = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Fetch user posts
$stmt = $conn->prepare("SELECT id, media_path, media_type, caption, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$postsResult = $stmt->get_result();
$postsCount = $postsResult->num_rows;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= htmlspecialchars($user['username']) ?>'s Profile</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #fafafa;
    margin: 0; padding: 0;
  }
  .container {
    max-width: 700px;
    margin: 20px auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  .profile-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
  }
  .avatar {
    width: 100px; height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #3498db;
  }
  .userinfo {
    flex-grow: 1;
  }
  .username {
    font-size: 28px;
    font-weight: bold;
    margin: 0 0 5px 0;
  }
  .email {
    color: #666;
    margin: 0 0 10px 0;
  }
  .bio {
    color: #444;
    margin: 10px 0;
    line-height: 1.4;
  }
  .stats {
    display: flex;
    gap: 20px;
    margin: 15px 0;
  }
  .stat {
    text-align: center;
    cursor: pointer;
  }
  .stat-count {
    font-weight: bold;
    font-size: 18px;
  }
  .stat-label {
    font-size: 14px;
    color: #666;
  }
  .follow-btn {
    padding: 10px 18px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.2s;
  }
  .follow-btn:hover {
    background: #2980b9;
  }
  .follow-btn.unfollow {
    background: #e74c3c;
  }
  .follow-btn.unfollow:hover {
    background: #c0392b;
  }
  .edit-btn {
    padding: 10px 18px;
    background: #2ecc71;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.2s;
    margin-left: 10px;
  }
  .edit-btn:hover {
    background: #27ae60;
  }
  .action-buttons {
    display: flex;
    height: fit-content;
  }
  .posts-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 30px 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
  }
  .posts-title {
    font-size: 20px;
    font-weight: bold;
    margin: 0;
  }
  .posts {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(200px,1fr));
    gap: 15px;
  }
  .post-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
    transition: transform 0.2s;
  }
  .post-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
  .post-item img,
  .post-item video {
    width: 100%;
    display: block;
    max-height: 200px;
    object-fit: cover;
  }
  .caption {
    padding: 8px;
    font-size: 14px;
    color: #333;
  }
  .no-posts {
    text-align: center;
    color: #888;
    padding: 40px 0;
    font-size: 16px;
  }
</style>
</head>
<body>

<div class="container">
  <div class="profile-header">
    <img src="<?= htmlspecialchars($user['avatar'] ?: 'default_avatar.png') ?>" alt="avatar" class="avatar" />
    <div class="userinfo">
      <h1 class="username"><?= htmlspecialchars($user['username']) ?></h1>
      <p class="email"><?= htmlspecialchars($user['email']) ?></p>
      
      <?php if (!empty($user['bio'])): ?>
        <p class="bio"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
      <?php endif; ?>
      
      <div class="stats">
        <div class="stat" onclick="location.href='follow.php?user_id=<?= $profileUserId ?>'">
          <div class="stat-count"><?= $followerCount ?></div>
          <div class="stat-label">Followers</div>
        </div>
        <div class="stat" onclick="location.href='following.php?user_id=<?= $profileUserId ?>'">
          <div class="stat-count"><?= $followingCount ?></div>
          <div class="stat-label">Following</div>
        </div>
        <div class="stat">
          <div class="stat-count"><?= $postsCount ?></div>
          <div class="stat-label">Posts</div>
        </div>
      </div>
    </div>

    <div class="action-buttons">
      <?php if ($profileUserId == $currentUserId): ?>
        <button class="edit-btn" onclick="location.href='edit_profile.php'">Edit Profile</button>
      <?php else: ?>
        <form action="follow.php" method="POST">
          <input type="hidden" name="followed_id" value="<?= $profileUserId ?>">
          <button type="submit" class="follow-btn <?= $isFollowing ? 'unfollow' : '' ?>">
            <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="posts-header">
    <h2 class="posts-title">Posts</h2>
  </div>

  <div class="posts">
    <?php if ($postsCount == 0): ?>
      <div class="no-posts">This user hasn't posted anything yet.</div>
    <?php else: ?>
      <?php while ($post = $postsResult->fetch_assoc()): ?>
        <div class="post-item" onclick="location.href='post.php?id=<?= $post['id'] ?>'">
          <?php if ($post['media_type'] === 'video'): ?>
            <video controls>
              <source src="<?= htmlspecialchars($post['media_path']) ?>" type="video/mp4" />
              Your browser does not support the video tag.
            </video>
          <?php else: ?>
            <img src="<?= htmlspecialchars($post['media_path']) ?>" alt="post image" />
          <?php endif; ?>
          <?php if (!empty($post['caption'])): ?>
            <div class="caption"><?= nl2br(htmlspecialchars($post['caption'])) ?></div>
          <?php endif; ?>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

</body>
</html>