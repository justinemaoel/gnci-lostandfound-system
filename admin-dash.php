<?php
session_start();
require 'includes/db.php'; // This file must define the $conn variable

// 1. INITIALIZE VARIABLES FIRST (Fixes the "Undefined variable" errors)
$pendingCount = 0;
$approvedCount = 0;
$totalPosts = 0;
$activeUsers = 0;
$pendingItems = [];

// 2. SESSION DATA
$firstName = $_SESSION['first_name'] ?? "Admin";
$lastName  = $_SESSION['last_name'] ?? "";
$fullName  = trim($firstName . " " . $lastName);
$role      = $_SESSION['role'] ?? "admin";

// 3. FETCH DATA FROM DATABASE (Matching your SQL column names)
if (isset($conn)) {
    // Fetch Counters using correct column: 'upload_status'
    $res1 = $conn->query("SELECT COUNT(*) as total FROM items WHERE upload_status = 'pending'");
    if ($res1) $pendingCount = $res1->fetch_assoc()['total'];

    $res2 = $conn->query("SELECT COUNT(*) as total FROM items WHERE upload_status = 'approved'");
    if ($res2) $approvedCount = $res2->fetch_assoc()['total'];

    $res3 = $conn->query("SELECT COUNT(*) as total FROM items");
    if ($res3) $totalPosts = $res3->fetch_assoc()['total'];

    $res4 = $conn->query("SELECT COUNT(*) as total FROM users");
    if ($res4) $activeUsers = $res4->fetch_assoc()['total'];

    // Fetch Pending Items with User and Category Joins
    $query = "SELECT i.*, u.first_name, u.last_name, c.category_name 
            FROM items i 
            JOIN users u ON i.user_id = u.id 
            JOIN categories c ON i.category_id = c.id 
            WHERE i.upload_status = 'pending' 
            ORDER BY i.created_at DESC";
            
    $result_items = $conn->query($query);
    if ($result_items) {
        while ($row = $result_items->fetch_assoc()) {
            $pendingItems[] = $row;
        }
    }
}

// 4. LOGIN LOGIC (Existing)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?"); 
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['first_name'] = $user['first_name']; 
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email']; 
        header("Location: admin-dash.php"); // Refresh to apply session
        exit();
    }
}   
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GNC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
</head>
<body>

