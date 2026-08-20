<?php

session_start();

// Clear all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Go back to login page
header("Location: login.html");
exit();

?>