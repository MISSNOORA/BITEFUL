<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM Recipe WHERE userID = '$user_id'";
$result = mysqli_query($conn, $query);
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
      <a href="user.html" class="nav-link">Dashboard</a>
      <a href="my-recipes.php" class="nav-link">My Recipes</a>
      <a href="index.html" class="btn btn-ghost">Sign Out</a>
    </nav>
  </div>
</header>

<!-- Page Content -->
<main class="container my-recipes-page">

  <!-- Title + Add Button -->
  <div class="page-head">
    <div>
      <h1 class="page-title">My Recipes</h1>
      <p class="page-sub">Manage all recipes you’ve added</p>
    </div>

    <a href="add-recipe.html" class="btn btn-primary">
      ➕ Add New Recipe
    </a>
  </div>

  <!-- Table -->
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
          <?php if (mysqli_num_rows($result) > 0) { ?>
          <?php while ($row = mysqli_fetch_assoc($result)) { 
    $recipe_id = $row['id'];

    $like_query = "SELECT COUNT(*) AS total FROM Likes WHERE recipeID = '$recipe_id'";
    $like_result = mysqli_query($conn, $like_query);
    $like_data = mysqli_fetch_assoc($like_result);
    $likes = $like_data['total'];
?> 

          <tr>
  <td>
    <a href="view-recipe.php?id=<?php echo $recipe_id; ?>" class="recipe-cell">
      <img src="images/<?php echo $row['photoFileName']; ?>" class="recipe-thumb">
      <span class="recipe-name"><?php echo $row['name']; ?></span>
    </a>
  </td>

  <!-- Ingredients -->
  <td>
    <?php
    $ing = mysqli_query($conn, "SELECT * FROM Ingredients WHERE recipeID='$recipe_id'");
    echo "<ul class='list'>";
    while ($i = mysqli_fetch_assoc($ing)) {
        echo "<li>" . $i['ingredientName'] . " - " . $i['ingredientQuantity'] . "</li>";
    }
    echo "</ul>";
    ?>
  </td>

  <!-- Instructions -->
  <td>
    <?php
    $ins = mysqli_query($conn, "SELECT * FROM Instructions WHERE recipeID='$recipe_id' ORDER BY stepOrder");
    echo "<ol class='list'>";
    while ($s = mysqli_fetch_assoc($ins)) {
        echo "<li>" . $s['step'] . "</li>";
    }
    echo "</ol>";
    ?>
  </td>

  <!-- Video -->
  <td>
    <?php if (!empty($row['videoFilePath'])) { ?>
      <a href="videos/<?php echo $row['videoFilePath']; ?>" target="_blank" class="video-link">Watch video</a>
    <?php } else { ?>
      <span class="no-video">no video</span>
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
    <a href="delete_recipe.php?id=<?php echo $recipe_id; ?>" class="action delete">Delete</a>
  </td>
</tr>

<?php } ?>

<?php } else { ?>
<tr>
  <td colspan="7">You have no recipes</td>
</tr>
<?php } ?>
      

      </tbody>
    </table>
  </div>

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

