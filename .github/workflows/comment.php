<?php
include('includes/auth.php');
include('includes/db.php');
$comment = $_POST['comment'];
$mysqli->query("INSERT INTO comments (user_id, post_id, comment) VALUES ($_SESSION[user_id], $_POST[post_id], '$comment')");
header("Location: feed.php");?>
<html>
<script src="js/main.js"></script>
</html>