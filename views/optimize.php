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
    $stmt = $pdo->prepare("SELECT o.id AS optimization_id,o.resume_id,o.job_description,o.optimized_summary,o.optimized_experience, o.resume_id,r.title,o.old_summary,o.old_experience FROM optimizations o JOIN resumes r ON o.resume_id = r.id WHERE o.id = ? AND r.user_id = ?");
$stmt->execute([$view_id, $_SESSION['user_id']]);
$result = $stmt->fetch();
if ($result) {
    $view_optimization = [
        'id' => $result['optimization_id'],
        'resume_id' => $result['resume_id'],
        'title' => $result['title'],
        'job_description' => $result['job_description'],
        'optimized_summary' => $result['optimized_summary'],
        'optimized_experience' => $result['optimized_experience'],
        'old_summary' => $result['old_summary'],
        'old_experience' => $result['old_experience']
    ]; 
}}

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
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Optimize Resume - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include('../includes/nav.php') ?>
<div class="container py-4"> 
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger d-flex align-items-center"> 
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success d-flex align-items-center"> 
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
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
                                        <h3 class="h5"><?php echo htmlspecialchars($resume['title']); ?></h3>
                                        <p class="text-muted small mb-2"> 
                                            Last updated: <?php echo $resume['updated_at']; ?>
                                        </p>
                                        <div class="d-grid">
                                            <a href="optimize.php?resume_id=<?php echo $resume['id']; ?>" class="btn btn-primary">Optimize</a>
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
                    <h2 class="mb-0">
                         Selected: <?php echo htmlspecialchars($resume['title']); ?>
                    </h2>
                    <?php if (isset($_GET['view']) || isset($_GET['resume_id']) || isset($_GET['optimize_id'])): ?>
                    <a href="optimize.php" class="btn btn-secondary">
                          Back to All Resumes</a>
                    <?php endif ?>
                </div>
                
                <form id="optimizeForm" class="card mb-4">
                    <div class="card-body">
                        <input type="hidden" name="resume_id" value="<?php echo $resume['id']; ?>">
                        <div class="mb-3">
                            <label for="job_description" class="form-label fw-bold">Paste Job Description </label>
                            <textarea id="job_description" name="job_description" rows="8" class="form-control" 
                                      required placeholder="Copy and paste the job description you're applying for..."></textarea>
                            <small class="text-muted">
                                  The more detailed the job description, the better we can optimize your resume
                            </small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="optimizeBtn">Improve Resume</button>
                            <a href="optimize.php" class="btn btn-danger">Cancel</a>
                        </div>
                    </div>
                </form>
                
                <div id="loading" style="display: none;" class="card text-center py-4">
                    <div class="card-body">
                        <h3 class="h4">  Optimizing Your Resume</h3>
                        <p class="mb-3">We're analyzing your resume and the job description to provide the best optimizations...</p>
                        <div class="loader"></div>
                    </div>
                </div>

                <div id="results" style="display: none;">
                    <h2 class="h3 mb-4">Optimization Results</h2>
                    <div class="row g-4">
                        <div class="col-md-6 original">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h3 class="h5">Original Content</h3>
                                    <div class="mb-4">
                                        <h4 class="h6 text-primary">Summary</h4>
                                        <p id="originalSummary" class="mb-0"><?php echo nl2br(htmlspecialchars($resume['summary'])); ?></p>
                                    </div>
                                    <div class="mb-4">
                                        <h4 class="h6 text-primary">Experience</h4>
                                        <p id="originalExperience" class="mb-0"><?php echo nl2br(htmlspecialchars($resume['experience'])); ?></p>
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
                                        <p id="optimizedExperience" class="mb-0"></p>
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
                                <input type="hidden" name="resume_id" value="<?php echo $resume['id']; ?>">
                                <input type="hidden" name="optimized_summary" value="">
                                <input type="hidden" name="optimized_experience" value=""> 
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
                    <h2 class="mb-0">Optimization Results for: <?php echo htmlspecialchars($view_optimization['title']); ?></h2>
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard
                 </a>
                </div>
                 
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="h5">Job Description</h3>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($view_optimization['job_description'])); ?></p>
                    </div>
                </div>
              <div class="row g-4">
                    <div class="col-md-6 original">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h3 class="h5 mb-0">
                                          Original Content
                                    </h3>
                                    <a href="print_template.php?optimize_id=<?php echo $view_optimization['id']; ?>&type=original" 
                                       target="_blank" class="btn btn-warning text-white">
                                      Print/Export
                                    </a>
                                </div>
                                <div class="mb-4">
                                    <h4 class="h6 text-primary">Summary</h4>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($view_optimization['old_summary'])); ?></p>
                                </div>
                                <div class="mb-4">
                                    <h4 class="h6 text-primary">Experience</h4>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($view_optimization['old_experience'])); ?></p>
                                </div> 
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 optimized">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h3 class="h5 mb-0"> Optimized Content</h3>
                                    <a href="print_template.php?optimize_id=<?php echo $view_optimization['id']; ?>&type=optimized" 
                                       target="_blank" class="btn btn-warning text-white">
                                          Print/Export
                                    </a>
                                </div>
                                <div class="mb-4">
                                    <h4 class="h6 text-primary">Summary</h4>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($view_optimization['optimized_summary'])); ?></p>
                                </div>
                                <div class="mb-4">
                                    <h4 class="h6 text-primary">Experience</h4>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($view_optimization['optimized_experience'])); ?></p>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <form method="post" id="delete-id-form" class="mb-0">
                        <input type="hidden" name="delete_id" value="<?php echo $view_optimization['id']; ?>">
                        <button type="submit" class="btn btn-danger">
                              Delete Optimization
                        </button>
                    </form>
                    <div class="d-flex gap-2">
                        <a href="optimize.php?resume_id=<?php echo $view_optimization['resume_id']; ?>" class="btn btn-primary">Optimize Again</a>
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

     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    
</body>
</html>