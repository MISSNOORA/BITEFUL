
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

$photoFileName = "default.jpg"; 

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadsDir = "images/"; 
    $tmpName    = $_FILES['photo']['tmp_name'];
    $originalName = basename($_FILES['photo']['name']);
    $uniqueName = uniqid() . "_" . $originalName;

    if (move_uploaded_file($tmpName, $uploadsDir . $uniqueName)) {
        $photoFileName = $uniqueName;
    }
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$userType = "user";

$insert = $conn->prepare("
    INSERT INTO user (userType, firstName, lastName, emailAddress, password, photoFileName)
    VALUES (?, ?, ?, ?, ?, ?)
");
$insert->bind_param("ssssss", $userType, $firstName, $lastName, $email, $hashedPassword, $photoFileName);

if ($insert->execute()) {
    $_SESSION['userID']   = $conn->insert_id; // gets the new user's auto-generated id
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

