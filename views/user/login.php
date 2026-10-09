<?php
session_start();
include_once __DIR__ . '/../../config/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['email'])) {
    header("Location: /RMS/public/index.php");
    exit();
}

$errorMsg = "";
$successMsg = "";

if (isset($_GET['registered'])) {
    $successMsg = "Account registered successfully! You can now log in.";
}

if (isset($_GET['auth_required'])) {
    $errorMsg = "Authentication Required: Please sign in to access administrative pages.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($pass)) {
        if ($conn) {
            // Auto-heal login table schema to guarantee compatibility
            @$conn->query("ALTER TABLE `login` MODIFY COLUMN `facebook_id` VARCHAR(150) NULL DEFAULT ''");
            @$conn->query("ALTER TABLE `login` MODIFY COLUMN `pass` VARCHAR(255) NOT NULL");
            @$conn->query("ALTER TABLE `login` MODIFY COLUMN `email` VARCHAR(120) NOT NULL");
            @$conn->query("ALTER TABLE `login` MODIFY COLUMN `name` VARCHAR(255) NULL DEFAULT 'Staff Member'");

            $emailSafe = mysqli_real_escape_string($conn, $email);

            // Case-insensitive & trimmed search
            $sql = "SELECT * FROM `login` WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$emailSafe')) LIMIT 1";
            $res = mysqli_query($conn, $sql);

            if ($res && mysqli_num_rows($res) === 1) {
                $user = mysqli_fetch_assoc($res);
                $storedPass = $user['pass'] ?? '';

                // Match against plaintext, trimmed, bcrypt password_hash, md5, or sha1
                $passMatch = ($pass === $storedPass)
                    || (trim($pass) === trim($storedPass))
                    || password_verify($pass, $storedPass)
                    || (md5($pass) === $storedPass)
                    || (sha1($pass) === $storedPass);

                if ($passMatch) {
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['name'] = !empty($user['name']) ? $user['name'] : 'Administrator';
                    $_SESSION['pass'] = $pass;
                    header("Location: /RMS/public/index.php");
                    exit();
                } else {
                    $errorMsg = "Incorrect password for " . htmlspecialchars($email) . ". Please double-check your password.";
                }
            } else {
                $errorMsg = "No account found for " . htmlspecialchars($email) . ". Please check the email spelling or create a new account.";
            }
        } else {
            // Demo fallback if DB is offline
            $_SESSION['email'] = $email;
            $_SESSION['name'] = 'Admin';
            $_SESSION['pass'] = $pass;
            header("Location: /RMS/public/index.php");
            exit();
        }
    } else {
        $errorMsg = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | RMS - Restaurant Management System</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/RMS/public/assets/css/style.css">
  <style>
    body {
      background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    .login-card {
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
      width: 100%;
      max-width: 440px;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .login-header {
      background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
      padding: 2.25rem 2rem;
      text-align: center;
      color: #ffffff;
    }
    .login-header-icon {
      width: 68px;
      height: 68px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin-bottom: 0.75rem;
      backdrop-filter: blur(8px);
    }
    .form-control:focus {
      border-color: #4f46e5;
      box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
    }
    .btn-login {
      background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
      color: #ffffff;
      font-weight: 700;
      padding: 0.75rem;
      border-radius: 10px;
      border: none;
      transition: all 0.2s ease;
    }
    .btn-login:hover {
      background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
      transform: translateY(-1px);
      box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
    }
    .btn-signup {
      border: 2px solid #4f46e5;
      color: #4f46e5;
      font-weight: 700;
      padding: 0.75rem;
      border-radius: 10px;
      background: transparent;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      text-decoration: none;
    }
    .btn-signup:hover {
      background: #eef2ff;
      color: #4338ca;
      transform: translateY(-1px);
    }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="login-header">
      <div class="login-header-icon">
        <i class="bi bi-shop-window"></i>
      </div>
      <h3 class="fw-bold mb-1">Welcome to RMS</h3>
      <p class="text-white-50 small mb-0">Restaurant Management & POS Terminal</p>
    </div>

    <div class="p-4 p-sm-5">
      <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 py-2 small" role="alert">
          <i class="bi bi-check-circle-fill"></i>
          <div><?= htmlspecialchars($successMsg) ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 small" role="alert">
          <i class="bi bi-exclamation-circle-fill"></i>
          <div><?= htmlspecialchars($errorMsg) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label fw-semibold text-secondary small">Email Address</label>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="admin@restaurant.com" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center">
            <label class="form-label fw-semibold text-secondary small">Password</label>
            <a href="#" class="small text-decoration-none text-muted" onclick="alert('Default password for SOHAIL@gmail.com is 123, and admin@rms.com is admin123.'); return false;">Forgot?</a>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
        </div>

        <button type="submit" name="login" class="btn btn-login w-100 mb-3">
          <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
        </button>

        <div class="d-flex align-items-center my-3">
          <hr class="flex-grow-1 text-muted">
          <span class="px-2 text-muted small fw-semibold">OR</span>
          <hr class="flex-grow-1 text-muted">
        </div>

        <!-- Prominent Sign Up Button -->
        <a href="register.php" class="btn btn-signup w-100">
          <i class="bi bi-person-plus-fill"></i> Create New Account (Sign Up)
        </a>
      </form>

      <div class="mt-3 p-2 bg-light rounded text-center small text-muted border">
        <span class="fw-semibold text-dark">Default Logins:</span> 
        <code>SOHAIL@gmail.com</code> (pass: <code>123</code>) or <code>admin@rms.com</code> (pass: <code>admin123</code>)
      </div>

      <div class="text-center mt-3 pt-3 border-top">
        <p class="text-muted small mb-2">Need to place or manage orders at the counter?</p>
        <a href="/RMS/views/order/new.php" class="btn btn-outline-dark btn-sm w-100 fw-semibold py-2">
          <i class="bi bi-cart3 me-1 text-primary"></i> Launch Standalone POS Terminal
        </a>
      </div>
    </div>
  </div>

</body>
</html>
