
<?php

// Start session
session_start();

// User must be logged in
if (!isset($_SESSION['user'])) {
    header("location:login.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
        content="Gas Hub — find reliable gas suppliers and buy gas easily.">

    <title>Gas Hub · Home</title>

    <!-- Bootstrap -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="./font/css/all.css">

    <!-- Animate CSS -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap"
        rel="stylesheet">

    <style>

        /* =========================
           GLOBAL
        ========================== */

        :root {
            --navy: #0b1728;
            --navy-light: #14253d;
            --gold: #f4b942;
            --gold-dark: #d89b1d;
            --cream: #fffaf0;
            --light: #f5f7fa;
            --white: #ffffff;
            --text: #1f2937;
            --muted: #667085;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            background: var(--light);
            line-height: 1.6;
        }

        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: 'Poppins', sans-serif;
        }

        /* IMPORTANT:
           Remove all unwanted lines/underlines
           from clickable links.
        */

        a,
        a:hover,
        a:focus,
        a:active,
        a:visited {
            text-decoration: none !important;
            outline: none;
        }


        /* =========================
           TOP NAVIGATION
        ========================== */

        .topbar {
            background: rgba(11, 23, 40, 0.98);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding: 10px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }

        .brand-logo {
            height: 48px;
            width: auto;
            object-fit: contain;
            margin-right: 10px;
        }

        .contact-info {
            color: rgba(255,255,255,0.78);
            font-size: 13px;
            margin: 0;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
        }

        .contact-info img {
            width: 20px;
            height: 20px;
            object-fit: contain;
        }

        .contact-label {
            color: var(--gold);
            font-weight: 700;
            margin-right: 4px;
        }

        .nav-area {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 7px;
        }

        .nav-link-custom {
            display: inline-flex;
            align-items: center;
            padding: 9px 14px;
            color: rgba(255,255,255,0.82) !important;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.25s ease;
            border: 1px solid transparent;
        }

        .nav-link-custom:hover,
        .nav-link-custom:focus,
        .nav-link-custom:active {
            color: var(--gold) !important;
            background: rgba(244,185,66,0.10);
            border-color: rgba(244,185,66,0.20);
            transform: translateY(-1px);
        }

        .nav-link-custom i {
            margin-right: 6px;
        }

        .logout-btn {
            border: 1px solid rgba(244,185,66,0.55);
            background: var(--gold);
            color: var(--navy);
            border-radius: 8px;
            padding: 9px 15px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .logout-btn:hover {
            background: var(--gold-dark);
            color: #fff;
            border-color: var(--gold-dark);
            transform: translateY(-1px);
        }


        /* =========================
           HERO
        ========================== */

        .hero {
            position: relative;
            min-height: 520px;
            display: flex;
            align-items: center;
            overflow: hidden;

            background:
                linear-gradient(
                    115deg,
                    rgba(11,23,40,0.97),
                    rgba(20,37,61,0.88)
                ),
                url("image/h2.jpg");

            background-size: cover;
            background-position: center;
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(244,185,66,0.10);
            top: -180px;
            right: -100px;
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(244,185,66,0.07);
            bottom: -130px;
            left: -80px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 850px;
        }

        .hero-badge {
            display: inline-block;
            padding: 7px 15px;
            border-radius: 30px;
            background: rgba(244,185,66,0.12);
            border: 1px solid rgba(244,185,66,0.35);
            color: var(--gold);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .hero h1 {
            color: #fff;
            font-size: 58px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 22px;
        }

        .hero h1 span {
            color: var(--gold);
        }

        .hero p {
            color: rgba(255,255,255,0.78);
            font-size: 18px;
            max-width: 700px;
            margin-bottom: 30px;
        }

        .hero-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-gold {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 13px 25px;
            border-radius: 9px;
            background: var(--gold);
            color: var(--navy) !important;
            font-weight: 700;
            transition: all 0.25s ease;
        }

        .btn-gold:hover {
            background: var(--gold-dark);
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(244,185,66,0.20);
        }

        .btn-outline-light-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 25px;
            border-radius: 9px;
            border: 1px solid rgba(255,255,255,0.35);
            color: #fff !important;
            font-weight: 600;
            transition: all 0.25s ease;
        }

        .btn-outline-light-custom:hover {
            background: #fff;
            color: var(--navy) !important;
            border-color: #fff;
        }


        /* =========================
           SECTION HEADINGS
        ========================== */

        .section {
            padding: 80px 0;
        }

        .section-white {
            background: #fff;
        }

        .section-cream {
            background: var(--cream);
        }

        .section-title {
            font-size: 34px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 12px;
        }

        .section-subtitle {
            color: var(--muted);
            max-width: 700px;
            margin: 0 auto 45px;
        }

        .gold-line {
            width: 55px;
            height: 4px;
            background: var(--gold);
            border-radius: 10px;
            margin: 0 auto 18px;
        }


        /* =========================
           FEATURE CARDS
        ========================== */

        .feature-card {
            height: 100%;
            background: #fff;
            border-radius: 16px;
            padding: 35px 25px;
            border: 1px solid #edf0f4;
            box-shadow: 0 10px 30px rgba(11,23,40,0.05);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 18px 40px rgba(11,23,40,0.10);
            border-color: rgba(244,185,66,0.35);
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
        }

        .feature-card h4 {
            color: var(--navy);
            font-weight: 700;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: var(--muted);
            margin-bottom: 0;
        }


        /* =========================
           CONTENT BLOCKS
        ========================== */

        .content-image {
            width: 100%;
            max-width: 560px;
            height: auto;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(11,23,40,0.10);
        }

        .content-label {
            color: var(--gold-dark);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .content-title {
            color: var(--navy);
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
        }

        .content-text {
            color: var(--muted);
            font-size: 16px;
            line-height: 1.8;
        }


        /* =========================
           CTA BUTTON
        ========================== */

        .cta-button {
            display: inline-flex;
            align-items: center;
            padding: 12px 22px;
            background: var(--navy);
            color: #fff !important;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.25s ease;
        }

        .cta-button:hover {
            background: var(--gold);
            color: var(--navy) !important;
            transform: translateY(-2px);
        }

        .cta-button i {
            margin-left: 8px;
        }


        /* =========================
           TRUST CARDS
        ========================== */

        .trust-card {
            text-align: center;
            padding: 25px 18px;
        }

        .trust-card img {
            width: 75px;
            height: 75px;
            object-fit: contain;
            margin-bottom: 15px;
        }

        .trust-card h4 {
            color: var(--navy);
            font-size: 21px;
            font-weight: 700;
        }

        .trust-card p {
            color: var(--muted);
            font-size: 14px;
        }


        /* =========================
           FOOTER
        ========================== */

        footer {
            background: var(--navy);
            color: rgba(255,255,255,0.70);
            padding: 35px 0;
        }

        .footer-title {
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }

        .footer-links {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin: 0;
        }

        .footer-links a {
            color: rgba(255,255,255,0.65) !important;
            font-size: 13px;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: var(--gold) !important;
        }


        /* =========================
           RESPONSIVE
        ========================== */

        @media (max-width: 991px) {

            .contact-area {
                text-align: center;
                margin-bottom: 10px;
            }

            .contact-info {
                justify-content: center;
            }

            .nav-area {
                justify-content: center;
            }

            .hero h1 {
                font-size: 45px;
            }
        }

        @media (max-width: 767px) {

            .topbar {
                position: relative;
            }

            .brand-logo {
                height: 42px;
            }

            .hero {
                min-height: 500px;
                text-align: center;
            }

            .hero h1 {
                font-size: 38px;
            }

            .hero p {
                font-size: 16px;
            }

            .hero-buttons {
                justify-content: center;
            }

            .section {
                padding: 60px 0;
            }

            .section-title {
                font-size: 28px;
            }

            .content-title {
                font-size: 27px;
            }

            .footer-links {
                justify-content: center;
                margin-top: 15px;
            }
        }

    </style>
</head>


<body>

<!-- =========================
     NAVIGATION
========================== -->

<header class="topbar">

    <div class="container">

        <div class="row align-items-center">

            <!-- Contact / Logo -->
            <div class="col-lg-6 contact-area">

                <p class="contact-info">

                    <img src="image/logo3.PNG"
                         class="brand-logo"
                         alt="Gas Hub">

                    <span class="contact-label">
                        Contact:
                    </span>

                    <img src="image/z1.png" alt="Phone">

                    <span>(+237) 681 332 408</span>

                    <img src="image/z2.png" alt="Email">

                    <span>loicjohn729@gmail.com</span>

                </p>

            </div>


            <!-- Navigation -->
            <div class="col-lg-6">

                <nav class="nav-area">

                    <a href="home page for gas.php"
                       class="nav-link-custom">

                        <i class="fas fa-home"></i>
                        Home

                    </a>


                    <a href="Become a seller.php"
                       class="nav-link-custom">

                        <i class="fas fa-store"></i>
                        Become a Seller

                    </a>


                    <a href="Buy a bottle.php"
                       class="nav-link-custom">

                        <i class="fas fa-shopping-cart"></i>
                        Buy a Bottle

                    </a>


                    <form action="logout.php"
                          method="POST"
                          class="d-inline m-0">

                        <button type="submit"
                                class="logout-btn">

                            <i class="fas fa-sign-out-alt"></i>
                            Logout

                        </button>

                    </form>

                </nav>

            </div>

        </div>

    </div>

</header>


<!-- =========================
     HERO
========================== -->

<section class="hero">

    <div class="container">

        <div class="hero-content">

            <span class="hero-badge">
                <i class="fas fa-fire"></i>
                GAS HUB MARKETPLACE
            </span>

            <h1>
                Find the right gas
                <span>without the hassle.</span>
            </h1>

            <p>
                Gas Hub connects customers with gas sellers through a
                simple and convenient online marketplace for buying
                and selling gas.
            </p>

            <div class="hero-buttons">

                <a href="Buy a bottle.php"
                   class="btn-gold">

                    <i class="fas fa-shopping-cart mr-2"></i>
                    Buy a Bottle

                </a>


                <a href="Become a seller.php"
                   class="btn-outline-light-custom">

                    <i class="fas fa-store mr-2"></i>
                    Become a Seller

                </a>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     FEATURES
========================== -->

<section class="section section-white">

    <div class="container">

        <div class="text-center">

            <div class="gold-line"></div>

            <h2 class="section-title">
                Everything You Need in One Place
            </h2>

            <p class="section-subtitle">
                Gas Hub makes it easier for customers and sellers
                to connect, communicate and complete transactions.
            </p>

        </div>


        <div class="row">

            <!-- Buy & Sell -->
            <div class="col-md-4 mb-4">

                <div class="feature-card text-center">

                    <img src="image/u3.jpg"
                         class="feature-icon"
                         alt="Buy and sell">

                    <h4>
                        Buy &amp; Sell
                    </h4>

                    <p>
                        Browse gas products from sellers and
                        showcase your gas products to potential
                        customers through one convenient platform.
                    </p>

                </div>

            </div>


            <!-- Network -->
            <div class="col-md-4 mb-4">

                <div class="feature-card text-center">

                    <img src="image/u4.jpg"
                         class="feature-icon"
                         alt="Build a network">

                    <h4>
                        Build a Network
                    </h4>

                    <p>
                        Connect buyers and sellers, communicate
                        clearly and build relationships before
                        completing a transaction.
                    </p>

                </div>

            </div>


            <!-- Secure -->
            <div class="col-md-4 mb-4">

                <div class="feature-card text-center">

                    <img src="image/u5.jpg"
                         class="feature-icon"
                         alt="Secure marketplace">

                    <h4>
                        Secure &amp; Transparent
                    </h4>

                    <p>
                        A simple marketplace designed to make
                        buying and selling gas more convenient
                        and transparent.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     BUYER SECTION
========================== -->

<section class="section section-cream">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-md-6 mb-4 mb-md-0">

                <div class="content-label">
                    For Buyers
                </div>

                <h2 class="content-title">
                    Discover, Connect and Purchase
                </h2>

                <p class="content-text mt-3">

                    Searching and buying gas can be easier.
                    With Gas Hub, customers can browse available
                    gas products, find sellers and start the
                    purchase process from one convenient platform.

                </p>

                <a href="Buy a bottle.php"
                   class="cta-button mt-3">

                    Get Started
                    <i class="fas fa-arrow-right"></i>

                </a>

            </div>


            <div class="col-md-6 text-center">

                <img src="image/g6.jpg"
                     class="content-image"
                     alt="Gas Hub buyer">

            </div>

        </div>

    </div>

</section>


<!-- =========================
     SELLER SECTION
========================== -->

<section class="section section-white">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-md-6 text-center order-2 order-md-1">

                <img src="image/h6.jpg"
                     class="content-image"
                     alt="Gas Hub seller">

            </div>


            <div class="col-md-6 order-1 order-md-2 mb-4 mb-md-0">

                <div class="content-label">
                    For Sellers
                </div>

                <h2 class="content-title">
                    List, Connect and Sell
                </h2>

                <p class="content-text mt-3">

                    Take your gas business online and showcase
                    your products to customers looking for gas.
                    Gas Hub provides a simple way to present
                    your products and connect with potential buyers.

                </p>

                <a href="Become a seller.php"
                   class="cta-button mt-3">

                    Become a Seller
                    <i class="fas fa-arrow-right"></i>

                </a>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     PLATFORM SECTION
========================== -->

<section class="section section-cream">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-md-6 mb-4 mb-md-0">

                <div class="content-label">
                    Gas Hub Platform
                </div>

                <h2 class="content-title">
                    A Better Way to Manage Gas Marketplace Activities
                </h2>

                <p class="content-text mt-3">

                    Gas Hub provides an online environment where
                    customers and sellers can interact more easily.
                    The platform can help businesses understand
                    demand, improve service delivery and make gas
                    purchasing more convenient.

                </p>

            </div>


            <div class="col-md-6 text-center">

                <img src="image/b5.jpg"
                     class="content-image"
                     alt="Gas Hub platform">

            </div>

        </div>

    </div>

</section>


<!-- =========================
     TRUST SECTION
========================== -->

<section class="section section-white">

    <div class="container">

        <div class="text-center mb-5">

            <div class="gold-line"></div>

            <h2 class="section-title">
                Why Use Gas Hub?
            </h2>

            <p class="section-subtitle">
                Designed to make gas marketplace transactions
                simple and convenient.
            </p>

        </div>


        <div class="row">

            <!-- Verified -->
            <div class="col-md-4 mb-4">

                <div class="trust-card">

                    <img src="image/u1.png"
                         alt="Verified companies">

                    <h4>
                        Verified Companies
                    </h4>

                    <p>
                        Seller registration can be followed by
                        verification procedures to improve trust
                        within the marketplace.
                    </p>

                </div>

            </div>


            <!-- Communication -->
            <div class="col-md-4 mb-4">

                <div class="trust-card">

                    <img src="image/u2.png"
                         alt="Communication">

                    <h4>
                        Easy Communication
                    </h4>

                    <p>
                        Buyers and sellers can communicate to
                        better understand requirements and
                        expectations.
                    </p>

                </div>

            </div>


            <!-- Payments -->
            <div class="col-md-4 mb-4">

                <div class="trust-card">

                    <img src="image/u2.png"
                         alt="Payments">

                    <h4>
                        Local Payment Options
                    </h4>

                    <p>
                        The platform can support payment options
                        such as MTN Mobile Money and Orange Money
                        as the payment system is developed.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     FOOTER
========================== -->

<footer>

    <div class="container">

        <div class="row align-items-center">

            <div class="col-md-6 text-center text-md-left">

                <p class="mb-0">
                    © 2026 Gas Hub.
                    All rights reserved.
                </p>

            </div>


            <div class="col-md-6">

                <ul class="footer-links">

                    <!-- These are intentionally not linked
                         until the actual pages exist. -->

                    <li>
                        <span>About Us</span>
                    </li>

                    <li>
                        <span>Contact Us</span>
                    </li>

                    <li>
                        <span>Terms of Service</span>
                    </li>

                    <li>
                        <span>Privacy Policy</span>
                    </li>

                </ul>

            </div>

        </div>

    </div>

</footer>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.slim.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

</body>

</html>

