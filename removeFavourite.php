<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID'])) {
    header("Location: signin.html");
    exit();
}

if (isset($_GET['recipeID'])) {
    $userID = $_SESSION['userID'];
    $recipeID = $_GET['recipeID'];

    $stmt = $conn->prepare("DELETE FROM favourites WHERE userID = ? AND recipeID = ?");
    $stmt->bind_param("ii", $userID, $recipeID);
    $stmt->execute();
}

header("Location: user.php");
exit();
?>
