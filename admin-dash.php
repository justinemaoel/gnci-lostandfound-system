<?php
session_start();
require 'includes/db.php';

if (!isset($pdo)) {
    die('DB connection not found: $pdo is missing in includes/db.php');
}

// AUTH CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Admin-only gate
$adminEmail   = "admin.lostandfound@gmail.com";
$currentEmail = $_SESSION['email'] ?? '';
if (strtolower(trim($currentEmail)) !== strtolower($adminEmail)) {
    header("Location: user-dash.php");
    exit();
}

// INITIALIZE VARIABLES
$pendingCount  = 0;
$approvedCount = 0;
$totalPosts    = 0;
$activeUsers   = 0;
$pendingItems  = [];
$approvedItems = [];

// SESSION DATA
$firstName = $_SESSION['first_name'] ?? "Admin";
$lastName  = $_SESSION['last_name']  ?? "";
$fullName  = trim($firstName . " " . $lastName);
$role      = $_SESSION['role']       ?? "admin";

// ACTIVE TAB (from URL param)
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'approved' ? 'approved' : 'pending';

// SUCCESS / ERROR FLASH
$flash = '';
if (isset($_GET['success'])) {
    $map = [
        'approved'         => ['success', 'Item has been approved and published.'],
        'rejected_deleted' => ['warning', 'Item has been rejected and removed.'],
    ];
    if (isset($map[$_GET['success']])) {
        [$type, $msg] = $map[$_GET['success']];
        $flash = "<div class='alert alert-{$type} alert-dismissible fade show mb-4' role='alert'>
                    <i class='bi bi-check-circle me-2'></i>{$msg}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// FETCH DATA
if (isset($pdo)) {
    $pendingCount  = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE upload_status = 'pending'")->fetchColumn();
    $approvedCount = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE upload_status = 'approved'")->fetchColumn();
    $totalPosts    = (int)$pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
    $activeUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Pending items
    $stmt = $pdo->query("SELECT i.*, u.first_name, u.last_name, c.category_name
                        FROM items i
                        JOIN users u ON i.user_id = u.id
                        JOIN categories c ON i.category_id = c.id
                        WHERE i.upload_status = 'pending'
                        ORDER BY i.created_at DESC");
    if ($stmt) $pendingItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Approved items
    $stmt2 = $pdo->query("SELECT i.*, u.first_name, u.last_name, c.category_name
                        FROM items i
                        JOIN users u ON i.user_id = u.id
                        JOIN categories c ON i.category_id = c.id
                        WHERE i.upload_status = 'approved'
                        ORDER BY i.created_at DESC");
    if ($stmt2) $approvedItems = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts - GNC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════
    TOP NAVBAR
════════════════════════════════════════════════ -->
    <header class="navbar sticky-top flex-md-nowrap p-3 shadow-sm" style="background-color: #0b4628;">
        <div class="d-flex align-items-center">
            <button class="navbar-toggler d-md-none me-3 border-white text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center text-white p-0" href="<?= $dashboardLink ?>">
                <img src="assets/images/GNC Logo.svg" alt="Logo" width="40" class="me-2">
                <div class="lh-1">
                    <span class="fw-bold d-block small">Guagua National Colleges</span>
                    <small style="font-size: 0.7rem;">Lost & Found</small>
                </div>
            </a>
        </div>

        <div class="text-white d-flex align-items-center">
            <div class="text-end me-3 d-none d-md-block lh-1">
                <div class="fw-bold mb-0 lh-1"><?= htmlspecialchars($fullName) ?></div>
                <small class="text-uppercase opacity-75" style="font-size: 10px;"><?= htmlspecialchars($role) ?></small>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=0b5a30&color=fff" width="35" height="35" class="rounded-circle">
        </div>
    </header>


<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="page-wrap">


    <!-- ═══════════════════════════════════════════
        SIDEBAR
    ════════════════════════════════════════════ -->
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar offcanvas-md offcanvas-start bg-white border-end p-0">
            <div class="offcanvas-header d-md-none border-bottom">
                <h5 class="offcanvas-title fw-bold">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu"></button>
            </div>
            
            <div class="offcanvas-body d-flex flex-column p-3 flex-grow-1">
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item mb-2">
                        <a href="<?= $dashboardLink ?>" class="nav-link d-flex align-items-center" style="color: #0b4628;">
                            <i class="bi bi-house-door-fill me-2"></i> Home
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="browse-item.php" class="nav-link d-flex align-items-center" style="background-color: #d1e7dd; color: #0b4628;">
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

    <!-- ═══════════════════════════════════════════
        MAIN CONTENT
    ════════════════════════════════════════════ -->
    <main class="main-content">

        <?php echo $flash; ?>

        <!-- STAT CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card pending">
                    <div class="stat-label"><i class="bi bi-clock text-warning"></i> Pending Approval</div>
                    <div class="stat-num"><?php echo $pendingCount; ?></div>
                    <div class="stat-sub">Item waiting for review</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card approved">
                    <div class="stat-label"><i class="bi bi-check-circle text-success"></i> Approved Posts</div>
                    <div class="stat-num"><?php echo $approvedCount; ?></div>
                    <div class="stat-sub">Published items</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card total">
                    <div class="stat-label"><i class="bi bi-box text-success"></i> Total Posts</div>
                    <div class="stat-num"><?php echo $totalPosts; ?></div>
                    <div class="stat-sub">All posted items</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card users">
                    <div class="stat-label"><i class="bi bi-people text-success"></i> Active Users</div>
                    <div class="stat-num"><?php echo $activeUsers; ?></div>
                    <div class="stat-sub">Registered users</div>
                </div>
            </div>
        </div>

        <!-- SECTION TITLE -->
        <div class="section-title">Manage Posts</div>

        <!-- TABS + POST NEW BUTTON -->
        <div class="tabs-row">
            <div class="tab-pills">
                <a href="?tab=pending"
                class="tab-pill <?php echo $activeTab === 'pending'  ? 'active' : ''; ?>">
                    Pending Approval
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge bg-warning text-dark ms-1" style="font-size:10px;"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=approved"
                class="tab-pill <?php echo $activeTab === 'approved' ? 'active' : ''; ?>">
                    Approved Posts
                </a>
            </div>
            <a href="post-item.php" class="btn-post-new">
                <i class="bi bi-plus-lg"></i> Post New Item
            </a>
        </div>

        <!-- ─────────────────────────────
            PENDING TAB
        ───────────────────────────── -->
        <?php if ($activeTab === 'pending'): ?>

            <?php if (empty($pendingItems)): ?>
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    No pending items found for approval.
                </div>
            <?php else: ?>
                <?php foreach ($pendingItems as $item): ?>
                    <?php
                        $postType  = strtolower($item['post_type'] ?? '');
                        $isLost    = $postType === 'lost';
                        $itemJson  = htmlspecialchars(
                            json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
                            ENT_QUOTES
                        );
                    ?>
                    <div class="item-card">
                        <!-- Image -->
                        <div class="card-img-col">
                            <img src="uploads/<?php echo htmlspecialchars($item['item_img']); ?>"
                                alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                onerror="this.src='assets/images/placeholder.png'">
                        </div>

                        <!-- Details -->
                        <div class="card-body-col">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                <strong style="font-size:1.05rem;"><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                <?php if ($isLost): ?>
                                    <span class="badge-lost">Lost</span>
                                <?php else: ?>
                                    <span class="badge-found">Found</span>
                                <?php endif; ?>
                                <span class="badge-pending-review">Pending Review</span>
                            </div>
                            <div class="mb-2">
                                <span class="cat-badge"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            </div>
                            <div class="meta-line"><i class="bi bi-geo-alt"></i><?php echo htmlspecialchars($item['location_text']); ?></div>
                            <div class="meta-line"><i class="bi bi-calendar"></i><?php echo date('F d, Y', strtotime($item['date_reported'])); ?></div>
                            <div class="meta-line"><i class="bi bi-person"></i>Submitted by: <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></div>
                            <div class="meta-line"><i class="bi bi-clock"></i>Submitted on: <?php echo date('F d, Y', strtotime($item['created_at'])); ?></div>
                        </div>

                        <!-- Actions -->
                        <div class="card-actions-col">
                            <a href="actions/update_status.php?id=<?php echo (int)$item['id']; ?>&status=approved&redirect=pending"
                            class="btn-approve">
                                <i class="bi bi-check-circle"></i> Approve
                            </a>
                            <a href="actions/update_status.php?id=<?php echo (int)$item['id']; ?>&status=rejected&redirect=pending"
                            class="btn-reject"
                            onclick="return confirm('Are you sure you want to reject and delete this item?')">
                                <i class="bi bi-x-circle"></i> Reject
                            </a>
                            <button type="button"
                                    class="btn-view btn-view-details"
                                    data-item="<?php echo $itemJson; ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#adminViewItemModal">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php endif; ?>

        <!-- ─────────────────────────────
            APPROVED TAB
        ───────────────────────────── -->
        <?php if ($activeTab === 'approved'): ?>

            <?php if (empty($approvedItems)): ?>
                <div class="alert alert-info d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    No approved items yet.
                </div>
            <?php else: ?>
                <?php foreach ($approvedItems as $item): ?>
                    <?php
                        $postType = strtolower($item['post_type'] ?? '');
                        $isLost   = $postType === 'lost';
                        $itemJson = htmlspecialchars(
                            json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
                            ENT_QUOTES
                        );
                    ?>
                    <div class="item-card">
                        <div class="card-img-col">
                            <img src="uploads/<?php echo htmlspecialchars($item['item_img']); ?>"
                                alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                onerror="this.src='assets/images/placeholder.png'">
                        </div>
                        <div class="card-body-col">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                <strong style="font-size:1.05rem;"><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                <?php if ($isLost): ?>
                                    <span class="badge-lost">Lost</span>
                                <?php else: ?>
                                    <span class="badge-found">Found</span>
                                <?php endif; ?>
                                <span class="badge-approved-tag">Approved</span>
                            </div>
                            <div class="mb-2">
                                <span class="cat-badge"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            </div>
                            <div class="meta-line"><i class="bi bi-geo-alt"></i><?php echo htmlspecialchars($item['location_text']); ?></div>
                            <div class="meta-line"><i class="bi bi-calendar"></i><?php echo date('F d, Y', strtotime($item['date_reported'])); ?></div>
                            <div class="meta-line"><i class="bi bi-person"></i>Submitted by: <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></div>
                            <div class="meta-line"><i class="bi bi-clock"></i>Approved on: <?php echo date('F d, Y', strtotime($item['created_at'])); ?></div>
                        </div>
                        <div class="card-actions-col">
                            <a href="actions/update_status.php?id=<?php echo (int)$item['id']; ?>&status=rejected&redirect=approved"
                            class="btn-reject"
                            onclick="return confirm('Are you sure you want to remove this approved item?')">
                                <i class="bi bi-trash"></i> Remove
                            </a>
                            <button type="button"
                                    class="btn-view btn-view-details"
                                    data-item="<?php echo $itemJson; ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#adminViewItemModal">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php endif; ?>

    </main><!-- /main-content -->
</div><!-- /page-wrap -->


<!-- ═══════════════════════════════════════════════
    VIEW DETAILS MODAL
════════════════════════════════════════════════ -->
<div class="modal fade" id="adminViewItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg p-3">
            <div class="modal-header pb-0">
                <h4 class="modal-title fw-bold">Item Details</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">

                    <!-- LEFT COLUMN -->
                    <div class="col-md-5">
                        <div class="position-relative mb-3">
                            <img id="admin-v-img" src=""
                                class="img-fluid rounded-3 w-100"
                                style="height:280px; object-fit:cover; background:#eee;"
                                onerror="this.src='assets/images/placeholder.png'">
                            <span id="admin-v-type" class="badge position-absolute top-0 end-0 m-2 rounded-pill px-3"></span>
                        </div>
                        <h5 class="fw-bold mb-2">Description</h5>
                        <p id="admin-v-desc" class="text-muted small mb-4" style="line-height:1.6;"></p>

                        <h5 class="fw-bold mb-3">Posted by:</h5>
                        <div class="p-3 rounded-3" style="background:#f8f9fa;">
                            <div class="d-flex align-items-center gap-3">
                                <img id="admin-v-avatar" src="" class="rounded-circle border" width="45" height="45" alt="Avatar">
                                <div class="lh-sm">
                                    <div class="fw-bold text-dark" id="admin-v-user-name"></div>
                                    <span id="admin-v-user-role"
                                        class="badge bg-light text-muted border-0 p-0 text-uppercase"
                                        style="font-size:10px;"></span>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top" style="font-size:12px;">
                                <div class="text-success mb-2"><i class="bi bi-envelope me-2"></i><span id="admin-v-email"></span></div>
                                <div class="text-muted"><i class="bi bi-telephone me-2"></i><span id="admin-v-phone"></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="col-md-7 border-start ps-md-4">
                        <h2 id="admin-v-name" class="fw-bold mb-1"></h2>
                        <div class="mb-4">
                            <span id="admin-v-cat" class="badge border fw-normal"
                                style="border-color:#d1e7dd !important; background:#f0fdf4; color:#157347;"></span>
                        </div>
                        <hr class="opacity-10 mb-4">

                        <h5 class="fw-bold mb-3">Location &amp; Time</h5>
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
                            <i id="admin-v-status-icon" class="bi bi-info-circle-fill fs-5"></i>
                            <div id="admin-v-status-text" class="small"></div>
                        </div>
                    </div>

                </div>
            </div><!-- /modal-body -->
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── MOBILE SIDEBAR TOGGLE ── */
(function () {
    const toggle  = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!toggle) return;

    function openSidebar()  { sidebar.classList.add('open');  overlay.classList.add('open'); }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }

    toggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
    overlay.addEventListener('click', closeSidebar);
})();

/* ── VIEW DETAILS MODAL ── */
(function () {
    const modalEl = document.getElementById('adminViewItemModal');
    if (!modalEl) return;

    modalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        let item;
        try {
            item = JSON.parse(button.getAttribute('data-item'));
        } catch (e) {
            console.error('Failed to parse item JSON', e);
            return;
        }

        const type              = (item.post_type || '').toLowerCase();
        const isLost            = type === 'lost';
        const submittedToOffice = !!item.submitted_to_office; // reads directly from DB via JSON

        // Image & name
        document.getElementById('admin-v-img').src  = item.item_img
            ? ('uploads/' + item.item_img)
            : 'assets/images/placeholder.png';
        document.getElementById('admin-v-name').textContent = item.item_name   || '';
        document.getElementById('admin-v-cat').textContent  = item.category_name || 'General';
        document.getElementById('admin-v-loc').textContent  = item.location_text  || '—';
        document.getElementById('admin-v-date').textContent = item.date_reported
            ? new Date(item.date_reported).toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric'})
            : '—';
        document.getElementById('admin-v-time').textContent = item.time_last_seen || '—';
        document.getElementById('admin-v-desc').textContent = item.description    || 'No description provided.';

        // User info
        const avatarName = ((item.first_name || '') + ' ' + (item.last_name || '')).trim();
        document.getElementById('admin-v-avatar').src =
            'https://ui-avatars.com/api/?name=' + encodeURIComponent(avatarName) + '&background=0b5a30&color=fff';
        document.getElementById('admin-v-user-name').textContent = avatarName || '—';
        document.getElementById('admin-v-user-role').textContent = 'Pending';
        document.getElementById('admin-v-email').textContent = item.contact_email || 'Not provided';
        document.getElementById('admin-v-phone').textContent = item.contact_num   || 'Not provided';

        // Type badge & status box
        const typeBadge  = document.getElementById('admin-v-type');
        const statusBox  = document.getElementById('admin-v-status-box');
        const statusText = document.getElementById('admin-v-status-text');
        const statusIcon = document.getElementById('admin-v-status-icon');

        // Reset inline styles so previous modal state doesn't bleed through
        statusBox.removeAttribute('style');
        statusIcon.removeAttribute('style');

        if (isLost) {
            // ── STATE 1: LOST ──────────────────────────────────────────
            typeBadge.textContent          = 'Lost';
            typeBadge.className            = 'badge bg-danger position-absolute top-0 end-0 m-2 rounded-pill px-3';
            statusBox.className            = 'alert d-flex align-items-start gap-3 border-0 rounded-3 p-3';
            statusBox.style.backgroundColor = '#EBCFCD';
            statusBox.style.borderLeft      = '4px solid #5E0006';
            statusIcon.className           = 'bi bi-exclamation-circle-fill fs-5';
            statusIcon.style.color         = '#5E0006';
            statusText.innerHTML           = '<div class="fw-bold mb-1" style="color:#5E0006; font-size:18px;">Someone is looking for this item</div>'
                                        + '<div class="fw-light" style="color:#343A40;">If you have found this item, contact the owner using the provided information.</div>';

        } else if (!submittedToOffice) {
            // ── STATE 2: FOUND — still held by finder ──────────────────
            typeBadge.textContent          = 'Found';
            typeBadge.className            = 'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3';
            statusBox.className            = 'alert d-flex align-items-start gap-3 border-0 rounded-3 p-3';
            statusBox.style.backgroundColor = '#fff3cd';
            statusBox.style.borderLeft      = '4px solid #ffc107';
            statusIcon.className           = 'bi bi-person-fill fs-5';
            statusIcon.style.color         = '#856404';
            statusText.innerHTML           = '<div class="fw-bold mb-1" style="color:#856404; font-size:18px;">Currently Held by Finder</div>'
                                        + '<p class="fw-light mb-0" style="color:#343A40;">The person who found this item is currently holding it. You can contact them directly using the information below.</p>';

        } else {
            // ── STATE 3: FOUND — submitted to office ───────────────────
            typeBadge.textContent          = 'Found';
            typeBadge.className            = 'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3';
            statusBox.className            = 'alert d-flex align-items-start gap-3 border-0 rounded-3 p-3';
            statusBox.style.backgroundColor = '#D4E3DA';
            statusBox.style.borderLeft      = '4px solid #0F6631';
            statusIcon.className           = 'bi bi-info-circle-fill fs-5';
            statusIcon.style.color         = '#0F6631';
            statusText.innerHTML           = '<div class="fw-bold mb-1" style="color:#0F6631; font-size:18px;">Surrendered to Lost &amp; Found Office</div>'
                                        + '<p class="fw-light mb-2" style="color:#343A40;">This item has been turned over to the GNC Lost &amp; Found Management Office.</p>';
        }
    });
})();
</script>
</body>
</html>