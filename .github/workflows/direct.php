<?php
session_start();
include('includes/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$currentUsername = $_SESSION['username'];
$currentAvatar = $_SESSION['avatar'] ?? 'default_avatar.png';

// Handle sending new messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipientId = (int)$_POST['recipient_id'];
    $message = trim($_POST['message']);

    if (!empty($message) && $recipientId > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $currentUserId, $recipientId, $message);
        $stmt->execute();
        $stmt->close();
        
        // Create notification
        $notificationMsg = "sent you a message";
        $link = "direct.php?user_id=".$recipientId;
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, trigger_user_id, type, message, link) VALUES (?, ?, 'message', ?, ?)");
        $stmt->bind_param("iiss", $recipientId, $currentUserId, $notificationMsg, $link);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: direct.php?user_id=".$recipientId);
    exit();
}

// Get conversation list (people you've messaged or who messaged you)
$conversations = [];
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.username, u.avatar, 
           (SELECT message FROM messages 
            WHERE (sender_id = ? AND recipient_id = u.id) OR (sender_id = u.id AND recipient_id = ?) 
            ORDER BY created_at DESC LIMIT 1) AS last_message,
           (SELECT created_at FROM messages 
            WHERE (sender_id = ? AND recipient_id = u.id) OR (sender_id = u.id AND recipient_id = ?) 
            ORDER BY created_at DESC LIMIT 1) AS last_message_time
    FROM users u
    JOIN messages m ON (u.id = m.sender_id OR u.id = m.recipient_id)
    WHERE (m.sender_id = ? OR m.recipient_id = ?) AND u.id != ?
    ORDER BY last_message_time DESC
");
$stmt->bind_param("iiiiiii", $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId);
$stmt->execute();
$conversationsResult = $stmt->get_result();
while ($convo = $conversationsResult->fetch_assoc()) {
    $conversations[] = $convo;
}
$stmt->close();

// Get messages for specific conversation if user_id is set
$messages = [];
$recipientId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$recipient = null;

