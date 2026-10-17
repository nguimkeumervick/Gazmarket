<?php
session_start();

if (!isset($_SESSION['error'])) {
    $_SESSION['error'] = '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Gas Hub</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="./font/css/all.css">

    <style>
        :root {
            --navy: #071a2f;
            --navy-light: #0d2a47;
            --gold: #f4b41a;
            --gold-dark: #d99a00;
            --white: #ffffff;
            --light: #f5f7fa;
            --text: #1f2937;
            --muted: #6b7280;
            --danger: #dc3545;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--light);
            color: var(--text);
        }

        /* Remove link lines */
        a,
        a:hover,
        a:focus,
        a:active,
        a:visited {
            text-decoration: none !important;
            outline: none !important;
        }

        /* =========================
           TOP BAR
        ========================= */

        .topbar {
            background: var(--navy);
            color: white;
            min-height: 76px;
            display: flex;
            align-items: center;
        }

        .brand-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            object-fit: contain;
            border-radius: 10px;
            background: white;
            padding: 3px;
        }

        .brand-name {
            color: white;
            font-size: 24px;
            font-weight: 800;
            margin: 0;
            letter-spacing: 0.3px;
        }

        .brand-name span {
            color: var(--gold);
        }

        .contact-info {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 22px;
            font-size: 14px;
        }

        .contact-info i {
            color: var(--gold);
            margin-right: 6px;
        }

        /* =========================
           NAVIGATION
        ========================= */

        .main-nav {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
        }

        .nav-inner {
            min-height: 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .nav-links a {
            display: inline-block;
            color: var(--navy);
            font-weight: 600;
            padding: 11px 15px;
            border-radius: 8px;
            transition: 0.25s ease;
        }

        .nav-links a:hover {
            background: #fff6dc;
            color: #9a6b00;
        }

        .nav-links .active {
            background: var(--navy);
            color: white;
        }

        .nav-links .active:hover {
            background: var(--navy-light);
            color: white;
        }

        .logout-btn {
            background: var(--gold);
            color: var(--navy) !important;
            padding: 10px 18px !important;
            border-radius: 8px;
            font-weight: 700 !important;
        }

        .logout-btn:hover {
            background: var(--gold-dark);
            color: var(--navy) !important;
        }

        /* =========================
           LOGIN SECTION
        ========================= */

        .login-section {
            min-height: calc(100vh - 138px);
            background:
                linear-gradient(
                    rgba(7, 26, 47, 0.78),
                    rgba(7, 26, 47, 0.78)
                ),
                url("image/q2.jpg") center center / cover no-repeat;

            display: flex;
            align-items: center;
            justify-content: center;
            padding: 70px 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 470px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 18px;
            padding: 38px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.30);
        }

        .login-icon {
            width: 68px;
            height: 68px;
            margin: 0 auto 18px;
            background: var(--navy);
            color: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .login-title {
            text-align: center;
            color: var(--navy);
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .login-subtitle {
            text-align: center;
            color: var(--muted);
            margin-bottom: 28px;
            font-size: 15px;
        }

        /* Error message */

        .error-message {
            background: #fff0f1;
            border: 1px solid #f5c2c7;
            color: var(--danger);
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .error-message:empty {
            display: none;
        }

        /* Form */

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: var(--navy);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8b95a1;
            z-index: 2;
        }

        .login-input {
            width: 100%;
            height: 52px;
            border: 1px solid #d7dce2;
            border-radius: 10px;
            padding: 10px 15px 10px 45px;
            font-size: 15px;
            outline: none;
            transition: 0.2s ease;
        }

        .login-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(244, 180, 26, 0.15);
        }

        .login-button {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 10px;
            background: var(--gold);
            color: var(--navy);
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .login-button:hover {
            background: var(--gold-dark);
            transform: translateY(-1px);
        }

        .admin-link {
            display: block;
            text-align: center;
            margin-top: 22px;
            color: var(--navy);
            font-weight: 700;
        }

        .admin-link:hover {
            color: #9a6b00;
        }

        .admin-link i {
            color: var(--gold-dark);
            margin-right: 5px;
        }

        .register-text {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: var(--muted);
            font-size: 14px;
        }

        .register-text a {
            color: #9a6b00;
            font-weight: 700;
        }

        .register-text a:hover {
            color: var(--navy);
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #04101e;
            color: white;
            padding: 35px 0 25px;
        }

        .footer-brand {
            font-size: 21px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .footer-brand span {
            color: var(--gold);
        }

        .footer-text {
            color: #b7c0cb;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-links a {
            color: #d8dee6 !important;
            font-size: 14px;
        }

        .footer-links a:hover {
            color: var(--gold) !important;
        }

        .copyright {
            border-top: 1px solid rgba(255,255,255,0.10);
            margin-top: 25px;
            padding-top: 20px;
            color: #8f9baa;
            font-size: 13px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 991px) {

            .contact-info {
                justify-content: flex-start;
                margin-top: 12px;
                padding-bottom: 15px;
            }

            .nav-inner {
                flex-direction: column;
                padding: 12px 0;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                margin-top: 8px;
            }

            .login-section {
                min-height: auto;
            }
        }

        @media (max-width: 576px) {

            .topbar {
                padding: 15px 0;
            }

            .brand-area {
                justify-content: center;
            }

            .contact-info {
                flex-direction: column;
                align-items: center;
                gap: 8px;
                font-size: 13px;
            }

            .nav-links {
                width: 100%;
                gap: 4px;
            }

            .nav-links a {
                padding: 9px 10px;
                font-size: 13px;
            }

            .login-section {
                padding: 40px 15px;
            }

            .login-card {
                padding: 28px 20px;
            }

            .login-title {
                font-size: 26px;
            }

            .footer-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>

<!-- =========================
     TOP BAR
========================= -->

<div class="topbar">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-lg-5">
                <div class="brand-area">
                    <img src="image/logo3.PNG" class="brand-logo" alt="Gas Hub Logo">

                    <p class="brand-name">
                        Gas <span>Hub</span>
                    </p>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="contact-info">

                    <span>
                        <i class="fas fa-phone"></i>
                        (+237) 681 332 408
                    </span>

                    <span>
                        <i class="fas fa-envelope"></i>
                        Contact@teampress.com
                    </span>

                </div>
            </div>

        </div>
    </div>
</div>


<!-- =========================
     NAVIGATION
========================= -->

<nav class="main-nav">
    <div class="container">

        <div class="nav-inner">

            <ul class="nav-links">

                <li>
                    <a href="home page for gas.php">
                        Home
                    </a>
                </li>

                <li>
                    <a href="Become a seller.php">
                        Become a seller
                    </a>
                </li>

                <li>
                    <a href="Buy a bottle.php">
                        Buy a bottle
                    </a>
                </li>

            </ul>

            <ul class="nav-links">

                <li>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>

            </ul>

        </div>

    </div>
</nav>


<!-- =========================
     LOGIN
========================= -->

<section class="login-section">

    <div class="login-wrapper">

        <div class="login-card">

            <div class="login-icon">
                <i class="fas fa-user"></i>
            </div>

            <h1 class="login-title">
                Welcome Back
            </h1>

            <p class="login-subtitle">
                Login to your Gas Hub account
            </p>


            <!-- Error message -->
            <?php if (!empty($_SESSION['error'])): ?>

                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                        echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8');
                    ?>
                </div>

            <?php endif; ?>


            <!-- Login form -->
            <form action="log.php" method="post">

                <div class="form-group">

                    <label for="email" class="form-label">
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-envelope"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="login-input"
                            placeholder="Enter your email"
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="password" class="form-label">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-lock"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="login-input"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                </div>


                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>

            </form>


            <!-- Admin login -->
            <a href="admin panel/index.php" class="admin-link">
                <i class="fas fa-user-shield"></i>
                Login as Admin
            </a>


            <!-- Registration -->
            <div class="register-text">
                Don't have an account?
                <a href="signup.php">Create an account</a>
            </div>

        </div>

    </div>

</section>


<!-- =========================
     FOOTER
========================= -->

<footer>

    <div class="container">

        <div class="footer-brand">
            Gas <span>Hub</span>
        </div>

        <p class="footer-text">
            Your trusted platform for buying and selling gas cylinders.
        </p>

        <ul class="footer-links">

            <li>
                <a href="#">About Us</a>
            </li>

            <li>
                <a href="#">Contact Us</a>
            </li>

            <li>
                <a href="#">Terms of Service</a>
            </li>

            <li>
                <a href="#">Privacy Policy</a>
            </li>

        </ul>

        <div class="copyright">
            Copyright &copy; 2026 Gas Hub. All rights reserved.
        </div>

    </div>

</footer>


<!-- =========================
     JAVASCRIPT
========================= -->

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>