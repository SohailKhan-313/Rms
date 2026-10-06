<?php
/**
 * RMS Root Router & Gateway
 * Automatically redirects root domain requests to the main application entry point,
 * preserving HTTPS and handling Railway reverse proxy headers cleanly.
 */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$proto = $isHttps ? 'https' : 'http';

$rawHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
// Strip internal container ports (e.g. :8080) for external traffic
$cleanHost = preg_replace('/:\d+$/', '', $rawHost);

header("Location: {$proto}://{$cleanHost}/RMS/public/index.php");
exit();
?>
