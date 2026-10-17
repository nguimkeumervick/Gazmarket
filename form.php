
<?php

// Start session
session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signup.php");
    exit;
}

// Get submitted values
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$password = $_POST['password'] ?? '';

// Validate required fields
if (
    $firstName === '' ||
    $lastName === '' ||
    $email === '' ||
    $telephone === '' ||
    $password === ''
) {
    die("Please fill in all required fields.");
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Please enter a valid email address.");
}

// Password validation
if (strlen($password) < 8) {
    die("Password must contain at least 8 characters.");
}

// Database connection
$conn = new mysqli(
    'localhost',
    'root',
    '',
    'projects'
);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Check if email already exists
$check = $conn->prepare(
    "SELECT email FROM signup WHERE email = ?"
);

if (!$check) {
    die("Database error: " . $conn->error);
}

$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    $conn->close();

    die("An account with this email already exists.");
}

$check->close();

// Hash password before storing it
$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// Insert new user
$stmt = $conn->prepare(
    "INSERT INTO signup
    (firstName, lastName, email, telephone, password)
    VALUES (?, ?, ?, ?, ?)"
);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "sssss",
    $firstName,
    $lastName,
    $email,
    $telephone,
    $hashedPassword
);

// Execute registration
if ($stmt->execute()) {

    // Prevent session fixation
    session_regenerate_id(true);

    // Store logged-in user
    $_SESSION['user'] = $email;
    $_SESSION['firstName'] = $firstName;
    $_SESSION['lastName'] = $lastName;

    $stmt->close();
    $conn->close();

    // Redirect to home page
    header("Location: home page for gas.php");
    exit;

} else {

    echo "Error: " . $stmt->error;

    $stmt->close();
    $conn->close();
}

?>
```
