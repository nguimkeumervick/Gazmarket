
<?php
session_start();
require_once "config.php";

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS PROTECTION
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['username']) || $_SESSION['username'] === '') {
    header("Location: index.php");
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
| DATABASE CHECK
|--------------------------------------------------------------------------
*/
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

$conn->set_charset("utf8mb4");

/*
|--------------------------------------------------------------------------
| GET SELLER COUNT
|--------------------------------------------------------------------------
*/
$totalSellers = 0;
$countQuery = "SELECT COUNT(*) AS total FROM seller";
$countResult = $conn->query($countQuery);

if ($countResult) {
    $countRow = $countResult->fetch_assoc();
    $totalSellers = (int)$countRow['total'];
}

/*
|--------------------------------------------------------------------------
| GET SELLERS
|--------------------------------------------------------------------------
*/
$sellers = [];
$sellerQuery = "SELECT id, name, email, phone FROM seller ORDER BY id ASC";
$sellerResult = $conn->query($sellerQuery);

if ($sellerResult) {
    while ($row = $sellerResult->fetch_assoc()) {
        $sellers[] = $row;
    }
} else {
    error_log("Gas Hub seller query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sellers · Gas Hub Admin</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="./font/css/all.css">

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

        .sidebar .nav-link i {
            width: 24px;
            margin-right: 7px;
        }

        .sidebar .nav-link:hover {
            background: rgba(244, 180, 26, .12);
            color: #fff;
        }

        .sidebar .nav-link.active {
            background: var(--gh-gold);
            color: var(--gh-navy);
            font-weight: 700;
        }

        .logout-link {
            color: #ffb4b4 !important;
            margin-top: 25px;
        }

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

        .welcome {
            color: var(--gh-muted);
            font-size: 14px;
            margin-top: 3px;
        }

        .admin-badge {
            background: #fff7df;
            color: #8a6500;
            padding: 8px 13px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .summary-card {
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(7, 26, 47, .04);
        }

        .summary-label {
            color: var(--gh-muted);
            font-size: 13px;
            margin-bottom: 7px;
        }

        .summary-value {
            color: var(--gh-navy);
            font-size: 27px;
            font-weight: 700;
            margin: 0;
        }

        .summary-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #fff5d6;
            color: var(--gh-gold-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .seller-card {
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(7, 26, 47, .05);
        }

        .seller-header {
            padding: 20px 22px;
            border-bottom: 1px solid var(--gh-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .seller-header h2 {
            margin: 0;
            color: var(--gh-navy);
            font-size: 19px;
            font-weight: 700;
        }

        .seller-count {
            background: #fff7df;
            color: #8a6500;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .seller-table {
            width: 100%;
            min-width: 800px;
            margin: 0;
            border-collapse: collapse;
        }

        .seller-table thead th {
            background: var(--gh-navy);
            color: #fff;
            padding: 15px;
            border: none;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        .seller-table tbody td {
            padding: 14px 15px;
            border-bottom: 1px solid #edf0f3;
            color: #374151;
            font-size: 13px;
            vertical-align: middle;
        }

        .seller-table tbody tr:last-child td { border-bottom: none; }
        .seller-table tbody tr:hover { background: #fafbfc; }

        .seller-id, .seller-name {
            color: var(--gh-navy);
            font-weight: 700;
        }

        .delete-btn {
            display: inline-block;
            background: #fff1f2;
            color: var(--gh-danger);
            border: 1px solid #fecdd3;
            border-radius: 8px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 700;
            transition: all .2s ease;
            white-space: nowrap;
        }

        .delete-btn:hover {
            background: var(--gh-danger);
            color: #fff;
            border-color: var(--gh-danger);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gh-muted);
        }

        .empty-state i {
            font-size: 42px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: var(--gh-navy);
            font-size: 18px;
            margin-bottom: 6px;
        }

        @media (max-width: 767px) {
            .sidebar { min-height: auto; }
            .main-content { padding: 18px; }
            .topbar, .seller-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <aside class="col-lg-2 col-md-3 sidebar">
            <a href="dashboard.php" class="brand">
                <div class="brand-icon">
                    <i class="fas fa-fire"></i>
                </div>
                Gas <span>Hub</span>
            </a>

            <div class="sidebar-title">Administration</div>

            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="user details.php" class="nav-link">
                    <i class="fas fa-users"></i> User details
                </a>
                <a href="transaction.php" class="nav-link">
                    <i class="fas fa-receipt"></i> Transactions
                </a>
                <a href="list of sellers.php" class="nav-link active">
                    <i class="fas fa-store"></i> Sellers
                </a>
                <a href="list of bottles.php" class="nav-link">
                    <i class="fas fa-gas-pump"></i> Gas bottles
                </a>
                <a href="logout.php" class="nav-link logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="col-lg-10 col-md-9 main-content">
            <div class="topbar">
                <div>
                    <h1>Sellers</h1>
                    <div class="welcome">Manage Gas Hub sellers</div>
                </div>
                <div class="admin-badge">
                    <i class="fas fa-user-shield"></i> Administrator
                </div>
            </div>

            <!-- SUMMARY -->
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="summary-label">Total Registered Sellers</div>
                        <p class="summary-value"><?= number_format($totalSellers); ?></p>
                    </div>
                    <div class="summary-icon">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>

            <!-- SELLER TABLE -->
            <div class="seller-card">
                <div class="seller-header">
                    <h2>
                        <i class="fas fa-users me-2"></i> Seller Accounts
                    </h2>
                    <div class="seller-count">
                        <?= number_format($totalSellers); ?> Seller<?= $totalSellers == 1 ? '' : 's'; ?>
                    </div>
                </div>

                <?php if (count($sellers) > 0): ?>
                    <div class="table-wrapper">
                        <table class="seller-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Operation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sellers as $row): ?>
                                    <tr>
                                        <td>
                                            <span class="seller-id">#<?= (int)$row['id']; ?></span>
                                        </td>
                                        <td>
                                            <span class="seller-name">
                                                <?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="seller-email">
                                                <?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($row['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td>
                                            <a href="delete3.php?delete=<?= (int)$row['id']; ?>&csrf_token=<?= urlencode($_SESSION['admin_csrf_token']); ?>"
                                               class="delete-btn"
                                               onclick="return confirm('Are you sure you want to delete this seller? This action cannot be undone.');">
                                                <i class="fas fa-trash-alt me-1"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-store"></i>
                        <h3>No sellers found</h3>
                        <p class="mb-0">There are currently no registered sellers in Gas Hub.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>