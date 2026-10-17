<?php

session_start();
require_once 'config.php';

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS PROTECTION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['username']) || $_SESSION['username'] === '') {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

$conn->set_charset("utf8mb4");

/*
|--------------------------------------------------------------------------
| OPTIONAL STATUS FILTER
|--------------------------------------------------------------------------
*/

$allowedStatuses = ['pending', 'accepted', 'rejected', 'payment_pending', 'payment_confirmed', 'out_for_delivery', 'delivered', 'completed', 'cancelled'];
$filter = $_GET['status'] ?? '';
$filter = in_array($filter, $allowedStatuses, true) ? $filter : '';

/*
|--------------------------------------------------------------------------
| COUNTS PER STATUS
|--------------------------------------------------------------------------
*/

$counts = ['pending' => 0, 'accepted' => 0, 'rejected' => 0, 'other' => 0];

$countResult = $conn->query("SELECT status, COUNT(*) AS c FROM orders GROUP BY status");
if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        if (in_array($row['status'], ['pending', 'accepted', 'rejected'], true)) {
            $counts[$row['status']] = (int)$row['c'];
        } else {
            $counts['other'] += (int)$row['c'];
        }
    }
}

$totalOrders = array_sum($counts);

/*
|--------------------------------------------------------------------------
| LOAD ORDERS
|--------------------------------------------------------------------------
*/

$orders = [];

$sql = "
    SELECT o.id, o.status, o.quantity, o.created_at,
           e.name AS bottle_name, e.price, e.location,
           s.name AS seller_name,
           b.firstName AS buyer_first, b.lastName AS buyer_last
    FROM orders o
    JOIN element e ON e.id = o.product_id
    JOIN seller s ON s.id = o.seller_id
    JOIN signup b ON b.id = o.buyer_id
";

if ($filter !== '') {
    $sql .= " WHERE o.status = ? ";
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Gas Hub admin orders query failed: " . $conn->error);
    die("Unable to load orders.");
}

