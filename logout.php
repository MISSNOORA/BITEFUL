<?php
session_start();
session_destroy(); // clears all session variables
header("Location: signin.html");
exit();
?>
