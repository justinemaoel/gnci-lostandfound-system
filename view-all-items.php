<?php
$host = 'localhost';
$db   = 'lost_and_found_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

require_once __DIR__ . '/includes/input.php';

$search       = getGetString('q');
$filterType   = getAllowedEnum('type', ['all', 'lost', 'found'], 'all');
$filterCatId  = getGetInt('category', 0);

// Load categories for the dropdown
$categories = [];
$catResult  = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");
if ($catResult) {
    $categories = $catResult->fetch_all(MYSQLI_ASSOC);
}

// Validate the submitted category against the loaded list
$validCatIds = array_map(fn($c) => (int)$c['id'], $categories);
if ($filterCatId !== 0 && !in_array($filterCatId, $validCatIds, true)) {
    $filterCatId = 0;
}

$conditions = ["items.upload_status = 'approved'"];
$params     = [];
$types      = '';

if ($search !== '') {
    $conditions[] = "(items.item_name LIKE ? OR items.location_text LIKE ?)";
    $params[]     = "%$search%";
    $params[]     = "%$search%";
    $types       .= 'ss';
}

if ($filterType === 'found') {
    $conditions[] = "items.post_type = 'Found'";
} elseif ($filterType === 'lost') {
    $conditions[] = "items.post_type = 'Lost'";
}

if ($filterCatId > 0) {
    $conditions[] = "items.category_id = ?";
    $params[]     = $filterCatId;
    $types       .= 'i';
}

$hasActiveFilters = ($search !== '' || $filterType !== 'all' || $filterCatId > 0);

$where = implode(' AND ', $conditions);

// ✅ JOIN users table so we have poster name for the modal
$sql = "SELECT items.*, categories.category_name,
        users.first_name, users.last_name
        FROM items
        LEFT JOIN categories ON items.category_id = categories.id
        LEFT JOIN users ON items.user_id = users.id
        WHERE $where
        ORDER BY items.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View all items</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/view-all-item-style.css">
