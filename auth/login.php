<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password']; 

    // 1. Change $conn to $pdo, which matches your db.php variable
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?"); 
    
    // 2. In PDO, you execute by passing the variables directly as an array
    $stmt->execute([$email]);
    
    // 3. Fetch the user record directly
    $user = $stmt->fetch();

    // Check if user exists and the password matches the hash in the database
    if ($user && password_verify($password, $user['password'])) {
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['first_name'] = $user['first_name']; 
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email']; 

        $adminEmail = "admin.lostandfound@gmail.com";

        if (strtolower($email) === strtolower($adminEmail)) {
            header("Location: ../admin-dash.php"); 
        } else {
            header("Location: ../user-dash.php");
        }
        exit();

    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GNC Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

    <div class="login-container">
        <div class="login-content">
            <h1 class="brand-title">Search. Report.</h1>
            <h1 class="brand-title highlight">Recover.</h1>
            
            <h2 class="login-subtitle">Login</h2>

            <?php if($error): ?>
                <div class="alert alert-danger py-2 small border-0 text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="form-label text-white">Email Address</label>
                    <input type="email" name="email" class="form-control custom-input" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label text-white">Password</label>
                    <div class="position-relative">
                        <input type="password" name="password" id="passwordInput" class="form-control custom-input pe-5" required>
                        <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 text-white-50" 
                        id="togglePasswordIcon" 
                        style="cursor: pointer; font-size: 1.2rem;"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-light w-100">Login</button>
                
                <div class="text-center mt-4">
                    <p class="register-text">
                        Do not have account? <a href="../auth/register.php" class="text-white fw-bold">Register</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script>
        const passwordInput = document.querySelector('#passwordInput');
        const toggleIcon = document.querySelector('#togglePasswordIcon');

        toggleIcon.addEventListener('click', function () {
            // Toggle the type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle the icon class
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
            
            // Optional: Make the icon brighter when active
            if (type === 'text') {
                this.classList.replace('text-white-50', 'text-white');
            } else {
                this.classList.replace('text-white', 'text-white-50');
            }
        });
    </script>
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>