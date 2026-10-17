<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$order_id || $order_id <= 0) {
    header("Location: Buy a bottle.php");
    exit;
} 

$conn = new mysqli("localhost", "root", "", "projects");

if ($conn->connect_error) {
    error_log("Gas Hub receipt DB connection failed: " . $conn->connect_error);
    die("Unable to connect to the database. Please try again later.");
}

$conn->set_charset("utf8mb4");

$stmt = $conn->prepare(
    "SELECT user_id, name, address, city, phone, email, bottle_name, Gas_size, quantity, amount, payment_status, transaction_id
     FROM deliver
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    error_log("Gas Hub receipt query preparation failed: " . $conn->error);
    die("Unable to retrieve your order. Please try again.");
}

$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$order) {
    header("Location: Buy a bottle.php");
    exit;
}

// Ownership check: this order must belong to the logged-in user
if ((int)$order['user_id'] !== (int)$_SESSION['user']) {
    http_response_code(403);
    die("You are not authorized to view this receipt.");
}

$isPaid = ($order['payment_status'] === 'paid');

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt | Gas Hub</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="./font/css/all.css">

  <style>
    :root {
      --gh-navy: #071a2f;
      --gh-gold: #f4b41a;
      --gh-gold-dark: #d99d00;
      --gh-success: #027a48;
      --gh-bg: #f5f7fa;
    }

    body { font-family: Arial, sans-serif; background: var(--gh-bg); margin: 0; padding: 0; }

    .status-banner {
      padding: 40px 20px;
      text-align: center;
      color: white;
    }
    .status-banner.paid { background: var(--gh-success); }
    .status-banner.pending { background: #b57900; }
    .status-banner.failed { background: #b02a2a; }

    .status-banner h1 { margin: 10px 0 0; font-size: 28px; }
    .status-banner i { font-size: 40px; }

    .receipt {
      max-width: 600px;
      margin: -20px auto 40px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 8px 25px rgba(7,26,47,.1);
      padding: 30px;
    }

    .receipt h2 {
      text-align: center;
      margin-bottom: 20px;
      color: var(--gh-navy);
    }

    .receipt table { width: 100%; border-collapse: collapse; }
    .receipt table th, .receipt table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
    .receipt table th { color: #667085; font-weight: 600; width: 40%; }
    .receipt .total th, .receipt .total td { font-weight: 800; font-size: 18px; color: var(--gh-navy); border-top: 2px solid var(--gh-navy); }

    .order-ref { text-align: center; color: #667085; font-size: 13px; margin-bottom: 20px; }

    .btn-back {
      display: block;
      width: 100%;
      text-align: center;
      margin-top: 20px;
      background: var(--gh-gold);
      color: var(--gh-navy) !important;
      padding: 12px;
      border-radius: 8px;
      font-weight: 700;
      text-decoration: none !important;
    }
  </style>
</head>
<body>

  <?php if ($isPaid): ?>
    <div class="status-banner paid">
      <i class="fas fa-check-circle"></i>
      <h1>Payment Successful</h1>
      <p>Your order has been confirmed.</p>
    </div>
  <?php elseif ($order['payment_status'] === 'failed'): ?>
    <div class="status-banner failed">
      <i class="fas fa-times-circle"></i>
      <h1>Payment Failed</h1>
      <p>Your payment could not be completed.</p>
    </div>
  <?php else: ?>
    <div class="status-banner pending">
      <i class="fas fa-clock"></i>
      <h1>Payment Pending</h1>
      <p>We haven't received confirmation of your payment yet.</p>
    </div>
  <?php endif; ?>

  <div class="receipt">
    <h2>Order Receipt</h2>
    <p class="order-ref">Order #<?= (int)$order_id ?><?= $order['transaction_id'] ? ' &middot; Ref: ' . e($order['transaction_id']) : '' ?></p>

    <table>
      <tr><th>Name</th><td><?= e($order['name']) ?></td></tr>
      <tr><th>Address</th><td><?= e($order['address']) ?>, <?= e($order['city']) ?></td></tr>
      <tr><th>Phone</th><td><?= e($order['phone']) ?></td></tr>
      <tr><th>Email</th><td><?= e($order['email']) ?></td></tr>
      <tr><th>Bottle</th><td><?= e($order['bottle_name']) ?></td></tr>
      <tr><th>Gas size</th><td><?= e($order['Gas_size']) ?></td></tr>
      <tr><th>Quantity</th><td><?= (int)$order['quantity'] ?></td></tr>
      <tr class="total"><th>Amount</th><td><?= number_format((float)$order['amount'], 0) ?> FCFA</td></tr>
    </table>

    <?php if ($isPaid): ?>
      <p class="text-center mt-3">Thank you for your order!</p>
    <?php elseif (!$isPaid && $order['payment_status'] !== 'failed'): ?>
      <a href="payment.php" class="btn-back">Complete Payment</a>
    <?php else: ?>
      <a href="Buy a bottle.php" class="btn-back">Back to Marketplace</a>
    <?php endif; ?>
  </div>

</body>
</html>