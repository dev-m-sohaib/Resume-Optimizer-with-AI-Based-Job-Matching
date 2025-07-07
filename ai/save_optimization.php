<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$resume_id = (int)$_POST['resume_id'];
$job_description = $_POST['job_description'] ?? '';
$optimized_summary = $_POST['optimized_summary'] ?? '';
$optimized_experience = $_POST['optimized_experience'] ?? '[]';
$optimized_score = (float)$_POST['optimized_score'];
$original_score = (float)$_POST['original_score'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT summary, experience FROM resumes WHERE id = ? AND user_id = ?");
    $stmt->execute([$resume_id, $_SESSION['user_id']]);
    $resume_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$resume_data) {
        throw new Exception('Invalid resume');
    }

    $old_summary = $resume_data['summary'] ?? '';
    $old_experience = $resume_data['experience'] ?? '[]';

    $stmt = $pdo->prepare("
        INSERT INTO optimizations (
            resume_id, user_id, job_description, optimized_summary, optimized_experience, 
            old_summary, old_experience, optimized_score, original_score
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertSuccess = $stmt->execute([
        $resume_id,
        $_SESSION['user_id'],
        $job_description,
        $optimized_summary,
        $optimized_experience,
        $old_summary,
        $old_experience,
        $optimized_score,
        $original_score
    ]);

    if (!$insertSuccess) {
        throw new Exception('Failed to save optimization history');
    }

    $optimization_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        UPDATE resumes 
        SET summary = ?, experience = ? 
        WHERE id = ? AND user_id = ?
    ");
    $updateSuccess = $stmt->execute([
        $optimized_summary,
        $optimized_experience,
        $resume_id,
        $_SESSION['user_id']
    ]);

    if (!$updateSuccess) {
        throw new Exception('Failed to update resume');
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'optimization_id' => $optimization_id,
        'message' => 'Resume updated and optimization saved successfully'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
}
?>