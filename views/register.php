<?php
require_once '../includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);   
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (register($username, $email, $password)) {
        header('Location: login.php');
        exit;
    } else {
        $error = 'Registration failed. Username or email may already exist.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
    <div class="register-container">
        <div class="brand-logo"> 
            <h1><?php echo APP_NAME; ?></h1>
        </div>
        <h2 class="text-center mb-4">Create your account</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center"> 
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">
                      Username
                </label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">
                     Email
                </label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">
             Password
                </label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label">
                      Confirm Password
                </label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">
                Register
            </button>
            <div class="text-center">
                <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>