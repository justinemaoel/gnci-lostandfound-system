<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$fullName = trim(($_SESSION['first_name'] ?? 'user') . " " . ($_SESSION['last_name'] ?? ''));
$userRole = $_SESSION['role'] ?? 'student';

// Determine correct dashboard link based on role
$adminEmail    = "admin.lostandfound@gmail.com";
$currentEmail  = $_SESSION['email'] ?? '';
$isAdmin       = strtolower(trim($currentEmail)) === strtolower($adminEmail);
$dashboardLink = $isAdmin ? 'admin-dash.php' : 'user-dash.php';

// Fetch ALL approved items — include submitted_to_office
$query = "SELECT items.*, categories.category_name, 
        users.first_name, users.last_name, users.role, users.email as user_email
        FROM items 
        LEFT JOIN categories ON items.category_id = categories.id
        LEFT JOIN users ON items.user_id = users.id
        WHERE items.upload_status = 'approved' 
        ORDER BY items.created_at DESC";

$stmt = $pdo->query($query);
$browse_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Items - GNC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/browse-item.css">
</head>
<body class="bg-light">

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
                <small class="text-uppercase opacity-75" style="font-size: 10px;"><?= htmlspecialchars($userRole) ?></small>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=0b5a30&color=fff" width="35" height="35" class="rounded-circle">
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

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 min-vh-100">
                <h3 class="fw-bold mb-4">Browse All Items</h3>
                
                <div class="row g-4">
                    <?php if (count($browse_items) > 0): ?>
                        <?php foreach($browse_items as $item): ?>
                            <?php
                                $img = !empty($item['item_img'])
                                    ? "uploads/" . $item['item_img']
                                    : "assets/images/placeholder.png";

                                // Pass submitted_to_office to JS as 'true' or 'false' string
                                $submittedToOffice = !empty($item['submitted_to_office']) ? 'true' : 'false';
                            ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                                    <img src="<?= $img ?>" class="card-img-top object-fit-cover" style="height: 200px;"
                                        onerror="this.src='assets/images/placeholder.png'">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="fw-bold mb-0 text-truncate" style="max-width: 70%;">
                                                <?= htmlspecialchars($item['item_name']) ?>
                                            </h5>
                                            <span class="badge rounded-pill <?= (strtolower($item['post_type']) == 'found') ? 'bg-success' : 'bg-danger' ?>">
                                                <?= ucfirst($item['post_type']) ?>
                                            </span>
                                        </div>
                                        <p class="text-muted small mb-3">
                                            <i class="bi bi-person me-1 text-success"></i>
                                            <strong>Posted by:</strong> <?= htmlspecialchars($item['first_name'] . " " . $item['last_name']) ?><br>
                                            <i class="bi bi-geo-alt me-1 text-success"></i> <?= htmlspecialchars($item['location_text']) ?>
                                        </p>
                                        <button class="btn btn-outline-success w-100 btn-sm fw-bold"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewItemModal"
                                            data-name="<?= htmlspecialchars($item['item_name']) ?>"
                                            data-type="<?= $item['post_type'] ?>"
                                            data-category="<?= htmlspecialchars($item['category_name'] ?? 'General') ?>"
                                            data-location="<?= htmlspecialchars($item['location_text']) ?>"
                                            data-date="<?= date('M d, Y', strtotime($item['date_reported'])) ?>"
                                            data-time="<?= date('h:i A', strtotime($item['date_reported'])) ?>"
                                            data-desc="<?= htmlspecialchars($item['description']) ?>"
                                            data-img="<?= $img ?>"
                                            data-user="<?= htmlspecialchars($item['first_name'] . " " . $item['last_name']) ?>"
                                            data-role="<?= ucfirst($item['role'] ?? 'Student') ?>"
                                            data-email="<?= htmlspecialchars($item['contact_email'] ?: ($item['user_email'] ?? 'Not provided')) ?>"
                                            data-phone="<?= htmlspecialchars($item['contact_num'] ?? 'Not provided') ?>"
                                            data-submitted-to-office="<?= $submittedToOffice ?>">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mt-2">No items available to browse.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- VIEW DETAILS MODAL -->
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
                                    style="height: 280px; background: #eee;"
                                    onerror="this.src='assets/images/placeholder.png'">
                                <span id="v-type" class="badge position-absolute top-0 end-0 m-2 rounded-pill px-3"></span>
                            </div>
                            <h5 class="fw-bold mb-2">Description</h5>
                            <p id="v-desc" class="text-muted small mb-4" style="line-height: 1.6;"></p>
                            
                            <h5 class="fw-bold mb-3">Posted by:</h5>
                            <div class="p-3 rounded-3" style="background-color: #f8f9fa;">
                                <div class="d-flex align-items-center gap-3">
                                    <img id="v-avatar" src="" class="rounded-circle border" width="45" height="45">
                                    <div class="lh-sm">
                                        <div class="fw-bold text-dark" id="v-user-name"></div>
                                        <span id="v-user-role" class="badge bg-light text-muted border-0 p-0 text-uppercase" style="font-size: 10px;"></span>
                                    </div>
                                </div>
                                <div class="mt-3 pt-2 border-top" style="font-size: 12px;">
                                    <div class="text-success mb-2"><i class="bi bi-envelope me-2"></i><span id="v-email"></span></div>
                                    <div class="text-muted"><i class="bi bi-telephone me-2"></i><span id="v-phone"></span></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7 border-start ps-md-4">
                            <h2 id="v-name" class="fw-bold mb-1"></h2>
                            <div class="mb-4">
                                <span id="v-cat" class="badge border text-success fw-normal"
                                      style="border-color: #d1e7dd !important; background-color: #f0fdf4; color: #157347;"></span>
                            </div>
                            
                            <hr class="opacity-10 mb-4">

                            <h5 class="fw-bold mb-3">Location &amp; Time</h5>
                            <div class="d-flex align-items-start mb-3">
                                <i class="bi bi-geo-alt text-success fs-5 me-3"></i>
                                <div>
                                    <div class="text-muted small">Location</div>
                                    <div class="fw-bold" id="v-loc"></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-3">
                                <i class="bi bi-calendar-event text-success fs-5 me-3"></i>
                                <div>
                                    <div class="text-muted small">Date Reported</div>
                                    <div class="fw-bold" id="v-date"></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-4">
                                <i class="bi bi-clock text-success fs-5 me-3"></i>
                                <div>
                                    <div class="text-muted small">Time Reported</div>
                                    <div class="fw-bold" id="v-time"></div>
                                </div>
                            </div>

                            <hr class="opacity-10 mb-4">

                            <h5 class="fw-bold mb-2">Item Status</h5>
                            <div id="v-status-box" class="alert d-flex align-items-start gap-3 border-0 rounded-3 p-3">
                                <i id="v-status-icon" class="bi bi-info-circle-fill fs-5"></i>
                                <div id="v-status-text" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const viewModal = document.getElementById('viewItemModal');
        if (viewModal) {
            viewModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                // Populate all basic fields
                document.getElementById('v-name').textContent      = button.getAttribute('data-name');
                document.getElementById('v-loc').textContent       = button.getAttribute('data-location');
                document.getElementById('v-date').textContent      = button.getAttribute('data-date');
                document.getElementById('v-time').textContent      = button.getAttribute('data-time');
                document.getElementById('v-desc').textContent      = button.getAttribute('data-desc');
                document.getElementById('v-cat').textContent       = button.getAttribute('data-category');
                document.getElementById('v-img').src               = button.getAttribute('data-img');
                document.getElementById('v-email').textContent     = button.getAttribute('data-email');
                document.getElementById('v-phone').textContent     = button.getAttribute('data-phone');
                document.getElementById('v-user-name').textContent = button.getAttribute('data-user');
                document.getElementById('v-user-role').textContent = button.getAttribute('data-role');
                document.getElementById('v-avatar').src =
                    `https://ui-avatars.com/api/?name=${encodeURIComponent(button.getAttribute('data-user'))}&background=0b5a30&color=fff`;

                const postType          = button.getAttribute('data-type');
                const submittedToOffice = button.getAttribute('data-submitted-to-office') === 'true';

                const typeBadge  = document.getElementById('v-type');
                const statusBox  = document.getElementById('v-status-box');
                const statusIcon = document.getElementById('v-status-icon');
                const statusText = document.getElementById('v-status-text');

                // Always reset inline styles first so previous state doesn't bleed through
                statusBox.removeAttribute('style');
                statusIcon.removeAttribute('style');

                if (postType === 'Lost') {
                    // ── STATE 1: LOST ──────────────────────────────────────
                    typeBadge.textContent = 'Lost';
                    typeBadge.className   = 'badge bg-danger position-absolute top-0 end-0 m-2 rounded-pill px-3';

                    statusBox.className             = 'alert d-flex align-items-start gap-3 border-0 rounded-3 p-3';
                    statusBox.style.backgroundColor = '#EBCFCD';
                    statusBox.style.borderLeft      = '4px solid #5E0006';

                    statusIcon.className   = 'bi bi-exclamation-circle-fill fs-5';
                    statusIcon.style.color = '#5E0006';

                    statusText.innerHTML = `
                        <div class="fw-bold mb-1" style="color:#5E0006; font-size:18px;">Someone is looking for this item</div>
                        <div class="fw-light" style="color:#343A40;">If you have found this item, please contact the owner using the information provided.</div>
                    `;

                } else if (!submittedToOffice) {
                    // ── STATE 2: FOUND — still held by finder ──────────────
                    typeBadge.textContent = 'Found';
                    typeBadge.className   = 'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3';

                    statusBox.className             = 'alert d-flex align-items-start gap-3 border-0 rounded-3 p-3';
                    statusBox.style.backgroundColor = '#fff3cd';
                    statusBox.style.borderLeft      = '4px solid #ffc107';

                    statusIcon.className   = 'bi bi-person-fill fs-5';
                    statusIcon.style.color = '#856404';

                    statusText.innerHTML = `
                        <div class="fw-bold mb-1" style="color:#856404; font-size:18px;">Currently Held by Finder</div>
                        <p class="fw-light mb-0" style="color:#343A40;">The person who found this item is currently holding it. You can contact them directly using the information below.</p>
                    `;

                } else {
                    // ── STATE 3: FOUND — submitted to office ───────────────
                    typeBadge.textContent = 'Found';
                    typeBadge.className   = 'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3';

                    statusBox.className             = 'alert d-flex align-items-start gap-3 border-0 rounded-3 p-3';
                    statusBox.style.backgroundColor = '#D4E3DA';
                    statusBox.style.borderLeft      = '4px solid #0F6631';

                    statusIcon.className   = 'bi bi-info-circle-fill fs-5';
                    statusIcon.style.color = '#0F6631';

                    statusText.innerHTML = `
                        <div class="fw-bold mb-1" style="color:#0F6631; font-size:18px;">Surrendered to Lost &amp; Found Office</div>
                        <p class="fw-light mb-2" style="color:#343A40;">This item has been turned over to the GNC Lost &amp; Found Management Office. Please visit the office during business hours to claim your item.</p>
                        <div class="small">
                            <strong>Office Hours:</strong> <span style="color:#343A40; font-weight:500;">Monday - Saturday, 8:00 AM - 5:00 PM</span><br>
                            <strong>Location:</strong> <span style="color:#343A40; font-weight:500;">Main Building, Ground Floor</span>
                        </div>
                    `;
                }
            });
        }
    </script>
</body>
</html>