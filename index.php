<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SocialFlow</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to bottom, #1e0a3c, #4b1c69);
      color: white;
      text-align: center;
    }

    header {
      display: flex;
      justify-content: space-between;
      padding: 1rem 2rem;
      background-color: rgba(0, 0, 0, 0.2);
      align-items: center;
    }

    .logo {
      font-size: 1.5rem;
      font-weight: bold;
      color: #ff88dc;
    }

    .auth-buttons a {
      margin-left: 1rem;
      padding: 0.5rem 1rem;
      font-size: 1rem;
      border: none;
      border-radius: 8px;
      text-decoration: none;
      display: inline-block;
    }

    .login-btn {
      background: transparent;
      color: white;
      border: 1px solid white;
    }

    .get-started-btn {
      background: linear-gradient(90deg, #c754ff, #ff5f8f);
      color: white;
    }

    h1 span {
      color: #ff88dc;
    }

    .main-content {
      padding: 4rem 2rem;
    }

    .join-button {
      background: linear-gradient(90deg, #c754ff, #ff5f8f);
      color: white;
      padding: 1rem 2rem;
      border: none;
      border-radius: 12px;
      font-size: 1.1rem;
      margin-top: 2rem;
      text-decoration: none;
      display: inline-block;
    }

    .features {
      display: flex;
      justify-content: center;
      gap: 2rem;
      margin-top: 3rem;
      flex-wrap: wrap;
    }

    .feature-card {
      background: rgba(255, 255, 255, 0.05);
      padding: 2rem;
      border-radius: 16px;
      width: 250px;
      cursor: pointer;
      transition: transform 0.3s ease, background 0.3s ease;
      text-decoration: none;
      color: white;
    }

    .feature-card:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-5px);
    }

    .feature-icon {
      font-size: 2rem;
      margin-bottom: 1rem;
    }

    .feature-title {
      font-size: 1.2rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }

    .feature-text {
      font-size: 0.9rem;
      color: #ccc;
    }
  </style>
</head>
<body>

  <header>
    <div class="logo">SocialFlow</div>
    <div class="auth-buttons">
      <a href="login.php" class="login-btn">Login</a>
      <a href="register.php" class="get-started-btn">Get Started</a>
    </div>
  </header>

  <section class="main-content">
    <h1>Welcome to <span>SocialFlow</span></h1>
    <p>Connect, share, and discover amazing content with people around the world. Your social media experience, reimagined.</p>
    <a href="/join.html" class="join-button">Join SocialFlow →</a>

    <div class="features">
      <a href="/connect.html" class="feature-card">
        <div class="feature-icon">👥</div>
        <div class="feature-title">Connect</div>
        <div class="feature-text">Build meaningful relationships with friends and communities.</div>
      </a>
      <a href="/share.html" class="feature-card">
        <div class="feature-icon">💬</div>
        <div class="feature-title">Share</div>
        <div class="feature-text">Express yourself through posts, stories, and real-time updates.</div>
      </a>
      <a href="/discover.html" class="feature-card">
        <div class="feature-icon">🖼️</div>
        <div class="feature-title">Discover</div>
        <div class="feature-text">Explore trending content and find inspiration from creators worldwide.</div>
      </a>
    </div>
  </section>

</body>
</html>
