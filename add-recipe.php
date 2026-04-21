<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID'])) {
    header("Location: signin.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $userID = $_SESSION['userID'];

    $name = trim($_POST['name'] ?? '');
    $categoryID = intval($_POST['category'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    // ===== IMAGE =====
    $photoName = "";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $photoName = uniqid() . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], "images/" . $photoName);
    }

    // ===== VIDEO =====
    $videoPath = "";
    if (!empty($_FILES['video']['name'])) {
        $videoPath = uniqid() . "_" . basename($_FILES['video']['name']);
        move_uploaded_file($_FILES['video']['tmp_name'], "videos/" . $videoPath);
    } elseif (!empty($_POST['videoURL'])) {
        $videoPath = trim($_POST['videoURL']);
    }

    // ===== INSERT RECIPE =====
    $stmt = $conn->prepare("INSERT INTO recipe (userID, name, categoryID, description, photoFileName, videoFilePath)
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisss", $userID, $name, $categoryID, $description, $photoName, $videoPath);
    $stmt->execute();

    $recipeID = $conn->insert_id;

    // ===== INGREDIENTS =====
    if (!empty($_POST['ingredientName'])) {
        foreach ($_POST['ingredientName'] as $i => $ingName) {

            $ingName = trim($ingName);
            $qty = trim($_POST['ingredientQuantity'][$i]);

            if ($ingName !== "" && $qty !== "") {
                $stmt = $conn->prepare("INSERT INTO ingredients (recipeID, ingredientName, ingredientQuantity)
                                       VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $recipeID, $ingName, $qty);
                $stmt->execute();
            }
        }
    }

    // ===== STEPS =====
    if (!empty($_POST['steps'])) {
        foreach ($_POST['steps'] as $index => $step) {

            $step = trim($step);
            if ($step !== "") {
                $order = $index + 1;

                $stmt = $conn->prepare("INSERT INTO instructions (recipeID, step, stepOrder)
                                       VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $recipeID, $step, $order);
                $stmt->execute();
            }
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
  <title>Add New Recipe</title>
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

<main class="page-content">
  <div class="center">
    <div class="box sign-page add-recipe-box">

      <h2 class="title">Add New Recipe</h2>
      <p class="desc">Fill in the details below to add a new healthy recipe.</p>

      <form class="form" method="POST" enctype="multipart/form-data">

        <div class="group">
          <label>Recipe Name</label>
          <input type="text" name="name" required>
        </div>

        <!-- ✅ CATEGORY FROM DB -->
        <div class="group">
          <label>Category</label>
          <select name="category" required>
            <option value="">Select</option>
            <?php
         
            $result = $conn->query("SELECT * FROM recipecategory");
while ($row = $result->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['categoryName']}</option>";
}
            ?>
          </select>
        </div>

        <div class="group">
          <label>Description</label>
          <textarea name="description" rows="4" class="recipe-textarea" required></textarea>
        </div>

        <div class="group">
          <label>Upload Recipe Photo</label>
          <input type="file" name="photo" accept="image/*" required>
        </div>

        <!-- INGREDIENTS -->
        <div class="group">
          <label>Ingredients</label>
          <div id="ingredients">
            <div class="two">
              <input type="text" name="ingredientName[]" placeholder="Ingredient name" required>
              <input type="text" name="ingredientQuantity[]" placeholder="Quantity" required>
            </div>
          </div>
          <button type="button" class="btn btn-ghost" onclick="addIngredient()">
            + Add another ingredient
          </button>
        </div>

        <!-- STEPS -->
        <div class="group">
          <label>Instructions</label>
          <div id="steps">
            <input type="text" name="steps[]" placeholder="Step 1" required>
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
          <input type="url" name="videoURL" placeholder="https://youtube.com/...">
        </div>

        <button type="submit" class="btn green">Add Recipe</button>

      </form>
    </div>
  </div>
</main>

<footer class="footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <span class="logo logo-footer"></span>
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