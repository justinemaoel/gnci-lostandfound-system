<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    $id = $_POST['id'];
    $action = $_POST['action'];
    
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $stmt = $conn->prepare("UPDATE items SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        header("Location: ../admin-dash.php?success=updated");
    } else {
        header("Location: ../admin-dash.php?error=failed");
    }
    exit();
}