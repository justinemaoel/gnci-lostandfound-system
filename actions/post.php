post.php
<?php
session_start();
require '../includes/db.php';

if (isset($_POST['submit_post']) && isset($_SESSION['user_id'])) {
    $img_name = "";
    
    // 1. Handle Image Upload
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $img_name = time() . "_" . $_FILES['item_image']['name'];
        move_uploaded_file($_FILES['item_image']['tmp_name'], "../uploads/" . $img_name);
    }

    // 2. Set Default Status
    $status = "available";

    // 2b. Require admin confirmation first
    $upload_status = "pending";
    
    // 3. Clean Optional Fields
    $notes = !empty($_POST['notes']) ? $_POST['notes'] : "";
    $submitted_to_office = isset($_POST['submitted_to_office']) ? 1 : 0;

    // 4. Prepare SQL
    $sql = "INSERT INTO items (user_id, category_id, item_name, location_text, description, notes, status, upload_status, post_type, date_reported, time_last_seen, contact_email, contact_num, item_img, submitted_to_office)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    // 5. Execute with Parameters (PDO way)
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
        header("Location: ../user-dash.php?success=pending");
        exit();
    } else {
        // Updated error handling to use PDO's errorInfo() instead of MySQLi's ->error
        $errorInfo = $stmt->errorInfo();
        echo "Error: " . $errorInfo[2];
    }
} else {
    // If someone tries to access this file directly without posting
    header("Location: ../user-dash.php");
    exit();
}
?>