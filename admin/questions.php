<?php
require_once '../config.php';
require_admin();

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $question_text = escape($_POST['question_text']);
        $option_a = escape($_POST['option_a']);
        $option_b = escape($_POST['option_b']);
        $option_c = escape($_POST['option_c']);
        $option_d = escape($_POST['option_d']);
        $option_e = escape($_POST['option_e']);
        $correct_option = escape($_POST['correct_option']);
        $category = escape($_POST['category']);

        query("INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, option_e, correct_option, category) 
               VALUES ('$question_text', '$option_a', '$option_b', '$option_c', '$option_d', '$option_e', '$correct_option', '$category')");
        redirect('/admin/questions.php?success=added');
    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $question_text = escape($_POST['question_text']);
        $option_a = escape($_POST['option_a']);
        $option_b = escape($_POST['option_b']);
        $option_c = escape($_POST['option_c']);
        $option_d = escape($_POST['option_d']);
        $option_e = escape($_POST['option_e']);
        $correct_option = escape($_POST['correct_option']);
        $category = escape($_POST['category']);

        query("UPDATE questions SET 
               question_text = '$question_text',
               option_a = '$option_a',
               option_b = '$option_b',
               option_c = '$option_c',
               option_d = '$option_d',
               option_e = '$option_e',
               correct_option = '$correct_option',
               category = '$category'
               WHERE id = $id");
        redirect('/admin/questions.php?success=updated');
    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        query("DELETE FROM questions WHERE id = $id");
        redirect('/admin/questions.php?success=deleted');
    }
}

// Get all questions
$questions = fetch_all("SELECT * FROM questions ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Soal - Cerdas Cermat</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Manajemen Soal</h1>
            <button onclick="showAddModal()" class="btn btn-primary">+ Tambah Soal</button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['success'] === 'added')
                    echo 'Soal berhasil ditambahkan!';
                elseif ($_GET['success'] === 'updated')
                    echo 'Soal berhasil diupdate!';
                elseif ($_GET['success'] === 'deleted')
                    echo 'Soal berhasil dihapus!';
                ?>
            </div>
        <?php endif; ?>

        <div class="questions-grid">
            <?php foreach ($questions as $q): ?>
                <div class="question-card">
                    <div class="question-header">
                        <span class="question-id">#<?= $q['id'] ?></span>
                        <?php if ($q['category']): ?>
                            <span class="question-category"><?= htmlspecialchars($q['category']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="question-text">
                        <?= htmlspecialchars($q['question_text']) ?>
                    </div>
                    <div class="question-options">
                        <div class="option <?= $q['correct_option'] === 'A' ? 'correct' : '' ?>">
                            <strong>A:</strong> <?= htmlspecialchars($q['option_a']) ?>
                        </div>
                        <div class="option <?= $q['correct_option'] === 'B' ? 'correct' : '' ?>">
                            <strong>B:</strong> <?= htmlspecialchars($q['option_b']) ?>
                        </div>
                        <div class="option <?= $q['correct_option'] === 'C' ? 'correct' : '' ?>">
                            <strong>C:</strong> <?= htmlspecialchars($q['option_c']) ?>
                        </div>
                        <div class="option <?= $q['correct_option'] === 'D' ? 'correct' : '' ?>">
                            <strong>D:</strong> <?= htmlspecialchars($q['option_d']) ?>
                        </div>
                        <div class="option <?= $q['correct_option'] === 'E' ? 'correct' : '' ?>">
                            <strong>E:</strong> <?= htmlspecialchars($q['option_e']) ?>
                        </div>
                    </div>
                    <div class="question-actions">
                        <button onclick='editQuestion(<?= json_encode($q) ?>)'
                            class="btn btn-sm btn-secondary">Edit</button>
                        <button onclick="deleteQuestion(<?= $q['id'] ?>)" class="btn btn-sm btn-danger">Hapus</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="questionModal" class="modal">
        <div class="modal-content modal-large">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Tambah Soal Baru</h2>
            <form method="POST" action="">
                <input type="hidden" id="action" name="action" value="add">
                <input type="hidden" id="question_id" name="id">

                <div class="form-group">
                    <label for="question_text">Teks Soal *</label>
                    <textarea id="question_text" name="question_text" rows="3" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="option_a">Opsi A *</label>
                        <input type="text" id="option_a" name="option_a" required>
                    </div>
                    <div class="form-group">
                        <label for="option_b">Opsi B *</label>
                        <input type="text" id="option_b" name="option_b" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="option_c">Opsi C *</label>
                        <input type="text" id="option_c" name="option_c" required>
                    </div>
                    <div class="form-group">
                        <label for="option_d">Opsi D *</label>
                        <input type="text" id="option_d" name="option_d" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="option_e">Opsi E *</label>
                        <input type="text" id="option_e" name="option_e" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <input type="text" id="category" name="category" placeholder="Misal: Matematika, Sains">
                    </div>
                </div>

                <div class="form-group">
                    <label>Jawaban Benar *</label>
                    <div class="radio-group">
                        <label><input type="radio" name="correct_option" value="A" required> A</label>
                        <label><input type="radio" name="correct_option" value="B"> B</label>
                        <label><input type="radio" name="correct_option" value="C"> C</label>
                        <label><input type="radio" name="correct_option" value="D"> D</label>
                        <label><input type="radio" name="correct_option" value="E"> E</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" method="POST" action="" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" id="delete_id" name="id">
    </form>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Soal Baru';
            document.getElementById('action').value = 'add';
            document.querySelector('form').reset();
            document.getElementById('questionModal').style.display = 'block';
        }

        function editQuestion(question) {
            document.getElementById('modalTitle').textContent = 'Edit Soal';
            document.getElementById('action').value = 'edit';
            document.getElementById('question_id').value = question.id;
            document.getElementById('question_text').value = question.question_text;
            document.getElementById('option_a').value = question.option_a;
            document.getElementById('option_b').value = question.option_b;
            document.getElementById('option_c').value = question.option_c;
            document.getElementById('option_d').value = question.option_d;
            document.getElementById('option_e').value = question.option_e;
            document.getElementById('category').value = question.category || '';
            document.querySelector(`input[name="correct_option"][value="${question.correct_option}"]`).checked = true;
            document.getElementById('questionModal').style.display = 'block';
        }

        function deleteQuestion(id) {
            if (confirm('Hapus soal ini?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function closeModal() {
            document.getElementById('questionModal').style.display = 'none';
        }

        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>

</html>