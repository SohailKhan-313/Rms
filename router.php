<?php
/**
 * RMS Built-in Web Server Router
 * Provides high-speed zero-dependency routing for Nixpacks / local PHP server.
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 1. Root redirect to application entry point
if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    header("Location: /RMS/public/index.php");
    exit();
}

// 2. Map /RMS/... requests to local repository root
if (strpos($uri, '/RMS/') === 0) {
    $subPath = substr($uri, 4);
    $realPath = __DIR__ . $subPath;

    if (is_file($realPath)) {
        // If it's a PHP file, execute it
        if (substr($realPath, -4) === '.php') {
            include $realPath;
            exit();
        }
        // Let built-in server serve static assets (CSS, JS, images, PDF)
        return false;
    }

    if (is_dir($realPath) && file_exists($realPath . '/index.php')) {
        include $realPath . '/index.php';
        exit();
    }
}

// 3. Direct file check
$filePath = __DIR__ . $uri;
if (is_file($filePath)) {
    if (substr($filePath, -4) === '.php') {
        include $filePath;
        exit();
    }
    return false;
}

if (is_dir($filePath) && file_exists($filePath . '/index.php')) {
    include $filePath . '/index.php';
    exit();
}

// 4. Fallback redirect
header("Location: /RMS/public/index.php");
exit();
?>
