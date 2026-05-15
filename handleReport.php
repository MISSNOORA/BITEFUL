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

if ($action === 'dismiss') {
    $stmt = $conn->prepare("DELETE FROM report WHERE id = ?");
    $stmt->bind_param("i", $reportID);
    $ok = $stmt->execute();
    ob_clean(); echo $ok ? "true" : "false"; exit();
}

if ($action === 'block') {
    $userStmt = $conn->prepare("SELECT firstName, lastName, emailAddress FROM user WHERE id = ?");
    $userStmt->bind_param("i", $creatorID);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows === 0) {
        ob_clean(); echo "false"; exit();
    }

    $user = $userResult->fetch_assoc();

    $ins = $conn->prepare("INSERT IGNORE INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)");
    $ins->bind_param("sss", $user['firstName'], $user['lastName'], $user['emailAddress']);
    $ins->execute();

    $del = $conn->prepare("DELETE FROM user WHERE id = ?");
    $del->bind_param("i", $creatorID);
    $ok = $del->execute();

    ob_clean(); echo $ok ? "true" : "false"; exit();
}

ob_clean(); echo "false"; exit();
?>