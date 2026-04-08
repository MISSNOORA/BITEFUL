<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BiteFul - Sign Up</title>
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
        <a class="tab" href="signin.php">Sign In</a>
        <a class="tab active" href="signup.php">Sign Up</a>
      </div>

      <h2 class="title">Create your account</h2>
      <p class="desc">New user? Fill the form below</p>

      <!-- Show error if email already exists -->
      <?php if (isset($_GET['error']) && $_GET['error'] == 'email_taken'): ?>
        <div class="error-msg">
          This email address is already registered. Please sign in or use a different email.
        </div>
      <?php endif; ?>

      <!-- Form posts to signup.php handler, enctype needed for file upload -->
      <form class="form" action="signup_handler.php" method="POST" enctype="multipart/form-data">

        <div class="profile">
          <div class="profile-circle">
            <span>👤</span>
          </div>
          <label class="upload">
            Upload photo (optional)
            <input name="photo" type="file" accept="image/*">
          </label>
        </div>

        <div class="two">
          <div class="group">
            <label>First Name *</label>
            <input name="firstName" type="text" placeholder="Lina" required>
          </div>
          <div class="group">
            <label>Last Name *</label>
            <input name="lastName" type="text" placeholder="Omar" required>
          </div>
        </div>

        <div class="group">
          <label>Email Address *</label>
          <input name="email" type="text" placeholder="Lina@gmail.com" required>
        </div>

        <div class="group">
          <label>Password *</label>
          <input name="password" type="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn green">Create Account →</button>
      </form>

    </div>
  </div>
  <script src="javaScript.js"></script>
</body>
</html>