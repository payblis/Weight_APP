<?php
require_once 'includes/config.php';

// Vérification de la connexion
if (!isLoggedIn()) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Vous devez être connecté pour accéder à cette page'
    ];
    header('Location: login.php');
    exit;
}

// Récupération de la période
$period = $_GET['period'] ?? 'month';
$today = date('Y-m-d');

switch ($period) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('-1 week'));
        break;
    case 'month':
        $startDate = date('Y-m-d', strtotime('-1 month'));
        break;
    case 'year':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        break;
    case 'all':
        $startDate = null;
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-1 month'));
        $period = 'month';
}

// Récupération des données de poids
$sql = "
    SELECT date, weight, notes
    FROM daily_logs
    WHERE user_id = ?
    " . ($startDate ? "AND date >= ?" : "") . "
    ORDER BY date ASC
";

$params = [$_SESSION['user_id']];
if ($startDate) {
    $params[] = $startDate;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$weights = $stmt->fetchAll();

// Récupération de l'objectif actif
$stmt = $pdo->prepare("
    SELECT *
    FROM weight_goals
    WHERE user_id = ? AND status = 'active'
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$activeGoal = $stmt->fetch();

// Calcul des statistiques
$stats = [
    'start_weight' => $weights[0]['weight'] ?? 0,
    'current_weight' => end($weights)['weight'] ?? 0,
    'total_loss' => 0,
    'average_weekly_loss' => 0,
    'best_week' => 0,
    'worst_week' => 0
];

if (count($weights) > 1) {
    $stats['total_loss'] = $stats['start_weight'] - $stats['current_weight'];
    
    // Calcul des variations hebdomadaires
    $weeklyChanges = [];
    $prevWeight = null;
    $prevDate = null;
    
    foreach ($weights as $log) {
        if ($prevWeight !== null && $prevDate !== null) {
            $days = (strtotime($log['date']) - strtotime($prevDate)) / (60 * 60 * 24);
            if ($days > 0) {
                $weeklyChange = ($prevWeight - $log['weight']) * (7 / $days);
                $weeklyChanges[] = $weeklyChange;
            }
        }
        $prevWeight = $log['weight'];
        $prevDate = $log['date'];
    }
    
    if (!empty($weeklyChanges)) {
        $stats['average_weekly_loss'] = array_sum($weeklyChanges) / count($weeklyChanges);
        $stats['best_week'] = max($weeklyChanges);
        $stats['worst_week'] = min($weeklyChanges);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progrès - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container mt-4">
        <!-- Filtres de période -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="btn-group" role="group">
                    <a href="?period=week" class="btn btn-outline-primary <?php echo $period === 'week' ? 'active' : ''; ?>">
                        Semaine
                    </a>
                    <a href="?period=month" class="btn btn-outline-primary <?php echo $period === 'month' ? 'active' : ''; ?>">
                        Mois
                    </a>
                    <a href="?period=year" class="btn btn-outline-primary <?php echo $period === 'year' ? 'active' : ''; ?>">
                        Année
                    </a>
                    <a href="?period=all" class="btn btn-outline-primary <?php echo $period === 'all' ? 'active' : ''; ?>">
                        Tout
                    </a>
                </div>
            </div>
        </div>

        <!-- Graphique -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <canvas id="weightChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Perte totale</h5>
                        <p class="card-text <?php echo $stats['total_loss'] > 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo abs(number_format($stats['total_loss'], 1)); ?> kg
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Moyenne hebdo</h5>
                        <p class="card-text <?php echo $stats['average_weekly_loss'] > 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo abs(number_format($stats['average_weekly_loss'], 1)); ?> kg
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Meilleure semaine</h5>
                        <p class="card-text text-success">
                            <?php echo abs(number_format($stats['best_week'], 1)); ?> kg
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Moins bonne semaine</h5>
                        <p class="card-text text-danger">
                            <?php echo abs(number_format($stats['worst_week'], 1)); ?> kg
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des mesures -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Historique détaillé</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Poids</th>
                                        <th>Variation</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $prevWeight = null;
                                    foreach (array_reverse($weights) as $log): 
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($log['date'])); ?></td>
                                        <td><?php echo number_format($log['weight'], 1); ?> kg</td>
                                        <td>
                                            <?php if ($prevWeight !== null): ?>
                                                <?php $diff = $log['weight'] - $prevWeight; ?>
                                                <span class="<?php echo $diff <= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo ($diff <= 0 ? '-' : '+') . abs(number_format($diff, 1)); ?> kg
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['notes'] ?? ''); ?></td>
                                    </tr>
                                    <?php 
                                    $prevWeight = $log['weight'];
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuration du graphique
        const ctx = document.getElementById('weightChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($log) {
                    return date('d/m/Y', strtotime($log['date']));
                }, $weights)); ?>,
                datasets: [{
                    label: 'Poids (kg)',
                    data: <?php echo json_encode(array_map(function($log) {
                        return $log['weight'];
                    }, $weights)); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }<?php if ($activeGoal): ?>, {
                    label: 'Objectif',
                    data: <?php echo json_encode(array_map(function($log) use ($activeGoal) {
                        $days = (strtotime($log['date']) - strtotime($activeGoal['start_date'])) / (60 * 60 * 24);
                        $targetLoss = ($days / 7) * $activeGoal['weekly_goal'];
                        return $activeGoal['start_weight'] - $targetLoss;
                    }, $weights)); ?>,
                    borderColor: 'rgba(255, 99, 132, 0.5)',
                    borderDash: [5, 5],
                    tension: 0.1
                }<?php endif; ?>]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
</body>
</html> 