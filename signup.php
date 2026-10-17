<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | Gas Hub</title>

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
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--light);
            color: var(--text);
        }

        /* Remove lines from links */
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
            list-style: none;
            margin: 0;
            padding: 0;
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
           SIGNUP SECTION
        ========================= */

        .signup-section {
            min-height: calc(100vh - 138px);

            background:
                linear-gradient(
                    rgba(7, 26, 47, 0.80),
                    rgba(7, 26, 47, 0.80)
                ),
                url("image/q2.jpg") center center / cover no-repeat;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 65px 20px;
        }

        .signup-wrapper {
            width: 100%;
            max-width: 540px;
        }

        .signup-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 18px;
            padding: 38px;

            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.30);
        }

        .signup-icon {
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

        .signup-title {
            text-align: center;
            color: var(--navy);
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .signup-subtitle {
            text-align: center;
            color: var(--muted);
            font-size: 15px;
            margin-bottom: 28px;
        }

        /* =========================
           FORM
        ========================= */

        .form-group {
            margin-bottom: 18px;
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

        .signup-input {
            width: 100%;
            height: 51px;

            border: 1px solid #d7dce2;
            border-radius: 10px;

            padding: 10px 15px 10px 45px;

            font-size: 15px;

            outline: none;

            transition: 0.2s ease;
        }

        .signup-input:focus {
            border-color: var(--gold);

            box-shadow:
                0 0 0 3px rgba(244, 180, 26, 0.15);
        }

        .signup-button {
            width: 100%;
            height: 52px;

            border: none;
            border-radius: 10px;

            background: var(--gold);
            color: var(--navy);

            font-size: 16px;
            font-weight: 800;

            cursor: pointer;

            margin-top: 8px;

            transition: 0.25s ease;
        }

        .signup-button:hover {
            background: var(--gold-dark);
            transform: translateY(-1px);
        }

        .login-text {
            text-align: center;

            margin-top: 24px;
            padding-top: 20px;

            border-top: 1px solid #e5e7eb;

            color: var(--muted);
            font-size: 14px;
        }

        .login-text a {
            color: #9a6b00;
            font-weight: 700;
        }

        .login-text a:hover {
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
                justify-content: center;
            }

            .nav-links a {
                padding: 9px 10px;
                font-size: 13px;
            }

            .signup-section {
                padding: 40px 15px;
            }

            .signup-card {
                padding: 28px 20px;
            }

            .signup-title {
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

                    <img
                        src="image/logo3.PNG"
                        class="brand-logo"
                        alt="Gas Hub Logo"
                    >

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
                    <a href="login.php" class="logout-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- =========================
     SIGN UP SECTION
========================= -->

<section class="signup-section">

    <div class="signup-wrapper">

        <div class="signup-card">


            <div class="signup-icon">

                <i class="fas fa-user-plus"></i>

            </div>


            <h1 class="signup-title">
                Create Your Account
            </h1>


            <p class="signup-subtitle">
                Join Gas Hub and start buying or selling gas cylinders.
            </p>


            <!-- SIGNUP FORM -->

            <form action="form.php" method="post">


                <!-- FIRST NAME -->

                <div class="form-group">

                    <label
                        for="firstName"
                        class="form-label"
                    >
                        First Name
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-user"></i>

                        <input
                            type="text"
                            id="firstName"
                            name="firstName"
                            class="signup-input"
                            placeholder="Enter your first name"
                            autocomplete="given-name"
                            required
                        >

                    </div>

                </div>


                <!-- LAST NAME -->

                <div class="form-group">

                    <label
                        for="lastName"
                        class="form-label"
                    >
                        Last Name
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-user"></i>

                        <input
                            type="text"
                            id="lastName"
                            name="lastName"
                            class="signup-input"
                            placeholder="Enter your last name"
                            autocomplete="family-name"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label
                        for="email"
                        class="form-label"
                    >
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-envelope"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="signup-input"
                            placeholder="Enter your email"
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>


                <!-- TELEPHONE -->

                <div class="form-group">

                    <label
                        for="telephone"
                        class="form-label"
                    >
                        Telephone Number
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-phone"></i>

                        <input
                            type="tel"
                            id="telephone"
                            name="telephone"
                            class="signup-input"
                            placeholder="e.g. 681332408"
                            autocomplete="tel"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Password
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-lock"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="signup-input"
                            placeholder="Create a password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                    </div>

                    <small class="text-muted">
                        Password must contain at least 8 characters.
                    </small>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="signup-button"
                >
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>


            </form>


            <!-- LOGIN LINK -->

            <div class="login-text">

                Already have an account?

                <a href="login.php">
                    Login here
                </a>

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
                <a href="#">
                    About Us
                </a>
            </li>

            <li>
                <a href="#">
                    Contact Us
                </a>
            </li>

            <li>
                <a href="#">
                    Terms of Service
                </a>
            </li>

            <li>
                <a href="#">
                    Privacy Policy
                </a>
            </li>

        </ul>


        <div class="copyright">
            Copyright &copy; 2026 Gas Hub. All rights reserved.
        </div>

    </div>

</footer>


<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>