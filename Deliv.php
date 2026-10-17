<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Buy a bottle.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "projects");

if ($conn->connect_error) {
    error_log("Gas Hub database connection failed: " . $conn->connect_error);
    die("Unable to connect to the database. Please try again later.");
}

$conn->set_charset("utf8mb4");

$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken   = $_SESSION['csrf_token'] ?? '';

if ($submittedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    $conn->close();
    die("Invalid security token. Please return to the delivery page and try again.");
}

$product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);

if ($product_id === false || $product_id === null || $product_id <= 0) {
    $conn->close();
    die("Invalid product selected.");
}

$userId = (int)$_SESSION['user'];

$name     = trim($_POST["Name"] ?? "");
$email    = trim($_POST["email"] ?? "");
$address  = trim($_POST["Address"] ?? "");
$city     = trim($_POST["City"] ?? "");
$phone    = trim($_POST["phone"] ?? "");
$Gas_size = trim($_POST["Gas_size"] ?? "");
$quantityInput = $_POST["Quantity"] ?? "";

if ($name === "" || $email === "" || $address === "" || $city === "" || $phone === "" || $Gas_size === "" || $quantityInput === "") {
    $conn->close();
    die("Please fill in all required fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $conn->close();
    die("Please enter a valid email address.");
}

if (
    mb_strlen($name) > 100 ||
    mb_strlen($email) > 150 ||
    mb_strlen($address) > 255 ||
    mb_strlen($city) > 100 ||
    mb_strlen($phone) > 20 ||
    mb_strlen($Gas_size) > 50
) {
    $conn->close();
    die("One or more fields contain too much information.");
}

if (filter_var($quantityInput, FILTER_VALIDATE_INT) === false) {
    $conn->close();
    die("Invalid quantity.");
}

$quantity = (int)$quantityInput;

if ($quantity < 1 || $quantity > 50) {
    $conn->close();
    die("Quantity must be between 1 and 50.");
}

$cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);

if (preg_match('/^237[0-9]{9}$/', $cleanPhone)) {
    $cleanPhone = '+' . $cleanPhone;
} elseif (preg_match('/^[0-9]{9}$/', $cleanPhone)) {
    $cleanPhone = '+237' . $cleanPhone;
} elseif (!preg_match('/^\+[0-9]{9,15}$/', $cleanPhone)) {
    $conn->close();
    die("Please enter a valid Cameroon phone number.");
}

$productStmt = $conn->prepare(
    "SELECT id, name, price, location FROM element WHERE id = ? LIMIT 1"
);

if (!$productStmt) {
    error_log("Gas Hub product query preparation failed: " . $conn->error);
    $conn->close();
    die("Unable to retrieve the selected product. Please try again.");
}

$productStmt->bind_param("i", $product_id);

if (!$productStmt->execute()) {
    error_log("Gas Hub product query execution failed: " . $productStmt->error);
    $productStmt->close();
    $conn->close();
    die("Unable to retrieve the selected product. Please try again.");
}

$productResult = $productStmt->get_result();
$product = $productResult->fetch_assoc();
$productStmt->close();

if (!$product) {
    $conn->close();
    die("The selected gas bottle is no longer available.");
}

$officialProductId  = (int)$product['id'];
$officialBottleName = trim($product['name']);
$officialLocation   = trim($product['location']);
$officialUnitPrice  = (float)$product['price'];

if ($officialProductId <= 0 || $officialBottleName === '') {
    $conn->close();
    die("The selected product contains invalid information.");
}

if (!is_numeric($product['price']) || !is_finite($officialUnitPrice) || $officialUnitPrice <= 0) {
    $conn->close();
    die("The selected product has an invalid price.");
}

$subtotal = round($officialUnitPrice * $quantity, 2);

$commissionRate = 0.05;
$gasHubCommission = round($subtotal * $commissionRate, 2);
$sellerAmount = round($subtotal - $gasHubCommission, 2);

if (
    !is_finite($subtotal) || !is_finite($gasHubCommission) || !is_finite($sellerAmount) ||
    $subtotal <= 0 || $gasHubCommission < 0 || $sellerAmount < 0
) {
    $conn->close();
    die("Invalid order calculation.");
}

$sql = "
    INSERT INTO deliver
    (user_id, name, address, city, phone, email, bottle_name, Gas_size, quantity, amount)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Gas Hub order insert preparation failed: " . $conn->error);
    $conn->close();
    die("Unable to create your order. Please try again.");
}

$stmt->bind_param(
    "isssssssid",
    $userId,
    $name,
    $address,
    $city,
    $cleanPhone,
    $email,
    $officialBottleName,
    $Gas_size,
    $quantity,
    $subtotal
);

if (!$stmt->execute()) {
    error_log("Gas Hub order insert failed: " . $stmt->error);
    $stmt->close();
    $conn->close();
    die("Unable to create your order. Please try again.");
}

$orderId = $conn->insert_id;

if (!$orderId) {
    error_log("Gas Hub order insert succeeded but returned no order ID.");
    $stmt->close();
    $conn->close();
    die("The order could not be created correctly.");
}

$_SESSION['gas_hub_order'] = [
    'order_id'        => $orderId,
    'product_id'      => $officialProductId,
    'bottle_name'     => $officialBottleName,
    'location'        => $officialLocation,
    'gas_size'        => $Gas_size,
    'quantity'        => $quantity,
    'unit_price'      => $officialUnitPrice,
    'subtotal'        => $subtotal,
    'commission_rate' => $commissionRate,
    'commission'      => $gasHubCommission,
    'seller_amount'   => $sellerAmount,
    'customer_name'   => $name,
    'customer_email'  => $email,
    'address'         => $address,
    'city'            => $city,
    'phone'           => $cleanPhone,
    'created_at'      => date('Y-m-d H:i:s'),
    'payment_status'  => 'pending',
    'payment_method'  => null,
    'transaction_id'  => null
];

unset($_SESSION['csrf_token']);

$stmt->close();
$conn->close();

header("Location: payment.php");
exit;

?>