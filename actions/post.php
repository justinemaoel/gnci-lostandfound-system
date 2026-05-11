<?php
session_start();
require '../includes/db.php';

if (isset($_POST['submit_post']) && isset($_SESSION['user_id'])) {

    // ── Determine if poster is admin ──────────────────────────────────────
    $adminEmail  = "admin.lostandfound@gmail.com";
    $isAdmin     = strtolower(trim($_SESSION['email'] ?? '')) === strtolower($adminEmail);

    // 1. Handle Image Upload
    $img_name = "";
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $img_name = time() . "_" . $_FILES['item_image']['name'];
        move_uploaded_file($_FILES['item_image']['tmp_name'], "../uploads/" . $img_name);
    }

    // 2. Set status:
    //    - Admin posts are auto-approved and skip the review queue
    //    - Regular user posts go to pending for admin review
    $status        = "available";
    $upload_status = $isAdmin ? "approved" : "pending";

    // 3. Clean optional fields
    $notes               = !empty($_POST['notes']) ? $_POST['notes'] : "";
    $submitted_to_office = isset($_POST['submitted_to_office']) ? 1 : 0;

    // 4. Prepare SQL
    $sql = "INSERT INTO items
                (user_id, category_id, item_name, location_text, description, notes,
                status, upload_status, post_type, date_reported, time_last_seen,
                contact_email, contact_num, item_img, submitted_to_office)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    $params = [
        $_SESSION['user_id'],
        $_POST['category_id'],
        $_POST['item_name'],
        $_POST['location_input'],
        $_POST['desc'],
        $notes,
        $status,
        $upload_status,
        $_POST['post_type'],
        $_POST['date'],
        $_POST['time'],
        $_POST['email'],
        $_POST['phone'],
        $img_name,
        $submitted_to_office
    ];

    if ($stmt->execute($params)) {
        // Redirect admin back to admin dashboard, users to their dashboard
        if ($isAdmin) {
            header("Location: ../admin-dash.php?success=approved");
        } else {
            header("Location: ../user-dash.php?success=pending");
        }
        exit();
    } else {
        $errorInfo = $stmt->errorInfo();
        echo "Error: " . htmlspecialchars($errorInfo[2]);
    }

} else {
    // Direct access without POST — send to appropriate dashboard
    $adminEmail = "admin.lostandfound@gmail.com";
    $isAdmin    = strtolower(trim($_SESSION['email'] ?? '')) === strtolower($adminEmail);
    header("Location: " . ($isAdmin ? "../admin-dash.php" : "../user-dash.php"));
    exit();
}
?>