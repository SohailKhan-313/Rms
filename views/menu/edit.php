<?php
ob_start();
include_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_btn']) && $conn) {
    $sn = intval($_POST['sn'] ?? 0);
    $item_name = trim($_POST['item_name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $gst = floatval($_POST['gst'] ?? 0);
    $total = round($price * (1 + ($gst / 100)), 2);

    $newImage = "";
    if (!empty($_FILES['product-image']['name']) && $_FILES['product-image']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = pathinfo($_FILES['product-image']['name']);
        $ext = strtolower($fileInfo['extension'] ?? '');
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $newImage = 'item_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $uploadDir = __DIR__ . '/../../public/assets/uploads';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newImage;
            move_uploaded_file($_FILES['product-image']['tmp_name'], $targetPath);
        }
    }

    if ($sn > 0 && !empty($item_name)) {
        if (!empty($newImage)) {
            $stmt = $conn->prepare("UPDATE `menue` SET `item_name` = ?, `code` = ?, `price` = ?, `catagory` = ?, `gst` = ?, `total` = ?, `image` = ? WHERE `sn` = ?");
            if ($stmt) {
                $stmt->bind_param("ssdsddsi", $item_name, $code, $price, $category, $gst, $total, $newImage, $sn);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("UPDATE `menue` SET `item_name` = ?, `code` = ?, `price` = ?, `catagory` = ?, `gst` = ?, `total` = ? WHERE `sn` = ?");
            if ($stmt) {
                $stmt->bind_param("ssdsddi", $item_name, $code, $price, $category, $gst, $total, $sn);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

header("Location: All_menus.php?msg=updated");
exit();
