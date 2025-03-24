<?php
require_once 'includes/config.php';
require_once 'includes/chatgpt.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupération des données de l'utilisateur
$userId = $_SESSION['user_id'];
$userData = [];

try {
    // Données de poids
    $weightStmt = $pdo->prepare("
        SELECT weight, date 
        FROM daily_logs 
        WHERE user_id = ? 
        ORDER BY date DESC 
        LIMIT 7
    ");
    $weightStmt->execute([$userId]);
    $weightData = $weightStmt->fetchAll();

    // Données caloriques
    $calorieStmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            SUM(calories) as total_calories
        FROM meal_foods
        WHERE user_id = ?
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
    ");
    $calorieStmt->execute([$userId]);
    $calorieData = $calorieStmt->fetchAll();

    // Objectifs
    $goalStmt = $pdo->prepare("
        SELECT * FROM weight_goals 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $goalStmt->execute([$userId]);
    $goalData = $goalStmt->fetch();

    // Badges récents
    $achievementStmt = $pdo->prepare("
        SELECT a.* 
        FROM achievements a
        JOIN user_achievements ua ON a.id = ua.achievement_id
        WHERE ua.user_id = ?
        ORDER BY ua.earned_at DESC
        LIMIT 3
    ");
    $achievementStmt->execute([$userId]);
    $recentAchievements = $achievementStmt->fetchAll();

    // Suggestions de l'IA
    $aiStmt = $pdo->prepare("
        SELECT * FROM ai_suggestions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $aiStmt->execute([$userId]);
    $aiSuggestions = $aiStmt->fetchAll();

} catch (PDOException $e) {
    // Gérer l'erreur
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
}

// Préparation des données pour les graphiques
$chartData = [
    'weight' => [
        'dates' => array_reverse(array_column($weightData, 'date')),
        'values' => array_reverse(array_column($weightData, 'weight'))
    ],
    'calories' => [
        'dates' => array_column($calorieData, 'date'),
        'values' => array_column($calorieData, 'total_calories')
    ]
];

include 'components/user_header.php';
?>

<div class="container-fluid">
    <!-- Titre de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Tableau de bord</h1>
        <button class="btn btn-primary" onclick="updateDailyLog()">
            <i class="fas fa-plus me-2"></i>Ajouter une entrée
        </button>
    </div>

    <!-- Cartes statistiques -->
    <div class="row">
        <!-- Poids actuel -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-label">Poids actuel</div>
                            <div class="stat-value">
                                <?php echo number_format($weightData[0]['weight'], 1); ?> kg
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-weight stat-icon text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Objectif -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-label">Objectif</div>
                            <div class="stat-value">
                                <?php echo number_format($goalData['target_weight'], 1); ?> kg
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bullseye stat-icon text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calories aujourd'hui -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-label">Calories aujourd'hui</div>
                            <div class="stat-value">
                                <?php 
                                $todayCalories = isset($calorieData[0]) ? $calorieData[0]['total_calories'] : 0;
                                echo number_format($todayCalories);
                                ?> kcal
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-fire stat-icon text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Badges gagnés -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-label">Badges gagnés</div>
                            <div class="stat-value">
                                <?php 
                                $badgeCount = count($recentAchievements);
                                echo $badgeCount;
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-trophy stat-icon text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row">
        <!-- Progression du poids -->
        <div class="col-xl-8 col-lg-7">
            <div class="chart-container">
                <h5 class="chart-title">Progression du poids</h5>
                <canvas id="weightChart"></canvas>
            </div>
        </div>

        <!-- Calories journalières -->
        <div class="col-xl-4 col-lg-5">
            <div class="chart-container">
                <h5 class="chart-title">Calories journalières</h5>
                <canvas id="calorieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Suggestions et badges -->
    <div class="row">
        <!-- Suggestions de l'IA -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary">Suggestions de votre coach IA</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($aiSuggestions)): ?>
                        <p class="text-muted">Aucune suggestion pour le moment.</p>
                    <?php else: ?>
                        <?php foreach ($aiSuggestions as $suggestion): ?>
                            <div class="ai-suggestion mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-robot text-primary fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($suggestion['title']); ?></h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($suggestion['content']); ?></p>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($suggestion['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Badges récents -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary">Badges récents</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentAchievements)): ?>
                        <p class="text-muted">Aucun badge gagné pour le moment.</p>
                    <?php else: ?>
                        <?php foreach ($recentAchievements as $achievement): ?>
                            <div class="achievement-item mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <?php if ($achievement['icon_url']): ?>
                                            <img src="<?php echo htmlspecialchars($achievement['icon_url']); ?>" alt="Badge" class="achievement-icon">
                                        <?php else: ?>
                                            <i class="fas fa-trophy text-warning fa-2x"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($achievement['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($achievement['description']); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'ajout d'entrée journalière -->
<div class="modal fade" id="dailyLogModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une entrée journalière</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="dailyLogForm" method="POST" action="/api/add_daily_log.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Poids (kg)</label>
                        <input type="number" class="form-control" name="weight" step="0.1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
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
// Données pour les graphiques
const weightData = <?php echo json_encode($chartData['weight']); ?>;
const calorieData = <?php echo json_encode($chartData['calories']); ?>;

// Fonction pour afficher le modal d'ajout d'entrée
function updateDailyLog() {
    const modal = new bootstrap.Modal(document.getElementById('dailyLogModal'));
    modal.show();
}

// Gestion du formulaire d'ajout d'entrée
document.getElementById('dailyLogForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement...';
    
    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Erreur réseau');
        }
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Succès', 'Entrée ajoutée avec succès', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
        bootstrap.Modal.getInstance(document.getElementById('dailyLogModal')).hide();
    }
});
</script>

<?php include 'components/user_footer.php'; ?> 