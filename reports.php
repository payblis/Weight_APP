<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$user = fetchOne($sql, [$user_id]);

// Récupérer la période de rapport
$period = isset($_GET['period']) ? sanitizeInput($_GET['period']) : 'month';
$custom_start = isset($_GET['start']) ? sanitizeInput($_GET['start']) : '';
$custom_end = isset($_GET['end']) ? sanitizeInput($_GET['end']) : '';

// Déterminer les dates de début et de fin en fonction de la période
$end_date = date('Y-m-d');

switch ($period) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-1 week'));
        $period_label = 'Dernière semaine';
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-1 month'));
        $period_label = 'Dernier mois';
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        $period_label = 'Dernière année';
        break;
    case 'custom':
        $start_date = $custom_start ?: date('Y-m-d', strtotime('-1 month'));
        $end_date = $custom_end ?: date('Y-m-d');
        $period_label = 'Période personnalisée';
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-1 month'));
        $period_label = 'Dernier mois';
        break;
}

// Récupérer les entrées de poids pour la période
$sql = "SELECT * FROM weight_logs WHERE user_id = ? AND log_date BETWEEN ? AND ? ORDER BY log_date";
$weight_logs = fetchAll($sql, [$user_id, $start_date, $end_date]);

// Récupérer les entrées alimentaires pour la période
$sql = "SELECT fl.*, f.name as food_name, f.calories, f.protein, f.carbs, f.fat 
        FROM food_logs fl 
        LEFT JOIN foods f ON fl.food_id = f.id 
        WHERE fl.user_id = ? AND fl.log_date BETWEEN ? AND ? 
        ORDER BY fl.log_date, fl.meal_type";
$food_logs = fetchAll($sql, [$user_id, $start_date, $end_date]);

// Récupérer les entrées d'exercices pour la période
$sql = "SELECT el.*, e.name as exercise_name 
        FROM exercise_logs el 
        LEFT JOIN exercises e ON el.exercise_id = e.id 
        WHERE el.user_id = ? AND el.log_date BETWEEN ? AND ? 
        ORDER BY el.log_date";
$exercise_logs = fetchAll($sql, [$user_id, $start_date, $end_date]);

// Récupérer l'objectif actuel
$sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);

// Calculer les statistiques de poids
$weight_stats = [
    'first' => count($weight_logs) > 0 ? $weight_logs[0]['weight'] : 0,
    'last' => count($weight_logs) > 0 ? $weight_logs[count($weight_logs) - 1]['weight'] : 0,
    'min' => 0,
    'max' => 0,
    'avg' => 0,
    'change' => 0,
    'change_percent' => 0
];

if (count($weight_logs) > 0) {
    $weight_values = array_column($weight_logs, 'weight');
    $weight_stats['min'] = min($weight_values);
    $weight_stats['max'] = max($weight_values);
    $weight_stats['avg'] = array_sum($weight_values) / count($weight_values);
    $weight_stats['change'] = $weight_stats['last'] - $weight_stats['first'];
    $weight_stats['change_percent'] = $weight_stats['first'] > 0 ? ($weight_stats['change'] / $weight_stats['first']) * 100 : 0;
}

// Calculer les statistiques alimentaires
$food_stats = [
    'total_calories' => 0,
    'total_protein' => 0,
    'total_carbs' => 0,
    'total_fat' => 0,
    'avg_calories' => 0,
    'avg_protein' => 0,
    'avg_carbs' => 0,
    'avg_fat' => 0,
    'days_tracked' => 0
];

$daily_calories = [];

foreach ($food_logs as $log) {
    $log_date = $log['log_date'];
    
    if (!isset($daily_calories[$log_date])) {
        $daily_calories[$log_date] = [
            'calories' => 0,
            'protein' => 0,
            'carbs' => 0,
            'fat' => 0
        ];
    }
    
    if (isset($log['calories'])) {
        $calories = $log['calories'] * ($log['quantity'] / 100);
        $protein = $log['protein'] * ($log['quantity'] / 100);
        $carbs = $log['carbs'] * ($log['quantity'] / 100);
        $fat = $log['fat'] * ($log['quantity'] / 100);
    } elseif (isset($log['custom_calories'])) {
        $calories = $log['custom_calories'];
        $protein = $log['custom_protein'] ?? 0;
        $carbs = $log['custom_carbs'] ?? 0;
        $fat = $log['custom_fat'] ?? 0;
    } else {
        $calories = 0;
        $protein = 0;
        $carbs = 0;
        $fat = 0;
    }
    
    $daily_calories[$log_date]['calories'] += $calories;
    $daily_calories[$log_date]['protein'] += $protein;
    $daily_calories[$log_date]['carbs'] += $carbs;
    $daily_calories[$log_date]['fat'] += $fat;
}

