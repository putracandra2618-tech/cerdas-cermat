<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['match_id']) || !isset($data['question_id']) || !isset($data['answer'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required data']);
    exit;
}

$match_id = intval($data['match_id']);
$question_id = intval($data['question_id']);
$answer = strtoupper($data['answer']);

// Get team ID from session (simplified approach)
// In a real application, you'd have proper team authentication
$team_id = 1; // This should be determined by which team is answering

// Get the correct answer
$question = fetch_single("SELECT correct_answer FROM questions WHERE id = $question_id");
$is_correct = ($question['correct_answer'] === $answer);
$points = $is_correct ? 10 : 0;

// Insert team answer
$sql = "INSERT INTO team_answers (match_id, question_id, team_id, selected_answer, is_correct, points_earned) 
        VALUES ($match_id, $question_id, $team_id, '$answer', $is_correct, $points)
        ON DUPLICATE KEY UPDATE 
        selected_answer = '$answer', 
        is_correct = $is_correct, 
        points_earned = $points";

if (query($sql)) {
    // Update team score
    $sql = "UPDATE match_teams SET score = score + $points 
            WHERE match_id = $match_id AND team_id = $team_id";
    query($sql);
    
    echo json_encode(['success' => true, 'is_correct' => $is_correct, 'points' => $points]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save answer']);
}
?>