<?php
session_start();
require 'includes/db.php'; // This file defines $pdo (PDO connection)

// If admin-dash.php previously expected mysqli ($conn), ensure we use PDO ($pdo) instead.
if (!isset($pdo)) {
    // Hard fail with visible message so it's obvious in the UI.
    die('DB connection not found: $pdo is missing in includes/db.php');
}


// 0. AUTH CHECK (must be logged in)
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Admin-only gate (only the single admin account can access this page)
$adminEmail = "admin.lostandfound@gmail.com";
$currentEmail = $_SESSION['email'] ?? '';
if (strtolower(trim($currentEmail)) !== strtolower($adminEmail)) {
    header("Location: user-dash.php");
    exit();
}

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
if (isset($pdo)) {
    // Fetch Counters using correct column: 'upload_status'
    $pendingCount = (int)$pdo->query("SELECT COUNT(*) as total FROM items WHERE upload_status = 'pending'")->fetchColumn();
    $approvedCount = (int)$pdo->query("SELECT COUNT(*) as total FROM items WHERE upload_status = 'approved'")->fetchColumn();
    $totalPosts = (int)$pdo->query("SELECT COUNT(*) as total FROM items")->fetchColumn();
    $activeUsers = (int)$pdo->query("SELECT COUNT(*) as total FROM users")->fetchColumn();

    // Fetch Pending Items with User and Category Joins
    $query = "SELECT i.*, u.first_name, u.last_name, c.category_name 
            FROM items i 
            JOIN users u ON i.user_id = u.id 
            JOIN categories c ON i.category_id = c.id 
            WHERE i.upload_status = 'pending' 
            ORDER BY i.created_at DESC";

    $stmt = $pdo->query($query);
    if ($stmt) {
        $pendingItems = $stmt->fetchAll();
    }
}


// Note: Admin authentication is handled via auth/login.php and the admin-only gate above.
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
                        <a href="browse-item.php" class="nav-link text-dark d-flex align-items-center">
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

<?php if ($pendingCount <= 0): ?>
            <div class="alert alert-warning">No pending items found for approval. Pending count: <?php echo (int)$pendingCount; ?></div>
        <?php endif; ?>

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
                        <a href="actions/update_status.php?id=<?php echo $item['id']; ?>&status=approved" class="btn btn-success mb-2 w-100"><i class="bi bi-check-circle me-2"></i> Approve</a>
                        <a href="actions/update_status.php?id=<?php echo $item['id']; ?>&status=rejected" class="btn btn-danger mb-2 w-100"><i class="bi bi-x-circle me-2"></i> Reject</a>
                        <button type="button" class="btn btn-outline-secondary w-100 btn-view-details" 
                            data-item=
'<?php echo json_encode($item, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>'>
                            <i class="bi bi-eye me-2"></i> View Details
                        </button>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </main>
    </div> <!-- end row -->
</div> <!-- end container-fluid -->

