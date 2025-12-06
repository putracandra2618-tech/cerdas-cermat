<?php
require_once '../config.php';
require_admin();

// Get all rooms with match info
$rooms = fetch_all("
    SELECT r.*, 
           m.name as match_name, 
           m.round,
           COUNT(DISTINCT rp.id) as participant_count
    FROM rooms r
    JOIN matches m ON r.match_id = m.id
    LEFT JOIN room_participants rp ON r.id = rp.room_id
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Rooms - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Semua Rooms</h1>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode Room</th>
                        <th>Match</th>
                        <th>Round</th>
                        <th>Status</th>
                        <th>Peserta</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($room['code']) ?></strong></td>
                            <td><?= htmlspecialchars($room['match_name']) ?></td>
                            <td><?= htmlspecialchars($room['round']) ?></td>
                            <td>
                                <span class="badge badge-<?= $room['status'] ?>">
                                    <?= strtoupper($room['status']) ?>
                                </span>
                            </td>
                            <td><?= $room['participant_count'] ?> tim</td>
                            <td><?= date('d/m/Y H:i', strtotime($room['created_at'])) ?></td>
                            <td>
                                <a href="/admin/room.php?code=<?= $room['code'] ?>" class="btn btn-sm btn-primary">
                                    Buka Room
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:2rem;">
                                Belum ada room. Buat room dari halaman <a href="brackets.php">Bracket</a>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>