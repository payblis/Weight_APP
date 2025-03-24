<?php
require_once 'includes/config.php';
require_once 'includes/chatgpt.php';

// Redirection si non connecté
redirectIfNotLoggedIn();

// Récupération des données de l'utilisateur
$stmt = $pdo->prepare("
    SELECT u.*, wg.weekly_goal, wg.start_date, wg.target_date
    FROM users u
    LEFT JOIN weight_goals wg ON u.id = wg.user_id
    WHERE u.id = ? AND wg.status = 'active'
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupération des logs de poids
$stmt = $pdo->prepare("
    SELECT weight, date
    FROM daily_logs
    WHERE user_id = ?
    ORDER BY date DESC
    LIMIT 7
");
$stmt->execute([$_SESSION['user_id']]);
$weight_logs = $stmt->fetchAll();

// Calcul des statistiques
$start_date = new DateTime($user['start_date']);
$target_date = new DateTime($user['target_date']);
$today = new DateTime();
$progress = [
    'days_elapsed' => $start_date->diff($today)->days,
    'days_remaining' => $today->diff($target_date)->days,
    'total_days' => $start_date->diff($target_date)->days,
    'weight_lost' => $user['start_weight'] - $weight_logs[0]['weight'],
    'weight_remaining' => $weight_logs[0]['weight'] - $user['target_weight']
];
$progress['percentage'] = ($progress['days_elapsed'] / $progress['total_days']) * 100;

// Obtention des suggestions IA
$chatgpt = new ChatGPT(CHATGPT_API_KEY);
$meal_suggestion = $chatgpt->getMealSuggestion([
    'current_weight' => $weight_logs[0]['weight'],
    'target_weight' => $user['target_weight'],
    'weekly_goal' => $user['weekly_goal']
]);

$exercise_suggestion = $chatgpt->getExercisePlan($user['activity_level']);

include 'components/header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Tableau de bord</h1>
        <button id="addWeightBtn" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ajouter un poids
        </button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <h3>Progression</h3>
                <span class="stat-date">Jour <?php echo $progress['days_elapsed']; ?> sur <?php echo $progress['total_days']; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress" style="width: <?php echo $progress['percentage']; ?>%"></div>
            </div>
            <div class="stat-details">
                <div class="stat-item">
                    <span class="stat-label">Poids perdu</span>
                    <span class="stat-value"><?php echo number_format($progress['weight_lost'], 1); ?> kg</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Reste à perdre</span>
                    <span class="stat-value"><?php echo number_format($progress['weight_remaining'], 1); ?> kg</span>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h3>Objectif hebdomadaire</h3>
                <span class="stat-date">Cette semaine</span>
            </div>
            <div class="stat-circle">
                <svg viewBox="0 0 36 36" class="circular-chart">
                    <path d="M18 2.0845
                        a 15.9155 15.9155 0 0 1 0 31.831
                        a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none"
                        stroke="#eee"
                        stroke-width="3" />
                    <path d="M18 2.0845
                        a 15.9155 15.9155 0 0 1 0 31.831
                        a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none"
                        stroke="var(--primary-color)"
                        stroke-width="3"
                        stroke-dasharray="<?php echo min($progress['weight_lost'] / $user['weekly_goal'] * 100, 100); ?>, 100" />
                    <text x="18" y="20.35" class="percentage"><?php echo number_format($progress['weight_lost'], 1); ?></text>
                </svg>
                <div class="stat-circle-label">kg / <?php echo number_format($user['weekly_goal'], 1); ?> kg</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h3>Derniers poids</h3>
                <a href="weight-history.php" class="btn btn-link">Voir tout</a>
            </div>
            <div class="weight-chart">
                <canvas id="weightChart"></canvas>
            </div>
        </div>
    </div>

    <div class="suggestions-grid">
        <div class="suggestion-card">
            <div class="suggestion-header">
                <h3><i class="fas fa-utensils"></i> Suggestion de repas</h3>
                <button class="btn btn-icon refresh-suggestion" data-type="meal">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="suggestion-content">
                <?php echo $meal_suggestion; ?>
            </div>
        </div>

        <div class="suggestion-card">
            <div class="suggestion-header">
                <h3><i class="fas fa-dumbbell"></i> Programme d'exercices</h3>
                <button class="btn btn-icon refresh-suggestion" data-type="exercise">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="suggestion-content">
                <?php echo $exercise_suggestion; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'ajout de poids -->
<div id="weightModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Ajouter un poids</h2>
            <button class="modal-close">&times;</button>
        </div>
        <form id="weightForm" method="POST" action="add-weight.php">
            <div class="form-group">
                <label for="weight">Poids (kg)</label>
                <input type="number" id="weight" name="weight" class="form-control" required step="0.1" min="30" max="300">
            </div>
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="notes">Notes (optionnel)</label>
                <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Enregistrer</button>
        </form>
    </div>
</div>

<style>
.dashboard {
    padding: 2rem 0;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-date {
    color: #666;
    font-size: 0.9rem;
}

.progress-bar {
    height: 8px;
    background: #eee;
    border-radius: 4px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.progress {
    height: 100%;
    background: var(--primary-color);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.stat-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-item {
    text-align: center;
}

.stat-label {
    display: block;
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-color);
}

.stat-circle {
    text-align: center;
}

.circular-chart {
    width: 150px;
    height: 150px;
}

.circular-chart .percentage {
    fill: var(--text-color);
    font-size: 0.5em;
    text-anchor: middle;
    font-weight: bold;
}

.stat-circle-label {
    margin-top: 0.5rem;
    color: #666;
}

.weight-chart {
    height: 200px;
}

.suggestions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.suggestion-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.suggestion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.suggestion-header h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-icon:hover {
    background: #f5f5f5;
    color: var(--primary-color);
}

.suggestion-content {
    color: #333;
    line-height: 1.6;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    width: 100%;
    max-width: 500px;
    margin: 1rem;
    position: relative;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
}

.modal-close:hover {
    color: var(--danger-color);
}

@media (max-width: 768px) {
    .dashboard {
        padding: 1rem 0;
    }
    
    .dashboard-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .circular-chart {
        width: 120px;
        height: 120px;
    }
}
</style>

<script>
// Graphique de poids
const weightData = <?php echo json_encode(array_reverse($weight_logs)); ?>;
const ctx = document.getElementById('weightChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: weightData.map(log => new Date(log.date).toLocaleDateString()),
        datasets: [{
            label: 'Poids (kg)',
            data: weightData.map(log => log.weight),
            borderColor: 'rgb(0, 102, 238)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});

// Modal d'ajout de poids
const modal = document.getElementById('weightModal');
const addWeightBtn = document.getElementById('addWeightBtn');
const closeBtn = document.querySelector('.modal-close');

addWeightBtn.addEventListener('click', () => {
    modal.classList.add('active');
});

closeBtn.addEventListener('click', () => {
    modal.classList.remove('active');
});

modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.classList.remove('active');
    }
});

// Rafraîchissement des suggestions
document.querySelectorAll('.refresh-suggestion').forEach(button => {
    button.addEventListener('click', async () => {
        const type = button.dataset.type;
        const card = button.closest('.suggestion-card');
        const content = card.querySelector('.suggestion-content');
        
        button.classList.add('rotating');
        
        try {
            const response = await fetch(`refresh-suggestion.php?type=${type}`);
            const data = await response.json();
            content.innerHTML = data.suggestion;
        } catch (error) {
            console.error('Erreur lors du rafraîchissement de la suggestion:', error);
        }
        
        button.classList.remove('rotating');
    });
});
</script>

<?php include 'components/footer.php'; ?> 