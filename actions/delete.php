<?php
// actions/delete.php
session_start();
require_once __DIR__ . '/../includes/db.php'; // Using safe absolute pathing

// 1. Security Check: Is the user logged in and did they actually click delete?
if (!isset($_SESSION['user_id']) || !isset($_POST['item_id'])) {
    header("Location: ../user-dash.php");
    exit();
}

$item_id = (int) $_POST['item_id'];
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'student';

// 2. Verify Ownership: Can this user actually delete this item?
// Converted to PDO prepare/execute block
$check = $pdo->prepare("SELECT user_id, item_img FROM items WHERE id = ?");
$check->execute([$item_id]);
$row = $check->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: ../user-dash.php?error=notfound");
    exit();
}

// Check if the user owns the post OR if they are an admin
if ((int)$row['user_id'] !== (int)$user_id && $role !== 'admin') {
    header("Location: ../user-dash.php?error=unauthorized");
    exit();
}

// 3. Cleanup: Delete the image file from the server
if (!empty($row['item_img'])) {
    $img_path = __DIR__ . "/../uploads/" . $row['item_img'];
    if (file_exists($img_path)) {
        unlink($img_path);
    }
}

// 4. Execution: Remove the record from the database
// Converted to PDO prepare/execute block
$stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
$success = $stmt->execute([$item_id]);

if ($success) {
    // Redirect with success message
    header("Location: ../user-dash.php?success=deleted");
} else {
    // Redirect with error message
    header("Location: ../user-dash.php?error=db_error");
}
exit();
?>