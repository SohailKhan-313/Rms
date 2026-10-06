<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['email']);
$currentScript = basename($_SERVER['PHP_SELF']);

// 1. GLOBAL ACCESS CONTROL:
// All administrative and report pages require an active login session.
// Only the POS terminal (new.php), login, and register pages can be viewed unauthenticated.
$publicScripts = ['new.php', 'login.php', 'register.php'];
if (!$isLoggedIn && !in_array($currentScript, $publicScripts)) {
    header("Location: /RMS/views/user/login.php?auth_required=1");
    exit();
}

// 2. KIOSK / POS SCREEN MODE:
// Default to kiosk mode when on new.php unless explicitly disabled by logged-in admin
if (!isset($isKioskMode)) {
    $isKioskMode = ($currentScript === 'new.php' && (!isset($_GET['admin_sidebar']) || empty($_SESSION['email'])));
}

$navUser = $_SESSION['name'] ?? ($isLoggedIn ? 'Admin' : 'Staff Cashier');
$navEmail = $_SESSION['email'] ?? ($isLoggedIn ? 'skpattan850911@gmail.com' : 'pos@restaurant.local');
?>
<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | RMS' : 'RMS | Restaurant Management System' ?></title>
    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#4f46e5" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->
    <!--begin::Primary Meta Tags-->
    <meta name="title" content="RMS | Restaurant Management System" />
    <meta name="description" content="Professional Restaurant Management System & POS Terminal" />
    <!--end::Primary Meta Tags-->
    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="/RMS/public/assets/css/adminlte.css" as="style" />
    <!--end::Accessibility Features-->
    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
      media="print"
      onload="this.media='all'"
    />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="/RMS/public/assets/css/adminlte.css" />
    <!--end::Required Plugin(AdminLTE)-->
    <!-- RMS Custom Design System & POS Styles -->
    <link rel="stylesheet" href="/RMS/public/assets/css/style.css" />
    <link rel="stylesheet" href="/RMS/public/assets/css/pos.css" />
    <!-- apexcharts -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
      integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0="
      crossorigin="anonymous"
    />
    <!-- jsvectormap -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
      integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4="
      crossorigin="anonymous"
    />
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <?php if (!empty($isKioskMode)): ?>
  <body class="pos-kiosk-body bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
  <?php else: ?>
  <body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body shadow-sm">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav align-items-center">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" title="Toggle Sidebar">
                <i class="bi bi-list fs-5"></i>
              </a>
            </li>
            <li class="nav-item d-none d-sm-block">
              <a href="/RMS/public/index.php" class="nav-link fw-semibold">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
              </a>
            </li>
            <li class="nav-item d-none d-sm-block">
              <a href="/RMS/views/order/new.php" class="nav-link fw-semibold text-primary">
                <i class="bi bi-cart-check-fill me-1"></i> POS Screen
              </a>
            </li>
            <li class="nav-item d-none d-lg-block">
              <a href="/RMS/views/menu/All_menus.php" class="nav-link text-secondary">
                <i class="bi bi-card-list me-1"></i> Menus
              </a>
            </li>
          </ul>
          <!--end::Start Navbar Links-->

          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto align-items-center gap-2">
            <!--begin::WhatsApp Contact Icon-->
            <li class="nav-item">
              <a class="nav-link rms-nav-contact-icon whatsapp-icon-btn" href="https://wa.me/9234702320579" target="_blank" rel="noopener noreferrer" title="WhatsApp: 034702320579" aria-label="WhatsApp 034702320579">
                <i class="bi bi-whatsapp"></i>
              </a>
            </li>
            <!--end::WhatsApp Contact Icon-->

            <!--begin::Email Contact Icon-->
            <li class="nav-item">
              <a class="nav-link rms-nav-contact-icon email-icon-btn" href="mailto:skpattan850911@gmail.com" title="Email: skpattan850911@gmail.com" aria-label="Email skpattan850911@gmail.com">
                <i class="bi bi-envelope-at-fill"></i>
              </a>
            </li>
            <!--end::Email Contact Icon-->

            <!--begin::Fullscreen Toggle-->
            <li class="nav-item">
              <a class="nav-link" href="#" data-lte-toggle="fullscreen" title="Full Screen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
              </a>
            </li>
            <!--end::Fullscreen Toggle-->

            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu ms-1">
              <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <img
                  src="/RMS/public/assets/images/avatar2.png"
                  class="user-image rounded-circle shadow-sm"
                  alt="User Image"
                  style="width: 32px; height: 32px; object-fit: cover;"
                />
                <span class="d-none d-md-inline fw-semibold"><?= htmlspecialchars($navUser) ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow border-0 rounded-3">
                <!--begin::User Image-->
                <li class="user-header text-bg-primary py-3 rounded-top text-center">
                  <img
                    src="/RMS/public/assets/images/avatar2.png"
                    class="rounded-circle shadow-sm mb-2"
                    alt="User Image"
                    style="width: 64px; height: 64px; object-fit: cover; background: #fff;"
                  />
                  <p class="mb-0 fw-bold"><?= htmlspecialchars($navUser) ?></p>
                  <small class="text-white-50"><?= htmlspecialchars($navEmail) ?></small>
                </li>
                <!--end::User Image-->
                <!--begin::Menu Body-->
                <li class="user-body p-2">
                  <a href="/RMS/views/order/new.php" class="dropdown-item py-2 rounded">
                    <i class="bi bi-cart3 me-2 text-primary"></i> Open POS Screen
                  </a>
                  <a href="/RMS/views/report/daily.php" class="dropdown-item py-2 rounded">
                    <i class="bi bi-graph-up me-2 text-success"></i> Sales & Profit Report
                  </a>
                  <a href="/RMS/views/report/daily_expenses.php" class="dropdown-item py-2 rounded">
                    <i class="bi bi-wallet2 me-2 text-warning"></i> Expenses & Audit
                  </a>
                </li>
                <!--end::Menu Body-->
                <!--begin::Menu Footer-->
                <li class="user-footer border-top p-2">
                  <a href="/RMS/views/user/logout.php" class="btn btn-outline-danger btn-sm w-100 fw-bold">
                    <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                  </a>
                </li>
                <!--end::Menu Footer-->
              </ul>
            </li>
            <!--end::User Menu Dropdown-->
          </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->
  <?php endif; ?>