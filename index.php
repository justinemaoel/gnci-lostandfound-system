<?php
$host = 'localhost';
$db   = 'lost_and_found_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$sql = "SELECT items.*, categories.category_name 
        FROM items 
        LEFT JOIN categories ON items.category_id = categories.id 
        WHERE items.upload_status = 'approved' 
        ORDER BY items.created_at DESC";

$result = $conn->query($sql);
$items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GNC | Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="assets/images/GNC Logo.svg" alt="GNC Logo" width="50" height="50" class="me-2">
                <div class="brand-text">
                    <span class="fw-bold d-block lh-1">Guagua National Colleges</span>
                    <small class="d-block">Lost & Found</small>
                </div>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link px-3" href="#">ABOUT</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="#">BROWSE ITEMS</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="nav-link btn-login-reg px-4 py-2" href="auth/login.php">LOGIN / REGISTER</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- LANDING PAGE -->
    <section class="hero-section vh-100 d-flex align-items-center text-white" 
            style="background: url('assets/images/gnc-landing-page-desktop.png') no-repeat center center; background-size: cover;">
        
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-xl-6">
                    
                    <h1 class="display-1 fw-bold mb-0">Search. Report.</h1>
                    <h1 class="display-1 fw-bold mb-4">Recover.</h1>
                    
                    <p class="fs-4 fw-light mb-5 opacity-75">
                        Built for GNCians to help each other find <br class="d-none d-md-block"> 
                        lost items on campus.
                    </p>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="#" class="btn btn-light btn-lg px-4 py-2 fw-semibold">Report Lost Items</a>
                        <a href="#" class="btn btn-outline-light btn-lg px-4 py-2 fw-semibold">Browse Found Items</a>
                    </div>

                </div>
            </div>
        </div>
    </section>


    <!-- MISSION STATEMENT -->
    <section class="mission-section py-5 my-5">
        <div class="container text-center">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    
                    <h2 class="display-5 fw-bold mb-4">Our Mission</h2>
                    
                    <p class="lead fs-4 text-secondary mb-5 px-md-5">
                        To provide a simple, secure, and reliable way to reunite lost items with their owners, 
                        reducing stress and fostering a culture of honesty and responsibility 
                        within the GNC community.
                    </p>

                    <div class="d-flex flex-wrap justify-content-center gap-4 mt-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle text-success fs-4 me-2"></i>
                            <span>24/7 Access</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle text-success fs-4 me-2"></i>
                            <span>Free to Use</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle text-success fs-4 me-2"></i>
                            <span>Secure & Private</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- RECENTLY FOUND ITEMS -->
    <section id="items" class="py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bold display-5 mb-2">Recently Found Items</h2>
                <p class="text-secondary">Check the latest found items. Is one of them yours?</p>
                
                <form class="mt-4 d-flex justify-content-center">
                    <div class="input-group" style="max-width: 600px;">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-secondary"></i>
                        </span>
                        <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Search for your lost item...">
                        <button class="btn btn-dark px-4" type="submit" style="background-color: #0b4628; border: none;">Search</button>
                    </div>
                </form>
            </div>

            <div class="row g-4">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border-0 overflow-hidden" style="border-radius: 12px;">
                                <div class="item-img-container" style="height: 250px; background-color: #f8f9fa;">
                                    <?php if (!empty($item['item_img'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($item['item_img'], ENT_QUOTES, 'UTF-8') ?>" 
                                            alt="<?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                            class="w-100 h-100 object-fit-cover">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">No Image</div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-body p-4">
                                    <h3 class="h5 fw-bold mb-2"><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    
                                    <span class="badge border text-success fw-normal mb-3" style="font-size: 0.75rem;">
                                        <?= htmlspecialchars($item['category'] ?? 'General', ENT_QUOTES, 'UTF-8') ?>
                                    </span>

                                    <div class="small text-secondary mb-4">
                                        <div class="mb-1">
                                            <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($item['location_text'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div>
                                            <i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y', strtotime($item['date_reported'])) ?>
                                        </div>
                                    </div>

                                    <a href="item-details.php?id=<?= (int) $item['id'] ?>" 
                                    class="btn btn-outline-success w-100 fw-bold py-2" 
                                    style="border-color: #0b4628; color: #0b4628;">
                                    View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-secondary">No found items yet. Check back later.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-5">
                <a href="browse-items.php" class="btn btn-success btn-lg px-5" style="background-color: #0b4628; border: none;">
                    View All Items
                </a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="mt-auto py-5" style="background-color: #06331a; color: #ffffff; font-family: sans-serif;">
        <div class="container-fluid px-5">
            
            <div class="mb-5">
                <h2 class="fw-bold mb-0" style="font-size: 1.75rem;">About Us</h2>
                <hr style="border-top: 1px solid rgba(255,255,255,0.3); opacity: 1; margin-top: 15px; margin-bottom: 20px;">
                <p style="font-size: 0.95rem; line-height: 1.6;">
                    The <strong>GNC Lost & Found System</strong> is a dedicated platform designed to simplify how our campus handles misplaced belongings. Our goal is to foster a community of honesty and efficiency by providing students and staff with a fast, photo-based way to report lost items and browse recovered property.
                </p>
                <p style="font-size: 0.95rem; line-height: 1.6;">
                    Whether you've lost a textbook, a wallet, or your school ID, this system serves as the primary bridge to reconnect you with your essentials. By centralizing all reports in one dashboard, we make the recovery process faster for everyone at Guagua National Colleges.
                </p>
            </div>

            <div class="mb-5">
                <h2 class="fw-bold mb-0" style="font-size: 1.75rem;">Found an item? But want to stay anonymous?</h2>
                <hr style="border-top: 1px solid rgba(255,255,255,0.3); opacity: 1; margin-top: 15px; margin-bottom: 20px;">
                <p style="font-size: 0.95rem; line-height: 1.6;">
                    If you have found a lost item and are unable to post it yourself, please surrender the item to the <strong>GNC Guard House</strong> or the <strong>Security Office</strong>. Our staff will secure the item and post the details on this system so the rightful owner can find it.
                </p>
            </div>

            <div class="mb-5">
                <h2 class="fw-bold mb-0" style="font-size: 1.75rem;">Contact Info</h2>
                <hr style="border-top: 1px solid rgba(255,255,255,0.3); opacity: 1; margin-top: 15px; margin-bottom: 30px;">
                
                <div class="row align-items-center">
                    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
                        <i class="bi bi-envelope me-3" style="font-size: 1.4rem;"></i>
                        <span style="font-size: 0.95rem;">nangit.trishia@gmail.com</span>
                    </div>
                    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
                        <i class="bi bi-chat-square-dots me-3" style="font-size: 1.4rem;"></i>
                        <span style="font-size: 0.95rem;">09+ *** *** ****</span>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <i class="bi bi-geo-alt me-3" style="font-size: 1.4rem;"></i>
                        <span style="font-size: 0.95rem;">Guagua, Pampanga, Philippines</span>
                    </div>
                </div>
            </div>
    </footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>