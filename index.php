<?php
require_once 'config.php';

// Require teacher login
require_teacher();

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}

// Get all teams
$teams = fetch_all("SELECT * FROM teams ORDER BY name");

// Get all questions count
$total_questions = fetch_single("SELECT COUNT(*) as count FROM questions")['count'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_name = escape($_POST['match_name']);
    $selected_teams = $_POST['teams'] ?? [];
    $question_count = intval($_POST['question_count']);
    
    if (count($selected_teams) >= 2 && $question_count > 0) {
        begin_transaction();
        
        try {
            // Create match
            $sql = "INSERT INTO matches (name, total_questions) VALUES ('$match_name', $question_count)";
            query($sql);
            $match_id = get_insert_id();
            
            // Add teams to match
            foreach ($selected_teams as $team_id) {
                $sql = "INSERT INTO match_teams (match_id, team_id) VALUES ($match_id, $team_id)";
                query($sql);
            }
            
            commit();
            
            // Redirect to game page
            header("Location: game.php?match_id=$match_id");
            exit;
            
        } catch (Exception $e) {
            rollback();
            $error = "Error creating match: " . $e->getMessage();
        }
    } else {
        $error = "Please select at least 2 teams and specify number of questions.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerdas Cermat - Pilih Pertandingan</title>
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
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
            font-size: 1.1em;
        }
        
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus, input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .teams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .team-checkbox {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .team-checkbox:hover {
            background: #e9ecef;
            border-color: #667eea;
        }
        
        .team-checkbox input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .team-checkbox.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #bee5eb;
        }
        
        .teacher-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -40px -40px 20px -40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .teacher-info {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 2em;
            }
            
            .teacher-header {
                margin: -20px -20px 15px -20px;
                padding: 12px 15px;
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="teacher-header">
            <div class="teacher-info">
                <span>👨‍🏫 Guru: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>
        <h1>🎯 Cerdas Cermat</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <strong>Petunjuk:</strong> Pilih minimal 2 tim dan jumlah soal yang ingin dimainkan.
        </div>
        
        <form method="POST" id="matchForm">
            <div class="form-group">
                <label for="match_name">Nama Pertandingan:</label>
                <input type="text" id="match_name" name="match_name" required 
                       placeholder="Contoh: Babak Penyisihan Grup A">
            </div>
            
            <div class="form-group">
                <label>Pilih Tim yang Bertanding:</label>
                <div class="teams-grid">
                    <?php foreach ($teams as $team): ?>
                        <label class="team-checkbox">
                            <input type="checkbox" name="teams[]" value="<?php echo $team['id']; ?>">
                            <span><?php echo htmlspecialchars($team['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="question_count">Jumlah Soal:</label>
                <input type="number" id="question_count" name="question_count" 
                       min="1" max="<?php echo $total_questions; ?>" required
                       placeholder="Maksimal <?php echo $total_questions; ?> soal tersedia">
            </div>
            
            <button type="submit" class="submit-btn">Mulai Pertandingan</button>
        </form>
    </div>
    
    <script>
        // Add visual feedback for team selection
        document.querySelectorAll('.team-checkbox').forEach(checkbox => {
            checkbox.addEventListener('click', function() {
                this.classList.toggle('selected');
            });
        });
        
        // Form validation
        document.getElementById('matchForm').addEventListener('submit', function(e) {
            const selectedTeams = document.querySelectorAll('input[name="teams[]"]:checked');
            if (selectedTeams.length < 2) {
                e.preventDefault();
                alert('Pilih minimal 2 tim untuk memulai pertandingan!');
                return false;
            }
        });
    </script>
</body>
</html>