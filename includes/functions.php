<?php 
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
function redirect($url) {
    header("Location: $url");
    exit();
}
function getResumeByUserId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM resumes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function saveResume($user_id, $data) {
    global $pdo;
    $resume = getResumeByUserId($user_id);
    if ($resume) {
        $stmt = $pdo->prepare("UPDATE resumes SET full_name = ?,email = ?,phone = ?,summary = ?, education = ?,  experience = ?,skills = ?,projects = ?,certifications = ? WHERE user_id = ?");
        return $stmt->execute([
            $data['full_name'], $data['email'], $data['phone'], $data['summary'],
            $data['education'], $data['experience'], $data['skills'], 
            $data['projects'], $data['certifications'], $user_id
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO resumes 
            (user_id, full_name, email, phone, summary, education, experience, skills, projects, certifications) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $user_id, $data['full_name'], $data['email'], $data['phone'], $data['summary'],
            $data['education'], $data['experience'], $data['skills'], 
            $data['projects'], $data['certifications']
        ]);
    }
}

function getOptimizationsByResumeId($resume_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM optimizations WHERE resume_id = ? ORDER BY created_at DESC");
    $stmt->execute([$resume_id]);
    return $stmt->fetchAll();
}
function getOptimizationById($optimization_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM optimizations WHERE id = ?");
    $stmt->execute([$optimization_id]);
    return $stmt->fetch();
}
function getUserResumes($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM resumes WHERE user_id = ? ORDER BY is_active DESC, created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
function getResumeById($resume_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM resumes WHERE id = ?");
    $stmt->execute([$resume_id]);
    return $stmt->fetch();
}
function updateResumeWithOptimization($resume_id, $optimized_data) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE resumes SET summary = ?, experience = ?, skills = ? WHERE id = ?");
    return $stmt->execute([
        $optimized_data['optimized_summary'],
        $optimized_data['optimized_experience'],
        $optimized_data['optimized_skills'],
        $resume_id
    ]);
}

function updateResume($resume_id, $data) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE resumes SET full_name = ?, email = ?, phone = ?,summary = ?,education = ?,experience = ?, skills = ?, projects = ?, certifications = ? WHERE id = ?");
    
    return $stmt->execute([
        $data['full_name'], $data['email'], $data['phone'], $data['summary'],
        $data['education'], $data['experience'], $data['skills'], 
        $data['projects'], $data['certifications'], $resume_id
    ]);
}

function getOptimizations($user_id) {
    global $pdo; 

    $stmt = $pdo->prepare("SELECT o.* FROM optimizations o JOIN resumes r ON o.resume_id = r.id WHERE r.user_id = ? ORDER BY o.created_at"); 
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
function createNewResume($user_id, $title, $data = []) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO resumes 
        (user_id, title, full_name, email, phone, summary, education, experience, skills, projects, certifications) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $success = $stmt->execute([
        $user_id, 
        $title,
        $data['full_name'] ?? '',
        $data['email'] ?? '',
        $data['phone'] ?? '',
        $data['summary'] ?? '',
        $data['education'] ?? '',
        $data['experience'] ?? '',
        $data['skills'] ?? '',
        $data['projects'] ?? '',
     $data['certifications'] ?? ''
    ]);
    return $success ? $pdo->lastInsertId() : false;
}

function deleteOptimization($optimization_id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE o FROM optimizations o JOIN resumes r ON o.resume_id = r.id WHERE o.id = ? AND r.user_id = ?");
    return $stmt->execute([$optimization_id, $user_id]);
}
function deleteResume($resume_id, $user_id) {
    global $pdo;
    $stmt1 = $pdo->prepare("DELETE FROM optimizations WHERE resume_id = ?");
    $stmt1->execute([$resume_id]);

    $stmt2 = $pdo->prepare("DELETE FROM resumes WHERE id = ? AND user_id = ?");
    return $stmt2->execute([$resume_id, $user_id]);
}


function printOptimizationButton($optimization_id, $type = 'optimized', $text = 'Print/Export') {
    return '<a href="print_template.php?id=' . $optimization_id . '&type=' . $type . '" target="_blank" class="btn btn-print">' . $text . '</a>';
}