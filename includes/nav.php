    <style>
        :root {
    --primary: #3498db;
    --primary-dark: #2980b9;
    --secondary: #2c3e50;
    --success: #27ae60;
    --danger: #e74c3c;
    --warning: #f39c12;
    --light: #ecf0f1;
    --dark: #2c3e50;
    --gray: #95a5a6;
    --white: #ffffff;
    --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
} 
header {
    background-color: var(--secondary);
    color: var(--white); 
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
}

nav h2 {
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--white);
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}
.nav-links span {
    font-weight: 500;
    color: var(--light);
} 
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 1.2rem;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    font-size: 1rem; 
}
.btn-primary {
    background-color: var(--primary);
    color: var(--white);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: var(--light);
    color: var(--dark);
}
.btn-secondary:hover {
    background-color: #d5dbdb;
    transform: translateY(-2px);
}
.btn-success {
    background-color: var(--success);
    color: var(--white);
}

.btn-success:hover {
    background-color: #219653;
    transform: translateY(-2px);
}

.btn-danger {
    background-color: var(--danger);
    color: var(--white);
}
.btn-danger:hover {
    background-color: #c0392b;
    transform: translateY(-2px);
}
</style>
<header>
    <nav>
        <h2>  <?php echo APP_NAME; ?></h2>
        <div class="nav-links">
            <span> Welcome, <?php echo $_SESSION['username']; ?></span>
            <a href="dashboard.php" class="btn btn-secondary"> Dashboard</a>
            <a href="optimize.php" class="btn btn-primary"> Optimize</a>
            <a href="../includes/auth.php?logout=1" class="btn btn-danger"> Logout</a>
        </div>
    </nav>
</header>