
<?php
session_start();
require_once "db.php";

// 1. Get form data
$firstName = trim($_POST['firstName']);
$lastName  = trim($_POST['lastName']);
$email     = trim($_POST['email']);
$password  = $_POST['password'];

// 2. Check if email is already in the 'user' table (active users)
$checkUser = $conn->prepare("SELECT id FROM user WHERE emailAddress = ?");
$checkUser->bind_param("s", $email);
$checkUser->execute();
$checkUser->store_result();

if ($checkUser->num_rows > 0) {
    // Email already registered as a regular/admin user
    header("Location: signup.php?error=email_taken");
    exit();
}
$checkUser->close();

// 3. Check if email is in the 'blockeduser' table
$checkBlocked = $conn->prepare("SELECT id FROM blockeduser WHERE emailAddress = ?");
$checkBlocked->bind_param("s", $email);
$checkBlocked->execute();
$checkBlocked->store_result();

if ($checkBlocked->num_rows > 0) {
    // Email belongs to a blocked user
    header("Location: signup.php?error=email_taken");
    exit();
}
$checkBlocked->close();

// 4. Handle photo upload
$photoFileName = "default.jpg"; // default photo if none uploaded

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadsDir = "images/"; // folder where photos are saved
    $tmpName    = $_FILES['photo']['tmp_name'];
    $originalName = basename($_FILES['photo']['name']);
    // Make filename unique to avoid overwriting
    $uniqueName = uniqid() . "_" . $originalName;

    if (move_uploaded_file($tmpName, $uploadsDir . $uniqueName)) {
        $photoFileName = $uniqueName;
    }
}

// 5. Hash the password — NEVER store plain text
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 6. New users are always type 'user'
$userType = "user";

// 7. Insert the new user into the database
$insert = $conn->prepare("
    INSERT INTO user (userType, firstName, lastName, emailAddress, password, photoFileName)
    VALUES (?, ?, ?, ?, ?, ?)
");
$insert->bind_param("ssssss", $userType, $firstName, $lastName, $email, $hashedPassword, $photoFileName);

if ($insert->execute()) {
    // 8. Set session variables
    $_SESSION['userID']   = $conn->insert_id; // gets the new user's auto-generated id
    $_SESSION['userType'] = $userType;

    // 9. Redirect to user dashboard
    header("Location: user.php");
    exit();
} else {
    // Something went wrong with the insert
    header("Location: signup.php?error=server_error");
    exit();
}

$insert->close();
$conn->close();
?>

