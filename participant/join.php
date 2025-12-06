<?php
require_once '../config.php';

// Handle join room
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_code = strtoupper(trim($_POST['room_code'] ?? ''));

    if (!$room_code) {
        $error = 'Kode room tidak boleh kosong!';
    } else {
        // Verify room exists
        $room = fetch_single("SELECT * FROM rooms WHERE code = '" . escape($room_code) . "'");

        if (!$room) {
            $error = 'Kode room tidak valid!';
        } elseif ($room['status'] === 'finished') {
            $error = 'Room ini sudah selesai!';
        } else {
            // Get teams in this match
            $teams = fetch_all("
                SELECT t.* 
                FROM teams t
                JOIN match_teams mt ON t.id = mt.team_id
                WHERE mt.match_id = " . $room['match_id'] . "
                ORDER BY t.name ASC
            ");

            if (empty($teams)) {
                $error = 'Tidak ada tim di match ini!';
            } else {
                $_SESSION['join_room_code'] = $room_code;
                $_SESSION['join_room_id'] = $room['id'];
                $_SESSION['join_teams'] = $teams;
                redirect('/participant/select_team.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Room - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/participant.css">
</head>

<body class="join-page">
    <div class="join-container">
        <div class="join-card">
            <div class="join-header">
                <h1>🎯 Cerdas Cermat</h1>
                <p>Masukkan Kode Room</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="join-form">
                <div class="form-group">
                    <label for="room_code">Kode Room</label>
                    <input type="text" id="room_code" name="room_code" placeholder="Contoh: TEX25-01" required autofocus
                        style="text-transform: uppercase;">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-large">
                    Gabung Room
                </button>
            </form>

            <div class="join-footer">
                <p><small>Dapatkan kode room dari panitia</small></p>
            </div>
        </div>
    </div>
</body>

</html>