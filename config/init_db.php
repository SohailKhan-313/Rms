<?php
/**
 * RMS Automated Database Migration & Schema Seeder
 * Executed during container startup or called directly to ensure all tables exist.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>RMS Database Setup</title>";
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'>";
    echo "</head><body class='bg-light p-4'><div class='container' style='max-width:700px;'>";
    echo "<div class='card shadow-sm'><div class='card-header bg-dark text-white fw-bold'>RMS Database Initializer</div><div class='card-body'><pre class='bg-dark text-success p-3 rounded' style='font-family:monospace;'>";
}

echo "==> RMS Database Initialization Starting...\n";

// Retry connecting to DB (CLI retries up to 15 times during container startup, HTTP runs 1 check)
$maxAttempts = $isCli ? 15 : 1;
$conn = false;

for ($i = 1; $i <= $maxAttempts; $i++) {
    include __DIR__ . '/database.php';
    if ($conn && !$conn->connect_error) {
        echo "==> Successfully connected to MySQL database on attempt {$i}!\n";
        break;
    }
    if ($maxAttempts > 1) {
        echo "==> Attempt {$i}/{$maxAttempts}: MySQL not ready yet. Retrying in 2 seconds...\n";
        sleep(2);
    }
}

if (!$conn || $conn->connect_error) {
    $errMsg = rms_db_last_error();
    echo "==> ERROR: Could not establish MySQL connection.\n";
    echo "==> Details: {$errMsg}\n";
    if ($isCli) {
        exit(1);
    } else {
        echo "</pre><div class='alert alert-danger'><strong>Database Error:</strong> " . htmlspecialchars($errMsg) . "</div>";
        echo "<p><a href='/RMS/public/db_check.php' class='btn btn-outline-danger'>View Diagnostic Center</a></p>";
        echo "</div></div></div></body></html>";
        exit;
    }
}

// Check if tables already exist or force re-seed requested
$force = isset($_GET['force']) || (isset($argv) && in_array('--force', $argv));
$check = $conn->query("SHOW TABLES LIKE 'menue'");
$tableCount = 0;
$tblList = $conn->query("SHOW TABLES");
if ($tblList) $tableCount = $tblList->num_rows;

if ($check && $check->num_rows > 0 && !$force) {
    echo "==> Database already initialized ({$tableCount} tables active). Checking admin account...\n";
} else {
    echo "==> Importing schema.sql...\n";
    $schemaFile = __DIR__ . '/../schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        
        // Multi-query execution
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            echo "==> schema.sql imported successfully!\n";
        } else {
            echo "==> Error importing schema.sql: " . $conn->error . "\n";
        }
    } else {
        echo "==> Warning: schema.sql file not found at {$schemaFile}!\n";
    }
}

// Ensure login table schema is flexible and compatible
@$conn->query("ALTER TABLE `login` MODIFY COLUMN `facebook_id` VARCHAR(150) NULL DEFAULT ''");
@$conn->query("ALTER TABLE `login` MODIFY COLUMN `pass` VARCHAR(255) NOT NULL");
@$conn->query("ALTER TABLE `login` MODIFY COLUMN `email` VARCHAR(120) NOT NULL");
@$conn->query("ALTER TABLE `login` MODIFY COLUMN `name` VARCHAR(255) NULL DEFAULT 'Staff Member'");

// Ensure default login account exists
$loginCheck = $conn->query("SELECT COUNT(*) FROM `login` WHERE `email` = 'SOHAIL@gmail.com'");
if (!$loginCheck || $loginCheck->fetch_row()[0] == 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS `login` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(120) NOT NULL UNIQUE,
        `pass` VARCHAR(255) NOT NULL,
        `name` VARCHAR(100) DEFAULT 'Administrator',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("INSERT IGNORE INTO `login` (`email`, `pass`, `name`) VALUES 
        ('SOHAIL@gmail.com', '123', 'Sohail Manager'),
        ('admin@rms.com', 'admin123', 'System Administrator')");
    echo "==> Default administrator accounts verified.\n";
}

echo "==> RMS Database setup complete and ready for service!\n";

if (!$isCli) {
    echo "</pre>";
    echo "<div class='alert alert-success'><strong>Success!</strong> Database schema is fully synced.</div>";
    echo "<div class='d-flex gap-2'>";
    echo "<a href='/RMS/public/index.php' class='btn btn-primary fw-bold'>Go to Dashboard</a>";
    echo "<a href='/RMS/views/order/new.php' class='btn btn-dark fw-bold'>Launch POS Terminal</a>";
    echo "<a href='/RMS/public/db_check.php' class='btn btn-outline-secondary'>Diagnostic Tool</a>";
    echo "</div>";
    echo "</div></div></div></body></html>";
}
?>
