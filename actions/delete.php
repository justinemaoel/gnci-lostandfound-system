<?php
// actions/delete.php
session_start();
require_once __DIR__ . '/../includes/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_POST['item_id'])) {
    header("Location: ../user-dash.php");
    exit();
}

$item_id  = (int) $_POST['item_id'];
$user_id  = $_SESSION['user_id'];
$role     = $_SESSION['role'] ?? 'student';

// Determine redirect base based on role
$redirect_base = ($role === 'admin') ? '../admin-dash.php' : '../user-dash.php';

// 2. Verify Ownership
$check = $pdo->prepare("SELECT user_id, item_img FROM items WHERE id = ?");
$check->execute([$item_id]);
$row = $check->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: {$redirect_base}?error=notfound");
    exit();
}

// Allow if owner OR admin
if ((int)$row['user_id'] !== (int)$user_id && $role !== 'admin') {
    header("Location: {$redirect_base}?error=unauthorized");
    exit();
}

// 3. Cleanup: Delete image file
if (!empty($row['item_img'])) {
    $img_path = __DIR__ . "/../uploads/" . $row['item_img'];
    if (file_exists($img_path)) {
        unlink($img_path);
    }
}

// 4. Delete from database
$stmt    = $pdo->prepare("DELETE FROM items WHERE id = ?");
$success = $stmt->execute([$item_id]);

if ($success) {
    header("Location: {$redirect_base}?success=deleted");
} else {
    header("Location: {$redirect_base}?error=db_error");
}
exit();
?>