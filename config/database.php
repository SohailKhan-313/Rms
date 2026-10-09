<?php
/**
 * RMS Database Connection Engine
 * Seamless support for Railway Cloud Deployment, Docker containers, 
 * Private Networking, and Local Multi-Port XAMPP/MariaDB environments.
 */
mysqli_report(MYSQLI_REPORT_OFF);

$GLOBALS['RMS_DB_ATTEMPTS'] = [];
$GLOBALS['RMS_DB_ERROR'] = null;

if (!function_exists('rms_db_last_error')) {
    function rms_db_last_error() {
        return $GLOBALS['RMS_DB_ERROR'] ?? 'Database connection could not be established.';
    }
}

if (!function_exists('get_rms_db_env')) {
    function get_rms_db_env($keys, $default = null) {
        static $fileEnv = null;
        if ($fileEnv === null) {
            $fileEnv = [];
            $envFile = __DIR__ . '/.db_env.json';
            if (file_exists($envFile)) {
                $raw = @file_get_contents($envFile);
                if ($raw) {
                    $json = @json_decode($raw, true);
                    if (is_array($json)) $fileEnv = $json;
                }
            }
        }

        if (!is_array($keys)) $keys = [$keys];
        foreach ($keys as $k) {
            $val = getenv($k);
            if ($val !== false && $val !== '') return $val;
            if (isset($_ENV[$k]) && $_ENV[$k] !== '') return $_ENV[$k];
            if (isset($_SERVER[$k]) && $_SERVER[$k] !== '') return $_SERVER[$k];
            if (isset($fileEnv[$k]) && $fileEnv[$k] !== '') return $fileEnv[$k];
            if (function_exists('apache_getenv')) {
                $aVal = @apache_getenv($k);
                if ($aVal !== false && $aVal !== '') return $aVal;
            }
        }
        return $default;
    }
}

if (!function_exists('rms_parse_db_url')) {
    function rms_parse_db_url($url) {
        if (empty($url)) return false;
        $parts = @parse_url($url);
        // Fallback regex in case password contains special characters like #, ?, @
        if (!$parts || empty($parts['host'])) {
            if (preg_match('#^mysql(?:i)?://(?:([^:@]+)(?::([^@]*))?@)?([^:/]+)(?::([0-9]+))?/(.*)$#i', $url, $m)) {
                $parts = [
                    'user' => $m[1] ?? 'root',
                    'pass' => $m[2] ?? '',
                    'host' => $m[3] ?? '',
                    'port' => !empty($m[4]) ? intval($m[4]) : 3306,
                    'path' => $m[5] ?? 'railway'
                ];
            }
        }
        return $parts;
    }
}

if (!function_exists('rms_try_db_connect')) {
    function rms_try_db_connect($host, $user, $pass, $dbname, $port = 3306, $label = '') {
        $port = intval($port > 0 ? $port : 3306);
        $user = !empty($user) ? $user : 'root';
        $dbname = !empty($dbname) ? $dbname : 'railway';

        try {
            $c = @new mysqli($host, $user, $pass, $dbname, $port);
            if ($c && !$c->connect_error) {
                $c->set_charset("utf8mb4");
                $GLOBALS['RMS_DB_ATTEMPTS'][] = "SUCCESS: Connected to {$host}:{$port} ({$dbname}) via {$label}";
                return $c;
            }

            $err1 = $c ? $c->connect_error : mysqli_connect_error();
            $GLOBALS['RMS_DB_ATTEMPTS'][] = "FAILED: {$host}:{$port} ({$dbname}) via {$label}: {$err1}";

            // If database does not exist yet, connect to server and create it
            $c2 = @new mysqli($host, $user, $pass, "", $port);
            if ($c2 && !$c2->connect_error) {
                @$c2->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                if (@$c2->select_db($dbname)) {
                    $c2->set_charset("utf8mb4");
                    $GLOBALS['RMS_DB_ATTEMPTS'][] = "SUCCESS: Created and connected to {$dbname} on {$host}:{$port} via {$label}";
                    return $c2;
                }
            }
            $GLOBALS['RMS_DB_ERROR'] = "Could not connect to {$host}:{$port} ({$dbname}): {$err1}";
        } catch (Throwable $e) {
            $GLOBALS['RMS_DB_ATTEMPTS'][] = "EXCEPTION on {$host}:{$port} via {$label}: " . $e->getMessage();
            $GLOBALS['RMS_DB_ERROR'] = "Error connecting to {$host}:{$port}: " . $e->getMessage();
        }
        return false;
    }
}

$conn = false;

