<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BiteFul - Sign In</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="bg-page sign-page">
  <div class="bg-overlay"></div>
  <div class="center">
    <div class="box">
      <a class="back-link" href="index.html">← Back to Home</a>

      <div class="top">
        <img class="logo-img" src="images/biteful-logo.png" alt="BiteFul Logo">
        <div>
          <h1 class="site-name">BiteFul</h1>
          <p class="site-sub">Fast & Easy Meals</p>
        </div>
      </div>

      <div class="tabs">
        <a class="tab active" href="signin.html">Sign In</a>
        <a class="tab" href="signup.php">Sign Up</a>
      </div>

      <h2 class="title">Welcome back</h2>
      <p class="desc">Sign in to continue to your account</p>

      <!-- Show error message if redirected back with an error -->
      <?php if (isset($_GET['error'])): ?>
        <div class="error-msg">
          <?php
            if ($_GET['error'] == 'blocked')   echo "Your account has been blocked. Please contact support.";
            if ($_GET['error'] == 'invalid')   echo "Invalid email or password. Please try again.";
          ?>
        </div>
      <?php endif; ?>

      <!-- ONE login form, action goes to login.php -->
      <form class="form" action="login.php" method="POST">
        <div class="group">
          <label>Email Address</label>
          <input name="email" type="text" placeholder="john@example.com" required>
        </div>
        <div class="group">
          <label>Password</label>
          <input name="password" type="password" placeholder="••••••••" required>
        </div>
        <!-- Single login button — PHP will redirect based on user type -->
        <button type="submit" class="btn green">Sign In →</button>
      </form>

    </div>
  </div>
  <script src="javaScript.js"></script>
</body>
</html>