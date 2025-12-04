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

// Get total teams in match
$result = query("SELECT COUNT(*) as total_teams FROM match_teams WHERE match_id = $match_id");
$row = $result->fetch_assoc();
$total_teams = $row['total_teams'];

// Get teams that have answered
$result = query("SELECT COUNT(DISTINCT team_id) as answered_teams 
                FROM team_answers 
                WHERE match_id = $match_id AND question_id = $question_id");
$row = $result->fetch_assoc();
$answered_teams = $row['answered_teams'];

$all_answered = ($answered_teams >= $total_teams);

echo json_encode(['all_answered' => $all_answered, 'answered_teams' => $answered_teams, 'total_teams' => $total_teams]);
?>