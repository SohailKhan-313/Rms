<?php
/**
 * RMS Order Controller
 * Handles POS order creation, order listing, status updates, and receipt fetching
 */

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../../config/database.php';

// Auto-create orders and order_items tables if they do not exist
function initOrderTables($conn) {
    if (!$conn) return false;

    $createOrdersTable = "CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_number` VARCHAR(50) UNIQUE NOT NULL,
        `order_type` VARCHAR(30) DEFAULT 'Dine-In',
        `floor_name` VARCHAR(50) DEFAULT NULL,
        `table_no` VARCHAR(50) DEFAULT NULL,
        `customer_name` VARCHAR(100) DEFAULT 'Walk-in Customer',
        `customer_phone` VARCHAR(30) DEFAULT NULL,
        `subtotal` DECIMAL(10,2) DEFAULT 0.00,
        `tax` DECIMAL(10,2) DEFAULT 0.00,
        `discount` DECIMAL(10,2) DEFAULT 0.00,
        `grand_total` DECIMAL(10,2) NOT NULL,
        `paid_amount` DECIMAL(10,2) DEFAULT 0.00,
        `change_amount` DECIMAL(10,2) DEFAULT 0.00,
        `payment_method` VARCHAR(30) DEFAULT 'Cash',
        `order_status` VARCHAR(30) DEFAULT 'Completed',
        `notes` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $createOrderItemsTable = "CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `item_code` VARCHAR(50) DEFAULT NULL,
        `item_name` VARCHAR(150) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `gst` DECIMAL(5,2) DEFAULT 0.00,
        `total` DECIMAL(10,2) NOT NULL,
        `notes` VARCHAR(255) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (`order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $conn->query($createOrdersTable);
    $conn->query($createOrderItemsTable);

    // Ensure floor_name column exists
    $checkCol = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'floor_name'");
    if ($checkCol && $checkCol->num_rows == 0) {
        $conn->query("ALTER TABLE `orders` ADD COLUMN `floor_name` VARCHAR(50) DEFAULT NULL AFTER `order_type`");
    }
    return true;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Read raw JSON body if sent via fetch(..., { headers: { 'Content-Type': 'application/json' } })
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

if ($jsonData && isset($jsonData['action'])) {
    $action = $jsonData['action'];
}

switch ($action) {
    case 'save_order':
        handleSaveOrder($conn, $jsonData ?? $_POST);
        break;

    case 'get_orders':
        handleGetOrders($conn);
        break;

    case 'get_order_details':
        $orderId = intval($_GET['order_id'] ?? $jsonData['order_id'] ?? 0);
        handleGetOrderDetails($conn, $orderId);
        break;

    case 'update_status':
        $orderId = intval($_POST['order_id'] ?? $jsonData['order_id'] ?? 0);
        $status = $_POST['status'] ?? $jsonData['status'] ?? 'Completed';
        handleUpdateStatus($conn, $orderId, $status);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action or request format.'
        ]);
        break;
}

function handleSaveOrder($conn, $data) {
    if (!$conn) {
        // Fallback demo simulation if database isn't currently connected
        $fakeId = rand(1000, 9999);
        $fakeNumber = 'ORD-' . date('Ymd') . '-' . rand(100, 999);
        echo json_encode([
            'success' => true,
            'order_id' => $fakeId,
            'order_number' => $fakeNumber,
            'message' => 'Order created successfully (Demo Mode - DB offline).',
            'order' => $data
        ]);
        return;
    }

    initOrderTables($conn);

    $orderType = mysqli_real_escape_string($conn, $data['order_type'] ?? 'Dine-In');
    $floorName = mysqli_real_escape_string($conn, $data['floor_name'] ?? '');
    $tableNo = mysqli_real_escape_string($conn, $data['table_no'] ?? '');
    $customerName = mysqli_real_escape_string($conn, $data['customer_name'] ?? 'Walk-in Customer');
    $customerPhone = mysqli_real_escape_string($conn, $data['customer_phone'] ?? '');
    $subtotal = floatval($data['subtotal'] ?? 0);
    $tax = floatval($data['tax'] ?? 0);
    $discount = floatval($data['discount'] ?? 0);
    $grandTotal = floatval($data['grand_total'] ?? 0);
    $paidAmount = floatval($data['paid_amount'] ?? $grandTotal);
    $changeAmount = floatval($data['change_amount'] ?? 0);
    $paymentMethod = mysqli_real_escape_string($conn, $data['payment_method'] ?? 'Cash');
    $notes = mysqli_real_escape_string($conn, $data['notes'] ?? '');

    // Generate unique order number
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

    $sqlOrder = "INSERT INTO `orders` 
        (`order_number`, `order_type`, `floor_name`, `table_no`, `customer_name`, `customer_phone`, `subtotal`, `tax`, `discount`, `grand_total`, `paid_amount`, `change_amount`, `payment_method`, `order_status`, `notes`)
        VALUES 
        ('$orderNumber', '$orderType', '$floorName', '$tableNo', '$customerName', '$customerPhone', '$subtotal', '$tax', '$discount', '$grandTotal', '$paidAmount', '$changeAmount', '$paymentMethod', 'Completed', '$notes')";

    if ($conn->query($sqlOrder)) {
        $orderId = $conn->insert_id;
        $items = $data['items'] ?? [];

        // If Dine-In with table, mark table as Occupied
        if ($orderType === 'Dine-In' && !empty($tableNo)) {
            @$conn->query("UPDATE `restaurant_tables` SET `status` = 'Occupied' WHERE `table_number` = '$tableNo'");
        }

        if (is_array($items) && count($items) > 0) {
            foreach ($items as $item) {
                $code = mysqli_real_escape_string($conn, $item['code'] ?? '');
                $name = mysqli_real_escape_string($conn, $item['name'] ?? 'Dish Item');
                $price = floatval($item['price'] ?? 0);
                $qty = intval($item['qty'] ?? 1);
                $itemGst = floatval($item['gst'] ?? 0);
                $total = floatval($item['total'] ?? ($price * $qty));
                $itemNotes = mysqli_real_escape_string($conn, $item['notes'] ?? '');

                $sqlItem = "INSERT INTO `order_items` (`order_id`, `item_code`, `item_name`, `price`, `quantity`, `gst`, `total`, `notes`)
                            VALUES ('$orderId', '$code', '$name', '$price', '$qty', '$itemGst', '$total', '$itemNotes')";
                $conn->query($sqlItem);
            }
        }

        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'message' => 'Order created successfully!',
            'order' => [
                'id' => $orderId,
                'order_number' => $orderNumber,
                'order_type' => $orderType,
                'floor_name' => $floorName,
                'table_no' => $tableNo,
                'customer_name' => $customerName,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'created_at' => date('Y-m-d H:i:s'),
                'items' => $items
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error placing order: ' . $conn->error
        ]);
    }
}

function handleGetOrders($conn) {
    if (!$conn) {
        echo json_encode(['success' => true, 'orders' => []]);
        return;
    }
    initOrderTables($conn);

    $sql = "SELECT o.*, COUNT(i.id) AS total_items 
            FROM `orders` o 
            LEFT JOIN `order_items` i ON o.id = i.order_id 
            GROUP BY o.id 
            ORDER BY o.id DESC LIMIT 50";
    $result = $conn->query($sql);

    $orders = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
}

function handleGetOrderDetails($conn, $orderId) {
    if (!$conn || $orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID or DB offline.']);
        return;
    }

    $sqlOrder = "SELECT * FROM `orders` WHERE `id` = '$orderId' LIMIT 1";
    $resOrder = $conn->query($sqlOrder);

    if (!$resOrder || $resOrder->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        return;
    }

    $order = $resOrder->fetch_assoc();

    $sqlItems = "SELECT * FROM `order_items` WHERE `order_id` = '$orderId'";
    $resItems = $conn->query($sqlItems);
    $items = [];
    if ($resItems) {
        while ($itemRow = $resItems->fetch_assoc()) {
            $items[] = $itemRow;
        }
    }
    $order['items'] = $items;

    echo json_encode([
        'success' => true,
        'order' => $order
    ]);
}

function handleUpdateStatus($conn, $orderId, $status) {
    if (!$conn || $orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Database offline or invalid ID.']);
        return;
    }
    $statusSafe = mysqli_real_escape_string($conn, $status);
    $sql = "UPDATE `orders` SET `order_status` = '$statusSafe' WHERE `id` = '$orderId'";
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Order status updated to ' . $status]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}
