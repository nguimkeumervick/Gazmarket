

<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === '') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: user details.php');
    exit;
}

$submittedToken = $_GET['csrf_token'] ?? '';

if (empty($_SESSION['admin_csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $submittedToken)) {
    http_response_code(400);
    die('Invalid request. Please go back and try again.');
}

$stmt = mysqli_prepare($conn, "DELETE FROM signup WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    header('Location: user details.php');
    exit;
} else {
    error_log("Gas Hub admin delete (signup) failed: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    die("Unable to delete this user. Please try again.");
}