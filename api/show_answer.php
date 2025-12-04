<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['match_id']) || !isset($data['question_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required data']);
    exit;
}

$match_id = intval($data['match_id']);
$question_id = intval($data['question_id']);

// Get correct answer
$question = fetch_single("SELECT correct_answer FROM questions WHERE id = $question_id");

if ($question) {
    echo json_encode(['success' => true, 'correct_answer' => $question['correct_answer']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Question not found']);
}
?>