$food_stats['days_tracked'] = count($daily_calories);

if ($food_stats['days_tracked'] > 0) {
    foreach ($daily_calories as $day_stats) {
        $food_stats['total_calories'] += $day_stats['calories'];
        $food_stats['total_protein'] += $day_stats['protein'];
        $food_stats['total_carbs'] += $day_stats['carbs'];
        $food_stats['total_fat'] += $day_stats['fat'];
    }
    
    $food_stats['avg_calories'] = $food_stats['total_calories'] / $food_stats['days_tracked'];
    $food_stats['avg_protein'] = $food_stats['total_protein'] / $food_stats['days_tracked'];
    $food_stats['avg_carbs'] = $food_stats['total_carbs'] / $food_stats['days_tracked'];
    $food_stats['avg_fat'] = $food_stats['total_fat'] / $food_stats['days_tracked'];
}

// Calculer les statistiques d'exercices
$exercise_stats = [
    'total_duration' => 0,
    'total_calories' => 0,
    'avg_duration' => 0,
    'avg_calories' => 0,
    'sessions' => count($exercise_logs),
    'days_tracked' => 0
];

$daily_exercises = [];

foreach ($exercise_logs as $log) {
    $log_date = $log['log_date'];
    
    if (!isset($daily_exercises[$log_date])) {
        $daily_exercises[$log_date] = [
            'duration' => 0,
            'calories' => 0
        ];
    }
    
    $daily_exercises[$log_date]['duration'] += $log['duration'];
    $daily_exercises[$log_date]['calories'] += $log['calories_burned'];
    
    $exercise_stats['total_duration'] += $log['duration'];
    $exercise_stats['total_calories'] += $log['calories_burned'];
}

$exercise_stats['days_tracked'] = count($daily_exercises);

if ($exercise_stats['sessions'] > 0) {
    $exercise_stats['avg_duration'] = $exercise_stats['total_duration'] / $exercise_stats['sessions'];
    $exercise_stats['avg_calories'] = $exercise_stats['total_calories'] / $exercise_stats['sessions'];
}

// Préparer les données pour les graphiques
$chart_dates = [];
$chart_weights = [];
$chart_calories_in = [];
$chart_calories_out = [];
$chart_net_calories = [];

// Créer un tableau pour chaque jour de la période
$period_start = new DateTime($start_date);
$period_end = new DateTime($end_date);
$interval = new DateInterval('P1D');
$date_range = new DatePeriod($period_start, $interval, $period_end->modify('+1 day'));

foreach ($date_range as $date) {
    $date_str = $date->format('Y-m-d');
    $chart_dates[] = $date->format('d/m');
    
    // Trouver le poids pour cette date
    $weight_found = false;
    foreach ($weight_logs as $log) {
        if ($log['log_date'] === $date_str) {
            $chart_weights[] = $log['weight'];
            $weight_found = true;
            break;
        }
    }
    if (!$weight_found) {
        $chart_weights[] = null;
    }
    
    // Calories entrantes pour cette date
    $calories_in = isset($daily_calories[$date_str]) ? $daily_calories[$date_str]['calories'] : 0;
    $chart_calories_in[] = $calories_in;
    
    // Calories sortantes pour cette date
    $calories_out = isset($daily_exercises[$date_str]) ? $daily_exercises[$date_str]['calories'] : 0;
    $chart_calories_out[] = $calories_out;
    
    // Calories nettes pour cette date
    $chart_net_calories[] = $calories_in - $calories_out;
}

// Générer des insights basés sur les données
$insights = [];

