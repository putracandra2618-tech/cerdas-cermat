<?php
require_once '../config.php';
require_admin();

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = escape($_POST['name']);
        query("INSERT INTO teams (name) VALUES ('$name')");
        redirect('/admin/teams.php?success=added');
    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $name = escape($_POST['name']);
        query("UPDATE teams SET name = '$name' WHERE id = $id");
        redirect('/admin/teams.php?success=updated');
    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        query("DELETE FROM teams WHERE id = $id");
        redirect('/admin/teams.php?success=deleted');
    }
}

// Get all teams
$teams = fetch_all("SELECT * FROM teams ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Tim - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Manajemen Tim</h1>
            <button onclick="showAddModal()" class="btn btn-primary">+ Tambah Tim</button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['success'] === 'added')
                    echo 'Tim berhasil ditambahkan!';
                elseif ($_GET['success'] === 'updated')
                    echo 'Tim berhasil diupdate!';
                elseif ($_GET['success'] === 'deleted')
                    echo 'Tim berhasil dihapus!';
                ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Tim</th>
                        <th>Total Skor</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teams as $team): ?>
                        <tr>
                            <td><?= $team['id'] ?></td>
                            <td><?= htmlspecialchars($team['name']) ?></td>
                            <td><?= $team['total_score'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($team['created_at'])) ?></td>
                            <td>
                                <button
                                    onclick="showEditModal(<?= $team['id'] ?>, '<?= htmlspecialchars($team['name'], ENT_QUOTES) ?>')"
                                    class="btn btn-sm btn-secondary">Edit</button>
                                <button
                                    onclick="deleteTeam(<?= $team['id'] ?>, '<?= htmlspecialchars($team['name'], ENT_QUOTES) ?>')"
                                    class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Team Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h2>Tambah Tim Baru</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="name">Nama Tim</label>
                    <input type="text" id="name" name="name" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>

    <!-- Edit Team Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit Tim</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_name">Nama Tim</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>

    <!-- Delete Form (hidden) -->
    <form id="deleteForm" method="POST" action="" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" id="delete_id" name="id">
    </form>

    <script src="/assets/js/admin-common.js"></script>
    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function showEditModal(id, name) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('editModal').style.display = 'block';
        }

        function deleteTeam(id, name) {
            if (confirm('Hapus tim "' + name + '"?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>

</html>