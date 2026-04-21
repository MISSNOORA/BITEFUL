<?php
// Include this at the very top of every page that requires login
session_start();

if (!isset($_SESSION['userID'])) {
    // Not logged in — send to login page
    header("Location: index.html");
    exit();
}
?>