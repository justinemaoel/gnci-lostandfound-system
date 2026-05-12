<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// SESSION DATA
$fullName = trim(($_SESSION['first_name'] ?? 'User') . " " . ($_SESSION['last_name'] ?? ''));
$userRole = $_SESSION['role'] ?? 'student';

// Dashboard link — admin vs regular user
$isAdmin       = strtolower(trim($_SESSION['email'] ?? '')) === 'admin.lostandfound@gmail.com';
$dashboardLink = $isAdmin ? 'admin-dash.php' : 'user-dash.php';

// Fetch all categories for filter dropdown
$categories = $pdo->query("SELECT id, category_name FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// FILTERS
$search         = trim($_GET['q'] ?? '');
$filterType     = in_array($_GET['type'] ?? '', ['all', 'lost', 'found']) ? $_GET['type'] : 'all';
$filterCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// PAGINATION
$perPage     = 12;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

// BUILD WHERE clause
$conditions = ["i.upload_status = 'approved'"];
$params     = [];

if ($search !== '') {
    $conditions[] = "(i.item_name LIKE ? OR i.location_text LIKE ? OR i.description LIKE ?)";
    $params[]     = "%$search%";
    $params[]     = "%$search%";
    $params[]     = "%$search%";
}

if ($filterType === 'lost') {
    $conditions[] = "i.post_type = 'Lost'";
} elseif ($filterType === 'found') {
    $conditions[] = "i.post_type = 'Found'";
}

if ($filterCategory > 0) {
    $conditions[] = "i.category_id = ?";
    $params[]     = $filterCategory;
}

$where = implode(' AND ', $conditions);

// COUNT total matching items
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items i WHERE $where");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalItems / $perPage);

// Clamp currentPage
$currentPage = min($currentPage, max(1, $totalPages));
$offset      = ($currentPage - 1) * $perPage;

// FETCH paginated items
$fetchParams = array_merge($params, [$perPage, $offset]);
$stmt = $pdo->prepare("
    SELECT i.id, i.item_name, i.post_type, i.location_text, i.date_reported,
           i.description, i.item_img, i.submitted_to_office,
           i.contact_email, i.contact_num,
           c.category_name,
           u.first_name, u.last_name, u.role AS user_role, u.email AS user_email
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    LEFT JOIN users u      ON i.user_id = u.id
    WHERE $where
    ORDER BY i.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($fetchParams);
$browse_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Active category name for tag display
$activeCategoryName = '';
foreach ($categories as $cat) {
    if ((int)$cat['id'] === $filterCategory) {
        $activeCategoryName = $cat['category_name'];
        break;
    }
}

// Helper: build query string preserving all current params except the ones we override
function buildQuery(array $overrides = []): string {
    $base = [
        'q'        => trim($_GET['q'] ?? ''),
        'type'     => $_GET['type'] ?? 'all',
        'category' => $_GET['category'] ?? 0,
        'page'     => 1,
    ];
    return http_build_query(array_merge($base, $overrides));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Items - GNC</title>

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://ui-avatars.com" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/browse-item.css">
    <style>
        /* ── Filter dropdown ─────────────────────────────────────── */
        .filter-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            z-index: 1050;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            min-width: 220px;
            padding: 8px 0;
            display: none;
        }
        .filter-dropdown.show { display: block; }

        .filter-dropdown .filter-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 18px;
            cursor: pointer;
            font-size: 0.9rem;
            color: #333;
            transition: background 0.15s;
            text-decoration: none;
        }
        .filter-dropdown .filter-item:hover { background-color: #f0f8f4; color: #0b4628; }
        .filter-dropdown .filter-item.active { color: #0b4628; font-weight: 600; }
        .filter-dropdown .filter-item .check-icon { display: none; color: #0b4628; }
        .filter-dropdown .filter-item.active .check-icon { display: inline; }
        .filter-dropdown-divider { border-top: 1px solid #f0f0f0; margin: 4px 0; }

        .filter-btn {
            border: 1.5px solid #0b4628;
            color: #0b4628;
            background: white;
            font-weight: 600;
            border-radius: 6px;
            padding: 7px 16px;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: background 0.15s, color 0.15s;
            cursor: pointer;
            font-size: 0.875rem;
        }
        .filter-btn:hover,
        .filter-btn.active-filter { background-color: #0b4628; color: white; }
        .filter-btn .filter-badge {
            background: #0b4628; color: white;
            border-radius: 50%; width: 18px; height: 18px;
            font-size: 11px; display: inline-flex;
            align-items: center; justify-content: center;
        }
        .filter-btn.active-filter .filter-badge { background: white; color: #0b4628; }

        .active-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e8f5ee;
            border: 1px solid #0b4628;
            color: #0b4628;
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 0.82rem;
            font-weight: 500;
        }
        .active-filter-tag a {
            color: #0b4628; line-height: 1;
            text-decoration: none; font-size: 1rem; cursor: pointer;
        }

        /* ── Type pills ──────────────────────────────────────────── */
        .type-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            border: 1.5px solid #dee2e6;
            background: white;
            color: #555;
            text-decoration: none;
            transition: all 0.15s;
        }
        .type-pill:hover { border-color: #0b4628; color: #0b4628; }
        .type-pill.active { background-color: #0b4628; color: white; border-color: #0b4628; }
    </style>
</head>
<body class="bg-light">

<!-- TOP NAVBAR -->
<header class="navbar sticky-top flex-md-nowrap p-3 shadow-sm" style="background-color: #0b4628;">
    <div class="d-flex align-items-center">
        <button class="d-md-none me-3 btn btn-sm border-0 text-white" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
            <i class="bi bi-list fs-4"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center text-white p-0" href="<?= $dashboardLink ?>">
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
            <small class="text-uppercase opacity-75" style="font-size: 10px;"><?= htmlspecialchars($userRole) ?></small>
        </div>
        <img id="nav-avatar"
             src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='35' height='35'%3E%3Crect width='35' height='35' rx='50' fill='%230b5a30'/%3E%3C/svg%3E"
             data-name="<?= urlencode($fullName) ?>"
             width="35" height="35" class="rounded-circle" alt="Avatar">
    </div>
</header>

<div class="container-fluid">
    <div class="row">

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
                <a href="<?= $dashboardLink ?>" class="nav-link d-flex align-items-center" style="color: #0b4628;">
                    <i class="bi bi-house-door-fill me-2"></i> Home
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="browse-item.php" class="nav-link d-flex align-items-center" style="background-color: #d1e7dd; color: #0b4628;">
                    <i class="bi bi-search me-2"></i> Browse Item
                </a>
            </li>
            <?php if ($isAdmin): ?>
            <li class="nav-item mb-2">
                <a href="my-activity.php" class="nav-link d-flex align-items-center" style="color: #0b4628;">
                    <i class="bi bi-clock-history me-2"></i> My Activity
                </a>
            </li>
            <?php endif; ?>
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
                    <a href="<?= $dashboardLink ?>" class="nav-link d-flex align-items-center" style="color: #0b4628;">
                        <i class="bi bi-house-door-fill me-2"></i> Home
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="browse-item.php" class="nav-link d-flex align-items-center" style="background-color: #d1e7dd; color: #0b4628;">
                        <i class="bi bi-search me-2"></i> Browse Item
                    </a>
                </li>
                <?php if ($isAdmin): ?>
                <li class="nav-item mb-2">
                    <a href="my-activity.php" class="nav-link d-flex align-items-center" style="color: #0b4628;">
                        <i class="bi bi-clock-history me-2"></i> My Activity
                    </a>
                </li>
                <?php endif; ?>
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
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 min-vh-100">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Browse All Items</h3>
                <small class="text-muted">
                    Showing <?= count($browse_items) ?> of <?= $totalItems ?> item<?= $totalItems !== 1 ? 's' : '' ?>
                </small>
            </div>

            <!-- ── Search + Filter bar ──────────────────────────────── -->
            <form method="GET" action="" id="searchForm" class="mb-3">
                <input type="hidden" name="type"     value="<?= htmlspecialchars($filterType) ?>" id="typeInput">
                <input type="hidden" name="category" value="<?= $filterCategory ?>"               id="categoryInput">
                <input type="hidden" name="page"     value="1">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-secondary"></i>
                            </span>
                            <input type="text" name="q"
                                   class="form-control border-start-0 py-2"
                                   placeholder="Search by item name, location, or description…"
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn fw-semibold px-4 py-2"
                                type="submit"
                                style="background-color:#0b4628; color:white; border:none;">
                            Search
                        </button>
                    </div>
                    <!-- Category filter button -->
                    <div class="col-auto position-relative">
                        <button type="button"
                                id="filterBtn"
                                class="filter-btn <?= $filterCategory > 0 ? 'active-filter' : '' ?>">
                            <i class="bi bi-funnel"></i>
                            Filter
                            <?php if ($filterCategory > 0): ?>
                                <span class="filter-badge">1</span>
                            <?php endif; ?>
                        </button>

                        <div class="filter-dropdown" id="filterDropdown">
                            <div style="padding:8px 18px 6px; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; color:#888; font-weight:600;">
                                Category
                            </div>
                            <a href="#" class="filter-item <?= $filterCategory === 0 ? 'active' : '' ?>"
                               data-category-id="0" data-filter-link>
                                All Categories
                                <i class="bi bi-check2 check-icon"></i>
                            </a>
                            <div class="filter-dropdown-divider"></div>
                            <?php foreach ($categories as $cat): ?>
                                <a href="#"
                                   class="filter-item <?= $filterCategory === (int)$cat['id'] ? 'active' : '' ?>"
                                   data-category-id="<?= $cat['id'] ?>"
                                   data-filter-link>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                    <i class="bi bi-check2 check-icon"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Active filters row -->
            <?php $hasActiveFilters = $filterCategory > 0 || $search !== '' || $filterType !== 'all'; ?>
            <?php if ($hasActiveFilters): ?>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                    <?php if ($filterCategory > 0 && $activeCategoryName): ?>
                        <span class="text-muted small">Filtered by:</span>
                        <span class="active-filter-tag">
                            <?= htmlspecialchars($activeCategoryName) ?>
                            <a href="#" data-category-id="0" data-filter-link title="Remove">&#x2715;</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($search !== ''): ?>
                        <span class="active-filter-tag">
                            "<?= htmlspecialchars($search) ?>"
                            <a href="?<?= buildQuery(['q' => '']) ?>" title="Clear search">&#x2715;</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($hasActiveFilters): ?>
                        <a href="browse-item.php" class="text-muted small ms-1" style="text-decoration:underline;">
                            Clear all
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ── Lost / Found / All type pills ───────────────────── -->
            <div class="d-flex gap-2 flex-wrap mb-4">
                <a href="#" class="type-pill <?= $filterType === 'all'   ? 'active' : '' ?>" data-type="all"   data-type-link>
                    <i class="bi bi-grid"></i> All Items
                </a>
                <a href="#" class="type-pill <?= $filterType === 'found' ? 'active' : '' ?>" data-type="found" data-type-link>
                    <i class="bi bi-check-circle"></i> Found Items
                </a>
                <a href="#" class="type-pill <?= $filterType === 'lost'  ? 'active' : '' ?>" data-type="lost"  data-type-link>
                    <i class="bi bi-exclamation-circle"></i> Lost Items
                </a>
            </div>

            <!-- ── Item grid ────────────────────────────────────────── -->
            <div class="row g-4">
                <?php if (!empty($browse_items)): ?>
                    <?php foreach ($browse_items as $item):
                        $img               = !empty($item['item_img']) ? "uploads/" . $item['item_img'] : "assets/images/placeholder-image.jpg";
                        $isFound           = strtolower($item['post_type']) === 'found';
                        $submittedToOffice = !empty($item['submitted_to_office']) ? 'true' : 'false';
                        $posterName        = htmlspecialchars($item['first_name'] . " " . $item['last_name']);
                        $contactEmail      = htmlspecialchars($item['contact_email'] ?: ($item['user_email'] ?? 'Not provided'));
                    ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                                <img loading="lazy"
                                     src="<?= $img ?>"
                                     class="card-img-top object-fit-cover"
                                     style="height: 200px;"
                                     alt="<?= htmlspecialchars($item['item_name']) ?>"
                                     onerror="this.src='assets/images/placeholder-image.jpg'">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold mb-0 text-truncate" style="max-width: 70%;">
                                            <?= htmlspecialchars($item['item_name']) ?>
                                        </h5>
                                        <span class="badge rounded-pill <?= $isFound ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($item['post_type']) ?>
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-1">
                                        <i class="bi bi-tag me-1 text-success"></i>
                                        <?= htmlspecialchars($item['category_name'] ?? 'General') ?>
                                    </p>
                                    <p class="text-muted small mb-3">
                                        <i class="bi bi-person me-1 text-success"></i>
                                        <strong>Posted by:</strong> <?= $posterName ?><br>
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
                                        data-user="<?= $posterName ?>"
                                        data-role="<?= ucfirst($item['user_role'] ?? 'Student') ?>"
                                        data-email="<?= $contactEmail ?>"
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
                        <p class="text-muted mt-2">
                            <?= $search !== '' ? 'No items found matching "' . htmlspecialchars($search) . '".' : 'No items available.' ?>
                        </p>
                        <?php if ($hasActiveFilters): ?>
                            <a href="browse-item.php" class="btn btn-outline-secondary btn-sm mt-2">Clear filters</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-5" aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= buildQuery(['page' => $currentPage - 1]) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= buildQuery(['page' => $p]) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= buildQuery(['page' => $currentPage + 1]) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

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
                    <!-- LEFT COLUMN -->
                    <div class="col-md-5">
                        <div class="position-relative mb-3">
                            <img id="v-img" src="" class="img-fluid rounded-3 w-100 object-fit-cover"
                                 style="height: 280px; background: #eee;"
                                 onerror="this.src='assets/images/placeholder-image.jpg'">
                            <span id="v-type" class="badge position-absolute top-0 end-0 m-2 rounded-pill px-3"></span>
                        </div>
                        <h5 class="fw-bold mb-2">Description</h5>
                        <p id="v-desc" class="text-muted small mb-4" style="line-height: 1.6;"></p>

                        <h5 class="fw-bold mb-3">Posted by:</h5>
                        <div class="p-3 rounded-3" style="background-color: #f8f9fa;">
                            <div class="d-flex align-items-center gap-3">
                                <img id="v-avatar" src="" class="rounded-circle border" width="45" height="45" alt="Avatar">
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

                    <!-- RIGHT COLUMN -->
                    <div class="col-md-7 border-start ps-md-4">
                        <h2 id="v-name" class="fw-bold mb-1"></h2>
                        <div class="mb-4">
                            <span id="v-cat" class="badge border fw-normal"
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Lazy-load navbar avatar ──────────────────────────────────────
    const navAvatar = document.getElementById('nav-avatar');
    if (navAvatar) {
        navAvatar.src = 'https://ui-avatars.com/api/?name=' + navAvatar.dataset.name + '&background=0b5a30&color=fff';
    }

    // ── Filter dropdown toggle ───────────────────────────────────────
    const filterBtn      = document.getElementById('filterBtn');
    const filterDropdown = document.getElementById('filterDropdown');

    filterBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        filterDropdown.classList.toggle('show');
    });
    document.addEventListener('click', function (e) {
        if (!filterDropdown.contains(e.target) && e.target !== filterBtn) {
            filterDropdown.classList.remove('show');
        }
    });

    // ── Category filter links ────────────────────────────────────────
    document.querySelectorAll('[data-filter-link]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('categoryInput').value = this.getAttribute('data-category-id');
            filterDropdown.classList.remove('show');
            sessionStorage.setItem('restoreScrollY', window.scrollY);
            document.getElementById('searchForm').submit();
        });
    });

    // ── Lost/Found/All type pills ────────────────────────────────────
    document.querySelectorAll('[data-type-link]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('typeInput').value = this.getAttribute('data-type');
            sessionStorage.setItem('restoreScrollY', window.scrollY);
            document.getElementById('searchForm').submit();
        });
    });

    // ── Search submit ────────────────────────────────────────────────
    document.getElementById('searchForm').addEventListener('submit', function () {
        sessionStorage.setItem('restoreScrollY', window.scrollY);
    });

    // ── Restore scroll position after reload ─────────────────────────
    const savedY = sessionStorage.getItem('restoreScrollY');
    if (savedY !== null) {
        sessionStorage.removeItem('restoreScrollY');
        window.scrollTo({ top: parseInt(savedY, 10), behavior: 'instant' });
    }

    // ── View Details Modal ───────────────────────────────────────────
    const STATUS_CONFIG = {
        lost: {
            label:  'Lost',
            badge:  'badge bg-danger position-absolute top-0 end-0 m-2 rounded-pill px-3',
            bg:     '#EBCFCD', border: '4px solid #5E0006',
            icon:   'bi bi-exclamation-circle-fill fs-5', color: '#5E0006',
            title:  'Someone is looking for this item',
            body:   'If you have found this item, please contact the owner using the information provided.',
        },
        foundHeld: {
            label:  'Found',
            badge:  'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3',
            bg:     '#fff3cd', border: '4px solid #ffc107',
            icon:   'bi bi-exclamation-circle-fill fs-5', color: '#856404',
            title:  'Currently Held by Finder',
            body:   'The person who found this item is currently holding it. You can contact them directly using the information below.',
        },
        foundOffice: {
            label:  'Found',
            badge:  'badge bg-success position-absolute top-0 end-0 m-2 rounded-pill px-3',
            bg:     '#D4E3DA', border: '4px solid #0F6631',
            icon:   'bi bi-info-circle-fill fs-5', color: '#0F6631',
            title:  'Surrendered to Lost &amp; Found Office',
            body:   'This item has been turned over to the GNC Lost &amp; Found Management Office. Please visit the office during business hours to claim your item.<br><br><strong>Office Hours:</strong> Monday - Saturday, 8:00 AM - 5:00 PM<br><strong>Location:</strong> Main Building, Ground Floor',
        },
    };

    const modalEl = document.getElementById('viewItemModal');
    if (!modalEl) return;

    modalEl.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;

        const get = attr => btn.getAttribute(attr);

        const isLost            = (get('data-type') || '').toLowerCase() === 'lost';
        const submittedToOffice = get('data-submitted-to-office') === 'true';
        const cfg               = isLost ? STATUS_CONFIG.lost
                                : submittedToOffice ? STATUS_CONFIG.foundOffice
                                : STATUS_CONFIG.foundHeld;

        document.getElementById('v-name').textContent      = get('data-name')     || '';
        document.getElementById('v-cat').textContent       = get('data-category') || 'General';
        document.getElementById('v-loc').textContent       = get('data-location') || '—';
        document.getElementById('v-date').textContent      = get('data-date')     || '—';
        document.getElementById('v-time').textContent      = get('data-time')     || '—';
        document.getElementById('v-desc').textContent      = get('data-desc')     || 'No description provided.';
        document.getElementById('v-img').src               = get('data-img')      || 'assets/images/placeholder-image.jpg';
        document.getElementById('v-email').textContent     = get('data-email')    || 'Not provided';
        document.getElementById('v-phone').textContent     = get('data-phone')    || 'Not provided';
        document.getElementById('v-user-name').textContent = get('data-user')     || '—';
        document.getElementById('v-user-role').textContent = get('data-role')     || 'Student';
        document.getElementById('v-avatar').src = 'https://ui-avatars.com/api/?name='
            + encodeURIComponent(get('data-user') || '') + '&background=0b5a30&color=fff';

        const typeBadge  = document.getElementById('v-type');
        const statusBox  = document.getElementById('v-status-box');
        const statusIcon = document.getElementById('v-status-icon');
        const statusText = document.getElementById('v-status-text');

        typeBadge.textContent           = cfg.label;
        typeBadge.className             = cfg.badge;
        statusBox.className             = 'alert d-flex align-items-start gap-3 border-0 rounded-3 p-3';
        statusBox.style.backgroundColor = cfg.bg;
        statusBox.style.borderLeft      = cfg.border;
        statusIcon.className            = cfg.icon;
        statusIcon.style.color          = cfg.color;
        statusText.innerHTML            = `<div class="fw-bold mb-1" style="color:${cfg.color}; font-size:18px;">${cfg.title}</div>`
                                        + `<div class="fw-light" style="color:#343A40;">${cfg.body}</div>`;
    });
});
</script>
</body>
</html>