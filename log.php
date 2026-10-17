<?php
session_start();

/*
|--------------------------------------------------------------------------
| Only allow POST requests
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get form data
|--------------------------------------------------------------------------
*/
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

/*
|--------------------------------------------------------------------------
| Validate input
|--------------------------------------------------------------------------
*/
if ($email === '' || $password === '') {
    $_SESSION['error'] = 'Please enter your email and password.';
    header('Location: login.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Database connection
|--------------------------------------------------------------------------
*/
$conn = new mysqli('localhost', 'root', '', 'projects');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

/*
|--------------------------------------------------------------------------
| Find user by email
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare(
    "SELECT firstName, lastName, email, password 
     FROM signup 
     WHERE email = ? 
     LIMIT 1"
);

if (!$stmt) {
    die('Database error: ' . $conn->error);
}

$stmt->bind_param('s', $email);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Check if account exists
|--------------------------------------------------------------------------
*/
if (!$data) {
    $_SESSION['error'] = 'Email or password invalid.';
    $stmt->close();
    $conn->close();

    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Verify password
|--------------------------------------------------------------------------
*/
if (!password_verify($password, $data['password'])) {
    $_SESSION['error'] = 'Email or password invalid.';
    $stmt->close();
    $conn->close();

    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Login successful
|--------------------------------------------------------------------------
*/
session_regenerate_id(true);

$_SESSION['user'] = $data['email'];
$_SESSION['firstName'] = $data['firstName'];
$_SESSION['lastName'] = $data['lastName'];
$_SESSION['error'] = '';

$stmt->close();
$conn->close();

/*
|--------------------------------------------------------------------------
| Redirect to logged-in home page
|--------------------------------------------------------------------------
*/
header('Location: home page for gas.php');
exit;
?>