if ($recipientId > 0) {
    // Get recipient info
    $stmt = $conn->prepare("SELECT id, username, avatar FROM users WHERE id = ?");
    $stmt->bind_param("i", $recipientId);
    $stmt->execute();
    $recipient = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($recipient) {
        // Mark messages as read
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ? AND is_read = 0");
        $stmt->bind_param("ii", $recipientId, $currentUserId);
        $stmt->execute();
        $stmt->close();

        // Get messages
        $stmt = $conn->prepare("
            SELECT m.*, u.username, u.avatar 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("iiii", $currentUserId, $recipientId, $recipientId, $currentUserId);
        $stmt->execute();
        $messagesResult = $stmt->get_result();
        while ($msg = $messagesResult->fetch_assoc()) {
            $messages[] = $msg;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Messages | SocialApp</title>
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
        --message-sent: #efefef;
        --message-received: #3897f0;
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
        height: 100vh;
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
    .dm-container {
        display: flex;
        max-width: 935px;
        height: calc(100vh - 60px);
        margin: 60px auto 0;
        border: 1px solid var(--border);
        background: var(--bg-white);
    }

    /* Conversations List */
    .conversations {
        width: 350px;
        border-right: 1px solid var(--border);
        overflow-y: auto;
    }

    .dm-header {
        padding: 15px;
        border-bottom: 1px solid var(--border);
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .new-chat-btn {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
    }

    .conversation {
        display: flex;
        padding: 10px 15px;
        border-bottom: 1px solid var(--border);
        cursor: pointer;
        transition: background 0.2s;
    }

    .conversation:hover {
        background: var(--bg-light);
    }

    .conversation-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        margin-right: 12px;
        object-fit: cover;
    }

    .conversation-info {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .conversation-username {
        font-weight: 600;
        margin-bottom: 4px;
    }

    .conversation-preview {
        font-size: 14px;
        color: var(--text-light);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .conversation-time {
        font-size: 12px;
        color: var(--text-light);
    }

    /* Messages Area */
    .messages-area {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .messages-header {
        padding: 15px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
    }

    .messages-header-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        margin-right: 12px;
        object-fit: cover;
    }

    .messages-header-username {
        font-weight: 600;
    }

    .messages-list {
        flex-grow: 1;
        padding: 20px;
        overflow-y: auto;
        background: var(--bg-light);
    }

    .message {
        max-width: 60%;
        margin-bottom: 15px;
        padding: 10px 15px;
        border-radius: 18px;
        font-size: 14px;
        line-height: 1.4;
        position: relative;
    }

    .message-sent {
        background: var(--message-sent);
        margin-left: auto;
        border-bottom-right-radius: 0;
    }

    .message-received {
        background: var(--message-received);
        color: white;
        margin-right: auto;
        border-bottom-left-radius: 0;
    }

    .message-time {
        font-size: 10px;
        color: var(--text-light);
        margin-top: 4px;
        text-align: right;
    }

    .message-received .message-time {
        color: rgba(255, 255, 255, 0.7);
    }

    .message-form {
        padding: 15px;
        border-top: 1px solid var(--border);
        display: flex;
    }

    .message-input {
        flex-grow: 1;
        padding: 10px 15px;
        border: 1px solid var(--border);
        border-radius: 20px;
        outline: none;
        font-size: 14px;
    }

    .send-btn {
        background: none;
        border: none;
        color: var(--primary);
        font-size: 20px;
        margin-left: 10px;
        cursor: pointer;
    }

    /* Empty State */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        text-align: center;
        padding: 20px;
    }

    .empty-icon {
        font-size: 48px;
        color: var(--text-light);
        margin-bottom: 20px;
    }

    .empty-title {
        font-size: 24px;
        font-weight: 300;
        margin-bottom: 10px;
    }

    .empty-text {
        color: var(--text-light);
        margin-bottom: 20px;
    }

    .start-chat-btn {
        background: var(--primary);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
    }

    /* Responsive */
    @media (max-width: 935px) {
        .dm-container {
            max-width: 100%;
            height: calc(100vh - 60px);
        }
    }

    @media (max-width: 735px) {
        .conversations {
            width: 100%;
            border-right: none;
            display: <?= $recipientId > 0 ? 'none' : 'block' ?>;
        }
        
        .messages-area {
            display: <?= $recipientId > 0 ? 'flex' : 'none' ?>;
        }
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
                    <img src="<?= htmlspecialchars($currentAvatar) ?>" class="user-avatar">
                </a>
            </div>
        </div>
    </nav>

    <!-- Direct Messages -->
    <div class="dm-container">
        <!-- Conversations List -->
        <div class="conversations">
            <div class="dm-header">
                <span><?= htmlspecialchars($currentUsername) ?></span>
                <button class="new-chat-btn"><i class="far fa-edit"></i></button>
            </div>
            
            <?php if (empty($conversations)): ?>
                <div class="empty-state">
                    <i class="far fa-paper-plane empty-icon"></i>
                    <h2 class="empty-title">Your Messages</h2>
                    <p class="empty-text">Send private messages to your friends</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $convo): ?>
                    <a href="direct.php?user_id=<?= $convo['id'] ?>" class="conversation">
                        <img src="<?= htmlspecialchars($convo['avatar'] ?? 'default_avatar.png') ?>" class="conversation-avatar">
                        <div class="conversation-info">
                            <div class="conversation-username"><?= htmlspecialchars($convo['username']) ?></div>
                            <div class="conversation-preview"><?= htmlspecialchars($convo['last_message']) ?></div>
                        </div>
                        <div class="conversation-time">
                            <?php 
                            $time = strtotime($convo['last_message_time']);
                            echo date('M j', $time); 
                            ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Messages Area -->
        <div class="messages-area">
            <?php if ($recipientId > 0 && $recipient): ?>
                <div class="messages-header">
                    <img src="<?= htmlspecialchars($recipient['avatar'] ?? 'default_avatar.png') ?>" class="messages-header-avatar">
                    <div class="messages-header-username"><?= htmlspecialchars($recipient['username']) ?></div>
                    <a href="direct.php" class="back-btn" style="margin-left: auto; display: none;"><i class="fas fa-arrow-left"></i></a>
                </div>
                
                <div class="messages-list" id="messages-list">
                    <?php if (empty($messages)): ?>
                        <div style="text-align: center; color: var(--text-light); margin-top: 50px;">
                            No messages yet. Start the conversation!
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?= $msg['sender_id'] == $currentUserId ? 'message-sent' : 'message-received' ?>">
                                <?= htmlspecialchars($msg['message']) ?>
                                <div class="message-time">
                                    <?= date('g:i A', strtotime($msg['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <form method="POST" class="message-form">
                    <input type="hidden" name="recipient_id" value="<?= $recipientId ?>">
                    <input type="text" name="message" placeholder="Message..." class="message-input" autocomplete="off">
                    <button type="submit" name="send_message" class="send-btn"><i class="far fa-paper-plane"></i></button>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <i class="far fa-comments empty-icon"></i>
                    <h2 class="empty-title">Select a conversation</h2>
                    <p class="empty-text">Choose an existing chat or start a new one</p>
                    <button class="start-chat-btn"><i class="fas fa-plus"></i> New Message</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Auto-scroll to bottom of messages
    const messagesList = document.getElementById('messages-list');
    if (messagesList) {
        messagesList.scrollTop = messagesList.scrollHeight;
    }
    
    // Responsive back button
    const backBtn = document.querySelector('.back-btn');
    if (backBtn) {
        if (window.innerWidth <= 735) {
            backBtn.style.display = 'block';
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 735) {
                backBtn.style.display = 'block';
            } else {
                backBtn.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>