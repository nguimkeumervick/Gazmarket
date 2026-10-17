<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$loginError = '';
if (isset($_SESSION['seller_login_error'])) {
    $loginError = (string)$_SESSION['seller_login_error'];
    unset($_SESSION['seller_login_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seller Login · Gas Hub</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="./font/css/all.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">

  <style>
    :root {
      --gh-navy: #071a2f;
      --gh-gold: #f4b41a;
      --gh-gold-dark: #d99d00;
      --gh-bg: #f5f7fa;
    }
    body { font-family: 'Inter', sans-serif; background: var(--gh-bg); color: #1f2937; }
    h1 { font-family: 'Poppins', sans-serif; }
    a, a:hover { text-decoration: none !important; }

    .nob { background: var(--gh-navy); }
    .not { height: 50px; }

    #post {
      background-image: linear-gradient(135deg, rgba(7,26,47,.88), rgba(7,26,47,.6)), url(image/q2.jpg);
      background-repeat: no-repeat;
      background-size: cover;
      background-position: center;
      padding-top: 6em;
      padding-bottom: 3em;
      min-height: calc(100vh - 120px);
    }

    .card { width: 460px; max-width: 100%; margin: auto; border: none; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,.2); }

    .btn-brand { background: var(--gh-gold); color: var(--gh-navy); font-weight: 700; border: none; }
    .btn-brand:hover { background: var(--gh-gold-dark); color: var(--gh-navy); }

    footer { background: var(--gh-navy); }
    footer a { color: rgba(255,255,255,.7); }
    footer a:hover { color: #fff; }
  </style>
</head>
<body>

  <div class="container-fluid nob">
    <div class="row py-2">
      <div class="col-sm-8">
        <p class="text-white" style="opacity:.85"><img src="image/logo3.PNG" class="not" alt="Gas Hub"> Gas Hub Seller Portal</p>
      </div>
      <div class="col-4 text-right">
        <a href="home page for gas.php" class="text-white" style="opacity:.85">Home</a>
      </div>
    </div>
  </div>

  <div class="container-fluid" id="post">
    <div class="container">  
      <div class="card">
        <div class="card-body p-4 p-md-5">
          <h1 class="h3 mb-1">Seller Login</h1>
          <p class="text-muted mb-4">Access your seller dashboard to manage your listings.</p>

          <?php if ($loginError !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($loginError) ?></div>
          <?php endif; ?>

          <form action="seller log.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
              <label for="email">Email address</label>
              <input type="email" id="email" required name="email" class="form-control" placeholder="you@example.com" maxlength="150">
            </div>

            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" required name="password" class="form-control" placeholder="password">
            </div>

            <button class="btn btn-brand btn-block mt-3">Log in</button>
          </form>

          <p class="text-center text-muted mt-4 mb-0">
            Not a seller yet? <a href="Become a seller.php" style="color:var(--gh-gold-dark);font-weight:600;">Register here</a>
          </p>
        </div>
      </div>
    </div>
  </div>

  <footer class="py-4">
    <div class="container-fluid text-center">
      <p class="text-white mb-0">Copyright &copy; 2026 Gas Hub. All rights reserved.</p>
    </div>
  </footer>

</body>
</html>