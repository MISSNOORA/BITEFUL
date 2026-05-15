<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(false);
    exit();
}

if (isset($_GET['recipeID'])) {
    $userID   = $_SESSION['userID'];
    $recipeID = intval($_GET['recipeID']);

    $stmt = $conn->prepare("DELETE FROM favourites WHERE userID = ? AND recipeID = ?");
    $stmt->bind_param("ii", $userID, $recipeID);
    $stmt->execute();

    echo json_encode($stmt->affected_rows > 0);
} else {
    echo json_encode(false);
}
?>
