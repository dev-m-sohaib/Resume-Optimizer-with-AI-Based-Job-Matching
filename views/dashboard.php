<?php
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_resumes = getUserResumes($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
<body class="bg-light">
    <?php include('../includes/nav.php') ?>
    
    <div class="container py-4">
        <h1 class="mb-4">
              Dashboard
        </h1>
        
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Your Resumes</h2>
                <a href="resume-form.php" class="btn btn-primary">Create New Resume</a>
            </div>
            
            <?php if (count($user_resumes) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($user_resumes as $resume): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 resume-card active">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h3 class="h5 mb-0"><?php echo htmlspecialchars($resume['title']); ?></h3>
                                </div>
                                <p class="text-muted small mb-2"> 
                                    <strong>Updated:</strong> <?php echo  $resume['updated_at']; ?>
                                </p>
                                <p class="summary-preview mb-3">
                                    <?php echo substr(htmlspecialchars($resume['summary']), 0, 100); ?><?php echo strlen($resume['summary']) > 100 ? '...' : ''; ?>
                                </p>
                                <div class="d-flex gap-2">
                                    <a href="resume-form.php?resume_id=<?php echo $resume['id']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <a href="optimize.php?resume_id=<?php echo $resume['id']; ?>" class="btn btn-sm btn-primary">Optimize</a>
                                </div>
                            </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card empty-state py-4"> 
                    <h3>No Resumes Yet</h3>
                    <p>You haven't created any resumes yet. Get started by creating your first resume!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="recent-optimizations">
            <h2 class="mb-4">Recent Optimizations </h2>
            
            <?php 
            $recent_optimizations = getOptimizations($_SESSION['user_id']);
            if (count($recent_optimizations) > 0): ?>
                <div class="list-group mb-4">
                    <?php foreach ($recent_optimizations as $opt): ?>
                    <div class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted"> 
                                    <?php echo $opt['created_at']; ?>
                                </small>
                                <h4 class="h6 mb-1"><?php echo htmlspecialchars(getResumeById($opt['resume_id'])['title']); ?></h4>
                                <p class="text-muted small mb-0"> 
                                    <?php echo substr(htmlspecialchars($opt['job_description']), 0, 100); ?>...
                                </p>
                            </div>
                            <a href="optimize.php?view=<?php echo $opt['id']; ?>" class="btn btn-sm btn-outline-secondary"> View </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div> 
            <?php else: ?>
                <div class="card empty-state py-4"> 
                    <h3>No Optimizations Yet</h3>
                    <p>You haven't optimized any resumes yet. Try optimizing one to see the results here.</p>

                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>