<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
if ((!isset($_GET['optimize_id']) && !isset($_GET['original_id'])) || !isLoggedIn()) {
    header('Location: login.php');
    exit;
}
if (isset($_GET['optimize_id'])) {
    $id = (int)$_GET['optimize_id'];
    $query = "SELECT o.optimized_summary,o.optimized_experience,o.old_summary,o.old_experience,r.full_name,r.email,r.phone,r.projects,r.education,r.certifications,r.skills FROM optimizations o JOIN resumes r ON o.resume_id = r.id WHERE o.id = ? AND r.user_id = ?";
} elseif (isset($_GET['original_id'])) {
    $id = (int) $_GET['original_id'];
    $query = "SELECT r.full_name,r.email,r.phone,r.summary AS old_summary,r.experience AS old_experience,r.skills,r.projects,r.education,r.certifications FROM resumes r WHERE r.id = ? AND r.user_id = ?";
}
$type = $_GET['type'] ?? 'optimized';
$stmt = $pdo->prepare($query);
$stmt->execute([$id, $_SESSION['user_id']]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    die("No data found or permission denied.");
}
function formatExperience($experience_json)
{
    $experiences = json_decode($experience_json ?? '[]', true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($experiences)) {
        return ['No experience data available'];
    }
    $result = [];
    foreach ($experiences as $index => $exp) {
        if (!empty($exp['company'])) {
            $description = str_replace("\n", "; ", $exp['description'] ?? '');
            $result[] = sprintf("Company: %s, Position: %s, Dates: %s - %s, Description: %s", $exp['company'] ?? 'N/A', $exp['position'] ?? 'N/A', $exp['start_date'] ?? 'N/A', $exp['current'] ? 'Present' : ($exp['end_date'] ?? 'N/A'), $description ?: 'N/A');
        }
    }
    return $result ?: ['No experience data available'];
}
function formatEducation($education_json)
{
    $education = json_decode($education_json ?? '[]', true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($education)) {
        return ['No education data available'];
    }
    $result = [];
    foreach ($education as $index => $edu) {
        $result[] = sprintf(
            "Institution: %s, Degree: %s, Field: %s, Dates: %s - %s",
            $edu['institution'] ?? 'N/A',
            $edu['degree'] ?? 'N/A',
            $edu['field'] ?? 'N/A',
            $edu['start_date'] ?? 'N/A',
            $edu['end_date'] ?? 'N/A'
        );
    }
    return $result ?: ['No education data available'];
}
$content = [];
$content[] = ['Full Name', $data['full_name'] ?? 'N/A'];
$content[] = ['Email', $data['email'] ?? 'N/A'];
$content[] = ['Phone', $data['phone'] ?? 'N/A'];
$content[] = ['Summary', $type === 'original' ? ($data['old_summary'] ?? 'N/A') : ($data['optimized_summary'] ?? 'N/A')];
$experience_data = $type === 'original' ? $data['old_experience'] : $data['optimized_experience'];
$experiences = formatExperience($experience_data);
foreach ($experiences as $index => $exp) {
    $content[] = ["Experience " . ($index + 1), $exp];
}
$education_data = $data['education'];
$education_entries = formatEducation($education_data);
foreach ($education_entries as $index => $edu) {
    $content[] = ["Education " . ($index + 1), $edu];
}
$skills_data = $data['skills'] ?? '[]';
$content[] = ["Skills", $skills_data];
$projects_data = $data['projects'] ?? '[]';
$content[] = ["Projects", $projects_data];
$certifications_data = $data['certifications'] ?? '[]';
$content[] = ["Certifications", $certifications_data];
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="resume_export.csv"');
$output = fopen('php://output', 'w');
foreach ($content as $row) {
    fputcsv($output, $row);
}
fclose($output);
exit;
