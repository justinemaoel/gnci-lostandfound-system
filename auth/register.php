<?php
require '../includes/db.php'; // This file defines $pdo
$message = "";
$messageClass = "alert-danger"; // Added to fix the undefined variable in your HTML

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $role_map = [1 => 'student', 2 => 'faculty', 3 => 'staff'];
        $role = $role_map[$_POST['account_type']] ?? 'student';

        try {
            // Use $pdo instead of $conn
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
            
            // PDO executes by passing an array to execute() instead of using bind_param
            if ($stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $hashed, $role])) {
                $message = "Success! <a href='login.php' class='alert-link'>Login here</a>";
                $messageClass = "alert-success";
            }
        } catch (PDOException $e) {
            // Error code 23000 usually means a duplicate email entry
            if ($e->getCode() == 23000) {
                $message = "Email already exists.";
            } else {
                $message = "An error occurred. Please try again.";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>

<div class="register-container">
    <div class="register-content">

        <h1 class="brand-title">Search. Report.</h1>
        <h1 class="brand-title highlight">Recover</h1>
        <h2 class="register-subtitle">Create Account</h2>

        <?php if($message): ?>
            <div class="alert <?= $messageClass ?> alert-dismissible fade show text-center">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="row gx-2 mb-3">
                <div class="col-6">
                    <label class="form-label text-white">First Name</label>
                    <input type="text" name="first_name" class="form-control custom-input" required>
                </div>
                <div class="col-6">
                    <label class="form-label text-white">Last Name</label>
                    <input type="text" name="last_name" class="form-control custom-input" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-white">Email Address</label>
                <input type="email" name="email" class="form-control custom-input" required>
            </div>

            <!-- ✅ FIXED DROPDOWN -->
            <div class="mb-3 text-start">
                <label class="form-label text-white">I am a...</label>
                <div class="position-relative">
                    <select name="account_type" class="form-select custom-input appearance-none" required>
                        <option value="" disabled selected>Choose your role</option>
                        <option value="1">Student</option>
                        <option value="2">Faculty</option>
                        <option value="3">Staff</option>
                    </select>
                    <i class="bi bi-chevron-down position-absolute top-50 end-0 translate-middle-y me-3 text-white-50 pe-none"></i>
                </div>
            </div>

            <div class="row gx-2 mb-4 text-start">
                <div class="col-6">
                    <label class="form-label text-white">Password</label>
                    <div class="position-relative">
                        <input type="password" name="password" id="regPassword" class="form-control custom-input pe-5" required>
                        <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-2 text-white-50" 
                        id="togglePasswordIcon" style="cursor: pointer;"></i>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label text-white">Confirm</label>
                    <div class="position-relative">
                        <input type="password" name="confirm_password" id="regConfirm" class="form-control custom-input pe-5" required>
                        <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-2 text-white-50" 
                        id="toggleConfirmIcon" style="cursor: pointer;"></i>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-light w-100 mb-3">Register</button>

            <div class="text-center text-white">
                Already have an account?
                <a href="../auth/login.php" class="fw-bold text-white text-decoration-underline">Login</a>
            </div>

        </form>
    </div>
</div>

<script>
    // Function to handle password toggling
    function setupPasswordToggle(inputSelector, iconSelector) {
        const passwordInput = document.querySelector(inputSelector);
        const toggleIcon = document.querySelector(iconSelector);

        toggleIcon.addEventListener('click', function () {
            // Toggle the type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle the icon class
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');

            // Handle color for desktop view (if applicable)
            if (window.innerWidth >= 992) {
                this.style.color = type === 'text' ? '#094624' : '#666';
            }
        }); 
    }

    // Initialize both toggles
    setupPasswordToggle('#regPassword', '#togglePasswordIcon');
    setupPasswordToggle('#regConfirm', '#toggleConfirmIcon');
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>