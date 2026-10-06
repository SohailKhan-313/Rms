<?php
ob_start();
include_once(__DIR__ . '/../../config/database.php');

if (isset($_POST['delete_btn']) && isset($_POST['delete_id']) && $conn) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM menue WHERE sn = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}
header("Location: All_menus.php?msg=deleted");
exit();
?>
