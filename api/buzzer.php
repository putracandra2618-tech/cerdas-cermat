<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['match_id']) || !isset($data['question_id']) || !isset($data['response_time'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required data']);
    exit;
}

$match_id = intval($data['match_id']);
$question_id = intval($data['question_id']);
$response_time = floatval($data['response_time']);

// Get the next buzzer order
$result = query("SELECT COUNT(*) as count FROM buzzer_responses 
                 WHERE match_id = $match_id AND question_id = $question_id");
$row = $result->fetch_assoc();
$buzzer_order = $row['count'] + 1;

// Get team ID from session (for now, we'll use a simple approach)
// In a real application, you'd have proper team authentication
$team_id = $buzzer_order; // This is a simplified approach

// Insert buzzer response
$sql = "INSERT INTO buzzer_responses (match_id, question_id, team_id, response_time, buzzer_order) 
        VALUES ($match_id, $question_id, $team_id, $response_time, $buzzer_order)";

if (query($sql)) {
    echo json_encode(['success' => true, 'buzzer_order' => $buzzer_order]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save buzzer response']);
}
?>