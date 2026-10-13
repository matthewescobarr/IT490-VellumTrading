<?php
session_start();

// Check whether the user has an authenticated session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>