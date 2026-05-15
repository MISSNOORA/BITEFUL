<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID']) || $_SESSION['userType'] != "admin") {
    header("Location: signin.html");
    exit();
}

$adminID = $_SESSION['userID'];

$adminStmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
$adminStmt->bind_param("i", $adminID);
$adminStmt->execute();
$adminResult = $adminStmt->get_result();
$admin = $adminResult->fetch_assoc();

$reportsQuery = "
    SELECT 
        report.id AS reportID,
        recipe.id AS recipeID,
        recipe.name AS recipeName,
        user.id AS creatorID,
        user.firstName,
        user.lastName,
        user.emailAddress AS creatorEmail,
        user.photoFileName
    FROM report
    JOIN recipe ON report.recipeID = recipe.id
    JOIN user ON recipe.userID = user.id
    ORDER BY report.id DESC
";
$reportsResult = $conn->query($reportsQuery);

$blockedUsersResult = $conn->query("SELECT * FROM blockeduser ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Biteful | Admin Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<div class="container user-wrap">

  <div class="admin-welcome">
    <h1 class="user-title">
      Welcome, <span class="name"><?php echo htmlspecialchars($admin['firstName']); ?></span>
    </h1>
    <p class="user-sub">Manage reported recipes and blocked users.</p>
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
      <div class="table-wrap" id="reports-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Recipe</th>
              <th>Creator</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="reports-tbody">
            <?php while ($report = $reportsResult->fetch_assoc()) { ?>
              <tr data-report-id="<?php echo $report['reportID']; ?>">
                <td>
                  <a href="viewRecipe.php?id=<?php echo $report['recipeID']; ?>" class="link">
                    <?php echo htmlspecialchars($report['recipeName']); ?>
                  </a>
                </td>
                <td class="creator-cell">
                  <img src="images/<?php echo htmlspecialchars($report['photoFileName']); ?>" class="creator-avatar" alt="creator">
                  <span class="creator-name"><?php echo htmlspecialchars($report['firstName'] . " " . $report['lastName']); ?></span>
                </td>
                <td>
                  <div class="admin-action-form">
                    <input type="hidden" class="report-id"     value="<?php echo $report['reportID']; ?>">
                    <input type="hidden" class="recipe-id"     value="<?php echo $report['recipeID']; ?>">
                    <input type="hidden" class="creator-id"    value="<?php echo $report['creatorID']; ?>">
                    <input type="hidden" class="creator-email" value="<?php echo htmlspecialchars($report['creatorEmail']); ?>">

                    <select class="action-select" required>
                      <option value="">Select</option>
                      <option value="block">Block User</option>
                      <option value="dismiss">Dismiss</option>
                    </select>

                    <button type="button" class="btn btn-ghost submit-action-btn">Submit</button>
                  </div>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } else { ?>
      <p id="no-reports-msg">No pending reports.</p>
    <?php } ?>
  </div>

  <div class="panel section">
    <div class="section-bar">
      <h2 class="section-title">Blocked Users</h2>
    </div>

    <?php if ($blockedUsersResult->num_rows > 0) { ?>
      <div class="table-wrap" id="blocked-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody id="blocked-tbody">
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
      <p id="no-blocked-msg">No blocked users.</p>
    <?php } ?>
  </div>

  <div class="admin-signout">
    <a href="logout.php" class="btn btn-danger">Sign Out</a>
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

<script>
$(document).ready(function () {

  $(document).on("click", ".submit-action-btn", function () {

    var $btn         = $(this);
    var $form        = $btn.closest(".admin-action-form");
    var $row         = $btn.closest("tr");

    var reportID     = $form.find(".report-id").val();
    var recipeID     = $form.find(".recipe-id").val();
    var creatorID    = $form.find(".creator-id").val();
    var creatorName  = $row.find(".creator-name").text().trim();
    var creatorEmail = $form.find(".creator-email").val();
    var action       = $form.find(".action-select").val();

    if (!action) {
      alert("Please select an action first.");
      return;
    }

    $btn.prop("disabled", true).text("Processing...");

    $.ajax({
      url: "handleReport.php",
      type: "POST",
      data: {
        reportID:  reportID,
        recipeID:  recipeID,
        creatorID: creatorID,
        action:    action
      },
      success: function (response) {
        if (response.trim() === "true") {

          if (action === "block") {
            var newRow = "<tr><td>" + creatorName + "</td><td>" + creatorEmail + "</td></tr>";
            if ($("#blocked-tbody").length) {
              $("#blocked-tbody").prepend(newRow);
            } else {
              $("#no-blocked-msg").replaceWith(
                '<div class="table-wrap" id="blocked-wrap">' +
                '<table class="table"><thead><tr><th>Name</th><th>Email</th></tr></thead>' +
                '<tbody id="blocked-tbody">' + newRow + '</tbody></table></div>'
              );
            }
          }

          $row.fadeOut(400, function () {
            $(this).remove();
            if ($("#reports-tbody tr").length === 0) {
              $("#reports-wrap").replaceWith("<p id='no-reports-msg'>No pending reports.</p>");
            }
          });

        } else {
          alert("Action failed. Please try again.");
          $btn.prop("disabled", false).text("Submit");
        }
      },
      error: function (xhr, status, error) {
        alert("Request failed: " + xhr.status + " " + error);
        $btn.prop("disabled", false).text("Submit");
      }
    });

  });

});
</script>

</body>
</html>