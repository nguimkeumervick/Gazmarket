<?php

session_start();

/*
 * Fapshi configuration.
 *
 * These are blank until you sign up at fapshi.com and get sandbox keys.
 * Set them as real environment variables on your server later —
 * never hardcode them directly here.
 */
$fapshiApiUser  = getenv('FAPSHI_API_USER') ?: '';
$fapshiApiKey   = getenv('FAPSHI_API_KEY') ?: '';
$fapshiBaseUrl  = getenv('FAPSHI_BASE_URL') ?: 'https://sandbox.fapshi.com';
$fapshiConfigured = ($fapshiApiUser !== '' && $fapshiApiKey !== '');

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['gas_hub_order']) || !is_array($_SESSION['gas_hub_order'])) {
    header('Location: Buy a bottle.php');
    exit;
}

$order = $_SESSION['gas_hub_order'];

$requiredOrderFields = [
    'order_id', 'product_id', 'bottle_name', 'quantity', 'unit_price',
    'subtotal', 'commission', 'seller_amount', 'customer_name',
    'customer_email', 'address', 'city', 'phone', 'payment_status'
];

foreach ($requiredOrderFields as $field) {
    if (!array_key_exists($field, $order)) {
        unset($_SESSION['gas_hub_order']);
        header('Location: Buy a bottle.php');
        exit;
    }
}

if ($order['payment_status'] !== 'pending') {
    if ($order['payment_status'] === 'paid') {
        header('Location: receipt.php');
        exit;
    }
    unset($_SESSION['gas_hub_order']);
    header('Location: Buy a bottle.php');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'projects');

if ($conn->connect_error) {
    error_log('Gas Hub payment database connection failed: ' . $conn->connect_error);
    die('Unable to connect to the database. Please try again later.');
}

$conn->set_charset('utf8mb4');

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
 * Minimal cURL helper for calling the Fapshi API.
 * Returns [statusCode, decodedBody] or [0, null] on a transport failure.
 */
function fapshiRequest($method, $path, $baseUrl, $apiUser, $apiKey, $body = null)
{
    $ch = curl_init($baseUrl . $path);

    $headers = [
        'Content-Type: application/json',
        'apiuser: ' . $apiUser,
        'apikey: ' . $apiKey,
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('Fapshi request transport error: ' . $curlError);
        return [0, null];
    }

    return [$statusCode, json_decode($response, true)];
}

$paymentError = '';

if (isset($_SESSION['payment-error'])) {
    $paymentError = (string)$_SESSION['payment-error'];
    unset($_SESSION['payment-error']);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/*
 * ==========================================================
 * HANDLE "PAY NOW" SUBMISSION
 * ==========================================================
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if ($submittedToken === '' || !hash_equals($csrfToken, $submittedToken)) {

        $paymentError = 'Invalid security token. Please refresh the payment page and try again.';

    } elseif (!$fapshiConfigured) {

        /*
         * This is the expected state until you add real API keys.
         * Nothing is charged, nothing is faked as paid.
         */
        $paymentError = 'Online payment is not fully set up yet. Please contact the Gas Hub administrator to complete your order.';

    } else {

        $orderId    = (int)$order['order_id'];
        $totalToPay = (int)round((float)$order['subtotal']);
        $customerEmail = trim((string)$order['customer_email']);

        if ($totalToPay < 100) {
            $paymentError = 'The order amount is too small to process.';
        } else {

            // Absolute redirect URL Fapshi sends the customer's browser back to
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'];
            $redirectUrl = $scheme . '://' . $host . '/receipt.php?order_id=' . $orderId;

            [$status, $body] = fapshiRequest(
                'POST',
                '/initiate-pay',
                $fapshiBaseUrl,
                $fapshiApiUser,
                $fapshiApiKey,
                [
                    'amount'      => $totalToPay,
                    'email'       => $customerEmail,
                    'redirectUrl' => $redirectUrl,
                    'externalId'  => (string)$orderId,
                    'message'     => 'Gas Hub order #' . $orderId,
                ]
            );

            if ($status === 200 && !empty($body['link']) && !empty($body['transId'])) {

                // Persist the transaction ID immediately, so the webhook can find
                // this order even though it has no access to this session.
                $updateStmt = $conn->prepare(
                    "UPDATE deliver SET transaction_id = ? WHERE id = ?"
                );
                $updateStmt->bind_param('si', $body['transId'], $orderId);
                $updateStmt->execute();
                $updateStmt->close();

                $_SESSION['gas_hub_order']['transaction_id'] = $body['transId'];

                $conn->close();

                header('Location: ' . $body['link']);
                exit;

            } else {

                error_log('Fapshi initiate-pay failed. HTTP ' . $status . ' Body: ' . json_encode($body));
                $paymentError = 'We could not start the payment. Please try again in a moment.';
            }
        }
    }
}

