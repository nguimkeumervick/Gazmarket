<?php

session_start();

require_once 'config.php';


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS PROTECTION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['username']) ||
    $_SESSION['username'] === ''
) {
    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
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
| GET TOTAL TRANSACTIONS
|--------------------------------------------------------------------------
*/

$totalTransactions = 0;

$countQuery = "
    SELECT COUNT(*) AS total
    FROM deliver
";

$countResult = $conn->query($countQuery);

if ($countResult) {

    $countRow = $countResult->fetch_assoc();

    $totalTransactions = (int)$countRow['total'];
}


/*
|--------------------------------------------------------------------------
| GET TOTAL PAID SALES AND COMMISSION
|--------------------------------------------------------------------------
*/

$totalSales = 0;
$totalCommission = 0;

$salesQuery = "
    SELECT
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN Amount ELSE 0 END), 0) AS total_sales,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN commission ELSE 0 END), 0) AS total_commission
    FROM deliver
";

$salesResult = $conn->query($salesQuery);

if ($salesResult) {

    $salesRow = $salesResult->fetch_assoc();

    $totalSales = (float)$salesRow['total_sales'];
    $totalCommission = (float)$salesRow['total_commission'];
}


/*
|--------------------------------------------------------------------------
| GET TRANSACTIONS
|--------------------------------------------------------------------------
|
| ASCENDING ID ORDER
|
| 1
| 2
| 3
| 4
| 5
| ...
|
|--------------------------------------------------------------------------
*/

$transactions = [];

$transactionQuery = "
    SELECT
        id,
        Name,
        Address,
        City,
        phone,
        email,
        bottle_name,
        Gas_size,
        Quantity,
        Amount,
        payment_status,
        commission,
        seller_amount,
        transaction_id
    FROM deliver
    ORDER BY id ASC
";


$transactionResult = $conn->query($transactionQuery);


if ($transactionResult) {

    while ($row = $transactionResult->fetch_assoc()) {

        $transactions[] = $row;

    }

} else {

    error_log(
        "Gas Hub transaction query failed: " .
        $conn->error
    );

}


function gh_status_badge_class($status)
{
    switch ($status) {
        case 'paid':
            return 'badge-paid';
        case 'failed':
            return 'badge-failed';
        default:
            return 'badge-pending';
    }
}


