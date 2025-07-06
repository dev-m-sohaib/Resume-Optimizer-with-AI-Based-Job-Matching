<?php
require_once '../includes/auth.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$resume_id = isset($_GET['resume_id']) ? (int)$_GET['resume_id'] : 0;
$resume = $resume_id ? getResumeById($resume_id) : null;
if ($resume && $resume['user_id'] != $_SESSION['user_id']) {
    header('Location: resume-form.php');
    exit;
}

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => sanitizeInput($_POST['full_name']),
        'email' => sanitizeInput($_POST['email']),
        'phone' => sanitizeInput($_POST['phone']),
        'summary' => sanitizeInput($_POST['summary']),
        'education' => sanitizeInput($_POST['education']),
        'experience' => sanitizeInput($_POST['experience']),
        'skills' => sanitizeInput($_POST['skills']),
        'projects' => sanitizeInput($_POST['projects']),
        'certifications' => sanitizeInput($_POST['certifications'])
    ];
    
    if ($resume_id && isset($_POST['update_resume'])) {
        if (updateResume($resume_id, $data)) {
            $_SESSION['success'] = 'Resume updated successfully!';
            $resume = getResumeById($resume_id); 
        } else {
            $_SESSION['error'] = 'Error updating resume. Please try again.';
        }
    } elseif(isset($_POST['create_resume'])) {
        $title = sanitizeInput($_POST['title']);
        $new_id = createNewResume($_SESSION['user_id'], $title, $data);
        if ($new_id) {
            $_SESSION['success'] = 'Resume created successfully!';
            header("Location: resume-form.php?resume_id=$new_id");
            exit;
        } else {
            $_SESSION['error'] = 'Error creating resume. Please try again.';
        }
    }elseif(isset($_POST['delete_id'])){

        $delete_id = isset($_POST['delete_id']) ? (int)$_POST['delete_id'] : 0;
             if (deleteResume($delete_id, $_SESSION['user_id'])) {
                $_SESSION['success'] = "Resume deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete Resume.";
            }
            header("Location: " . ($resume_id ? "resume-form.php?resume_id=$resume_id" : "optimize.php"));
            exit;
     }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $resume ? 'Edit Resume' : 'Create Resume'; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/resume-form.css">
</head>
<body class="bg-light">
<?php include('../includes/nav.php') ?>
    <div class="container py-4">
        <h1 class="mb-4"> 
            <?php echo $resume ? 'Edit Resume: ' . htmlspecialchars($resume['title']) : 'Create New Resume'; ?>
        </h1>
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
        <form method="POST" id="resumeForm" class="bg-white p-4 rounded shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <?php if (!$resume): ?>
                    <div class="w-100">
                        <label for="title" class="form-label fw-bold">
                             Resume Title
                        </label>
                        <input type="text" id="title" name="title" class="form-control"  
                               placeholder="e.g. Software Engineer Resume"> 
                    </div>
                <?php else: ?>
                    <h2 class="mb-0">
                         <?php echo htmlspecialchars($resume['title']); ?>
                    </h2>
                    <a href="print_template.php?original_id=<?php echo $resume['id']; ?>&type=original_resume" 
                       target="_blank" class="btn btn-warning text-white">
                        Print/Export
                    </a>
                <?php endif; ?>
            </div>
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">
                Personal Information
                </h3> 
            </div>
            <div class="section-content py-3" id="personalInfo">
                <div class="mb-3">
                    <label for="full_name" class="form-label fw-bold">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           value="<?php echo htmlspecialchars($resume['full_name'] ?? ''); ?>" >
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-bold">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?php echo htmlspecialchars($resume['email'] ?? ''); ?>" >
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label fw-bold">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                           value="<?php echo htmlspecialchars($resume['phone'] ?? ''); ?>">
                </div>
            </div>
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">  Professional Summary</h3> 
            </div>
            <div class="section-content py-3" id="summarySection">
                <div class="alert alert-info mb-3 form-tip"> 
                    Write an overview of your professional background.
                </div>
                <div class="mb-3">
                    <textarea id="summary" name="summary" rows="4" class="form-control"><?php echo htmlspecialchars($resume['summary'] ?? ''); ?></textarea>
                 </div>
            </div>
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">  Education
                </h3> 
            </div>
            <div class="section-content py-3" id="educationSection">
                <div class="alert alert-info mb-3 form-tip"> 
                    List your degrees.
                </div>

                <div class="mb-3">
                    <textarea id="education" name="education" rows="4" class="form-control"><?php echo htmlspecialchars($resume['education'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0"> Work Experience
                </h3> 
            </div>
            <div class="section-content py-3" id="experienceSection">
                <div class="alert alert-info mb-3 form-tip"> 
                    List your work history. Include company names, job titles and dates.
                </div>

                <div class="mb-3">
                    <textarea id="experience" name="experience" rows="6" class="form-control"><?php echo htmlspecialchars($resume['experience'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">  Skills
                </h3> 
            </div>
            <div class="section-content py-3" id="skillsSection">
                <div class="alert alert-info mb-3 form-tip"> 
                    List your most relevant skills.
                </div>
                <div class="mb-3">
                    <textarea id="skills" name="skills" rows="4" class="form-control" 
                              placeholder="e.g., JavaScript, Python, Project Management, Team Leadership"><?php echo htmlspecialchars($resume['skills'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0"> Projects</h3> 
            </div>
            <div class="section-content py-3" id="projectsSection">
                <div class="mb-3">
                    <textarea id="projects" name="projects" rows="4" class="form-control"><?php echo htmlspecialchars($resume['projects'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">  Certifications
                </h3> 
            </div>
            <div class="section-content py-3" id="certificationsSection"> 
                <div class="mb-3">
                    <textarea id="certifications" name="certifications" rows="4" class="form-control"><?php echo htmlspecialchars($resume['certifications'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                <button name="<?php echo $resume ? 'update_resume' : 'create_resume'; ?>" type="submit" class="btn btn-success">  <?php echo $resume ? 'Update Resume' : 'Create Resume'; ?>
                </button>
                <div>
                    <?php if ($resume): ?>
                        <form method="post" id="delete-id-form" class="mb-0">
                        <input type="hidden" name="delete_id" value="<?php echo $resume['id']; ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Deleting this resume will also delete its related improved resumes!')">
                              Delete Optimization
                        </button>
                    </form>
                    <a href="optimize.php?resume_id=<?php echo $resume['id']; ?>" class="btn btn-primary">  Improve This Resume
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-danger"> Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div> 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('resumeForm');
    form.addEventListener('submit', function (event) {
        const fieldsToCheck = [
            'full_name',
            'email',
            'phone',
            'summary',
            'education',
            'experience',
            'skills'
        ];

        let emptyFields = [];
        for (let i = 0; i < fieldsToCheck.length; i++) {
            const fieldId = fieldsToCheck[i];
            const input = document.getElementById(fieldId);

            if (input && input.value.trim() === '') {
                emptyFields.push(fieldId.replace('_', ' '));  
            }
        }
        if (emptyFields.length > 0) {
            event.preventDefault();
            let message = "Please fill in the following fields before submitting:\n\n";
            for (let i = 0; i < emptyFields.length; i++) {
                message += emptyFields[i] + "\n";
            }
            alert(message);
        }
    }); 
});
</script>
</body>
</html>