// Insight sur la tendance de poids
if (count($weight_logs) >= 2) {
    if ($weight_stats['change'] < 0) {
        $insights[] = [
            'icon' => 'fas fa-arrow-down',
            'color' => 'success',
            'title' => 'Perte de poids',
            'text' => 'Vous avez perdu ' . number_format(abs($weight_stats['change']), 1) . ' kg (' . number_format(abs($weight_stats['change_percent']), 1) . '%) pendant cette période.'
        ];
    } elseif ($weight_stats['change'] > 0) {
        $insights[] = [
            'icon' => 'fas fa-arrow-up',
            'color' => 'danger',
            'title' => 'Prise de poids',
            'text' => 'Vous avez pris ' . number_format($weight_stats['change'], 1) . ' kg (' . number_format($weight_stats['change_percent'], 1) . '%) pendant cette période.'
        ];
    } else {
        $insights[] = [
            'icon' => 'fas fa-equals',
            'color' => 'info',
            'title' => 'Poids stable',
            'text' => 'Votre poids est resté stable pendant cette période.'
        ];
    }
}

// Insight sur l'objectif
if ($current_goal && count($weight_logs) > 0) {
    $latest_weight = $weight_logs[count($weight_logs) - 1]['weight'];
    $weight_diff = $current_goal['target_weight'] - $latest_weight;
    
    if (abs($weight_diff) < 0.1) {
        $insights[] = [
            'icon' => 'fas fa-trophy',
            'color' => 'success',
            'title' => 'Objectif atteint',
            'text' => 'Félicitations ! Vous avez atteint votre objectif de poids de ' . number_format($current_goal['target_weight'], 1) . ' kg.'
        ];
    } else {
        if ($current_goal['goal_type'] === 'perte' && $weight_diff > 0) {
            $insights[] = [
                'icon' => 'fas fa-bullseye',
                'color' => 'warning',
                'title' => 'Objectif dépassé',
                'text' => 'Vous avez dépassé votre objectif de perte de poids de ' . number_format(abs($weight_diff), 1) . ' kg.'
            ];
        } elseif ($current_goal['goal_type'] === 'prise' && $weight_diff < 0) {
            $insights[] = [
                'icon' => 'fas fa-bullseye',
                'color' => 'warning',
                'title' => 'Objectif dépassé',
                'text' => 'Vous avez dépassé votre objectif de prise de poids de ' . number_format(abs($weight_diff), 1) . ' kg.'
        ];
        } else {
            $insights[] = [
                'icon' => 'fas fa-hourglass-half',
                'color' => 'primary',
                'title' => 'Progression vers l\'objectif',
                'text' => 'Il vous reste ' . number_format(abs($weight_diff), 1) . ' kg pour atteindre votre objectif de ' . number_format($current_goal['target_weight'], 1) . ' kg.'
            ];
        }
    }
}

// Insight sur l'alimentation
if ($food_stats['days_tracked'] > 0) {
    $macro_ratio = [
        'protein' => ($food_stats['avg_protein'] * 4) / $food_stats['avg_calories'] * 100,
        'carbs' => ($food_stats['avg_carbs'] * 4) / $food_stats['avg_calories'] * 100,
        'fat' => ($food_stats['avg_fat'] * 9) / $food_stats['avg_calories'] * 100
    ];
    
    $insights[] = [
        'icon' => 'fas fa-utensils',
        'color' => 'info',
        'title' => 'Répartition des macronutriments',
        'text' => 'Votre alimentation moyenne se compose de ' . number_format($macro_ratio['protein'], 1) . '% de protéines, ' . number_format($macro_ratio['carbs'], 1) . '% de glucides et ' . number_format($macro_ratio['fat'], 1) . '% de lipides.'
    ];
}

// Insight sur l'activité physique
if ($exercise_stats['days_tracked'] > 0) {
    $exercise_frequency = ($exercise_stats['days_tracked'] / count($chart_dates)) * 100;
    
    if ($exercise_frequency >= 70) {
        $insights[] = [
            'icon' => 'fas fa-running',
            'color' => 'success',
            'title' => 'Activité physique régulière',
            'text' => 'Vous avez été actif ' . number_format($exercise_frequency, 0) . '% des jours, ce qui est excellent pour votre santé et votre forme physique.'
        ];
    } elseif ($exercise_frequency >= 40) {
        $insights[] = [
            'icon' => 'fas fa-running',
            'color' => 'primary',
            'title' => 'Activité physique modérée',
            'text' => 'Vous avez été actif ' . number_format($exercise_frequency, 0) . '% des jours. Essayez d\'augmenter légèrement votre fréquence d\'exercice.'
        ];
    } else {
        $insights[] = [
            'icon' => 'fas fa-running',
            'color' => 'warning',
            'title' => 'Activité physique limitée',
            'text' => 'Vous avez été actif seulement ' . number_format($exercise_frequency, 0) . '% des jours. Essayez d\'intégrer plus d\'activité physique dans votre routine.'
        ];
    }
}

