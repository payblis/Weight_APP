<?php
require_once '../includes/config.php';

// Vérification si l'utilisateur est admin
if (!isLoggedIn() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

// Récupération des statistiques
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_users' => $pdo->query("SELECT COUNT(DISTINCT user_id) FROM daily_logs WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    'total_weight_loss' => $pdo->query("
        SELECT COALESCE(SUM(
            CASE 
                WHEN dl1.weight > dl2.weight THEN dl1.weight - dl2.weight
                ELSE 0
            END
        ), 0) as total_loss
        FROM daily_logs dl1
        JOIN daily_logs dl2 ON dl1.user_id = dl2.user_id
        WHERE dl2.date > dl1.date
        AND dl1.date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn(),
    'avg_daily_logs' => $pdo->query("
        SELECT ROUND(COUNT(*) / COUNT(DISTINCT user_id), 2)
        FROM daily_logs
        WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn(),
    'total_foods' => $pdo->query("SELECT COUNT(*) FROM foods")->fetchColumn(),
    'total_exercises' => $pdo->query("SELECT COUNT(*) FROM exercises")->fetchColumn(),
    'achievements_earned' => $pdo->query("SELECT COUNT(*) FROM user_achievements")->fetchColumn()
];

// Récupération des données pour les graphiques
$userGrowth = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetchAll();

$weightLossProgress = $pdo->query("
    SELECT DATE(date) as log_date, 
           ROUND(AVG(weight), 2) as avg_weight
    FROM daily_logs
    WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(date)
    ORDER BY log_date
")->fetchAll();

$popularExercises = $pdo->query("
    SELECT e.name, COUNT(*) as count
    FROM workout_exercises we
    JOIN exercises e ON we.exercise_id = e.id
    WHERE we.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY e.id
    ORDER BY count DESC
    LIMIT 5
")->fetchAll();

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
                        <a class="nav-link" href="achievements.php">
                            <i class="fas fa-trophy"></i> Badges
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="stats.php">
                            <i class="fas fa-chart-bar"></i> Statistiques
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Statistiques</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportStats('pdf')">
                            <i class="fas fa-file-pdf"></i> Exporter PDF
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportStats('csv')">
                            <i class="fas fa-file-csv"></i> Exporter CSV
                        </button>
                    </div>
                    <select class="form-select form-select-sm" id="timeRange" onchange="updateStats()">
                        <option value="7">7 derniers jours</option>
                        <option value="30" selected>30 derniers jours</option>
                        <option value="90">90 derniers jours</option>
                        <option value="365">Année</option>
                    </select>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Utilisateurs Total</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['users']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Utilisateurs Actifs (7j)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['active_users']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Perte de poids totale</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo round($stats['total_weight_loss'], 1); ?> kg</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-weight-scale fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Badges débloqués</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['achievements_earned']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-trophy fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Croissance des utilisateurs</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="userGrowthChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Exercices populaires</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie">
                                <canvas id="popularExercisesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Progression moyenne du poids</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="weightProgressChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Configuration des graphiques
const userGrowthData = <?php echo json_encode(array_map(function($row) {
    return ['date' => $row['date'], 'count' => $row['count']];
}, $userGrowth)); ?>;

const weightProgressData = <?php echo json_encode(array_map(function($row) {
    return ['date' => $row['log_date'], 'weight' => $row['avg_weight']];
}, $weightLossProgress)); ?>;

const popularExercisesData = <?php echo json_encode(array_map(function($row) {
    return ['name' => $row['name'], 'count' => $row['count']];
}, $popularExercises)); ?>;

// Graphique de croissance des utilisateurs
new Chart(document.getElementById('userGrowthChart'), {
    type: 'line',
    data: {
        labels: userGrowthData.map(d => d.date),
        datasets: [{
            label: 'Nouveaux utilisateurs',
            data: userGrowthData.map(d => d.count),
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.05)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});

// Graphique des exercices populaires
new Chart(document.getElementById('popularExercisesChart'), {
    type: 'doughnut',
    data: {
        labels: popularExercisesData.map(d => d.name),
        datasets: [{
            data: popularExercisesData.map(d => d.count),
            backgroundColor: [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
            ]
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Graphique de progression du poids
new Chart(document.getElementById('weightProgressChart'), {
    type: 'line',
    data: {
        labels: weightProgressData.map(d => d.date),
        datasets: [{
            label: 'Poids moyen (kg)',
            data: weightProgressData.map(d => d.weight),
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28, 200, 138, 0.05)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

function exportStats(format) {
    // À implémenter : export des statistiques
    alert('Export en ' + format + ' à implémenter');
}

function updateStats() {
    // À implémenter : mise à jour des statistiques selon la période sélectionnée
    const days = document.getElementById('timeRange').value;
    // Recharger la page avec le nouveau paramètre
    window.location.href = 'stats.php?days=' + days;
}
</script>

<style>
.chart-area {
    position: relative;
    height: 300px;
    margin: 0 -1rem;
}

.chart-pie {
    position: relative;
    height: 250px;
}

.border-left-primary {
    border-left: .25rem solid #4e73df!important;
}

.border-left-success {
    border-left: .25rem solid #1cc88a!important;
}

.border-left-info {
    border-left: .25rem solid #36b9cc!important;
}

.border-left-warning {
    border-left: .25rem solid #f6c23e!important;
}

.text-gray-300 {
    color: #dddfeb!important;
}

.text-gray-800 {
    color: #5a5c69!important;
}
</style>

<?php include '../components/admin_footer.php'; ?> 