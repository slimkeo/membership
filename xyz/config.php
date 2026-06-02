<?php
// =============================================
// FILE: config.php
// =============================================
// Show all PHP errors (Development Mode)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Optional: Also show errors in HTML format with colors
//error_reporting(E_ALL | E_STRICT);


$host     = "localhost";
$db_user  = "snatuni1_user";
$db_pass  = "Snat2026!";
$db_name  = "snatuni1_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>