<?php
session_start();
require_once "db.php";

// Remove hardcoded session lines and add proper security check
if (!isset($_SESSION['userID'])) {
    header("Location: signin.php");
    exit();
}

if ($_SESSION['userType'] != "user") {
    header("Location: signin.php");
    exit();
}

$userID = $_SESSION['userID'];

$userStmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
$userStmt->bind_param("i", $userID);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

$recipesCountStmt = $conn->prepare("SELECT COUNT(*) AS totalRecipes FROM recipe WHERE userID = ?");
$recipesCountStmt->bind_param("i", $userID);
$recipesCountStmt->execute();
$recipesCountResult = $recipesCountStmt->get_result();
$totalRecipes = $recipesCountResult->fetch_assoc()['totalRecipes'];

$likesCountStmt = $conn->prepare("
    SELECT COUNT(l.recipeID) AS totalLikes
    FROM recipe r
    LEFT JOIN likes l ON r.id = l.recipeID
    WHERE r.userID = ?
");
$likesCountStmt->bind_param("i", $userID);
$likesCountStmt->execute();
$likesCountResult = $likesCountStmt->get_result();
$totalLikes = $likesCountResult->fetch_assoc()['totalLikes'];

$categoriesResult = $conn->query("SELECT * FROM recipecategory");

$selectedCategory = "All";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['category'])) {
    $selectedCategory = $_POST['category'];

    if ($selectedCategory == "All") {
        $recipesStmt = $conn->prepare("
            SELECT r.id, r.name, r.photoFileName, u.firstName, u.lastName, c.categoryName,
            (SELECT COUNT(*) FROM likes WHERE recipeID = r.id) AS likesCount
            FROM recipe r
            JOIN user u ON r.userID = u.id
            JOIN recipecategory c ON r.categoryID = c.id
            ORDER BY r.id DESC
        ");
        $recipesStmt->execute();
        $recipesResult = $recipesStmt->get_result();
    } else {
        $recipesStmt = $conn->prepare("
            SELECT r.id, r.name, r.photoFileName, u.firstName, u.lastName, c.categoryName,
            (SELECT COUNT(*) FROM likes WHERE recipeID = r.id) AS likesCount
            FROM recipe r
            JOIN user u ON r.userID = u.id
            JOIN recipecategory c ON r.categoryID = c.id
            WHERE r.categoryID = ?
            ORDER BY r.id DESC
        ");
        $recipesStmt->bind_param("i", $selectedCategory);
        $recipesStmt->execute();
        $recipesResult = $recipesStmt->get_result();
    }
} else {
    $recipesStmt = $conn->prepare("
        SELECT r.id, r.name, r.photoFileName, u.firstName, u.lastName, c.categoryName,
        (SELECT COUNT(*) FROM likes WHERE recipeID = r.id) AS likesCount
        FROM recipe r
        JOIN user u ON r.userID = u.id
        JOIN recipecategory c ON r.categoryID = c.id
        ORDER BY r.id DESC
    ");
    $recipesStmt->execute();
    $recipesResult = $recipesStmt->get_result();
}

$favouritesStmt = $conn->prepare("
    SELECT r.id, r.name, r.photoFileName
    FROM favourites f
    JOIN recipe r ON f.recipeID = r.id
    WHERE f.userID = ?
    ORDER BY r.id DESC
");
$favouritesStmt->bind_param("i", $userID);
$favouritesStmt->execute();
$favouritesResult = $favouritesStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Biteful | User</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="index.html" class="brand">
      <div class="logo">
        <img src="images/BiteFul-logo.png" alt="BiteFul Logo">
      </div>
      <div class="brand-text">
        <span class="brand-name">BiteFul</span>
        <span class="brand-tag">Fast & Easy Meals</span>
      </div>
    </a>

    <nav class="nav">
      <a href="user.php" class="nav-link">Dashboard</a>
      <a href="myRecipes.php" class="nav-link">My Recipes</a>
      <a href="logout.php" class="btn btn-ghost">Sign Out</a>
    </nav>
  </div>
</header>

<main class="container user-wrap">

  <section class="user-welcome">
    <h1 class="user-title">Welcome back, <span class="name"><?php echo htmlspecialchars($user['firstName']); ?></span></h1>
    <p class="user-sub">Here's what's happening with your recipes today.</p>
  </section>

  <section class="user-top">
    <div class="panel user-card">
      <img class="user-avatar-img" src="images/<?php echo htmlspecialchars($user['photoFileName']); ?>" alt="User photo">
      <div class="user-info">
        <h3 class="user-name"><?php echo htmlspecialchars($user['firstName'] . " " . $user['lastName']); ?></h3>
        <p class="user-email"><?php echo htmlspecialchars($user['emailAddress']); ?></p>
      </div>
    </div>

    <div class="panel stat stat-green">
      <div class="stat-label">My Recipes</div>
      <div class="stat-value"><?php echo $totalRecipes; ?></div>
      <div class="stat-sub">Created by you</div>
    </div>

    <div class="panel stat stat-pink">
      <div class="stat-label">Total Likes</div>
      <div class="stat-value"><?php echo $totalLikes; ?></div>
      <div class="stat-sub">Across all your recipes</div>
    </div>
  </section>

  <section class="panel section">
    <div class="section-bar">
      <div>
        <h2 class="section-title">All Available Recipes</h2>
        <p class="section-sub">Simple healthy meals in minutes with minimal ingredients.</p>
      </div>

      <form class="filter" method="POST" action="user.php">
        <label class="filter-label" for="category">Category</label>
        <select id="category" name="category">
          <option value="All" <?php if ($selectedCategory == "All") echo "selected"; ?>>All</option>
          <?php while ($cat = $categoriesResult->fetch_assoc()) { ?>
            <option value="<?php echo $cat['id']; ?>" <?php if ($selectedCategory == $cat['id']) echo "selected"; ?>>
              <?php echo htmlspecialchars($cat['categoryName']); ?>
            </option>
          <?php } ?>
        </select>
        <button class="filter-btn" type="submit">Filter</button>
      </form>
    </div>

    <div class="table-wrap">
      <?php if ($recipesResult->num_rows > 0) { ?>
      <table class="table">
        <thead>
          <tr>
            <th>Recipe</th>
            <th>Creator</th>
            <th>Likes</th>
            <th>Category</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($recipe = $recipesResult->fetch_assoc()) { ?>
          <tr>
            <td>
              <div class="recipe-cell">
                <img class="thumb-img" src="images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>" alt="Recipe image">
                <a class="recipe-link" href="viewRecipe.php?id=<?php echo $recipe['id']; ?>">
                  <?php echo htmlspecialchars($recipe['name']); ?>
                </a>
              </div>
            </td>
            <td>
              <div class="creator-cell">
                <?php echo htmlspecialchars($recipe['firstName'] . " " . $recipe['lastName']); ?>
              </div>
            </td>
            <td>
              <span class="likepill"><span class="heart">♥</span><?php echo $recipe['likesCount']; ?></span>
            </td>
            <td>
              <span class="cat"><?php echo htmlspecialchars($recipe['categoryName']); ?></span>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
      <?php } else { ?>
        <p>No recipes found.</p>
      <?php } ?>
    </div>
  </section>

  <section class="panel section">
    <div class="section-bar">
      <div>
        <h2 class="section-title">My Favourite Recipes</h2>
        <p class="section-sub">Recipes you saved for later.</p>
      </div>
    </div>

    <div class="table-wrap">
      <?php if ($favouritesResult->num_rows > 0) { ?>
      <table class="table">
        <thead>
          <tr>
            <th>Recipe</th>
            <th class="actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($fav = $favouritesResult->fetch_assoc()) { ?>
          <tr>
            <td>
              <div class="recipe-cell">
                <img class="thumb-img" src="images/<?php echo htmlspecialchars($fav['photoFileName']); ?>" alt="Recipe image">
                <a class="recipe-link" href="viewRecipe.php?id=<?php echo $fav['id']; ?>">
                  <?php echo htmlspecialchars($fav['name']); ?>
                </a>
              </div>
            </td>
            <td class="actions">
              <a class="remove-btn" href="removeFavourite.php?recipeID=<?php echo $fav['id']; ?>">Remove</a>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
      <?php } else { ?>
        <p>You do not have any favourite recipes.</p>
      <?php } ?>
    </div>
  </section>

</main>

<footer class="footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <span class="logo logo-footer" aria-hidden="true"></span>
      <span class="footer-name">BiteFul</span>
    </div>
    <div class="footer-copy">
      © 2026 BiteFul. All rights reserved. ADVANCED WEB TECH.
    </div>
  </div>
</footer>

</body>
</html>