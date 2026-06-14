<?php
session_start();

// Clear all session variables
$_SESSION = array();

//  Kill the session
session_destroy();

// send them back to the home page
header("Location: index.php");
exit();
?>