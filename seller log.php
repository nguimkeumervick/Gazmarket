<?php
session_start();

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['seller_login_error'] = 'Invalid request. Please try again.';
    header('Location: seller login.php');
    exit; 
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['seller_login_error'] = 'Please enter both email and password.';
    header('Location: seller login.php');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'projects');
if ($conn->connect_error) {
    error_log('Gas Hub seller login DB connection failed: ' . $conn->connect_error);
    die('Something went wrong on our end. Please try again shortly.');
}
$conn->set_charset('utf8mb4');

$stmt = $conn->prepare("SELECT id, name, password FROM seller WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$seller = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$seller || !password_verify($password, $seller['password'])) {
    $_SESSION['seller_login_error'] = 'Invalid email or password.';
    header('Location: seller login.php');
    exit;
}

session_regenerate_id(true);
$_SESSION['seller_id'] = (int)$seller['id'];
$_SESSION['seller_name'] = $seller['name'];
unset($_SESSION['csrf_token']);

header('Location: create bottle.php');
exit;