// Insight sur l'équilibre calorique
if ($food_stats['days_tracked'] > 0 && $exercise_stats['days_tracked'] > 0) {
    $avg_net_calories = $food_stats['avg_calories'] - ($exercise_stats['total_calories'] / count($chart_dates));
    
    if ($avg_net_calories < 0) {
        $insights[] = [
            'icon' => 'fas fa-fire',
            'color' => 'success',
            'title' => 'Déficit calorique',
            'text' => 'Vous avez maintenu un déficit calorique moyen de ' . number_format(abs($avg_net_calories), 0) . ' calories par jour, ce qui favorise la perte de poids.'
        ];
    } elseif ($avg_net_calories > 500) {
        $insights[] = [
            'icon' => 'fas fa-fire',
            'color' => 'danger',
            'title' => 'Surplus calorique élevé',
            'text' => 'Vous avez maintenu un surplus calorique moyen de ' . number_format($avg_net_calories, 0) . ' calories par jour, ce qui peut entraîner une prise de poids.'
        ];
    } else {
        $insights[] = [
            'icon' => 'fas fa-fire',
            'color' => 'info',
            'title' => 'Équilibre calorique',
            'text' => 'Vous avez maintenu un équilibre calorique proche de la maintenance avec ' . number_format($avg_net_calories, 0) . ' calories nettes par jour en moyenne.'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Barre de navigation -->
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <!-- En-tête de la page -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-0">Rapports et analyses</h1>
                <p class="text-muted">Visualisez vos progrès et obtenez des insights personnalisés</p>
            </div>
            <div class="col-md-4 text-md-end">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#periodSelector" aria-expanded="false" aria-controls="periodSelector">
                    <i class="fas fa-calendar-alt me-1"></i><?php echo $period_label; ?>
                </button>
                <button class="btn btn-outline-secondary ms-2" type="button" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Imprimer
                </button>
            </div>
        </div>

        <!-- Sélecteur de période -->
        <div class="collapse mb-4" id="periodSelector">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="reports.php" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="period" class="form-label">Période</label>
                            <select class="form-select" id="period" name="period" onchange="toggleCustomDates()">
                                <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Dernière semaine</option>
                                <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Dernier mois</option>
                                <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Dernière année</option>
                                <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Personnalisée</option>
                            </select>
                        </div>
                        <div class="col-md-3 custom-date <?php echo $period !== 'custom' ? 'd-none' : ''; ?>">
                            <label for="start" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="start" name="start" value="<?php echo $custom_start; ?>">
                        </div>
                        <div class="col-md-3 custom-date <?php echo $period !== 'custom' ? 'd-none' : ''; ?>">
                            <label for="end" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="end" name="end" value="<?php echo $custom_end; ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Appliquer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if (count($weight_logs) === 0 && count($food_logs) === 0 && count($exercise_logs) === 0): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center py-5">
                    <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                    <h4>Aucune donnée disponible</h4>
                    <p class="text-muted mb-4">Commencez à enregistrer votre poids, vos repas et vos exercices pour voir des rapports détaillés.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="weight-log.php?action=add" class="btn btn-primary">
                            <i class="fas fa-weight me-1"></i>Ajouter un poids
                        </a>
                        <a href="food-log.php?action=add" class="btn btn-success">
                            <i class="fas fa-utensils me-1"></i>Ajouter un repas
                        </a>
                        <a href="exercise-log.php?action=add" class="btn btn-warning">
                            <i class="fas fa-running me-1"></i>Ajouter un exercice
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Insights -->
            <div class="row mb-4">
                <?php foreach ($insights as $index => $insight): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-<?php echo $insight['color']; ?> bg-opacity-10 p-3 rounded">
                                            <i class="<?php echo $insight['icon']; ?> fa-2x text-<?php echo $insight['color']; ?>"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title"><?php echo $insight['title']; ?></h5>
                                        <p class="card-text"><?php echo $insight['text']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Graphiques -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Évolution du poids</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($weight_logs) > 0): ?>
                                <canvas id="weightChart" height="300"></canvas>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <p class="text-muted mb-0">Aucune donnée de poids disponible</p>
                                    <a href="weight-log.php?action=add" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus-circle me-1"></i>Ajouter une entrée de poids
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Équilibre calorique</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($food_logs) > 0 || count($exercise_logs) > 0): ?>
                                <canvas id="calorieChart" height="300"></canvas>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <p class="text-muted mb-0">Aucune donnée calorique disponible</p>
                                    <div class="d-flex justify-content-center gap-2 mt-2">
                                        <a href="food-log.php?action=add" class="btn btn-success">
                                            <i class="fas fa-plus-circle me-1"></i>Ajouter un repas
                                        </a>
                                        <a href="exercise-log.php?action=add" class="btn btn-warning">
                                            <i class="fas fa-plus-circle me-1"></i>Ajouter un exercice
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques détaillées -->
            <div class="row mb-4">
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Statistiques de poids</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($weight_logs) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <th>Poids initial</th>
                                                <td class="text-end"><?php echo number_format($weight_stats['first'], 1); ?> kg</td>
                                            </tr>
                                            <tr>
                                                <th>Poids final</th>
                                                <td class="text-end"><?php echo number_format($weight_stats['last'], 1); ?> kg</td>
                                            </tr>
                                            <tr>
                                                <th>Variation</th>
                                                <td class="text-end <?php echo $weight_stats['change'] < 0 ? 'text-success' : ($weight_stats['change'] > 0 ? 'text-danger' : ''); ?>">
                                                    <?php 
                                                        if ($weight_stats['change'] < 0) {
                                                            echo '-' . number_format(abs($weight_stats['change']), 1) . ' kg';
                                                        } elseif ($weight_stats['change'] > 0) {
                                                            echo '+' . number_format($weight_stats['change'], 1) . ' kg';
                                                        } else {
                                                            echo '0 kg';
                                                        }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Variation en %</th>
                                                <td class="text-end <?php echo $weight_stats['change_percent'] < 0 ? 'text-success' : ($weight_stats['change_percent'] > 0 ? 'text-danger' : ''); ?>">
                                                    <?php 
                                                        if ($weight_stats['change_percent'] < 0) {
                                                            echo '-' . number_format(abs($weight_stats['change_percent']), 1) . '%';
                                                        } elseif ($weight_stats['change_percent'] > 0) {
                                                            echo '+' . number_format($weight_stats['change_percent'], 1) . '%';
                                                        } else {
                                                            echo '0%';
                                                        }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Poids minimum</th>
                                                <td class="text-end"><?php echo number_format($weight_stats['min'], 1); ?> kg</td>
                                            </tr>
                                            <tr>
                                                <th>Poids maximum</th>
                                                <td class="text-end"><?php echo number_format($weight_stats['max'], 1); ?> kg</td>
                                            </tr>
                                            <tr>
                                                <th>Poids moyen</th>
                                                <td class="text-end"><?php echo number_format($weight_stats['avg'], 1); ?> kg</td>
                                            </tr>
                                            <tr>
                                                <th>Entrées enregistrées</th>
                                                <td class="text-end"><?php echo count($weight_logs); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">Aucune donnée de poids disponible</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Statistiques alimentaires</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($food_stats['days_tracked'] > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <th>Calories moyennes</th>
                                                <td class="text-end"><?php echo number_format($food_stats['avg_calories'], 0); ?> cal/jour</td>
                                            </tr>
                                            <tr>
                                                <th>Protéines moyennes</th>
                                                <td class="text-end"><?php echo number_format($food_stats['avg_protein'], 1); ?> g/jour</td>
                                            </tr>
                                            <tr>
                                                <th>Glucides moyens</th>
                                                <td class="text-end"><?php echo number_format($food_stats['avg_carbs'], 1); ?> g/jour</td>
                                            </tr>
                                            <tr>
                                                <th>Lipides moyens</th>
                                                <td class="text-end"><?php echo number_format($food_stats['avg_fat'], 1); ?> g/jour</td>
                                            </tr>
                                            <tr>
                                                <th>Calories totales</th>
                                                <td class="text-end"><?php echo number_format($food_stats['total_calories'], 0); ?> cal</td>
                                            </tr>
                                            <tr>
                                                <th>Protéines totales</th>
                                                <td class="text-end"><?php echo number_format($food_stats['total_protein'], 1); ?> g</td>
                                            </tr>
                                            <tr>
                                                <th>Glucides totaux</th>
                                                <td class="text-end"><?php echo number_format($food_stats['total_carbs'], 1); ?> g</td>
                                            </tr>
                                            <tr>
                                                <th>Lipides totaux</th>
                                                <td class="text-end"><?php echo number_format($food_stats['total_fat'], 1); ?> g</td>
                                            </tr>
                                            <tr>
                                                <th>Jours suivis</th>
                                                <td class="text-end"><?php echo $food_stats['days_tracked']; ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">Aucune donnée alimentaire disponible</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Statistiques d'exercices</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($exercise_stats['sessions'] > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <th>Séances d'exercice</th>
                                                <td class="text-end"><?php echo $exercise_stats['sessions']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Jours d'activité</th>
                                                <td class="text-end"><?php echo $exercise_stats['days_tracked']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Fréquence</th>
                                                <td class="text-end">
                                                    <?php 
                                                        $frequency = ($exercise_stats['days_tracked'] / count($chart_dates)) * 100;
                                                        echo number_format($frequency, 0) . '% des jours';
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Durée totale</th>
                                                <td class="text-end"><?php echo number_format($exercise_stats['total_duration'], 0); ?> minutes</td>
                                            </tr>
                                            <tr>
                                                <th>Calories brûlées totales</th>
                                                <td class="text-end"><?php echo number_format($exercise_stats['total_calories'], 0); ?> calories</td>
                                            </tr>
                                            <tr>
                                                <th>Durée moyenne par séance</th>
                                                <td class="text-end"><?php echo number_format($exercise_stats['avg_duration'], 0); ?> minutes</td>
                                            </tr>
                                            <tr>
                                                <th>Calories brûlées par séance</th>
                                                <td class="text-end"><?php echo number_format($exercise_stats['avg_calories'], 0); ?> calories</td>
                                            </tr>
                                            <tr>
                                                <th>Durée moyenne par jour actif</th>
                                                <td class="text-end">
                                                    <?php 
                                                        $avg_duration_per_day = $exercise_stats['days_tracked'] > 0 ? $exercise_stats['total_duration'] / $exercise_stats['days_tracked'] : 0;
                                                        echo number_format($avg_duration_per_day, 0) . ' minutes';
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Calories brûlées par jour actif</th>
                                                <td class="text-end">
                                                    <?php 
                                                        $avg_calories_per_day = $exercise_stats['days_tracked'] > 0 ? $exercise_stats['total_calories'] / $exercise_stats['days_tracked'] : 0;
                                                        echo number_format($avg_calories_per_day, 0) . ' calories';
                                                    ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">Aucune donnée d'exercice disponible</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Répartition des macronutriments -->
            <?php if ($food_stats['days_tracked'] > 0): ?>
                <div class="row mb-4">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Répartition des macronutriments</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="macroChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Recommandations personnalisées</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="mb-2"><i class="fas fa-utensils me-2 text-primary"></i>Alimentation</h6>
                                    <ul class="list-group list-group-flush">
                                        <?php if ($current_goal && $current_goal['goal_type'] === 'perte'): ?>
                                            <li class="list-group-item px-0">Maintenez un déficit calorique d'environ 500 calories par jour pour une perte de poids saine.</li>
                                            <li class="list-group-item px-0">Visez une consommation de protéines d'environ <?php echo number_format($weight_stats['last'] * 2, 0); ?> g par jour pour préserver votre masse musculaire.</li>
                                            <li class="list-group-item px-0">Limitez votre consommation de glucides raffinés et privilégiez les sources complexes comme les légumes, les fruits et les céréales complètes.</li>
                                        <?php elseif ($current_goal && $current_goal['goal_type'] === 'prise'): ?>
                                            <li class="list-group-item px-0">Maintenez un surplus calorique d'environ 500 calories par jour pour une prise de poids saine.</li>
                                            <li class="list-group-item px-0">Augmentez votre consommation de protéines à environ <?php echo number_format($weight_stats['last'] * 2.2, 0); ?> g par jour pour favoriser la prise de masse musculaire.</li>
                                            <li class="list-group-item px-0">Incluez des sources de graisses saines et des glucides complexes pour atteindre vos objectifs caloriques.</li>
                                        <?php else: ?>
                                            <li class="list-group-item px-0">Maintenez un équilibre calorique pour conserver votre poids actuel.</li>
                                            <li class="list-group-item px-0">Visez une répartition équilibrée des macronutriments : 30% de protéines, 40% de glucides et 30% de lipides.</li>
                                            <li class="list-group-item px-0">Privilégiez les aliments non transformés et riches en nutriments pour une alimentation de qualité.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-2"><i class="fas fa-running me-2 text-primary"></i>Activité physique</h6>
                                    <ul class="list-group list-group-flush">
                                        <?php if ($exercise_stats['days_tracked'] > 0): ?>
                                            <?php if (($exercise_stats['days_tracked'] / count($chart_dates)) * 100 >= 70): ?>
                                                <li class="list-group-item px-0">Excellent niveau d'activité ! Continuez à maintenir cette régularité.</li>
                                                <li class="list-group-item px-0">Pensez à varier vos exercices pour solliciter différents groupes musculaires.</li>
                                            <?php elseif (($exercise_stats['days_tracked'] / count($chart_dates)) * 100 >= 40): ?>
                                                <li class="list-group-item px-0">Bon niveau d'activité. Essayez d'augmenter légèrement votre fréquence d'exercice.</li>
                                                <li class="list-group-item px-0">Visez 4-5 jours d'activité par semaine pour des résultats optimaux.</li>
                                            <?php else: ?>
                                                <li class="list-group-item px-0">Essayez d'augmenter votre niveau d'activité physique.</li>
                                                <li class="list-group-item px-0">Commencez par 2-3 jours d'exercice par semaine et augmentez progressivement.</li>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <li class="list-group-item px-0">Commencez par intégrer une activité physique régulière dans votre routine.</li>
                                            <li class="list-group-item px-0">Visez au moins 150 minutes d'activité modérée par semaine, réparties sur plusieurs jours.</li>
                                        <?php endif; ?>
                                        <li class="list-group-item px-0">Combinez des exercices cardio et de musculation pour des bénéfices optimaux.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Pied de page -->
    <footer class="bg-light py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; 2023 Weight Tracker. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-muted me-3">Conditions d'utilisation</a>
                    <a href="#" class="text-muted me-3">Confidentialité</a>
                    <a href="#" class="text-muted">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script>
        // Initialiser les sélecteurs de date
        flatpickr("#start, #end", {
            locale: "fr",
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
        
        // Fonction pour afficher/masquer les dates personnalisées
        function toggleCustomDates() {
            const period = document.getElementById('period').value;
            const customDateFields = document.querySelectorAll('.custom-date');
            
            customDateFields.forEach(field => {
                if (period === 'custom') {
                    field.classList.remove('d-none');
                } else {
                    field.classList.add('d-none');
                }
            });
        }
        
        <?php if (count($weight_logs) > 0): ?>
        // Graphique d'évolution du poids
        const weightCtx = document.getElementById('weightChart').getContext('2d');
        const weightChart = new Chart(weightCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_dates); ?>,
                datasets: [{
                    label: 'Poids (kg)',
                    data: <?php echo json_encode($chart_weights); ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true,
                    pointBackgroundColor: 'rgba(13, 110, 253, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y + ' kg';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: false,
                        grid: {
                            borderDash: [2, 2]
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        <?php if (count($food_logs) > 0 || count($exercise_logs) > 0): ?>
        // Graphique d'équilibre calorique
        const calorieCtx = document.getElementById('calorieChart').getContext('2d');
        const calorieChart = new Chart(calorieCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_dates); ?>,
                datasets: [
                    {
                        label: 'Calories ingérées',
                        data: <?php echo json_encode($chart_calories_in); ?>,
                        backgroundColor: 'rgba(40, 167, 69, 0.5)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Calories brûlées (exercice)',
                        data: <?php echo json_encode($chart_calories_out); ?>,
                        backgroundColor: 'rgba(220, 53, 69, 0.5)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Bilan calorique net',
                        data: <?php echo json_encode($chart_net_calories); ?>,
                        type: 'line',
                        backgroundColor: 'rgba(255, 193, 7, 0.5)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: false,
                        pointBackgroundColor: 'rgba(255, 193, 7, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [2, 2]
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        <?php if ($food_stats['days_tracked'] > 0): ?>
        // Graphique de répartition des macronutriments
        const macroCtx = document.getElementById('macroChart').getContext('2d');
        const macroChart = new Chart(macroCtx, {
            type: 'pie',
            data: {
                labels: ['Protéines', 'Glucides', 'Lipides'],
                datasets: [{
                    data: [
                        <?php echo $food_stats['avg_protein'] * 4; ?>,
                        <?php echo $food_stats['avg_carbs'] * 4; ?>,
                        <?php echo $food_stats['avg_fat'] * 9; ?>
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${percentage}% (${value.toFixed(0)} calories)`;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
