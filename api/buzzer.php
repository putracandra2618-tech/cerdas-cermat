<?php
require_once '../config.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$room_id = (int) ($input['room_id'] ?? 0);
$question_id = (int) ($input['question_id'] ?? 0);
$team_id = (int) ($input['team_id'] ?? 0);

if (!$room_id || !$question_id || !$team_id) {
    json_error('Missing required fields', 400);
}

// Get room
$room = fetch_single("SELECT * FROM rooms WHERE id = $room_id");

if (!$room) {
    json_error('Room not found', 404);
}

if ($room['current_phase'] !== 'buzzer') {
    json_error('Buzzer phase not active', 400);
}

// Check if already buzzed
$existing = fetch_single("
    SELECT * FROM buzzers 
    WHERE room_id = $room_id 
    AND question_id = $question_id 
    AND team_id = $team_id
");

if ($existing) {
    json_error('Already buzzed', 400);
}

// Calculate buzz time
$question_start = (float) $room['question_start_time'];
$buzz_time_raw = get_microtime();
$buzz_time = $buzz_time_raw - $question_start;

// Get current count to determine order
$current_count = fetch_single("
    SELECT COUNT(*) as count FROM buzzers 
    WHERE room_id = $room_id AND question_id = $question_id
")['count'];

$buzzer_order = $current_count + 1;

// Insert buzzer
query("INSERT INTO buzzers (room_id, question_id, team_id, buzz_time, buzzer_order) 
       VALUES ($room_id, $question_id, $team_id, $buzz_time, $buzzer_order)");

json_response([
    'order' => $buzzer_order,
    'buzz_time' => $buzz_time,
    'message' => 'Buzzer received'
]);
?>