<header class="navbar sticky-top flex-md-nowrap p-3 shadow-sm" style="background-color: #0b4628;">
    <div class="d-flex align-items-center">
        <button class="navbar-toggler d-md-none me-3 border-white text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center text-white p-0" href="#">
            <img src="assets/images/GNC Logo.svg" alt="Logo" width="40" class="me-2">
            <div class="lh-1 d-none d-sm-block">
                <span class="fw-bold d-block small">Guagua National Colleges</span>
                <small style="font-size: 0.7rem;">Lost & Found</small>
            </div>
        </a>
    </div>
    <div class="text-white d-flex align-items-center">
        <div class="text-end me-3 d-none d-md-block lh-1">
            <div class="fw-bold mb-0 lh-1"><?php echo htmlspecialchars($fullName); ?></div>
            <small class="text-uppercase opacity-75" style="font-size: 10px;"><?php echo htmlspecialchars($role); ?></small>
        </div>
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fullName); ?>&background=0b5a30&color=fff" class="user-avatar shadow-sm">
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (Offcanvas on mobile, static column on desktop) -->
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar offcanvas-md offcanvas-start bg-white border-end p-0">
            <div class="offcanvas-header d-md-none border-bottom">
                <h5 class="offcanvas-title fw-bold">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu"></button>
            </div>
            
            <div class="offcanvas-body d-flex flex-column p-3 flex-grow-1">
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link active d-flex align-items-center" style="background-color: #d1e7dd; color: #0b4628;">
                            <i class="bi bi-house-door-fill me-2"></i> Home
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link text-dark d-flex align-items-center">
                            <i class="bi bi-search me-2"></i> Browse Item
                        </a>
                    </li>
                </ul>
                
                <div class="mt-auto border-top pt-3">
                    <ul class="nav nav-pills flex-column pb-3">
                        <li class="nav-item">
                            <a href="auth/logout.php" class="nav-link text-danger d-flex align-items-center fw-semibold">
                                <i class="bi bi-box-arrow-right me-2"></i> Sign out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content Column -->
        <main class="col-md-9 col-lg-10 px-4 py-4">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card card-pending p-3 shadow-sm">
                        <small class="text-muted fw-bold"><i class="bi bi-clock text-warning me-2"></i>Pending Approval</small>
                        <h2 class="fw-bold my-2"><?php echo $pendingCount; ?></h2>
                        <small class="text-muted" style="font-size: 11px;">Item waiting for review</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card card-approved p-3 shadow-sm">
                        <small class="text-muted fw-bold"><i class="bi bi-check-circle text-success me-2"></i>Approved Posts</small>
                        <h2 class="fw-bold my-2"><?php echo $approvedCount; ?></h2>
                        <small class="text-muted" style="font-size: 11px;">Published items</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3 shadow-sm" style="border-bottom-color: var(--gnc-green);">
                        <small class="text-muted fw-bold"><i class="bi bi-box text-success me-2"></i>Total Posts</small>
                        <h2 class="fw-bold my-2"><?php echo $totalPosts; ?></h2>
                        <small class="text-muted" style="font-size: 11px;">All posted items</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3 shadow-sm" style="border-bottom-color: #198754;">
                        <small class="text-muted fw-bold"><i class="bi bi-people text-success me-2"></i>Active Users</small>
                        <h2 class="fw-bold my-2"><?php echo $activeUsers; ?></h2>
                        <small class="text-muted" style="font-size: 11px;">Registered users</small>
                    </div>
                </div>
            </div>

            <h4 class="fw-bold mb-4">Manage Posts</h4>

            <?php foreach ($pendingItems as $item): ?>
            <div class="card item-card mb-3 shadow-sm">
                <div class="row g-0">
                    <div class="col-md-2">
                        <img src="uploads/<?php echo $item['item_img']; ?>" class="img-fluid rounded-start h-100 w-100" style="object-fit: cover; min-height: 150px;">
                    </div>
                    <div class="col-md-7 p-3">
                        <div class="d-flex align-items-center mb-2">
                            <h5 class="fw-bold mb-0 me-2"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                            <span class="badge badge-type me-1"><?php echo $item['post_type']; ?></span>
                            <span class="badge badge-pending">Pending Review</span>
                        </div>
                        <div class="mb-2"><span class="cat-badge"><?php echo $item['category_name']; ?></span></div>
                        <div class="small text-muted mb-1"><i class="bi bi-geo-alt me-2"></i><?php echo htmlspecialchars($item['location_text']); ?></div>
                        <div class="small text-muted mb-1"><i class="bi bi-calendar me-2"></i><?php echo date('F d, Y', strtotime($item['date_reported'])); ?></div>
                        <div class="small text-muted mb-1"><i class="bi bi-person me-2"></i>Submitted by: <?php echo $item['first_name'] . ' ' . $item['last_name']; ?></div>
                        <div class="small text-muted"><i class="bi bi-clock me-2"></i>Submitted on: <?php echo date('F d, Y', strtotime($item['created_at'])); ?></div>
                    </div>
                    <div class="col-md-3 d-flex flex-column justify-content-center p-3 border-start">
                        <a href="update_status.php?id=<?php echo $item['id']; ?>&status=approved" class="btn btn-success mb-2 w-100"><i class="bi bi-check-circle me-2"></i> Approve</a>
                        <a href="update_status.php?id=<?php echo $item['id']; ?>&status=rejected" class="btn btn-danger mb-2 w-100"><i class="bi bi-x-circle me-2"></i> Reject</a>
                        <button class="btn btn-outline-secondary w-100"><i class="bi bi-eye me-2"></i> View Details</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </main>
    </div> <!-- end row -->
</div> <!-- end container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>