// 1. Check for Railway/Heroku style DATABASE_URL or MYSQL_URL
$dbUrl = get_rms_db_env(['MYSQL_URL', 'DATABASE_URL', 'MYSQL_PUBLIC_URL', 'DATABASE_PUBLIC_URL', 'MYSQL_PRIVATE_URL']);
if (!empty($dbUrl)) {
    $parsed = rms_parse_db_url($dbUrl);
    if ($parsed && !empty($parsed['host'])) {
        $host = $parsed['host'];
        $port = intval($parsed['port'] ?? 3306);
        $user = isset($parsed['user']) ? urldecode($parsed['user']) : 'root';
        $pass = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
        $dbname = ltrim(urldecode($parsed['path'] ?? 'railway'), '/');
        if (strpos($dbname, '?') !== false) {
            $dbname = explode('?', $dbname)[0];
        }

        $conn = rms_try_db_connect($host, $user, $pass, $dbname, $port, 'MYSQL_URL');
    }
}

// 2. Check for individual Railway / Docker environment variables (MYSQLHOST, MYSQLPORT, etc.)
if (!$conn) {
    $host = get_rms_db_env(['MYSQLHOST', 'DB_HOST', 'MYSQL_HOST', 'DB_HOSTNAME', 'MYSQL_HOSTNAME']);
    if (!empty($host)) {
        $port = intval(get_rms_db_env(['MYSQLPORT', 'DB_PORT', 'MYSQL_PORT'], 3306));
        $user = get_rms_db_env(['MYSQLUSER', 'DB_USER', 'MYSQL_USER', 'DB_USERNAME', 'MYSQL_USERNAME'], 'root');
        $pass = get_rms_db_env(['MYSQLPASSWORD', 'DB_PASSWORD', 'MYSQL_PASSWORD', 'DB_PASS', 'MYSQL_ROOT_PASSWORD'], '');
        $dbname = get_rms_db_env(['MYSQLDATABASE', 'DB_NAME', 'MYSQL_DATABASE', 'DB_DATABASE'], 'railway');

        $conn = rms_try_db_connect($host, $user, $pass, $dbname, $port, 'MYSQLHOST Env');
    }
}

// 3. Container-internal fallbacks (if running in Docker/Railway container and password or private domain is present)
if (!$conn && (file_exists('/.dockerenv') || !empty(getenv('PORT')) || !empty(getenv('RAILWAY_ENVIRONMENT')))) {
    $pass = get_rms_db_env(['MYSQLPASSWORD', 'DB_PASSWORD', 'MYSQL_PASSWORD', 'DB_PASS', 'MYSQL_ROOT_PASSWORD'], '');
    $user = get_rms_db_env(['MYSQLUSER', 'DB_USER', 'MYSQL_USER'], 'root');
    $dbname = get_rms_db_env(['MYSQLDATABASE', 'DB_NAME', 'MYSQL_DATABASE'], 'railway');
    $port = intval(get_rms_db_env(['MYSQLPORT', 'DB_PORT'], 3306));

    $containerHosts = ['mysql.railway.internal', 'mysql', 'mariadb', 'db'];
    foreach ($containerHosts as $cHost) {
        $cConn = rms_try_db_connect($cHost, $user, $pass, $dbname, $port, "Container ({$cHost})");
        if ($cConn) {
            $conn = $cConn;
            break;
        }
    }
}

// 4. Fallback to Local Development (XAMPP / MariaDB multi-port 3307 / 3306)
if (!$conn) {
    $ports = [3307, 3306];
    $username = "root";
    $password = "";
    $database = "rms";

    foreach ($ports as $port) {
        $localConn = rms_try_db_connect("127.0.0.1", $username, $password, $database, $port, "Localhost ({$port})");
        if ($localConn) {
            $conn = $localConn;
            break;
        }
    }

    // If neither port was open, try to launch XAMPP mysqld if present on the machine
    if (!$conn && file_exists('B:\xampp\mysql\bin\mysqld.exe')) {
        @pclose(@popen("start /B B:\\xampp\\mysql\\bin\\mysqld.exe --defaults-file=B:\\xampp\\mysql\\bin\\my.ini --standalone", "r"));
        usleep(800000); // 800ms
        $conn = rms_try_db_connect("127.0.0.1", $username, $password, $database, 3307, "XAMPP Launch");
    }
}

if (!$conn && empty($GLOBALS['RMS_DB_ERROR'])) {
    $GLOBALS['RMS_DB_ERROR'] = "No MySQL credentials detected. On Railway, please link your MySQL service in Web Service -> Variables.";
}
?>
