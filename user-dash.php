<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$firstName = $_SESSION['first_name'] ?? 'User'; 
$lastName = $_SESSION['last_name'] ?? '';
$fullName = trim($firstName . " " . $lastName);
$role = $_SESSION['role'] ?? 'Student';

// FETCH USER ITEMS
$query = "SELECT items.*, categories.category_name
        FROM items 
        LEFT JOIN categories ON items.category_id = categories.id
        WHERE items.user_id = ? 
        ORDER BY items.created_at DESC";

// --- PDO CONVERSION FOR CATEGORIES ---
$cat_query = "SELECT * FROM categories";
$cat_result = $pdo->query($cat_query);
$categories = $cat_result->fetchAll();

// --- PDO CONVERSION FOR USER ITEMS ---
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();
$item_count = count($items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - GNC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/user-dash.css">
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

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 bg-light min-vh-100">
                <div class="d-flex justify-content-between align-items-center pt-4 mb-4">
                    <h3 class="fw-bold mb-0">My Posts</h3>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#postItemModal" class="btn btn-success d-flex align-items-center px-3" style="background-color: #157347; border: none; border-radius: 8px;">
                        <i class="bi bi-plus-lg me-2"></i> Post New Item
                    </a>
                </div>

                <div class="mb-4">
                    <div class="d-inline-flex p-1 bg-white shadow-sm rounded-pill border">
                        <button class="btn btn-success rounded-pill px-4 py-2 fw-semibold small active-filter" data-filter="all">All Items</button>
                        <button class="btn btn-link text-dark text-decoration-none rounded-pill px-4 py-2 fw-semibold small" data-filter="found">Found Items</button>
                        <button class="btn btn-link text-dark text-decoration-none rounded-pill px-4 py-2 fw-semibold small" data-filter="lost">Lost Items</button>
                    </div>
                </div>

                <div class="row g-3">
                    <?php if ($item_count > 0): ?>
                        <?php foreach($items as $row): ?>
                            <div class="col-12 mb-3">
                                <div class="col-12 mb-3 item-card <?php echo strtolower($row['post_type']); ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-3 overflow-hidden me-3 position-relative" style="width: 120px; height: 100px;">
                                            <?php $img = !empty($row['item_img']) ? "uploads/".$row['item_img'] : "assets/images/placeholder.png"; ?>
                                            <img src="<?php echo $img; ?>" class="w-100 h-100 object-fit-cover">
                                            
                                            <span class="badge position-absolute top-0 start-0 m-2 <?php echo ($row['upload_status'] ?? 'pending') == 'approved' ? 'bg-success' : (($row['upload_status'] ?? 'pending') == 'rejected' ? 'bg-danger' : 'bg-warning text-dark'); ?>" style="font-size: 10px;">
                                                <?php echo ucfirst($row['upload_status'] ?? 'Pending'); ?>
                                            </span>
                                        </div>

                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($row['item_name']); ?></h5>
                                                <?php if (!empty($row['post_type'])): ?>
                                                    <span class="badge rounded-pill <?php echo (strtolower($row['post_type']) == 'found') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> px-3">
                                                        <?php echo ucfirst($row['post_type']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <span class="badge border text-success fw-normal" style="border-color: #0b5a30 !important; color: #0b5a30;">
                                                    <?php echo htmlspecialchars($row['category_name'] ?? 'General'); ?>
                                                </span>
                                            </div>

                                            <div class="d-flex flex-column gap-1 text-muted small">
                                                <span><i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($row['location_text']); ?></span>
                                                <span><i class="bi bi-calendar3 me-1"></i> <?php echo date('M d, Y', strtotime($row['date_reported'] ?? 'now')); ?></span>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="button" 
                                                class="btn btn-outline-secondary btn-sm px-3" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewItemModal"
                                                data-name="<?php echo htmlspecialchars($row['item_name']); ?>"
                                                data-type="<?php echo $row['post_type']; ?>"
                                                data-category="<?php echo htmlspecialchars($row['category_name'] ?? 'General'); ?>"
                                                data-location="<?php echo htmlspecialchars($row['location_text']); ?>"
                                                data-date="<?php echo date('M d, Y', strtotime($row['date_reported'])); ?>"
                                                data-time="<?php echo date('h:i A', strtotime($row['date_reported'])); ?>"
                                                data-desc="<?php echo htmlspecialchars($row['description'] ?? 'No description provided.'); ?>"
                                                data-img="<?php echo $img; ?>"
                                                data-user="<?php echo htmlspecialchars($fullName); ?>"
                                                data-role="<?php echo htmlspecialchars($role); ?>"
                                                data-email="<?php echo htmlspecialchars($row['contact_email'] ?? 'Not provided'); ?>"
                                                data-phone="<?php echo htmlspecialchars($row['contact_num'] ?? 'Not provided'); ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>

                                            <button type="button" 
                                                class="btn btn-outline-secondary btn-sm px-3 btn-edit-trigger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editItemModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['item_name']); ?>"
                                                data-type="<?php echo $row['post_type']; ?>"
                                                data-category="<?php echo $row['category_id']; ?>"
                                                data-location="<?php echo htmlspecialchars($row['location_text']); ?>"
                                                data-date="<?php echo date('Y-m-d', strtotime($row['date_reported'])); ?>"
                                                data-time="<?php echo date('H:i', strtotime($row['date_reported'])); ?>"
                                                data-desc="<?php echo htmlspecialchars($row['description'] ?? ''); ?>"
                                                data-notes="<?php echo htmlspecialchars($row['notes'] ?? ''); ?>"
                                                data-img="<?php echo $img; ?>"
                                                data-email="<?php echo htmlspecialchars($row['contact_email'] ?? ''); ?>"
                                                data-phone="<?php echo htmlspecialchars($row['contact_num'] ?? ''); ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>

                                            <form action="actions/delete.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
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
                        <div class="text-center py-5">
                            <p class="text-muted">No items posted yet.</p>
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
                <div class="modal-header px-4 py-3 border-0 rounded-0">
                    <h5 class="modal-title fw-bold">Post an Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="actions/post.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body p-4 pb-2">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Post Type *</label>
                                    <div class="d-flex gap-2 post-type-toggle">
                                        <input type="radio" class="btn-check" name="post_type" id="typeFound" value="Found">
                                        <label class="btn btn-outline-toggle w-100 py-2" for="typeFound">I found something</label>

                                        <input type="radio" class="btn-check" name="post_type" id="typeLost" value="Lost" checked>
                                        <label class="btn btn-outline-toggle w-100 py-2" for="typeLost">I lost something</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Item Name *</label>
                                    <input type="text" name="item_name" class="form-control custom-input" placeholder="e.g., Black Cellphone" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Category *</label>
                                    <select name="category_id" class="form-select custom-input" required>
                                        <option selected disabled>Select a category</option>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['category_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Location*</label>
                                    <input type="text" name="location_input" class="form-control custom-input" placeholder="e.g, Library - 2nd Floor" required>
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
                                    <div class="upload-drop-zone p-4" id="upload-container">
                                        <div id="upload-placeholder">
                                            <i class="bi bi-upload fs-3 text-muted"></i>
                                            <p class="small text-muted mb-2">Click to upload or drag and drop<br>PNG, JPG, JPEG up to 5MB</p>
                                        </div>
                                        <img id="image-preview" src="" alt="Preview" class="img-fluid rounded d-none mb-2" style="max-height: 150px;">
                                        <input type="file" name="item_image" id="item_image" class="file-input-hidden" required>
                                        <div class="d-flex flex-column align-items-center">
                                            <button type="button" class="btn btn-light btn-sm shadow-sm px-3" onclick="document.getElementById('item_image').click()">
                                                Choose File
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="contact-info-card mt-auto">
                                    <p class="small mb-2 opacity-75">Your Contact Information:</p>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="fw-bold small mb-1">Email Address *</label>
                                            <input type="email" name="email" class="form-control form-control-sm bg-light-gray border-secondary" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="fw-bold small mb-1">Phone Number *</label>
                                            <input type="text" name="phone" class="form-control form-control-sm bg-light-gray border-secondary" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-start">
                        <button type="submit" name="submit_post" class="btn btn-gnc-primary px-5 fw-bold py-2 position-relative" style="z-index: 1051;"> Post Item </button>
                        <button type="button" class="btn btn-gnc-outline px-5 fw-bold py-2" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modal -->
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
                                <img id="v-img" src="" class="img-fluid rounded-3 w-100 object-fit-cover" style="height: 250px; background: #eee;">
                                <span id="v-type" class="badge position-absolute top-0 end-0 m-2 rounded-pill px-3"></span>
                            </div>
                            <h5 class="fw-bold mb-2">Description</h5>
                            <p id="v-desc" class="text-muted small"></p>
                            
                            <div class="mt-4 p-3 bg-light rounded-3">
                                <h6 class="fw-bold mb-3">Posted by:</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <img id="v-avatar" src="" class="rounded-circle me-2" width="35">
                                    <div class="fw-bold small"><?= htmlspecialchars($fullName); ?></div>
                                </div>
                                <div style="font-size: 11px;">
                                    <div class="text-success mb-1"><i class="bi bi-envelope me-1"></i> <span id="v-email"></span></div>
                                    <div class="text-muted"><i class="bi bi-telephone me-1"></i> <span id="v-phone"></span></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <h2 id="v-name" class="fw-bold mb-1"></h2>
                            <span id="v-cat" class="badge border text-success fw-normal mb-4" style="border-color: #198754 !important;"></span>
                            
                            <h5 class="fw-bold mb-3">Location & Time</h5>
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
                <div class="modal-header px-4 py-3 border-0 rounded-0" style="background-color: #0b4628; color: white;">
                    <h5 class="modal-title fw-bold">Edit Item Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="actions/edit-item.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    
                    <div class="modal-body p-4 pb-2">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Post Type *</label>
                                    <div class="d-flex gap-2 post-type-toggle">
                                        <input type="radio" class="btn-check" name="post_type" id="editTypeFound" value="Found">
                                        <label class="btn btn-outline-toggle w-100 py-2" for="editTypeFound">I found something</label>

                                        <input type="radio" class="btn-check" name="post_type" id="editTypeLost" value="Lost">
                                        <label class="btn btn-outline-toggle w-100 py-2" for="editTypeLost">I lost something</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Item Name *</label>
                                    <input type="text" name="item_name" id="edit_item_name" class="form-control custom-input" placeholder="e.g., Black Cellphone" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Category *</label>
                                    <select name="category_id" id="edit_category_id" class="form-select custom-input" required>
                                        <option disabled>Select a category</option>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['category_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Location*</label>
                                    <input type="text" name="location_input" id="edit_location_input" class="form-control custom-input" placeholder="e.g, Library - 2nd Floor" required>
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
                                    <textarea name="desc" id="edit_desc" class="form-control custom-input" rows="4" placeholder="Provide detailed description..." required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Additional Notes</label>
                                    <input type="text" name="notes" id="edit_notes" class="form-control custom-input" placeholder="Any additional information...">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Upload Photo (Leave empty to keep existing)</label>
                                    <div class="upload-drop-zone p-4" id="edit-upload-container">
                                        <div id="edit-upload-placeholder" class="d-none">
                                            <i class="bi bi-upload fs-3 text-muted"></i>
                                            <p class="small text-muted mb-2">Click to upload or drag and drop<br>PNG, JPG, JPEG up to 5MB</p>
                                        </div>
                                        <img id="edit-image-preview" src="" alt="Preview" class="img-fluid rounded mb-2" style="max-height: 150px;">
                                        <input type="file" name="item_image" id="edit_item_image" class="file-input-hidden">
                                        <div class="d-flex flex-column align-items-center">
                                            <button type="button" class="btn btn-light btn-sm shadow-sm px-3" onclick="document.getElementById('edit_item_image').click()">
                                                Choose File
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="contact-info-card mt-auto" style="background-color: #d1e7dd; border-radius: 8px; padding: 15px;">
                                    <p class="small mb-2 opacity-75">Your Contact Information:</p>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="fw-bold small mb-1">Email Address *</label>
                                            <input type="email" name="email" id="edit_email" class="form-control form-control-sm bg-light-gray border-secondary" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="fw-bold small mb-1">Phone Number *</label>
                                            <input type="text" name="phone" id="edit_phone" class="form-control form-control-sm bg-light-gray border-secondary" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-start">
                        <button type="submit" name="submit_edit" class="btn px-5 fw-bold py-2 text-white" style="background-color: #0b4628; border: none; border-radius: 8px;"> Save Changes </button>
                        <button type="button" class="btn btn-outline-secondary px-5 fw-bold py-2" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Filter logic
        const buttons = document.querySelectorAll('.btn[data-filter]');
        const cards = document.querySelectorAll('.item-card');

        buttons.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                buttons.forEach(b => {
                    b.classList.remove('btn-success', 'active-filter');
                    b.classList.add('btn-link', 'text-dark', 'text-decoration-none');
                });
                this.classList.remove('btn-link', 'text-dark', 'text-decoration-none');
                this.classList.add('btn-success', 'active-filter');

                cards.forEach(card => {
                    if (filter === 'all' || card.classList.contains(filter)) {
                        card.classList.remove('d-none');
                    } else {
                        card.classList.add('d-none');
                    }
                });
            });
        });

        // Image Preview Logic (Post Modal)
        const postImgInput = document.getElementById('item_image');
        if (postImgInput) {
            postImgInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                const preview = document.getElementById('image-preview');
                const placeholder = document.getElementById('upload-placeholder');
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.classList.remove('d-none');
                        placeholder.classList.add('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // View Modal Population
        const viewModal = document.getElementById('viewItemModal');
        if (viewModal) {
            viewModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const info = {
                    name: button.getAttribute('data-name'),
                    loc: button.getAttribute('data-location'),
                    date: button.getAttribute('data-date'),
                    time: button.getAttribute('data-time'),
                    desc: button.getAttribute('data-desc'),
                    img: button.getAttribute('data-img'),
                    type: button.getAttribute('data-type'),
                    cat: button.getAttribute('data-category'),
                    email: button.getAttribute('data-email'),
                    phone: button.getAttribute('data-phone')
                };

                document.getElementById('v-name').textContent = info.name;
                document.getElementById('v-loc').textContent = info.loc;
                document.getElementById('v-date').textContent = info.date;
                document.getElementById('v-time').textContent = info.time;
                document.getElementById('v-desc').textContent = info.desc;
                document.getElementById('v-cat').textContent = info.cat;
                document.getElementById('v-img').src = info.img;
                document.getElementById('v-email').textContent = info.email;
                document.getElementById('v-phone').textContent = info.phone;
                
                const userName = button.getAttribute('data-user');
                document.getElementById('v-avatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=0b5a30&color=fff`;

                const statusBox = document.getElementById('v-status-box');
                const statusText = document.getElementById('v-status-text');
                const typeBadge = document.getElementById('v-type');

                if(info.type === 'Lost') {
                    typeBadge.textContent = 'Lost';
                    typeBadge.className = 'badge bg-danger position-absolute top-0 end-0 m-2 rounded-pill px-3';
                    statusBox.className = 'alert alert-danger d-flex align-items-start gap-2 border-0 rounded-3';
                    statusText.innerHTML = `
                        <div class="fw-bold mb-1">Someone is looking for this item</div>
                        <div>If you have found this item, please contact the owner using the information provided.</div>
                    `;
                } else {
                    typeBadge.textContent = 'Found';
                    typeBadge.className = 'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3';
                    statusBox.className = 'alert alert-success d-flex align-items-start gap-2 border-0 rounded-3';
                    statusText.innerHTML = `
                        <div class="fw-bold mb-1 text-dark">Surrendered to Lost & Found Office</div>
                        <p class="mb-2">This item has been turned over to the GNC Lost & Found Management Office. Please visit the office during business hours to claim your item.</p>
                        <div class="small">
                            <strong>Office Hours:</strong> Monday - Saturday, 8:00 AM - 5:00 PM<br>
                            <strong>Location:</strong> Main Building, Ground Floor
                        </div>
                    `;
                }
            });
        }

        // Edit Modal Population and Image Preview Logic
        const editModal = document.getElementById('editItemModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                // Extract values from trigger button attributes
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const type = button.getAttribute('data-type');
                const categoryId = button.getAttribute('data-category');
                const location = button.getAttribute('data-location');
                const date = button.getAttribute('data-date');
                const time = button.getAttribute('data-time');
                const desc = button.getAttribute('data-desc');
                const notes = button.getAttribute('data-notes');
                const img = button.getAttribute('data-img');
                const email = button.getAttribute('data-email');
                const phone = button.getAttribute('data-phone');

                // Set form fields
                document.getElementById('edit_item_id').value = id;
                document.getElementById('edit_item_name').value = name;
                document.getElementById('edit_category_id').value = categoryId;
                document.getElementById('edit_location_input').value = location;
                document.getElementById('edit_date').value = date;
                document.getElementById('edit_time').value = time;
                document.getElementById('edit_desc').value = desc;
                document.getElementById('edit_notes').value = notes;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_phone').value = phone;

                // Toggle Radios
                if (type && type.toLowerCase() === 'found') {
                    document.getElementById('editTypeFound').checked = true;
                } else {
                    document.getElementById('editTypeLost').checked = true;
                }

                // Handle Image Previews
                const previewImg = document.getElementById('edit-image-preview');
                const placeholder = document.getElementById('edit-upload-placeholder');
                
                if (img && !img.includes('placeholder.png')) {
                    previewImg.src = img;
                    previewImg.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                } else {
                    previewImg.classList.add('d-none');
                    placeholder.classList.remove('d-none');
                }
            });

            // Handle instant image selection changes in the edit modal
            const editImgInput = document.getElementById('edit_item_image');
            if (editImgInput) {
                editImgInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    const preview = document.getElementById('edit-image-preview');
                    const placeholder = document.getElementById('edit-upload-placeholder');
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.classList.remove('d-none');
                            placeholder.classList.add('d-none');
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        }
    });
    </script>
    
</body>
</html>
