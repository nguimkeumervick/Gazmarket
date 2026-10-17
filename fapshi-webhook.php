<?php

/*
 * fapshi-webhook.php
 *
 * Called directly by Fapshi's server when a payment finishes
 * (SUCCESSFUL, FAILED, or EXPIRED). Configure this file's full
 * URL as the webhook URL in your Fapshi dashboard, per service.
 *
 * IMPORTANT: this has no session. We never trust the incoming
 * payload alone — we re-verify the transaction status directly
 * with Fapshi's API using our own credentials before marking
 * anything as paid.
 */

$fapshiApiUser = getenv('FAPSHI_API_USER') ?: '';
$fapshiApiKey  = getenv('FAPSHI_API_KEY') ?: '';
$fapshiBaseUrl = getenv('FAPSHI_BASE_URL') ?: 'https://sandbox.fapshi.com';

header('Content-Type: application/json');

if (!$fapshiApiUser || !$fapshiApiKey) {
    error_log('Fapshi webhook received but gateway is not configured.');
    http_response_code(200); // acknowledge receipt either way
    echo json_encode(['received' => true]);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

if (!is_array($payload) || empty($payload['transId'])) {
    error_log('Fapshi webhook received malformed payload: ' . $rawBody);
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$transId = (string)$payload['transId'];

// Re-verify directly with Fapshi rather than trusting the POST body
$ch = curl_init($fapshiBaseUrl . '/payment-status/' . urlencode($transId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apiuser: ' . $fapshiApiUser,
    'apikey: ' . $fapshiApiKey,
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $statusCode !== 200) {
    error_log('Fapshi webhook: could not re-verify transaction ' . $transId);
    http_response_code(502);
    echo json_encode(['error' => 'verification failed']);
    exit;
}

$verified = json_decode($response, true);

if (!is_array($verified) || empty($verified['externalId']) || empty($verified['status'])) {
    error_log('Fapshi webhook: malformed verification response for ' . $transId);
    http_response_code(502);
    echo json_encode(['error' => 'malformed verification']);
    exit;
}

$orderId = filter_var($verified['externalId'], FILTER_VALIDATE_INT);
$verifiedStatus = $verified['status'];
$verifiedAmount = isset($verified['amount']) ? (int)$verified['amount'] : null;

if (!$orderId || $orderId <= 0) {
    error_log('Fapshi webhook: invalid externalId ' . $verified['externalId']);
    http_response_code(400);
    echo json_encode(['error' => 'invalid order reference']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'projects');

if ($conn->connect_error) {
    error_log('Fapshi webhook DB connection failed: ' . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['error' => 'database unavailable']);
    exit;
}

$conn->set_charset('utf8mb4');

// Fetch the order and confirm the amount matches before marking paid —
// defends against a webhook claiming success for the wrong amount.
$stmt = $conn->prepare("SELECT amount, payment_status FROM deliver WHERE id = ? AND transaction_id = ? LIMIT 1");
$stmt->bind_param('is', $orderId, $transId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    error_log("Fapshi webhook: no matching order for id=$orderId transId=$transId");
    $conn->close();
    http_response_code(404);
    echo json_encode(['error' => 'order not found']);
    exit;
}

if (
    $row['payment_status'] === 'pending' &&
    $verifiedStatus === 'SUCCESSFUL' &&
    ($verifiedAmount === null || $verifiedAmount == (int)round((float)$row['amount']))
) {
    $newStatus = 'paid';
} elseif ($verifiedStatus === 'FAILED' || $verifiedStatus === 'EXPIRED') {
    $newStatus = 'failed';
} else {
    // Nothing changed / not yet successful — acknowledge and do nothing further
    $conn->close();
    http_response_code(200);
    echo json_encode(['received' => true, 'status' => $row['payment_status']]);
    exit;
}

$update = $conn->prepare("UPDATE deliver SET payment_status = ? WHERE id = ? AND transaction_id = ?");
$update->bind_param('sis', $newStatus, $orderId, $transId);
$update->execute();
$update->close();

$conn->close();

http_response_code(200);
echo json_encode(['received' => true, 'status' => $newStatus]);