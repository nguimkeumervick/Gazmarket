<?php

session_start();

require_once 'config.php';

/*
|--------------------------------------------------------------------------
| ADMIN LOGIN PAGE
|--------------------------------------------------------------------------
| Existing database:
| Database: projects
| Table: admin
| Columns used: username, password
|
| Existing session:
| $_SESSION['username']
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['admin_login_csrf'])) {
    $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$error = '';
$success = '';


/*
|--------------------------------------------------------------------------
| ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['username']) && $_SESSION['username'] !== '') {

    header('Location: dashboard.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| PROCESS LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    /*
    |--------------------------------------------------------------------------
    | CHECK CSRF TOKEN
    |--------------------------------------------------------------------------
    */

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        empty($csrfToken) ||
        empty($_SESSION['admin_login_csrf']) ||
        !hash_equals($_SESSION['admin_login_csrf'], $csrfToken)
    ) {

        $error = 'Your login session has expired. Please try again.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | GET FORM DATA
        |--------------------------------------------------------------------------
        */

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | VALIDATE INPUT
        |--------------------------------------------------------------------------
        */

        if ($username === '' || $password === '') {

            $error = 'Please enter your username and password.';

        } elseif (strlen($username) > 100) {

            $error = 'Invalid username or password.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | FIND ADMIN ACCOUNT
            |--------------------------------------------------------------------------
            |
            | Prepared statement protects the login query from SQL injection.
            |
            */

            $stmt = $conn->prepare(
                "SELECT username, password
                 FROM admin
                 WHERE username = ?
                 LIMIT 1"
            );


            if (!$stmt) {

                error_log(
                    'Gas Hub admin login prepare failed: ' .
                    $conn->error
                );

                $error = 'Unable to process the login at the moment.';

            } else {

                $stmt->bind_param('s', $username);

                if (!$stmt->execute()) {

                    error_log(
                        'Gas Hub admin login execute failed: ' .
                        $stmt->error
                    );

                    $error = 'Unable to process the login at the moment.';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | GET RESULT
                    |--------------------------------------------------------------------------
                    */

                    $stmt->store_result();

                    if ($stmt->num_rows === 1) {

                        $stmt->bind_result(
                            $dbUsername,
                            $storedPassword
                        );

                        $stmt->fetch();


                        /*
                        |--------------------------------------------------------------------------
                        | PASSWORD VERIFICATION
                        |--------------------------------------------------------------------------
                        |
                        | This supports BOTH:
                        |
                        | 1. Existing plain-text password
                        | 2. Secure password_hash() password
                        |
                        | If the existing password is plain text, it is automatically
                        | converted to a secure hash after successful login.
                        |
                        |--------------------------------------------------------------------------
                        */

                        $loginSuccess = false;

                        /*
                        |--------------------------------------------------------------------------
                        | FIRST: CHECK SECURE HASH
                        |--------------------------------------------------------------------------
                        */

                        $passwordInfo = password_get_info($storedPassword);

                        if (
                            isset($passwordInfo['algo']) &&
                            $passwordInfo['algo'] !== 0
                        ) {

                            $loginSuccess = password_verify(
                                $password,
                                $storedPassword
                            );

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | TEMPORARY SUPPORT FOR OLD PLAIN-TEXT PASSWORD
                            |--------------------------------------------------------------------------
                            |
                            | This allows your existing admin account to continue working.
                            |
                            */

                            if (
                                is_string($storedPassword) &&
                                hash_equals($storedPassword, $password)
                            ) {

                                $loginSuccess = true;


                                /*
                                |--------------------------------------------------------------------------
                                | UPGRADE PASSWORD TO SECURE HASH
                                |--------------------------------------------------------------------------
                                */

                                $newPasswordHash = password_hash(
                                    $password,
                                    PASSWORD_DEFAULT
                                );


                                if ($newPasswordHash !== false) {

                                    $update = $conn->prepare(
                                        "UPDATE admin
                                         SET password = ?
                                         WHERE username = ?
                                         LIMIT 1"
                                    );

                                    if ($update) {

                                        $update->bind_param(
                                            'ss',
                                            $newPasswordHash,
                                            $dbUsername
                                        );

                                        if (!$update->execute()) {

                                            error_log(
                                                'Gas Hub admin password migration failed: ' .
                                                $update->error
                                            );
                                        }

                                        $update->close();
                                    }
                                }
                            }
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | SUCCESSFUL LOGIN
                        |--------------------------------------------------------------------------
                        */

                        if ($loginSuccess) {

                            /*
                            | Prevent session fixation.
                            */
                            session_regenerate_id(true);


                            /*
                            | Preserve your existing session variable.
                            */
                            $_SESSION['username'] = $dbUsername;


                            /*
                            | Optional admin-specific session information.
                            */
                            $_SESSION['admin_logged_in'] = true;


                            /*
                            | Create a fresh CSRF token for the logged-in session.
                            */
                            $_SESSION['csrf_token'] = bin2hex(
                                random_bytes(32)
                            );


                            /*
                            | Redirect to existing dashboard.
                            */
                            header('Location: dashboard.php');
                            exit;

                        } else {

                            $error = 'Invalid username or password.';
                        }

                    } else {

                        $error = 'Invalid username or password.';
                    }
                }

                $stmt->close();
            }
        }
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

    <title>Admin Login · Gas Hub</title>


    <!-- Bootstrap -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="../font/css/all.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | GAS HUB COLORS
        |--------------------------------------------------------------------------
        */

        :root {

            --gh-navy: #071a2f;

            --gh-navy-light: #0d2a47;

            --gh-gold: #f4b41a;

            --gh-gold-dark: #d99d00;

            --gh-bg: #f5f7fa;

            --gh-white: #ffffff;

            --gh-text: #172033;

            --gh-muted: #6b7280;

            --gh-border: #e2e8f0;

            --gh-danger: #b42318;

        }


        /*
        |--------------------------------------------------------------------------
        | GLOBAL
        |--------------------------------------------------------------------------
        */

        * {
            box-sizing: border-box;
        }


        html,
        body {

            margin: 0;

            padding: 0;

            min-height: 100%;

        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                var(--gh-navy);

        }


        /*
        |--------------------------------------------------------------------------
        | REMOVE LINK UNDERLINES
        |--------------------------------------------------------------------------
        */

        a,
        a:hover,
        a:focus,
        a:active,
        a:visited {

            text-decoration: none !important;

            outline: none !important;

        }


        /*
        |--------------------------------------------------------------------------
        | LOGIN PAGE
        |--------------------------------------------------------------------------
        */

        .login-page {

            min-height: 100vh;

            position: relative;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 40px 20px;


            /*
            | IMPORTANT:
            | admin panel is one folder below the main project.
            */
            background:

                linear-gradient(
                    rgba(7, 26, 47, 0.88),
                    rgba(7, 26, 47, 0.88)
                ),

                url("../image/q2.jpg");

            background-repeat: no-repeat;

            background-size: cover;

            background-position: center;

        }


        /*
        |--------------------------------------------------------------------------
        | LOGIN CARD
        |--------------------------------------------------------------------------
        */

        .login-card {

            width: 100%;

            max-width: 450px;

            background: rgba(255, 255, 255, 0.98);

            border-radius: 22px;

            padding: 38px;

            box-shadow:
                0 25px 70px rgba(0, 0, 0, 0.35);

            border:
                1px solid rgba(255, 255, 255, 0.5);

        }


        /*
        |--------------------------------------------------------------------------
        | LOGO
        |--------------------------------------------------------------------------
        */

        .logo-icon {

            width: 66px;

            height: 66px;

            margin:
                0 auto 16px;

            border-radius: 17px;

            background:
                var(--gh-gold);

            color:
                var(--gh-navy);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 29px;

            box-shadow:
                0 8px 20px
                rgba(244, 180, 26, 0.25);

        }


        /*
        |--------------------------------------------------------------------------
        | BRAND
        |--------------------------------------------------------------------------
        */

        .brand-name {

            text-align: center;

            color:
                var(--gh-navy);

            font-size: 29px;

            font-weight: 700;

            margin-bottom: 4px;

        }


        .brand-name span {

            color:
                var(--gh-gold-dark);

        }


        .login-subtitle {

            text-align: center;

            color:
                var(--gh-muted);

            font-size: 14px;

            margin-bottom: 30px;

        }


        /*
        |--------------------------------------------------------------------------
        | ERROR MESSAGE
        |--------------------------------------------------------------------------
        */

        .error-message {

            background:
                #fff1f2;

            border:
                1px solid #fecdd3;

            color:
                var(--gh-danger);

            border-radius: 10px;

            padding: 12px 14px;

            font-size: 13px;

            margin-bottom: 20px;

        }


        /*
        |--------------------------------------------------------------------------
        | FORM LABEL
        |--------------------------------------------------------------------------
        */

        .form-label {

            display: block;

            color:
                var(--gh-navy);

            font-size: 13px;

            font-weight: 700;

            margin-bottom: 8px;

        }


        /*
        |--------------------------------------------------------------------------
        | INPUT
        |--------------------------------------------------------------------------
        */

        .input-group {

            margin-bottom: 20px;

        }


        .input-group-text {

            min-width: 48px;

            justify-content: center;

            background:
                #f8fafc;

            border:
                1px solid var(--gh-border);

            border-right: none;

            color:
                #64748b;

        }


        .form-control {

            height: 48px;

            border:
                1px solid var(--gh-border);

            box-shadow: none !important;

            color:
                var(--gh-text);

        }


        .form-control:focus {

            border-color:
                var(--gh-gold);

            box-shadow:
                0 0 0 3px
                rgba(244, 180, 26, 0.15)
                !important;

        }


        /*
        |--------------------------------------------------------------------------
        | LOGIN BUTTON
        |--------------------------------------------------------------------------
        */

        .login-btn {

            width: 100%;

            height: 50px;

            border: none;

            border-radius: 11px;

            background:
                var(--gh-gold);

            color:
                var(--gh-navy);

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            transition:
                background .2s ease,
                transform .2s ease,
                box-shadow .2s ease;

        }


        .login-btn:hover {

            background:
                var(--gh-gold-dark);

            color:
                var(--gh-navy);

            transform:
                translateY(-1px);

            box-shadow:
                0 7px 18px
                rgba(217, 157, 0, 0.25);

        }


        .login-btn:focus {

            outline: none;

        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .login-footer {

            text-align: center;

            margin-top: 25px;

            padding-top: 20px;

            border-top:
                1px solid #edf0f3;

            color:
                #94a3b8;

            font-size: 12px;

        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 500px) {

            .login-page {

                padding:
                    20px 14px;

            }


            .login-card {

                padding:
                    28px 22px;

                border-radius:
                    18px;

            }


            .brand-name {

                font-size:
                    25px;

            }

        }

    </style>

</head>


<body>


<div class="login-page">


    <div class="login-card">


        <!-- =====================================================
             GAS HUB LOGO
        ====================================================== -->

        <div class="logo-icon">

            <i class="fas fa-fire"></i>

        </div>


        <div class="brand-name">

            Gas <span>Hub</span>

        </div>


        <div class="login-subtitle">

            Administrator access

        </div>


        <!-- =====================================================
             ERROR MESSAGE
        ====================================================== -->

        <?php if ($error !== ''): ?>

            <div class="error-message">

                <i class="fas fa-exclamation-circle mr-1"></i>

                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             LOGIN FORM
        ====================================================== -->

        <form
            method="post"
            action=""
            autocomplete="on"
        >


            <!-- CSRF TOKEN -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['admin_login_csrf'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
            >


            <!-- USERNAME -->

            <label
                for="username"
                class="form-label"
            >
                Username
            </label>


            <div class="input-group">

                <div class="input-group-prepend">

                    <span class="input-group-text">

                        <i class="fas fa-user"></i>

                    </span>

                </div>


                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="Enter admin username"
                    required
                    maxlength="100"
                    autocomplete="username"
                >

            </div>


            <!-- PASSWORD -->

            <label
                for="password"
                class="form-label"
            >
                Password
            </label>


            <div class="input-group">

                <div class="input-group-prepend">

                    <span class="input-group-text">

                        <i class="fas fa-lock"></i>

                    </span>

                </div>


                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Enter admin password"
                    required
                    autocomplete="current-password"
                >

            </div>


            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                name="submit"
                value="Login"
                class="login-btn"
            >

                <i class="fas fa-sign-in-alt mr-2"></i>

                Login to Dashboard

            </button>


        </form>


        <!-- =====================================================
             FOOTER
        ====================================================== -->

        <div class="login-footer">

            &copy; <?= date('Y'); ?> Gas Hub.
            All rights reserved.

        </div>


    </div>


</div>


<!-- =========================================================
     BOOTSTRAP JAVASCRIPT
========================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js">
</script>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>