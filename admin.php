<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID']) || $_SESSION['userType'] != "admin") {
    header("Location: signin.html");
    exit();
}

$adminID = $_SESSION['userID'];

/* admin info */
$adminStmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
$adminStmt->bind_param("i", $adminID);
$adminStmt->execute();
$adminResult = $adminStmt->get_result();
$admin = $adminResult->fetch_assoc();

/* reports */
$reportsQuery = "
    SELECT 
        report.id AS reportID,
        recipe.id AS recipeID,
        recipe.name AS recipeName,
        user.id AS creatorID,
        user.firstName,
        user.lastName,
        user.photoFileName
    FROM report
    JOIN recipe ON report.recipeID = recipe.id
    JOIN user ON recipe.userID = user.id
    ORDER BY report.id DESC
";
$reportsResult = $conn->query($reportsQuery);

/* blocked users */
$blockedUsersResult = $conn->query("SELECT * FROM blockeduser ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Biteful | Admin Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container user-wrap">

  <div class="admin-welcome">
    <h1 class="user-title">
      Welcome, <span class="name"><?php echo htmlspecialchars($admin['firstName']); ?></span>
    </h1>
    <p class="user-sub">
      Manage reported recipes and blocked users.
    </p>
  </div>

  <div class="panel section">
    <p><strong>Name:</strong> <?php echo htmlspecialchars($admin['firstName'] . " " . $admin['lastName']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['emailAddress']); ?></p>
  </div>

  <div class="panel section">
    <div class="section-bar">
      <h2 class="section-title">Reported Recipes</h2>
    </div>

    <?php if ($reportsResult->num_rows > 0) { ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Recipe</th>
              <th>Creator</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>

            <?php while ($report = $reportsResult->fetch_assoc()) { ?>
              <tr>
                <td>
                  <a href="viewRecipe.php?id=<?php echo $report['recipeID']; ?>" class="link">
                    <?php echo htmlspecialchars($report['recipeName']); ?>
                  </a>
                </td>
                <td class="creator-cell">
                  <img src="images/<?php echo htmlspecialchars($report['photoFileName']); ?>" class="creator-avatar" alt="creator">
                  <?php echo htmlspecialchars($report['firstName'] . " " . $report['lastName']); ?>
                </td>
                <td>
                  <form action="handleReport.php" method="post" class="admin-action-form">
                    <input type="hidden" name="reportID" value="<?php echo $report['reportID']; ?>">
                    <input type="hidden" name="recipeID" value="<?php echo $report['recipeID']; ?>">
                    <input type="hidden" name="creatorID" value="<?php echo $report['creatorID']; ?>">

                    <select name="action" required>
                      <option value="">Select</option>
                      <option value="block">Block User</option>
                      <option value="dismiss">Dismiss</option>
                    </select>

                    <button type="submit" class="btn btn-ghost">Submit</button>
                  </form>
                </td>
              </tr>
            <?php } ?>

          </tbody>
        </table>
      </div>
    <?php } else { ?>
      <p>No pending reports.</p>
    <?php } ?>
  </div>

  <div class="panel section">
    <div class="section-bar">
      <h2 class="section-title">Blocked Users</h2>
    </div>

    <?php if ($blockedUsersResult->num_rows > 0) { ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($blocked = $blockedUsersResult->fetch_assoc()) { ?>
              <tr>
                <td><?php echo htmlspecialchars($blocked['firstName'] . " " . $blocked['lastName']); ?></td>
                <td><?php echo htmlspecialchars($blocked['emailAddress']); ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } else { ?>
      <p>No blocked users.</p>
    <?php } ?>
  </div>

  <div class="admin-signout">
    <a href="logout.php" class="btn btn-danger">
      Sign Out
    </a>
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