function gh_status_label($status)
{
    switch ($status) {
        case 'paid':
            return 'Paid';
        case 'failed':
            return 'Failed';
        default:
            return 'Pending';
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Transactions · Gas Hub Admin</title>


    <!-- Bootstrap -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="../font/css/all.css"
    >


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

            --gh-danger: #b42318;

        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background:
                var(--gh-bg);

            color:
                var(--gh-text);

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        a,
        a:hover,
        a:focus,
        a:active,
        a:visited {

            text-decoration: none !important;

            outline: none !important;

        }


        .sidebar {

            min-height: 100vh;

            background:
                var(--gh-navy);

            padding:
                25px 18px;

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


        .brand:hover {

            color: #fff;

        }


        .brand-icon {

            width: 42px;

            height: 42px;

            border-radius: 12px;

            background:
                var(--gh-gold);

            color:
                var(--gh-navy);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

        }


        .brand span {

            color:
                var(--gh-gold);

        }


        .sidebar-title {

            color:
                #8fa3b8;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin:
                0 0 12px 12px;

        }


        .sidebar .nav-link {

            color:
                #cbd5e1;

            padding:
                12px 14px;

            margin-bottom:
                5px;

            border-radius:
                10px;

            font-size:
                14px;

            transition:
                all .2s ease;

        }


        .sidebar .nav-link i {

            width: 24px;

            margin-right: 7px;

        }


        .sidebar .nav-link:hover {

            background:
                rgba(244, 180, 26, .12);

            color:
                #fff;

        }


        .sidebar .nav-link.active {

            background:
                var(--gh-gold);

            color:
                var(--gh-navy);

            font-weight:
                700;

        }


        .logout-link {

            color:
                #ffb4b4 !important;

            margin-top:
                25px;

        }


        .logout-link:hover {

            background:
                rgba(220, 53, 69, .15) !important;

            color:
                #fff !important;

        }


        .main-content {

            padding:
                28px;

        }


        .topbar {

            background:
                #fff;

            border:
                1px solid var(--gh-border);

            border-radius:
                16px;

            padding:
                18px 22px;

            margin-bottom:
                25px;

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            box-shadow:
                0 5px 20px
                rgba(7, 26, 47, .04);

        }


        .topbar h1 {

            margin:
                0;

            font-size:
                26px;

            font-weight:
                700;

            color:
                var(--gh-navy);

        }


        .welcome {

            color:
                var(--gh-muted);

            font-size:
                14px;

            margin-top:
                3px;

        }


        .admin-badge {

            background:
                #fff7df;

            color:
                #8a6500;

            padding:
                8px 13px;

            border-radius:
                20px;

            font-size:
                13px;

            font-weight:
                600;

        }


        .summary-card {

            background:
                #fff;

            border:
                1px solid var(--gh-border);

            border-radius:
                16px;

            padding:
                20px;

            box-shadow:
                0 5px 20px
                rgba(7, 26, 47, .04);

            height:
                100%;

        }


        .summary-label {

            color:
                var(--gh-muted);

            font-size:
                13px;

            margin-bottom:
                7px;

        }


        .summary-value {

            color:
                var(--gh-navy);

            font-size:
                26px;

            font-weight:
                700;

            margin:
                0;

        }


        .summary-icon {

            width:
                44px;

            height:
                44px;

            border-radius:
                12px;

            background:
                #fff5d6;

            color:
                var(--gh-gold-dark);

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                18px;

        }


        .transactions-card {

            background:
                #fff;

            border:
                1px solid var(--gh-border);

            border-radius:
                16px;

            overflow:
                hidden;

            box-shadow:
                0 5px 20px
                rgba(7, 26, 47, .05);

        }


        .transactions-header {

            padding:
                20px 22px;

            border-bottom:
                1px solid var(--gh-border);

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

        }


        .transactions-header h2 {

            margin:
                0;

            color:
                var(--gh-navy);

            font-size:
                19px;

            font-weight:
                700;

        }


        .transaction-count {

            background:
                #fff7df;

            color:
                #8a6500;

            padding:
                7px 12px;

            border-radius:
                20px;

            font-size:
                12px;

            font-weight:
                700;

        }


        .table-wrapper {

            width:
                100%;

            overflow-x:
                auto;

        }


        .transactions-table {

            width:
                100%;

            min-width:
                1350px;

            margin:
                0;

            border-collapse:
                collapse;

        }


        .transactions-table thead th {

            background:
                var(--gh-navy);

            color:
                #fff;

            padding:
                15px 14px;

            border:
                none;

            font-size:
                11px;

            text-transform:
                uppercase;

            letter-spacing:
                .5px;

            white-space:
                nowrap;

        }


        .transactions-table tbody td {

            padding:
                14px;

            border-bottom:
                1px solid #edf0f3;

            color:
                #374151;

            font-size:
                13px;

            vertical-align:
                middle;

        }


        .transactions-table tbody tr:last-child td {

            border-bottom:
                none;

        }


        .transactions-table tbody tr:hover {

            background:
                #fafbfc;

        }


        .transaction-id {

            color:
                var(--gh-navy);

            font-weight:
                700;

        }


        .customer-name {

            color:
                var(--gh-navy);

            font-weight:
                600;

        }


        .amount {

            color:
                #166534;

            font-weight:
                700;

            white-space:
                nowrap;

        }


        .commission-amount {

            color:
                var(--gh-gold-dark);

            font-weight:
                700;

            white-space:
                nowrap;

        }


        .status-badge {

            display:
                inline-block;

            padding:
                5px 11px;

            border-radius:
                20px;

            font-size:
                11px;

            font-weight:
                700;

            white-space:
                nowrap;

        }


        .badge-paid {

            background:
                #ecfdf3;

            color:
                #027a48;

        }


        .badge-pending {

            background:
                #fff7df;

            color:
                #8a6500;

        }


        .badge-failed {

            background:
                #fff1f2;

            color:
                var(--gh-danger);

        }


        .delete-btn {

            display:
                inline-block;

            background:
                #fff1f2;

            color:
                var(--gh-danger);

            border:
                1px solid #fecdd3;

            border-radius:
                8px;

            padding:
                7px 11px;

            font-size:
                12px;

            font-weight:
                700;

            transition:
                all .2s ease;

            white-space:
                nowrap;

        }


        .delete-btn:hover {

            background:
                var(--gh-danger);

            color:
                #fff;

            border-color:
                var(--gh-danger);

        }


        .empty-state {

            text-align:
                center;

            padding:
                60px 20px;

            color:
                var(--gh-muted);

        }


        .empty-state i {

            font-size:
                42px;

            color:
                #cbd5e1;

            margin-bottom:
                15px;

        }


        .empty-state h3 {

            color:
                var(--gh-navy);

            font-size:
                18px;

            margin-bottom:
                6px;

        }


        @media (max-width: 767px) {

            .sidebar {

                min-height:
                    auto;

            }


            .main-content {

                padding:
                    18px;

            }


            .topbar {

                flex-direction:
                    column;

                align-items:
                    flex-start;

                gap:
                    12px;

            }


            .transactions-header {

                flex-direction:
                    column;

                align-items:
                    flex-start;

                gap:
                    12px;

            }

        }

    </style>

</head>


<body>


<div class="container-fluid">

    <div class="row">


        <aside class="col-lg-2 col-md-3 sidebar">


            <a
                href="dashboard.php"
                class="brand"
            >

                <div class="brand-icon">

                    <i class="fas fa-fire"></i>

                </div>

                Gas <span>Hub</span>

            </a>


            <div class="sidebar-title">

                Administration

            </div>


            <nav class="nav flex-column">


                <a
                    href="dashboard.php"
                    class="nav-link"
                >

                    <i class="fas fa-chart-line"></i>

                    Dashboard

                </a>


                <a
                    href="user details.php"
                    class="nav-link"
                >

                    <i class="fas fa-users"></i>

                    User details

                </a>


                <a
                    href="transaction.php"
                    class="nav-link active"
                >

                    <i class="fas fa-receipt"></i>

                    Transactions

                </a>


                <a
                    href="list of sellers.php"
                    class="nav-link"
                >

                    <i class="fas fa-store"></i>

                    Sellers

                </a>


                <a
                    href="list of bottles.php"
                    class="nav-link"
                >

                    <i class="fas fa-gas-pump"></i>

                    Gas bottles

                </a>


                <a
                    href="logout.php"
                    class="nav-link logout-link"
                >

                    <i class="fas fa-sign-out-alt"></i>

                    Logout

                </a>


            </nav>


        </aside>


        <main class="col-lg-10 col-md-9 main-content">


            <div class="topbar">


                <div>

                    <h1>
                        Transactions
                    </h1>


                    <div class="welcome">

                        Gas Hub transaction records

                    </div>

                </div>


                <div class="admin-badge">

                    <i class="fas fa-user-shield"></i>

                    Administrator

                </div>


            </div>


            <div class="row g-4 mb-4">


                <div class="col-md-4 mb-3">

                    <div class="summary-card">


                        <div class="d-flex justify-content-between align-items-center">


                            <div>

                                <div class="summary-label">

                                    Total Transactions

                                </div>


                                <p class="summary-value">

                                    <?= number_format(
                                        $totalTransactions
                                    ); ?>

                                </p>

                            </div>


                            <div class="summary-icon">

                                <i class="fas fa-receipt"></i>

                            </div>


                        </div>


                    </div>

                </div>


                <div class="col-md-4 mb-3">

                    <div class="summary-card">


                        <div class="d-flex justify-content-between align-items-center">


                            <div>

                                <div class="summary-label">

                                    Total Paid Sales

                                </div>


                                <p class="summary-value">

                                    <?= number_format(
                                        $totalSales,
                                        0
                                    ); ?>

                                    FCFA

                                </p>

                            </div>


                            <div class="summary-icon">

                                <i class="fas fa-money-bill-wave"></i>

                            </div>


                        </div>


                    </div>

                </div>


                <div class="col-md-4 mb-3">

                    <div class="summary-card">


                        <div class="d-flex justify-content-between align-items-center">


                            <div>

                                <div class="summary-label">

                                    Gas Hub Commission Earned

                                </div>


                                <p class="summary-value">

                                    <?= number_format(
                                        $totalCommission,
                                        0
                                    ); ?>

                                    FCFA

                                </p>

                            </div>


                            <div class="summary-icon">

                                <i class="fas fa-percentage"></i>

                            </div>


                        </div>


                    </div>

                </div>


            </div>


            <div class="transactions-card">


                <div class="transactions-header">


                    <h2>

                        <i class="fas fa-exchange-alt mr-2"></i>

                        Transaction Details

                    </h2>


                    <div class="transaction-count">

                        <?= number_format(
                            $totalTransactions
                        ); ?>

                        Transaction<?= $totalTransactions == 1 ? '' : 's'; ?>

                    </div>


                </div>


                <?php if (count($transactions) > 0): ?>


                    <div class="table-wrapper">


                        <table class="transactions-table">


                            <thead>

                                <tr>

                                    <th>
                                        ID
                                    </th>

                                    <th>
                                        Name
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Address
                                    </th>

                                    <th>
                                        City
                                    </th>

                                    <th>
                                        Phone
                                    </th>

                                    <th>
                                        Bottle Name
                                    </th>

                                    <th>
                                        Gas Size
                                    </th>

                                    <th>
                                        Quantity
                                    </th>

                                    <th>
                                        Amount
                                    </th>

                                    <th>
                                        Commission
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Operation
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach ($transactions as $row): ?>


                                    <tr>


                                        <td>

                                            <span class="transaction-id">

                                                #

                                                <?= (int)$row['id']; ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="customer-name">

                                                <?= htmlspecialchars(
                                                    $row['Name'] ?? '',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $row['email'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $row['Address'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $row['City'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $row['phone'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $row['bottle_name'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $row['Gas_size'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>


                                        <td>

                                            <?= (int)$row['Quantity']; ?>

                                        </td>


                                        <td>

                                            <span class="amount">

                                                <?= number_format(
                                                    (float)$row['Amount'],
                                                    0
                                                ); ?>

                                                FCFA

                                            </span>

                                        </td>


                                        <td>

                                            <span class="commission-amount">

                                                <?= $row['commission'] !== null
                                                    ? number_format((float)$row['commission'], 0) . ' FCFA'
                                                    : '—'; ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?php
                                                $status = $row['payment_status'] ?? 'pending';
                                            ?>

                                            <span class="status-badge <?= gh_status_badge_class($status) ?>">

                                                <?= gh_status_label($status) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <a
                                                href="delete2.php?delete=<?= (int)$row['id']; ?>&csrf_token=<?= urlencode($_SESSION['admin_csrf_token']); ?>"
                                                class="delete-btn"
                                                onclick="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.');"
                                            >

                                                <i class="fas fa-trash-alt mr-1"></i>

                                                Delete

                                            </a>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="empty-state">


                        <i class="fas fa-receipt"></i>


                        <h3>
                            No transactions found
                        </h3>


                        <p class="mb-0">

                            There are currently no transaction
                            records in Gas Hub.

                        </p>


                    </div>


                <?php endif; ?>


            </div>


        </main>


    </div>

</div>


<!-- Bootstrap JavaScript -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>