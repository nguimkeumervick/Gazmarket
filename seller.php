<?php

session_start();

/*
|--------------------------------------------------------------------------
| Only allow POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Become a seller.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF protection
|--------------------------------------------------------------------------
*/

$sessionCsrf = $_SESSION['csrf_token'] ?? '';
$submittedCsrf = $_POST['csrf_token'] ?? '';

if (
    $sessionCsrf === '' ||
    $submittedCsrf === '' ||
    !hash_equals($sessionCsrf, $submittedCsrf)
) {
    $_SESSION['register-error'] =
        'Invalid request. Please refresh the page and try again.';

    header('Location: Become a seller.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Get and clean form data
|--------------------------------------------------------------------------
*/

$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';


/*
|--------------------------------------------------------------------------
| Validate required fields
|--------------------------------------------------------------------------
*/

if (
    $name === '' ||
    $email === '' ||
    $phone === '' ||
    $password === ''
) {
    $_SESSION['register-error'] =
        'Please fill in all required fields.';

    header('Location: Become a seller.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Validate seller name
|--------------------------------------------------------------------------
*/

if (strlen($name) < 2 || strlen($name) > 100) {
    $_SESSION['register-error'] =
        'Please enter a valid seller name.';

    header('Location: Become a seller.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Validate email
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register-error'] =
        'Please enter a valid email address.';

    header('Location: Become a seller.php');
    exit;
}

if (strlen($email) > 150) {
    $_SESSION['register-error'] =
        'Email address is too long.';

    header('Location: Become a seller.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Cameroon phone number
|--------------------------------------------------------------------------
|
| Accepted examples:
|
| 6XXXXXXXX
| 2XXXXXXXX
| +2376XXXXXXXX
| +2372XXXXXXXX
|
| Spaces, hyphens and brackets are removed before validation.
|--------------------------------------------------------------------------
*/

$cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);

if (!preg_match('/^(?:\+237)?[0-9]{9}$/', $cleanPhone)) {
    $_SESSION['register-error'] =
        'Please enter a valid Cameroon phone number.';

    header('Location: Become a seller.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Normalize phone number
|--------------------------------------------------------------------------
*/

if (strpos($cleanPhone, '+237') !== 0) {
    $cleanPhone = '+237' . $cleanPhone;
}

$phone = $cleanPhone;


/*
|--------------------------------------------------------------------------
| Validate password
|--------------------------------------------------------------------------
*/

if (strlen($password) < 8) {
    $_SESSION['register-error'] =
        'Password must contain at least 8 characters.';

    header('Location: Become a seller.php');
    exit;
}

if (strlen($password) > 72) {
    $_SESSION['register-error'] =
        'Password is too long.';

    header('Location: Become a seller.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Database connection
|--------------------------------------------------------------------------
*/

$conn = new mysqli(
    'localhost',
    'root',
    '',
    'projects'
);

if ($conn->connect_error) {

    error_log(
        'Gas Hub seller registration database connection error: '
        . $conn->connect_error
    );

    $_SESSION['register-error'] =
        'The service is temporarily unavailable. Please try again later.';

    header('Location: Become a seller.php');
    exit;
}

$conn->set_charset('utf8mb4');


/*
|--------------------------------------------------------------------------
| Check whether seller email already exists
|--------------------------------------------------------------------------
*/

$check = $conn->prepare(
    "SELECT email
     FROM seller
     WHERE email = ?
     LIMIT 1"
);

if (!$check) {

    error_log(
        'Gas Hub seller registration prepare error: '
        . $conn->error
    );

    $conn->close();

    $_SESSION['register-error'] =
        'Unable to process your registration. Please try again.';

    header('Location: Become a seller.php');
    exit;
}


$check->bind_param('s', $email);

$check->execute();

$result = $check->get_result();


/*
|--------------------------------------------------------------------------
| Seller already exists
|--------------------------------------------------------------------------
*/

if ($result && $result->num_rows > 0) {

    $check->close();
    $conn->close();

    $_SESSION['register-error'] =
        'A seller account with this email already exists.';

    header('Location: Become a seller.php');
    exit;
}

$check->close();


/*
|--------------------------------------------------------------------------
| Hash seller password
|--------------------------------------------------------------------------
*/

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

if ($hashedPassword === false) {

    $conn->close();

    $_SESSION['register-error'] =
        'Unable to secure your password. Please try again.';

    header('Location: Become a seller.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Insert seller into database
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "INSERT INTO seller (name, email, phone, password)
     VALUES (?, ?, ?, ?)"
);

if (!$stmt) {

    error_log(
        'Gas Hub seller insert prepare error: '
        . $conn->error
    );

    $conn->close();

    $_SESSION['register-error'] =
        'Unable to create seller account. Please try again.';

    header('Location: Become a seller.php');
    exit;
}


$stmt->bind_param(
    'ssss',
    $name,
    $email,
    $phone,
    $hashedPassword
);


/*
|--------------------------------------------------------------------------
| Save seller
|--------------------------------------------------------------------------
*/

if ($stmt->execute()) {

    /*
    |--------------------------------------------------------------------------
    | Prevent session fixation
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | Create seller session
    |--------------------------------------------------------------------------
    */

    $_SESSION['seller'] = $email;
    $_SESSION['seller_name'] = $name;


    /*
    |--------------------------------------------------------------------------
    | Clear previous registration error
    |--------------------------------------------------------------------------
    */

    unset($_SESSION['register-error']);


    /*
    |--------------------------------------------------------------------------
    | Generate a fresh CSRF token
    |--------------------------------------------------------------------------
    */

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));


    /*
    |--------------------------------------------------------------------------
    | Close database resources
    |--------------------------------------------------------------------------
    */

    $stmt->close();
    $conn->close();


    /*
    |--------------------------------------------------------------------------
    | Send seller to bottle listing page
    |--------------------------------------------------------------------------
    */

    header('Location: create bottle.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Handle database insertion failure
|--------------------------------------------------------------------------
*/

error_log(
    'Gas Hub seller registration insert error: '
    . $stmt->error
);

$stmt->close();
$conn->close();

$_SESSION['register-error'] =
    'Unable to create seller account. Please try again.';

header('Location: Become a seller.php');
exit;

?>