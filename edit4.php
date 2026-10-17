

<?php

session_start();
include "config.php";

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS PROTECTION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['username']) || $_SESSION['username'] === '') {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| GAS HUB ADMIN - EDIT BOTTLE
|--------------------------------------------------------------------------
*/


// =====================================================
// GET BOTTLE ID
// =====================================================

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if ($id === false || $id === null || $id <= 0) {
    die("Invalid bottle ID.");
}


// =====================================================
// GET BOTTLE INFORMATION
// =====================================================

$stmt = $conn->prepare("
    SELECT id, name, price, location, img, description
    FROM element
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Unable to prepare bottle query.");
}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    $stmt->close();
    die("Unable to retrieve bottle information.");
}

$result = $stmt->get_result();
$bottle = $result->fetch_assoc();
$stmt->close();


// =====================================================
// CHECK BOTTLE EXISTS
// =====================================================

if (!$bottle) {
    die("The selected gas bottle was not found.");
}


// =====================================================
// INITIAL VALUES
// =====================================================

$name = $bottle['name'];
$price = $bottle['price'];
$location = $bottle['location'];
$oldImage = (string)$bottle['img'];
$description = (string)$bottle['description'];

$error = "";


// =====================================================
// PROCESS UPDATE
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = (string)($_POST['csrf_token'] ?? '');

    if (empty($_SESSION['admin_csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $submittedToken)) {

        $error = "Invalid security token. Please reload the page and try again.";

    } else {

        $name = trim($_POST['name'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');


        // =================================================
        // VALIDATION
        // =================================================

        if ($name === '' || $price === '' || $location === '') {

            $error = "Please complete all required fields.";

        } elseif (!is_numeric($price)) {

            $error = "Please enter a valid price.";

        } elseif ((float)$price <= 0) {

            $error = "The price must be greater than zero.";

        } elseif (mb_strlen($name) > 150) {

            $error = "The bottle name is too long.";

        } elseif (mb_strlen($location) > 150) {

            $error = "The location is too long.";

        } elseif (mb_strlen($description) > 2000) {

            $error = "The description is too long.";

        }


        // =================================================
        // IMAGE (upload only; the old image is removed
        // AFTER the database update succeeds)
        // =================================================

        $newImage = $oldImage;
        $uploadedPath = null;

        if ($error === "" && isset($_FILES['img']) && $_FILES['img']['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES['img']['error'] !== UPLOAD_ERR_OK) {

                $error = "There was a problem uploading the image.";

            } else {

                $fileSize = $_FILES['img']['size'];

                if ($fileSize > 5 * 1024 * 1024) {

                    $error = "The image must not exceed 5 MB.";

                } else {

                    $tmpName = $_FILES['img']['tmp_name'];
                    $originalName = $_FILES['img']['name'];
                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                    if (!in_array($extension, $allowedExtensions, true)) {

                        $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

                    } else {

                        $imageInfo = getimagesize($tmpName);
                        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

                        if ($imageInfo === false || !in_array($imageInfo[2], $allowedTypes, true)) {

                            $error = "The uploaded file is not a valid JPG, PNG or WEBP image.";

                        } else {

                            $imageDirectory = "../images/";

                            if (!is_dir($imageDirectory)) {
                                if (!mkdir($imageDirectory, 0755, true)) {
                                    $error = "Unable to create the images directory.";
                                }
                            }

                            if ($error === "") {

                                $newFileName = 'gas_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
                                $destination = $imageDirectory . $newFileName;

                                if (!move_uploaded_file($tmpName, $destination)) {

                                    $error = "Unable to save the uploaded image.";

                                } else {

                                    $newImage = 'images/' . $newFileName;
                                    $uploadedPath = $destination;
                                }
                            }
                        }
                    }
                }
            }
        }


        // =================================================
        // UPDATE DATABASE
        // =================================================

        if ($error === "") {

            $updateStmt = $conn->prepare("
                UPDATE element
                SET name = ?, price = ?, location = ?, img = ?, description = ?
                WHERE id = ?
            ");

            if (!$updateStmt) {

                $error = "Unable to prepare the update.";

                // Remove the new file we just uploaded, nothing was saved
                if ($uploadedPath !== null && file_exists($uploadedPath)) {
                    unlink($uploadedPath);
                }

            } else {

                $priceValue = (float)$price;

                $updateStmt->bind_param(
                    "sdsssi",
                    $name,
                    $priceValue,
                    $location,
                    $newImage,
                    $description,
                    $id
                );

                if ($updateStmt->execute()) {

                    $updateStmt->close();

                    // The database is updated: now it is safe to remove
                    // the old image, but only if no other listing uses it.
                    if ($uploadedPath !== null && $oldImage !== '' && $oldImage !== $newImage) {

                        $oldRelative = str_replace('\\', '/', $oldImage);

                        if (strpos($oldRelative, 'images/') === 0 && strpos($oldRelative, '..') === false) {

                            $usedStmt = $conn->prepare("SELECT COUNT(*) FROM element WHERE img = ? AND id <> ?");

                            if ($usedStmt) {

                                $usedStmt->bind_param("si", $oldImage, $id);
                                $usedStmt->execute();
                                $usedStmt->bind_result($usedCount);
                                $usedStmt->fetch();
                                $usedStmt->close();

                                $oldImagePath = '../' . $oldRelative;

                                if ((int)$usedCount === 0 && file_exists($oldImagePath)) {
                                    unlink($oldImagePath);
                                }
                            }
                        }
                    }

                    $conn->close();

                    header("Location: list%20of%20bottles.php?updated=1");
                    exit;

                } else {

                    $error = "Unable to update the bottle. Please try again.";
                    $updateStmt->close();

                    // Remove the new file we just uploaded, nothing was saved
                    if ($uploadedPath !== null && file_exists($uploadedPath)) {
                        unlink($uploadedPath);
                    }
                }
            }
        }
    }
}


// =====================================================
// CURRENT IMAGE PATH
// =====================================================

$currentImage = trim($oldImage);
$currentImagePath = "";

if ($currentImage !== "") {

    $currentImage = str_replace('\\', '/', $currentImage);
    $currentImage = preg_replace('#^\./#', '', $currentImage);

    if (strpos($currentImage, 'images/') === 0) {
        $currentImagePath = '../' . $currentImage;
    } else {
        $currentImagePath = '../images/' . $currentImage;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bottle · Gas Hub Admin</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
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

        .edit-wrapper {
            display: grid;
            grid-template-columns: 1fr 330px;
            gap: 25px;
            align-items: start;
        }

        .card-box {
            background: white;
            border: 1px solid var(--gh-border);
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, .05);
        }

        .card-header-custom {
            padding: 20px 24px;
            border-bottom: 1px solid #edf0f4;
        }

        .card-header-custom h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        .card-header-custom p {
            margin: 4px 0 0;
            font-size: 12px;
            color: #8a93a2;
        }

        .card-body-custom { padding: 25px; }

        .form-label-custom {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 7px;
        }

        .required { color: #dc3545; }

        .form-control {
            height: 45px;
            border: 1px solid #dfe3e8;
            border-radius: 7px;
            font-size: 14px;
            box-shadow: none !important;
        }

        .form-control:focus {
            border-color: var(--gh-gold-dark);
            box-shadow: 0 0 0 3px rgba(244, 180, 26, .15) !important;
        }

        textarea.form-control {
            height: 125px;
            resize: vertical;
            padding-top: 12px;
        }

        .field-help {
            display: block;
            font-size: 11px;
            color: #8a93a2;
            margin-top: 5px;
        }

        .form-row-custom {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .image-preview-box { padding: 25px; text-align: center; }

        .image-preview {
            width: 100%;
            height: 230px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e1e5ea;
            background: #f7f8fa;
        }

        .image-placeholder {
            width: 100%;
            height: 230px;
            background: #f1f4f8;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #9aa3b2;
        }

        .image-placeholder i { font-size: 35px; margin-bottom: 10px; }

        .image-title {
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }

        .image-help {
            text-align: left;
            color: #8a93a2;
            font-size: 11px;
            line-height: 1.5;
            margin-top: 10px;
        }

        .file-input { margin-top: 15px; width: 100%; font-size: 12px; }

        .info-box {
            margin-top: 18px;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e8edf3;
            border-radius: 8px;
            text-align: left;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 7px 0;
            font-size: 12px;
            border-bottom: 1px solid #e8edf3;
        }

        .info-row:last-child { border-bottom: none; }
        .info-label { color: #7b8494; }
        .info-value { font-weight: 600; color: #374151; }

        .form-actions {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #edf0f4;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-save {
            background: var(--gh-navy);
            color: white;
            border: none;
            border-radius: 7px;
            padding: 11px 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s ease;
        }

        .btn-save:hover { background: var(--gh-navy-light); color: white; }

        .btn-cancel {
            background: white;
            color: #4b5563;
            border: 1px solid #d9dee6;
            border-radius: 7px;
            padding: 10px 19px;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-cancel:hover { background: #f8fafc; color: #111827; }

        .custom-alert {
            border: none;
            border-left: 4px solid #dc3545;
            background: #fff5f5;
            color: #842029;
            font-size: 13px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        @media (max-width: 992px) {
            .sidebar { min-height: auto; }
            .edit-wrapper { grid-template-columns: 1fr; }
        }

        @media (max-width: 600px) {
            .main-content { padding: 18px; }
            .form-row-custom { grid-template-columns: 1fr; }
            .form-actions { flex-direction: column; }
            .btn-save, .btn-cancel { width: 100%; text-align: center; }
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
                    <h1>Edit Bottle Listing</h1>
                    <div class="welcome">Update the product information displayed on the marketplace</div>
                </div>
            </div>

            <?php if ($error !== ""): ?>
                <div class="alert custom-alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="edit-wrapper">

                    <div class="card-box">

                        <div class="card-header-custom">
                            <h5><i class="fas fa-sliders-h me-2"></i> Listing Information</h5>
                            <p>Modify the details of this gas bottle.</p>
                        </div>

                        <div class="card-body-custom">

                            <div class="mb-4">
                                <label class="form-label-custom">Listing ID</label>
                                <input type="text" class="form-control" value="<?= (int)$id; ?>" readonly style="background:#f8fafc;">
                                <small class="field-help">The listing ID cannot be changed.</small>
                            </div>

                            <div class="form-row-custom mb-4">

                                <div>
                                    <label class="form-label-custom">Bottle Name <span class="required">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Example: Total Gas 12.5kg" required>
                                </div>

                                <div>
                                    <label class="form-label-custom">Price (FCFA) <span class="required">*</span></label>
                                    <input type="number" name="price" class="form-control" value="<?= htmlspecialchars((string)$price, ENT_QUOTES, 'UTF-8'); ?>" min="1" step="1" placeholder="6500" required>
                                </div>

                            </div>

                            <div class="mb-4">
                                <label class="form-label-custom">Location <span class="required">*</span></label>
                                <input type="text" name="location" class="form-control" value="<?= htmlspecialchars((string)$location, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Example: Yaoundé, Cameroon" required>
                                <small class="field-help">Enter the location where this bottle is available.</small>
                            </div>

                            <div class="mb-2">
                                <label class="form-label-custom">Description</label>
                                <textarea name="description" class="form-control" placeholder="Enter information about this gas bottle..."><?= htmlspecialchars((string)$description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <small class="field-help">Provide useful information about the bottle for customers.</small>
                            </div>

                            <div class="form-actions">
                                <a href="list%20of%20bottles.php" class="btn-cancel"><i class="fas fa-arrow-left me-1"></i> Cancel</a>
                                <button type="submit" class="btn-save"><i class="fas fa-save me-1"></i> Save Changes</button>
                            </div>

                        </div>
                    </div>

                    <div>
                        <div class="card-box">

                            <div class="card-header-custom">
                                <h5><i class="fas fa-image me-2"></i> Product Image</h5>
                                <p>Manage the listing image.</p>
                            </div>

                            <div class="image-preview-box">

                                <div class="image-title">Current Image</div>

                                <?php if ($currentImagePath !== ""): ?>
                                    <img src="<?= htmlspecialchars($currentImagePath, ENT_QUOTES, 'UTF-8'); ?>" alt="Current gas bottle" class="image-preview">
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="fas fa-image"></i>
                                        <span>No image available</span>
                                    </div>
                                <?php endif; ?>

                                <div class="info-box">
                                    <div class="info-row">
                                        <span class="info-label">Listing ID</span>
                                        <span class="info-value">#<?= (int)$id; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Current Price</span>
                                        <span class="info-value"><?= number_format((float)$bottle['price'], 0, '.', ','); ?> FCFA</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Location</span>
                                        <span class="info-value"><?= htmlspecialchars((string)$bottle['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>

                                <div class="image-title mt-4">Replace Image</div>

                                <input type="file" name="img" class="file-input" accept=".jpg,.jpeg,.png,.webp">

                                <div class="image-help">
                                    <i class="fas fa-info-circle"></i>
                                    Select a new image only if you want to replace the current image.
                                    <br><br>
                                    Maximum size: <strong>5 MB</strong>
                                    <br>
                                    Supported: JPG, JPEG, PNG and WEBP.
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

            </form>

        </main>
    </div>
</div>

</body>
</html>