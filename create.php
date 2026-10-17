<?php
session_start();

// Must be logged in as a seller to post a listing
if (empty($_SESSION['seller_id'])) {
    header('Location: seller login.php');
    exit;
}

// CSRF check
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['create_error'] = 'Invalid request. Please try submitting the form again.';
    header('Location: create bottle.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'projects');
if ($conn->connect_error) {
    error_log('DB connection failed: ' . $conn->connect_error);
    $_SESSION['create_error'] = 'Something went wrong on our end. Please try again shortly.';
    header('Location: create bottle.php');
    exit;
}

// Validate text fields
$name        = trim($_POST['name'] ?? '');
$price       = $_POST['price'] ?? '';
$location    = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($name === '' || $location === '' || $description === '') {
    $_SESSION['create_error'] = 'Please fill in every field.';
    header('Location: create bottle.php');
    exit;
}
if (!is_numeric($price) || $price < 0) {
    $_SESSION['create_error'] = 'Please enter a valid price.';
    header('Location: create bottle.php');
    exit;
}

// Validate the uploaded image
if (!isset($_FILES['img']) || $_FILES['img']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['create_error'] = 'Please choose a valid photo to upload.';
    header('Location: create bottle.php');
    exit;
}

$maxBytes = 2 * 1024 * 1024; // 2MB
if ($_FILES['img']['size'] > $maxBytes) {
    $_SESSION['create_error'] = 'Image is too large (max 2MB).';
    header('Location: create bottle.php');
    exit;
}

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

// Check the ACTUAL file content, not just the filename/extension the browser sent
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['img']['tmp_name']);
finfo_close($finfo);

if (!isset($allowedTypes[$mimeType])) {
    $_SESSION['create_error'] = 'Only JPG, PNG or WEBP images are allowed.';
    header('Location: create bottle.php');
    exit;
}

// Generate a random filename instead of trusting the one the user sent
$extension = $allowedTypes[$mimeType];
$imgFilename = bin2hex(random_bytes(16)) . '.' . $extension;

$uploadDir = __DIR__ . '/images/';
if (!move_uploaded_file($_FILES['img']['tmp_name'], $uploadDir . $imgFilename)) {
    error_log('Failed to move uploaded file for seller ' . $_SESSION['seller_id']);
    $_SESSION['create_error'] = 'Something went wrong uploading your photo. Please try again.';
    header('Location: create bottle.php');
    exit;
}

// Save to the database using a prepared statement, now recording seller ownership
$sellerId = (int)$_SESSION['seller_id'];

$stmt = $conn->prepare("INSERT INTO element (seller_id, name, price, location, img, description) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('isdsss', $sellerId, $name, $price, $location, $imgFilename, $description);

if ($stmt->execute()) {
    header('Location: Buy a bottle.php');
    exit;
} else {
    error_log('Insert failed: ' . $stmt->error);
    $_SESSION['create_error'] = 'Something went wrong saving your listing. Please try again.';
    header('Location: create bottle.php');
    exit;
}