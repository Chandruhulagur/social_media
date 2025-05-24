<?php
session_start();
include('includes/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];

// Fetch posts with user info and engagement data
$sql = "SELECT p.id AS post_id, p.user_id, p.media_path, p.media_type, p.caption, p.created_at, 
               u.username, u.avatar,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
               (SELECT 1 FROM likes WHERE user_id = ? AND post_id = p.id LIMIT 1) AS is_liked,
               (SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = p.user_id LIMIT 1) AS is_following
        FROM posts p 
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $currentUserId, $currentUserId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed | SocialApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #0095f6;
        --primary-light: #67b5fa;
        --text-dark: #262626;
        --text-light: #8e8e8e;
        --border: #dbdbdb;
        --bg-light: #fafafa;
        --bg-white: #ffffff;
        --danger: #ed4956;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    body {
        background-color: var(--bg-light);
        color: var(--text-dark);
    }

    /* Navigation */
    .navbar {
        position: fixed;
        top: 0;
        width: 100%;
        height: 60px;
        background: var(--bg-white);
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: center;
        z-index: 1000;
    }

    .nav-container {
        width: 100%;
        max-width: 975px;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        font-family: 'Billabong', cursive;
        font-size: 28px;
        font-weight: 600;
        color: var(--text-dark);
        text-decoration: none;
    }

    .nav-icons {
        display: flex;
        gap: 22px;
    }

    .nav-icon {
        font-size: 24px;
        color: var(--text-dark);
    }

    .user-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Main Content */
    .main-container {
        max-width: 935px;
        margin: 80px auto 30px;
        display: flex;
    }

    .feed {
        width: 100%;
        max-width: 614px;
        margin-right: 28px;
    }

    /* Stories */
    .stories {
        background: var(--bg-white);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
        display: flex;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .stories::-webkit-scrollbar {
        display: none;
    }

    .story {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-right: 15px;
    }

    .story-avatar {
        width: 66px;
        height: 66px;
        border-radius: 50%;
        padding: 2px;
        background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
    }

    .story-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 2px solid white;
        object-fit: cover;
    }

    .story-username {
        font-size: 12px;
        margin-top: 6px;
        max-width: 66px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Posts */
    .post {
        background: var(--bg-white);
        border: 1px solid var(--border);
        border-radius: 8px;
        margin-bottom: 24px;
    }

    .post-header {
        display: flex;
        align-items: center;
        padding: 14px 16px;
    }

    .post-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        margin-right: 12px;
        object-fit: cover;
    }

    .post-username {
        font-weight: 600;
        font-size: 14px;
        color: var(--text-dark);
        text-decoration: none;
    }

    .post-options {
        margin-left: auto;
        font-size: 18px;
    }

    .post-media {
        width: 100%;
        max-height: 767px;
        object-fit: cover;
    }

    .post-actions {
        padding: 6px 16px;
        display: flex;
        align-items: center;
    }

    .post-action {
        font-size: 24px;
        margin-right: 16px;
        color: var(--text-dark);
        background: none;
        border: none;
        cursor: pointer;
    }

    .post-action.save {
        margin-left: auto;
        margin-right: 0;
    }

    .post-likes {
        font-weight: 600;
        font-size: 14px;
        padding: 0 16px;
        margin-bottom: 8px;
    }

    .post-caption {
        padding: 0 16px;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .post-caption a {
        font-weight: 600;
        color: var(--text-dark);
        text-decoration: none;
    }

    .post-comments {
        padding: 0 16px;
        margin-bottom: 8px;
        font-size: 14px;
        color: var(--text-light);
    }

    .post-time {
        padding: 0 16px;
        margin-bottom: 8px;
        font-size: 10px;
        color: var(--text-light);
        text-transform: uppercase;
    }

    .post-comment-form {
        display: flex;
        border-top: 1px solid var(--border);
        padding: 16px;
    }

    .comment-input {
        flex-grow: 1;
        border: none;
        outline: none;
        font-size: 14px;
    }

    .post-button {
        background: none;
        border: none;
        color: var(--primary);
        font-weight: 600;
        font-size: 14px;
        opacity: 0.5;
        cursor: pointer;
    }

    /* Sidebar */
    .sidebar {
        width: 293px;
        position: sticky;
        top: 90px;
        height: fit-content;
    }

    .user-profile {
        display: flex;
        align-items: center;
        margin-bottom: 24px;
    }

    .sidebar-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        margin-right: 16px;
        object-fit: cover;
    }

    .user-info {
        flex-grow: 1;
    }

    .sidebar-username {
        font-weight: 600;
        font-size: 14px;
        color: var(--text-dark);
        text-decoration: none;
    }

    .user-name {
        font-size: 14px;
        color: var(--text-light);
    }

    .switch-button {
        background: none;
        border: none;
        color: var(--primary);
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
    }

    .suggestions-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .suggestions-title {
        font-weight: 600;
        font-size: 14px;
        color: var(--text-light);
    }

    .see-all {
        font-weight: 600;
        font-size: 12px;
        color: var(--text-dark);
        text-decoration: none;
    }

    .suggestion {
        display: flex;
        align-items: center;
        margin-bottom: 16px;
    }

    .suggestion-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        margin-right: 12px;
        object-fit: cover;
    }

    .suggestion-info {
        flex-grow: 1;
    }

    .suggestion-username {
        font-weight: 600;
        font-size: 14px;
        color: var(--text-dark);
        text-decoration: none;
    }

    .suggestion-relation {
        font-size: 12px;
        color: var(--text-light);
    }

    .follow-button {
        background: none;
        border: none;
        color: var(--primary);
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
    }

    .footer {
        margin-top: 30px;
        font-size: 11px;
        color: var(--text-light);
        line-height: 1.5;
    }

    .footer-links {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .footer-link {
        margin-right: 8px;
        margin-bottom: 4px;
        color: var(--text-light);
        text-decoration: none;
    }

    /* Responsive */
    @media (max-width: 935px) {
        .main-container {
            max-width: 614px;
        }
        .sidebar {
            display: none;
        }
    }

    @media (max-width: 614px) {
        .main-container {
            margin: 60px auto 0;
        }
        .feed {
            margin-right: 0;
        }
    }

    /* Animations */
    @keyframes heartBeat {
        0% { transform: scale(1); }
        25% { transform: scale(1.2); }
        50% { transform: scale(0.95); }
        100% { transform: scale(1); }
    }

    .liked {
        color: var(--danger) !important;
        animation: heartBeat 0.5s ease;
    }

    /* Custom Font */
    @font-face {
        font-family: 'Billabong';
        src: url('fonts/Billabong.ttf') format('truetype');
    }
    .post-user-info {
        display: flex;
        align-items: center;
        flex-grow: 1;
    }
    
    .follow-btn {
        margin-left: auto;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid var(--primary);
    }
    
    .follow-btn.follow {
        background: var(--primary);
        color: white;
    }
    
    .follow-btn.following {
        background: white;
        color: var(--text-dark);
    }
    
    .follow-btn:hover {
        opacity: 0.9;
    }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="feed.php" class="logo">SocialApp</a>
            <div class="nav-icons">
                <a href="feed.php" class="nav-icon"><i class="fas fa-home"></i></a>
                <a href="direct.php" class="nav-icon"><i class="fas fa-paper-plane"></i></a>
                <a href="upload.php" class="nav-icon"><i class="fas fa-plus-square"></i></a>
                <a href="explore.php" class="nav-icon"><i class="fas fa-compass"></i></a>
                <a href="activity.php" class="nav-icon"><i class="fas fa-heart"></i></a>
                <a href="profile.php?user_id=<?= $currentUserId ?>">
                    <img src="<?= htmlspecialchars($_SESSION['avatar'] ?? 'default_avatar.png') ?>" class="user-avatar">
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <div class="feed">
            <!-- Stories -->
            <div class="stories">
                <?php
                // Fetch stories (users to display in stories)
                $stmt = $conn->prepare("SELECT id, username, avatar FROM users WHERE id != ? ORDER BY RAND() LIMIT 10");
                $stmt->bind_param("i", $currentUserId);
                $stmt->execute();
                $stories = $stmt->get_result();
                
                while ($story = $stories->fetch_assoc()): 
                ?>
                    <div class="story">
                        <div class="story-avatar">
                            <img src="<?= htmlspecialchars($story['avatar'] ?? 'default_avatar.png') ?>">
                        </div>
                        <div class="story-username"><?= htmlspecialchars($story['username']) ?></div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Posts -->
            <?php if ($result->num_rows == 0): ?>
                <div class="no-posts" style="text-align: center; padding: 40px 0;">
                    <h3>No posts yet</h3>
                    <p>Follow people to see their posts or create your own</p>
                    <a href="upload.php" style="color: var(--primary); text-decoration: none;">Upload your first post</a>
                </div>
            <?php else: ?>
                <?php while ($post = $result->fetch_assoc()): ?>
                    <div class="post">
                        
                        <!-- Post Header -->
                        <div class="post-header">
                            <img src="<?= htmlspecialchars($post['avatar'] ?? 'default_avatar.png') ?>" class="post-avatar">
                            <a href="profile.php?user_id=<?= $post['user_id'] ?>" class="post-username"><?= htmlspecialchars($post['username']) ?></a>
                            <div class="post-options">
                                <i class="fas fa-ellipsis-h"></i>
                            </div>
                        </div>

                        <!-- Post Media -->
                        <?php if ($post['media_type'] === 'video'): ?>
                            <video controls class="post-media">
                                <source src="<?= htmlspecialchars($post['media_path']) ?>" type="video/mp4">
                            </video>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($post['media_path']) ?>" class="post-media">
                        <?php endif; ?>

                        <!-- Post Actions -->
                        <div class="post-actions">
                            <form action="like.php" method="POST" style="display: inline;">
                                <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                <button type="submit" class="post-action <?= $post['is_liked'] ? 'liked' : '' ?>">
                                    <i class="far fa-heart"></i>
                                </button>
                            </form>
                            <a href="post.php?id=<?= $post['post_id'] ?>" class="post-action">
                                <i class="far fa-comment"></i>
                            </a>
                            <a href="#" class="post-action">
                                <i class="far fa-paper-plane"></i>
                            </a>
                            <button class="post-action save">
                                <i class="far fa-bookmark"></i>
                            </button>
                        </div>

                        <!-- Post Likes -->
                        <div class="post-likes"><?= number_format($post['likes_count']) ?> likes</div>

                        <!-- Post Caption -->
                        <div class="post-caption">
                            <a href="profile.php?user_id=<?= $post['user_id'] ?>" class="post-username"><?= htmlspecialchars($post['username']) ?></a>
                            <?= nl2br(htmlspecialchars($post['caption'])) ?>
                        </div>

                        <!-- Post Comments -->
                        <?php if ($post['comments_count'] > 0): ?>
                            <div class="post-comments">
                                <a href="post.php?id=<?= $post['post_id'] ?>">
                                    View all <?= number_format($post['comments_count']) ?> comments
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Post Time -->
                        <div class="post-time">
                            <?php
                            $postTime = strtotime($post['created_at']);
                            $currentTime = time();
                            $diff = $currentTime - $postTime;
                            
                            if ($diff < 60) {
                                echo $diff . 's ago';
                            } elseif ($diff < 3600) {
                                echo floor($diff/60) . 'm ago';
                            } elseif ($diff < 86400) {
                                echo floor($diff/3600) . 'h ago';
                            } else {
                                echo date('M j, Y', $postTime);
                            }
                            ?>
                        </div>

                        <!-- Comment Form -->
                        <form action="comment.php" method="POST" class="post-comment-form">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                            <input type="text" placeholder="Add a comment..." class="comment-input" name="comment">
                            <button type="submit" class="post-button">Post</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- User Profile -->
            <div class="user-profile">
                <img src="<?= htmlspecialchars($_SESSION['avatar'] ?? 'uploads/p1.jpeg') ?>" class="sidebar-avatar">
                <div class="user-info">
                    <a href="profile.php?user_id=<?= $currentUserId ?>" class="sidebar-username"><?= htmlspecialchars($_SESSION['username']) ?></a>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                </div>
                <button class="switch-button">Switch</button>
            </div>

            <!-- Suggestions -->
            <div class="suggestions">
                <div class="suggestions-header">
                    <div class="suggestions-title">Suggestions For You</div>
                    <a href="explore.php" class="see-all">See All</a>
                </div>

                <?php
                // Fetch suggested users (people you don't follow)
                $stmt = $conn->prepare("SELECT u.id, u.username, u.avatar 
                                      FROM users u
                                      LEFT JOIN follows f ON u.id = f.followed_id AND f.follower_id = ?
                                      WHERE u.id != ? AND f.followed_id IS NULL
                                      ORDER BY RAND() LIMIT 5");
                $stmt->bind_param("ii", $currentUserId, $currentUserId);
                $stmt->execute();
                $suggestions = $stmt->get_result();
                
                while ($suggestion = $suggestions->fetch_assoc()): 
                ?>
                    <div class="suggestion">
                        <img src="<?= htmlspecialchars($suggestion['avatar'] ?? 'default_avatar.png') ?>" class="suggestion-avatar">
                        <div class="suggestion-info">
                            <a href="profile.php?user_id=<?= $suggestion['id'] ?>" class="suggestion-username"><?= htmlspecialchars($suggestion['username']) ?></a>
                            <div class="suggestion-relation">Suggested for you</div>
                        </div>
                        <form action="follow.php" method="POST" style="display: inline;">
                            <input type="hidden" name="followed_id" value="<?= $suggestion['id'] ?>">
                            <button type="submit" class="follow-button">Follow</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="footer-links">
                    <a href="#" class="footer-link">About</a>
                    <a href="#" class="footer-link">Help</a>
                    <a href="#" class="footer-link">Press</a>
                    <a href="#" class="footer-link">API</a>
                    <a href="#" class="footer-link">Jobs</a>
                    <a href="#" class="footer-link">Privacy</a>
                    <a href="#" class="footer-link">Terms</a>
                    <a href="#" class="footer-link">Locations</a>
                </div>
                <div>© 2023 SOCIALAPP</div>
            </div>
        </div>
    </div>

    <script>
    // Like button animation
    document.addEventListener('DOMContentLoaded', function() {
        const likeButtons = document.querySelectorAll('.post-action i.fa-heart');
        
        likeButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (this.classList.contains('far')) {
                    this.classList.remove('far');
                    this.classList.add('fas', 'liked');
                } else {
                    this.classList.remove('fas', 'liked');
                    this.classList.add('far');
                }
            });
        });
    });
    </script>
</body>
</html>