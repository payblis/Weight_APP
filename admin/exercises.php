<?php
require_once '../includes/config.php';

// Vérification si l'utilisateur est admin
if (!isLoggedIn() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO exercises (name, category_id, description, difficulty_level, calories_per_hour, instructions, video_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['category_id'],
                    $_POST['description'],
                    $_POST['difficulty_level'],
                    $_POST['calories_per_hour'],
                    $_POST['instructions'],
                    $_POST['video_url']
                ]);
                break;
            case 'edit':
                $stmt = $pdo->prepare("UPDATE exercises SET name = ?, category_id = ?, description = ?, difficulty_level = ?, calories_per_hour = ?, instructions = ?, video_url = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['category_id'],
                    $_POST['description'],
                    $_POST['difficulty_level'],
                    $_POST['calories_per_hour'],
                    $_POST['instructions'],
                    $_POST['video_url'],
                    $_POST['exercise_id']
                ]);
                break;
            case 'delete':
                if (isset($_POST['exercise_id'])) {
                    $stmt = $pdo->prepare("DELETE FROM exercises WHERE id = ?");
                    $stmt->execute([$_POST['exercise_id']]);
                }
                break;
        }
        header('Location: exercises.php');
        exit;
    }
}

include '../components/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="foods.php">
                            <i class="fas fa-utensils"></i> Aliments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="exercises.php">
                            <i class="fas fa-dumbbell"></i> Exercices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="achievements.php">
                            <i class="fas fa-trophy"></i> Badges
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stats.php">
                            <i class="fas fa-chart-bar"></i> Statistiques
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Gestion des exercices</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExerciseModal">
                    <i class="fas fa-plus"></i> Ajouter un exercice
                </button>
            </div>

            <!-- Search and filters -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchExercise" placeholder="Rechercher un exercice...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="categoryFilter">
                        <option value="">Toutes les catégories</option>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM exercise_categories ORDER BY name");
                        while ($category = $stmt->fetch()) {
                            echo "<option value='{$category['id']}'>{$category['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="difficultyFilter">
                        <option value="">Tous les niveaux</option>
                        <option value="beginner">Débutant</option>
                        <option value="intermediate">Intermédiaire</option>
                        <option value="advanced">Avancé</option>
                    </select>
                </div>
            </div>

            <!-- Exercises table -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="exercisesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Catégorie</th>
                                    <th>Niveau</th>
                                    <th>Calories/h</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("
                                    SELECT e.*, ec.name as category_name 
                                    FROM exercises e 
                                    LEFT JOIN exercise_categories ec ON e.category_id = ec.id 
                                    ORDER BY e.name
                                ");
                                while ($exercise = $stmt->fetch()) {
                                    echo "<tr>";
                                    echo "<td>{$exercise['id']}</td>";
                                    echo "<td>{$exercise['name']}</td>";
                                    echo "<td>{$exercise['category_name']}</td>";
                                    echo "<td>";
                                    switch ($exercise['difficulty_level']) {
                                        case 'beginner':
                                            echo '<span class="badge bg-success">Débutant</span>';
                                            break;
                                        case 'intermediate':
                                            echo '<span class="badge bg-warning">Intermédiaire</span>';
                                            break;
                                        case 'advanced':
                                            echo '<span class="badge bg-danger">Avancé</span>';
                                            break;
                                    }
                                    echo "</td>";
                                    echo "<td>{$exercise['calories_per_hour']}</td>";
                                    echo "<td>";
                                    echo "<div class='btn-group'>";
                                    echo "<button type='button' class='btn btn-sm btn-info' onclick='viewExercise({$exercise['id']})'><i class='fas fa-eye'></i></button>";
                                    echo "<button type='button' class='btn btn-sm btn-warning' onclick='editExercise({$exercise['id']})'><i class='fas fa-edit'></i></button>";
                                    echo "<form method='POST' style='display: inline;' onsubmit='return confirm(\"Êtes-vous sûr de vouloir supprimer cet exercice ?\")'>";
                                    echo "<input type='hidden' name='action' value='delete'>";
                                    echo "<input type='hidden' name='exercise_id' value='{$exercise['id']}'>";
                                    echo "<button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>";
                                    echo "</form>";
                                    echo "</div>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Exercise Modal -->
<div class="modal fade" id="addExerciseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un exercice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select class="form-select" name="category_id" required>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM exercise_categories ORDER BY name");
                            while ($category = $stmt->fetch()) {
                                echo "<option value='{$category['id']}'>{$category['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Niveau de difficulté</label>
                        <select class="form-select" name="difficulty_level" required>
                            <option value="beginner">Débutant</option>
                            <option value="intermediate">Intermédiaire</option>
                            <option value="advanced">Avancé</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Calories par heure</label>
                        <input type="number" class="form-control" name="calories_per_hour" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instructions</label>
                        <textarea class="form-control" name="instructions" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL de la vidéo</label>
                        <input type="url" class="form-control" name="video_url">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Exercise Modal -->
<div class="modal fade" id="viewExerciseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de l'exercice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="exerciseDetails">
                    <!-- Les détails seront chargés via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Exercise Modal -->
<div class="modal fade" id="editExerciseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier un exercice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editExerciseForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="exercise_id" id="editExerciseId">
                <div class="modal-body">
                    <!-- Les champs seront remplis via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewExercise(exerciseId) {
    // À implémenter : charger les détails de l'exercice via AJAX
    $('#viewExerciseModal').modal('show');
}

function editExercise(exerciseId) {
    // À implémenter : charger les détails de l'exercice via AJAX
    $('#editExerciseModal').modal('show');
}

// Filtrage par catégorie
document.getElementById('categoryFilter').addEventListener('change', function() {
    filterExercises();
});

// Filtrage par niveau de difficulté
document.getElementById('difficultyFilter').addEventListener('change', function() {
    filterExercises();
});

// Recherche en temps réel
document.getElementById('searchExercise').addEventListener('keyup', function() {
    filterExercises();
});

function filterExercises() {
    var categoryId = document.getElementById('categoryFilter').value;
    var difficulty = document.getElementById('difficultyFilter').value;
    var searchInput = document.getElementById('searchExercise').value.toLowerCase();
    
    var table = document.getElementById('exercisesTable');
    var rows = table.getElementsByTagName('tr');

    for (var i = 1; i < rows.length; i++) {
        var row = rows[i];
        var name = row.getElementsByTagName('td')[1].textContent.toLowerCase();
        var category = row.getElementsByTagName('td')[2].textContent;
        var difficultyCell = row.getElementsByTagName('td')[3].textContent.toLowerCase();
        
        var showRow = true;
        
        if (categoryId && !category.includes(categoryId)) showRow = false;
        if (difficulty && !difficultyCell.includes(difficulty)) showRow = false;
        if (searchInput && !name.includes(searchInput)) showRow = false;
        
        row.style.display = showRow ? '' : 'none';
    }
}
</script>

<style>
.btn-group .btn {
    margin-right: 5px;
}

.badge {
    font-size: 0.8rem;
    padding: 0.5em 0.8em;
}

.table th {
    white-space: nowrap;
}
</style>

<?php include '../components/admin_footer.php'; ?> 