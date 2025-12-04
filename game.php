<?php
require_once 'config.php';

$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

if (!$match_id) {
    if (is_teacher()) {
        header("Location: index.php");
    } else {
        header("Location: participant_view.php");
    }
    exit;
}

// Get match details
$match = fetch_single("SELECT * FROM matches WHERE id = $match_id");
if (!$match) {
    header("Location: index.php");
    exit;
}

// Get teams in this match
$teams = fetch_all("SELECT t.*, mt.score FROM teams t 
                   JOIN match_teams mt ON t.id = mt.team_id 
                   WHERE mt.match_id = $match_id 
                   ORDER BY t.name");

// Get current question
$current_question_num = $match['current_question'] + 1;
$question = fetch_single("SELECT * FROM questions ORDER BY id LIMIT " . ($current_question_num - 1) . ", 1");

// Get buzzer responses for current question
$buzzer_responses = fetch_all("SELECT br.*, t.name as team_name 
                              FROM buzzer_responses br 
                              JOIN teams t ON br.team_id = t.id 
                              WHERE br.match_id = $match_id AND br.question_id = " . ($question['id'] ?? 0) . " 
                              ORDER BY br.buzzer_order");

// Check if all teams have answered
$total_teams = count($teams);
$answered_teams = count($buzzer_responses);
$all_answered = ($answered_teams >= $total_teams);

// Get team answers for current question
$team_answers = fetch_all("SELECT ta.*, t.name as team_name 
                          FROM team_answers ta 
                          JOIN teams t ON ta.team_id = t.id 
                          WHERE ta.match_id = $match_id AND ta.question_id = " . ($question['id'] ?? 0));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerdas Cermat - Pertandingan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 2em;
            margin: 0;
        }
        
        .teacher-controls, .participant-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.9em;
        }
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .match-info {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            padding: 20px;
        }
        
        .question-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        
        .timer {
            font-size: 3em;
            font-weight: bold;
            text-align: center;
            color: #667eea;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
        }
        
        .question {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .question h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .question-text {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #555;
        }
        
        .options {
            display: grid;
            gap: 10px;
        }
        
        .option {
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.1em;
        }
        
        .option:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .option.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .scoreboard {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .scoreboard h3 {
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .team-score {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            font-weight: bold;
        }
        
        .buzzer-order {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .buzzer-order h3 {
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .buzzer-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            margin-bottom: 5px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.9em;
        }
        
        .buzzer-item:first-child {
            background: #d4edda;
            color: #155724;
            font-weight: bold;
        }
        
        .controls {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 1em;
            margin: 5px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .buzzer-instruction {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            color: #856404;
        }
        
        .teacher-instruction {
            background: #cce7ff;
            border: 1px solid #80bdff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            color: #004085;
        }
        
        .hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .teacher-controls, .participant-info {
                justify-content: center;
            }
            
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .timer {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <h1>🎯 Cerdas Cermat</h1>
                <?php if (is_teacher()): ?>
                    <div class="teacher-controls">
                        <span>👨‍🏫 Mode: Guru</span>
                        <a href="index.php" class="btn-back">← Kembali</a>
                    </div>
                <?php else: ?>
                    <div class="participant-info">
                        <span>👥 Mode: Peserta</span>
                        <a href="participant_view.php" class="btn-back">← Daftar Pertandingan</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="match-info">
                <?php echo htmlspecialchars($match['name']); ?> | 
                Soal <?php echo $current_question_num; ?> dari <?php echo $match['total_questions']; ?>
            </div>
        </div>
        
        <div class="main-content">
            <div class="question-section">
                <?php if ($question): ?>
                    <div class="timer" id="timer">0.00</div>
                    
                    <?php if (!is_teacher()): ?>
                    <div class="buzzer-instruction" id="buzzerInstruction">
                        🔊 Tekan SPASI untuk membunyikan buzzer!
                    </div>
                    <?php else: ?>
                    <div class="teacher-instruction">
                        🔊 Tampilan Guru - Peserta akan menekan SPASI untuk buzzer
                    </div>
                    <?php endif; ?>
                    
                    <div class="question">
                        <h2>Soal #<?php echo $current_question_num; ?></h2>
                        <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
                        
                        <div class="options" id="optionsContainer">
                            <div class="option" data-answer="A">A. <?php echo htmlspecialchars($question['option_a']); ?></div>
                            <div class="option" data-answer="B">B. <?php echo htmlspecialchars($question['option_b']); ?></div>
                            <div class="option" data-answer="C">C. <?php echo htmlspecialchars($question['option_c']); ?></div>
                            <div class="option" data-answer="D">D. <?php echo htmlspecialchars($question['option_d']); ?></div>
                        </div>
                    </div>
                    
                    <?php if (is_teacher()): ?>
                    <div class="controls">
                        <button class="btn btn-success" id="showAnswerBtn" 
                                <?php echo $all_answered ? '' : 'disabled'; ?>>
                            Tampilkan Jawaban Benar
                        </button>
                        <button class="btn" id="nextQuestionBtn" style="display: none;">
                            Soal Selanjutnya
                        </button>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <h2>🏆 Pertandingan Selesai!</h2>
                        <p>Semua soal telah dijawab.</p>
                        <a href="final_ranking.php?match_id=<?php echo $match_id; ?>" class="btn btn-success">
                            Lihat Peringkat Akhir
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sidebar">
                <div class="scoreboard">
                    <h3>🏆 Papan Skor</h3>
                    <?php foreach ($teams as $team): ?>
                        <div class="team-score">
                            <span><?php echo htmlspecialchars($team['name']); ?></span>
                            <span><?php echo $team['score']; ?> pts</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="buzzer-order">
                    <h3>⚡ Urutan Buzzer</h3>
                    <?php if (count($buzzer_responses) > 0): ?>
                        <?php foreach ($buzzer_responses as $index => $response): ?>
                            <div class="buzzer-item">
                                <span><?php echo ($index + 1) . '. ' . htmlspecialchars($response['team_name']); ?></span>
                                <span><?php echo number_format($response['response_time'], 2); ?>s</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; font-style: italic;">
                            Belum ada tim yang menekan buzzer
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let startTime = null;
        let timerInterval = null;
        let buzzerPressed = false;
        let buzzerOrder = [];
        let currentQuestionId = <?php echo $question['id'] ?? 0; ?>;
        let matchId = <?php echo $match_id; ?>;
        
        // Timer functionality
        function startTimer() {
            startTime = Date.now();
            timerInterval = setInterval(updateTimer, 10);
        }
        
        function updateTimer() {
            if (!startTime) return;
            
            const elapsed = (Date.now() - startTime) / 1000;
            document.getElementById('timer').textContent = elapsed.toFixed(2);
        }
        
        function stopTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
        }
        
        // Buzzer functionality
        function handleBuzzerPress() {
            if (buzzerPressed || !currentQuestionId) return;
            
            const responseTime = (Date.now() - startTime) / 1000;
            buzzerPressed = true;
            
            // Send buzzer response to server
            fetch('api/buzzer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    match_id: matchId,
                    question_id: currentQuestionId,
                    response_time: responseTime
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to update buzzer order
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                buzzerPressed = false;
            });
        }
        
        // Spacebar detection (only for participants)
        <?php if (!is_teacher()): ?>
        document.addEventListener('keydown', function(event) {
            if (event.code === 'Space' && !buzzerPressed && currentQuestionId) {
                event.preventDefault();
                handleBuzzerPress();
            }
        });
        <?php endif; ?>
        
        // Option selection (only for teachers)
        <?php if (is_teacher()): ?>
        document.querySelectorAll('.option').forEach(option => {
            option.addEventListener('click', function() {
                if (buzzerPressed) {
                    // Remove previous selection
                    document.querySelectorAll('.option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Add selection
                    this.classList.add('selected');
                    
                    // Submit answer
                    const answer = this.dataset.answer;
                    submitAnswer(answer);
                }
            });
        });
        
        function submitAnswer(answer) {
            fetch('api/submit_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    match_id: matchId,
                    question_id: currentQuestionId,
                    answer: answer
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Check if all teams have answered
                    checkAllAnswered();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        function checkAllAnswered() {
            fetch('api/check_answered.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    match_id: matchId,
                    question_id: currentQuestionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.all_answered) {
                    document.getElementById('showAnswerBtn').disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        <?php endif; // End of teacher-only option selection ?>
        
        // Show answer button (teacher only)
        <?php if (is_teacher()): ?>
        document.getElementById('showAnswerBtn')?.addEventListener('click', function() {
            fetch('api/show_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    match_id: matchId,
                    question_id: currentQuestionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show correct answer
                    const correctAnswer = data.correct_answer;
                    document.querySelectorAll('.option').forEach(option => {
                        if (option.dataset.answer === correctAnswer) {
                            option.style.background = '#28a745';
                            option.style.color = 'white';
                            option.style.borderColor = '#28a745';
                        }
                    });
                    
                    // Update scores
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
        <?php endif; // End of teacher-only show answer ?>
        
        // Start timer on page load
        if (currentQuestionId) {
            startTimer();
        }
    </script>
</body>
</html>