<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID']) || $_SESSION['userType'] != "user") {
    header("Location: signin.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipeID']) && isset($_POST['comment'])) {
    $userID = $_SESSION['userID'];
    $recipeID = (int) $_POST['recipeID'];
    $comment = trim($_POST['comment']);

    if ($comment !== "") {
        $stmt = $conn->prepare("INSERT INTO comment (recipeID, userID, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $recipeID, $userID, $comment);
        $stmt->execute();
    }

    header("Location: viewRecipe.php?id=" . $recipeID);
    exit();
}

header("Location: user.php");
exit();
?>