<?php
include_once __DIR__ . '/database.php';
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn ? $conn->connect_error : 'No DB connection'));
}

// 1. Category table
$conn->query("CREATE TABLE IF NOT EXISTS `category` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 2. Customers table
$conn->query("CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(100) NULL,
    `dob` DATE NULL,
    `address` VARCHAR(255) NULL,
    `discount` VARCHAR(20) DEFAULT '0',
    `added_by` VARCHAR(50) DEFAULT 'Admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Ensure columns exist and alter phone type to varchar so it doesn't fail on leading zeros or dashes
$conn->query("ALTER TABLE `customers` MODIFY COLUMN `phone` VARCHAR(30) NULL");
$conn->query("ALTER TABLE `customers` MODIFY COLUMN `discount` VARCHAR(20) DEFAULT '0'");
@$conn->query("ALTER TABLE `customers` ADD COLUMN `added_by` VARCHAR(50) DEFAULT 'Admin'");
@$conn->query("ALTER TABLE `customers` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// 3. Suppliers table (support both 'supliers' and 'suppliers')
$conn->query("CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `item_of_supply` VARCHAR(150) NOT NULL,
    `date` DATE NOT NULL,
    `dues` DECIMAL(10,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// In case the existing table is 'supliers'
$conn->query("ALTER TABLE `supliers` MODIFY COLUMN `item` VARCHAR(150) NULL");
$conn->query("ALTER TABLE `supliers` MODIFY COLUMN `phone` VARCHAR(30) NULL");
$conn->query("ALTER TABLE `supliers` MODIFY COLUMN `dues` DECIMAL(10,2) DEFAULT 0.00");

// 4. Expenses table
$conn->query("CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL,
    `rp` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `catagory` VARCHAR(100) NOT NULL,
    `note` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("ALTER TABLE `expenses` MODIFY COLUMN `amount` DECIMAL(10,2) NOT NULL");
@$conn->query("ALTER TABLE `expenses` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// 5. Orders table
$conn->query("CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL,
    `customer_name` VARCHAR(100) DEFAULT 'Walk-in Customer',
    `order_type` VARCHAR(50) DEFAULT 'Dine-In',
    `payment_method` VARCHAR(50) DEFAULT 'Cash',
    `subtotal` DECIMAL(10,2) DEFAULT 0.00,
    `tax` DECIMAL(10,2) DEFAULT 0.00,
    `discount` DECIMAL(10,2) DEFAULT 0.00,
    `grand_total` DECIMAL(10,2) DEFAULT 0.00,
    `total` DECIMAL(10,2) DEFAULT 0.00,
    `status` VARCHAR(50) DEFAULT 'COMPLETED',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@$conn->query("ALTER TABLE `orders` ADD COLUMN `order_number` VARCHAR(50) NULL");
@$conn->query("ALTER TABLE `orders` ADD COLUMN `customer_name` VARCHAR(100) DEFAULT 'Walk-in Customer'");
@$conn->query("ALTER TABLE `orders` ADD COLUMN `order_type` VARCHAR(50) DEFAULT 'Dine-In'");
@$conn->query("ALTER TABLE `orders` ADD COLUMN `payment_method` VARCHAR(50) DEFAULT 'Cash'");
@$conn->query("ALTER TABLE `orders` ADD COLUMN `subtotal` DECIMAL(10,2) DEFAULT 0.00");
@$conn->query("ALTER TABLE `orders` ADD COLUMN `tax` DECIMAL(10,2) DEFAULT 0.00");
@$conn->query("ALTER TABLE `orders` ADD COLUMN `discount` DECIMAL(10,2) DEFAULT 0.00");
@$conn->query("ALTER TABLE `orders` ADD COLUMN `grand_total` DECIMAL(10,2) DEFAULT 0.00");

// 6. Order items table
$conn->query("CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(100) NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `qty` INT NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@$conn->query("ALTER TABLE `order_items` ADD COLUMN `product_name` VARCHAR(100) NULL");
@$conn->query("ALTER TABLE `order_items` ADD COLUMN `subtotal` DECIMAL(10,2) DEFAULT 0.00");

// 7. Seed categories if empty
$catCheck = $conn->query("SELECT COUNT(*) FROM category");
if ($catCheck && $catCheck->fetch_row()[0] < 5) {
    $seedCats = ['Burgers', 'Pizza', 'Beverages', 'Sides', 'Desserts', 'Biryani', 'BBQ'];
    foreach ($seedCats as $sc) {
        $conn->query("INSERT IGNORE INTO category (category_name) VALUES ('$sc')");
    }
}

// 8. Seed menue with the 10 real food dishes if menue has less than 5 items
$menuCheck = $conn->query("SELECT COUNT(*) FROM menue");
if ($menuCheck && $menuCheck->fetch_row()[0] < 5) {
    $seedDishes = [
        ['code' => 101, 'name' => 'Classic Cheeseburger', 'cat' => 'Burgers', 'price' => 8.99, 'gst' => 5, 'img' => '/RMS/public/assets/images/food/pos_cheeseburger.jpg'],
        ['code' => 102, 'name' => 'Double Bacon BBQ Burger', 'cat' => 'Burgers', 'price' => 11.50, 'gst' => 5, 'img' => '/RMS/public/assets/images/food/pos_bbq_burger.jpg'],
        ['code' => 103, 'name' => 'Margherita Pizza 12"', 'cat' => 'Pizza', 'price' => 13.99, 'gst' => 5, 'img' => '/RMS/public/assets/images/food/pos_pizza.jpg'],
        ['code' => 104, 'name' => 'Pepperoni Passion Pizza', 'cat' => 'Pizza', 'price' => 15.50, 'gst' => 5, 'img' => '/RMS/public/assets/images/food/pos_pepperoni.jpg'],
        ['code' => 105, 'name' => 'Fresh Iced Lemon Tea', 'cat' => 'Beverages', 'price' => 3.50, 'gst' => 0, 'img' => '/RMS/public/assets/images/food/pos_iced_tea.jpg'],
        ['code' => 106, 'name' => 'Cappuccino / Latte', 'cat' => 'Beverages', 'price' => 4.25, 'gst' => 0, 'img' => '/RMS/public/assets/images/food/pos_cappuccino.jpg'],
        ['code' => 107, 'name' => 'Crispy French Fries (L)', 'cat' => 'Sides', 'price' => 4.50, 'gst' => 5, 'img' => '/RMS/public/assets/images/food/pos_fries.jpg'],
        ['code' => 108, 'name' => 'Buffalo Chicken Wings (6pc)', 'cat' => 'Sides', 'price' => 9.25, 'gst' => 5, 'img' => '/RMS/public/assets/images/food/pos_buffalo_wings.jpg'],
        ['code' => 109, 'name' => 'Chocolate Lava Cake', 'cat' => 'Desserts', 'price' => 6.50, 'gst' => 5, 'img' => '/RMS/public/assets/images/food/pos_lava_cake.jpg'],
        ['code' => 110, 'name' => 'Vanilla Bean Sundae', 'cat' => 'Desserts', 'price' => 5.00, 'gst' => 5, 'img' => '/RMS/public/assets/images/food/pos_icecream_sundae.jpg'],
    ];

    foreach ($seedDishes as $d) {
        $total = round($d['price'] * (1 + $d['gst'] / 100), 2);
        $conn->query("INSERT INTO menue (code, image, item_name, catagory, price, gst, total) 
                      VALUES ({$d['code']}, '{$d['img']}', '{$d['name']}', '{$d['cat']}', {$d['price']}, {$d['gst']}, {$total})");
    }
}

echo "Database updated successfully!\n";
$conn->close();
