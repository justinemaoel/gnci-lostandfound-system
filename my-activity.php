<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$firstName = $_SESSION['first_name'] ?? 'User';
$lastName  = $_SESSION['last_name']  ?? '';
$fullName  = trim($firstName . " " . $lastName);
$role      = $_SESSION['role'] ?? 'student';

// FETCH CATEGORIES
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

// FETCH USER ITEMS
$stmt = $pdo->prepare("
    SELECT items.*, categories.category_name
    FROM items
    LEFT JOIN categories ON items.category_id = categories.id
    WHERE items.user_id = ?
    ORDER BY items.created_at DESC
");
$stmt->execute([$user_id]);
$items      = $stmt->fetchAll();
$item_count = count($items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - GNC</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://ui-avatars.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/my-activity-style.css">
</head>
<body>

<!-- TOP NAVBAR -->
<header class="navbar sticky-top flex-md-nowrap p-3 shadow-sm" style="background-color: #0b4628;">
    <div class="d-flex align-items-center">
        <button class="d-md-none me-3 btn btn-sm border-0 text-white" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
            <i class="bi bi-list fs-4"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center text-white p-0" href="user-dash.php">
            <img src="assets/images/GNC Logo.svg" alt="Logo" width="40" class="me-2">
            <div class="lh-1 d-none d-md-block">
                <span class="fw-bold d-block small">Guagua National Colleges</span>
                <small style="font-size: 0.7rem;">Lost &amp; Found</small>
            </div>
        </a>
    </div>
    <div class="text-white d-flex align-items-center">
        <div class="text-end me-3 d-none d-md-block lh-1">
            <div class="fw-bold mb-0 lh-1"><?= htmlspecialchars($fullName) ?></div>
            <small class="text-uppercase opacity-75" style="font-size: 10px;"><?= htmlspecialchars($role) ?></small>
        </div>
        <img id="nav-avatar"
             src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='35' height='35'%3E%3Crect width='35' height='35' rx='50' fill='%230b5a30'/%3E%3C/svg%3E"
             data-name="<?= urlencode($fullName) ?>"
             width="35" height="35" class="rounded-circle shadow-sm" alt="Avatar">
    </div>
</header>

<!-- MOBILE OFFCANVAS SIDEBAR -->
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileSidebar"
     data-bs-scroll="true" data-bs-backdrop="true" style="width: 260px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <ul class="nav nav-pills flex-column flex-grow-1">
            <li class="nav-item mb-2">
                <a href="admin-dash.php" class="nav-link d-flex align-items-center"
                   style="<?= basename($_SERVER['PHP_SELF']) === 'admin-dash.php' ? 'background-color: #d1e7dd;' : '' ?> color: #0b4628;">
                    <i class="bi bi-house-door-fill me-2"></i> Home
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="browse-item.php" class="nav-link d-flex align-items-center" style="<?= basename($_SERVER['PHP_SELF']) === 'browse-item.php' ? 'background-color: #d1e7dd;' : '' ?> color: #0b4628;">
                    <i class="bi bi-search me-2"></i> Browse Item
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="my-activity.php" class="nav-link d-flex align-items-center" style="<?= basename($_SERVER['PHP_SELF']) === 'my-activity.php' ? 'background-color: #d1e7dd;' : '' ?> color: #0b4628;">
                    <i class="bi bi-clock-history me-2"></i> My Activity
                </a>
            </li>
        </ul>
        <ul class="nav nav-pills flex-column border-top pt-2 mt-2">
            <li class="nav-item">
                <a href="auth/logout.php" class="nav-link text-danger d-flex align-items-center fw-semibold">
                    <i class="bi bi-box-arrow-right me-2"></i> Sign out
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="container-fluid">
    <div class="row">

        <!-- DESKTOP SIDEBAR -->
        <nav class="col-md-3 col-lg-2 d-none d-md-flex flex-column bg-white border-end p-3"
             style="min-height: calc(100vh - 65px); position: sticky; top: 65px; height: calc(100vh - 65px);">
            <ul class="nav nav-pills flex-column flex-grow-1">
                <li class="nav-item mb-2">
                    <a href="admin-dash.php" class="nav-link d-flex align-items-center"
                       style="<?= basename($_SERVER['PHP_SELF']) === 'admin-dash.php' ? 'background-color: #d1e7dd;' : '' ?> color: #0b4628;">
                        <i class="bi bi-house-door-fill me-2"></i> Home
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="browse-item.php" class="nav-link d-flex align-items-center" style="<?= basename($_SERVER['PHP_SELF']) === 'browse-item.php' ? 'background-color: #d1e7dd;' : '' ?> color: #0b4628;">
                        <i class="bi bi-search me-2"></i> Browse Item
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="my-activity.php" class="nav-link d-flex align-items-center" style="<?= basename($_SERVER['PHP_SELF']) === 'my-activity.php' ? 'background-color: #d1e7dd;' : '' ?> color: #0b4628;">
                        <i class="bi bi-clock-history me-2"></i> My Activity
                    </a>
                </li>
            </ul>
            <ul class="nav nav-pills flex-column border-top pt-2 mt-2">
                <li class="nav-item">
                    <a href="auth/logout.php" class="nav-link text-danger d-flex align-items-center fw-semibold">
                        <i class="bi bi-box-arrow-right me-2"></i> Sign out
                    </a>
                </li>
            </ul>
        </nav>

        <!-- MAIN CONTENT -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 bg-light min-vh-100">
            <div class="d-flex justify-content-between align-items-center pt-4 mb-4">
                <h3 class="fw-bold mb-0">My Posts</h3>
                <button type="button" data-bs-toggle="modal" data-bs-target="#postItemModal"
                        class="btn btn-success d-flex align-items-center px-3"
                        style="background-color: #157347; border: none; border-radius: 8px;">
                    <i class="bi bi-plus-lg me-2"></i> Post New Item
                </button>
            </div>

            <!-- FILTER PILLS -->
            <div class="mb-4">
                <div class="d-inline-flex p-1 bg-white shadow-sm rounded-pill border">
                    <button class="btn btn-success rounded-pill px-4 py-2 fw-semibold small active-filter" data-filter="all">All Items</button>
                    <button class="btn btn-link text-dark text-decoration-none rounded-pill px-4 py-2 fw-semibold small" data-filter="found">Found Items</button>
                    <button class="btn btn-link text-dark text-decoration-none rounded-pill px-4 py-2 fw-semibold small" data-filter="lost">Lost Items</button>
                </div>
            </div>

            <!-- ITEMS LIST -->
            <div class="row g-3">
                <?php if ($item_count > 0): ?>
                    <?php foreach ($items as $row):
                        $img = !empty($row['item_img']) ? "uploads/" . $row['item_img'] : "assets/images/placeholder-image.jpg";
                        $uploadStatus = $row['upload_status'] ?? 'pending';
                        $badgeClass = $uploadStatus === 'approved' ? 'bg-success' : ($uploadStatus === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
                    ?>
                        <div class="col-12">
                            <div class="col-12 item-card <?= strtolower($row['post_type']) ?>">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-3 overflow-hidden me-3 position-relative flex-shrink-0" style="width: 120px; height: 100px;">
                                        <img loading="lazy" src="<?= $img ?>"
                                             class="w-100 h-100 object-fit-cover"
                                             alt="<?= htmlspecialchars($row['item_name']) ?>"
                                             onerror="this.onerror=null; this.src='assets/images/placeholder-image.jpg'">
                                        <span class="badge position-absolute top-0 start-0 m-2 <?= $badgeClass ?>" style="font-size: 10px;">
                                            <?= ucfirst($uploadStatus) ?>
                                        </span>
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($row['item_name']) ?></h5>
                                            <?php if (!empty($row['post_type'])): ?>
                                                <span class="badge rounded-pill <?= strtolower($row['post_type']) === 'found' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> px-3">
                                                    <?= ucfirst($row['post_type']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-2">
                                            <span class="badge border text-success fw-normal" style="border-color: #0b5a30 !important;">
                                                <?= htmlspecialchars($row['category_name'] ?? 'General') ?>
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column gap-1 text-muted small">
                                            <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['location_text']) ?></span>
                                            <span><i class="bi bi-calendar3 me-1"></i><?= date('M d, Y', strtotime($row['date_reported'] ?? 'now')) ?></span>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 flex-shrink-0">
                                        <button type="button" class="btn btn-outline-secondary btn-sm px-3"
                                            data-bs-toggle="modal" data-bs-target="#viewItemModal"
                                            data-name="<?= htmlspecialchars($row['item_name']) ?>"
                                            data-type="<?= $row['post_type'] ?>"
                                            data-category="<?= htmlspecialchars($row['category_name'] ?? 'General') ?>"
                                            data-location="<?= htmlspecialchars($row['location_text']) ?>"
                                            data-date="<?= date('M d, Y', strtotime($row['date_reported'])) ?>"
                                            data-time="<?= date('h:i A', strtotime($row['date_reported'])) ?>"
                                            data-desc="<?= htmlspecialchars($row['description'] ?? '') ?>"
                                            data-img="<?= $img ?>"
                                            data-user="<?= htmlspecialchars($fullName) ?>"
                                            data-role="<?= htmlspecialchars($role) ?>"
                                            data-email="<?= htmlspecialchars($row['contact_email'] ?? 'Not provided') ?>"
                                            data-phone="<?= htmlspecialchars($row['contact_num'] ?? 'Not provided') ?>"
                                            data-submitted-to-office="<?= !empty($row['submitted_to_office']) ? 'true' : 'false' ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>

                                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 btn-edit-trigger"
                                            data-bs-toggle="modal" data-bs-target="#editItemModal"
                                            data-id="<?= $row['id'] ?>"
                                            data-name="<?= htmlspecialchars($row['item_name']) ?>"
                                            data-type="<?= $row['post_type'] ?>"
                                            data-category="<?= $row['category_id'] ?>"
                                            data-location="<?= htmlspecialchars($row['location_text']) ?>"
                                            data-date="<?= date('Y-m-d', strtotime($row['date_reported'])) ?>"
                                            data-time="<?= date('H:i', strtotime($row['date_reported'])) ?>"
                                            data-desc="<?= htmlspecialchars($row['description'] ?? '') ?>"
                                            data-notes="<?= htmlspecialchars($row['notes'] ?? '') ?>"
                                            data-img="<?= $img ?>"
                                            data-email="<?= htmlspecialchars($row['contact_email'] ?? '') ?>"
                                            data-phone="<?= htmlspecialchars($row['contact_num'] ?? '') ?>"
                                            data-submitted-to-office="<?= !empty($row['submitted_to_office']) ? 'true' : 'false' ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>

                                        <form action="actions/delete.php" method="POST" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm px-3">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No items posted yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>


<!-- POST ITEM MODAL -->
<div class="modal fade" id="postItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-0 shadow-lg">
            <div class="modal-header px-4 py-3 border-0" style="background-color: #0b4628; color: white;">
                <h5 class="modal-title fw-bold">Post an Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/post.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4 pb-2">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Post Type *</label>
                                <div class="d-flex gap-2 post-type-toggle">
                                    <input type="radio" class="btn-check" name="post_type" id="typeFound" value="Found">
                                    <label class="btn btn-outline-success w-100 py-2" for="typeFound">I found something</label>
                                    <input type="radio" class="btn-check" name="post_type" id="typeLost" value="Lost" checked>
                                    <label class="btn btn-outline-danger w-100 py-2" for="typeLost">I lost something</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Item Name *</label>
                                <input type="text" name="item_name" class="form-control custom-input" placeholder="e.g., Black Cellphone" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Category *</label>
                                <select name="category_id" class="form-select custom-input" required>
                                    <option selected disabled value="">Select a category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Location *</label>
                                <input type="text" name="location_input" class="form-control custom-input" placeholder="e.g., Library - 2nd Floor" required>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label fw-bold small">Date *</label>
                                    <input type="date" class="form-control custom-input" name="date" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold small">Time Last Seen *</label>
                                    <input type="time" class="form-control custom-input" name="time" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 d-flex flex-column">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Description *</label>
                                <textarea name="desc" class="form-control custom-input" rows="4" placeholder="Provide detailed description..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Additional Notes</label>
                                <input type="text" name="notes" class="form-control custom-input" placeholder="Any additional information...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Upload Photo *</label>
                                <div class="upload-drop-zone p-4" id="upload-container"
                                     onclick="document.getElementById('item_image').click()" style="cursor:pointer;">
                                    <div id="upload-placeholder" class="text-center">
                                        <i class="bi bi-upload fs-3 text-muted"></i>
                                        <p class="small text-muted mb-2">Click to upload or drag and drop<br>PNG, JPG, JPEG up to 5MB</p>
                                    </div>
                                    <img id="image-preview" src="" alt="Preview" class="img-fluid rounded d-none mb-2" style="max-height: 150px;">
                                    <input type="file" name="item_image" id="item_image" class="file-input-hidden d-none" accept="image/*" required>
                                    <div class="d-flex justify-content-center">
                                        <button type="button" class="btn btn-light btn-sm shadow-sm px-3"
                                                onclick="event.stopPropagation(); document.getElementById('item_image').click()">
                                            Choose File
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto" style="background-color: #d1e7dd; border: 2px solid #5a8f6f; border-radius: 12px; padding: 20px;">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="submitted_to_office" id="submitted_to_office" style="width: 20px; height: 20px;">
                                    <label class="form-check-label fw-bold ms-2" for="submitted_to_office" style="font-size: 15px;">
                                        I have submitted this item to the Lost &amp; Found Office
                                    </label>
                                </div>
                                <p class="small text-muted mb-0 ms-4">Check this if you've already turned the item over to the office.</p>
                                <hr style="border-top: 2px solid #5a8f6f; margin: 14px 0;">
                                <p class="small mb-3" style="color:#666;">Since you're holding the item, please provide your contact information</p>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="fw-bold mb-1 small">Email Address *</label>
                                        <input type="email" name="email" class="form-control" style="background-color: #D2CECE; border: 1px solid #999;" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="fw-bold mb-1 small">Phone Number *</label>
                                        <input type="text" name="phone" class="form-control" style="background-color: #D2CECE; border: 1px solid #999;" required>
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


<!-- VIEW ITEM MODAL -->
<div class="modal fade" id="viewItemModal" tabindex="-1" aria-hidden="true">
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
                            <img id="v-img" src="" class="img-fluid rounded-3 w-100 object-fit-cover"
                                 style="height: 250px; background: #eee;"
                                 onerror="this.onerror=null; this.src='assets/images/placeholder-image.jpg'">
                            <span id="v-type" class="badge position-absolute top-0 end-0 m-2 rounded-pill px-3"></span>
                        </div>
                        <h5 class="fw-bold mb-2">Description</h5>
                        <p id="v-desc" class="text-muted small"></p>
                        <div class="mt-4 p-3 bg-light rounded-3">
                            <h6 class="fw-bold mb-3">Posted by:</h6>
                            <div class="d-flex align-items-center mb-2">
                                <img id="v-avatar" src="" class="rounded-circle me-2" width="35" alt="Avatar">
                                <div class="fw-bold small"><?= htmlspecialchars($fullName) ?></div>
                            </div>
                            <div style="font-size: 11px;">
                                <div class="text-success mb-1"><i class="bi bi-envelope me-1"></i><span id="v-email"></span></div>
                                <div class="text-muted"><i class="bi bi-telephone me-1"></i><span id="v-phone"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h2 id="v-name" class="fw-bold mb-1"></h2>
                        <span id="v-cat" class="badge border text-success fw-normal mb-4" style="border-color: #198754 !important;"></span>
                        <h5 class="fw-bold mb-3">Location &amp; Time</h5>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-geo-alt text-success fs-5 me-2"></i>
                            <div><div class="text-muted small">Location</div><div class="fw-bold" id="v-loc"></div></div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-calendar-event text-success fs-5 me-2"></i>
                            <div><div class="text-muted small">Date</div><div class="fw-bold" id="v-date"></div></div>
                        </div>
                        <div class="d-flex align-items-start mb-4">
                            <i class="bi bi-clock text-success fs-5 me-2"></i>
                            <div><div class="text-muted small">Time Reported</div><div class="fw-bold" id="v-time"></div></div>
                        </div>
                        <h5 class="fw-bold mb-2">Item Status</h5>
                        <div id="v-status-box" class="alert d-flex align-items-start gap-2 border-0 rounded-3">
                            <i class="bi bi-info-circle-fill"></i>
                            <div class="small fw-bold" id="v-status-text"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- EDIT ITEM MODAL -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-0 shadow-lg">
            <div class="modal-header px-4 py-3 border-0" style="background-color: #0b4628; color: white;">
                <h5 class="modal-title fw-bold">Edit Item Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/edit-item.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="item_id" id="edit_item_id">
                <input type="hidden" name="referer" value="my-activity"> <!-- ADDED -->
                <div class="modal-body p-4 pb-2">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Post Type *</label>
                                <div class="d-flex gap-2 post-type-toggle">
                                    <input type="radio" class="btn-check" name="post_type" id="editTypeFound" value="Found">
                                    <label class="btn btn-outline-success w-100 py-2" for="editTypeFound">I found something</label>
                                    <input type="radio" class="btn-check" name="post_type" id="editTypeLost" value="Lost">
                                    <label class="btn btn-outline-danger w-100 py-2" for="editTypeLost">I lost something</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Item Name *</label>
                                <input type="text" name="item_name" id="edit_item_name" class="form-control custom-input" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Category *</label>
                                <select name="category_id" id="edit_category_id" class="form-select custom-input" required>
                                    <option disabled>Select a category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Location *</label>
                                <input type="text" name="location_input" id="edit_location_input" class="form-control custom-input" required>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label fw-bold small">Date *</label>
                                    <input type="date" class="form-control custom-input" name="date" id="edit_date" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold small">Time Last Seen *</label>
                                    <input type="time" class="form-control custom-input" name="time" id="edit_time" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 d-flex flex-column">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Description *</label>
                                <textarea name="desc" id="edit_desc" class="form-control custom-input" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Additional Notes</label>
                                <input type="text" name="notes" id="edit_notes" class="form-control custom-input">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Upload Photo <small class="text-muted">(leave empty to keep existing)</small></label>
                                <div class="upload-drop-zone p-4" id="edit-upload-container"
                                     onclick="document.getElementById('edit_item_image').click()" style="cursor:pointer;">
                                    <div id="edit-upload-placeholder" class="text-center d-none">
                                        <i class="bi bi-upload fs-3 text-muted"></i>
                                        <p class="small text-muted mb-2">Click to upload or drag and drop</p>
                                    </div>
                                    <img id="edit-image-preview" src="" alt="Preview" class="img-fluid rounded mb-2" style="max-height: 150px;">
                                    <input type="file" name="item_image" id="edit_item_image" class="d-none" accept="image/*">
                                    <div class="d-flex justify-content-center">
                                        <button type="button" class="btn btn-light btn-sm shadow-sm px-3"
                                                onclick="event.stopPropagation(); document.getElementById('edit_item_image').click()">
                                            Choose File
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto" style="background-color: #d1e7dd; border: 2px solid #5a8f6f; border-radius: 12px; padding: 20px;">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="submitted_to_office" id="edit_submitted_to_office" style="width: 20px; height: 20px;">
                                    <label class="form-check-label fw-bold ms-2" for="edit_submitted_to_office" style="font-size: 15px;">
                                        I have submitted this item to the Lost &amp; Found Office
                                    </label>
                                </div>
                                <p class="small text-muted mb-0 ms-4">Check this if you've already turned the item over to the office.</p>
                                <hr style="border-top: 2px solid #5a8f6f; margin: 14px 0;">
                                <p class="small mb-3" style="color:#666;">Since you're holding the item, please provide your contact information</p>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="fw-bold mb-1 small">Email Address *</label>
                                        <input type="email" name="email" id="edit_email" class="form-control" style="background-color: #D2CECE; border: 1px solid #999;" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="fw-bold mb-1 small">Phone Number *</label>
                                        <input type="text" name="phone" id="edit_phone" class="form-control" style="background-color: #D2CECE; border: 1px solid #999;" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-start">
                    <button type="submit" name="submit_edit" class="btn px-5 fw-bold py-2 text-white"
                            style="background-color: #0b4628; border: none; border-radius: 8px;">Save Changes</button>
                    <button type="button" class="btn btn-outline-secondary px-5 fw-bold py-2" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Navbar avatar ────────────────────────────────────────────────────
    const navAvatar = document.getElementById('nav-avatar');
    if (navAvatar) {
        navAvatar.src = 'https://ui-avatars.com/api/?name=' + navAvatar.dataset.name + '&background=0b5a30&color=fff';
    }

    // ── Filter pills ─────────────────────────────────────────────────────
    document.querySelectorAll('.btn[data-filter]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn[data-filter]').forEach(b => {
                b.classList.remove('btn-success', 'active-filter');
                b.classList.add('btn-link', 'text-dark', 'text-decoration-none');
            });
            this.classList.remove('btn-link', 'text-dark', 'text-decoration-none');
            this.classList.add('btn-success', 'active-filter');

            const filter = this.getAttribute('data-filter');
            document.querySelectorAll('.item-card').forEach(card => {
                card.closest('.col-12').classList.toggle('d-none',
                    filter !== 'all' && !card.classList.contains(filter));
            });
        });
    });

    // ── Post modal: image preview ────────────────────────────────────────
    const postImg = document.getElementById('item_image');
    if (postImg) {
        postImg.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('image-preview').src = e.target.result;
                document.getElementById('image-preview').classList.remove('d-none');
                document.getElementById('upload-placeholder').classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    }

    // Reset post modal on close
    document.getElementById('postItemModal')?.addEventListener('hidden.bs.modal', function () {
        document.getElementById('image-preview').src = '';
        document.getElementById('image-preview').classList.add('d-none');
        document.getElementById('upload-placeholder').classList.remove('d-none');
        document.getElementById('item_image').value = '';
    });

    // ── View modal ───────────────────────────────────────────────────────
    document.getElementById('viewItemModal')?.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const get = a => btn.getAttribute(a);

        document.getElementById('v-name').textContent  = get('data-name');
        document.getElementById('v-loc').textContent   = get('data-location');
        document.getElementById('v-date').textContent  = get('data-date');
        document.getElementById('v-time').textContent  = get('data-time');
        document.getElementById('v-desc').textContent  = get('data-desc');
        document.getElementById('v-cat').textContent   = get('data-category');
        document.getElementById('v-img').src           = get('data-img');
        document.getElementById('v-email').textContent = get('data-email');
        document.getElementById('v-phone').textContent = get('data-phone');
        document.getElementById('v-avatar').src = 'https://ui-avatars.com/api/?name='
            + encodeURIComponent(get('data-user') || '') + '&background=0b5a30&color=fff';

        const typeBadge  = document.getElementById('v-type');
        const statusBox  = document.getElementById('v-status-box');
        const statusText = document.getElementById('v-status-text');
        const isLost     = get('data-type') === 'Lost';
        const toOffice   = get('data-submitted-to-office') === 'true';

        if (isLost) {
            typeBadge.textContent = 'Lost';
            typeBadge.className   = 'badge bg-danger position-absolute top-0 end-0 m-2 rounded-pill px-3';
            statusBox.style.cssText = 'background-color:#EBCFCD; border-left:4px solid #5E0006;';
            statusText.innerHTML = `<div class="fw-bold mb-1" style="color:#5E0006;font-size:18px;">Someone is looking for this item</div>
                <div class="fw-light">If you have found this item, please contact the owner using the information provided.</div>`;
        } else if (toOffice) {
            typeBadge.textContent = 'Found';
            typeBadge.className   = 'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3';
            statusBox.style.cssText = 'background-color:#D4E3DA; border-left:4px solid #0F6631;';
            statusText.innerHTML = `<div class="fw-bold mb-1" style="color:#0F6631;font-size:18px;">Surrendered to Lost &amp; Found Office</div>
                <p class="fw-light mb-2" style="color:#343A40;">This item has been turned over to the GNC Lost &amp; Found Management Office.</p>
                <div class="small"><strong>Office Hours:</strong> Monday - Saturday, 8:00 AM - 5:00 PM<br><strong>Location:</strong> Main Building, Ground Floor</div>`;
        } else {
            typeBadge.textContent = 'Found';
            typeBadge.className   = 'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3';
            statusBox.style.cssText = 'background-color:#fff3cd; border-left:4px solid #ffc107;';
            statusText.innerHTML = `<div class="fw-bold mb-1" style="color:#856404;font-size:18px;">Currently Held by Finder</div>
                <p class="fw-light mb-0" style="color:#343A40;">The person who found this item is currently holding it.</p>`;
        }
    });

    // ── Edit modal ───────────────────────────────────────────────────────
    document.getElementById('editItemModal')?.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const get = a => btn.getAttribute(a);

        document.getElementById('edit_item_id').value        = get('data-id');
        document.getElementById('edit_item_name').value      = get('data-name');
        document.getElementById('edit_category_id').value    = get('data-category');
        document.getElementById('edit_location_input').value = get('data-location');
        document.getElementById('edit_date').value           = get('data-date');
        document.getElementById('edit_time').value           = get('data-time');
        document.getElementById('edit_desc').value           = get('data-desc');
        document.getElementById('edit_notes').value          = get('data-notes');
        document.getElementById('edit_email').value          = get('data-email');
        document.getElementById('edit_phone').value          = get('data-phone');
        document.getElementById('edit_submitted_to_office').checked = get('data-submitted-to-office') === 'true';

        const type = get('data-type') || '';
        document.getElementById(type.toLowerCase() === 'found' ? 'editTypeFound' : 'editTypeLost').checked = true;

        const img         = get('data-img') || '';
        const preview     = document.getElementById('edit-image-preview');
        const placeholder = document.getElementById('edit-upload-placeholder');
        if (img && !img.includes('placeholder')) {
            preview.src = img;
            preview.classList.remove('d-none');
            placeholder.classList.add('d-none');
        } else {
            preview.classList.add('d-none');
            placeholder.classList.remove('d-none');
        }
    });

    // Edit modal: image change preview
    document.getElementById('edit_item_image')?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('edit-image-preview').src = e.target.result;
            document.getElementById('edit-image-preview').classList.remove('d-none');
            document.getElementById('edit-upload-placeholder').classList.add('d-none');
        };
        reader.readAsDataURL(file);
    });
});
</script>
</body>
</html>