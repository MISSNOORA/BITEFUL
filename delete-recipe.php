<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID'])) {
    header("Location: signin.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: my-recipes.php");
    exit();
}

$recipeID = intval($_GET['id']);

// Get files
$stmt = $conn->prepare("SELECT photoFileName, videoFilePath FROM recipe WHERE id = ?");
$stmt->bind_param("i", $recipeID);
$stmt->execute();
$result = $stmt->get_result();
$recipe = $result->fetch_assoc();

$photo = $recipe['photoFileName'];
$video = $recipe['videoFilePath'];

// Delete files
if (!empty($photo) && file_exists("images/" . $photo)) {
    unlink("images/" . $photo);
}

if (!empty($video) && file_exists("videos/" . $video)) {
    unlink("videos/" . $video);
}

// Delete related data
$stmt = $conn->prepare("DELETE FROM Ingredients WHERE recipeID = ?");
$stmt->bind_param("i", $recipeID);
$stmt->execute();

$stmt = $conn->prepare("DELETE FROM Instructions WHERE recipeID = ?");
$stmt->bind_param("i", $recipeID);
$stmt->execute();

$stmt = $conn->prepare("DELETE FROM Likes WHERE recipeID = ?");
$stmt->bind_param("i", $recipeID);
$stmt->execute();

// Delete recipe
$stmt = $conn->prepare("DELETE FROM recipe WHERE id = ?");
$stmt->bind_param("i", $recipeID);
$stmt->execute();

header("Location: my-recipes.php");
exit();
?>