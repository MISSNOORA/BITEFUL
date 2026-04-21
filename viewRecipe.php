<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID'])) {
    header("Location: signin.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user.php");
    exit();
}

$userID = $_SESSION['userID'];
$userType = $_SESSION['userType'];
$recipeID = (int) $_GET['id'];

/* recipe + creator + category */
$sql = "
    SELECT r.*, 
           u.firstName, u.lastName, u.photoFileName AS creatorPhoto, u.id AS creatorID,
           c.categoryName
    FROM recipe r
    JOIN user u ON r.userID = u.id
    JOIN recipecategory c ON r.categoryID = c.id
    WHERE r.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recipeID);
$stmt->execute();
$recipeResult = $stmt->get_result();

if ($recipeResult->num_rows === 0) {
    header("Location: user.php");
    exit();
}

$recipe = $recipeResult->fetch_assoc();

/* ingredients */
$ingredientsStmt = $conn->prepare("
    SELECT ingredientName, ingredientQuantity
    FROM ingredients
    WHERE recipeID = ?
    ORDER BY id ASC
");
$ingredientsStmt->bind_param("i", $recipeID);
$ingredientsStmt->execute();
$ingredientsResult = $ingredientsStmt->get_result();

/* instructions */
$instructionsStmt = $conn->prepare("
    SELECT step, stepOrder
    FROM instructions
    WHERE recipeID = ?
    ORDER BY stepOrder ASC
");
$instructionsStmt->bind_param("i", $recipeID);
$instructionsStmt->execute();
$instructionsResult = $instructionsStmt->get_result();

/* comments */
$commentsStmt = $conn->prepare("
    SELECT c.comment, c.date, u.firstName, u.lastName
    FROM comment c
    JOIN user u ON c.userID = u.id
    WHERE c.recipeID = ?
    ORDER BY c.date DESC
");
$commentsStmt->bind_param("i", $recipeID);
$commentsStmt->execute();
$commentsResult = $commentsStmt->get_result();

/* likes count */
$likesStmt = $conn->prepare("SELECT COUNT(*) AS totalLikes FROM likes WHERE recipeID = ?");
$likesStmt->bind_param("i", $recipeID);
$likesStmt->execute();
$likesResult = $likesStmt->get_result();
$totalLikes = $likesResult->fetch_assoc()['totalLikes'];

/* button rules */
$isCreator = ($recipe['creatorID'] == $userID);
$isAdmin = ($userType === 'admin');

$alreadyLiked = false;
$alreadyFavourited = false;
$alreadyReported = false;

if (!$isCreator && !$isAdmin) {
    $checkLike = $conn->prepare("SELECT * FROM likes WHERE userID = ? AND recipeID = ?");
    $checkLike->bind_param("ii", $userID, $recipeID);
    $checkLike->execute();
    $alreadyLiked = $checkLike->get_result()->num_rows > 0;

    $checkFav = $conn->prepare("SELECT * FROM favourites WHERE userID = ? AND recipeID = ?");
    $checkFav->bind_param("ii", $userID, $recipeID);
    $checkFav->execute();
    $alreadyFavourited = $checkFav->get_result()->num_rows > 0;

    $checkReport = $conn->prepare("SELECT * FROM report WHERE userID = ? AND recipeID = ?");
    $checkReport->bind_param("ii", $userID, $recipeID);
    $checkReport->execute();
    $alreadyReported = $checkReport->get_result()->num_rows > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Biteful | <?php echo htmlspecialchars($recipe['name']); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
session_start();
?>

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

      <?php if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin'): ?>

        <!-- ADMIN NAV -->
        <a href="admin.php" class="nav-link">Dashboard</a>

      <?php else: ?>

        <!-- NORMAL USER NAV -->
        <a href="user.php" class="nav-link">Dashboard</a>
        <a href="my-recipes.php" class="nav-link">My Recipes</a>

      <?php endif; ?>

      <a href="logout.php" class="btn btn-ghost">Sign Out</a>

    </nav>
  </div>
</header>

<div class="container user-wrap">

  <div class="panel section">
    <div class="recipe-header">

      <img src="images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>"
           alt="<?php echo htmlspecialchars($recipe['name']); ?>"
           class="recipe-image">

      <div class="recipe-info">
        <h1 class="section-title recipe-title">
          <?php echo htmlspecialchars($recipe['name']); ?>
        </h1>

        <p class="section-sub recipe-description">
          <?php echo htmlspecialchars($recipe['description']); ?>
        </p>

        <p class="section-sub">Total Likes: <?php echo $totalLikes; ?></p>

        <?php if (!$isCreator && !$isAdmin): ?>
          <div class="recipe-actions">

            <form action="addLike.php" method="post">
              <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
              <button type="submit" class="btn btn-ghost" <?php if ($alreadyLiked) echo "disabled"; ?>>
                <?php echo $alreadyLiked ? "❤️ Liked" : "❤️ Like"; ?>
              </button>
            </form>

            <form action="addFavourite.php" method="post">
              <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
              <button type="submit" class="btn btn-ghost" <?php if ($alreadyFavourited) echo "disabled"; ?>>
                <?php echo $alreadyFavourited ? "⭐ Added" : "⭐ Add to Favourites"; ?>
              </button>
            </form>

            <form action="addReport.php" method="post">
              <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
              <button type="submit" class="btn btn-ghost" <?php if ($alreadyReported) echo "disabled"; ?>>
                <?php echo $alreadyReported ? "🚩 Reported" : "🚩 Report"; ?>
              </button>
            </form>

          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <div class="panel section">
    <div class="creator-cell">
      <img src="images/<?php echo htmlspecialchars($recipe['creatorPhoto']); ?>" class="creator-avatar" alt="creator">
      <strong><?php echo htmlspecialchars($recipe['firstName'] . " " . $recipe['lastName']); ?></strong>
    </div>
    <div class="recipe-category">
      <span class="cat"><?php echo htmlspecialchars($recipe['categoryName']); ?></span>
    </div>
  </div>

  <div class="panel section">
    <h3 class="section-title">Ingredients</h3>
    <ul>
      <?php while ($ingredient = $ingredientsResult->fetch_assoc()) { ?>
        <li>
          <?php echo htmlspecialchars($ingredient['ingredientQuantity'] . " " . $ingredient['ingredientName']); ?>
        </li>
      <?php } ?>
    </ul>
  </div>

  <div class="panel section">
    <h3 class="section-title">Instructions</h3>
    <ol>
      <?php while ($instruction = $instructionsResult->fetch_assoc()) { ?>
        <li><?php echo htmlspecialchars($instruction['step']); ?></li>
      <?php } ?>
    </ol>
  </div>

  <div class="panel section">
    <h3 class="section-title">Recipe Video</h3>
    <?php if (!empty($recipe['videoFilePath'])) { ?>
      <div class="recipe-video">
        <a href="<?php echo htmlspecialchars($recipe['videoFilePath']); ?>" target="_blank" class="video-link">
          Watch recipe video
        </a>
      </div>
    <?php } else { ?>
      <p class="no-video">No video available for this recipe.</p>
    <?php } ?>
  </div>

  <div class="panel section">
    <h3 class="section-title">Comments</h3>

    <?php if ($commentsResult->num_rows > 0) { ?>
      <?php while ($comment = $commentsResult->fetch_assoc()) { ?>
        <div class="comment">
          <p class="comment-text">
            <strong><?php echo htmlspecialchars($comment['firstName'] . " " . $comment['lastName']); ?>:</strong>
            <?php echo htmlspecialchars($comment['comment']); ?>
          </p>
          <span class="comment-date">
            <?php echo date("M d, Y · g:i A", strtotime($comment['date'])); ?>
          </span>
        </div>
      <?php } ?>
    <?php } else { ?>
      <p>No comments yet.</p>
    <?php } ?>

    <form action="addComment.php" method="post" class="comment-form">
      <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
      <input type="text" name="comment" placeholder="Write a comment..." class="comment-input" required>
      <button type="submit" class="btn btn-primary comment-btn">
        Add Comment
      </button>
    </form>
  </div>

</div>

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