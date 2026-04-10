


 <?php
session_start();
require_once "db.php";

// 1. Get form data
$email    = trim($_POST['email']);
$password = $_POST['password'];

// 2. Check if the email is in the blockeduser table
$checkBlocked = $conn->prepare("SELECT id FROM blockeduser WHERE emailAddress = ?");
$checkBlocked->bind_param("s", $email);
$checkBlocked->execute();
$checkBlocked->store_result();

if ($checkBlocked->num_rows > 0) {
    // User is blocked
    header("Location: signin.php?error=blocked");
    exit();
}
$checkBlocked->close();

// 3. Look up the user by email in the user table
$stmt = $conn->prepare("SELECT id, userType, password FROM user WHERE emailAddress = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No user found with that email
    header("Location: signin.php?error=invalid");
    exit();
}

$user = $result->fetch_assoc();

// 4. Verify the password against the stored hash
if (!password_verify($password, $user['password'])) {
    // Wrong password
    header("Location: signin.php?error=invalid");
    exit();
}

// 5. Login successful — set session variables
$_SESSION['userID']   = $user['id'];
$_SESSION['userType'] = $user['userType'];

// 6. Redirect based on user type
if ($user['userType'] === 'admin') {
    header("Location: admin.php");
} else {
    header("Location: user.php");
}
exit();

$stmt->close();
$conn->close();
?>
