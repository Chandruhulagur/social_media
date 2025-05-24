<?php
session_start();
include('includes/db.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $errors[] = "Please enter username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($user_id, $password_hash);
        if ($stmt->fetch()) {
            if (password_verify($password, $password_hash)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                header("Location: feed.php");
                exit;
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "User not found.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login | SocialSite</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
  body {
    background: linear-gradient(135deg, #f7971e, #ffd200);
    font-family: 'Poppins', sans-serif;
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .login-container {
    background: white;
    padding: 40px 50px;
    border-radius: 15px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    width: 350px;
    text-align: center;
  }
  h2 {
    margin-bottom: 25px;
    color: #c47f00;
  }
  input[type="text"], input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    margin: 10px 0 20px 0;
    border: 2px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
  }
  input[type="text"]:focus, input[type="password"]:focus {
    border-color: #c47f00;
    outline: none;
  }
  button {
    width: 100%;
    background: #c47f00;
    color: white;
    border: none;
    padding: 14px 0;
    font-size: 18px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease;
  }
  button:hover {
    background: #9f6500;
  }
  .errors {
    color: #b00020;
    margin-bottom: 15px;
    font-size: 14px;
    text-align: left;
  }
  .link {
    margin-top: 15px;
    font-size: 14px;
    color: #555;
  }
  .link a {
    color: #c47f00;
    text-decoration: none;
    font-weight: 600;
  }
  .link a:hover {
    text-decoration: underline;
  }
  .forgot-password {
    margin-top: 12px;
    font-size: 13px;
  }
  .forgot-password a {
    color: #c47f00;
    text-decoration: none;
    font-weight: 500;
  }
  .forgot-password a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>
  <div class="login-container">
    <h2>Welcome Back</h2>

    <?php if ($errors): ?>
      <div class="errors">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?=htmlspecialchars($e)?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="text" name="username" placeholder="Username" value="<?=htmlspecialchars($_POST['username'] ?? '')?>" required />
      <input type="password" name="password" placeholder="Password" required />
      <button type="submit">Login</button>
    </form>

    <div class="forgot-password">
      <a href="forgot_password.php">Forgot password?</a>
    </div>

    <div class="link">
      Don't have an account? <a href="register.php">Register here</a>
    </div>
  </div>
</body>
</html>
