<?php
require_once '../config.php';

header('Content-Type: application/json');

$room_id = (int) ($_GET['room_id'] ?? 0);

if (!$room_id) {
    json_error('Room ID required', 400);
}

// Get room data
$room = fetch_single("SELECT * FROM rooms WHERE id = $room_id");

if (!$room) {
    json_error('Room not found', 404);
}

// Get participants count
$participants_count = fetch_single("SELECT COUNT(*) as count FROM room_participants WHERE room_id = $room_id")['count'];

json_response([
    'status' => $room['status'],
    'current_question_id' => $room['current_question_id'],
    'current_phase' => $room['current_phase'],
    'countdown' => $room['countdown_value'],
    'participants_count' => $participants_count
]);
?>