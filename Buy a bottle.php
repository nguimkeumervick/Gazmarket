<?php

session_start();

/*
 * Buy a bottle.php
 * Gas Hub marketplace page
 *
 * Database: projects
 * Table: element
 *
 * IMPORTANT:
 * - Each product has a unique ID in the element table.
 * - The product ID is used to identify the selected bottle.
 * - The price is NEVER sent as a trusted value to the next page.
 * - Deliv.php retrieves the official price directly from the database.
 */

// Require the user to be logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}


// =========================
// DATABASE CONNECTION
// =========================

$con = mysqli_connect(
    "localhost",
    "root",
    "",
    "projects"
);

if (!$con) {
    die("Database connection failed.");
}

mysqli_set_charset($con, "utf8mb4");


// =========================
// CSRF TOKEN
// =========================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// =========================
// GET PRODUCTS
// =========================

$select = "
    SELECT id, name, price, location, img, description
    FROM element
    ORDER BY id DESC
";

$sql = mysqli_query($con, $select);

if (!$sql) {
    die("Unable to load gas products.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Gas Hub - Buy gas bottles easily from our online marketplace.">

    <title>Buy a Bottle · Gas Hub</title>


    <!-- Bootstrap -->

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">


    <!-- Font Awesome -->

    <link rel="stylesheet"
          href="./font/css/all.css">


    <!-- Google Fonts -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap"
          rel="stylesheet">


    <style>

        :root {
            --gh-navy: #071a2f;
            --gh-navy-light: #0d2a47;
            --gh-gold: #f4b41a;
            --gh-gold-dark: #d99d00;
            --gh-bg: #f5f7fa;
            --gh-text: #1f2937;
            --gh-muted: #6b7280;
            --gh-border: #e5e7eb;
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
            color: var(--gh-text);
            background-color: var(--gh-bg);
        }


        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: 'Poppins', sans-serif;
        }


        /* Remove underlines */

        a,
        a:hover,
        a:focus,
        a:active,
        a:visited {
            text-decoration: none !important;
            outline: none !important;
        }


        /* =========================
           TOP NAVIGATION
        ========================== */

        .nob {
            background: var(--gh-navy);
            border-bottom: 1px solid rgba(255,255,255,.08);
        }


        .not {
            height: 52px;
            width: auto;
            object-fit: contain;
            vertical-align: middle;
        }


        .contact-info {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0;
            color: #fff;
            font-size: .88rem;
        }


        .contact-info img:not(.not) {
            height: 18px;
            width: auto;
            object-fit: contain;
        }


        .nav-links {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 20px;
        }


        .nav-links a {
            color: #fff !important;
            opacity: .88;
            font-weight: 500;
            transition: .2s ease;
        }


        .nav-links a:hover {
            color: var(--gh-gold) !important;
            opacity: 1;
        }


        .logout-btn {
            border: 1px solid var(--gh-gold);
            color: var(--gh-gold) !important;
            border-radius: 7px;
            font-weight: 600;
            padding: 7px 15px;
        }


        .logout-btn:hover {
            background: var(--gh-gold);
            color: var(--gh-navy) !important;
        }


        /* =========================
           HERO
        ========================== */

        #post {
            min-height: 470px;

            background-image:
                linear-gradient(
                    135deg,
                    rgba(7, 26, 47, .94),
                    rgba(7, 26, 47, .60)
                ),
                url("image/banner.png");

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;

            display: flex;
            align-items: center;
        }


        .hero-content {
            text-align: center;
            max-width: 900px;
            margin: auto;
            padding: 70px 20px;
        }


        .hero-badge {
            display: inline-block;
            background: rgba(244,180,26,.15);
            border: 1px solid rgba(244,180,26,.45);
            color: var(--gh-gold);
            padding: 7px 15px;
            border-radius: 50px;
            font-size: .85rem;
            font-weight: 600;
            margin-bottom: 20px;
        }


        .hero-content h1 {
            font-size: 2.8rem;
            line-height: 1.2;
            font-weight: 700;
            color: #fff;
            margin-bottom: 20px;
        }


        .hero-content p {
            font-size: 1.15rem;
            color: rgba(255,255,255,.88);
            line-height: 1.7;
            max-width: 760px;
            margin: auto;
        }


        /* =========================
           INTRO
        ========================== */

        .intro-section {
            padding: 65px 20px 30px;
            text-align: center;
        }


        .intro-section h2 {
            font-weight: 700;
            color: var(--gh-navy);
            margin-bottom: 12px;
        }


        .intro-section p {
            color: var(--gh-muted);
            font-size: 1.02rem;
            margin-bottom: 8px;
        }


        .commission-note {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff8df;
            color: #725500;
            border: 1px solid #f2d477;
            border-radius: 8px;
            padding: 9px 14px;
            margin-top: 12px;
            font-size: .88rem;
            font-weight: 500;
        }


        .commission-note i {
            color: var(--gh-gold-dark);
        }


        /* =========================
           PRODUCTS
        ========================== */

        .products-section {
            padding: 25px 0 80px;
        }


        .product-card {
            height: 100%;
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(7,26,47,.07);

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;
        }


        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(7,26,47,.13);
            border-color: rgba(244,180,26,.55);
        }


        .product-image-container {
            height: 220px;

            background:
                linear-gradient(
                    135deg,
                    #f8fafc,
                    #eef2f7
                );

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 20px;
        }


        .product-image {
            max-width: 100%;
            max-height: 190px;
            object-fit: contain;
            transition: transform .25s ease;
        }


        .product-card:hover .product-image {
            transform: scale(1.04);
        }


        .product-body {
            padding: 22px;
        }


        .product-name {
            font-family: 'Poppins', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--gh-navy);
            margin-bottom: 12px;
        }


        .product-detail {
            margin-bottom: 8px;
            color: #4b5563;
            font-size: .94rem;
        }


        .product-detail strong {
            color: #1f2937;
        }


        .price {
            color: var(--gh-gold-dark);
            font-size: 1.12rem;
            font-weight: 700;
        }


        .product-description {
            min-height: 48px;
            color: var(--gh-muted);
            margin-top: 12px;
            margin-bottom: 20px;
            line-height: 1.55;
            font-size: .92rem;
        }


        .buy-btn {
            width: 100%;
            background: var(--gh-navy);
            border: none;
            color: #fff !important;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;

            transition:
                background .2s ease,
                transform .2s ease;
        }


        .buy-btn:hover {
            background: var(--gh-gold);
            color: var(--gh-navy) !important;
            transform: translateY(-1px);
        }


        .buy-btn i {
            margin-right: 7px;
        }


        /* =========================
           EMPTY PRODUCTS
        ========================== */

        .empty-products {
            text-align: center;
            padding: 70px 20px;
            color: var(--gh-muted);
        }


        .empty-products i {
            font-size: 45px;
            color: var(--gh-gold);
            margin-bottom: 18px;
        }


        .empty-products h4 {
            color: var(--gh-navy);
            font-weight: 700;
        }


        /* =========================
           FOOTER
        ========================== */

        footer {
            background: var(--gh-navy);
            padding: 38px 20px;
        }


        footer p {
            margin-bottom: 20px;
        }


        footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }


        footer ul li {
            display: inline-block;
            margin: 0 12px;
        }


        footer a {
            color: rgba(255,255,255,.7) !important;
            transition: color .2s ease;
        }


        footer a:hover {
            color: var(--gh-gold) !important;
        }


        /* =========================
           RESPONSIVE
        ========================== */

        @media (max-width: 991px) {

            .contact-info {
                justify-content: center;
                margin-bottom: 15px;
            }

            .nav-links {
                justify-content: center;
                padding-bottom: 15px;
            }

            .hero-content h1 {
                font-size: 2.25rem;
            }

        }


        @media (max-width: 576px) {

            .hero-content {
                padding: 55px 15px;
            }

            .hero-content h1 {
                font-size: 1.85rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .nav-links {
                gap: 12px;
            }

            .contact-info {
                font-size: .78rem;
            }

            footer ul li {
                display: block;
                margin: 8px 0;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     NAVIGATION
========================= -->

<div class="container-fluid nob">

    <div class="row align-items-center py-2">

        <div class="col-lg-7 col-md-12">

            <p class="contact-info">

                <img
                    src="image/logo3.PNG"
                    class="not"
                    alt="Gas Hub Logo"
                >

                <span>Contact us:</span>

                <img
                    src="image/z1.png"
                    alt="Phone"
                >

                <span>(+237) 681332408</span>

                <img
                    src="image/z2.png"
                    alt="Email"
                >

                <span>Contact@teampress.com</span>

            </p>

        </div>


        <div class="col-lg-5 col-md-12">

            <div class="nav-links">

                <a href="home page for gas.php">
                    Home
                </a>

                <a href="Become a seller.php">
                    Become a seller
                </a>

                <a href="Buy a bottle.php">
                    Buy a bottle
                </a>

                <a href="logout.php"
                   class="logout-btn">

                    Logout

                </a>

            </div>

        </div>

    </div>

</div>


<!-- =========================
     HERO
========================= -->

<section id="post">

    <div class="container">

        <div class="hero-content">

            <span class="hero-badge">

                <i class="fas fa-fire mr-1"></i>

                Gas Hub Marketplace

            </span>


            <h1>
                Find the Right Gas for Your Needs
            </h1>


            <p>
                Browse gas bottles from different sellers,
                compare prices and locations, and select the
                product that suits you.
            </p>

        </div>

    </div>

</section>


<!-- =========================
     INTRODUCTION
========================= -->

<section class="intro-section">

    <div class="container">

        <h2>
            Gas Bottles Available
        </h2>


        <p>
            Choose a bottle below to continue with your order.
        </p>


        <div class="commission-note">

            <i class="fas fa-shield-alt"></i>

            <span>
                Gas Hub applies a 5% commission to each completed sale.
                The commission is calculated from the actual bottle price.
            </span>

        </div>

    </div>

</section>


<!-- =========================
     PRODUCTS
========================= -->

<section class="products-section">

    <div class="container">

        <div class="row">

            <?php if (mysqli_num_rows($sql) > 0): ?>

                <?php while ($post = mysqli_fetch_assoc($sql)): ?>

                    <?php

                    /*
                     * IMPORTANT:
                     *
                     * The unique database ID identifies this
                     * exact product listing.
                     *
                     * We intentionally DO NOT put the price
                     * in the URL.
                     */

                    $productId = (int)$post['id'];

                    $deliveryUrl = 'Delivery.php?' .
                        http_build_query([
                            'product_id' => $productId
                        ]);

                    ?>

                    <div class="col-lg-3 col-md-6 col-sm-12 mb-4">

                        <div class="product-card">


                            <!-- Product image -->

                            <div class="product-image-container">

                                <img
                                    src="images/<?= htmlspecialchars(
                                        $post['img'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    class="product-image"
                                    alt="<?= htmlspecialchars(
                                        $post['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    loading="lazy"
                                    onerror="this.src='image/logo3.PNG';"
                                >

                            </div>


                            <!-- Product information -->

                            <div class="product-body">

                                <div class="product-name">

                                    <?= htmlspecialchars(
                                        $post['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>


                                <p class="product-detail">

                                    <strong>
                                        Price:
                                    </strong>

                                    <span class="price">

                                        <?= number_format(
                                            (float)$post['price'],
                                            0,
                                            '.',
                                            ','
                                        ) ?>

                                        FCFA

                                    </span>

                                </p>


                                <p class="product-detail">

                                    <strong>
                                        Location:
                                    </strong>

                                    <?= htmlspecialchars(
                                        $post['location'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>


                                <p class="product-description">

                                    <?= htmlspecialchars(
                                        $post['description'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>


                                <!--
                                    Only the product ID is passed.

                                    The price is NOT passed.
                                -->

                                <a
                                    href="<?= htmlspecialchars(
                                        $deliveryUrl,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    class="btn buy-btn"
                                >

                                    <i class="fas fa-shopping-cart"></i>

                                    Buy this bottle

                                </a>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="col-12">

                    <div class="empty-products">

                        <i class="fas fa-gas-pump"></i>

                        <h4>
                            No gas bottles available
                        </h4>

                        <p>
                            There are currently no gas products listed.
                            Please check again later.
                        </p>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =========================
     FOOTER
========================= -->

<footer>

    <div class="container text-center">

        <p class="text-white">

            Copyright &copy; 2026 Gas Hub.
            All rights reserved.

        </p>


        <ul>

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

    </div>

</footer>


<!-- Bootstrap JavaScript -->

<script src="https://code.jquery.com/jquery-3.6.4.slim.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>


</body>

</html>

<?php

mysqli_close($con);

?>