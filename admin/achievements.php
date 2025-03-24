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
                $stmt = $pdo->prepare("INSERT INTO achievements (name, description, icon_url, condition_type, condition_value) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['icon_url'],
                    $_POST['condition_type'],
                    $_POST['condition_value']
                ]);
                break;
            case 'edit':
                $stmt = $pdo->prepare("UPDATE achievements SET name = ?, description = ?, icon_url = ?, condition_type = ?, condition_value = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['icon_url'],
                    $_POST['condition_type'],
                    $_POST['condition_value'],
                    $_POST['achievement_id']
                ]);
                break;
            case 'delete':
                if (isset($_POST['achievement_id'])) {
                    $stmt = $pdo->prepare("DELETE FROM achievements WHERE id = ?");
                    $stmt->execute([$_POST['achievement_id']]);
                }
                break;
        }
        header('Location: achievements.php');
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
                        <a class="nav-link" href="exercises.php">
                            <i class="fas fa-dumbbell"></i> Exercices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="achievements.php">
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
                <h1>Gestion des badges</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
                    <i class="fas fa-plus"></i> Ajouter un badge
                </button>
            </div>

            <!-- Search -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchAchievement" placeholder="Rechercher un badge...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <select class="form-select" id="conditionFilter">
                        <option value="">Tous les types de conditions</option>
                        <option value="weight_loss">Perte de poids</option>
                        <option value="exercise_count">Nombre d'exercices</option>
                        <option value="streak_days">Jours consécutifs</option>
                        <option value="food_tracking">Suivi alimentaire</option>
                    </select>
                </div>
            </div>

            <!-- Achievements grid -->
            <div class="row">
                <?php
                $stmt = $pdo->query("SELECT * FROM achievements ORDER BY name");
                while ($achievement = $stmt->fetch()) {
                    echo "<div class='col-md-4 mb-4 achievement-card'>";
                    echo "<div class='card h-100'>";
                    echo "<div class='card-body'>";
                    echo "<div class='d-flex align-items-center mb-3'>";
                    if ($achievement['icon_url']) {
                        echo "<img src='{$achievement['icon_url']}' alt='{$achievement['name']}' class='achievement-icon me-3'>";
                    } else {
                        echo "<i class='fas fa-trophy fa-2x text-warning me-3'></i>";
                    }
                    echo "<h5 class='card-title mb-0'>{$achievement['name']}</h5>";
                    echo "</div>";
                    echo "<p class='card-text'>{$achievement['description']}</p>";
                    echo "<div class='achievement-details'>";
                    echo "<p><strong>Type de condition:</strong> " . ucfirst(str_replace('_', ' ', $achievement['condition_type'])) . "</p>";
                    echo "<p><strong>Valeur requise:</strong> {$achievement['condition_value']}</p>";
                    echo "</div>";
                    echo "<div class='mt-3'>";
                    echo "<button class='btn btn-sm btn-info me-2' onclick='editAchievement({$achievement['id']})'><i class='fas fa-edit'></i> Modifier</button>";
                    echo "<form method='POST' style='display: inline;' onsubmit='return confirm(\"Êtes-vous sûr de vouloir supprimer ce badge ?\")'>";
                    echo "<input type='hidden' name='action' value='delete'>";
                    echo "<input type='hidden' name='achievement_id' value='{$achievement['id']}'>";
                    echo "<button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i> Supprimer</button>";
                    echo "</form>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                }
                ?>
            </div>
        </main>
    </div>
</div>

<!-- Add Achievement Modal -->
<div class="modal fade" id="addAchievementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un badge</h5>
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
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL de l'icône</label>
                        <input type="url" class="form-control" name="icon_url">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type de condition</label>
                        <select class="form-select" name="condition_type" required>
                            <option value="weight_loss">Perte de poids</option>
                            <option value="exercise_count">Nombre d'exercices</option>
                            <option value="streak_days">Jours consécutifs</option>
                            <option value="food_tracking">Suivi alimentaire</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valeur requise</label>
                        <input type="number" class="form-control" name="condition_value" required>
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

<!-- Edit Achievement Modal -->
<div class="modal fade" id="editAchievementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier un badge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editAchievementForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="achievement_id" id="editAchievementId">
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
function editAchievement(achievementId) {
    // À implémenter : charger les détails du badge via AJAX
    $('#editAchievementModal').modal('show');
}

// Recherche en temps réel
document.getElementById('searchAchievement').addEventListener('keyup', function() {
    filterAchievements();
});

// Filtrage par type de condition
document.getElementById('conditionFilter').addEventListener('change', function() {
    filterAchievements();
});

function filterAchievements() {
    var searchInput = document.getElementById('searchAchievement').value.toLowerCase();
    var conditionType = document.getElementById('conditionFilter').value;
    
    var cards = document.getElementsByClassName('achievement-card');
    
    Array.from(cards).forEach(function(card) {
        var title = card.querySelector('.card-title').textContent.toLowerCase();
        var condition = card.querySelector('.achievement-details').textContent.toLowerCase();
        
        var showCard = true;
        
        if (searchInput && !title.includes(searchInput)) showCard = false;
        if (conditionType && !condition.includes(conditionType)) showCard = false;
        
        card.style.display = showCard ? '' : 'none';
    });
}
</script>

<style>
.achievement-icon {
    width: 48px;
    height: 48px;
    object-fit: cover;
}

.achievement-card .card {
    transition: transform 0.2s;
}

.achievement-card .card:hover {
    transform: translateY(-5px);
}

.achievement-details {
    font-size: 0.9rem;
    color: #6c757d;
}

.btn-group .btn {
    margin-right: 5px;
}
</style>

<?php include '../components/admin_footer.php'; ?> 