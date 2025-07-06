<style>
    :root {
        --secondary: #2c3e50;
        --white: #ffffff;
    }

    .navbar {
        background-color: var(--secondary);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand, .nav-link, .navbar-text {
        color: var(--white) !important;
    }

    .navbar-text {
        font-weight: 500;
    } 
</style>

<!-- Responsive Bootstrap Navbar -->
<nav class="navbar navbar-expand-lg p-3">
    <div class="container-fluid">
        <h2 class="navbar-brand"><?php echo APP_NAME; ?></h2>
        <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
            <span class="navbar-text me-3">
                Welcome, <?php echo $_SESSION['username']; ?>
            </span>
            <div class="pt-2">
                <a href="dashboard.php" class="btn btn-secondary me-2">Dashboard</a>
                <a href="optimize.php" class="btn btn-primary me-2">Optimize</a>
                <a href="../includes/auth.php?logout=1" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</nav>
