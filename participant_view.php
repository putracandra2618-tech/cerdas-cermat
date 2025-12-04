<?php
session_start();
require_once 'config.php';

// Set participant session if not set
if (!isset($_SESSION['user_type'])) {
    $_SESSION['user_type'] = 'participant';
}

$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

if (!$match_id) {
    // Show match selection for participants
    $active_matches = fetch_all("SELECT m.*, COUNT(mt.team_id) as team_count 
                                FROM matches m 
                                JOIN match_teams mt ON m.id = mt.match_id 
                                WHERE m.status IN ('waiting', 'active')
                                GROUP BY m.id 
                                ORDER BY m.created_at DESC");
} else {
    // Get specific match details
    $match = fetch_single("SELECT * FROM matches WHERE id = $match_id");
    if (!$match) {
        header("Location: participant_view.php");
        exit;
    }
    
    // Get current question
    $current_question_num = $match['current_question'] + 1;
    $question = fetch_single("SELECT * FROM questions ORDER BY id LIMIT " . ($current_question_num - 1) . ", 1");
    
    // Get teams and scores
    $teams = fetch_all("SELECT t.*, mt.score FROM teams t 
                       JOIN match_teams mt ON t.id = mt.team_id 
                       WHERE mt.match_id = $match_id 
                       ORDER BY mt.score DESC, t.name");
    
    // Get buzzer responses
    $buzzer_responses = fetch_all("SELECT br.*, t.name as team_name 
                                  FROM buzzer_responses br 
                                  JOIN teams t ON br.team_id = t.id 
                                  WHERE br.match_id = $match_id AND br.question_id = " . ($question['id'] ?? 0) . " 
                                  ORDER BY br.buzzer_order");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tampilan Peserta - Cerdas Cermat</title>
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
        
        .participant-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        
        .participant-header h1 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        
        .participant-header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .teacher-link {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9em;
            transition: background 0.3s;
        }
        
        .teacher-link:hover {
            background: rgba(255,255,255,0.3);
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
            font-size: 1.1em;
            transition: all 0.3s;
        }
        
        .option.correct {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .option.incorrect {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
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
        
        .match-selection {
            text-align: center;
            padding: 40px;
        }
        
        .match-selection h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2em;
        }
        
        .match-list {
            display: grid;
            gap: 15px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .match-item {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }
        
        .match-item:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .match-item h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .match-item p {
            color: #666;
            margin-bottom: 5px;
        }
        
        .match-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .status-waiting {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-matches {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-matches h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .timer {
                font-size: 2em;
            }
            
            .teacher-link {
                position: static;
                display: inline-block;
                margin-top: 10px;
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="participant-header">
            <h1>🎯 Cerdas Cermat</h1>
            <p>Tampilan Peserta - Ikuti pertandingan secara real-time</p>
            <a href="login.php" class="teacher-link">Login Guru</a>
        </div>
        
        <?php if (!$match_id): ?>
            <!-- Match Selection for Participants -->
            <div class="match-selection">
                <h2>Pilih Pertandingan</h2>
                <?php if (count($active_matches) > 0): ?>
                    <div class="match-list">
                        <?php foreach ($active_matches as $match): ?>
                            <a href="?match_id=<?php echo $match['id']; ?>" class="match-item">
                                <h3><?php echo htmlspecialchars($match['name']); ?></h3>
                                <p>📋 <?php echo $match['total_questions']; ?> soal</p>
                                <p>👥 <?php echo $match['team_count']; ?> tim</p>
                                <span class="match-status status-<?php echo $match['status']; ?>">
                                    <?php echo ucfirst($match['status']); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-matches">
                        <h3>Belum ada pertandingan aktif</h3>
                        <p>Silakan hubungi guru untuk memulai pertandingan baru.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Active Match View -->
            <div class="main-content">
                <div class="question-section">
                    <?php if ($question): ?>
                        <div class="timer" id="timer">0.00</div>
                        
                        <div class="buzzer-instruction">
                            🔊 Tekan SPASI untuk membunyikan buzzer!
                        </div>
                        
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
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px;">
                            <h2>🏆 Pertandingan Selesai!</h2>
                            <p>Semua soal telah dijawab.</p>
                            <a href="final_ranking.php?match_id=<?php echo $match_id; ?>" class="btn btn-success" style="display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 6px;">
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
        <?php endif; ?>
    </div>
    
    <script>
        let startTime = null;
        let timerInterval = null;
        let buzzerPressed = false;
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
        
        // Buzzer functionality for participants
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
                    // Show success message
                    document.querySelector('.buzzer-instruction').innerHTML = 
                        '✅ Buzzer terkirim! Urutan: #' + data.buzzer_order;
                    document.querySelector('.buzzer-instruction').style.background = '#d4edda';
                    document.querySelector('.buzzer-instruction').style.color = '#155724';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                buzzerPressed = false;
            });
        }
        
        // Spacebar detection
        document.addEventListener('keydown', function(event) {
            if (event.code === 'Space' && !buzzerPressed && currentQuestionId) {
                event.preventDefault();
                handleBuzzerPress();
            }
        });
        
        // Auto refresh every 5 seconds to update scores and buzzer order
        setInterval(function() {
            if (matchId) {
                location.reload();
            }
        }, 5000);
        
        // Start timer on page load
        if (currentQuestionId) {
            startTimer();
        }
    </script>
</body>
</html>