<?php
require_once '../config.php';

if (!is_participant()) {
    redirect('/participant/join.php');
}

$room_id = get_participant_room_id();
$team_id = get_participant_team_id();

// Get room, team info
$room = fetch_single("SELECT * FROM rooms WHERE id = $room_id");
$team = fetch_single("SELECT * FROM teams WHERE id = $team_id");

if (!$room || !$team) {
    redirect('/participant/join.php');
}

if ($room['status'] === 'waiting') {
    redirect('/participant/lobby.php');
}

if ($room['status'] === 'finished') {
    redirect('/participant/finished.php');
}

// Get current question
$current_question = null;
if ($room['current_question_id']) {
    $current_question = fetch_single("SELECT id, question_text, option_a, option_b, option_c, option_d, option_e FROM questions WHERE id = " . $room['current_question_id']);
}

// Check if already buzzed for current question
$already_buzzed = false;
if ($room['current_question_id']) {
    $buzz = fetch_single("SELECT * FROM buzzers WHERE room_id = $room_id AND question_id = " . $room['current_question_id'] . " AND team_id = $team_id");
    $already_buzzed = $buzz ? true : false;
}

// Check if already answered
$already_answered = false;
$my_answer = null;
if ($room['current_question_id']) {
    $my_answer = fetch_single("SELECT * FROM answers WHERE room_id = $room_id AND question_id = " . $room['current_question_id'] . " AND team_id = $team_id");
    $already_answered = $my_answer ? true : false;
}

// Get current score
$my_score = fetch_single("SELECT * FROM scores WHERE room_id = $room_id AND team_id = $team_id");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/participant.css">
</head>

