<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID'])) {
    header("Location: signin.php");
    exit();
}

$userID = $_SESSION['userID'];

/* ✅ Secure Query */
$stmt = $conn->prepare("SELECT * FROM recipe WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Recipes | BiteFul</title>
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
      <a href="my-recipes.php" class="nav-link">My Recipes</a>
      <a href="logout.php" class="btn btn-ghost">Sign Out</a>
    </nav>
  </div>
</header>

<main class="container my-recipes-page">

  <div class="page-head">
    <div>
      <h1 class="page-title">My Recipes</h1>
      <p class="page-sub">Manage all recipes you’ve added</p>
    </div>

    <a href="add-recipe.php" class="btn btn-primary">
      ➕ Add New Recipe
    </a>
  </div>

  <div class="recipes-table-wrap">
    <table class="recipes-table">
      <thead>
        <tr>
          <th>Recipe</th>
          <th>Ingredients</th>
          <th>Instructions</th>
          <th>Video</th>
          <th>Likes</th>
          <th>Edit</th>
          <th>Delete</th>
        </tr>
      </thead>

      <tbody>

<?php if ($result->num_rows > 0) { ?>
<?php while ($row = $result->fetch_assoc()) { 
    $recipe_id = $row['id'];

    /* ✅ Likes count */
    $like_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM Likes WHERE recipeID = ?");
    $like_stmt->bind_param("i", $recipe_id);
    $like_stmt->execute();
    $like_result = $like_stmt->get_result();
    $likes = $like_result->fetch_assoc()['total'];

    /* ✅ Image fallback */
    $img = !empty($row['photoFileName']) ? $row['photoFileName'] : 'default.png';
?>

<tr>
  <!-- Recipe -->
  <td>
    <a href="viewRecipe.php?id=<?php echo $recipe_id; ?>" class="recipe-cell">
      <img src="images/<?php echo htmlspecialchars($img); ?>" class="recipe-thumb">
      <span class="recipe-name"><?php echo htmlspecialchars($row['name']); ?></span>
    </a>
  </td>

  <!-- Ingredients -->
  <td>
    <ul class="list">
    <?php
    $ing_stmt = $conn->prepare("SELECT * FROM Ingredients WHERE recipeID = ?");
    $ing_stmt->bind_param("i", $recipe_id);
    $ing_stmt->execute();
    $ing_result = $ing_stmt->get_result();

    while ($i = $ing_result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($i['ingredientName']) . " - " . htmlspecialchars($i['ingredientQuantity']) . "</li>";
    }
    ?>
    </ul>
  </td>

  <!-- Instructions -->
  <td>
    <ol class="list">
    <?php
    $ins_stmt = $conn->prepare("SELECT * FROM Instructions WHERE recipeID = ? ORDER BY stepOrder");
    $ins_stmt->bind_param("i", $recipe_id);
    $ins_stmt->execute();
    $ins_result = $ins_stmt->get_result();

    while ($s = $ins_result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($s['step']) . "</li>";
    }
    ?>
    </ol>
  </td>

  <!-- Video -->
  <td>
    <?php if (!empty($row['videoFilePath'])) { ?>
      <a href="videos/<?php echo htmlspecialchars($row['videoFilePath']); ?>" target="_blank" class="video-link">
        Watch video
      </a>
    <?php } else { ?>
      <span class="no-video">No video</span>
    <?php } ?>
  </td>

  <!-- Likes -->
  <td>
    <span class="like-pill">❤️ <?php echo $likes; ?></span>
  </td>

  <!-- Edit -->
  <td>
    <a href="edit-recipe.php?id=<?php echo $recipe_id; ?>" class="action edit">Edit</a>
  </td>

  <!-- Delete -->
  <td>
    <a href="delete-recipe.php?id=<?php echo $recipe_id; ?>" 
       class="action delete"
       onclick="return confirm('Are you sure you want to delete this recipe?');">
       Delete
    </a>
  </td>
</tr>

<?php } ?>

<?php } else { ?>
<tr>
  <td colspan="7" style="text-align:center;">
    🍽️ You haven’t added any recipes yet. Start by adding one!
  </td>
</tr>
<?php } ?>

      </tbody>
    </table>
  </div>

</main>

<footer class="footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <span class="footer-name">BiteFul</span>
    </div>
    <div class="footer-copy">
      © 2026 BiteFul. All rights reserved. ADVANCED WEB TECH.
    </div>
  </div>
</footer>

</body>
</html>