<?php
session_start();
include_once __DIR__ . '/../../config/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['email'])) {
    header("Location: /RMS/public/index.php");
    exit();
}

$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');

    if (empty($name) || empty($email) || empty($pass)) {
        $errorMsg = "Please fill in all required fields.";
    } elseif ($pass !== $confirmPass) {
        $errorMsg = "Passwords do not match. Please re-enter.";
    } elseif (strlen($pass) < 4) {
        $errorMsg = "Password must be at least 4 characters long.";
    } else {
        if ($conn) {
            // Auto-create login table if not exists
            $conn->query("CREATE TABLE IF NOT EXISTS `login` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `email` VARCHAR(120) NOT NULL UNIQUE,
                `pass` VARCHAR(255) NOT NULL,
                `name` VARCHAR(100) DEFAULT 'Staff Member',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $nameSafe = mysqli_real_escape_string($conn, $name);
            $emailSafe = mysqli_real_escape_string($conn, $email);
            $passSafe = mysqli_real_escape_string($conn, $pass);

            // Check if email already registered
            $checkSql = "SELECT `id` FROM `login` WHERE `email` = '$emailSafe' LIMIT 1";
            $checkRes = mysqli_query($conn, $checkSql);

            if ($checkRes && mysqli_num_rows($checkRes) > 0) {
                $errorMsg = "This email is already registered. Please sign in instead.";
            } else {
                $insertSql = "INSERT INTO `login` (`name`, `email`, `pass`) VALUES ('$nameSafe', '$emailSafe', '$passSafe')";
                if (mysqli_query($conn, $insertSql)) {
                    $_SESSION['email'] = $email;
                    $_SESSION['name'] = $name;
                    $_SESSION['pass'] = $pass;
                    header("Location: /RMS/public/index.php");
                    exit();
                } else {
                    $errorMsg = "Registration error: " . mysqli_error($conn);
                }
            }
        } else {
            // Demo fallback if DB is offline
            $_SESSION['email'] = $email;
            $_SESSION['name'] = $name;
            $_SESSION['pass'] = $pass;
            header("Location: /RMS/public/index.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | RMS - Restaurant Management System</title>
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
    .register-card {
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
      width: 100%;
      max-width: 480px;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .register-header {
      background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
      padding: 2rem 2rem;
      text-align: center;
      color: #ffffff;
    }
    .register-header-icon {
      width: 60px;
      height: 60px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      margin-bottom: 0.5rem;
      backdrop-filter: blur(8px);
    }
    .form-control:focus {
      border-color: #4f46e5;
      box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
    }
    .btn-register {
      background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
      color: #ffffff;
      font-weight: 700;
      padding: 0.75rem;
      border-radius: 10px;
      border: none;
      transition: all 0.2s ease;
    }
    .btn-register:hover {
      background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
      transform: translateY(-1px);
      box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
    }
  </style>
</head>
<body>

  <div class="register-card">
    <div class="register-header">
      <div class="register-header-icon">
        <i class="bi bi-person-badge"></i>
      </div>
      <h3 class="fw-bold mb-1">Create an Account</h3>
      <p class="text-white-50 small mb-0">Join RMS Restaurant Staff & Management</p>
    </div>

    <div class="p-4 p-sm-5">
      <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 small" role="alert">
          <i class="bi bi-exclamation-circle-fill"></i>
          <div><?= htmlspecialchars($errorMsg) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label fw-semibold text-secondary small">Full Name</label>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
            <input type="text" name="name" class="form-control" placeholder="John Doe" required autofocus value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold text-secondary small">Email Address</label>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="staff@restaurant.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold text-secondary small">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold text-secondary small">Confirm Password</label>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="bi bi-shield-check"></i></span>
            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
          </div>
        </div>

        <button type="submit" name="register" class="btn btn-register w-100 mb-3">
          <i class="bi bi-check2-circle me-1"></i> Complete Sign Up
        </button>

        <div class="text-center mt-3">
          <span class="text-muted small">Already have an account?</span>
          <a href="login.php" class="fw-bold text-primary text-decoration-none ms-1">Sign In here</a>
        </div>
      </form>
    </div>
  </div>

</body>
</html>
