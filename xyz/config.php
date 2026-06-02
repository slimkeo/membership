<?php
// =============================================
// FILE: config.php
// =============================================
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