<body class="play-page">
    <div class="play-header">
        <div class="header-left">
            <span class="team-badge"><?= htmlspecialchars($team['name']) ?></span>
        </div>
        <div class="header-right">
            <span class="score-badge">
                Skor: <?= $my_score['total_points'] ?? 0 ?>
                (<?= $my_score['correct_answers'] ?? 0 ?> benar)
            </span>
        </div>
    </div>

    <div class="play-container">
        <?php if ($room['current_phase'] === 'idle'): ?>
            <div class="play-idle">
                <div class="idle-icon">⏳</div>
                <h1>Menunggu Soal Berikutnya...</h1>
                <p>Panitia akan segera memilih soal</p>
            </div>

        <?php elseif ($room['current_phase'] === 'countdown'): ?>
            <div class="play-countdown">
                <h1>Bersiap!</h1>
                <div class="countdown-display" id="countdownNumber">3</div>
                <p>Pertandingan akan dimulai...</p>
            </div>

            <script>
                let countdown = <?= $room['countdown_value'] ?>;
                const display = document.getElementById('countdownNumber');

                const interval = setInterval(() => {
                    countdown--;
                    if (countdown > 0) {
                        display.textContent = countdown;
                    } else {
                        clearInterval(interval);
                        // Wait a bit then reload to buzzer phase
                        setTimeout(() => location.reload(), 500);
                    }
                }, 1000);
            </script>

        <?php elseif ($room['current_phase'] === 'buzzer'): ?>
            <div class="play-buzzer">
                <?php if (!$already_buzzed): ?>
                    <h1 class="buzzer-title">🔔 TEKAN SPASI SEKARANG!</h1>
                    <div class="buzzer-instruction">Jadilah yang tercepat!</div>

                    <div id="buzzerStatus" class="buzzer-status"></div>
                <?php else: ?>
                    <h1 class="buzzer-title">✅ Buzzer Terkirim!</h1>
                    <div class="buzzer-instruction">Menunggu fase jawaban...</div>
                <?php endif; ?>
            </div>

            <script>
                const roomId = <?= $room_id ?>;
                const questionId = <?= $room['current_question_id'] ?>;
                const teamId = <?= $team_id ?>;
                let buzzerSent = <?= $already_buzzed ? 'true' : 'false' ?>;

                // Spacebar listener
                document.addEventListener('keydown', function (e) {
                    if (e.code === 'Space' && !buzzerSent) {
                        e.preventDefault();
                        sendBuzzer();
                    }
                });

                function sendBuzzer() {
                    buzzerSent = true;
                    document.getElementById('buzzerStatus').innerHTML = '<div class="loading">Mengirim buzzer...</div>';

                    fetch('/api/buzzer.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            room_id: roomId,
                            question_id: questionId,
                            team_id: teamId
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('buzzerStatus').innerHTML =
                                    '<div class="success">✅ Buzzer diterima! Urutan ke-' + data.data.order + '</div>';
                            } else {
                                document.getElementById('buzzerStatus').innerHTML =
                                    '<div class="error">❌ ' + data.error + '</div>';
                            }
                        })
                        .catch(error => {
                            document.getElementById('buzzerStatus').innerHTML =
                                '<div class="error">❌ Gagal mengirim buzzer</div>';
                            buzzerSent = false;
                        });
                }

                // Poll for phase change
                setInterval(() => {
                    fetch('/api/room_status.php?room_id=' + roomId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data.current_phase !== 'buzzer') {
                                location.reload();
                            }
                        });
                }, 2000);
            </script>

        <?php elseif ($room['current_phase'] === 'answer'): ?>
            <div class="play-answer">
                <div class="question-box">
                    <h2><?= htmlspecialchars($current_question['question_text']) ?></h2>
                </div>

                <?php if (!$already_answered): ?>
                    <div class="options-grid" id="optionsGrid">
                        <button class="option-btn" data-option="A">
                            <span class="option-letter">A</span>
                            <span class="option-text"><?= htmlspecialchars($current_question['option_a']) ?></span>
                        </button>
                        <button class="option-btn" data-option="B">
                            <span class="option-letter">B</span>
                            <span class="option-text"><?= htmlspecialchars($current_question['option_b']) ?></span>
                        </button>
                        <button class="option-btn" data-option="C">
                            <span class="option-letter">C</span>
                            <span class="option-text"><?= htmlspecialchars($current_question['option_c']) ?></span>
                        </button>
                        <button class="option-btn" data-option="D">
                            <span class="option-letter">D</span>
                            <span class="option-text"><?= htmlspecialchars($current_question['option_d']) ?></span>
                        </button>
                        <button class="option-btn" data-option="E">
                            <span class="option-letter">E</span>
                            <span class="option-text"><?= htmlspecialchars($current_question['option_e']) ?></span>
                        </button>
                    </div>

                    <div id="answerStatus"></div>
                <?php else: ?>
                    <div class="answer-submitted">
                        <div class="submitted-icon">✅</div>
                        <h2>Jawaban Terkirim!</h2>
                        <p>Anda memilih: <strong><?= $my_answer['selected_answer'] ?></strong></p>
                        <p class="wait-message">Menunggu hasil dari panitia...</p>
                    </div>
                <?php endif; ?>
            </div>

            <script>
                const buttons = document.querySelectorAll('.option-btn');
                let answered = <?= $already_answered ? 'true' : 'false' ?>;

                buttons.forEach(btn => {
                    btn.addEventListener('click', function () {
                        if (answered) return;

                        const option = this.dataset.option;
                        submitAnswer(option);
                    });
                });

                function submitAnswer(choice) {
                    answered = true;
                    buttons.forEach(btn => btn.disabled = true);

                    document.getElementById('answerStatus').innerHTML = '<div class="loading">Mengirim jawaban...</div>';

                    fetch('/api/answer.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            room_id: <?= $room_id ?>,
                            question_id: <?= $room['current_question_id'] ?>,
                            team_id: <?= $team_id ?>,
                            choice: choice
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                document.getElementById('answerStatus').innerHTML =
                                    '<div class="error">❌ ' + data.error + '</div>';
                                answered = false;
                                buttons.forEach(btn => btn.disabled = false);
                            }
                        })
                        .catch(error => {
                            document.getElementById('answerStatus').innerHTML =
                                '<div class="error">❌ Gagal mengirim jawaban</div>';
                            answered = false;
                            buttons.forEach(btn => btn.disabled = false);
                        });
                }

                // Poll for phase change
                setInterval(() => {
                    fetch('/api/room_status.php?room_id=<?= $room_id ?>')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data.current_phase !== 'answer') {
                                location.reload();
                            }
                        });
                }, 2000);
            </script>

        <?php elseif ($room['current_phase'] === 'result'): ?>
            <div class="play-result">
                <?php
                // Get correct answer
                $full_question = fetch_single("SELECT correct_option FROM questions WHERE id = " . $room['current_question_id']);
                $correct_answer = $full_question['correct_option'];

                $is_correct = ($my_answer && $my_answer['selected_answer'] === $correct_answer);
                ?>

                <div class="result-header <?= $is_correct ? 'correct' : 'wrong' ?>">
                    <?php if ($is_correct): ?>
                        <div class="result-icon">🎉</div>
                        <h1>BENAR!</h1>
                        <p>+<?= CORRECT_POINTS ?> poin</p>
                    <?php else: ?>
                        <div class="result-icon">😞</div>
                        <h1>SALAH</h1>
                        <p>Jawaban Anda: <?= $my_answer ? $my_answer['selected_answer'] : '-' ?></p>
                    <?php endif; ?>
                </div>

                <div class="correct-answer-display">
                    <strong>Jawaban Benar:</strong>
                    <span class="answer-badge"><?= $correct_answer ?></span>
                </div>

                <div class="score-update">
                    <h3>Skor Anda Sekarang:</h3>
                    <div class="score-value"><?= $my_score['total_points'] ?? 0 ?> poin</div>
                    <p>
                        <?= $my_score['correct_answers'] ?? 0 ?> benar |
                        <?= $my_score['wrong_answers'] ?? 0 ?> salah
                    </p>
                </div>

                <p class="next-message">Menunggu soal berikutnya...</p>
            </div>

            <script>
                // Poll for next question
                setInterval(() => {
                    fetch('/api/room_status.php?room_id=<?= $room_id ?>')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (data.data.status === 'finished') {
                                    window.location.href = '/participant/finished.php';
                                } else if (data.data.current_phase !== 'result') {
                                    location.reload();
                                }
                            }
                        });
                }, 2000);
            </script>

        <?php endif; ?>
    </div>
</body>

</html>