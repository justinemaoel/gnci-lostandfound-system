<?php

session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['item_id'])) {
    header("Location: ../my-activity.php");
    exit();
}

// 1. Data Collection
$user_id  = $_SESSION['user_id'];
$role     = $_SESSION['role'] ?? 'student';
$item_id  = $_POST['item_id'];
$is_admin = ($role === 'admin');

// Determine where the form was submitted from
$referer       = $_POST['referer'] ?? '';
$redirect_base = match(true) {
    $is_admin && str_contains($referer, 'admin-dash') => '../admin-dash.php',
    $is_admin && str_contains($referer, 'my-activity') => '../my-activity.php',
    $is_admin => '../admin-dash.php',
    default   => '../my-activity.php',
};

$post_type           = $_POST['post_type'];
$item_name           = $_POST['item_name'];
$category_id         = $_POST['category_id'];
$location_input      = $_POST['location_input'];
$date                = $_POST['date'];
$time                = $_POST['time'];
$datetime            = $date . ' ' . $time;
$desc                = $_POST['desc'];
$notes               = $_POST['notes'] ?? '';
$submitted_to_office = isset($_POST['submitted_to_office']) ? 1 : 0;
$contact_email       = $_POST['email'] ?? '';
$contact_num       = $_POST['phone'] ?? '';

// 2. Manage Images
if ($is_admin) {
    $get_img_stmt = $pdo->prepare("SELECT item_img FROM items WHERE id = ?");
    $get_img_stmt->execute([$item_id]);
} else {
    $get_img_stmt = $pdo->prepare("SELECT item_img FROM items WHERE id = ? AND user_id = ?");
    $get_img_stmt->execute([$item_id, $user_id]);
}

$current_item = $get_img_stmt->fetch();

if (!$current_item) {
    header("Location: {$redirect_base}?error=unauthorized");
    exit();
}

$filename = $current_item['item_img'];

if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $file_tmp  = $_FILES['item_image']['tmp_name'];
    $file_name = $_FILES['item_image']['name'];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_exts = ['png', 'jpg', 'jpeg'];

    if (in_array($file_ext, $allowed_exts)) {
        $filename    = uniqid('item_', true) . '.' . $file_ext;
        $upload_path = __DIR__ . '/../uploads/' . $filename;
        move_uploaded_file($file_tmp, $upload_path);
    }
}

// 3. Database Update
if ($is_admin) {
    $update_sql = "UPDATE items SET
        post_type           = ?,
        item_name           = ?,
        category_id         = ?,
        location_text       = ?,
        date_reported       = ?,
        description         = ?,
        notes               = ?,
        item_img            = ?,
        contact_email       = ?,
        contact_num         = ?,
        submitted_to_office = ?
        WHERE id            = ?";

    $params = [
        $post_type, $item_name, $category_id, $location_input,
        $datetime, $desc, $notes, $filename,
        $contact_email, $contact_num, $submitted_to_office,
        $item_id
    ];
} else {
    $update_sql = "UPDATE items SET
        post_type           = ?,
        item_name           = ?,
        category_id         = ?,
        location_text       = ?,
        date_reported       = ?,
        description         = ?,
        notes               = ?,
        item_img            = ?,
        contact_email       = ?,
        contact_num         = ?,
        submitted_to_office = ?
        WHERE id            = ? AND user_id = ?";

    $params = [
        $post_type, $item_name, $category_id, $location_input,
        $datetime, $desc, $notes, $filename,
        $contact_email, $contact_num, $submitted_to_office,
        $item_id, $user_id
    ];
}

$stmt    = $pdo->prepare($update_sql);
$success = $stmt->execute($params);

// 4. Redirect
if ($success) {
    header("Location: {$redirect_base}?msg=updated");
} else {
    header("Location: {$redirect_base}?error=db_error");
}
exit();