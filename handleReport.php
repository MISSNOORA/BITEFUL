<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID']) || $_SESSION['userType'] != "admin") {
    header("Location: signin.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php");
    exit();
}

$reportID = isset($_POST['reportID']) ? (int) $_POST['reportID'] : 0;
$recipeID = isset($_POST['recipeID']) ? (int) $_POST['recipeID'] : 0;
$creatorID = isset($_POST['creatorID']) ? (int) $_POST['creatorID'] : 0;
$action = $_POST['action'] ?? '';

if ($action === 'dismiss') {
    $stmt = $conn->prepare("DELETE FROM report WHERE id = ?");
    $stmt->bind_param("i", $reportID);
    $stmt->execute();

    header("Location: admin.php");
    exit();
}

if ($action === 'block') {
    /* get user info first */
    $userStmt = $conn->prepare("SELECT firstName, lastName, emailAddress FROM user WHERE id = ?");
    $userStmt->bind_param("i", $creatorID);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();

        /* add to blockeduser */
        $insertBlocked = $conn->prepare("
            INSERT INTO blockeduser (firstName, lastName, emailAddress)
            VALUES (?, ?, ?)
        ");
        $insertBlocked->bind_param("sss", $user['firstName'], $user['lastName'], $user['emailAddress']);
        $insertBlocked->execute();

        /* delete user from user table
           recipe/comments/likes/favourites/report related rows will cascade
           because your SQL dump already has foreign keys with ON DELETE CASCADE */
        $deleteUser = $conn->prepare("DELETE FROM user WHERE id = ?");
        $deleteUser->bind_param("i", $creatorID);
        $deleteUser->execute();
    }

    header("Location: admin.php");
    exit();
}

header("Location: admin.php");
exit();
?>