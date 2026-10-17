<?php
session_start();

// This page assumes a logged-in buyer (it shows a logout button), same pattern as home page for gas.php
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
  <meta name="description" content="Register as a seller on Gas Hub and start listing your gas cylinders for buyers near you.">
  <title>Become a Seller · Gas Hub</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="./font/css/all.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">

  <style>
    :root {
      --gh-navy: #101a2b;
      --gh-blue: #1d4ed8;
      --gh-orange: #f97316;
      --gh-bg: #f7f8fa;
    }

    body { font-family: 'Inter', sans-serif; color: #1f2733; background-color: var(--gh-bg); }
    h1, h4 { font-family: 'Poppins', sans-serif; }

    a { text-decoration: none; }
    a:hover, a:focus { text-decoration: none; }

    .nob { background-color: var(--gh-navy); }
    .nob a { transition: opacity .15s ease; }
    .nob a:hover { opacity: 1; color: #fff !important; }
    .not { height: 50px; }

    #post {
      background-image: linear-gradient(135deg, rgba(16,26,43,.85), rgba(16,26,43,.55)), url(image/q2.jpg);
      background-repeat: no-repeat;
      background-size: cover;
      background-position: center;
      padding-top: 6em;
      padding-bottom: 3em;
      min-height: calc(100vh - 120px);
    }

    .card {
      width: 600px;
      max-width: 100%;
      margin: auto;
      border: none;
      border-radius: 14px;
      box-shadow: 0 10px 30px rgba(0,0,0,.15);
    }

    .btn-brand {
      background-color: var(--gh-blue);
      color: #fff;
      font-weight: 600;
      border: none;
    }
    .btn-brand:hover { background-color: #1739ad; color: #fff; }

    footer { background-color: var(--gh-navy); }
    footer a { color: rgba(255,255,255,.7); }
    footer a:hover { color: #fff; }
    footer ul { list-style: none; padding-left: 0; margin-bottom: 0; }
    footer ul li { display: inline-block; margin-right: 1.5rem; }
  </style>
</head>
<body>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

  <div class="container-fluid nob">
    <div class="row">
      <div class="col-sm-8 pt-3">
        <p class="text-white" style="opacity:.85">  <img src="image/logo3.PNG" class="not" alt="Gas Hub"> contact us: <img src="image/z1.png" alt=""> (+237)681332408 &nbsp; <img src="image/z2.png" alt="">
          loicjohn729@gmail.com
        </p>
      </div>
      <div class="col-4 pt-3 text-right">
        <a href="home page for gas.php" class="text-white" style="opacity:.85">Home</a>
        <a href="Become a seller.php" class="text-white" style="opacity:.85">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Become a seller</a>
        <a href="Buy a bottle.php" class="text-white" style="opacity:.85">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Buy a bottle</a>
        &nbsp;&nbsp;
        <form action="logout.php" method="POST" class="d-inline">
          <button type="submit" class="btn btn-outline-success my-2 my-sm-0">logout</button>
        </form>
      </div>
    </div>
  </div>

  <div class="container-fluid" id="post">
    <div class="container">
      <div class="card">
        <div class="card-body p-4 p-md-5">
          <h1 class="h3 mb-1">Become a seller</h1>
          <p class="text-muted mb-4">Fill in the information below to start listing gas bottles.</p>

          <?php if (!empty($_SESSION['seller_error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['seller_error']) ?></div>
            <?php unset($_SESSION['seller_error']); ?>
          <?php endif; ?>

          <form action="seller.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
              <label for="name">Names</label>
              <input type="text" id="name" required name="name" class="form-control" placeholder="Names" maxlength="100">
            </div>

            <div class="form-group">
              <label for="email">Email address</label>
              <input type="email" id="email" required name="email" class="form-control" placeholder="address Email" maxlength="150">
            </div>

            <div class="form-group">
              <label for="phone">Phone number</label>
              <input type="tel" id="phone" required name="phone" class="form-control" placeholder="phone number" pattern="[0-9]{9}" title="Enter a 9-digit phone number">
            </div>

            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" required name="password" class="form-control" placeholder="password" minlength="8" autocomplete="new-password">
            </div>

            <center>
              <button class="btn btn-brand px-5 mt-3">submit</button>
            </center>
          </form>
        </div>
      </div>
    </div>
  </div>

  <footer class="py-4">
    <div class="container-fluid text-center">
      <p class="text-white">Copyright &copy; 2026 Gas Hub. All rights reserved.</p>
      <ul>
        <li><a href="#">About Us</a></li>
        <li><a href="#">Contact Us</a></li>
        <li><a href="#">Terms of Service</a></li>
        <li><a href="#">Privacy Policy</a></li>
      </ul>
    </div>
  </footer>

</body>
</html>