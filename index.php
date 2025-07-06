<?php
require_once 'includes/auth.php';
if (isLoggedIn()) {
    header('Location: views/dashboard.php');
} else {
    header('Location: views/login.php');
}
exit;