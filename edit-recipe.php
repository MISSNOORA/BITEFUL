<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID'])) {
    header("Location: signin.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("No recipe ID");
}

$recipe_id = $_GET['id'];

/* ===== FETCH RECIPE ===== */
$stmt = $conn->prepare("SELECT * FROM recipe WHERE id=?");
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

if (!$recipe) {
    die("Recipe not found");
}

/* ===== FETCH INGREDIENTS ===== */
$ingredients = $conn->query("SELECT * FROM ingredients WHERE recipeID=$recipe_id");

/* ===== FETCH STEPS ===== */
$steps = $conn->query("SELECT * FROM instructions WHERE recipeID=$recipe_id ORDER BY stepOrder");

/* ===== UPDATE ===== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $recipe_id = $_POST['recipe_id']; // ✅ المطلوب
    $name = $_POST['name'];
    $categoryID = $_POST['category'];
    $description = $_POST['description'];

    /* IMAGE */
    if (!empty($_FILES['photo']['name'])) {
        $photoName = uniqid() . "_" . $_FILES['photo']['name'];
        move_uploaded_file($_FILES['photo']['tmp_name'], "images/" . $photoName);
    } else {
        $photoName = $recipe['photoFileName'];
    }

    /* VIDEO */
    if (!empty($_FILES['video']['name'])) {
        $videoName = uniqid() . "_" . $_FILES['video']['name'];
        move_uploaded_file($_FILES['video']['tmp_name'], "videos/" . $videoName);
        $videoPath = $videoName;
    } else {
        $videoPath = !empty($_POST['videoURL']) ? $_POST['videoURL'] : $recipe['videoFilePath'];
    }

    /* UPDATE RECIPE */
    $stmt = $conn->prepare("UPDATE recipe SET name=?, categoryID=?, description=?, photoFileName=?, videoFilePath=? WHERE id=?");
    $stmt->bind_param("sisssi", $name, $categoryID, $description, $photoName, $videoPath, $recipe_id);
    $stmt->execute();

    /* INGREDIENTS */
    $conn->query("DELETE FROM ingredients WHERE recipeID=$recipe_id");

    foreach ($_POST['ingredientName'] as $i => $ingName) {
        $qty = $_POST['ingredientQuantity'][$i];
        if ($ingName && $qty) {
            $stmt = $conn->prepare("INSERT INTO ingredients (recipeID, ingredientName, ingredientQuantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $recipe_id, $ingName, $qty);
            $stmt->execute();
        }
    }

    /* STEPS */
    $conn->query("DELETE FROM instructions WHERE recipeID=$recipe_id");

    foreach ($_POST['steps'] as $i => $step) {
        if ($step) {
            $order = $i + 1;
            $stmt = $conn->prepare("INSERT INTO instructions (recipeID, step, stepOrder) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $recipe_id, $step, $order);
            $stmt->execute();
        }
    }

    header("Location: my-recipes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Recipe</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="index.html" class="brand">
      <div class="logo">
        <img src="images/BiteFul-logo.png">
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

<main class="page-content">
  <div class="center">
    <div class="box sign-page add-recipe-box">

      <h2 class="title">Edit Recipe</h2>
      <p class="desc">Update the details of your recipe.</p>

      <form class="form" method="POST" enctype="multipart/form-data">

        <!-- ✅ REQUIRED -->
        <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">

        <div class="group">
          <label>Recipe Name</label>
          <input type="text" name="name" value="<?= $recipe['name'] ?>" required>
        </div>

        <div class="group">
          <label>Category</label>
          <select name="category" required>
            <?php
            $cats = $conn->query("SELECT * FROM recipecategory");
            while ($c = $cats->fetch_assoc()) {
                $selected = ($c['id'] == $recipe['categoryID']) ? "selected" : "";
                echo "<option value='{$c['id']}' $selected>{$c['categoryName']}</option>";
            }
            ?>
          </select>
        </div>

        <div class="group">
          <label>Description</label>
          <textarea name="description" rows="4" class="recipe-textarea" required><?= $recipe['description'] ?></textarea>
        </div>

        <div class="group">
          <label>Upload Recipe Photo</label>
          <img src="images/<?= $recipe['photoFileName'] ?>" width="80"><br><br>
          <input type="file" name="photo" accept="image/*">
        </div>

        <div class="group">
          <label>Ingredients</label>

          <div id="ingredients">
            <?php while($ing = $ingredients->fetch_assoc()) { ?>
              <div class="two">
                <input type="text" name="ingredientName[]" value="<?= $ing['ingredientName'] ?>" required>
                <input type="text" name="ingredientQuantity[]" value="<?= $ing['ingredientQuantity'] ?>" required>
              </div>
            <?php } ?>
          </div>

          <button type="button" class="btn btn-ghost" onclick="addIngredient()">
            + Add another ingredient
          </button>
        </div>

        <div class="group">
          <label>Instructions</label>

          <div id="steps">
            <?php while($s = $steps->fetch_assoc()) { ?>
              <input type="text" name="steps[]" value="<?= $s['step'] ?>" required>
            <?php } ?>
          </div>

          <button type="button" class="btn btn-ghost" onclick="addStep()">
            + Add another step
          </button>
        </div>

        <div class="group">
          <label>Upload Video (Optional)</label>
          <input type="file" name="video" accept="video/*">
        </div>

        <div class="group">
          <label>Video URL (Optional)</label>
          <input type="url" name="videoURL" value="<?= $recipe['videoFilePath'] ?>">
        </div>

        <button type="submit" class="btn green">Update Recipe</button>

      </form>

    </div>
  </div>
</main>

<footer class="footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <span class="footer-name">BiteFul</span>
    </div>
    <div class="footer-copy">
      © 2026 BiteFul. All rights reserved.
    </div>
  </div>
</footer>

<script>
function addIngredient() {
  const container = document.getElementById("ingredients");
  const div = document.createElement("div");
  div.className = "two";
  div.innerHTML = `
    <input type="text" name="ingredientName[]" placeholder="Ingredient name" required>
    <input type="text" name="ingredientQuantity[]" placeholder="Quantity" required>
  `;
  container.appendChild(div);
}

function addStep() {
  const steps = document.getElementById("steps");
  const stepNum = steps.children.length + 1;
  const input = document.createElement("input");
  input.type = "text";
  input.name = "steps[]";
  input.placeholder = "Step " + stepNum;
  input.required = true;
  steps.appendChild(input);
}
</script>

</body>
</html>