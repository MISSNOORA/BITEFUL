<?php
session_start();
require_once "db.php";

$firstName = trim($_POST['firstName']);
$lastName  = trim($_POST['lastName']);
$email     = trim($_POST['email']);
$password  = $_POST['password'];

$checkUser = $conn->prepare("SELECT id FROM user WHERE emailAddress = ?");
$checkUser->bind_param("s", $email);
$checkUser->execute();
$checkUser->store_result();
if ($checkUser->num_rows > 0) {
    header("Location: signup.php?error=email_taken");
    exit();
}
$checkUser->close();

$checkBlocked = $conn->prepare("SELECT id FROM blockeduser WHERE emailAddress = ?");
$checkBlocked->bind_param("s", $email);
$checkBlocked->execute();
$checkBlocked->store_result();
if ($checkBlocked->num_rows > 0) {
    header("Location: signup.php?error=email_taken");
    exit();
}
$checkBlocked->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$userType = "user";

// Insert user first WITHOUT photo so we get the userID
$insert = $conn->prepare("
    INSERT INTO user (userType, firstName, lastName, emailAddress, password, photoFileName)
    VALUES (?, ?, ?, ?, ?, ?)
");
$tempPhoto = "default.jpg";
$insert->bind_param("ssssss", $userType, $firstName, $lastName, $email, $hashedPassword, $tempPhoto);

if ($insert->execute()) {
    $newUserID = $conn->insert_id;

    // Now handle photo upload with userID in the file name
    $photoFileName = "default.jpg";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadsDir   = "images/";
        $originalName = basename($_FILES['photo']['name']);
        // FIX: include userID in the file name
        $uniqueName   = "user_" . $newUserID . "_" . uniqid() . "_" . $originalName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadsDir . $uniqueName)) {
            $photoFileName = $uniqueName;
            // Update the row with the correct file name
            $update = $conn->prepare("UPDATE user SET photoFileName = ? WHERE id = ?");
            $update->bind_param("si", $photoFileName, $newUserID);
            $update->execute();
        }
    }

    $_SESSION['userID']   = $newUserID;
    $_SESSION['userType'] = $userType;
    header("Location: user.php");
    exit();

} else {
    header("Location: signup.php?error=server_error");
    exit();
}

$insert->close();
$conn->close();
?>