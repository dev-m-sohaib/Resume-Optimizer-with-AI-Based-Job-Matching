<?php
require_once '../includes/auth.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
function processMultiFieldData($prefix, $postData) {
    $result = [];
    $index = 0;
    
    while (isset($postData[$prefix.'_institution_'.$index]) || 
           isset($postData[$prefix.'_degree_'.$index]) || 
           isset($postData[$prefix.'_start_date_'.$index])) {
        
        $item = [
            'institution' => sanitizeInput($postData[$prefix.'_institution_'.$index] ?? ''),
            'degree' => sanitizeInput($postData[$prefix.'_degree_'.$index] ?? ''),
            'field_of_study' => sanitizeInput($postData[$prefix.'_field_of_study_'.$index] ?? ''),
            'start_date' => sanitizeInput($postData[$prefix.'_start_date_'.$index] ?? ''),
            'end_date' => sanitizeInput($postData[$prefix.'_end_date_'.$index] ?? ''),
            'description' => sanitizeInput($postData[$prefix.'_description_'.$index] ?? ''),
            'current' => isset($postData[$prefix.'_current_'.$index]) ? 1 : 0
        ];
        
        if (!empty($item['institution'])) {
            $result[] = $item;
        }
        
        $index++;
    }
    
    return $result;
}

function processExperienceData($postData) {
    $result = [];
    $index = 0;
    
    while (isset($postData['exp_company_'.$index])) {
        $item = [
            'company' => sanitizeInput($postData['exp_company_'.$index] ?? ''),
            'position' => sanitizeInput($postData['exp_position_'.$index] ?? ''),
            'start_date' => sanitizeInput($postData['exp_start_date_'.$index] ?? ''),
            'end_date' => sanitizeInput($postData['exp_end_date_'.$index] ?? ''),
            'description' => sanitizeInput($postData['exp_description_'.$index] ?? ''),
            'current' => isset($postData['exp_current_'.$index]) ? 1 : 0
        ];
        
        if (!empty($item['company'])) {
            $result[] = $item;
        }
        
        $index++;
    }
    
    return $result;
}

$resume_id = isset($_GET['resume_id']) ? (int)$_GET['resume_id'] : 0;
$resume = $resume_id ? getResumeById($resume_id) : null;
if ($resume && $resume['user_id'] != $_SESSION['user_id']) {
    header('Location: resume-form.php');
    exit;
}
$education_entries = [];
$experience_entries = [];