<!-- View Details Modal (Admin) -->
<div class="modal fade" id="adminViewItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg p-3">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold">Item Details</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="position-relative mb-3">
                            <img id="admin-v-img" src="" class="img-fluid rounded-3 w-100 object-fit-cover" style="height: 280px; background: #eee;">
                            <span id="admin-v-type" class="badge position-absolute top-0 end-0 m-2 rounded-pill px-3"></span>
                        </div>
                        <h5 class="fw-bold mb-2">Description</h5>
                        <p id="admin-v-desc" class="text-muted small mb-4" style="line-height: 1.6;"></p>
                        <h5 class="fw-bold mb-3">Posted by:</h5>
                        <div class="p-3 rounded-3" style="background-color: #f8f9fa;">
                            <div class="d-flex align-items-center gap-3">
                                <img id="admin-v-avatar" src="" class="rounded-circle border" width="45" height="45">
                                <div class="lh-sm">
                                    <div class="fw-bold text-dark" id="admin-v-user-name"></div>
                                    <span id="admin-v-user-role" class="badge bg-light text-muted border-0 p-0 text-uppercase" style="font-size: 10px;"></span>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top" style="font-size: 12px;">
                                <div class="text-success mb-2"><i class="bi bi-envelope me-2"></i><span id="admin-v-email"></span></div>
                                <div class="text-muted"><i class="bi bi-telephone me-2"></i><span id="admin-v-phone"></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7 border-start ps-md-4">
                        <h2 id="admin-v-name" class="fw-bold mb-1"></h2>
                        <div class="mb-4">
                            <span id="admin-v-cat" class="badge border text-success fw-normal" style="border-color: #d1e7dd !important; background-color: #f0fdf4; color: #157347;"></span>
                        </div>
                        <hr class="opacity-10 mb-4">

                        <h5 class="fw-bold mb-3">Location & Time</h5>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-geo-alt text-success fs-5 me-3"></i>
                            <div>
                                <div class="text-muted small">Location</div>
                                <div class="fw-bold" id="admin-v-loc"></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-calendar-event text-success fs-5 me-3"></i>
                            <div>
                                <div class="text-muted small">Date Reported</div>
                                <div class="fw-bold" id="admin-v-date"></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-4">
                            <i class="bi bi-clock text-success fs-5 me-3"></i>
                            <div>
                                <div class="text-muted small">Time Reported</div>
                                <div class="fw-bold" id="admin-v-time"></div>
                            </div>
                        </div>

                        <hr class="opacity-10 mb-4">

                        <h5 class="fw-bold mb-2">Item Status</h5>
                        <div id="admin-v-status-box" class="alert d-flex align-items-start gap-3 border-0 rounded-3 p-3">
                            <div class="d-flex align-items-center justify-content-center" style="min-width: 35px; height: 35px;">
                                <i class="bi bi-info-circle-fill text-success" id="admin-v-status-icon"></i>
                            </div>
                            <div id="admin-v-status-text" class="small"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
    const modalEl = document.getElementById('adminViewItemModal');
    if (!modalEl) return;

    modalEl.addEventListener('show.bs.modal', function(event){
        const button = event.relatedTarget;
        if (!button) return;

        let item = null;
        try {
            item = JSON.parse(button.getAttribute('data-item'));
        } catch (e) {
            console.error('Failed to parse item JSON', e);
            return;
        }

        const type = (item.post_type || '').toLowerCase();
        const isLost = type === 'lost';

        document.getElementById('admin-v-img').src = item.item_img ? ('uploads/' + item.item_img) : 'assets/images/placeholder.png';
        document.getElementById('admin-v-name').textContent = item.item_name || '';
        document.getElementById('admin-v-cat').textContent = item.category_name || 'General';
        document.getElementById('admin-v-loc').textContent = item.location_text || '';
        document.getElementById('admin-v-date').textContent = item.date_reported ? item.date_reported : '';
        document.getElementById('admin-v-time').textContent = item.time_last_seen || '';
        document.getElementById('admin-v-desc').textContent = item.description || '';

        const avatarName = ((item.first_name || '') + ' ' + (item.last_name || '')).trim();
        document.getElementById('admin-v-avatar').src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(avatarName) + '&background=0b5a30&color=fff';
        document.getElementById('admin-v-user-name').textContent = avatarName || '';

        // user role not selected in admin query; keep badge minimal
        document.getElementById('admin-v-user-role').textContent = 'Pending';

        document.getElementById('admin-v-email').textContent = item.contact_email || 'Not provided';
        document.getElementById('admin-v-phone').textContent = item.contact_num || 'Not provided';

        const typeBadge = document.getElementById('admin-v-type');
        const statusBox = document.getElementById('admin-v-status-box');
        const statusText = document.getElementById('admin-v-status-text');
        const statusIcon = document.getElementById('admin-v-status-icon');

        if (isLost) {
            typeBadge.textContent = 'Lost';
            typeBadge.className = 'badge bg-danger position-absolute top-0 end-0 m-2 rounded-pill px-3';
            statusBox.className = 'alert alert-danger d-flex align-items-start gap-2 border-0 rounded-3 p-3';
            statusIcon.className = 'bi bi-exclamation-circle-fill fs-5';
            statusIcon.style.color = '#58151C';
            statusText.innerHTML = '<div class="fw-bold mb-1">Someone is looking for this item</div><div class="fw-light">If you have found this item, contact the owner using the provided information.</div>';
        } else {
            typeBadge.textContent = 'Found';
            typeBadge.className = 'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3';
            statusBox.className = 'alert alert-success d-flex align-items-start gap-2 border-0 rounded-3 p-3';
            statusIcon.className = 'bi bi-info-circle-fill fs-5';
            statusIcon.style.color = '#0A3634';
            statusText.innerHTML = '<div class="fw-bold mb-1 text-dark">Surrendered to Lost & Found Office</div><p class="fw-light mb-2">This item has been turned over to the GNC Lost & Found Management Office.</p>';
        }
    });

    // Bind click handlers (in case modal show doesn't fire)
    document.querySelectorAll('.btn-view-details').forEach(btn => {
        btn.addEventListener('click', () => {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show(btn);
        });
    });
})();
</script>
</body>
</html>