</head>
<body>

    <nav class="navbar sticky-top navbar-expand-lg shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="assets/images/GNC Logo.svg" alt="GNC Logo" width="50" height="50" class="me-2">
                <div class="brand-text d-none d-md-block">
                    <span class="fw-bold d-block lh-1">Guagua National Colleges</span>
                    <small class="d-block">Lost & Found</small>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link px-3" href="index.php">HOME</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#items">BROWSE ITEMS</a></li>
                    <li class="nav-item ms-lg-3">
                        <a id="login-register" class="nav-link px-4 py-2" href="auth/login.php" target="_blank">LOGIN / REGISTER</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="items" class="py-5">
        <div class="container py-4">
            <h2 class="fw-bold display-6 mb-4">Lost & Found Items</h2>

            <form method="GET" class="mb-4">
                <input type="hidden" name="type" value="<?= htmlspecialchars($filterType) ?>">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-secondary"></i>
                            </span>
                            <input type="text" name="q"
                                class="form-control bg-light border-start-0 py-2"
                                placeholder="Search for your lost item..."
                                value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <select name="category" class="form-select bg-light py-2">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"
                                    <?= $filterCatId === (int)$cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button class="btn px-4 py-2 w-100" type="submit"
                                style="background-color:#0b4628; color:white; border:none;">Search</button>
                    </div>
                    <?php if ($hasActiveFilters): ?>
                        <div class="col-12 col-md-auto">
                            <a href="view-all-items.php" class="btn btn-link text-decoration-none p-0 px-md-2"
                               style="color:#0b4628;">
                                <i class="bi bi-x-circle me-1"></i>Clear
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <div class="item-count-text">
                Showing <strong><?= count($items) ?></strong> item<?= count($items) !== 1 ? 's' : '' ?>
                <?php if ($search !== ''): ?>
                    for "<strong><?= htmlspecialchars($search) ?></strong>"
                <?php endif; ?>
            </div>

            <div class="mb-5">
                <?php
                    $pillBase = ['q' => $search];
                    if ($filterCatId > 0) {
                        $pillBase['category'] = $filterCatId;
                    }
                    $pillAllUrl   = '?' . http_build_query(array_merge($pillBase, ['type' => 'all']));
                    $pillFoundUrl = '?' . http_build_query(array_merge($pillBase, ['type' => 'found']));
                    $pillLostUrl  = '?' . http_build_query(array_merge($pillBase, ['type' => 'lost']));
                ?>
                <div class="filter-pills-container">
                    <a href="<?= htmlspecialchars($pillAllUrl) ?>"
                    class="filter-pill <?= $filterType === 'all'   ? 'active' : '' ?>">All Items</a>
                    <a href="<?= htmlspecialchars($pillFoundUrl) ?>"
                    class="filter-pill <?= $filterType === 'found' ? 'active' : '' ?>">Found Items</a>
                    <a href="<?= htmlspecialchars($pillLostUrl) ?>"
                    class="filter-pill <?= $filterType === 'lost'  ? 'active' : '' ?>">Lost Items</a>
                </div>
            </div>

            <div class="row g-4">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $type       = $item['post_type'] ?? 'Found';
                            $badgeClass = $type === 'Lost' ? 'bg-danger' : 'bg-success';
                            // ✅ Build fullName here per item, no undefined variable
                            $fullName   = trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''));
                            $imgSrc     = !empty($item['item_img']) ? 'uploads/' . $item['item_img'] : '';
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border-0 overflow-hidden"
                                style="border-radius:12px; border:1px solid #eee !important;">
                                <div style="height:250px; background-color:#f8f9fa; position:relative;">
                                    <span class="badge <?= $badgeClass ?> position-absolute top-0 end-0 m-3 px-3 py-2">
                                        <?= htmlspecialchars($type) ?>
                                    </span>
                                    <?php if ($imgSrc): ?>
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" class="w-100 h-100 object-fit-cover">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-4">
                                    <h3 class="h5 fw-bold mb-2"><?= htmlspecialchars($item['item_name']) ?></h3>
                                    <span class="badge border text-success fw-normal mb-3">
                                        <?= htmlspecialchars($item['category_name'] ?? 'General') ?>
                                    </span>
                                    <div class="small text-secondary mb-4">
                                        <div class="mb-1">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            <?= htmlspecialchars($item['location_text'] ?? 'Unknown Location') ?>
                                        </div>
                                        <div>
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= isset($item['date_reported']) ? date('F d, Y', strtotime($item['date_reported'])) : 'Date N/A' ?>
                                        </div>
                                    </div>

                                    <!-- ✅ Changed to <button> with data-bs-toggle so modal actually opens -->
                                    <button type="button"
                                            class="btn btn-outline-success w-100 fw-bold py-2"
                                            style="border-color:#0b4628; color:#0b4628;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewItemModal"
                                            data-name="<?= htmlspecialchars($item['item_name']) ?>"
                                            data-type="<?= htmlspecialchars($type) ?>"
                                            data-badge-class="<?= $badgeClass ?>"
                                            data-category="<?= htmlspecialchars($item['category_name'] ?? 'General') ?>"
                                            data-location="<?= htmlspecialchars($item['location_text'] ?? 'Unknown') ?>"
                                            data-date="<?= isset($item['date_reported']) ? date('F d, Y', strtotime($item['date_reported'])) : 'N/A' ?>"
                                            data-time="<?= htmlspecialchars($item['time_last_seen'] ?? 'N/A') ?>"
                                            data-desc="<?= htmlspecialchars($item['description'] ?? '') ?>"
                                            data-img="<?= htmlspecialchars($imgSrc) ?>"
                                            data-user="<?= htmlspecialchars($fullName) ?>"
                                            data-email="<?= htmlspecialchars($item['contact_email'] ?? 'N/A') ?>"
                                            data-phone="<?= htmlspecialchars($item['contact_num'] ?? 'N/A') ?>"
                                            data-submitted-to-office="<?= !empty($item['submitted_to_office']) ? 'true' : 'false' ?>"
                                            data-status="<?= htmlspecialchars($item['item_resolved_status'] ?? 'not resolved') ?>">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-secondary">
                            <?php if ($hasActiveFilters): ?>
                                No items match your search or filters. Try clearing them and searching again.
                            <?php else: ?>
                                No items found.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ✅ Modal with v-status-icon element added, and $fullName removed from static HTML -->
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
                                    style="height:250px; background:#eee;">
                                <span id="v-type" class="badge position-absolute top-0 end-0 m-2 rounded-pill px-3"></span>
                            </div>
                            <h5 class="fw-bold mb-2">Description</h5>
                            <p id="v-desc" class="text-muted small"></p>

                            <div class="mt-3 p-3 bg-light rounded-3">
                                <h6 class="fw-bold mb-2">Posted by:</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <!-- ✅ Avatar generated dynamically via JS -->
                                    <img id="v-avatar" src="" class="rounded-circle me-2" width="35" height="35">
                                    <div class="fw-bold small" id="v-poster"></div>
                                </div>
                                <div style="font-size:12px;">
                                    <div class="text-success mb-1"><i class="bi bi-envelope me-1"></i><span id="v-email"></span></div>
                                    <div class="text-muted"><i class="bi bi-telephone me-1"></i><span id="v-phone"></span></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <h2 id="v-name" class="fw-bold mb-1 h4"></h2>
                            <span id="v-cat" class="badge border text-success fw-normal mb-4"
                                style="border-color:#198754 !important;"></span>

                            <h5 class="fw-bold mb-3">Location & Time</h5>
                            <div class="d-flex align-items-start mb-3">
                                <i class="bi bi-geo-alt text-success fs-5 me-2"></i>
                                <div>
                                    <div class="text-muted small">Location</div>
                                    <div class="fw-bold" id="v-loc"></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-3">
                                <i class="bi bi-calendar-event text-success fs-5 me-2"></i>
                                <div>
                                    <div class="text-muted small">Date</div>
                                    <div class="fw-bold" id="v-date"></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-4">
                                <i class="bi bi-clock text-success fs-5 me-2"></i>
                                <div>
                                    <div class="text-muted small">Time Reported</div>
                                    <div class="fw-bold" id="v-time"></div>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-2">Item Status</h5>
                            <!-- ✅ v-status-icon now exists as a real element -->
                            <div id="v-status-box" class="alert d-flex align-items-start gap-2 border-0 rounded-3">
                                <i id="v-status-icon" class="bi bi-info-circle-fill fs-5"></i>
                                <div id="v-status-text" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const viewModal = document.getElementById('viewItemModal');
        viewModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;

            const name               = btn.getAttribute('data-name');
            const type               = btn.getAttribute('data-type');
            const bclass             = btn.getAttribute('data-badge-class');
            const cat                = btn.getAttribute('data-category');
            const loc                = btn.getAttribute('data-location');
            const date               = btn.getAttribute('data-date');
            const time               = btn.getAttribute('data-time');
            const desc               = btn.getAttribute('data-desc');
            const img                = btn.getAttribute('data-img');
            const user               = btn.getAttribute('data-user');
            const email              = btn.getAttribute('data-email');
            const phone              = btn.getAttribute('data-phone');
            const submittedToOffice  = btn.getAttribute('data-submitted-to-office') === 'true';

            // Fields
            document.getElementById('v-name').textContent   = name;
            document.getElementById('v-cat').textContent    = cat;
            document.getElementById('v-loc').textContent    = loc;
            document.getElementById('v-date').textContent   = date;
            document.getElementById('v-time').textContent   = time;
            document.getElementById('v-desc').textContent   = desc || 'No description provided.';
            document.getElementById('v-email').textContent  = email;
            document.getElementById('v-phone').textContent  = phone;
            document.getElementById('v-poster').textContent = user || 'Unknown';

            // Image
            document.getElementById('v-img').src = img || '';

            // Avatar
            document.getElementById('v-avatar').src =
                `https://ui-avatars.com/api/?name=${encodeURIComponent(user || 'User')}&background=0b5a30&color=fff`;

            // Type badge
            const typeBadge = document.getElementById('v-type');
            typeBadge.textContent = type;
            typeBadge.className   = `badge position-absolute top-0 end-0 m-2 rounded-pill px-3 ${bclass}`;

            // Status box
            const statusBox  = document.getElementById('v-status-box');
            const statusIcon = document.getElementById('v-status-icon');
            const statusText = document.getElementById('v-status-text');

            if (type === 'Lost') {
                statusBox.className      = 'alert alert-danger d-flex align-items-start gap-2 border-0 rounded-3';
                statusIcon.className     = 'bi bi-exclamation-circle-fill fs-5';
                statusIcon.style.color   = '#58151C';
                statusText.innerHTML     = `
                    <div class="fw-bold mb-1">Someone is looking for this item</div>
                    <div class="fw-light">If you have found this item, please contact the owner using the information provided.</div>
                `;
            } else if (submittedToOffice) {
                statusBox.className      = 'alert alert-success d-flex align-items-start gap-2 border-0 rounded-3';
                statusIcon.className     = 'bi bi-info-circle-fill fs-5';
                statusIcon.style.color   = '#0A3634';
                statusText.innerHTML     = `
                    <div class="fw-bold mb-1 text-dark">Surrendered to Lost & Found Office</div>
                    <p class="fw-light mb-2">This item has been turned over to the GNC Lost & Found Management Office. Please visit the office during business hours to claim your item.</p>
                    <div class="small">
                        <strong>Office Hours:</strong> Monday – Saturday, 8:00 AM – 5:00 PM<br>
                        <strong>Location:</strong> Main Building, Ground Floor
                    </div>
                `;
            } else {
                statusBox.className      = 'alert alert-warning d-flex align-items-start gap-2 border-0 rounded-3';
                statusIcon.className     = 'bi bi-exclamation-circle-fill fs-5';
                statusIcon.style.color   = '#856404';
                statusText.innerHTML     = `
                    <div class="fw-bold mb-1" style="color:#856404;">Currently Held by Finder</div>
                    <div class="fw-light">The person who found this item is currently holding it. You can contact them directly using the information below.</div>
                `;
            }
        });
    </script>
</body>
</html>