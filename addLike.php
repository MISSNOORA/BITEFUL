<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID']) || $_SESSION['userType'] != "user") {
    echo "false";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipeID'])) {
    $userID   = $_SESSION['userID'];
    $recipeID = (int) $_POST['recipeID'];

    $creatorStmt = $conn->prepare("SELECT userID FROM recipe WHERE id = ?");
    $creatorStmt->bind_param("i", $recipeID);
    $creatorStmt->execute();
    $creatorResult = $creatorStmt->get_result();

    if ($creatorResult->num_rows > 0) {
        $creatorID = $creatorResult->fetch_assoc()['userID'];

        if ($creatorID != $userID) {
            $checkStmt = $conn->prepare("SELECT * FROM likes WHERE userID = ? AND recipeID = ?");
            $checkStmt->bind_param("ii", $userID, $recipeID);
            $checkStmt->execute();

            if ($checkStmt->get_result()->num_rows == 0) {
                $insertStmt = $conn->prepare("INSERT INTO likes (userID, recipeID) VALUES (?, ?)");
                $insertStmt->bind_param("ii", $userID, $recipeID);
                if ($insertStmt->execute()) {
                    echo "true";
                    exit();
                }
            }
        }
    }
}

echo "false";
exit();
?>