<?php
require_once '../config.php';
require_admin();

// Handle create match
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create_match') {
        $name = escape($_POST['name']);
        $round = escape($_POST['round']);
        $team_ids = $_POST['team_ids'] ?? [];

        if (count($team_ids) < 2) {
            redirect('/admin/brackets.php?error=min_teams');
        }

        begin_transaction();
        try {
            // Create match
            query("INSERT INTO matches (name, round) VALUES ('$name', '$round')");
            $match_id = get_insert_id();

            // Assign teams to match
            foreach ($team_ids as $team_id) {
                $team_id = (int) $team_id;
                query("INSERT INTO match_teams (match_id, team_id) VALUES ($match_id, $team_id)");
            }

            commit();
            redirect('/admin/brackets.php?success=created');
        } catch (Exception $e) {
            rollback();
            redirect('/admin/brackets.php?error=failed');
        }
    } elseif ($action === 'create_room') {
        $match_id = (int) $_POST['match_id'];

        // Generate unique room code
        do {
            $code = generate_room_code();
            $exists = fetch_single("SELECT id FROM rooms WHERE code = '$code'");
        } while ($exists);

        query("INSERT INTO rooms (match_id, code) VALUES ($match_id, '$code')");
        $room_id = get_insert_id();

        redirect('/admin/room.php?code=' . $code);
    } elseif ($action === 'delete_match') {
        $match_id = (int) $_POST['match_id'];
        query("DELETE FROM matches WHERE id = $match_id");
        redirect('/admin/brackets.php?success=deleted');
    }
}

// Get all matches with team info
$matches = fetch_all("
    SELECT m.*, 
           GROUP_CONCAT(t.name SEPARATOR ', ') as team_names,
           COUNT(DISTINCT mt.team_id) as team_count,
           r.code as room_code,
           tw.name as winner_name
    FROM matches m
    LEFT JOIN match_teams mt ON m.id = mt.match_id
    LEFT JOIN teams t ON mt.team_id = t.id
    LEFT JOIN rooms r ON m.id = r.match_id
    LEFT JOIN teams tw ON m.winner_team_id = tw.id
    GROUP BY m.id
    ORDER BY m.created_at DESC
");

// Get all teams for dropdown
$all_teams = fetch_all("SELECT * FROM teams ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bracket & Matches - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Bracket & Matches</h1>
            <button onclick="showCreateModal()" class="btn btn-primary">+ Buat Match Baru</button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['success'] === 'created')
                    echo 'Match berhasil dibuat!';
                elseif ($_GET['success'] === 'deleted')
                    echo 'Match berhasil dihapus!';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                if ($_GET['error'] === 'min_teams')
                    echo 'Minimal 2 tim harus dipilih!';
                elseif ($_GET['error'] === 'failed')
                    echo 'Gagal membuat match!';
                ?>
            </div>
        <?php endif; ?>

        <div class="matches-grid">
            <?php foreach ($matches as $match): ?>
                <div class="match-card status-<?= $match['status'] ?>">
                    <div class="match-header">
                        <h3><?= htmlspecialchars($match['name']) ?></h3>
                        <span class="match-round"><?= htmlspecialchars($match['round']) ?></span>
                    </div>

                    <div class="match-info">
                        <div class="info-row">
                            <strong>Status:</strong>
                            <span class="badge badge-<?= $match['status'] ?>">
                                <?php
                                if ($match['status'] === 'waiting')
                                    echo '⏳ Menunggu';
                                elseif ($match['status'] === 'running')
                                    echo '▶️ Berlangsung';
                                else
                                    echo '✅ Selesai';
                                ?>
                            </span>
                        </div>

                        <div class="info-row">
                            <strong>Tim:</strong>
                            <span><?= $match['team_count'] ?> tim</span>
                        </div>

                        <div class="team-list">
                            <?= htmlspecialchars($match['team_names']) ?>
                        </div>

                        <?php if ($match['winner_name']): ?>
                            <div class="winner-badge">
                                🏆 Pemenang: <?= htmlspecialchars($match['winner_name']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="match-actions">
                        <?php if ($match['room_code']): ?>
                            <a href="/admin/room.php?code=<?= $match['room_code'] ?>" class="btn btn-primary btn-block">
                                🚪 Buka Room (<?= $match['room_code'] ?>)
                            </a>
                        <?php else: ?>
                            <form method="POST" action="" style="margin:0;">
                                <input type="hidden" name="action" value="create_room">
                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                <button type="submit" class="btn btn-success btn-block">
                                    + Buat Room
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($match['status'] === 'waiting'): ?>
                            <button onclick="deleteMatch(<?= $match['id'] ?>)" class="btn btn-danger btn-sm">
                                Hapus Match
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Create Match Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Buat Match Baru</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_match">

                <div class="form-group">
                    <label for="name">Nama Match *</label>
                    <input type="text" id="name" name="name" placeholder="Contoh: Penyisihan Room 1" required>
                </div>

                <div class="form-group">
                    <label for="round">Ronde *</label>
                    <select id="round" name="round" required>
                        <option value="Penyisihan">Penyisihan</option>
                        <option value="Semifinal">Semifinal</option>
                        <option value="Final">Final</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Pilih Tim * (minimal 2)</label>
                    <div class="checkbox-group">
                        <?php foreach ($all_teams as $team): ?>
                            <label>
                                <input type="checkbox" name="team_ids[]" value="<?= $team['id'] ?>">
                                <?= htmlspecialchars($team['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Buat Match</button>
            </form>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" method="POST" action="" style="display:none;">
        <input type="hidden" name="action" value="delete_match">
        <input type="hidden" id="delete_match_id" name="match_id">
    </form>

    <script>
        function showCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        function deleteMatch(id) {
            if (confirm('Hapus match ini?')) {
                document.getElementById('delete_match_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>

</html>