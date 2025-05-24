<?php
include('includes/auth.php');
include('includes/db.php');

$sender_id = $_POST['sender_id'];
$receiver_id = $_POST['receiver_id'];
$message = $mysqli->real_escape_string($_POST['message']);

$mysqli->query("INSERT INTO messages (sender_id, receiver_id, message) VALUES ($sender_id, $receiver_id, '$message')");

echo "Message sent";?>
<div id="chat-box" style="border:1px solid #ccc; height:300px; overflow-y:scroll; padding:10px;"></div>
<input type="text" id="chat-message" placeholder="Type a message">
<button onclick="sendMessage(<?= $_SESSION['user_id'] ?>, <?= $receiver_id ?>)">Send</button>

<script>
    setInterval(() => {
        loadChat(<?= $_SESSION['user_id'] ?>, <?= $receiver_id ?>);
    }, 3000); // Poll every 3 seconds
</script>