if ($filter !== '') {
    $stmt->bind_param('s', $filter);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$statusBadges = [
    'pending'           => ['bg-pending', 'Pending'],
    'accepted'          => ['bg-success2', 'Accepted'],
    'rejected'          => ['bg-danger2', 'Rejected'],
    'payment_pending'   => ['bg-pending', 'Awaiting payment'],
    'payment_confirmed' => ['bg-success2', 'Paid'],
    'out_for_delivery'  => ['bg-success2', 'Out for delivery'],
    'delivered'         => ['bg-success2', 'Delivered'],
    'completed'         => ['bg-success2', 'Completed'],
    'cancelled'         => ['bg-danger2', 'Cancelled'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders · Gas Hub Admin</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./font/css/all.css">

    <style>
        :root {
            --gh-navy: #071a2f;
            --gh-gold: #f4b41a;
            --gh-gold-dark: #d99d00;
            --gh-bg: #f5f7fa;
            --gh-text: #172033;
            --gh-muted: #6b7280;
            --gh-border: #e5e7eb;
            --gh-danger: #b42318;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--gh-bg);
            color: var(--gh-text);
            font-family: Arial, Helvetica, sans-serif;
        }

        a, a:hover, a:focus, a:active, a:visited {
            text-decoration: none !important;
            outline: none !important;
        }

        .sidebar {
            min-height: 100vh;
            background: var(--gh-navy);
            padding: 25px 18px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-size: 23px;
            font-weight: 700;
            margin-bottom: 35px;
        }

        .brand:hover { color: #fff; }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--gh-gold);
            color: var(--gh-navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .brand span { color: var(--gh-gold); }

        .sidebar-title {
            color: #8fa3b8;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 12px 12px;
        }

        .sidebar .nav-link {
            color: #cbd5e1;
            padding: 12px 14px;
            margin-bottom: 5px;
            border-radius: 10px;
            font-size: 14px;
            transition: all .2s ease;
        }

        .sidebar .nav-link i { width: 24px; margin-right: 7px; }

        .sidebar .nav-link:hover {
            background: rgba(244, 180, 26, .12);
            color: #fff;
        }

        .sidebar .nav-link.active {
            background: var(--gh-gold);
            color: var(--gh-navy);
            font-weight: 700;
        }

        .logout-link { color: #ffb4b4 !important; margin-top: 25px; }

        .logout-link:hover {
            background: rgba(220, 53, 69, .15) !important;
            color: #fff !important;
        }

        .main-content { padding: 28px; }

        .topbar {
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            padding: 18px 22px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 20px rgba(7, 26, 47, .04);
        }

        .topbar h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            color: var(--gh-navy);
        }

        .welcome { color: var(--gh-muted); font-size: 14px; margin-top: 3px; }

        .filter-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }

        .filter-pill {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            background: #fff;
            border: 1px solid var(--gh-border);
            color: var(--gh-text);
        }

        .filter-pill.active {
            background: var(--gh-navy);
            color: #fff;
            border-color: var(--gh-navy);
        }

        .orders-card {
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(7, 26, 47, .05);
        }

        .orders-header {
            padding: 20px 22px;
            border-bottom: 1px solid var(--gh-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .orders-header h2 { margin: 0; color: var(--gh-navy); font-size: 19px; font-weight: 700; }

        .order-count {
            background: #fff7df;
            color: #8a6500;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .table-wrapper { width: 100%; overflow-x: auto; }

        .orders-table { width: 100%; min-width: 1000px; margin: 0; border-collapse: collapse; }

        .orders-table thead th {
            background: var(--gh-navy);
            color: #fff;
            padding: 15px 14px;
            border: none;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        .orders-table tbody td {
            padding: 14px;
            border-bottom: 1px solid #edf0f3;
            color: #374151;
            font-size: 13px;
            vertical-align: middle;
        }

        .orders-table tbody tr:last-child td { border-bottom: none; }
        .orders-table tbody tr:hover { background: #fafbfc; }

        .order-id { color: var(--gh-navy); font-weight: 700; }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .bg-pending { background: #fff7df; color: #8a6500; }
        .bg-success2 { background: #ecfdf3; color: #027a48; }
        .bg-danger2 { background: #fff1f2; color: var(--gh-danger); }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--gh-muted); }
        .empty-state i { font-size: 42px; color: #cbd5e1; margin-bottom: 15px; }
        .empty-state h3 { color: var(--gh-navy); font-size: 18px; margin-bottom: 6px; }

        @media (max-width: 767px) {
            .sidebar { min-height: auto; }
            .main-content { padding: 18px; }
            .topbar, .orders-header { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <aside class="col-lg-2 col-md-3 sidebar">
            <a href="dashboard.php" class="brand">
                <div class="brand-icon"><i class="fas fa-fire"></i></div>
                Gas <span>Hub</span>
            </a>

            <div class="sidebar-title">Administration</div>

            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="user details.php" class="nav-link"><i class="fas fa-users"></i> User details</a>
                <a href="orders.php" class="nav-link active"><i class="fas fa-clipboard-list"></i> Orders</a>
                <a href="transaction.php" class="nav-link"><i class="fas fa-receipt"></i> Transactions</a>
                <a href="list of sellers.php" class="nav-link"><i class="fas fa-store"></i> Sellers</a>
                <a href="list of bottles.php" class="nav-link"><i class="fas fa-gas-pump"></i> Gas bottles</a>
                <a href="logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="col-lg-10 col-md-9 main-content">

            <div class="topbar">
                <div>
                    <h1>Orders</h1>
                    <div class="welcome">Pending, accepted, and rejected orders across the marketplace</div>
                </div>
                <div class="order-count"><?= number_format($totalOrders) ?> Total</div>
            </div>

            <div class="filter-pills">
                <a href="orders.php" class="filter-pill <?= $filter === '' ? 'active' : '' ?>">All (<?= $totalOrders ?>)</a>
                <a href="orders.php?status=pending" class="filter-pill <?= $filter === 'pending' ? 'active' : '' ?>">Pending (<?= $counts['pending'] ?>)</a>
                <a href="orders.php?status=accepted" class="filter-pill <?= $filter === 'accepted' ? 'active' : '' ?>">Accepted (<?= $counts['accepted'] ?>)</a>
                <a href="orders.php?status=rejected" class="filter-pill <?= $filter === 'rejected' ? 'active' : '' ?>">Rejected (<?= $counts['rejected'] ?>)</a>
            </div>

            <div class="orders-card">

                <div class="orders-header">
                    <h2><i class="fas fa-clipboard-list me-2"></i> Order List</h2>
                    <div class="order-count"><?= number_format(count($orders)) ?> shown</div>
                </div>

                <?php if (count($orders) > 0): ?>
                    <div class="table-wrapper">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Bottle</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Seller</th>
                                    <th>Buyer</th>
                                    <th>Status</th>
                                    <th>Placed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                    <?php $badge = $statusBadges[$o['status']] ?? ['bg-pending', e($o['status'])]; ?>
                                    <tr>
                                        <td><span class="order-id">#<?= (int)$o['id'] ?></span></td>
                                        <td><?= e($o['bottle_name']) ?></td>
                                        <td><?= (int)$o['quantity'] ?></td>
                                        <td><?= number_format((float)$o['price'], 0) ?> FCFA</td>
                                        <td><?= e($o['seller_name']) ?></td>
                                        <td><?= e($o['buyer_first'] . ' ' . $o['buyer_last']) ?></td>
                                        <td><span class="status-badge <?= e($badge[0]) ?>"><?= e($badge[1]) ?></span></td>
                                        <td><?= e(date('d M Y, H:i', strtotime($o['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No orders found</h3>
                        <p class="mb-0">
                            <?= $filter !== '' ? 'No orders with this status yet.' : 'No orders have been placed yet.' ?>
                        </p>
                    </div>
                <?php endif; ?>

            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>