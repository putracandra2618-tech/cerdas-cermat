<?php
require_once '../config.php';

if (!is_participant()) {
    redirect('/participant/join.php');
}

$room_id = get_participant_room_id();
$team_id = get_participant_team_id();
$room_code = $_SESSION['participant_room_code'];

// Get room and team info
$room = fetch_single("SELECT * FROM rooms WHERE id = $room_id");
$team = fetch_single("SELECT * FROM teams WHERE id = $team_id");

if (!$room || !$team) {
    redirect('/participant/join.php');
}

// If match has started, redirect to play
if ($room['status'] === 'running') {
    redirect('/participant/play.php');
}

if ($room['status'] === 'finished') {
    redirect('/participant/finished.php');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/participant.css">
</head>

<body class="lobby-page">
    <div class="lobby-container">
        <div class="lobby-card">
            <div class="lobby-icon">⏳</div>
            <h1>Menunggu Pertandingan Dimulai...</h1>

            <div class="lobby-info">
                <div class="info-item">
                    <strong>Room Code:</strong>
                    <span class="room-code"><?= htmlspecialchars($room_code) ?></span>
                </div>
                <div class="info-item">
                    <strong>Tim Anda:</strong>
                    <span class="team-name"><?= htmlspecialchars($team['name']) ?></span>
                </div>
            </div>

            <div class="loading-animation">
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>

            <p class="lobby-message">Panitia akan segera memulai pertandingan...</p>
        </div>
    </div>

    <script>
        // Poll room status every 1 second
        setInterval(function () {
            fetch('/api/room_status.php?room_id=<?= $room_id ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.status === 'running') {
                        window.location.href = '/participant/play.php';
                    } else if (data.success && data.data.status === 'finished') {
                        window.location.href = '/participant/finished.php';
                    }
                })
                .catch(error => console.error('Error polling status:', error));
        }, 1000);
    </script>
</body>

</html>