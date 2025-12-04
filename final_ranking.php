<?php
require_once 'config.php';

$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

if (!$match_id) {
    header("Location: index.php");
    exit;
}

// Get match details
$match = fetch_single("SELECT * FROM matches WHERE id = $match_id");
if (!$match) {
    header("Location: index.php");
    exit;
}

// Get final ranking
$final_ranking = fetch_all("SELECT t.name as team_name, mt.score, 
                           (SELECT COUNT(*) FROM team_answers ta 
                            WHERE ta.match_id = mt.match_id AND ta.team_id = mt.team_id AND ta.is_correct = 1) as correct_answers
                           FROM match_teams mt
                           JOIN teams t ON mt.team_id = t.id
                           WHERE mt.match_id = $match_id
                           ORDER BY mt.score DESC, correct_answers DESC");

// Get match statistics
$total_questions = $match['total_questions'];
$questions_answered = fetch_single("SELECT COUNT(DISTINCT question_id) as count 
                                   FROM team_answers 
                                   WHERE match_id = $match_id")['count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerdas Cermat - Peringkat Akhir</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 800px;
            width: 100%;
            text-align: center;
        }
        
        .trophy {
            font-size: 4em;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .match-name {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 30px;
        }
        
        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .ranking-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            font-weight: bold;
            text-align: center;
        }
        
        .ranking-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        
        .ranking-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .ranking-table tr:hover {
            background: #e3f2fd;
            transform: scale(1.02);
            transition: all 0.3s;
        }
        
        .rank-1 {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%) !important;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%) !important;
            font-weight: bold;
        }
        
        .rank-3 {
            background: linear-gradient(135deg, #cd7f32 0%, #e6a65d 100%) !important;
            font-weight: bold;
        }
        
        .medal {
            font-size: 1.5em;
            margin-right: 5px;
        }
        
        .stats {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            display: block;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .winner-announcement {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
        }
        
        .winner-text {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .winner-team {
            font-size: 2em;
            color: #d4af37;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 2em;
            }
            
            .ranking-table {
                font-size: 0.9em;
            }
            
            .ranking-table th,
            .ranking-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="trophy">🏆</div>
        <h1>Pertandingan Selesai!</h1>
        <div class="match-name"><?php echo htmlspecialchars($match['name']); ?></div>
        
        <?php if (count($final_ranking) > 0): ?>
            <div class="winner-announcement">
                <div class="winner-text">🎉 Juara Pertandingan:</div>
                <div class="winner-team"><?php echo htmlspecialchars($final_ranking[0]['team_name']); ?></div>
                <div style="font-size: 1.2em; margin-top: 10px;">
                    dengan skor <?php echo $final_ranking[0]['score']; ?> poin
                </div>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo $total_questions; ?></span>
                <span class="stat-label">Total Soal</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $questions_answered; ?></span>
                <span class="stat-label">Soal Dijawab</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count($final_ranking); ?></span>
                <span class="stat-label">Tim Bermain</span>
            </div>
        </div>
        
        <h2 style="margin-bottom: 20px; color: #333;">Peringkat Akhir</h2>
        
        <table class="ranking-table">
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Tim</th>
                    <th>Skor</th>
                    <th>Jawaban Benar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($final_ranking as $index => $team): ?>
                    <tr class="rank-<?php echo $index + 1; ?>">
                        <td>
                            <?php if ($index === 0): ?>
                                <span class="medal">🥇</span>
                            <?php elseif ($index === 1): ?>
                                <span class="medal">🥈</span>
                            <?php elseif ($index === 2): ?>
                                <span class="medal">🥉</span>
                            <?php else: ?>
                                <?php echo $index + 1; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                        <td><strong><?php echo $team['score']; ?></strong> poin</td>
                        <td><?php echo $team['correct_answers']; ?> soal</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="action-buttons">
            <a href="game.php?match_id=<?php echo $match_id; ?>" class="btn">
                📊 Lihat Detail Pertandingan
            </a>
            <a href="index.php" class="btn btn-secondary">
                🏠 Kembali ke Beranda
            </a>
            <a href="index.php" class="btn">
                🎯 Pertandingan Baru
            </a>
        </div>
    </div>
    
    <script>
        // Add some animation to the winner announcement
        setTimeout(() => {
            const winnerAnnouncement = document.querySelector('.winner-announcement');
            if (winnerAnnouncement) {
                winnerAnnouncement.style.animation = 'bounce 1s ease-in-out';
            }
        }, 500);
        
        // Confetti effect (simple version)
        function createConfetti() {
            const colors = ['#ffd700', '#ffed4e', '#667eea', '#764ba2', '#28a745', '#dc3545'];
            const container = document.querySelector('.container');
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'absolute';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.top = '-10px';
                confetti.style.borderRadius = '50%';
                confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;
                
                container.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
        }
        
        // Add CSS animation for confetti
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Trigger confetti after 1 second
        setTimeout(createConfetti, 1000);
        setTimeout(createConfetti, 2000);
    </script>
</body>
</html>