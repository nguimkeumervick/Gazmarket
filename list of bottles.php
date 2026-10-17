
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


// =====================================================
// GET ALL BOTTLE LISTINGS
// =====================================================

$check_database = "
    SELECT id, name, price, location, img, description
    FROM element
    ORDER BY id ASC
";

$query = mysqli_query($conn, $check_database);

if (!$query) {
    error_log("Gas Hub bottle listing query failed: " . mysqli_error($conn));
    die("Unable to retrieve bottle listings.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gas Bottles · Gas Hub Admin</title>

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

        .admin-badge {
            background: #fff7df;
            color: #8a6500;
            padding: 8px 13px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .listings-card {
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(7, 26, 47, .05);
        }

        .listings-header {
            padding: 20px 22px;
            border-bottom: 1px solid var(--gh-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .listings-header h2 {
            margin: 0;
            color: var(--gh-navy);
            font-size: 19px;
            font-weight: 700;
        }

        .listing-count {
            background: #fff7df;
            color: #8a6500;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .table-wrapper { width: 100%; overflow-x: auto; }

        .bottles-table {
            width: 100%;
            min-width: 1000px;
            margin: 0;
            border-collapse: collapse;
        }

        .bottles-table thead th {
            background: var(--gh-navy);
            color: #fff;
            padding: 15px 14px;
            border: none;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        .bottles-table tbody td {
            padding: 14px;
            border-bottom: 1px solid #edf0f3;
            color: #374151;
            font-size: 13px;
            vertical-align: middle;
        }

        .bottles-table tbody tr:last-child td { border-bottom: none; }
        .bottles-table tbody tr:hover { background: #fafbfc; }

        .bottle-id { color: var(--gh-navy); font-weight: 700; }
        .bottle-name { color: var(--gh-navy); font-weight: 600; }

        .price { font-weight: 700; color: #166534; white-space: nowrap; }

        .bottle-image {
            width: 80px;
            height: 65px;
            object-fit: cover;
            border-radius: 7px;
            border: 1px solid var(--gh-border);
            background-color: #f1f1f1;
        }

        .no-image {
            width: 80px;
            height: 65px;
            background: #e9ecef;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
            font-size: 11px;
            text-align: center;
        }

        .description { max-width: 280px; min-width: 180px; }

        .action-buttons { white-space: nowrap; }

        .edit-btn {
            display: inline-block;
            background: #eef3ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 700;
            margin-right: 6px;
        }

        .edit-btn:hover { background: #1d4ed8; color: #fff; }

        .delete-btn {
            display: inline-block;
            background: #fff1f2;
            color: var(--gh-danger);
            border: 1px solid #fecdd3;
            border-radius: 8px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 700;
        }

        .delete-btn:hover {
            background: var(--gh-danger);
            color: #fff;
            border-color: var(--gh-danger);
        }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--gh-muted); }
        .empty-state i { font-size: 42px; color: #cbd5e1; margin-bottom: 15px; }
        .empty-state h3 { color: var(--gh-navy); font-size: 18px; margin-bottom: 6px; }

        @media (max-width: 767px) {
            .sidebar { min-height: auto; }
            .main-content { padding: 18px; }
            .topbar, .listings-header {
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

        <aside class="col-lg-2 col-md-3 sidebar">
            <a href="dashboard.php" class="brand">
                <div class="brand-icon"><i class="fas fa-fire"></i></div>
                Gas <span>Hub</span>
            </a>

            <div class="sidebar-title">Administration</div>

            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="user details.php" class="nav-link"><i class="fas fa-users"></i> User details</a>
                <a href="transaction.php" class="nav-link"><i class="fas fa-receipt"></i> Transactions</a>
                <a href="list of sellers.php" class="nav-link"><i class="fas fa-store"></i> Sellers</a>
                <a href="list of bottles.php" class="nav-link active"><i class="fas fa-gas-pump"></i> Gas bottles</a>
                <a href="logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="col-lg-10 col-md-9 main-content">

            <div class="topbar">
                <div>
                    <h1>Gas Bottles</h1>
                    <div class="welcome">Manage all listings on the marketplace</div>
                </div>
                <div class="admin-badge"><i class="fas fa-user-shield"></i> Administrator</div>
            </div>

            <div class="listings-card">

                <div class="listings-header">
                    <h2><i class="fas fa-gas-pump me-2"></i> Bottle Listings</h2>
                    <div class="listing-count">
                        <?= number_format(mysqli_num_rows($query)); ?> Listing<?= mysqli_num_rows($query) == 1 ? '' : 's'; ?>
                    </div>
                </div>

                <?php if (mysqli_num_rows($query) > 0): ?>

                    <div class="table-wrapper">
                        <table class="bottles-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Location</th>
                                    <th>Image</th>
                                    <th>Description</th>
                                    <th>Operation</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php while ($rows = mysqli_fetch_assoc($query)): ?>

                                    <tr>

                                        <td><span class="bottle-id">#<?= (int)$rows['id']; ?></span></td>

                                        <td><span class="bottle-name"><?= htmlspecialchars($rows['name'], ENT_QUOTES, 'UTF-8'); ?></span></td>

                                        <td class="price">
                                            <?= number_format((float)$rows['price'], 0, '.', ','); ?> FCFA
                                        </td>

                                        <td><?= htmlspecialchars($rows['location'], ENT_QUOTES, 'UTF-8'); ?></td>

                                        <td>
                                            <?php
                                            $image = trim($rows['img']);

                                            /*
                                             * Your admin panel is inside:
                                             *   project development/admin panel/
                                             * while images live in:
                                             *   project development/images/
                                             * so we need ../images/
                                             */

                                            if ($image !== '') {

                                                $image = str_replace('\\', '/', $image);
                                                $image = preg_replace('#^\./#', '', $image);

                                                if (strpos($image, 'images/') === 0) {
                                                    $imagePath = '../' . $image;
                                                } else {
                                                    $imagePath = '../images/' . $image;
                                                }

                                            } else {
                                                $imagePath = '';
                                            }

                                            if ($imagePath !== ''):
                                            ?>
                                                <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>"
                                                     alt="Gas bottle" class="bottle-image"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="no-image" style="display:none;">Image unavailable</div>
                                            <?php else: ?>
                                                <div class="no-image">No image</div>
                                            <?php endif; ?>
                                        </td>

                                        <td class="description">
                                            <?php
                                            $description = trim($rows['description']);
                                            if ($description !== '') {
                                                echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
                                            } else {
                                                echo '<span class="text-muted">No description</span>';
                                            }
                                            ?>
                                        </td>

                                        <td class="action-buttons">
                                            <a class="edit-btn" href="edit4.php?id=<?= urlencode($rows['id']); ?>&csrf_token=<?= urlencode($_SESSION['admin_csrf_token']); ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>

                                            <a class="delete-btn"
                                               href="delete4.php?delete=<?= urlencode($rows['id']); ?>&csrf_token=<?= urlencode($_SESSION['admin_csrf_token']); ?>"
                                               onclick="return confirm('Are you sure you want to delete this bottle listing?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>
                        </table>
                    </div>

                <?php else: ?>

                    <div class="empty-state">
                        <i class="fas fa-gas-pump"></i>
                        <h3>No gas bottles found</h3>
                        <p class="mb-0">There are currently no bottle listings in the marketplace.</p>
                    </div>

                <?php endif; ?>

            </div>

        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>