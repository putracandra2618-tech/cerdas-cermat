<?php
require_once '../config.php';
require_admin();

$code = $_GET['code'] ?? '';
if (!$code) {
    redirect('/admin/brackets.php');
}

// Get room data
$room = fetch_single("
    SELECT r.*, m.name as match_name, m.round, m.status as match_status
    FROM rooms r
    JOIN matches m ON r.match_id = m.id
    WHERE r.code = '" . escape($code) . "'
");

if (!$room) {
    redirect('/admin/brackets.php?error=room_not_found');
}

// Handle control actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_id = (int) $room['id'];

    if ($action === 'start_match') {
        query("UPDATE rooms SET status = 'running' WHERE id = $room_id");
        query("UPDATE matches SET status = 'running' WHERE id = " . $room['match_id']);
        redirect('/admin/room.php?code=' . $code);
    } elseif ($action === 'set_question') {
        $question_id = (int) $_POST['question_id'];
        $start_time = get_microtime();
        query("UPDATE rooms SET 
               current_question_id = $question_id,
               current_phase = 'countdown',
               question_start_time = $start_time,
               countdown_value = 3
               WHERE id = $room_id");
        redirect('/admin/room.php?code=' . $code . '&phase=countdown');
    } elseif ($action === 'open_buzzer') {
        query("UPDATE rooms SET current_phase = 'buzzer' WHERE id = $room_id");
        redirect('/admin/room.php?code=' . $code . '&phase=buzzer');
    } elseif ($action === 'close_buzzer') {
        query("UPDATE rooms SET current_phase = 'answer' WHERE id = $room_id");
        redirect('/admin/room.php?code=' . $code . '&phase=answer');
    } elseif ($action === 'reveal_answer') {
        $question_id = (int) $room['current_question_id'];

        // Get correct answer
        $question = fetch_single("SELECT correct_option FROM questions WHERE id = $question_id");
        $correct = $question['correct_option'];

        // Update all answers for this question
        $answers = fetch_all("
            SELECT * FROM answers 
            WHERE room_id = $room_id AND question_id = $question_id
        ");

        foreach ($answers as $answer) {
            $is_correct = ($answer['selected_answer'] === $correct) ? 1 : 0;
            $points = $is_correct ? CORRECT_POINTS : WRONG_POINTS;

            query("UPDATE answers SET 
                   is_correct = $is_correct,
                   points_earned = $points
                   WHERE id = " . $answer['id']);

            // Update scores table
            $team_id = $answer['team_id'];
            $score_exists = fetch_single("
                SELECT * FROM scores 
                WHERE room_id = $room_id AND team_id = $team_id
            ");

            if ($score_exists) {
                $new_total = $score_exists['total_points'] + $points;
                $new_correct = $score_exists['correct_answers'] + ($is_correct ? 1 : 0);
                $new_wrong = $score_exists['wrong_answers'] + ($is_correct ? 0 : 1);

                query("UPDATE scores SET 
                       total_points = $new_total,
                       correct_answers = $new_correct,
                       wrong_answers = $new_wrong
                       WHERE room_id = $room_id AND team_id = $team_id");
            } else {
                query("INSERT INTO scores (room_id, team_id, total_points, correct_answers, wrong_answers)
                       VALUES ($room_id, $team_id, $points, " . ($is_correct ? 1 : 0) . ", " . ($is_correct ? 0 : 1) . ")");
            }
        }

        query("UPDATE rooms SET current_phase = 'result' WHERE id = $room_id");
        redirect('/admin/room.php?code=' . $code . '&phase=result');
    } elseif ($action === 'next_question') {
        query("UPDATE rooms SET 
               current_question_id = NULL,
               current_phase = 'idle',
               question_start_time = NULL
               WHERE id = $room_id");
        redirect('/admin/room.php?code=' . $code);
    } elseif ($action === 'finish_match') {
        query("UPDATE rooms SET status = 'finished' WHERE id = $room_id");
        query("UPDATE matches SET status = 'finished' WHERE id = " . $room['match_id']);

        // Set winner (highest score)
        $winner = fetch_single("
            SELECT team_id FROM scores 
            WHERE room_id = $room_id 
            ORDER BY total_points DESC, correct_answers DESC 
            LIMIT 1
        ");

        if ($winner) {
            query("UPDATE matches SET winner_team_id = " . $winner['team_id'] . " WHERE id = " . $room['match_id']);
        }

        redirect('/admin/room.php?code=' . $code . '&finished=1');
    }
}

// Get participants
$participants = fetch_all("
    SELECT rp.*, t.name as team_name
    FROM room_participants rp
    JOIN teams t ON rp.team_id = t.id
    WHERE rp.room_id = " . $room['id'] . "
    ORDER BY rp.joined_at ASC
");

// Get all questions for dropdown
$questions = fetch_all("SELECT id, question_text, category FROM questions ORDER BY id ASC");

// Get current question if exists
$current_question = null;
if ($room['current_question_id']) {
    $current_question = fetch_single("SELECT * FROM questions WHERE id = " . $room['current_question_id']);
}

// Get buzzer ranking for current question
$buzzer_ranking = [];
if ($room['current_question_id']) {
    $buzzer_ranking = fetch_all("
        SELECT b.*, t.name as team_name
        FROM buzzers b
        JOIN teams t ON b.team_id = t.id
        WHERE b.room_id = " . $room['id'] . " 
        AND b.question_id = " . $room['current_question_id'] . "
        ORDER BY b.buzzer_order ASC, b.buzz_time ASC
    ");
}

// Get answers for current question
$answers_data = [];
if ($room['current_question_id']) {
    $answers_data = fetch_all("
        SELECT a.*, t.name as team_name
        FROM answers a
        JOIN teams t ON a.team_id = t.id
        WHERE a.room_id = " . $room['id'] . "
        AND a.question_id = " . $room['current_question_id'] . "
    ");
}

// Get current scores
$scores = fetch_all("
    SELECT s.*, t.name as team_name
    FROM scores s
    JOIN teams t ON s.team_id = t.id
    WHERE s.room_id = " . $room['id'] . "
    ORDER BY s.total_points DESC, s.correct_answers DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Control: <?= htmlspecialchars($code) ?> - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .room-control {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-top: 20px;
        }

        @media (max-width: 1024px) {
            .room-control {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="page-header">
            <div>
                <h1>🚪 Room: <?= htmlspecialchars($code) ?></h1>
                <p><?= htmlspecialchars($room['match_name']) ?> - <?= htmlspecialchars($room['round']) ?></p>
            </div>
            <div>
                <span class="badge badge-<?= $room['status'] ?> badge-large">
                    Status: <?= strtoupper($room['status']) ?>
                </span>
            </div>
        </div>

        <?php if (isset($_GET['finished'])): ?>
            <div class="alert alert-success">
                <strong>✅ Pertandingan Selesai!</strong>
                <br>Pemenang telah ditentukan. Lihat skor final di bawah.
            </div>
        <?php endif; ?>

        <div class="room-control">
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Participants -->
                <div class="panel">
                    <h3>👥 Peserta (<?= count($participants) ?>)</h3>
                    <div class="participants-list">
                        <?php foreach ($participants as $p): ?>
                            <div class="participant-item">
                                <span class="participant-status online"></span>
                                <?= htmlspecialchars($p['team_name']) ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($participants)): ?>
                            <p class="text-muted">Belum ada peserta yang bergabung</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Scores -->
                <div class="panel">
                    <h3>📊 Skor Sementara</h3>
                    <div class="scores-list">
                        <?php foreach ($scores as $idx => $s): ?>
                            <div class="score-item rank-<?= $idx + 1 ?>">
                                <span class="rank">#<?= $idx + 1 ?></span>
                                <span class="team-name"><?= htmlspecialchars($s['team_name']) ?></span>
                                <span class="points"><?= $s['total_points'] ?> pts</span>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($scores)): ?>
                            <p class="text-muted">Belum ada skor</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Match Controls -->
                <div class="panel">
                    <h3>⚙️ Kontrol Match</h3>

                    <?php if ($room['status'] === 'waiting'): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="start_match">
                            <button type="submit" class="btn btn-success btn-block" <?= count($participants) < 2 ? 'disabled' : '' ?>>
                                ▶️ Mulai Pertandingan
                            </button>
                        </form>
                        <?php if (count($participants) < 2): ?>
                            <p class="text-muted"><small>Minimal 2 peserta harus join</small></p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($room['status'] === 'running' && $room['current_phase'] === 'idle'): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="set_question">
                            <div class="form-group">
                                <label>Pilih Soal:</label>
                                <select name="question_id" required class="form-control">
                                    <option value="">-- Pilih Soal --</option>
                                    <?php foreach ($questions as $q): ?>
                                        <option value="<?= $q['id'] ?>">
                                            #<?= $q['id'] ?> - <?= htmlspecialchars(substr($q['question_text'], 0, 50)) ?>...
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                🎯 Mulai Soal
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($room['status'] === 'running'): ?>
                        <form method="POST" action="" style="margin-top: 10px;">
                            <input type="hidden" name="action" value="finish_match">
                            <button type="submit" class="btn btn-danger btn-block"
                                onclick="return confirm('Akhiri pertandingan dan tentukan pemenang?')">
                                🏁 Akhiri Pertandingan
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Main Display Area (Proyektor) -->
            <div class="main-display">
                <?php if ($room['current_phase'] === 'idle' && $room['status'] === 'running'): ?>
                    <div class="display-waiting">
                        <h2>Menunggu soal berikutnya...</h2>
                        <p>Pilih soal dari panel sebelah kiri untuk memulai</p>
                    </div>

                <?php elseif ($room['current_phase'] === 'countdown'): ?>
                    <div class="display-countdown">
                        <h1>Bersiap-siap!</h1>
                        <div class="countdown-number" id="countdownDisplay">3</div>
                        <p>Soal akan dimulai...</p>
                    </div>

                    <script>
                        let countdown = 3;
                        const display = document.getElementById('countdownDisplay');
                        const interval = setInterval(() => {
                            countdown--;
                            if (countdown > 0) {
                                display.textContent = countdown;
                            } else {
                                clearInterval(interval);
                                // Auto open buzzer
                                document.getElementById('openBuzzerForm').submit();
                            }
                        }, 1000);
                    </script>

                    <form id="openBuzzerForm" method="POST" action="" style="display:none;">
                        <input type="hidden" name="action" value="open_buzzer">
                    </form>

                <?php elseif ($room['current_phase'] === 'buzzer'): ?>
                    <div class="display-buzzer">
                        <h1>🔔 BUZZER TERBUKA!</h1>
                        <p class="buzzer-instruction">Peserta: Tekan SPASI sekarang!</p>

                        <div class="buzzer-ranking">
                            <h3>Urutan Buzzer:</h3>
                            <?php foreach ($buzzer_ranking as $idx => $b): ?>
                                <div class="buzzer-rank rank-<?= $idx + 1 ?>">
                                    <span class="rank-number"><?= $idx + 1 ?></span>
                                    <span class="team-name"><?= htmlspecialchars($b['team_name']) ?></span>
                                    <span class="buzz-time"><?= number_format($b['buzz_time'], 3) ?>s</span>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($buzzer_ranking)): ?>
                                <p class="text-muted">Menunggu peserta menekan buzzer...</p>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="" style="margin-top: 20px;">
                            <input type="hidden" name="action" value="close_buzzer">
                            <button type="submit" class="btn btn-warning btn-large">
                                🔒 Tutup Buzzer & Buka Jawaban
                            </button>
                        </form>
                    </div>

                    <script>
                        // Auto-refresh buzzer ranking
                        setInterval(() => {
                            location.reload();
                        }, 2000);
                    </script>

                <?php elseif ($room['current_phase'] === 'answer'): ?>
                    <div class="display-answer">
                        <div class="question-display">
                            <h2><?= htmlspecialchars($current_question['question_text']) ?></h2>
                            <div class="options-display">
                                <div class="option-item">A. <?= htmlspecialchars($current_question['option_a']) ?></div>
                                <div class="option-item">B. <?= htmlspecialchars($current_question['option_b']) ?></div>
                                <div class="option-item">C. <?= htmlspecialchars($current_question['option_c']) ?></div>
                                <div class="option-item">D. <?= htmlspecialchars($current_question['option_d']) ?></div>
                                <div class="option-item">E. <?= htmlspecialchars($current_question['option_e']) ?></div>
                            </div>
                        </div>

                        <div class="answer-status">
                            <h3>Status Jawaban:</h3>
                            <?php foreach ($participants as $p): ?>
                                <?php
                                $answered = false;
                                foreach ($answers_data as $a) {
                                    if ($a['team_id'] == $p['team_id']) {
                                        $answered = true;
                                        break;
                                    }
                                }
                                ?>
                                <div class="answer-status-item <?= $answered ? 'answered' : 'waiting' ?>">
                                    <?= htmlspecialchars($p['team_name']) ?>:
                                    <?= $answered ? '✅ Sudah Jawab' : '⏳ Menunggu' ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="POST" action="" style="margin-top: 20px;">
                            <input type="hidden" name="action" value="reveal_answer">
                            <button type="submit" class="btn btn-primary btn-large">
                                👁️ Tampilkan Jawaban Benar
                            </button>
                        </form>
                    </div>

                    <script>
                        // Auto-refresh answer status
                        setInterval(() => {
                            location.reload();
                        }, 3000);
                    </script>

                <?php elseif ($room['current_phase'] === 'result'): ?>
                    <div class="display-result">
                        <h1>Jawaban Benar:</h1>
                        <div class="correct-answer">
                            <?= $current_question['correct_option'] ?>
                        </div>

                        <div class="results-grid">
                            <?php foreach ($answers_data as $a): ?>
                                <div class="result-item <?= $a['is_correct'] ? 'correct' : 'wrong' ?>">
                                    <span class="team-name"><?= htmlspecialchars($a['team_name']) ?></span>
                                    <span class="answer">Jawaban: <?= $a['selected_answer'] ?></span>
                                    <span class="status">
                                        <?= $a['is_correct'] ? '✅ BENAR (+' . CORRECT_POINTS . ' poin)' : '❌ SALAH' ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="POST" action="" style="margin-top: 20px;">
                            <input type="hidden" name="action" value="next_question">
                            <button type="submit" class="btn btn-success btn-large">
                                ➡️ Soal Berikutnya
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <div class="display-waiting">
                        <h1>Selamat Datang!</h1>
                        <h2>Room Code: <?= htmlspecialchars($code) ?></h2>
                        <p>Peserta dapat join dengan kode ini</p>
                        <?php if ($room['status'] === 'waiting'): ?>
                            <p class="text-muted">Klik "Mulai Pertandingan" untuk memulai</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="/assets/js/admin-room.js"></script>
</body>

</html>