update_status.php
<?php
session_start();
require '../includes/db.php';

// Admin approve/reject endpoint used by admin-dash buttons.
// admin-dash passes: update_status.php?id=...&status=approved|rejected
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { 
    // Ensure PDO exists
    if (!isset($pdo)) {
        header("Location: ../admin-dash.php?error=db");
        exit();
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $statusParam = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
    // Normalize accepted values
    if ($statusParam === 'approved') $statusParam = 'approved';
    if ($statusParam === 'rejected') $statusParam = 'rejected';


    if ($id <= 0 || !in_array($statusParam, ['approved', 'rejected'], true)) {
        header("Location: ../admin-dash.php?error=invalid");
        exit();
    }

    if ($statusParam === 'approved') {
        // Publish: set upload_status=approved
        $stmt = $pdo->prepare("UPDATE items SET upload_status = ?, status = ? WHERE id = ?");
        $stmt->execute([$statusParam, $statusParam, $id]);

        // Redirect to landing/index after approval
        header("Location: ../index.php?success=approved");
        exit();
    }

    // Rejected: delete item immediately (DB row + uploaded image)
    $imgStmt = $pdo->prepare("SELECT item_img FROM items WHERE id = ?");
    $imgStmt->execute([$id]);
    $imgRow = $imgStmt->fetch();

    if ($imgRow && !empty($imgRow['item_img'])) {
        $imgPath = __DIR__ . "/../uploads/" . $imgRow['item_img'];
        if (file_exists($imgPath)) {
            unlink($imgPath);
        }
    }

    $delStmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $delStmt->execute([$id]);

    header("Location: ../admin-dash.php?success=rejected_deleted");
    exit();

}

header("Location: ../admin-dash.php?error=unauthorized");
exit();