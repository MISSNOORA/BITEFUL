<?php
ob_start();
session_start();
require_once "db.php";
header('Content-Type: text/plain');

if (!isset($_SESSION['userID']) || $_SESSION['userType'] != "admin") {
    ob_clean(); echo "false"; exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean(); echo "false"; exit();
}

$reportID  = isset($_POST['reportID'])  ? (int)$_POST['reportID']  : 0;
$recipeID  = isset($_POST['recipeID'])  ? (int)$_POST['recipeID']  : 0;
$creatorID = isset($_POST['creatorID']) ? (int)$_POST['creatorID'] : 0;
$action    = $_POST['action'] ?? '';

/* ===== DISMISS ===== */
if ($action === 'dismiss') {
    $stmt = $conn->prepare("DELETE FROM report WHERE id = ?");
    $stmt->bind_param("i", $reportID);
    $ok = $stmt->execute();
    ob_clean(); echo $ok ? "true" : "false"; exit();
}

/* ===== BLOCK ===== */
if ($action === 'block') {

    // Get user info
    $userStmt = $conn->prepare("SELECT firstName, lastName, emailAddress, photoFileName FROM user WHERE id = ?");
    $userStmt->bind_param("i", $creatorID);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows === 0) {
        ob_clean(); echo "false"; exit();
    }

    $user = $userResult->fetch_assoc();

    // FIX: Delete the user's profile photo from disk
    $userPhoto = $user['photoFileName'];
    if ($userPhoto && $userPhoto !== "default.jpg" && file_exists("images/" . $userPhoto)) {
        unlink("images/" . $userPhoto);
    }

    // FIX: Get all recipes by this user and delete their photo/video files
    $recipesStmt = $conn->prepare("SELECT photoFileName, videoFilePath FROM recipe WHERE userID = ?");
    $recipesStmt->bind_param("i", $creatorID);
    $recipesStmt->execute();
    $recipesResult = $recipesStmt->get_result();

    while ($recipe = $recipesResult->fetch_assoc()) {

        // Delete recipe photo
        $recipePhoto = $recipe['photoFileName'];
        if ($recipePhoto && file_exists("images/" . $recipePhoto)) {
            unlink("images/" . $recipePhoto);
        }

        // Delete recipe video (only if it's a local file, not a URL)
        $recipeVideo = $recipe['videoFilePath'];
        if ($recipeVideo && !filter_var($recipeVideo, FILTER_VALIDATE_URL) && file_exists("videos/" . $recipeVideo)) {
            unlink("videos/" . $recipeVideo);
        }
    }

    // Add user to blocked table
    $ins = $conn->prepare("INSERT IGNORE INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)");
    $ins->bind_param("sss", $user['firstName'], $user['lastName'], $user['emailAddress']);
    $ins->execute();

    // Delete user from user table (cascades to recipes, likes, etc. if FK set)
    $del = $conn->prepare("DELETE FROM user WHERE id = ?");
    $del->bind_param("i", $creatorID);
    $ok = $del->execute();

    ob_clean(); echo $ok ? "true" : "false"; exit();
}

ob_clean(); echo "false"; exit();
?>