<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["user_id"])) {
    header("location: auth-login.php");
    exit;
}
?>