<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projects";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Gas Hub admin DB connection failed: " . $conn->connect_error);
    die("Unable to connect to the database. Please try again later.");
}

$conn->set_charset("utf8mb4");
?>