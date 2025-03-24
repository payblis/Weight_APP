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
                $stmt = $pdo->prepare("INSERT INTO foods (name, brand, category_id, calories, proteins, carbs, fats, fiber, serving_size, serving_unit, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['brand'],
                    $_POST['category_id'],
                    $_POST['calories'],
                    $_POST['proteins'],
                    $_POST['carbs'],
                    $_POST['fats'],
                    $_POST['fiber'],
                    $_POST['serving_size'],
                    $_POST['serving_unit'],
                    $_SESSION['user_id']
                ]);
                break;
            case 'edit':
                $stmt = $pdo->prepare("UPDATE foods SET name = ?, brand = ?, category_id = ?, calories = ?, proteins = ?, carbs = ?, fats = ?, fiber = ?, serving_size = ?, serving_unit = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['brand'],
                    $_POST['category_id'],
                    $_POST['calories'],
                    $_POST['proteins'],
                    $_POST['carbs'],
                    $_POST['fats'],
                    $_POST['fiber'],
                    $_POST['serving_size'],
                    $_POST['serving_unit'],
                    $_POST['food_id']
                ]);
                break;
            case 'delete':
                if (isset($_POST['food_id'])) {
                    $stmt = $pdo->prepare("DELETE FROM foods WHERE id = ?");
                    $stmt->execute([$_POST['food_id']]);
                }
                break;
            case 'verify':
                if (isset($_POST['food_id'])) {
                    $stmt = $pdo->prepare("UPDATE foods SET is_verified = NOT is_verified WHERE id = ?");
                    $stmt->execute([$_POST['food_id']]);
                }
                break;
        }
        header('Location: foods.php');
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
                        <a class="nav-link active" href="foods.php">
                            <i class="fas fa-utensils"></i> Aliments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exercises.php">
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
                <h1>Gestion des aliments</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFoodModal">
                    <i class="fas fa-plus"></i> Ajouter un aliment
                </button>
            </div>

            <!-- Search and filters -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchFood" placeholder="Rechercher un aliment...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="categoryFilter">
                        <option value="">Toutes les catégories</option>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM food_categories ORDER BY name");
                        while ($category = $stmt->fetch()) {
                            echo "<option value='{$category['id']}'>{$category['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-outline-primary" onclick="importFoods()">
                        <i class="fas fa-upload"></i> Importer
                    </button>
                    <button class="btn btn-outline-primary" onclick="exportFoods()">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                </div>
            </div>

            <!-- Foods table -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="foodsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Marque</th>
                                    <th>Catégorie</th>
                                    <th>Calories</th>
                                    <th>Protéines</th>
                                    <th>Glucides</th>
                                    <th>Lipides</th>
                                    <th>Portion</th>
                                    <th>Vérifié</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("
                                    SELECT f.*, fc.name as category_name 
                                    FROM foods f 
                                    LEFT JOIN food_categories fc ON f.category_id = fc.id 
                                    ORDER BY f.name
                                ");
                                while ($food = $stmt->fetch()) {
                                    echo "<tr>";
                                    echo "<td>{$food['id']}</td>";
                                    echo "<td>{$food['name']}</td>";
                                    echo "<td>{$food['brand']}</td>";
                                    echo "<td>{$food['category_name']}</td>";
                                    echo "<td>{$food['calories']}</td>";
                                    echo "<td>{$food['proteins']}g</td>";
                                    echo "<td>{$food['carbs']}g</td>";
                                    echo "<td>{$food['fats']}g</td>";
                                    echo "<td>{$food['serving_size']}{$food['serving_unit']}</td>";
                                    echo "<td>";
                                    if ($food['is_verified']) {
                                        echo '<span class="badge bg-success"><i class="fas fa-check"></i></span>';
                                    } else {
                                        echo '<span class="badge bg-warning"><i class="fas fa-clock"></i></span>';
                                    }
                                    echo "</td>";
                                    echo "<td>";
                                    echo "<div class='btn-group'>";
                                    echo "<button type='button' class='btn btn-sm btn-info' onclick='editFood({$food['id']})'><i class='fas fa-edit'></i></button>";
                                    echo "<form method='POST' style='display: inline;'>";
                                    echo "<input type='hidden' name='action' value='verify'>";
                                    echo "<input type='hidden' name='food_id' value='{$food['id']}'>";
                                    echo "<button type='submit' class='btn btn-sm btn-success'><i class='fas fa-check'></i></button>";
                                    echo "</form>";
                                    echo "<form method='POST' style='display: inline;' onsubmit='return confirm(\"Êtes-vous sûr de vouloir supprimer cet aliment ?\")'>";
                                    echo "<input type='hidden' name='action' value='delete'>";
                                    echo "<input type='hidden' name='food_id' value='{$food['id']}'>";
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

<!-- Add Food Modal -->
<div class="modal fade" id="addFoodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un aliment</h5>
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
                        <label class="form-label">Marque</label>
                        <input type="text" class="form-control" name="brand">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select class="form-select" name="category_id" required>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM food_categories ORDER BY name");
                            while ($category = $stmt->fetch()) {
                                echo "<option value='{$category['id']}'>{$category['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Calories</label>
                            <input type="number" class="form-control" name="calories" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Protéines (g)</label>
                            <input type="number" step="0.1" class="form-control" name="proteins" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Glucides (g)</label>
                            <input type="number" step="0.1" class="form-control" name="carbs" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lipides (g)</label>
                            <input type="number" step="0.1" class="form-control" name="fats" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fibres (g)</label>
                        <input type="number" step="0.1" class="form-control" name="fiber">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Taille de la portion</label>
                            <input type="number" class="form-control" name="serving_size" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unité</label>
                            <select class="form-select" name="serving_unit" required>
                                <option value="g">Grammes (g)</option>
                                <option value="ml">Millilitres (ml)</option>
                                <option value="unit">Unité</option>
                            </select>
                        </div>
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

<!-- Edit Food Modal -->
<div class="modal fade" id="editFoodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier un aliment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editFoodForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="food_id" id="editFoodId">
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
function editFood(foodId) {
    // À implémenter : charger les détails de l'aliment via AJAX
    $('#editFoodModal').modal('show');
}

function importFoods() {
    // À implémenter : import des aliments
    alert('Import à implémenter');
}

function exportFoods() {
    // À implémenter : export des aliments
    alert('Export à implémenter');
}

// Filtrage par catégorie
document.getElementById('categoryFilter').addEventListener('change', function() {
    var categoryId = this.value;
    var table = document.getElementById('foodsTable');
    var rows = table.getElementsByTagName('tr');

    for (var i = 1; i < rows.length; i++) {
        var categoryCell = rows[i].getElementsByTagName('td')[3];
        if (!categoryId || categoryCell.textContent.includes(categoryId)) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
});

// Recherche en temps réel
document.getElementById('searchFood').addEventListener('keyup', function() {
    var input = this.value.toLowerCase();
    var table = document.getElementById('foodsTable');
    var rows = table.getElementsByTagName('tr');

    for (var i = 1; i < rows.length; i++) {
        var name = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
        var brand = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
        
        if (name.includes(input) || brand.includes(input)) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
});
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