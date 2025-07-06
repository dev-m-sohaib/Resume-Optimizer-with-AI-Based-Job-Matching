<?php
session_start();
require_once 'db.php';
require_once 'functions.php';
 function isLoggedIn() {
    if(isset($_SESSION['user_id'])){
        return $_SESSION['user_id'];

    }else{

        return false; 
    }
}
function login($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    return false;
}
 function register($username, $email, $password) {
    global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return false;
    }
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    return $stmt->execute([$username, $email, $hashedPassword]);
}
function logout() {
    session_unset();
    session_destroy();
    header('Location: ../views/login.php');
}

if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    logout();
}