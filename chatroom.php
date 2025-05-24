<?php
include('includes/auth.php');
include('includes/db.php');

// Receiver ID from GET parameter
if (!isset($_GET['user'])) {
    echo "No user selected for chat.";
    exit;
}

$receiver_id = (int)$_GET['user'];

// Fetch receiver username
$res = $mysqli->query("SELECT username FROM users WHERE id = $receiver_id");
if ($res->num_rows === 0) {
    echo "User not found.";
    exit;
}
$receiver = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with <?= htmlspecialchars($receiver['username']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/main.js" defer></script>
</head>
<body>

<h2>Chat with <?= htmlspecialchars($receiver['username']) ?></h2>

<div id="chat-box" style="border:1px solid #ccc; height:300px; overflow-y:auto; padding:10px; background:#fff;"></div>

<div style="margin-top:10px;">
    <input type="text" id="chat-message" placeholder="Type a message..." style="width:80%;">
    <button onclick="sendMessage(<?= $_SESSION['user_id'] ?>, <?= $receiver_id ?>)">Send</button>
</div>

<p><a href="feed.php">← Back to Feed</a></p>

<script>
    // Auto-load chat every 3 seconds
    setInterval(() => {
        loadChat(<?= $_SESSION['user_id'] ?>, <?= $receiver_id ?>);
    }, 3000);

    // Initial load
    loadChat(<?= $_SESSION['user_id'] ?>, <?= $receiver_id ?>);
</script>

</body>
</html>
