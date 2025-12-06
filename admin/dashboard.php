<?php
require_once '../config.php';
require_admin();

// Get statistics
$total_teams = fetch_single("SELECT COUNT(*) as count FROM teams")['count'];
$total_questions = fetch_single("SELECT COUNT(*) as count FROM questions")['count'];
$total_matches = fetch_single("SELECT COUNT(*) as count FROM matches")['count'];
$total_rooms = fetch_single("SELECT COUNT(*) as count FROM rooms")['count'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Panitia - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Dashboard Panitia</h1>
            <p>Selamat datang, <?= htmlspecialchars($_SESSION['admin_username']) ?>!</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?= $total_teams ?></div>
                <div class="stat-label">Total Tim</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-value"><?= $total_questions ?></div>
                <div class="stat-label">Total Soal</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-value"><?= $total_matches ?></div>
                <div class="stat-label">Total Match</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🚪</div>
                <div class="stat-value"><?= $total_rooms ?></div>
                <div class="stat-label">Total Room</div>
            </div>
        </div>

        <div class="menu-grid">
            <a href="teams.php" class="menu-card">
                <div class="menu-icon">👥</div>
                <h3>Manajemen Tim</h3>
                <p>Kelola tim peserta pertandingan</p>
            </a>

            <a href="questions.php" class="menu-card">
                <div class="menu-icon">📝</div>
                <h3>Manajemen Soal</h3>
                <p>Kelola bank soal pilihan ganda</p>
            </a>

            <a href="brackets.php" class="menu-card">
                <div class="menu-icon">🏆</div>
                <h3>Bracket & Matches</h3>
                <p>Buat dan kelola pertandingan</p>
            </a>

            <a href="rooms.php" class="menu-card">
                <div class="menu-icon">🚪</div>
                <h3>Rooms</h3>
                <p>Lihat semua room yang dibuat</p>
            </a>
        </div>
    </div>
</body>

</html>