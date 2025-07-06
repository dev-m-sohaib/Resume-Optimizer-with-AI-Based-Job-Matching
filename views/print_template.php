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
$query = "SELECT r.summary,r.experience,r.skills,r.summary,r.experience,r.skills,r.full_name,r.email,r.phone,r.projects,r.education,r.certifications FROM resumes r WHERE r.id = ? AND r.user_id = ?";
}
$type = $_GET['type'] ?? 'optimized'; 

$stmt = $pdo->prepare($query);
$stmt->execute([$id, $_SESSION['user_id']]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Optimization not found or you don't have permission to view it.");
}
$content = [
    'education' => $data['education'],
    'projects' => $data['projects'],
    'certifications' => $data['certifications']
];
if($type === 'original_resume'){
    $content['summary'] = $data['summary'];
    $content['experience'] = $data['experience'];
    $content['skills'] = $data['skills'];
}else{
    $content['summary'] = $type === 'original' ? $data['old_summary'] : $data['optimized_summary'];
    $content['experience'] = $type === 'original' ? $data['old_experience'] : $data['optimized_experience'];
    $content['skills'] = $type === 'original' ? $data['old_skills'] : $data['optimized_skills'];
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
                <?php 
                    $educationEntries = explode("\n\n", trim($content['education']));
                    foreach ($educationEntries as $entry):
                        $lines = array_filter(array_map('trim', explode("\n", $entry)));
                        if (!empty($lines)):
                ?>
                    <div class="subsection">
                        <div class="subsection-header">
                            <span class="subsection-title"><?php echo htmlspecialchars($lines[0]); ?></span>
                            <?php if (!empty($lines[1])): ?>
                                <span class="subsection-details"><?php echo htmlspecialchars($lines[1]); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (count($lines) > 2): ?>
                            <div class="subsection-content"><?php echo nl2br(htmlspecialchars(implode("\n", array_slice($lines, 2)))); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($content['experience'])): ?>
            <div class="section">
                <div class="section-title">Experience</div>
                <?php 
                    $experienceEntries = explode("\n\n", trim($content['experience']));
                    foreach ($experienceEntries as $entry):
                        $lines = array_filter(array_map('trim', explode("\n", $entry)));
                        if (!empty($lines)):
                ?>
                    <div class="subsection">
                        <div class="subsection-header">
                            <span class="subsection-title"><?php echo htmlspecialchars($lines[0]); ?></span>
                            <?php if (!empty($lines[1])): ?>
                                <span class="subsection-details"><?php echo htmlspecialchars($lines[1]); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (count($lines) > 2): ?>
                            <div class="subsection-content"><?php echo nl2br(htmlspecialchars(implode("\n", array_slice($lines, 2)))); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($content['skills'])): ?>
            <div class="section">
                <div class="section-title">Skills</div>
                <div class="subsection-content"><?php echo nl2br(htmlspecialchars($content['skills'])); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($content['projects'])): ?>
            <div class="section">
                <div class="section-title">Projects</div>
                <?php 
                    $projectEntries = explode("\n\n", trim($content['projects']));
                    foreach ($projectEntries as $entry):
                        $lines = array_filter(array_map('trim', explode("\n", $entry)));
                        if (!empty($lines)):
                ?>
                    <div class="subsection">
                        <div class="subsection-header">
                            <span class="subsection-title"><?php echo htmlspecialchars($lines[0]); ?></span>
                            <?php if (!empty($lines[1])): ?>
                                <span class="subsection-details"><?php echo htmlspecialchars($lines[1]); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (count($lines) > 2): ?>
                            <div class="subsection-content"><?php echo nl2br(htmlspecialchars(implode("\n", array_slice($lines, 2)))); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($content['certifications'])): ?>
            <div class="section">
                <div class="section-title">Certifications</div>
                <div class="subsection-content"><?php echo nl2br(htmlspecialchars($content['certifications'])); ?></div>
            </div>
        <?php endif; ?>

        <div class="no-print action-buttons">
            <button onclick="window.print()" class="btn btn-primary">Print Resume</button>
            <a href="dashboard.php" class="btn btn-cancel">Back to Dashboard</a>
            <a href="download_csv.php?
            <?php if(isset($_GET['optimize_id'])): ?>
            optimize_id=<?php echo $id; ?>
            <?php elseif(isset($_GET['original_id'])): ?>
            original_id=<?php echo $id; ?>
            <?php endif; ?>
            &type=<?php echo $type; ?>" class="btn btn-secondary">Download CSV</a>
        </div>
    </div> 
    <script>
        if (window.location.search.includes('autoprint')) {
            window.print();
        }
    </script>
</body>
</html>