/*
 * ==========================================================
 * ORDER VALUES FOR DISPLAY
 * ==========================================================
 */

$orderId        = (int)$order['order_id'];
$productId      = (int)$order['product_id'];
$bottleName     = trim((string)$order['bottle_name']);
$quantity       = (int)$order['quantity'];
$unitPrice      = (float)$order['unit_price'];
$subtotal       = (float)$order['subtotal'];
$commission     = (float)$order['commission'];
$sellerAmount   = (float)$order['seller_amount'];
$commissionRate = isset($order['commission_rate']) ? (float)$order['commission_rate'] : 0.05;
$customerName   = trim((string)$order['customer_name']);
$customerEmail  = trim((string)$order['customer_email']);
$city           = trim((string)$order['city']);

if (
    $orderId <= 0 || $productId <= 0 || $quantity < 1 || $quantity > 50 ||
    $unitPrice <= 0 || $subtotal <= 0 || $commission < 0 || $sellerAmount < 0
) {
    $conn->close();
    unset($_SESSION['gas_hub_order']);
    header('Location: Buy a bottle.php');
    exit;
}

$expectedSubtotal = round($unitPrice * $quantity, 2);
$expectedCommission = round($expectedSubtotal * $commissionRate, 2);
$expectedSellerAmount = round($expectedSubtotal - $expectedCommission, 2);

if (
    abs($subtotal - $expectedSubtotal) > 0.01 ||
    abs($commission - $expectedCommission) > 0.01 ||
    abs($sellerAmount - $expectedSellerAmount) > 0.01
) {
    $conn->close();
    unset($_SESSION['gas_hub_order']);
    die('The order calculation is invalid. Please create the order again.');
}

