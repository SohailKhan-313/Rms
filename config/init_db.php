<?php
/**
 * RMS Automated Database Migration & Schema Seeder
 * Executed during container startup or called directly to ensure all tables exist.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "==> RMS Database Initialization Starting...\n";

// Retry connecting to DB up to 15 times (useful while MySQL container is starting up)
$maxAttempts = 15;
$conn = false;

for ($i = 1; $i <= $maxAttempts; $i++) {
    include __DIR__ . '/database.php';
    if ($conn && !$conn->connect_error) {
        echo "==> Successfully connected to MySQL database on attempt {$i}!\n";
        break;
    }
    echo "==> Attempt {$i}/{$maxAttempts}: MySQL not ready yet. Retrying in 2 seconds...\n";
    sleep(2);
}

if (!$conn || $conn->connect_error) {
    echo "==> ERROR: Could not establish MySQL connection after {$maxAttempts} attempts.\n";
    if (php_sapi_name() === 'cli') {
        exit(1);
    } else {
        die("Could not connect to database.");
    }
}

// Check if tables already exist
$check = $conn->query("SHOW TABLES LIKE 'menue'");
if ($check && $check->num_rows > 0) {
    echo "==> Database already initialized. Checking admin account...\n";
} else {
    echo "==> Database is empty. Importing schema.sql...\n";
    $schemaFile = __DIR__ . '/../schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        
        // Multi-query execution
        if ($conn->multi_query($sql)) {
            do {
                // Free previous result
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
if (php_sapi_name() !== 'cli') {
    echo "<p style='color: green; font-weight: bold;'>Database is fully configured!</p>";
}
?>
