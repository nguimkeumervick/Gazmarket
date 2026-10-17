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

$csrfToken = $_SESSION['admin_csrf_token'];


/*
|--------------------------------------------------------------------------
| DATABASE CHECK
|--------------------------------------------------------------------------
*/

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection is not available.');
}

$conn->set_charset('utf8mb4');


/*
|--------------------------------------------------------------------------
| MESSAGES (kept in the session so they survive the redirect)
|--------------------------------------------------------------------------
*/

$error = '';
$success = '';

if (!empty($_SESSION['users_flash_success'])) {
    $success = $_SESSION['users_flash_success'];
    unset($_SESSION['users_flash_success']);
}

if (!empty($_SESSION['users_flash_error'])) {
    $error = $_SESSION['users_flash_error'];
    unset($_SESSION['users_flash_error']);
}


/*
|--------------------------------------------------------------------------
| DELETE USER (POST, then redirect so F5 does not resend the delete)
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {

    $submittedToken = (string)($_POST['csrf_token'] ?? '');

    if ($submittedToken === '' || !hash_equals($csrfToken, $submittedToken)) {

        $_SESSION['users_flash_error'] = 'Invalid security token. Please try again.';

    } else {

        $userId = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);

        if ($userId === false || $userId <= 0) {

            $_SESSION['users_flash_error'] = 'Invalid user selected.';

        } else {

            $deleteStmt = $conn->prepare("DELETE FROM signup WHERE id = ? LIMIT 1");

            if (!$deleteStmt) {

                error_log('Gas Hub user delete prepare failed: ' . $conn->error);
                $_SESSION['users_flash_error'] = 'Unable to delete the user at the moment.';

            } else {

                $deleteStmt->bind_param('i', $userId);

                if ($deleteStmt->execute()) {

                    if ($deleteStmt->affected_rows > 0) {
                        $_SESSION['users_flash_success'] = 'User deleted successfully.';
                    } else {
                        $_SESSION['users_flash_error'] = 'User could not be found.';
                    }

                } else {

                    error_log('Gas Hub user delete failed: ' . $deleteStmt->error);
                    $_SESSION['users_flash_error'] = 'Unable to delete the user at the moment.';
                }

                $deleteStmt->close();
            }
        }
    }

    header('Location: user%20details.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| GET TOTAL NUMBER OF USERS
|--------------------------------------------------------------------------
*/

$totalUsers = 0;

$countResult = $conn->query("SELECT COUNT(*) AS total FROM signup");

if ($countResult) {
    $countRow = $countResult->fetch_assoc();
    $totalUsers = (int)$countRow['total'];
}


/*
|--------------------------------------------------------------------------
| GET USERS (ascending ID order)
|--------------------------------------------------------------------------
*/

$users = [];

$userResult = $conn->query("
    SELECT id, firstName, lastName, email, telephone
    FROM signup
    ORDER BY id ASC
");

if ($userResult) {

    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }

} else {

    error_log('Gas Hub user details query failed: ' . $conn->error);
    $error = 'Unable to load the registered users.';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details · Gas Hub Admin</title>

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

        .alert-box {
            border-radius: 12px;
            padding: 13px 16px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success-box {
            background: #ecfdf3;
            border: 1px solid #a7f3d0;
            color: #047857;
        }

        .alert-error-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: var(--gh-danger);
        }

        .users-card {
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(7, 26, 47, .05);
        }

        .users-header {
            padding: 20px 22px;
            border-bottom: 1px solid var(--gh-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .users-header h2 {
            margin: 0;
            color: var(--gh-navy);
            font-size: 19px;
            font-weight: 700;
        }

        .user-count {
            background: #fff7df;
            color: #8a6500;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .table-wrapper { width: 100%; overflow-x: auto; }

        .users-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            min-width: 760px;
        }

        .users-table thead th {
            background: var(--gh-navy);
            color: #fff;
            border: none;
            padding: 15px 16px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        .users-table tbody td {
            padding: 15px 16px;
            border-bottom: 1px solid #edf0f3;
            color: #374151;
            font-size: 14px;
            vertical-align: middle;
        }

        .users-table tbody tr:last-child td { border-bottom: none; }
        .users-table tbody tr:hover { background: #fafbfc; }

        .user-id { color: var(--gh-navy); font-weight: 700; }
        .user-name { color: var(--gh-navy); font-weight: 600; }
        .email-address { color: #475569; }

        .delete-btn {
            border: 1px solid #fecdd3;
            background: #fff1f2;
            color: #b42318;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s ease;
        }

        .delete-btn:hover {
            background: #b42318;
            color: #fff;
            border-color: #b42318;
        }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--gh-muted); }
        .empty-state i { font-size: 42px; margin-bottom: 15px; color: #cbd5e1; }
        .empty-state h3 { font-size: 18px; color: var(--gh-navy); margin-bottom: 6px; }

        @media (max-width: 767px) {
            .sidebar { min-height: auto; }
            .main-content { padding: 18px; }
            .topbar, .users-header {
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
                <div class="brand-icon"><i class="fas fa-fire"></i></div>
                Gas <span>Hub</span>
            </a>

            <div class="sidebar-title">Administration</div>

            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="user details.php" class="nav-link active"><i class="fas fa-users"></i> User details</a>
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
                    <h1>User Details</h1>
                    <div class="welcome">Registered Gas Hub customers</div>
                </div>
                <div class="admin-badge">
                    <i class="fas fa-user-shield"></i> Administrator
                </div>
            </div>

            <?php if ($success !== ''): ?>
                <div class="alert-box alert-success-box">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert-box alert-error-box">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="users-card">

                <div class="users-header">
                    <h2><i class="fas fa-users me-2"></i> Registered Users</h2>
                    <div class="user-count">
                        <?= number_format($totalUsers); ?> User<?= $totalUsers == 1 ? '' : 's'; ?>
                    </div>
                </div>

                <?php if (count($users) > 0): ?>

                    <div class="table-wrapper">
                        <table class="users-table">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <th>Telephone</th>
                                    <th>Operation</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($users as $user): ?>

                                    <tr>

                                        <td><span class="user-id">#<?= (int)$user['id']; ?></span></td>

                                        <td>
                                            <span class="user-name">
                                                <?= htmlspecialchars($user['firstName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>

                                        <td><?= htmlspecialchars($user['lastName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>

                                        <td>
                                            <span class="email-address">
                                                <?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>

                                        <td><?= htmlspecialchars($user['telephone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>

                                        <td>
                                            <form method="POST" action=""
                                                  onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">

                                                <input type="hidden" name="csrf_token"
                                                       value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                                                <input type="hidden" name="user_id"
                                                       value="<?= (int)$user['id']; ?>">

                                                <button type="submit" name="delete_user" value="1" class="delete-btn">
                                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                                </button>

                                            </form>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>
                    </div>

                <?php else: ?>

                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No registered users</h3>
                        <p class="mb-0">There are currently no users registered on Gas Hub.</p>
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