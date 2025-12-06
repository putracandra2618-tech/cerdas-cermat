<?php
require_once '../config.php';

if (!isset($_SESSION['join_room_code']) || !isset($_SESSION['join_teams'])) {
    redirect('/participant/join.php');
}

$room_code = $_SESSION['join_room_code'];
$room_id = $_SESSION['join_room_id'];
$teams = $_SESSION['join_teams'];

// Handle team selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_id = (int) $_POST['team_id'];

    // Check if team already joined
    $already_joined = fetch_single("
        SELECT * FROM room_participants 
        WHERE room_id = $room_id AND team_id = $team_id
    ");

    if ($already_joined) {
        $error = 'Tim ini sudah diambil oleh peserta lain!';
    } else {
        // Insert participant
        query("INSERT INTO room_participants (room_id, team_id) VALUES ($room_id, $team_id)");

        // Create score record
        $score_exists = fetch_single("SELECT * FROM scores WHERE room_id = $room_id AND team_id = $team_id");
        if (!$score_exists) {
            query("INSERT INTO scores (room_id, team_id) VALUES ($room_id, $team_id)");
        }

        // Set session
        $_SESSION['participant_room_id'] = $room_id;
        $_SESSION['participant_room_code'] = $room_code;
        $_SESSION['participant_team_id'] = $team_id;

        // Clear join session
        unset($_SESSION['join_room_code']);
        unset($_SESSION['join_room_id']);
        unset($_SESSION['join_teams']);

        redirect('/participant/lobby.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Tim - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/participant.css">
</head>

<body class="select-team-page">
    <div class="select-team-container">
        <div class="select-team-card">
            <div class="select-team-header">
                <h1>Pilih Tim Anda</h1>
                <p>Room: <?= htmlspecialchars($room_code) ?></p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="teams-grid">
                    <?php foreach ($teams as $team): ?>
                        <label class="team-option">
                            <input type="radio" name="team_id" value="<?= $team['id'] ?>" required>
                            <div class="team-card">
                                <div class="team-icon">👥</div>
                                <div class="team-name"><?= htmlspecialchars($team['name']) ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-large">
                    Lanjutkan
                </button>
            </form>
        </div>
    </div>
</body>

</html>