$totalToPay = $subtotal;

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Secure Gas Hub payment">
    <title>Payment | Gas Hub</title>

    <link rel="stylesheet" href="./font/css/all.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            color: #172033;
        }

        a, a:hover, a:focus, a:active, a:visited {
            text-decoration: none !important;
            outline: none !important;
        }

        .navbar { background: #071a2f !important; padding: 15px 30px; }
        .navbar-brand { color: #f4b41a !important; font-size: 25px; font-weight: 800; }
        .nav-link { color: #ffffff !important; font-weight: 600; margin-left: 15px; }
        .nav-link:hover { color: #f4b41a !important; }

        .page-header {
            background: linear-gradient(rgba(7, 26, 47, .92), rgba(7, 26, 47, .92)), url('image/banner.png') center/cover;
            color: white;
            padding: 55px 20px;
            text-align: center;
        }

        .page-header h1 { font-size: 38px; font-weight: 800; margin-bottom: 10px; }
        .page-header p { margin: 0; color: #e8edf3; }

        .payment-wrapper { max-width: 1100px; margin: 40px auto; padding: 0 20px; }

        .card-box {
            background: #ffffff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 8px 30px rgba(7, 26, 47, .08);
            height: 100%;
        }

        .section-title { color: #071a2f; font-weight: 800; margin-bottom: 22px; }

        .order-number {
            display: inline-block;
            background: #eef3f8;
            color: #071a2f;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .product-name { font-size: 22px; font-weight: 800; color: #071a2f; }

        .order-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 12px 0;
            border-bottom: 1px solid #e8edf2;
        }

        .order-row:last-child { border-bottom: none; }
        .order-label { color: #657080; }
        .order-value { font-weight: 700; text-align: right; }
        .commission-row { color: #b57900; }
        .seller-row { color: #26734d; }

        .total-box {
            margin-top: 20px;
            padding: 18px;
            border-radius: 12px;
            background: #071a2f;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-box strong { color: #f4b41a; font-size: 25px; }

        .provider-row {
            display: flex;
            gap: 12px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .provider-chip {
            flex: 1 1 auto;
            border: 2px solid #e1e7ed;
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            font-weight: 700;
            color: #071a2f;
            font-size: 13px;
        }

        .provider-chip i { display: block; font-size: 20px; color: #f4b41a; margin-bottom: 6px; }

        .btn-pay {
            width: 100%;
            border: none;
            border-radius: 10px;
            background: #f4b41a;
            color: #071a2f;
            padding: 15px;
            font-size: 17px;
            font-weight: 800;
            margin-top: 10px;
            cursor: pointer;
        }

        .btn-pay:hover { background: #d99d00; }
        .btn-pay:disabled { background: #d9dee5; color: #8a94a3; cursor: not-allowed; }

        .security-note {
            margin-top: 18px;
            padding: 14px;
            border-radius: 10px;
            background: #eef8f2;
            color: #276746;
            font-size: 14px;
        }

        .warning-note {
            margin-top: 15px;
            padding: 14px;
            border-radius: 10px;
            background: #fff7df;
            color: #765800;
            font-size: 14px;
        }

        footer {
            background: #071a2f;
            color: #d9e0e8;
            padding: 30px 20px;
            margin-top: 50px;
            text-align: center;
        }

        footer strong { color: #f4b41a; }

        @media (max-width: 768px) {
            .navbar { padding: 12px 15px; }
            .page-header h1 { font-size: 30px; }
            .card-box { padding: 20px; margin-bottom: 20px; }
        }

    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <a class="navbar-brand" href="home page for gas.php"><i class="fas fa-fire"></i> Gas Hub</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#gasHubNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="gasHubNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="home page for gas.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="Buy a bottle.php">Buy a Bottle</a></li>
            <li class="nav-item"><a class="nav-link" href="Become a seller.php">Become a Seller</a></li>
        </ul>
    </div>
</nav>

<section class="page-header">
    <h1>Secure Payment</h1>
    <p>Complete your Gas Hub order securely.</p>
</section>

<div class="payment-wrapper">

    <?php if ($paymentError !== ''): ?>
        <div class="alert alert-warning">
            <i class="fas fa-info-circle mr-2"></i> <?= e($paymentError) ?>
        </div>
    <?php endif; ?>

    <div class="row">

        <div class="col-lg-5 mb-4">
            <div class="card-box">

                <h3 class="section-title"><i class="fas fa-receipt"></i> Order Summary</h3>

                <div class="order-number">Order #<?= $orderId ?></div>
                <div class="product-name mb-3"><?= e($bottleName) ?></div>

                <div class="order-row">
                    <span class="order-label">Quantity</span>
                    <span class="order-value"><?= $quantity ?></span>
                </div>

                <div class="order-row">
                    <span class="order-label">Unit price</span>
                    <span class="order-value"><?= number_format($unitPrice, 0) ?> FCFA</span>
                </div>

                <div class="order-row">
                    <span class="order-label">Subtotal</span>
                    <span class="order-value"><?= number_format($subtotal, 0) ?> FCFA</span>
                </div>

                <div class="order-row commission-row">
                    <span class="order-label">Gas Hub commission (<?= number_format($commissionRate * 100, 0) ?>%)</span>
                    <span class="order-value"><?= number_format($commission, 0) ?> FCFA</span>
                </div>

                <div class="order-row seller-row">
                    <span class="order-label">Seller receives</span>
                    <span class="order-value"><?= number_format($sellerAmount, 0) ?> FCFA</span>
                </div>

                <div class="total-box">
                    <span>Total to pay</span>
                    <strong><?= number_format($totalToPay, 0) ?> FCFA</strong>
                </div>

                <div class="mt-4">
                    <small class="text-muted"><strong>Customer:</strong> <?= e($customerName) ?></small><br>
                    <small class="text-muted"><strong>Email:</strong> <?= e($customerEmail) ?></small><br>
                    <small class="text-muted"><strong>Location:</strong> <?= e($city) ?></small>
                </div>

            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card-box">

                <h3 class="section-title"><i class="fas fa-credit-card"></i> Pay Securely</h3>

                <p class="text-muted">You'll choose between MTN Mobile Money, Orange Money, or a bank card on the next secure screen.</p>

                <div class="provider-row">
                    <div class="provider-chip"><i class="fas fa-mobile-alt"></i> MTN MoMo</div>
                    <div class="provider-chip"><i class="fas fa-mobile-alt"></i> Orange Money</div>
                    <div class="provider-chip"><i class="fas fa-credit-card"></i> Visa / Mastercard</div>
                </div>

                <form action="payment.php" method="POST">

                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                    <button type="submit" class="btn-pay" <?= !$fapshiConfigured ? 'disabled' : '' ?>>
                        <i class="fas fa-lock mr-2"></i>
                        <?= $fapshiConfigured ? 'Continue to Payment' : 'Payment Temporarily Unavailable' ?>
                    </button>

                </form>

                <div class="security-note">
                    <i class="fas fa-lock mr-1"></i>
                    <strong>Payment security:</strong> Gas Hub never asks for your Mobile Money PIN, bank PIN, or card CVV directly — you'll enter those only on the payment provider's own secure page.
                </div>

                <div class="warning-note">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Payment verification:</strong> Your order is marked paid only after the payment provider confirms the transaction to our server.
                </div>

            </div>
        </div>

    </div>
</div>

<footer>
    <strong>Gas Hub</strong>
    <p class="mb-0 mt-2">Safe and convenient gas-cylinder marketplace.</p>
</footer>

<script src="https://code.jquery.com/jquery-3.6.4.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

</body>
</html>