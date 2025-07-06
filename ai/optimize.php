<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resume_id = isset($_POST['resume_id']) ? (int)$_POST['resume_id'] : 0;
    $job_description = isset($_POST['job_description']) ? sanitizeInput($_POST['job_description']) : '';
    $stmt = $pdo->prepare("SELECT * FROM resumes WHERE id = ?");
    $stmt->execute([$resume_id]);
    $resume = $stmt->fetch();

    if (!$resume) {
        echo json_encode(['error' => 'Resume not found']);
        exit;
    }

    $prompt = "I have a resume and a job description. Please suggest optimizations to better align the resume with the job description. 
Only suggest changes to existing content - do not add any new experiences or qualifications that aren't already in the resume.

Resume Summary:
{$resume['summary']}

Resume Experience:
{$resume['experience']} 

Job Description:
$job_description

Please return your suggestions in JSON format with these fields: 
{
  \"optimized_summary\": {
    \"original\": \"[original summary text]\",
    \"optimized\": \"[optimized summary text]\",
    \"original_score\": [0-10],
    \"optimized_score\": [0-10]
  },
  \"optimized_experience\": {
    \"original\": \"[original experience text]\",
    \"optimized\": \"[optimized experience text]\",
    \"original_score\": [0-10],
    \"optimized_score\": [0-10]
  },
  \"overall_score\": {
    \"original\": [0-10],
    \"optimized\": [0-10]
  }
}
Focus on rewording to better match keywords and requirements from the job description, while maintaining truthfulness.";

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init(AI_ENDPOINT . '?key=' . AI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResponse = $result['candidates'][0]['content']['parts'][0]['text'];
        $cleanedResponse = preg_replace('/^```json|```$/m', '', trim($aiResponse));
        $optimizedData = json_decode(trim($cleanedResponse), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            echo json_encode([
                'optimized_summary' => $optimizedData['optimized_summary'] ?? ['original' => '', 'optimized' => '', 'original_score' => 0, 'optimized_score' => 0],
                'optimized_experience' => $optimizedData['optimized_experience'] ?? ['original' => '', 'optimized' => '', 'original_score' => 0, 'optimized_score' => 0],
                'overall_score' => $optimizedData['overall_score'] ?? ['original' => 0, 'optimized' => 0]
            ]);
        } else {
            echo json_encode([
                'error' => 'Failed to parse AI response',
                'raw_response' => $aiResponse
            ]);
        }
    } else {
        echo json_encode([
            'error' => 'AI API request failed',
            'details' => $result
        ]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}