<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if ((!isset($_GET['optimize_id']) && !isset($_GET['original_id'])) || !isLoggedIn()) {
    header('Location: login.php');
    exit;
}
if(isset($_GET['optimize_id'])){
    $id = (int)$_GET['optimize_id'];
    $query = "SELECT o.optimized_summary,o.optimized_experience,o.optimized_skills,o.old_summary,o.old_experience,o.old_skills,r.full_name,r.email,r.phone,r.projects,r.education,r.certifications FROM optimizations o JOIN resumes r ON o.resume_id = r.id WHERE o.id = ? AND r.user_id = ?";
    }elseif(isset($_GET['original_id'])){
    $id = (int)$_GET['original_id'];
    $query = "SELECT r.* FROM resumes r WHERE r.id = ? AND r.user_id = ?";
    }
    $type = $_GET['type'] ?? 'optimized'; 
     
$stmt = $pdo->prepare($query);
$stmt->execute([$id, $_SESSION['user_id']]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    die("No data found or permission denied.");
}

$content = [
    'Full Name' => $data['full_name'],
    'Email' => $data['email'],
    'Phone' => $data['phone'],
    'Summary' => $type === 'original' ? $data['old_summary'] : $data['optimized_summary'],
    'Education' => $data['education'],
    'Experience' => $type === 'original' ? $data['old_experience'] : $data['optimized_experience'],
    'Skills' => $type === 'original' ? $data['old_skills'] : $data['optimized_skills'],
    'Projects' => $data['projects'],
    'Certifications' => $data['certifications']
];
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="resume_export.csv"');

$output = fopen('php://output', 'w');
foreach ($content as $label => $value) {
    fputcsv($output, [$label, $value]);
}
fclose($output);
exit;
