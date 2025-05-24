<?php
session_start();
include('includes/db.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $bio = trim($_POST['bio'] ?? '');

    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3-50 characters.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Check if username or email exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username or email already taken.";
        }
        $stmt->close();
    }

    // Handle profile image upload
    $avatar_path = 'default_avatar.png';
    if (empty($errors) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $file_type = $_FILES['avatar']['type'];
        
        if (!array_key_exists($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, GIF files allowed for avatar.";
        } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) { // 2MB limit
            $errors[] = "Avatar image size should be less than 2MB.";
        } else {
            $ext = $allowed_types[$file_type];
            $new_filename = 'avatar_' . uniqid() . '.' . $ext;
            $upload_dir = 'uploads/avatars/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                $avatar_path = $target_file;
            } else {
                $errors[] = "Failed to upload avatar image.";
            }
        }
    }

    // Create user if no errors
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, avatar, bio) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $password_hash, $avatar_path, $bio);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;
            header("Location: profile.php");
            exit();
        } else {
            $errors[] = "Database error: " . $conn->error;
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
<title>Register | SocialApp</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');
  
  :root {
    --primary: #3498db;
    --secondary: #2ecc71;
    --dark: #2c3e50;
    --light: #ecf0f1;
    --danger: #e74c3c;
  }
  
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }
  
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
  }
  
  .register-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 450px;
    padding: 40px;
    position: relative;
    overflow: hidden;
  }
  
  .register-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
  }
  
  h2 {
    color: var(--dark);
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 30px;
    text-align: center;
  }
  
  .form-group {
    margin-bottom: 20px;
  }
  
  label {
    display: block;
    margin-bottom: 8px;
    color: var(--dark);
    font-weight: 500;
  }
  
  input[type="text"],
  input[type="email"],
  input[type="password"],
  textarea,
  .file-input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #dfe6e9;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
    font-family: 'Poppins', sans-serif;
  }
  
  input[type="text"]:focus,
  input[type="email"]:focus,
  input[type="password"]:focus,
  textarea:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
  }
  
  textarea {
    min-height: 100px;
    resize: vertical;
  }
  
  .file-input {
    padding: 10px;
    display: flex;
    align-items: center;
    cursor: pointer;
  }
  
  .file-input input[type="file"] {
    display: none;
  }
  
  .file-input-label {
    margin-left: 10px;
    color: #7f8c8d;
  }
  
  .btn {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
  }
  
  .btn-primary {
    background: var(--primary);
    color: white;
  }
  
  .btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
  }
  
  .errors {
    background: rgba(231, 76, 60, 0.1);
    border-left: 4px solid var(--danger);
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 4px;
  }
  
  .errors ul {
    list-style: none;
  }
  
  .errors li {
    color: var(--danger);
    font-size: 14px;
    margin-bottom: 5px;
  }
  
  .errors li:last-child {
    margin-bottom: 0;
  }
  
  .login-link {
    text-align: center;
    margin-top: 25px;
    color: #7f8c8d;
    font-size: 14px;
  }
  
  .login-link a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
  }
  
  .login-link a:hover {
    text-decoration: underline;
  }
  
  .avatar-preview {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #dfe6e9;
    margin: 10px auto 20px;
    display: block;
  }
</style>
</head>
<body>
  <div class="register-container">
    <h2>Join Our Community</h2>

    <?php if (!empty($errors)): ?>
      <div class="errors">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required />
      </div>
      
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
      </div>
      
      <div class="form-group">
        <label for="bio">Bio (Optional)</label>
        <textarea id="bio" name="bio"><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
      </div>
      
      <div class="form-group">
        <label>Profile Picture</label>
        <div class="file-input" onclick="document.getElementById('avatar').click()">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7f8c8d" stroke-width="2">
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7"></path>
            <line x1="16" y1="5" x2="22" y2="5"></line>
            <line x1="19" y1="2" x2="19" y2="8"></line>
            <circle cx="9" cy="9" r="2"></circle>
            <path d="M21 15l-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
          </svg>
          <span class="file-input-label" id="file-label">Choose an image...</span>
          <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewImage(this)" />
        </div>
        <img id="avatar-preview" class="avatar-preview" style="display: none;" />
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />
      </div>
      
      <div class="form-group">
        <label for="password_confirm">Confirm Password</label>
        <input type="password" id="password_confirm" name="password_confirm" required />
      </div>
      
      <button type="submit" class="btn btn-primary">Create Account</button>
    </form>
    
    <div class="login-link">
      Already have an account? <a href="login.php">Sign in</a>
    </div>
  </div>

  <script>
    function previewImage(input) {
      const preview = document.getElementById('avatar-preview');
      const fileLabel = document.getElementById('file-label');
      
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
          fileLabel.textContent = input.files[0].name;
        }
        
        reader.readAsDataURL(input.files[0]);
      }
    }
  </script>
</body>
</html>