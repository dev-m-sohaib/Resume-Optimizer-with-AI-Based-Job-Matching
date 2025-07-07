<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
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
    $query = "SELECT r.summary,r.experience,r.skills,r.full_name,r.email,r.phone,r.projects,r.education,r.certifications FROM resumes r WHERE r.id = ? AND r.user_id = ?";
}
$type = $_GET['type'] ?? 'optimized';
$stmt = $pdo->prepare($query);
$stmt->execute([$id, $_SESSION['user_id']]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    die("Optimization not found or you don't have permission to view it.");
}
$content = [];
$content['education'] = json_decode($data['education'] ?? '[]', true);
$content['projects'] = $data['projects'];
$content['certifications'] = $data['certifications'];
$content['skills'] = $data['skills'];

if ($type === 'original_resume') {
    $content['summary'] = $data['summary'] ?? '';
    $content['experience'] = json_decode($data['experience'] ?? '[]', true);
} else {
    $content['summary'] = $type === 'original' ? ($data['old_summary'] ?? '') : ($data['optimized_summary'] ?? '');
    $content['experience'] = json_decode(($type === 'original' ? ($data['old_experience'] ?? '[]') : ($data['optimized_experience'] ?? '[]')), true);
}
if (json_last_error() !== JSON_ERROR_NONE) {
    $content['education'] = [];
    $content['experience'] = [];
    $content['skills'] = [];
    $content['projects'] = [];
    $content['certifications'] = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($data['full_name']); ?> - Resume</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/cv-template.css">
</head>
<body>
    <div class="resume-container">
        <?php if ($type === 'original'): ?>
            <div class="version-badge no-print">Original Version</div>
        <?php else: ?>
            <div class="version-badge no-print">Optimized Version</div>
        <?php endif; ?>
        <div class="resume-header">
            <h1><?php echo htmlspecialchars($data['full_name']); ?></h1>
            <div class="contact-info">
                <?php echo htmlspecialchars($data['email']); ?> |
                <?php echo htmlspecialchars($data['phone']); ?>
            </div>
        </div>
        <?php if (!empty($content['summary'])): ?>
            <div class="section">
                <div class="section-title">Professional Summary</div>
                <div class="subsection-content"><?php echo nl2br(htmlspecialchars($content['summary'])); ?></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($content['education'])): ?>
            <div class="section">
                <div class="section-title">Education</div>
                <?php foreach ($content['education'] as $edu):
                    if (!empty($edu['institution'])):
                        $date = $edu['start_date'] . ' - ' . ($edu['current'] ? 'Present' : ($edu['end_date'] ?? ''));
                        $description_lines = array_filter(array_map('trim', explode("\n", $edu['description'] ?? '')));
                ?>
                        <div class="subsection">
                            <div class="subsection-header">
                                <span class="subsection-title"><?php echo htmlspecialchars($edu['institution']); ?></span>
                                <span class="subsection-details"><?php echo htmlspecialchars($date); ?></span>
                            </div>
                            <div class="subsection-content">
                                <?php echo htmlspecialchars($edu['degree']); ?>
                                <?php if (!empty($edu['field_of_study'])): ?>
                                    , <?php echo htmlspecialchars($edu['field_of_study']); ?>
                                <?php endif; ?>
                                <?php if (!empty($description_lines)): ?>
                                    <ul>
                                        <?php foreach ($description_lines as $line): ?>
                                            <li><?php echo htmlspecialchars($line); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php endif;
                endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($content['experience'])): ?>
            <div class="section">
                <div class="section-title">Experience</div>
                <?php foreach ($content['experience'] as $exp):
                    if (!empty($exp['company'])):
                        $date = $exp['start_date'] . ' - ' . ($exp['current'] ? 'Present' : ($exp['end_date'] ?? ''));
                        $description_lines = array_filter(array_map('trim', explode("\n", $exp['description'] ?? '')));
                ?>
                        <div class="subsection">
                            <div class="subsection-header">
                                <span class="subsection-title"><?php echo htmlspecialchars($exp['company']); ?></span>
                                <span class="subsection-details"><?php echo htmlspecialchars($date); ?></span>
                            </div>
                            <div class="subsection-content">
                                <?php echo htmlspecialchars($exp['position']); ?>
                                <?php if (!empty($description_lines)): ?>
                                    <ul>
                                        <?php foreach ($description_lines as $line): ?>
                                            <li><?php echo htmlspecialchars($line); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php endif;
                endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($content['skills'])): ?>
            <div class="section">
                <div class="section-title">Skills</div>
                <div class="subsection-content">
                     <?php echo $content['skills']; ?>  
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($content['projects'])): ?>
            <div class="section">
                <div class="section-title">Projects</div>
                <div class="subsection-content">
                <?php echo $content['projects']; ?> 
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($content['certifications'])): ?>
            <div class="section">
                <div class="section-title">Certifications</div>
                <div class="subsection-content">
                <?php echo $content['certifications']; ?> 
                </div>
            </div>
        <?php endif; ?>
        <div class="no-print action-buttons">
            <button onclick="window.print()" class="btn btn-primary">Print Resume</button>
            <a href="dashboard.php" class="btn btn-cancel">Back to Dashboard</a>
            <a href="download_csv.php?<?php echo isset($_GET['optimize_id']) ? 'optimize_id=' . $id : 'original_id=' . $id; ?>&type=<?php echo $type; ?>" class="btn btn-secondary">Download CSV</a>
        </div>
    </div>
    <script>
        if (window.location.search.includes('autoprint')) {
            window.print();
        }
    </script>
</body>

</html>