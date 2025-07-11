<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_resumes = getUserResumes($_SESSION['user_id']);
$resume_id = isset($_GET['resume_id']) ? (int)$_GET['resume_id'] : 0;
$resume = $resume_id ? getResumeById($resume_id) : null;

if ($resume && $resume['user_id'] != $_SESSION['user_id']) {
    $resume = null;
    $resume_id = 0;
}

$view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$view_optimization = null;

if ($view_id) {
    $stmt = $pdo->prepare("SELECT o.id AS optimization_id, o.resume_id, o.job_description, o.optimized_summary, o.optimized_experience, o.old_summary, o.old_experience, o.original_score, o.optimized_score, r.title FROM optimizations o JOIN resumes r ON o.resume_id = r.id WHERE o.id = ? AND r.user_id = ?");
    $stmt->execute([$view_id, $_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    if ($result) {
        $view_optimization = [
            'id' => $result['optimization_id'],
            'resume_id' => $result['resume_id'],
            'title' => $result['title'],
            'job_description' => $result['job_description'],
            'optimized_summary' => $result['optimized_summary'],
            'optimized_experience' => json_decode($result['optimized_experience'], true),
            'old_summary' => $result['old_summary'],
            'old_experience' => json_decode($result['old_experience'], true),
            'original_score' => $result['original_score'],
            'optimized_score' => $result['optimized_score']
        ];
    }
}

$delete_id = isset($_POST['delete_id']) ? (int)$_POST['delete_id'] : 0;
if ($delete_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (deleteOptimization($delete_id, $_SESSION['user_id'])) {
        $_SESSION['success'] = "Optimization deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete optimization.";
    }
    header("Location: " . ($resume_id ? "optimize.php?resume_id=$resume_id" : "optimize.php"));
    exit;
}

function formatExperience($experiences) {
    $output = '';
    if (!is_array($experiences)) {
        return 'No experience data available';
    }
    foreach ($experiences as $exp) {
        if (!empty($exp['company'])) {
            $output .= '<div class="experience-entry">';
            $output .= '<h5>' . htmlspecialchars($exp['company']) . '</h5>';
            $output .= '<p><strong>' . htmlspecialchars($exp['position']) . '</strong></p>';
            $output .= '<p><em>' . htmlspecialchars($exp['start_date']) . ' - ' . ($exp['current'] ? 'Present' : ($exp['end_date'] ?? '')) . '</em></p>';
            if (!empty($exp['description'])) {
                $lines = array_filter(array_map('trim', explode("\n", $exp['description'])));
                $output .= '<ul>';
                foreach ($lines as $line) {
                    $output .= '<li>' . htmlspecialchars($line) . '</li>';
                }
                $output .= '</ul>';
            }
            $output .= '</div>';
        }
    }
    return $output ?: 'No experience data available';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Optimize Resume - <?=APP_NAME?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include('../includes/nav.php') ?>
<div class="container py-4">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger d-flex align-items-center">
            <?=$_SESSION['error']; unset($_SESSION['error'])?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success d-flex align-items-center">
            <?=$_SESSION['success']; unset($_SESSION['success'])?>
        </div>
    <?php endif; ?>

    <?php if (!$resume_id && !$view_id): ?>
        <div class="resume-selection mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Select a Resume to Optimize</h2>
                <a href="resume-form.php" class="btn btn-primary">Create New Resume</a>
            </div>
            <?php if (count($user_resumes) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($user_resumes as $resume): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 resume-card">
                                <div class="card-body">
                                    <h3 class="h5"><?=htmlspecialchars($resume['title'])?></h3>
                                    <p class="text-muted small mb-2">Last updated: <?=$resume['updated_at']?></p>
                                    <div class="d-grid">
                                        <a href="optimize.php?resume_id=<?=$resume['id']?>" class="btn btn-primary">Optimize</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card text-center py-4 empty-state">
                    <h3>No Resumes Found</h3>
                    <p class="mb-4">You haven't created any resumes yet. Get started by creating your first resume!</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($resume && !$view_id): ?>
        <div class="optimize-form">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Selected: <?=htmlspecialchars($resume['title'])?></h2>
                <a href="optimize.php" class="btn btn-secondary">Back to All Resumes</a>
            </div>
            
            <form id="optimizeForm" class="card mb-4">
                <div class="card-body">
                    <input type="hidden" name="resume_id" value="<?=$resume['id']?>">
                    <div class="mb-3">
                        <label for="job_description" class="form-label fw-bold">Paste Job Description</label>
                        <textarea id="job_description" name="job_description" rows="8" class="form-control" required placeholder="Copy and paste the job description you're applying for..."><?php if(isset($_GET['job_description'])) { echo trim($_GET['job_description']); } ?></textarea>
                        <small class="text-muted">The more detailed the job description, the better we can optimize your resume</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="optimizeBtn">Improve Resume</button>
                        <a href="optimize.php" class="btn btn-danger">Cancel</a>
                    </div>
                </div>
            </form>
            
            <div id="loading" style="display: none;" class="card text-center py-4">
                <div class="card-body">
                    <h3 class="h4">Optimizing Your Resume</h3>
                    <p class="mb-3">We're analyzing your resume and the job description to provide the best optimizations...</p>
                    <div class="loader"></div>
                </div>
            </div>

            <div id="results" style="display: none;">
                <h2 class="h3 mb-4">Optimization Results</h2>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card score-card bg-light">
                            <div class="score-label">Original Match Score</div>
                            <div class="score-value" id="originalScore">0</div>
                            <div class="progress">
                                <div id="scoreProgressOriginal" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card score-card bg-light">
                            <div class="score-label">Optimized Match Score <span id="scoreDiff" class="score-diff"></span></div>
                            <div class="score-value" id="optimizedScore">0</div>
                            <div class="progress">
                                <div id="scoreProgressOptimized" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6 original">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="h5">Original Content</h3>
                                <div class="mb-4">
                                    <h4 class="h6 text-primary">Summary</h4>
                                    <p id="originalSummary" class="mb-0"><?=nl2br(htmlspecialchars($resume['summary']))?></p>
                                </div>
                                <div class="mb-4">
                                    <h4 class="h6 text-primary">Experience</h4>
                                    <div id="originalExperience"><?=formatExperience(json_decode($resume['experience'] ?? '[]', true))?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 optimized">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="h5">Optimized Content</h3>
                                <div class="mb-4">
                                    <h4 class="h6 text-primary">Summary</h4>
                                    <p id="optimizedSummary" class="mb-0"></p>
                                </div>
                                <div class="mb-4">
                                    <h4 class="h6 text-primary">Experience</h4>
                                    <div id="optimizedExperience"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <form id="saveOptimizationForm" method="POST" class="card">
                        <div class="card-body">
                            <h3 class="h5">Save Optimization</h3>
                            <p class="mb-4">Would you like to save these optimizations to your resume?</p>
                            <input type="hidden" name="resume_id" value="<?=$resume['id']?>">
                            <input type="hidden" name="optimized_summary" id="optimized_summary_input">
                            <input type="hidden" name="optimized_experience" id="optimized_experience_input">
                            <input type="hidden" name="original_score" id="original_score_input">
                            <input type="hidden" name="optimized_score" id="optimized_score_input">
                            <textarea name="job_description" style="display:none;"></textarea>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">Save Changes</button>
                                <button type="button" id="optimizeAgainBtn" class="btn btn-primary">Improve Again</button>
                                <a href="optimize.php" class="btn btn-danger">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($view_optimization): ?>
        <div class="optimization-result">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Optimization Results for: <?=htmlspecialchars($view_optimization['title'])?></h2>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card score-card bg-light">
                        <div class="score-label">Original Match Score</div>
                        <div class="score-value"><?=round($view_optimization['original_score'], 1)?></div>
                        <div class="progress">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?=$view_optimization['original_score'] * 10?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card score-card bg-light">
                        <?php 
                            $scoreDiff = $view_optimization['optimized_score'] - $view_optimization['original_score'];
                            $diffClass = $scoreDiff >= 0 ? 'positive' : 'negative';
                        ?>
                        <div class="score-label">Optimized Match Score <span class="score-diff <?=$diffClass?>"><?=($scoreDiff >= 0 ? '+' : '') . round($scoreDiff, 1)?></span></div>
                        <div class="score-value"><?=round($view_optimization['optimized_score'], 1)?></div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?=$view_optimization['optimized_score'] * 10?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="h5">Job Description</h3>
                    <p class="mb-0"><?=nl2br(htmlspecialchars($view_optimization['job_description']))?></p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 original">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h5 mb-0">Original Content</h3>
                                <a href="print_template.php?optimize_id=<?=$view_optimization['id']?>&type=original" target="_blank" class="btn btn-warning text-white">Print/Export</a>
                            </div>
                            <div class="mb-4">
                                <h4 class="h6 text-primary">Summary</h4>
                                <p class="mb-0"><?=nl2br(htmlspecialchars($view_optimization['old_summary']))?></p>
                            </div>
                            <div class="mb-4">
                                <h4 class="h6 text-primary">Experience</h4>
                                <div><?=formatExperience($view_optimization['old_experience'])?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 optimized">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h5 mb-0">Optimized Content</h3>
                                <a href="print_template.php?optimize_id=<?=$view_optimization['id']?>&type=optimized" target="_blank" class="btn btn-warning text-white">Print/Export</a>
                            </div>
                            <div class="mb-4">
                                <h4 class="h6 text-primary">Summary</h4>
                                <p class="mb-0"><?=nl2br(htmlspecialchars($view_optimization['optimized_summary']))?></p>
                            </div>
                            <div class="mb-4">
                                <h4 class="h6 text-primary">Experience</h4>
                                <div><?=formatExperience($view_optimization['optimized_experience'])?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-4">
                <form method="post" id="delete-id-form" class="mb-0">
                    <input type="hidden" name="delete_id" value="<?=$view_optimization['id']?>">
                    <button type="submit" class="btn btn-danger">Delete Optimization</button>
                </form>
                <div class="d-flex gap-2">
                    <a href="optimize.php?resume_id=<?=$view_optimization['resume_id']?>&job_description=<?=urlencode($view_optimization['job_description'])?>" class="btn btn-primary">Improve Again</a>
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        </div>
    <?php elseif ($view_id && !$view_optimization): ?>
        <div class="alert alert-danger d-flex align-items-center">
            Optimization not found or you don't have permission to view it.
            <a href="optimize.php" class="btn btn-secondary ms-3">Back to Optimizations</a>
        </div>
    <?php endif; ?>
</div>
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>