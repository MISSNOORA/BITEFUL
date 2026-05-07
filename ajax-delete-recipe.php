<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID'])) {
    echo "false";
    exit();
}

$userID = $_SESSION['userID'];
$recipeID = isset($_POST['recipeID']) ? intval($_POST['recipeID']) : 0;

if ($recipeID <= 0) {
    echo "false";
    exit();
}

// ✅ Verify ownership
$check = $conn->prepare("SELECT id FROM recipe WHERE id = ? AND userID = ?");
$check->bind_param("ii", $recipeID, $userID);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo "false";
    exit();
}

// ✅ Get file paths before deleting
$file_stmt = $conn->prepare("SELECT photoFileName, videoFilePath FROM recipe WHERE id = ?");
$file_stmt->bind_param("i", $recipeID);
$file_stmt->execute();
$file_result = $file_stmt->get_result();
$files = $file_result->fetch_assoc();

// ✅ Safely delete from related tables (skips if table doesn't exist)
$related_tables = ["Ingredients", "Instructions", "Likes", "Favourites", "Reports"];
foreach ($related_tables as $table) {
    // Check if table exists first
    $tableCheck = $conn->query("SHOW TABLES LIKE '$table'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $del = $conn->prepare("DELETE FROM $table WHERE recipeID = ?");
        if ($del) {
            $del->bind_param("i", $recipeID);
            $del->execute();
        }
    }
}

// ✅ Delete the recipe itself
$del_recipe = $conn->prepare("DELETE FROM recipe WHERE id = ? AND userID = ?");
$del_recipe->bind_param("ii", $recipeID, $userID);
$del_recipe->execute();

if ($del_recipe->affected_rows > 0) {
    // ✅ Delete physical files
    if (!empty($files['photoFileName'])) {
        $photo_path = "images/" . $files['photoFileName'];
        if (file_exists($photo_path)) unlink($photo_path);
    }

    if (!empty($files['videoFilePath']) && !filter_var($files['videoFilePath'], FILTER_VALIDATE_URL)) {
        $video_path = "videos/" . $files['videoFilePath'];
        if (file_exists($video_path)) unlink($video_path);
    }

    echo "true";
} else {
    echo "false";
}
?>