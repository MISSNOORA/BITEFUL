<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userID']) || $_SESSION['userType'] != "user") {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

$category = isset($_GET['category']) ? $_GET['category'] : 'All';

if ($category === 'All') {
    $stmt = $conn->prepare("
        SELECT r.id, r.name, r.photoFileName, u.firstName, u.lastName, c.categoryName,
        (SELECT COUNT(*) FROM likes WHERE recipeID = r.id) AS likesCount
        FROM recipe r
        JOIN user u ON r.userID = u.id
        JOIN recipecategory c ON r.categoryID = c.id
        ORDER BY r.id DESC
    ");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        SELECT r.id, r.name, r.photoFileName, u.firstName, u.lastName, c.categoryName,
        (SELECT COUNT(*) FROM likes WHERE recipeID = r.id) AS likesCount
        FROM recipe r
        JOIN user u ON r.userID = u.id
        JOIN recipecategory c ON r.categoryID = c.id
        WHERE r.categoryID = ?
        ORDER BY r.id DESC
    ");
    $stmt->bind_param("i", $category);
    $stmt->execute();
}

$result = $stmt->get_result();
$recipes = [];
while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
}

header('Content-Type: application/json');
echo json_encode($recipes);
