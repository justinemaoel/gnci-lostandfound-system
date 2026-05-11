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

// SESSION DATA
$firstName = $_SESSION['first_name'] ?? "Admin";
$lastName  = $_SESSION['last_name']  ?? "";
$fullName  = trim($firstName . " " . $lastName);
$role      = $_SESSION['role']       ?? "admin";

// ACTIVE TAB
$activeTab = (isset($_GET['tab']) && $_GET['tab'] === 'approved') ? 'approved' : 'pending';

// FLASH MESSAGE
$flash = '';
if (isset($_GET['success'])) {
    $flashMap = [
        'approved'         => ['success', 'Item has been approved and published.'],
        'rejected_deleted' => ['warning', 'Item has been rejected and removed.'],
    ];
    if (isset($flashMap[$_GET['success']])) {
        [$fType, $fMsg] = $flashMap[$_GET['success']];
        $flash = "<div class='alert alert-{$fType} alert-dismissible fade show mb-4' role='alert'>
                    <i class='bi bi-check-circle me-2'></i>{$fMsg}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    }
}

// FETCH COUNTS
$counts = $pdo->query("
    SELECT
        SUM(upload_status = 'pending')  AS pending,
        SUM(upload_status = 'approved') AS approved,
        COUNT(*)                        AS total
    FROM items
")->fetch(PDO::FETCH_ASSOC);

$pendingCount  = (int)($counts['pending']  ?? 0);
$approvedCount = (int)($counts['approved'] ?? 0);
$totalPosts    = (int)($counts['total']    ?? 0);
$activeUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// PAGINATION
$perPage     = 10;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE upload_status = ?");
$countStmt->execute([$activeTab]);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalItems / $perPage);

// FETCH ITEMS
$stmt = $pdo->prepare("
    SELECT i.id, i.item_name, i.post_type, i.location_text, i.date_reported,
           i.time_last_seen, i.description, i.item_img, i.upload_status,
           i.submitted_to_office, i.contact_email, i.contact_num, i.created_at,
           i.category_id,
           u.first_name, u.last_name, u.role,
           c.category_name
    FROM items i
    JOIN users u ON i.user_id = u.id
    JOIN categories c ON i.category_id = c.id
    WHERE i.upload_status = ?
    ORDER BY i.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$activeTab, $perPage, $offset]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH CATEGORIES
