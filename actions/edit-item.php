<?php

session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['submit_edit'])) {
    header("Location: ../auth/login.php");
    exit();
}

// 1. Data Collection
$user_id = $_SESSION['user_id'];
$item_id = $_POST['item_id'];
$post_type = $_POST['post_type'];
$item_name = $_POST['item_name'];
$category_id = $_POST['category_id'];
$location_input = $_POST['location_input'];
$date = $_POST['date'];
$time = $_POST['time'];
$datetime = $date . ' ' . $time;
$desc = $_POST['desc'];
$notes = $_POST['notes'] ?? '';

// Prevent "Undefined array key" warnings
$contact_email = $_POST['contact_email'] ?? '';
$contact_phone = $_POST['contact_phone'] ?? '';

// 2. Manage Images
$get_img_stmt = $pdo->prepare("SELECT item_img FROM items WHERE id = ? AND user_id = ?");
$get_img_stmt->execute([$item_id, $user_id]);
$current_item = $get_img_stmt->fetch();

if (!$current_item) {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

$filename = $current_item['item_img'];

if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['item_image']['tmp_name'];
    $file_name = $_FILES['item_image']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    $allowed_exts = ['png', 'jpg', 'jpeg'];
    
    if (in_array($file_ext, $allowed_exts)) {
        $filename = uniqid('item_', true) . '.' . $file_ext;
        $upload_path = __DIR__ . '/../uploads/' . $filename;
        move_uploaded_file($file_tmp, $upload_path);
    }
}

// 3. Database Update
$update_sql = "UPDATE items SET 
    post_type = ?, 
    item_name = ?, 
    category_id = ?, 
    location_text = ?, 
    date_reported = ?, 
    description = ?, 
    notes = ?, 
    item_img = ?, 
    contact_email = ?, 
    contact_num = ? 
    WHERE id = ? AND user_id = ?";

$stmt = $pdo->prepare($update_sql);

$success = $stmt->execute([
    $post_type, 
    $item_name, 
    $category_id, 
    $location_input, 
    $datetime, 
    $desc, 
    $notes, 
    $filename, 
    $contact_email, 
    $contact_phone,
    $item_id, 
    $user_id
]);

// 4. Redirect
if ($success) {
    header("Location: ../user-dash.php?msg=updated");
} else {
    header("Location: ../user-dash.php?error=db_error");
}
exit();