if ($resume) {
    if (!empty($resume['education'])) {
        $education_entries = json_decode($resume['education'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $education_entries = []; 
        }
    }
    
    if (!empty($resume['experience'])) {
        $experience_entries = json_decode($resume['experience'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $experience_entries = []; 
        }
    }
}

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $education_data = processMultiFieldData('edu', $_POST);
    $experience_data = processExperienceData($_POST);
    
    $data = [
        'full_name' => sanitizeInput($_POST['full_name']),
        'email' => sanitizeInput($_POST['email']),
        'phone' => sanitizeInput($_POST['phone']),
        'summary' => sanitizeInput($_POST['summary']),
        'education' => json_encode($education_data),
        'experience' => json_encode($experience_data),
        'skills' => sanitizeInput($_POST['skills']),
        'projects' => sanitizeInput($_POST['projects']),
        'certifications' => sanitizeInput($_POST['certifications'])
    ];
    
    if ($resume_id && isset($_POST['update_resume'])) {
        if (updateResume($resume_id, $data)) {
            $_SESSION['success'] = 'Resume updated successfully!';
            header("Location: resume-form.php?resume_id=$resume_id");
            exit;
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
    } elseif(isset($_POST['delete_id'])) {
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
                               placeholder="e.g. Software Engineer Resume" required> 
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
                <h3 class="h5 text-primary mb-0">Personal Information</h3> 
            </div>
            <div class="section-content py-3" id="personalInfo">
                <div class="mb-3">
                    <label for="full_name" class="form-label fw-bold">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           value="<?php echo htmlspecialchars($resume['full_name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-bold">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?php echo htmlspecialchars($resume['email'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label fw-bold">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                           value="<?php echo htmlspecialchars($resume['phone'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">Professional Summary</h3> 
            </div>
            <div class="section-content py-3" id="summarySection">
                <div class="alert alert-info mb-3 form-tip"> 
                    Write an overview of your professional background.
                </div>
                <div class="mb-3">
                    <textarea id="summary" name="summary" rows="4" class="form-control" required><?php echo htmlspecialchars($resume['summary'] ?? ''); ?></textarea>
                 </div>
            </div>
                    <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">Education</h3> 
            </div>
            <div class="section-content py-3" id="educationSection">
                <div class="alert alert-info mb-3 form-tip"> 
                    List your educational background. Add multiple entries if needed.
                </div>
                
                <div id="educationEntries">
                    <?php if (!empty($education_entries)): ?>
                        <?php foreach ($education_entries as $index => $edu): ?>
                            <div class="entry-container" id="eduEntry_<?php echo $index; ?>">
                                <div class="entry-header">
                                    <h5>Education #<?php echo $index + 1; ?></h5>
                                    <span class="remove-entry" onclick="removeEntry('eduEntry_<?php echo $index; ?>')">&times;</span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Institution</label>
                                    <input type="text" name="edu_institution_<?php echo $index; ?>" class="form-control" 
                                           value="<?php echo htmlspecialchars($edu['institution'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Degree</label>
                                    <input type="text" name="edu_degree_<?php echo $index; ?>" class="form-control" 
                                           value="<?php echo htmlspecialchars($edu['degree'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Field of Study</label>
                                    <input type="text" name="edu_field_of_study_<?php echo $index; ?>" class="form-control" 
                                           value="<?php echo htmlspecialchars($edu['field_of_study'] ?? ''); ?>">
                                </div>
                                <div class="date-fields mb-3">
                                    <div class="date-field">
                                        <label class="form-label">Start Date</label>
                                        <input type="month" name="edu_start_date_<?php echo $index; ?>" class="form-control" 
                                               value="<?php echo htmlspecialchars($edu['start_date'] ?? ''); ?>">
                                    </div>
                                    <div class="date-field" id="eduEndDateContainer_<?php echo $index; ?>">
                                        <label class="form-label">End Date</label>
                                        <input type="month" name="edu_end_date_<?php echo $index; ?>" class="form-control" 
                                               value="<?php echo htmlspecialchars($edu['end_date'] ?? ''); ?>" 
                                               <?php echo !empty($edu['current']) ? 'disabled' : ''; ?>>
                                    </div>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" name="edu_current_<?php echo $index; ?>" class="form-check-input current-checkbox" 
                                           id="eduCurrent_<?php echo $index; ?>" 
                                           <?php echo !empty($edu['current']) ? 'checked' : ''; ?>
                                           onchange="toggleEndDate('eduEndDateContainer_<?php echo $index; ?>', 'edu_end_date_<?php echo $index; ?>', this)">
                                    <label class="form-check-label" for="eduCurrent_<?php echo $index; ?>">Currently attending</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="edu_description_<?php echo $index; ?>" class="form-control" rows="3"><?php echo htmlspecialchars($edu['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="entry-container" id="eduEntry_0">
                            <div class="entry-header">
                                <h5>Education #1</h5>
                                <span class="remove-entry" onclick="removeEntry('eduEntry_0')">&times;</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Institution</label>
                                <input type="text" name="edu_institution_0" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Degree</label>
                                <input type="text" name="edu_degree_0" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Field of Study</label>
                                <input type="text" name="edu_field_of_study_0" class="form-control">
                            </div>
                            <div class="date-fields mb-3">
                                <div class="date-field">
                                    <label class="form-label">Start Date</label>
                                    <input type="month" name="edu_start_date_0" class="form-control">
                                </div>
                                <div class="date-field" id="eduEndDateContainer_0">
                                    <label class="form-label">End Date</label>
                                    <input type="month" name="edu_end_date_0" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="edu_current_0" class="form-check-input current-checkbox" 
                                       id="eduCurrent_0" onchange="toggleEndDate('eduEndDateContainer_0', 'edu_end_date_0', this)">
                                <label class="form-check-label" for="eduCurrent_0">Currently attending</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="edu_description_0" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-outline-primary" onclick="addEducationEntry()">
                    <i class="bi bi-plus"></i> Add Another Education
                </button>
            </div>
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">Work Experience</h3> 
            </div>
            <div class="section-content py-3" id="experienceSection">
                <div class="alert alert-info mb-3 form-tip"> 
                    List your work history. Include company names, job titles and dates.
                </div>
                
                <div id="experienceEntries">
                    <?php if (!empty($experience_entries)): ?>
                        <?php foreach ($experience_entries as $index => $exp): ?>
                            <div class="entry-container" id="expEntry_<?php echo $index; ?>">
                                <div class="entry-header">
                                    <h5>Experience #<?php echo $index + 1; ?></h5>
                                    <span class="remove-entry" onclick="removeEntry('expEntry_<?php echo $index; ?>')">&times;</span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Company</label>
                                    <input type="text" name="exp_company_<?php echo $index; ?>" class="form-control" 
                                           value="<?php echo htmlspecialchars($exp['company'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Position</label>
                                    <input type="text" name="exp_position_<?php echo $index; ?>" class="form-control" 
                                           value="<?php echo htmlspecialchars($exp['position'] ?? ''); ?>" required>
                                </div>
                                <div class="date-fields mb-3">
                                    <div class="date-field">
                                        <label class="form-label">Start Date</label>
                                        <input type="month" name="exp_start_date_<?php echo $index; ?>" class="form-control" 
                                               value="<?php echo htmlspecialchars($exp['start_date'] ?? ''); ?>">
                                    </div>
                                    <div class="date-field" id="expEndDateContainer_<?php echo $index; ?>">
                                        <label class="form-label">End Date</label>
                                        <input type="month" name="exp_end_date_<?php echo $index; ?>" class="form-control" 
                                               value="<?php echo htmlspecialchars($exp['end_date'] ?? ''); ?>"
                                               <?php echo !empty($exp['current']) ? 'disabled' : ''; ?>>
                                    </div>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" name="exp_current_<?php echo $index; ?>" class="form-check-input current-checkbox" 
                                           id="expCurrent_<?php echo $index; ?>" 
                                           <?php echo !empty($exp['current']) ? 'checked' : ''; ?>
                                           onchange="toggleEndDate('expEndDateContainer_<?php echo $index; ?>', 'exp_end_date_<?php echo $index; ?>', this)">
                                    <label class="form-check-label" for="expCurrent_<?php echo $index; ?>">I currently work here</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="exp_description_<?php echo $index; ?>" class="form-control" rows="3"><?php echo htmlspecialchars($exp['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="entry-container" id="expEntry_0">
                            <div class="entry-header">
                                <h5>Experience #1</h5>
                                <span class="remove-entry" onclick="removeEntry('expEntry_0')">&times;</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Company</label>
                                <input type="text" name="exp_company_0" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Position</label>
                                <input type="text" name="exp_position_0" class="form-control" required>
                            </div>
                            <div class="date-fields mb-3">
                                <div class="date-field">
                                    <label class="form-label">Start Date</label>
                                    <input type="month" name="exp_start_date_0" class="form-control">
                                </div>
                                <div class="date-field" id="expEndDateContainer_0">
                                    <label class="form-label">End Date</label>
                                    <input type="month" name="exp_end_date_0" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="exp_current_0" class="form-check-input current-checkbox" 
                                       id="expCurrent_0" onchange="toggleEndDate('expEndDateContainer_0', 'exp_end_date_0', this)">
                                <label class="form-check-label" for="expCurrent_0">I currently work here</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="exp_description_0" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-outline-primary" onclick="addExperienceEntry()">
                    <i class="bi bi-plus"></i> Add Another Experience
                </button>
            </div>
            
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">Skills</h3> 
            </div>
            <div class="section-content py-3" id="skillsSection">
                <div class="alert alert-info mb-3 form-tip"> 
                    List your most relevant skills.
                </div>
                <div class="mb-3">
                    <textarea id="skills" name="skills" rows="4" class="form-control" 
                              placeholder="e.g., JavaScript, Python, Project Management, Team Leadership" required><?php echo htmlspecialchars($resume['skills'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">Projects</h3> 
            </div>
            <div class="section-content py-3" id="projectsSection">
                <div class="mb-3">
                    <textarea id="projects" name="projects" rows="4" class="form-control"><?php echo htmlspecialchars($resume['projects'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="section-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                <h3 class="h5 text-primary mb-0">Certifications</h3> 
            </div>
            <div class="section-content py-3" id="certificationsSection"> 
                <div class="mb-3">
                    <textarea id="certifications" name="certifications" rows="4" class="form-control"><?php echo htmlspecialchars($resume['certifications'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                <button name="<?php echo $resume ? 'update_resume' : 'create_resume'; ?>" type="submit" class="btn btn-success">  
                    <?php echo $resume ? 'Update Resume' : 'Create Resume'; ?>
                </button>
                <div>
                    <?php if ($resume): ?>
                        <form method="post" id="delete-id-form" class="mb-0">
                            <input type="hidden" name="delete_id" value="<?php echo $resume['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Deleting this resume will also delete its related improved resumes!')">
                                Delete Resume
                            </button>
                        </form>
                        <a href="optimize.php?resume_id=<?php echo $resume['id']; ?>" class="btn btn-primary">Improve This Resume</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-danger">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div> 
    <script>
        let educationEntryCount = <?php echo !empty($education_entries) ? count($education_entries) : 1; ?>;
        let experienceEntryCount = <?php echo !empty($experience_entries) ? count($experience_entries) : 1; ?>;
    </script>
    <script src="../assets/js/resume-form.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>