$categories = $pdo->query("SELECT id, category_name FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts - GNC</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://ui-avatars.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>
<body class="bg-light">

<!-- TOP NAVBAR -->
<header class="navbar sticky-top flex-md-nowrap p-3 shadow-sm" style="background-color: #0b4628;">
    <div class="d-flex align-items-center">
        <button class="d-md-none me-3 btn btn-sm border-0 text-white" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <i class="bi bi-list fs-4"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center text-white p-0" href="admin-dash.php">
            <img src="assets/images/GNC Logo.svg" alt="Logo" width="40" class="me-2">
            <div class="lh-1 d-none d-md-block">
                <span class="fw-bold d-block small">Guagua National Colleges</span>
                <small style="font-size:0.7rem;">Lost &amp; Found</small>
            </div>
        </a>
    </div>
    <div class="text-white d-flex align-items-center">
        <div class="text-end me-3 d-none d-md-block lh-1">
            <div class="fw-bold mb-0 lh-1"><?= htmlspecialchars($fullName) ?></div>
            <small class="text-uppercase opacity-75" style="font-size:10px;"><?= htmlspecialchars($role) ?></small>
        </div>
        <img id="nav-avatar"
            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='35' height='35'%3E%3Crect width='35' height='35' rx='50' fill='%230b5a30'/%3E%3C/svg%3E"
            data-name="<?= urlencode($fullName) ?>"
            width="35" height="35" class="rounded-circle" alt="Avatar">
    </div>
</header>

<!-- MOBILE SIDEBAR -->
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileSidebar"
    data-bs-scroll="true" data-bs-backdrop="true" style="width:260px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <ul class="nav nav-pills flex-column flex-grow-1">
            <li class="nav-item mb-2"><a href="admin-dash.php" class="nav-link d-flex align-items-center" style="background-color:#d1e7dd;color:#0b4628;"><i class="bi bi-house-door-fill me-2"></i>Home</a></li>
            <li class="nav-item mb-2"><a href="browse-item.php" class="nav-link d-flex align-items-center" style="color:#0b4628;"><i class="bi bi-search me-2"></i>Browse Item</a></li>
            <li class="nav-item mb-2"><a href="my-activity.php" class="nav-link d-flex align-items-center" style="color:#0b4628;"><i class="bi bi-clock-history me-2"></i>My Activity</a></li>
        </ul>
        <ul class="nav nav-pills flex-column border-top pt-2 mt-2">
            <li class="nav-item"><a href="auth/logout.php" class="nav-link text-danger d-flex align-items-center fw-semibold"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
        </ul>
    </div>
</div>

<div class="container-fluid">
    <div class="row">

        <!-- DESKTOP SIDEBAR -->
        <nav class="col-md-3 col-lg-2 d-none d-md-flex flex-column bg-white border-end p-3"
            style="min-height:calc(100vh - 65px);position:sticky;top:65px;height:calc(100vh - 65px);">
            <ul class="nav nav-pills flex-column flex-grow-1">
                <li class="nav-item mb-2"><a href="admin-dash.php" class="nav-link d-flex align-items-center" style="background-color:#d1e7dd;color:#0b4628;"><i class="bi bi-house-door-fill me-2"></i>Home</a></li>
                <li class="nav-item mb-2"><a href="browse-item.php" class="nav-link d-flex align-items-center" style="color:#0b4628;"><i class="bi bi-search me-2"></i>Browse Item</a></li>
                <li class="nav-item mb-2"><a href="my-activity.php" class="nav-link d-flex align-items-center" style="color:#0b4628;"><i class="bi bi-clock-history me-2"></i>My Activity</a></li>
            </ul>
            <ul class="nav nav-pills flex-column border-top pt-2 mt-2">
                <li class="nav-item"><a href="auth/logout.php" class="nav-link text-danger d-flex align-items-center fw-semibold"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
            </ul>
        </nav>

        <!-- MAIN CONTENT -->
        <main class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 min-vh-100">

            <?= $flash ?>

            <!-- STAT CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card pending">
                        <div class="stat-label"><i class="bi bi-clock text-warning"></i> Pending Approval</div>
                        <div class="stat-num"><?= $pendingCount ?></div>
                        <div class="stat-sub">Items waiting for review</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card approved">
                        <div class="stat-label"><i class="bi bi-check-circle text-success"></i> Approved Posts</div>
                        <div class="stat-num"><?= $approvedCount ?></div>
                        <div class="stat-sub">Published items</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card total">
                        <div class="stat-label"><i class="bi bi-box text-success"></i> Total Posts</div>
                        <div class="stat-num"><?= $totalPosts ?></div>
                        <div class="stat-sub">All posted items</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card users">
                        <div class="stat-label"><i class="bi bi-people text-success"></i> Active Users</div>
                        <div class="stat-num"><?= $activeUsers ?></div>
                        <div class="stat-sub">Registered users</div>
                    </div>
                </div>
            </div>

            <div class="section-title">Manage Posts</div>

            <!-- TABS + POST NEW -->
            <div class="tabs-row">
                <div class="tab-pills">
                    <a href="?tab=pending&page=1" class="tab-pill <?= $activeTab === 'pending' ? 'active' : '' ?>">
                        Pending Approval
                        <?php if ($pendingCount > 0): ?>
                            <span class="badge bg-warning text-dark ms-1" style="font-size:10px;"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=approved&page=1" class="tab-pill <?= $activeTab === 'approved' ? 'active' : '' ?>">
                        Approved Posts
                    </a>
                </div>
                <button type="button" class="btn-post-new" data-bs-toggle="modal" data-bs-target="#postItemModal">
                    <i class="bi bi-plus-lg"></i> Post New Item
                </button>
            </div>

            <!-- ITEMS LIST -->
            <?php if (empty($items)): ?>
                <div class="alert alert-<?= $activeTab === 'pending' ? 'warning' : 'info' ?> d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <?= $activeTab === 'pending' ? 'No pending items found for approval.' : 'No approved items yet.' ?>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item):
                    $isLost   = strtolower($item['post_type'] ?? '') === 'lost';
                    $itemJson = htmlspecialchars(
                        json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
                        ENT_QUOTES
                    );
                ?>
                    <div class="item-card">
                        <div class="card-img-col">
                            <img loading="lazy"
                                 src="uploads/<?= htmlspecialchars($item['item_img']) ?>"
                                 alt="<?= htmlspecialchars($item['item_name']) ?>"
                                 onerror="this.src='assets/images/placeholder-image.jpg'">
                        </div>
                        <div class="card-body-col">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                <strong style="font-size:1.05rem;"><?= htmlspecialchars($item['item_name']) ?></strong>
                                <span class="badge rounded-pill px-3 py-1 fw-semibold"
                                      style="<?= $isLost ? 'background-color:#dc3545;color:#fff;' : 'background-color:#198754;color:#fff;' ?>">
                                    <?= $isLost ? 'Lost' : 'Found' ?>
                                </span>
                                <span class="<?= $activeTab === 'pending' ? 'badge-pending-review' : 'badge-approved-tag' ?>">
                                    <?= $activeTab === 'pending' ? 'Pending Review' : 'Approved' ?>
                                </span>
                            </div>
                            <div class="mb-2"><span class="cat-badge"><?= htmlspecialchars($item['category_name']) ?></span></div>
                            <div class="meta-line"><i class="bi bi-geo-alt"></i><?= htmlspecialchars($item['location_text']) ?></div>
                            <div class="meta-line"><i class="bi bi-calendar"></i><?= date('F d, Y', strtotime($item['date_reported'])) ?></div>
                            <div class="meta-line"><i class="bi bi-person"></i>Submitted by: <?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></div>
                            <div class="meta-line">
                                <i class="bi bi-clock"></i>
                                <?= $activeTab === 'pending' ? 'Submitted' : 'Approved' ?> on: <?= date('F d, Y', strtotime($item['created_at'])) ?>
                            </div>
                        </div>
                        <div class="card-actions-col">
                            <?php if ($activeTab === 'pending'): ?>
                                <a href="actions/update_status.php?id=<?= (int)$item['id'] ?>&status=approved&redirect=pending" class="btn-approve">
                                    <i class="bi bi-check-circle"></i> Approve
                                </a>
                                <a href="actions/update_status.php?id=<?= (int)$item['id'] ?>&status=rejected&redirect=pending"
                                   class="btn-reject"
                                   onclick="return confirm('Are you sure you want to reject and delete this item?')">
                                    <i class="bi bi-x-circle"></i> Reject
                                </a>
                            <?php else: ?>
                                <a href="actions/update_status.php?id=<?= (int)$item['id'] ?>&status=rejected&redirect=approved"
                                   class="btn-reject"
                                   onclick="return confirm('Are you sure you want to remove this approved item?')">
                                    <i class="bi bi-trash"></i> Remove
                                </a>
                            <?php endif; ?>

                            <!-- EDIT BUTTON -->
                            <button type="button"
                                    class="btn-view"
                                    style="background-color:#f8f9fa; border:1px solid #dee2e6; color:#212529;"
                                    data-item="<?= $itemJson ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#adminEditItemModal">
                                <i class="bi bi-pencil"></i> Edit
                            </button>

                            <!-- VIEW DETAILS BUTTON -->
                            <button type="button"
                                    class="btn-view btn-view-details"
                                    data-item="<?= $itemJson ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#adminViewItemModal">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item"><a class="page-link" href="?tab=<?= $activeTab ?>&page=<?= $currentPage - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                            <?php endif; ?>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="?tab=<?= $activeTab ?>&page=<?= $p ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="?tab=<?= $activeTab ?>&page=<?= $currentPage + 1 ?>"><i class="bi bi-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

        </main>
    </div>
</div>


<!-- ══════════════════════════════════════════════
     POST ITEM MODAL
══════════════════════════════════════════════ -->
<div class="modal fade" id="postItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-0 shadow-lg">
            <div class="modal-header px-4 py-3 border-0" style="background-color:#0b4628;color:white;">
                <h5 class="modal-title fw-bold">Post an Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/post.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4 pb-2">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Post Type *</label>
                                <div class="d-flex gap-2">
                                    <input type="radio" class="btn-check" name="post_type" id="adminTypeFound" value="Found">
                                    <label class="btn btn-outline-success w-100 py-2" for="adminTypeFound">I found something</label>
                                    <input type="radio" class="btn-check" name="post_type" id="adminTypeLost" value="Lost" checked>
                                    <label class="btn btn-outline-danger w-100 py-2" for="adminTypeLost">I lost something</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Item Name *</label>
                                <input type="text" name="item_name" class="form-control" placeholder="e.g., Black Cellphone" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Category *</label>
                                <select name="category_id" class="form-select" required>
                                    <option selected disabled value="">Select a category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Location *</label>
                                <input type="text" name="location_input" class="form-control" placeholder="e.g., Library - 2nd Floor" required>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label fw-bold small">Date *</label>
                                    <input type="date" class="form-control" name="date" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold small">Time Last Seen *</label>
                                    <input type="time" class="form-control" name="time" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex flex-column">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Description *</label>
                                <textarea name="desc" class="form-control" rows="4" placeholder="Provide detailed description..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Upload Photo *</label>
                                <div class="border rounded p-4 text-center d-flex flex-column align-items-center"
                                     id="admin-upload-container" style="cursor:pointer;background:#f8f9fa;gap:0.75rem;"
                                     onclick="document.getElementById('admin_item_image').click()">
                                    <div id="admin-upload-placeholder">
                                        <i class="bi bi-upload fs-3 text-muted"></i>
                                        <p class="small text-muted mb-2">Click to upload or drag and drop<br>PNG, JPG, JPEG up to 5MB</p>
                                    </div>
                                    <img id="admin-image-preview" src="" alt="Preview" class="img-fluid rounded d-none" style="max-height:150px;width:100%;">
                                    <input type="file" name="item_image" id="admin_item_image" class="d-none" accept="image/*" required>
                                    <button type="button" class="btn btn-light btn-sm shadow-sm px-3"
                                            onclick="event.stopPropagation();document.getElementById('admin_item_image').click()">
                                        Choose File
                                    </button>
                                </div>
                            </div>
                            <div class="mt-auto p-3 rounded-3" style="background-color:#d1e7dd;border:2px solid #5a8f6f;">
                                <div class="form-check mb-2" id="admin-post-checkbox-section">
                                    <input class="form-check-input" type="checkbox" name="submitted_to_office" id="admin_submitted_to_office" style="width:20px;height:20px;">
                                    <label class="form-check-label fw-bold ms-2" for="admin_submitted_to_office" style="font-size:15px;">
                                        I have submitted this item to the Lost &amp; Found Office
                                    </label>
                                </div>
                                <p class="small text-muted mb-0 ms-4" id="admin-post-checkbox-desc">Check this if you've already turned the item over to the office.</p>
                                <hr id="admin-post-office-hr" style="border-top:2px solid #5a8f6f;margin:14px 0;">
                                <p class="small mb-3" style="color:#666;">Contact information for this item</p>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="fw-bold mb-1 small">Email Address *</label>
                                        <input type="email" name="email" class="form-control" style="background:#D2CECE;border:1px solid #999;" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="fw-bold mb-1 small">Phone Number *</label>
                                        <input type="text" name="phone" class="form-control" style="background:#D2CECE;border:1px solid #999;" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-start">
                    <button type="submit" name="submit_post" class="btn btn-success px-5 fw-bold py-2">Post Item</button>
                    <button type="button" class="btn btn-outline-secondary px-5 fw-bold py-2" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════
     EDIT ITEM MODAL
══════════════════════════════════════════════ -->
<div class="modal fade" id="adminEditItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-0 shadow-lg">
            <div class="modal-header px-4 py-3 border-0" style="background-color:#0b4628;color:white;">
                <h5 class="modal-title fw-bold">Edit Item Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/edit-item.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="item_id" id="admin_edit_item_id">
                <div class="modal-body p-4 pb-2">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Post Type *</label>
                                <div class="d-flex gap-2">
                                    <input type="radio" class="btn-check" name="post_type" id="adminEditTypeFound" value="Found">
                                    <label class="btn btn-outline-success w-100 py-2" for="adminEditTypeFound">I found something</label>
                                    <input type="radio" class="btn-check" name="post_type" id="adminEditTypeLost" value="Lost">
                                    <label class="btn btn-outline-danger w-100 py-2" for="adminEditTypeLost">I lost something</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Item Name *</label>
                                <input type="text" name="item_name" id="admin_edit_item_name" class="form-control" placeholder="e.g., Black Cellphone" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Category *</label>
                                <select name="category_id" id="admin_edit_category_id" class="form-select" required>
                                    <option disabled>Select a category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Location *</label>
                                <input type="text" name="location_input" id="admin_edit_location" class="form-control" placeholder="e.g., Library - 2nd Floor" required>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label fw-bold small">Date *</label>
                                    <input type="date" class="form-control" name="date" id="admin_edit_date" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold small">Time Last Seen *</label>
                                    <input type="time" class="form-control" name="time" id="admin_edit_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex flex-column">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Description *</label>
                                <textarea name="desc" id="admin_edit_desc" class="form-control" rows="4" placeholder="Provide detailed description..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Upload Photo <small class="fw-normal text-muted">(leave empty to keep existing)</small></label>
                                <div class="border rounded p-4 text-center d-flex flex-column align-items-center"
                                     id="admin-edit-upload-container" style="cursor:pointer;background:#f8f9fa;gap:0.75rem;"
                                     onclick="document.getElementById('admin_edit_item_image').click()">
                                    <div id="admin-edit-upload-placeholder" class="d-none">
                                        <i class="bi bi-upload fs-3 text-muted"></i>
                                        <p class="small text-muted mb-2">Click to upload or drag and drop<br>PNG, JPG, JPEG up to 5MB</p>
                                    </div>
                                    <img id="admin-edit-image-preview" src="" alt="Preview" class="img-fluid rounded d-none" style="max-height:150px;width:100%;">
                                    <input type="file" name="item_image" id="admin_edit_item_image" class="d-none" accept="image/*">
                                    <button type="button" class="btn btn-light btn-sm shadow-sm px-3"
                                            onclick="event.stopPropagation();document.getElementById('admin_edit_item_image').click()">
                                        Choose File
                                    </button>
                                </div>
                            </div>
                            <div class="mt-auto p-3 rounded-3" style="background-color:#d1e7dd;border:2px solid #5a8f6f;">
                                <div class="form-check mb-2" id="admin-edit-checkbox-section">
                                    <input class="form-check-input" type="checkbox" name="submitted_to_office" id="admin_edit_submitted_to_office" style="width:20px;height:20px;">
                                    <label class="form-check-label fw-bold ms-2" for="admin_edit_submitted_to_office" style="font-size:15px;">
                                        I have submitted this item to the Lost &amp; Found Office
                                    </label>
                                </div>
                                <p class="small text-muted mb-0 ms-4" id="admin-edit-checkbox-desc">Check this if you've already turned the item over to the office.</p>
                                <hr id="admin-edit-office-hr" style="border-top:2px solid #5a8f6f;margin:14px 0;">
                                <p class="small mb-3" style="color:#666;">Contact information for this item</p>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="fw-bold mb-1 small">Email Address *</label>
                                        <input type="email" name="email" id="admin_edit_email" class="form-control" style="background:#D2CECE;border:1px solid #999;" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="fw-bold mb-1 small">Phone Number *</label>
                                        <input type="text" name="phone" id="admin_edit_phone" class="form-control" style="background:#D2CECE;border:1px solid #999;" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-start">
                    <button type="submit" name="submit_edit" class="btn btn-success px-5 fw-bold py-2">Save Changes</button>
                    <button type="button" class="btn btn-outline-secondary px-5 fw-bold py-2" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════
     VIEW DETAILS MODAL
══════════════════════════════════════════════ -->
<div class="modal fade" id="adminViewItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg p-3">
            <div class="modal-header pb-0">
                <h4 class="modal-title fw-bold">Item Details</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="position-relative mb-3">
                            <img id="admin-v-img" src="" class="img-fluid rounded-3 w-100"
                                 style="height:280px;object-fit:cover;background:#eee;"
                                 onerror="this.src='assets/images/placeholder-image.jpg'">
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
                                    <span id="admin-v-user-role" class="badge bg-light text-muted border-0 p-0 text-uppercase" style="font-size:10px;"></span>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top" style="font-size:12px;">
                                <div class="text-success mb-2"><i class="bi bi-envelope me-2"></i><span id="admin-v-email"></span></div>
                                <div class="text-muted"><i class="bi bi-telephone me-2"></i><span id="admin-v-phone"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7 border-start ps-md-4">
                        <h2 id="admin-v-name" class="fw-bold mb-1"></h2>
                        <div class="mb-4">
                            <span id="admin-v-cat" class="badge border fw-normal"
                                  style="border-color:#d1e7dd !important;background:#f0fdf4;color:#157347;"></span>
                        </div>
                        <hr class="opacity-10 mb-4">
                        <h5 class="fw-bold mb-3">Location &amp; Time</h5>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-geo-alt text-success fs-5 me-3"></i>
                            <div><div class="text-muted small">Location</div><div class="fw-bold" id="admin-v-loc"></div></div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-calendar-event text-success fs-5 me-3"></i>
                            <div><div class="text-muted small">Date Reported</div><div class="fw-bold" id="admin-v-date"></div></div>
                        </div>
                        <div class="d-flex align-items-start mb-4">
                            <i class="bi bi-clock text-success fs-5 me-3"></i>
                            <div><div class="text-muted small">Time Reported</div><div class="fw-bold" id="admin-v-time"></div></div>
                        </div>
                        <hr class="opacity-10 mb-4">
                        <h5 class="fw-bold mb-2">Item Status</h5>
                        <div id="admin-v-status-box" class="alert d-flex align-items-start gap-3 border-0 rounded-3 p-3">
                            <i id="admin-v-status-icon" class="bi bi-info-circle-fill fs-5"></i>
                            <div id="admin-v-status-text" class="small"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Navbar avatar ─────────────────────────────────────────────────────────
    const navAvatar = document.getElementById('nav-avatar');
    if (navAvatar) {
        navAvatar.src = 'https://ui-avatars.com/api/?name=' + navAvatar.dataset.name + '&background=0b5a30&color=fff';
    }

    // ── Helper: show/hide checkbox + desc + hr based on post type ─────────────
    function toggleOfficeElements(prefix, typeValue) {
        const isLost = typeValue === 'Lost';
        const map = {
            post: ['admin-post-checkbox-section', 'admin-post-checkbox-desc', 'admin-post-office-hr'],
            edit: ['admin-edit-checkbox-section', 'admin-edit-checkbox-desc', 'admin-edit-office-hr'],
        };
        (map[prefix] || []).forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.style.display = isLost ? 'none' : '';
        });
    }

    // ── POST MODAL ────────────────────────────────────────────────────────────
    const postModal = document.getElementById('postItemModal');
    if (postModal) {
        postModal.addEventListener('show.bs.modal', function () {
            const checked = document.querySelector('#postItemModal input[name="post_type"]:checked');
            if (checked) toggleOfficeElements('post', checked.value);
        });
        postModal.addEventListener('hidden.bs.modal', function () {
            const preview     = document.getElementById('admin-image-preview');
            const placeholder = document.getElementById('admin-upload-placeholder');
            const input       = document.getElementById('admin_item_image');
            if (preview)     { preview.src = ''; preview.classList.add('d-none'); }
            if (placeholder) { placeholder.classList.remove('d-none'); }
            if (input)       { input.value = ''; }
        });
        document.querySelectorAll('#postItemModal input[name="post_type"]').forEach(function (r) {
            r.addEventListener('change', function () { toggleOfficeElements('post', this.value); });
        });
    }
    // Run once on load (Lost is default-checked)
    const defaultPostType = document.querySelector('#postItemModal input[name="post_type"]:checked');
    if (defaultPostType) toggleOfficeElements('post', defaultPostType.value);

    // Post modal image preview
    const postImgInput = document.getElementById('admin_item_image');
    if (postImgInput) {
        postImgInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            const preview = document.getElementById('admin-image-preview');
            const placeholder = document.getElementById('admin-upload-placeholder');
            const reader = new FileReader();
            reader.onload = function (ev) {
                preview.src = ev.target.result;
                preview.classList.remove('d-none');
                placeholder.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    }

    // ── EDIT MODAL ────────────────────────────────────────────────────────────
    const editModal = document.getElementById('adminEditItemModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;
            let item;
            try { item = JSON.parse(btn.getAttribute('data-item')); }
            catch (e) { console.error('Failed to parse item JSON', e); return; }

            document.getElementById('admin_edit_item_id').value      = item.id            || '';
            document.getElementById('admin_edit_item_name').value    = item.item_name     || '';
            document.getElementById('admin_edit_category_id').value  = item.category_id  || '';
            document.getElementById('admin_edit_location').value     = item.location_text || '';
            document.getElementById('admin_edit_desc').value         = item.description   || '';
            document.getElementById('admin_edit_email').value        = item.contact_email || '';
            document.getElementById('admin_edit_phone').value        = item.contact_num   || '';
            document.getElementById('admin_edit_submitted_to_office').checked = !!parseInt(item.submitted_to_office);

            if (item.date_reported) {
                document.getElementById('admin_edit_date').value = item.date_reported.substring(0, 10);
            }
            if (item.time_last_seen) {
                document.getElementById('admin_edit_time').value = item.time_last_seen.substring(0, 5);
            }

            // Post type radio
            const typeId = (item.post_type || '').toLowerCase() === 'found' ? 'adminEditTypeFound' : 'adminEditTypeLost';
            document.getElementById(typeId).checked = true;
            toggleOfficeElements('edit', item.post_type || 'Lost');

            // Image preview
            const previewImg  = document.getElementById('admin-edit-image-preview');
            const placeholder = document.getElementById('admin-edit-upload-placeholder');
            if (item.item_img) {
                previewImg.src = 'uploads/' + item.item_img;
                previewImg.classList.remove('d-none');
                placeholder.classList.add('d-none');
            } else {
                previewImg.classList.add('d-none');
                placeholder.classList.remove('d-none');
            }
        });

        document.querySelectorAll('#adminEditItemModal input[name="post_type"]').forEach(function (r) {
            r.addEventListener('change', function () { toggleOfficeElements('edit', this.value); });
        });

        const editImgInput = document.getElementById('admin_edit_item_image');
        if (editImgInput) {
            editImgInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (!file) return;
                const preview = document.getElementById('admin-edit-image-preview');
                const placeholder = document.getElementById('admin-edit-upload-placeholder');
                const reader = new FileReader();
                reader.onload = function (ev) {
                    preview.src = ev.target.result;
                    preview.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            });
        }
    }

    // ── VIEW DETAILS MODAL ────────────────────────────────────────────────────
    const viewModalEl = document.getElementById('adminViewItemModal');
    if (!viewModalEl) return;

    const STATUS_CONFIG = {
        lost: {
            label: 'Lost', badge: 'badge bg-danger position-absolute top-0 end-0 m-2 rounded-pill px-3',
            bg: '#EBCFCD', border: '4px solid #5E0006',
            icon: 'bi bi-exclamation-circle-fill fs-5', color: '#5E0006',
            title: 'Someone is looking for this item',
            body: 'If you have found this item, contact the owner using the provided information.',
        },
        foundHeld: {
            label: 'Found', badge: 'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3',
            bg: '#fff3cd', border: '4px solid #ffc107',
            icon: 'bi bi-exclamation-circle-fill fs-5', color: '#856404',
            title: 'Currently Held by Finder',
            body: 'The person who found this item is currently holding it.',
        },
        foundOffice: {
            label: 'Found',
            badge: 'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3',
            bg: '#D4E3DA',
            border: '4px solid #0F6631',
            icon: 'bi bi-info-circle-fill fs-5',
            color: '#0F6631',
            title: 'Surrendered to Lost & Found Office',
            body: 'This item has been turned over to the GNC Lost & Found Management Office. Please visit the office during business hours to claim your item.',
            hours: 'Monday – Saturday, 8:00 AM – 5:00 PM',
            location: 'Main Building, Ground Floor'
        },
    };

    viewModalEl.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;
        let item;
        try { item = JSON.parse(btn.getAttribute('data-item')); }
        catch (e) { console.error('Failed to parse item JSON', e); return; }

        const isLost = (item.post_type || '').toLowerCase() === 'lost';
        const cfg    = isLost ? STATUS_CONFIG.lost
                     : parseInt(item.submitted_to_office) ? STATUS_CONFIG.foundOffice
                     : STATUS_CONFIG.foundHeld;

        document.getElementById('admin-v-img').src          = item.item_img ? 'uploads/' + item.item_img : 'assets/images/placeholder-image.jpg';
        document.getElementById('admin-v-name').textContent = item.item_name     || '';
        document.getElementById('admin-v-cat').textContent  = item.category_name || 'General';
        document.getElementById('admin-v-loc').textContent  = item.location_text || '—';
        document.getElementById('admin-v-date').textContent = item.date_reported
            ? new Date(item.date_reported).toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' })
            : '—';
        document.getElementById('admin-v-time').textContent = item.time_last_seen || '—';
        document.getElementById('admin-v-desc').textContent = item.description   || 'No description provided.';

        const avatarName = ((item.first_name || '') + ' ' + (item.last_name || '')).trim();
        document.getElementById('admin-v-avatar').src            = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(avatarName) + '&background=0b5a30&color=fff';
        document.getElementById('admin-v-user-name').textContent = avatarName         || '—';
        document.getElementById('admin-v-user-role').textContent = item.role          || 'User';
        document.getElementById('admin-v-email').textContent     = item.contact_email || 'Not provided';
        document.getElementById('admin-v-phone').textContent     = item.contact_num   || 'Not provided';

        const typeBadge  = document.getElementById('admin-v-type');
        const statusBox  = document.getElementById('admin-v-status-box');
        const statusIcon = document.getElementById('admin-v-status-icon');
        const statusText = document.getElementById('admin-v-status-text');

        typeBadge.textContent           = cfg.label;
        typeBadge.className             = cfg.badge;
        statusBox.className             = 'alert d-flex align-items-start gap-3 border-0 rounded-3 p-3';
        statusBox.style.backgroundColor = cfg.bg;
        statusBox.style.borderLeft      = cfg.border;
        statusIcon.className            = cfg.icon;
        statusIcon.style.color          = cfg.color;

        // ── FIX: render hours and location if present ──────────────────────
        statusText.innerHTML = `<div class="fw-bold mb-1" style="color:${cfg.color};font-size:18px;">${cfg.title}</div>`
                             + `<div class="fw-light" style="color:#343A40;">${cfg.body}</div>`
                             + (cfg.hours    ? `<div class="mt-2 small"><strong>Office Hours:</strong> ${cfg.hours}</div>`       : '')
                             + (cfg.location ? `<div class="small"><strong>Location:</strong> ${cfg.location}</div>` : '');
    });
});
</script>
</body>
</html>