<?php
session_start();

// 🚫 Restrict access if user not logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['pass'])) {
    header("Location: /RMS/views/user/login.php?auth_required=1");
    exit();
}

$pageTitle = "Dashboard";
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../views/layouts/header.php';
include_once __DIR__ . '/../views/layouts/sidebar.php';
include_once __DIR__ . '/../views/layouts/main.php';
include_once __DIR__ . '/../views/layouts/footer.php';
?>