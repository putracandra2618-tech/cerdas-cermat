<?php
require_once '../config.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$room_id = (int) ($input['room_id'] ?? 0);
$question_id = (int) ($input['question_id'] ?? 0);
$team_id = (int) ($input['team_id'] ?? 0);
$choice = strtoupper(trim($input['choice'] ?? ''));

if (!$room_id || !$question_id || !$team_id || !$choice) {
    json_error('Missing required fields', 400);
}

if (!in_array($choice, ['A', 'B', 'C', 'D', 'E'])) {
    json_error('Invalid choice', 400);
}

// Get room
$room = fetch_single("SELECT * FROM rooms WHERE id = $room_id");

if (!$room) {
    json_error('Room not found', 404);
}

if ($room['current_phase'] !== 'answer') {
    json_error('Answer phase not active', 400);
}

// Check if already answered
$existing = fetch_single("
    SELECT * FROM answers 
    WHERE room_id = $room_id 
    AND question_id = $question_id 
    AND team_id = $team_id
");

if ($existing) {
    json_error('Already answered', 400);
}

// Insert answer (is_correct will be calculated later when admin reveals)
query("INSERT INTO answers (room_id, question_id, team_id, selected_answer, is_correct, points_earned) 
       VALUES ($room_id, $question_id, $team_id, '$choice', 0, 0)");

json_response([
    'message' => 'Answer submitted',
    'choice' => $choice
]);
?>