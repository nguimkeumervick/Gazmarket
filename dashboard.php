
<?php

session_start();

require_once 'config.php';


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS PROTECTION
|--------------------------------------------------------------------------
| index.php creates $_SESSION['username'], so the dashboard checks the
| same session variable.
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['username']) || $_SESSION['username'] === '') {
    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

$conn->set_charset("utf8mb4");


/*
|--------------------------------------------------------------------------
| ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

$adminUsername = $_SESSION['username'];


/*
|--------------------------------------------------------------------------
| HELPER: single COUNT value
|--------------------------------------------------------------------------
*/

function gh_count($conn, $table)
{
    $allowedTables = ['signup', 'seller', 'deliver', 'element'];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");

    if ($result) {
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }

    error_log("Gas Hub dashboard count failed for $table: " . $conn->error);
    return 0;
}

$userCount = gh_count($conn, 'signup');
$sellerCount = gh_count($conn, 'seller');
$transactionCount = gh_count($conn, 'deliver');
$listingCount = gh_count($conn, 'element');


/*
|--------------------------------------------------------------------------
| SALES (same logic as transaction.php: only PAID orders are counted)
|--------------------------------------------------------------------------
*/

$totalSales = 0;
$totalCommission = 0;
$unpaidOrders = 0;

$salesQuery = "
    SELECT
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN Amount ELSE 0 END), 0) AS total_sales,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN commission ELSE 0 END), 0) AS total_commission,
        COALESCE(SUM(CASE WHEN payment_status IS NULL OR payment_status <> 'paid' THEN 1 ELSE 0 END), 0) AS unpaid_orders
    FROM deliver
";

$salesResult = $conn->query($salesQuery);

if ($salesResult) {

    $salesRow = $salesResult->fetch_assoc();

    $totalSales = (float)$salesRow['total_sales'];
    $totalCommission = (float)$salesRow['total_commission'];
    $unpaidOrders = (int)$salesRow['unpaid_orders'];

} else {

    error_log("Gas Hub dashboard sales query failed: " . $conn->error);
}


/*
|--------------------------------------------------------------------------
| AVERAGE GAS BOTTLE LISTING PRICE
|--------------------------------------------------------------------------
*/

$averagePrice = 0;

