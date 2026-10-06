<?php
/**
 * RMS Database Connection
 * Seamless support for Railway Cloud Deployment, Docker containers, 
 * and Local Multi-Port XAMPP/MariaDB environments.
 */
mysqli_report(MYSQLI_REPORT_OFF);

if (!function_exists('get_rms_db_env')) {
    function get_rms_db_env($keys, $default = null) {
        if (!is_array($keys)) $keys = [$keys];
        foreach ($keys as $k) {
            $val = getenv($k);
            if ($val !== false && $val !== '') return $val;
            if (isset($_ENV[$k]) && $_ENV[$k] !== '') return $_ENV[$k];
            if (isset($_SERVER[$k]) && $_SERVER[$k] !== '') return $_SERVER[$k];
        }
        return $default;
    }
}

$conn = false;

// 1. Check for Railway/Heroku style DATABASE_URL or MYSQL_URL
$dbUrl = get_rms_db_env(['MYSQL_URL', 'DATABASE_URL']);
if (!empty($dbUrl)) {
    $parsed = parse_url($dbUrl);
    if ($parsed && isset($parsed['host'])) {
        $host = $parsed['host'];
        $port = intval($parsed['port'] ?? 3306);
        $user = $parsed['user'] ?? 'root';
        $pass = $parsed['pass'] ?? '';
        $dbname = ltrim($parsed['path'] ?? 'railway', '/');

        try {
            $c = @new mysqli($host, $user, $pass, $dbname, $port);
            if ($c && !$c->connect_error) {
                $c->set_charset("utf8mb4");
                $conn = $c;
            }
        } catch (Throwable $e) {}
    }
}

// 2. Check for individual Railway environment variables (MYSQLHOST, MYSQLPORT, etc.)
if (!$conn) {
    $host = get_rms_db_env(['MYSQLHOST', 'DB_HOST', 'MYSQL_HOST']);
    if (!empty($host)) {
        $port = intval(get_rms_db_env(['MYSQLPORT', 'DB_PORT', 'MYSQL_PORT'], 3306));
        $user = get_rms_db_env(['MYSQLUSER', 'DB_USER', 'MYSQL_USER'], 'root');
        $pass = get_rms_db_env(['MYSQLPASSWORD', 'DB_PASSWORD', 'MYSQL_PASSWORD'], '');
        $dbname = get_rms_db_env(['MYSQLDATABASE', 'DB_NAME', 'MYSQL_DATABASE'], 'railway');

        try {
            $c = @new mysqli($host, $user, $pass, $dbname, $port);
            if ($c && !$c->connect_error) {
                $c->set_charset("utf8mb4");
                $conn = $c;
            } else {
                $c = @new mysqli($host, $user, $pass, "", $port);
                if ($c && !$c->connect_error) {
                    @$c->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    if (@$c->select_db($dbname)) {
                        $c->set_charset("utf8mb4");
                        $conn = $c;
                    }
                }
            }
        } catch (Throwable $e) {}
    }
}

// 3. Fallback to Local Development (XAMPP / MariaDB multi-port 3307 / 3306)
if (!$conn) {
    $ports = [3307, 3306];
    $username = "root";
    $password = "";
    $database = "rms";

    foreach ($ports as $port) {
        try {
            $c = @new mysqli("127.0.0.1", $username, $password, "", $port);
            if ($c && !$c->connect_error) {
                // Ensure rms database exists
                @$c->query("CREATE DATABASE IF NOT EXISTS `rms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                if (@$c->select_db($database)) {
                    $c->set_charset("utf8mb4");
                    $conn = $c;
                    break;
                }
            }
        } catch (Throwable $e) {
            // try next port
        }
    }

    // If neither port was open, try to launch XAMPP mysqld if present on the machine
    if (!$conn && file_exists('B:\xampp\mysql\bin\mysqld.exe')) {
        @pclose(@popen("start /B B:\\xampp\\mysql\\bin\\mysqld.exe --defaults-file=B:\\xampp\\mysql\\bin\\my.ini --standalone", "r"));
        usleep(800000); // 800ms
        try {
            $c = @new mysqli("127.0.0.1", "root", "", "rms", 3307);
            if ($c && !$c->connect_error) {
                $c->set_charset("utf8mb4");
                $conn = $c;
            }
        } catch (Throwable $e) {}
    }
}
?>
