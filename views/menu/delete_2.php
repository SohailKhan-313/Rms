<?php
ob_start();
include_once(__DIR__ . '/../../config/database.php');

if (isset($_POST['delete_btn']) && isset($_POST['delete_id']) && $conn) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM category WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}
header("Location: Menue_catagories.php?msg=deleted");
exit();
?>