$avgResult = $conn->query("
    SELECT COALESCE(AVG(price), 0) AS average_price
    FROM element
");

if ($avgResult) {

    $avgRow = $avgResult->fetch_assoc();

    $averagePrice = (float)$avgRow['average_price'];
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard · Gas Hub Admin</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="../font/css/all.css">

    <style>

        :root {
            --gh-navy: #071a2f;
            --gh-navy-light: #0d2a47;
            --gh-gold: #f4b41a;
            --gh-gold-dark: #d99d00;
            --gh-bg: #f5f7fa;
            --gh-white: #ffffff;
            --gh-text: #172033;
            --gh-muted: #6b7280;
            --gh-border: #e5e7eb;
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
            background: var(--gh-white);
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            padding: 18px 22px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 20px rgba(7, 26, 47, .04);
        }

        .topbar h1 { margin: 0; font-size: 26px; font-weight: 700; }

        .welcome { color: var(--gh-muted); font-size: 14px; margin-top: 3px; }

        .admin-badge {
            background: #fff7df;
            color: #8a6500;
            padding: 8px 13px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .stat-card {
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 5px 20px rgba(7, 26, 47, .04);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(7, 26, 47, .08);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff5d6;
            color: var(--gh-gold-dark);
            font-size: 19px;
        }

        .stat-label { color: var(--gh-muted); font-size: 13px; margin-bottom: 5px; }

        .stat-value { color: var(--gh-navy); font-size: 27px; font-weight: 700; margin: 0; }

        .section-card {
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(7, 26, 47, .04);
            overflow: hidden;
        }

        .section-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--gh-border);
            font-weight: 700;
            color: var(--gh-navy);
        }

        .section-body { padding: 20px; }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 0;
            border-bottom: 1px solid #f0f1f3;
        }

        .info-row:last-child { border-bottom: none; }

        .info-label { color: var(--gh-muted); font-size: 14px; }

        .info-value { color: var(--gh-navy); font-weight: 700; }

        .sales-box {
            background: var(--gh-navy);
            color: white;
            border-radius: 16px;
            padding: 24px;
            height: 100%;
        }

        .sales-box .label { color: #aebdca; font-size: 13px; }

        .sales-box .amount {
            color: var(--gh-gold);
            font-size: 30px;
            font-weight: 700;
            margin-top: 8px;
        }

        .sales-box p { color: #cbd5e1; font-size: 14px; }

        .sales-box .commission-line {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, .15);
        }

        .sales-box .commission-amount {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
        }

        @media (max-width: 767px) {
            .sidebar { min-height: auto; }
            .main-content { padding: 18px; }
            .topbar {
                align-items: flex-start;
                gap: 15px;
                flex-direction: column;
            }
            .stat-value { font-size: 24px; }
        }

    </style>

</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <aside class="col-lg-2 col-md-3 sidebar">

            <a href="dashboard.php" class="brand">
                <div class="brand-icon"><i class="fas fa-fire"></i></div>
                Gas <span>Hub</span>
            </a>

            <div class="sidebar-title">Administration</div>

            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="user details.php" class="nav-link"><i class="fas fa-users"></i> User details</a>
                <a href="transaction.php" class="nav-link"><i class="fas fa-receipt"></i> Transactions</a>
                <a href="list of sellers.php" class="nav-link"><i class="fas fa-store"></i> Sellers</a>
                <a href="list of bottles.php" class="nav-link"><i class="fas fa-gas-pump"></i> Gas bottles</a>
                <a href="logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>

        </aside>

        <!-- MAIN CONTENT -->
        <main class="col-lg-10 col-md-9 main-content">

            <div class="topbar">
                <div>
                    <h1>Dashboard</h1>
                    <div class="welcome">
                        Welcome back, <?= htmlspecialchars((string)$adminUsername, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
                <div class="admin-badge">
                    <i class="fas fa-user-shield"></i> Administrator
                </div>
            </div>

            <!-- STATISTICS -->
            <div class="row g-4 mb-4">

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <div class="stat-label">Registered Users</div>
                                <p class="stat-value"><?= number_format($userCount); ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <div class="stat-label">Total Sellers</div>
                                <p class="stat-value"><?= number_format($sellerCount); ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-store"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <div class="stat-label">Gas Listings</div>
                                <p class="stat-value"><?= number_format($listingCount); ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-gas-pump"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <div class="stat-label">Transactions</div>
                                <p class="stat-value"><?= number_format($transactionCount); ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- SALES + MARKET OVERVIEW -->
            <div class="row g-4">

                <div class="col-lg-5 mb-4">
                    <div class="sales-box">

                        <div class="label">Total Paid Sales</div>

                        <div class="amount"><?= number_format($totalSales, 0); ?> FCFA</div>

                        <p class="mt-3 mb-0">
                            Based on <strong>paid</strong> orders in the <strong>deliver</strong> table.
                        </p>

                        <div class="commission-line">
                            <div class="label">Gas Hub Commission Earned</div>
                            <div class="commission-amount"><?= number_format($totalCommission, 0); ?> FCFA</div>
                        </div>

                    </div>
                </div>

                <div class="col-lg-7 mb-4">
                    <div class="section-card">

                        <div class="section-header">
                            <i class="fas fa-chart-bar me-2"></i> Market Overview
                        </div>

                        <div class="section-body">

                            <div class="info-row">
                                <span class="info-label">Registered users</span>
                                <span class="info-value"><?= number_format($userCount); ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Sellers</span>
                                <span class="info-value"><?= number_format($sellerCount); ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Gas bottle listings</span>
                                <span class="info-value"><?= number_format($listingCount); ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Transactions</span>
                                <span class="info-value"><?= number_format($transactionCount); ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Unpaid or pending orders</span>
                                <span class="info-value"><?= number_format($unpaidOrders); ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Average listing price</span>
                                <span class="info-value"><?= number_format($averagePrice, 0); ?> FCFA</span>
                            </div>

                        </div>

                    </div>
                </div>

            </div>

        </main>

    </div>
</div>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>