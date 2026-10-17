
<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("location:login.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Bottle | Gas Hub</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./font/css/all.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #f4f6f9;
            color: #1f2937;
        }

        h1, h2, h3 { font-family: 'Poppins', sans-serif; }
        a { text-decoration: none !important; }

        /* NAV */
        .topbar { background: #0b1220; padding: 12px 0; }
        .logo { height: 50px; width: auto; }
        .contact { color: #ffffff; font-size: 13px; margin-left: 15px; }
        .contact img { height: 18px; vertical-align: middle; margin-right: 5px; }
        .nav-links { display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap; gap: 5px; }
        .nav-links a { color: #ffffff; padding: 9px 12px; border-radius: 7px; font-size: 14px; }
        .nav-links a:hover { background: rgba(255,255,255,0.12); }
        .logout { border: 1px solid #ffffff; background: none; cursor: pointer; }
        .logout:hover { background: #dc3545 !important; border-color: #dc3545; }

        /* SPLIT LAYOUT */
        .create-wrap {
            display: flex;
            min-height: calc(100vh - 78px);
            flex-wrap: wrap;
        }

        .brand-panel {
            flex: 1 1 380px;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 15% 20%, rgba(37,99,235,0.55), transparent 45%),
                radial-gradient(circle at 85% 75%, rgba(249,115,22,0.45), transparent 50%),
                linear-gradient(160deg, #0b1220 0%, #101828 55%, #14213d 100%);
            color: #fff;
            padding: 60px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-panel .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: .5;
        }
        .brand-panel .blob-1 { width: 260px; height: 260px; background: #2563eb; top: -60px; right: -60px; }
        .brand-panel .blob-2 { width: 220px; height: 220px; background: #f97316; bottom: -50px; left: -40px; }

        .brand-panel .content { position: relative; z-index: 2; }

        .brand-panel .badge-step {
            display: inline-block;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            letter-spacing: .5px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .brand-panel h1 { font-size: 32px; font-weight: 700; margin-bottom: 14px; line-height: 1.25; }
        .brand-panel p.lead-text { color: rgba(255,255,255,0.75); font-size: 15px; margin-bottom: 30px; }

        .benefit-list { list-style: none; padding: 0; margin: 0; }
        .benefit-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 18px;
            font-size: 14px;
            color: rgba(255,255,255,0.85);
        }
        .benefit-list li i {
            background: rgba(37,99,235,0.25);
            color: #93c5fd;
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* FORM PANEL */
        .form-panel {
            flex: 1 1 480px;
            background: #ffffff;
            padding: 60px 45px;
            display: flex;
            align-items: center;
        }

        .form-panel-inner { width: 100%; max-width: 460px; margin: auto; }

        .form-panel-inner h2 { font-size: 24px; margin-bottom: 6px; }
        .form-panel-inner p.subtitle { color: #6b7280; font-size: 14px; margin-bottom: 28px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 8px; }

        .form-control {
            height: 46px;
            border-radius: 8px;
            border: 1px solid #d6dbe3;
        }
        textarea.form-control { height: auto; min-height: 110px; resize: vertical; }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }

        .upload-area {
            border: 2px dashed #cbd5e1;
            background: #f8fafc;
            padding: 18px;
            border-radius: 10px;
            text-align: center;
        }
        .upload-area:hover { border-color: #2563eb; }
        .upload-icon { font-size: 22px; color: #2563eb; margin-bottom: 6px; }

        .submit-btn {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: 0.2s;
        }
        .submit-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(37,99,235,0.35); }

        .alert-danger {
            background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;
            padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 14px;
        }

        /* FOOTER */
        footer { background: #0b1220; color: white; padding: 30px 15px; }
        footer ul { list-style: none; padding: 0; margin: 12px 0 0; }
        footer li { display: inline-block; margin: 0 12px; }
        footer a { color: rgba(255,255,255,0.7); font-size: 13px; }
        footer a:hover { color: white; }
        .copyright { margin-top: 20px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; color: rgba(255,255,255,0.5); }

        @media (max-width: 991px) {
            .brand-panel, .form-panel { padding: 40px 28px; }
        }
        @media (max-width: 767px) {
            .contact { margin-left: 0; margin-top: 10px; }
            .nav-links { justify-content: center; margin-top: 12px; }
            footer li { display: block; margin: 8px 0; }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="d-flex align-items-center flex-wrap">
                    <a href="home page for gas.php">
                        <img src="image/logo3.PNG" class="logo" alt="Gas Hub Logo">
                    </a>
                    <div class="contact">
                        <span class="mr-3"><img src="image/z1.png" alt="">(+237) 681332408</span>
                        <span><img src="image/z2.png" alt="">Contact@teampress.com</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <nav class="nav-links">
                    <a href="home page for gas.php"><i class="fas fa-home"></i> Home</a>
                    <a href="Become a seller.php"><i class="fas fa-store"></i> Become a seller</a>
                    <a href="Buy a bottle.php"><i class="fas fa-shopping-cart"></i> Buy a bottle</a>
                    <form action="logout.php" method="POST" class="d-inline">
                        <button type="submit" class="logout" style="color:#fff;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </nav>
            </div>
        </div>
    </div>
</header>

<div class="create-wrap">

    <div class="brand-panel">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>

        <div class="content">
            <span class="badge-step"><i class="fas fa-gas-pump"></i> Seller · New listing</span>
            <h1>Get your gas bottle in front of buyers near you</h1>
            <p class="lead-text">A complete, honest listing sells faster. Fill in the details on the right — it only takes a minute.</p>

            <ul class="benefit-list">
                <li><i class="fas fa-eye"></i> Seen instantly on the marketplace once submitted</li>
                <li><i class="fas fa-shield-alt"></i> Your listing is tied to your verified seller account</li>
                <li><i class="fas fa-bolt"></i> No fees to post — start selling right away</li>
            </ul>
        </div>
    </div>

    <div class="form-panel">
        <div class="form-panel-inner">
            <h2>Create a Gas Bottle Listing</h2>
            <p class="subtitle">Add the details of the gas bottle you want to sell.</p>

            <?php if (!empty($_SESSION['create_error'])): ?>
                <div class="alert-danger"><?= htmlspecialchars($_SESSION['create_error']) ?></div>
                <?php unset($_SESSION['create_error']); ?>
            <?php endif; ?>

            <!-- KEEP THIS ACTION AS create.php -->
            <form method="POST" action="create.php" enctype="multipart/form-data">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="form-group">
                    <label for="name">Bottle Name</label>
                    <input type="text" id="name" name="name" class="form-control"
                        placeholder="Example: Total 12.5kg Gas Bottle" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="price">Price (CFA)</label>
                    <input type="number" id="price" name="price" class="form-control"
                        placeholder="Example: 6500" min="0" required>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="form-control"
                        placeholder="Example: Biyem-Assi, Yaoundé" maxlength="150" required>
                </div>

                <div class="form-group">
                    <label for="description">Bottle Description</label>
                    <textarea id="description" name="description" class="form-control"
                        placeholder="Describe the bottle condition, weight, size, etc."
                        rows="4" maxlength="500" required></textarea>
                </div>

                <div class="form-group">
                    <label for="img">Bottle Image</label>
                    <div class="upload-area">
                        <div class="upload-icon"><i class="fas fa-camera"></i></div>
                        <p class="mb-2" style="font-size:13px;">Clear photo, JPG/PNG/WEBP, up to 2MB.</p>
                        <input type="file" id="img" name="img" class="form-control-file"
                            accept="image/png, image/jpeg, image/webp" required>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-plus-circle"></i> Create Listing
                </button>

            </form>
        </div>
    </div>

</div>

<footer>
    <div class="container text-center">
        <h5>Gas Hub</h5>
        <p>A convenient platform for buying and selling gas bottles.</p>
        <ul>
            <li><a href="#">About Us</a></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href="#">Terms of Service</a></li>
            <li><a href="#">Privacy Policy</a></li>
        </ul>
        <div class="copyright">Copyright &copy; 2026 Gas Hub. All rights reserved.</div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>