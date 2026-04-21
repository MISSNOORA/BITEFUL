<?php
session_start();
session_destroy(); // clears all session variables
header("Location: index.html");
exit();
?>
