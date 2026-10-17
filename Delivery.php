
<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$con = mysqli_connect("localhost", "root", "", "projects");
if (!$con) {
    die("Database connection failed.");
}
mysqli_set_charset($con, "utf8mb4");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);

if (!$product_id || $product_id <= 0) {
    mysqli_close($con);
    header("Location: Buy a bottle.php");
    exit;
}

$stmt = mysqli_prepare(
    $con,
    "SELECT id, name, price, location, description, img
     FROM element
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to prepare product query.");
}

mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$product) {
    mysqli_close($con);
    header("Location: Buy a bottle.php");
    exit;
}

$selectedProductId   = (int)$product['id'];
$selectedBottle      = trim($product['name']);
$selectedLocation    = trim($product['location']);
$selectedPrice       = (float)$product['price'];
$selectedDescription = trim($product['description']);
$selectedImage       = trim($product['img']);

if ($selectedPrice < 0) {
    mysqli_close($con);
    die("Invalid product price.");
}

$formattedPrice = number_format($selectedPrice, 0, '.', ',');

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gas Hub delivery information">
    <title>Gas Hub | Delivery Information</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./font/css/all.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>

        :root {
            --gh-navy: #071a2f;
            --gh-navy-light: #0d2a47;
            --gh-gold: #f4b41a;
            --gh-gold-dark: #d99d00;
            --gh-bg: #f5f7fa;
            --gh-text: #1f2937;
            --gh-muted: #667085;
            --gh-border: #e5e7eb;
            --gh-success: #027a48;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            background: var(--gh-bg);
            color: var(--gh-text);
        }

        h1, h2, h3, h4, h5, h6 { font-family: 'Poppins', sans-serif; }

        a, a:hover, a:focus, a:active, a:visited {
            text-decoration: none !important;
            outline: none !important;
        }

        .topbar {
            background: var(--gh-navy);
            color: white;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .brand-logo { height: 48px; width: auto; object-fit: contain; }

        .contact-info { font-size: 13px; color: #d0d5dd; margin: 0; }
        .contact-info img { height: 18px; width: 18px; object-fit: contain; vertical-align: middle; margin: 0 5px; }

        .navbar-links { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 8px; }

        .navbar-links a {
            color: #fff !important;
            font-size: 14px;
            font-weight: 500;
            padding: 9px 13px;
            border-radius: 7px;
            transition: .25s ease;
        }

        .navbar-links a:hover { background: rgba(255,255,255,.08); color: var(--gh-gold) !important; }

        .logout-btn {
            border: 1px solid var(--gh-gold);
            color: var(--gh-gold) !important;
            background: none;
            cursor: pointer;
        }

        .logout-btn:hover { background: var(--gh-gold) !important; color: var(--gh-navy) !important; }

        .hero {
            background: linear-gradient(135deg, rgba(7,26,47,.98), rgba(13,42,71,.94));
            color: white;
            padding: 58px 0;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 330px;
            height: 330px;
            border-radius: 50%;
            background: rgba(244,180,26,.08);
            top: -150px;
            right: 8%;
        }

        .hero-content { position: relative; z-index: 2; }

        .hero-badge {
            display: inline-block;
            background: rgba(244,180,26,.13);
            color: var(--gh-gold);
            border: 1px solid rgba(244,180,26,.35);
            padding: 7px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 18px;
        }

        .hero h1 { font-size: 42px; font-weight: 800; line-height: 1.15; margin-bottom: 15px; }
        .hero p { color: #d0d5dd; font-size: 16px; max-width: 600px; margin-bottom: 0; line-height: 1.7; }

        .hero-image { max-height: 220px; max-width: 100%; width: auto; object-fit: contain; position: relative; z-index: 2; }

        .main-section { padding: 50px 0 75px; }

        .section-title { font-size: 25px; font-weight: 800; color: var(--gh-navy); margin-bottom: 5px; }
        .section-subtitle { color: var(--gh-muted); font-size: 14px; margin-bottom: 28px; }

        .summary-card {
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(7,26,47,.06);
            padding: 25px;
            height: 100%;
        }

        .summary-card h4 { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--gh-navy); }

        .selected-product {
            background: #f8fafc;
            border: 1px solid #eaecf0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .selected-product-image {
            height: 85px;
            width: 85px;
            object-fit: contain;
            background: #fff;
            border-radius: 9px;
            padding: 7px;
            border: 1px solid #eaecf0;
        }

        .selected-product-name { font-family: 'Poppins', sans-serif; font-size: 16px; font-weight: 700; color: var(--gh-navy); }
        .selected-product-location { font-size: 12px; color: var(--gh-muted); margin-top: 3px; }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 13px 0;
            border-bottom: 1px solid #eaecf0;
            font-size: 14px;
        }

        .summary-item:last-child { border-bottom: none; }
        .summary-label { color: var(--gh-muted); }
        .summary-value { font-weight: 600; color: var(--gh-navy); text-align: right; }
        .price-value { color: var(--gh-gold-dark); font-weight: 800; }

        .commission-note {
            margin-top: 20px;
            padding: 12px 14px;
            background: #fff8df;
            border: 1px solid #f2d477;
            border-radius: 10px;
            color: #725500;
            font-size: 12px;
            line-height: 1.6;
        }

        .secure-note {
            margin-top: 12px;
            padding: 12px 14px;
            background: #ecfdf3;
            border-radius: 10px;
            color: var(--gh-success);
            font-size: 12px;
            line-height: 1.6;
        }

        .form-card {
            background: #fff;
            border: 1px solid var(--gh-border);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(7,26,47,.06);
            padding: 30px;
        }

        .form-card h4 { font-size: 21px; font-weight: 800; color: var(--gh-navy); margin-bottom: 5px; }
        .form-description { color: var(--gh-muted); font-size: 13px; margin-bottom: 25px; }

        .form-group label { font-size: 13px; font-weight: 600; color: #344054; margin-bottom: 7px; }

        .form-control {
            height: 48px;
            border: 1px solid #d0d5dd;
            border-radius: 9px;
            padding: 0 14px;
            font-size: 14px;
            transition: .2s;
        }

        .form-control:focus { border-color: var(--gh-gold); box-shadow: 0 0 0 3px rgba(244,180,26,.15); }

        .input-icon { position: relative; }
        .input-icon i { position: absolute; left: 14px; top: 17px; color: #98a2b3; z-index: 3; }
        .input-icon .form-control { padding-left: 40px; }

        .readonly-field { background: #f8fafc !important; color: #344054; font-weight: 600; }
        .price-field { background: #fff8df !important; color: #725500; font-weight: 800; }
        .quantity-help { color: #98a2b3; font-size: 11px; margin-top: 5px; }

        .submit-btn {
            width: 100%;
            min-height: 50px;
            border: none;
            border-radius: 9px;
            background: var(--gh-gold);
            color: var(--gh-navy);
            font-weight: 800;
            font-size: 14px;
            transition: .25s ease;
        }

        .submit-btn:hover { background: var(--gh-gold-dark); transform: translateY(-1px); box-shadow: 0 8px 18px rgba(244,180,26,.25); }

        .form-note { text-align: center; color: #98a2b3; font-size: 11px; margin-top: 14px; margin-bottom: 0; line-height: 1.5; }

        footer { background: var(--gh-navy); color: white; padding: 38px 0; }
        .footer-brand { font-size: 18px; font-weight: 800; margin-bottom: 8px; }
        .footer-text { color: #98a2b3; font-size: 12px; margin-bottom: 0; }

        .footer-links { list-style: none; padding: 0; margin: 0; text-align: right; }
        .footer-links li { display: inline-block; margin-left: 20px; }
        .footer-links a { color: #d0d5dd !important; font-size: 12px; }
        .footer-links a:hover { color: var(--gh-gold) !important; }

        @media (max-width: 991px) {
            .navbar-links { justify-content: flex-start; margin-top: 12px; }
            .hero h1 { font-size: 34px; }
            .hero-image { margin-top: 30px; max-height: 180px; }
            .footer-links { text-align: left; margin-top: 20px; }
            .footer-links li { margin-left: 0; margin-right: 20px; }
        }

        @media (max-width: 575px) {
            .hero { padding: 42px 0; }
            .hero h1 { font-size: 29px; }
            .form-card, .summary-card { padding: 20px; }
            .navbar-links a { padding: 7px 9px; font-size: 12px; }
        }

    </style>

</head>

<body>

<div class="topbar">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-lg-6">
                <div class="d-flex align-items-center">
                    <img src="image/logo3.PNG" class="brand-logo mr-3" alt="Gas Hub Logo">
                    <p class="contact-info">
                        Contact us:
                        <img src="image/z1.png" alt="Phone"> (+237) 681332408
                        <img src="image/z2.png" alt="Email"> Contact@teampress.com
                    </p>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="navbar-links">
                    <a href="home page for gas.php"><i class="fas fa-home"></i> Home</a>
                    <a href="Become a seller.php"><i class="fas fa-store"></i> Become a seller</a>
                    <a href="Buy a bottle.php"><i class="fas fa-shopping-cart"></i> Buy a bottle</a>
                    <form action="logout.php" method="POST" class="d-inline">
                        <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 hero-content">
                <span class="hero-badge"><i class="fas fa-truck"></i> Delivery Information</span>
                <h1>Let's get your gas<br>delivered safely.</h1>
                <p>Please provide your delivery information below. We will use these details to process your order and prepare it for payment.</p>
            </div>
            <div class="col-lg-4 text-center">
                <img src="image/g1.png" class="hero-image" alt="Gas cylinder">
            </div>
        </div>
    </div>
</section>

<section class="main-section">
    <div class="container">

        <div class="text-center mb-4">
            <h2 class="section-title">Complete Your Delivery</h2>
            <p class="section-subtitle">Enter your information carefully to avoid delivery delays.</p>
        </div>

        <div class="row">

            <div class="col-lg-4 mb-4">
                <div class="summary-card">

                    <h4><i class="fas fa-receipt mr-2"></i> Selected Product</h4>

                    <div class="selected-product">
                        <div class="d-flex align-items-center">
                            <img src="images/<?= htmlspecialchars($selectedImage, ENT_QUOTES, 'UTF-8') ?>"
                                 class="selected-product-image mr-3"
                                 alt="<?= htmlspecialchars($selectedBottle, ENT_QUOTES, 'UTF-8') ?>"
                                 onerror="this.src='image/logo3.PNG';">
                            <div>
                                <div class="selected-product-name"><?= htmlspecialchars($selectedBottle, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="selected-product-location">
                                    <i class="fas fa-map-marker-alt mr-1"></i> Seller location:
                                    <?= htmlspecialchars($selectedLocation, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Bottle</span>
                        <span class="summary-value"><?= htmlspecialchars($selectedBottle, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Unit Price</span>
                        <span class="summary-value price-value"><?= $formattedPrice ?> FCFA</span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Seller Location</span>
                        <span class="summary-value"><?= htmlspecialchars($selectedLocation, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="commission-note">
                        <i class="fas fa-info-circle mr-1"></i>
                        Gas Hub applies a <strong>5% commission</strong> to each completed sale.
                        The commission is calculated from the actual order value.
                    </div>

                    <div class="secure-note">
                        <i class="fas fa-shield-alt mr-2"></i>
                        The official product price comes directly from the Gas Hub database and is not editable by the customer.
                    </div>

                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="form-card">

                    <h4>Delivery Information</h4>
                    <p class="form-description">Please provide your correct contact and delivery details.</p>

                    <form action="Deliv.php" method="POST">

                        <input type="hidden" name="csrf_token"
                               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                        <input type="hidden" name="product_id" value="<?= $selectedProductId ?>">

                        <div class="form-group">
                            <label for="Name">Full Name</label>
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="Name" id="Name" class="form-control"
                                       placeholder="Enter your full name" autocomplete="name" maxlength="100" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" id="email" class="form-control"
                                       placeholder="you@example.com" autocomplete="email" maxlength="150" required>
                            </div>
                            <small class="quantity-help">Used only to send your payment confirmation and receipt.</small>
                        </div>

                        <div class="row">

                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="Address">Delivery Address</label>
                                    <div class="input-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <input type="text" name="Address" id="Address" class="form-control"
                                               placeholder="Enter your delivery address" autocomplete="street-address" maxlength="255" required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="City">City</label>
                                    <div class="input-icon">
                                        <i class="fas fa-city"></i>
                                        <input type="text" name="City" id="City" class="form-control"
                                               placeholder="City" autocomplete="address-level2" maxlength="100" required>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <div class="input-icon">
                                <i class="fas fa-phone"></i>
                                <input type="tel" name="phone" id="phone" class="form-control"
                                       placeholder="e.g. 681 332 408" autocomplete="tel" maxlength="20" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="font-weight-bold mb-3"><i class="fas fa-gas-pump mr-2"></i> Gas Order Details</h6>

                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bottle_name">Selected Bottle</label>
                                    <input type="text" name="bottle_name" id="bottle_name" class="form-control readonly-field"
                                           value="<?= htmlspecialchars($selectedBottle, ENT_QUOTES, 'UTF-8') ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="Gas_size">Gas Size</label>
                                    <input type="text" name="Gas_size" id="Gas_size" class="form-control"
                                           placeholder="e.g. 12.5kg" maxlength="50" required>
                                    <small class="quantity-help">Enter the size indicated by the seller.</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="Quantity">Quantity</label>
                                    <input type="number" name="Quantity" id="Quantity" class="form-control"
                                           placeholder="Enter quantity" min="1" max="50" step="1" required>
                                    <small class="quantity-help">Example: 1 bottle, 2 bottles, etc.</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="AmountDisplay">Official Unit Price</label>
                                    <input type="text" id="AmountDisplay" class="form-control price-field"
                                           value="<?= $formattedPrice ?> FCFA" readonly>
                                </div>
                            </div>

                        </div>

                        <button type="submit" class="submit-btn"><i class="fas fa-arrow-right mr-2"></i> Continue to Payment</button>

                        <p class="form-note">
                            Your order total and Gas Hub commission will be calculated securely by the server before payment processing.
                        </p>

                    </form>

                </div>
            </div>

        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="footer-brand">Gas Hub</div>
                <p class="footer-text">© 2026 Gas Hub. All rights reserved.</p>
            </div>
            <div class="col-md-6">
                <ul class="footer-links">
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.4.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

</body>
</html>

<?php
mysqli_close($con);
?>