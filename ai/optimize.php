<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resume_id = isset($_POST['resume_id']) ? (int)$_POST['resume_id'] : 0;
    $job_description = isset($_POST['job_description']) ? sanitizeInput($_POST['job_description']) : '';
    
    $stmt = $pdo->prepare("SELECT summary, experience FROM resumes WHERE id = ?");
    $stmt->execute([$resume_id]);
    $resume = $stmt->fetch();

    if (!$resume) {
        echo json_encode(['error' => 'Resume not found']);
        exit;
    }

    $experience = json_decode($resume['experience'] ?? '[]', true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $experience = [];
    }
    $experience_text = '';
    foreach ($experience as $index => $exp) {
        $experience_text .= "Experience Entry " . ($index + 1) . ":\n";
        $experience_text .= "Company: " . ($exp['company'] ?? '') . "\n";
        $experience_text .= "Position: " . ($exp['position'] ?? '') . "\n";
        $experience_text .= "Start Date: " . ($exp['start_date'] ?? '') . "\n";
        $experience_text .= "End Date: " . ($exp['end_date'] ?? ($exp['current'] ? 'Present' : '')) . "\n";
        $experience_text .= "Description: " . ($exp['description'] ?? '') . "\n\n";
    }

    $prompt = <<<EOD
I have a resume and a job description. Please suggest optimizations to better align the resume's summary and experience sections with the job description. 
Only suggest changes to existing content - do not add new experiences or qualifications that aren't already in the resume.

Resume Summary:
{$resume['summary']}

Resume Experience (JSON format):
{$resume['experience']}

Job Description:
$job_description

Please return your suggestions in JSON format with the following structure:
{
  "optimized_summary": {
    "original": "[original summary text]",
    "optimized": "[optimized summary text]",
    "original_score": [0-10],
    "optimized_score": [0-10]
  },
  "optimized_experience": [
    {
      "original": {
        "company": "[original company]",
        "position": "[original position]",
        "start_date": "[original start_date]",
        "end_date": "[original end_date]",
        "description": "[original description]",
        "current": [0 or 1]
      },
      "optimized": {
        "company": "[optimized company]",
        "position": "[optimized position]",
        "start_date": "[optimized start_date]",
        "end_date": "[optimized end_date]",
        "description": "[optimized description]",
        "current": [0 or 1]
      },
      "original_score": [0-10],
      "optimized_score": [0-10]
    }
    // ... more experience entries
  ],
  "overall_score": {
    "original": [0-10],
    "optimized": [0-10]
  }
}
Focus on rewording the summary and experience descriptions to better match keywords and requirements from the job description while maintaining truthfulness. Preserve the JSON structure for experience, optimizing each entry individually.
EOD;

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
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($http_code === 200 && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResponse = $result['candidates'][0]['content']['parts'][0]['text'];
        $cleanedResponse = preg_replace('/^```json|```$/m', '', trim($aiResponse));
        $optimizedData = json_decode(trim($cleanedResponse), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $responseData = [
                'optimized_summary' => $optimizedData['optimized_summary'] ?? [
                    'original' => $resume['summary'] ?? '',
                    'optimized' => $resume['summary'] ?? '',
                    'original_score' => 0,
                    'optimized_score' => 0
                ],
                'optimized_experience' => $optimizedData['optimized_experience'] ?? [],
                'overall_score' => $optimizedData['overall_score'] ?? [
                    'original' => 0,
                    'optimized' => 0
                ]
            ]; 

            echo json_encode($responseData);
        } else {
            echo json_encode([
                'error' => 'Failed to parse AI response',
                'raw_response' => $aiResponse
            ]);
        }
    } else {
        echo json_encode([
            'error' => 'AI API request failed',
            'details' => $result,
            'http_code' => $http_code
        ]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>