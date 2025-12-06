<?php
require_once '../config.php';

if (!is_participant()) {
    redirect('/participant/join.php');
}

$room_id = get_participant_room_id();
$team_id = get_participant_team_id();

// Get final score and ranking
$my_score = fetch_single("SELECT * FROM scores WHERE room_id = $room_id AND team_id = $team_id");
$team = fetch_single("SELECT * FROM teams WHERE id = $team_id");

// Get all rankings
$rankings = fetch_all("
    SELECT s.*, t.name as team_name
    FROM scores s
    JOIN teams t ON s.team_id = t.id
    WHERE s.room_id = $room_id
    ORDER BY s.total_points DESC, s.correct_answers DESC
");

// Find my rank
$my_rank = 0;
foreach ($rankings as $idx => $r) {
    if ($r['team_id'] == $team_id) {
        $my_rank = $idx + 1;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Akhir - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/participant.css">
</head>

<body class="finished-page">
    <div class="finished-container">
        <div class="finished-card">
            <h1>🏁 Pertandingan Selesai!</h1>

            <div class="my-result">
                <h2><?= htmlspecialchars($team['name']) ?></h2>
                <div class="rank-badge rank-<?= $my_rank ?>">
                    Peringkat #<?= $my_rank ?>
                </div>
                <div class="final-score">
                    <div class="score-value"><?= $my_score['total_points'] ?? 0 ?></div>
                    <div class="score-label">Total Poin</div>
                </div>
                <div class="score-details">
                    ✅ <?= $my_score['correct_answers'] ?? 0 ?> Benar |
                    ❌ <?= $my_score['wrong_answers'] ?? 0 ?> Salah
                </div>
            </div>

            <div class="final-rankings">
                <h3>🏆 Peringkat Final</h3>
                <?php foreach ($rankings as $idx => $r): ?>
                    <div class="ranking-item <?= $r['team_id'] == $team_id ? 'my-team' : '' ?>">
                        <span class="rank">#<?= $idx + 1 ?></span>
                        <span class="team-name"><?= htmlspecialchars($r['team_name']) ?></span>
                        <span class="points"><?= $r['total_points'] ?> pts</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="finished-footer">
                <p>Terima kasih telah berpartisipasi!</p>
            </div>
        </div>
    </div>
</body>

</html>