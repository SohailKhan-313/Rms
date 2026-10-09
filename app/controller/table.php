<?php
/**
 * RMS Restaurant Tables & Floors Controller
 * Handles CRUD for floors and tables, status toggling, and AJAX queries
 */

header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../../config/database.php';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . rms_db_last_error()]);
    exit();
}

// Auto-ensure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS `restaurant_floors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `floor_name` VARCHAR(100) NOT NULL UNIQUE,
    `floor_code` VARCHAR(50) NULL,
    `description` VARCHAR(255) NULL,
    `status` VARCHAR(20) DEFAULT 'Active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$conn->query("CREATE TABLE IF NOT EXISTS `restaurant_tables` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `table_number` VARCHAR(50) NOT NULL UNIQUE,
    `floor_id` INT NOT NULL,
    `floor_name` VARCHAR(100) NOT NULL,
    `capacity` INT NOT NULL DEFAULT 4,
    `status` VARCHAR(30) DEFAULT 'Available',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Check raw JSON
$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
if ($json && isset($json['action'])) {
    $action = $json['action'];
    $_POST = array_merge($_POST, $json);
}

switch ($action) {
    // --- FLOORS ---
    case 'get_floors':
        $floors = [];
        $res = $conn->query("SELECT f.*, COUNT(t.id) AS table_count 
                             FROM `restaurant_floors` f 
                             LEFT JOIN `restaurant_tables` t ON f.id = t.floor_id 
                             GROUP BY f.id 
                             ORDER BY f.id ASC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $floors[] = $row;
            }
        }
        echo json_encode(['success' => true, 'floors' => $floors]);
        break;

    case 'save_floor':
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['floor_name'] ?? '');
        $code = trim($_POST['floor_code'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'Active');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Floor name is required.']);
            exit();
        }

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE `restaurant_floors` SET `floor_name` = ?, `floor_code` = ?, `description` = ?, `status` = ? WHERE `id` = ?");
            $stmt->bind_param("ssssi", $name, $code, $desc, $status, $id);
            $stmt->execute();
            // Also update floor_name in tables
            $stmt2 = $conn->prepare("UPDATE `restaurant_tables` SET `floor_name` = ? WHERE `floor_id` = ?");
            $stmt2->bind_param("si", $name, $id);
            $stmt2->execute();
            $stmt2->close();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Floor updated successfully.']);
        } else {
            $stmt = $conn->prepare("INSERT INTO `restaurant_floors` (`floor_name`, `floor_code`, `description`, `status`) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $code, $desc, $status);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Floor created successfully.', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create floor: ' . $stmt->error]);
            }
            $stmt->close();
        }
        break;

    case 'delete_floor':
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // Delete associated tables or set them to unassigned
            $conn->query("DELETE FROM `restaurant_tables` WHERE `floor_id` = $id");
            $conn->query("DELETE FROM `restaurant_floors` WHERE `id` = $id");
            echo json_encode(['success' => true, 'message' => 'Floor and its tables removed successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid floor ID.']);
        }
        break;

    // --- TABLES ---
    case 'get_tables':
        $floorId = intval($_GET['floor_id'] ?? 0);
        $sql = "SELECT t.*, f.floor_code FROM `restaurant_tables` t 
                LEFT JOIN `restaurant_floors` f ON t.floor_id = f.id ";
        if ($floorId > 0) {
            $sql .= " WHERE t.floor_id = $floorId ";
        }
        $sql .= " ORDER BY t.floor_id ASC, t.table_number ASC";
        $res = $conn->query($sql);
        $tables = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $tables[] = $row;
            }
        }
        echo json_encode(['success' => true, 'tables' => $tables]);
        break;

    case 'save_table':
        $id = intval($_POST['id'] ?? 0);
        $tableNum = trim($_POST['table_number'] ?? '');
        $floorId = intval($_POST['floor_id'] ?? 0);
        $capacity = intval($_POST['capacity'] ?? 4);
        $status = trim($_POST['status'] ?? 'Available');

        if (empty($tableNum)) {
            echo json_encode(['success' => false, 'message' => 'Table number/name is required.']);
            exit();
        }
        if ($floorId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid floor.']);
            exit();
        }

        // Fetch floor name
        $rf = $conn->query("SELECT floor_name FROM `restaurant_floors` WHERE id = $floorId LIMIT 1");
        $floorName = ($rf && $rf->num_rows > 0) ? $rf->fetch_assoc()['floor_name'] : 'Ground Floor';

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE `restaurant_tables` SET `table_number` = ?, `floor_id` = ?, `floor_name` = ?, `capacity` = ?, `status` = ? WHERE `id` = ?");
            $stmt->bind_param("sisisi", $tableNum, $floorId, $floorName, $capacity, $status, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Table updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update table: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO `restaurant_tables` (`table_number`, `floor_id`, `floor_name`, `capacity`, `status`) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisis", $tableNum, $floorId, $floorName, $capacity, $status);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Table added successfully.', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add table: ' . $stmt->error]);
            }
            $stmt->close();
        }
        break;

    case 'update_table_status':
        $id = intval($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Available');
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE `restaurant_tables` SET `status` = ? WHERE `id` = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Table status updated to ' . $status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid table ID.']);
        }
        break;

    case 'delete_table':
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("DELETE FROM `restaurant_tables` WHERE `id` = $id");
            echo json_encode(['success' => true, 'message' => 'Table deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid table ID.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
