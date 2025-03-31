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

// Initialiser les variables
$start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d');
$success_message = '';
$errors = [];

// Valider les dates
if (!validateDate($start_date) || !validateDate($end_date)) {
    $errors[] = "Les dates sélectionnées ne sont pas valides.";
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// Récupérer les données caloriques pour la période sélectionnée
$sql = "SELECT 
            m.log_date,
            SUM(m.total_calories) as calories_in,
            (SELECT SUM(el.calories_burned) FROM exercise_logs el WHERE el.user_id = ? AND el.log_date = m.log_date) as calories_out
        FROM 
            meals m
        WHERE 
            m.user_id = ? AND 
            m.log_date BETWEEN ? AND ?
        GROUP BY 
            m.log_date
        ORDER BY 
            m.log_date";

$calorie_data = fetchAll($sql, [$user_id, $user_id, $start_date, $end_date]);

// Récupérer les données IMC pour la période sélectionnée
$sql = "SELECT 
            wl.log_date,
            wl.weight,
            up.height,
            (wl.weight / ((up.height / 100) * (up.height / 100))) as bmi
        FROM 
            weight_logs wl
        JOIN 
            user_profiles up ON wl.user_id = up.user_id
        WHERE 
            wl.user_id = ? AND 
            wl.log_date BETWEEN ? AND ?
        ORDER BY 
            wl.log_date";

$bmi_data = fetchAll($sql, [$user_id, $start_date, $end_date]);

// Préparer les données pour les graphiques
$dates = [];
$calories_in = [];
$calories_out = [];
$net_calories = [];
$bmi_dates = [];
$bmi_values = [];

foreach ($calorie_data as $data) {
    $dates[] = date('d/m', strtotime($data['log_date']));
    $calories_in[] = round($data['calories_in'] ?? 0);
    $calories_out[] = round($data['calories_out'] ?? 0);
    $net_calories[] = round(($data['calories_in'] ?? 0) - ($data['calories_out'] ?? 0));
}

foreach ($bmi_data as $data) {
    $bmi_dates[] = date('d/m', strtotime($data['log_date']));
    $bmi_values[] = round($data['bmi'], 1);
}

// Calculer les moyennes
$avg_calories_in = !empty($calories_in) ? array_sum($calories_in) / count($calories_in) : 0;
$avg_calories_out = !empty($calories_out) ? array_sum($calories_out) / count($calories_out) : 0;
$avg_net_calories = !empty($net_calories) ? array_sum($net_calories) / count($net_calories) : 0;
$avg_bmi = !empty($bmi_values) ? array_sum($bmi_values) / count($bmi_values) : 0;

// Récupérer le profil de l'utilisateur
$sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$profile = fetchOne($sql, [$user_id]);

// Récupérer le dernier poids enregistré
$sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
$latest_weight = fetchOne($sql, [$user_id]);

// Récupérer l'objectif de poids actuel
$sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);

// Calculer les besoins caloriques quotidiens
$bmr = 0;
$tdee = 0;
$calorie_goal = 0;

if ($profile && $latest_weight) {
    // Calculer le BMR (métabolisme de base) avec la formule de Mifflin-St Jeor
    $age = isset($profile['birth_date']) ? (date('Y') - date('Y', strtotime($profile['birth_date']))) : 30;
    
    if ($profile['gender'] === 'homme') {
        $bmr = 10 * $latest_weight['weight'] + 6.25 * $profile['height'] - 5 * $age + 5;
    } else {
        $bmr = 10 * $latest_weight['weight'] + 6.25 * $profile['height'] - 5 * $age - 161;
    }
    
    // Calculer le TDEE (dépense énergétique totale quotidienne)
    $activity_factors = [
        'sedentaire' => 1.2,
        'leger' => 1.375,
        'modere' => 1.55,
        'actif' => 1.725,
        'tres_actif' => 1.9
    ];
    
    $activity_factor = $activity_factors[$profile['activity_level']] ?? 1.2;
    $tdee = $bmr * $activity_factor;
    
    // Récupérer le programme actif de l'utilisateur
    $sql = "SELECT p.* FROM user_programs up 
            JOIN programs p ON up.program_id = p.id 
            WHERE up.user_id = ? AND up.status = 'actif' 
            ORDER BY up.created_at DESC LIMIT 1";
    $active_program = fetchOne($sql, [$user_id]);
    
    // Calculer l'objectif calorique en fonction du programme ou de l'objectif de poids
    if ($active_program) {
        // Utiliser les valeurs du programme
        $calorie_goal = $active_program['daily_calories'];
    } elseif ($current_goal) {
        // Calculer en fonction de l'objectif de poids
        if ($current_goal['target_weight'] < $latest_weight['weight']) {
            // Objectif de perte de poids (déficit de 500 calories)
            $calorie_goal = $tdee - 500;
        } elseif ($current_goal['target_weight'] > $latest_weight['weight']) {
            // Objectif de prise de poids (surplus de 500 calories)
            $calorie_goal = $tdee + 500;
        } else {
            // Objectif de maintien
            $calorie_goal = $tdee;
        }
    } else {
        // Pas d'objectif défini, utiliser le TDEE comme objectif
        $calorie_goal = $tdee;
    }
}

// Enregistrer l'historique IMC quotidien si ce n'est pas déjà fait aujourd'hui
if ($latest_weight && $profile) {
    $today = date('Y-m-d');
    $sql = "SELECT id FROM bmi_history WHERE user_id = ? AND log_date = ?";
    $existing_bmi = fetchOne($sql, [$user_id, $today]);
    
    if (!$existing_bmi) {
        $bmi = $latest_weight['weight'] / (($profile['height'] / 100) * ($profile['height'] / 100));
        $sql = "INSERT INTO bmi_history (user_id, log_date, weight, height, bmi, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        insert($sql, [$user_id, $today, $latest_weight['weight'], $profile['height'], $bmi]);
    }
}

// Récupérer l'historique IMC complet
$sql = "SELECT *, DATE_FORMAT(log_date, '%d/%m/%Y') as formatted_date 
        FROM bmi_history 
        WHERE user_id = ? 
        ORDER BY log_date DESC 
        LIMIT 100";
$bmi_history = fetchAll($sql, [$user_id]);

// Récupérer l'historique calorique détaillé
$sql = "SELECT 
            m.log_date, 
            DATE_FORMAT(m.log_date, '%d/%m/%Y') as formatted_date,
            SUM(m.total_calories) as calories_in,
            (SELECT SUM(el.calories_burned) FROM exercise_logs el WHERE el.user_id = ? AND el.log_date = m.log_date) as calories_out,
            SUM(m.total_protein) as protein,
            SUM(m.total_carbs) as carbs,
            SUM(m.total_fat) as fat
        FROM 
            meals m
        WHERE 
            m.user_id = ? 
        GROUP BY 
            m.log_date
        ORDER BY 
            m.log_date DESC
        LIMIT 30";

$calorie_history = fetchAll($sql, [$user_id, $user_id]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique calorique et IMC - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-4">Historique calorique et IMC</h1>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="weight-log.php" class="btn btn-primary">
                    <i class="fas fa-weight me-1"></i>Journal de poids
                </a>
                <a href="food-log.php" class="btn btn-success">
                    <i class="fas fa-utensils me-1"></i>Journal alimentaire
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Filtres de date -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" action="calorie-history.php" class="row g-3">
                    <div class="col-md-5">
                        <label for="start_date" class="form-label">Date de début</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label for="end_date" class="form-label">Date de fin</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i>Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Résumé des calories -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Calories consommées</h5>
                        <h2 class="mb-0"><?php echo round($avg_calories_in); ?></h2>
                        <p class="card-text">moyenne par jour</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Calories brûlées</h5>
                        <h2 class="mb-0"><?php echo round($avg_calories_out); ?></h2>
                        <p class="card-text">moyenne par jour</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card <?php echo $avg_net_calories <= 0 ? 'bg-info' : 'bg-warning'; ?> text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Bilan calorique</h5>
                        <h2 class="mb-0"><?php echo round($avg_net_calories); ?></h2>
                        <p class="card-text">moyenne par jour</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">IMC moyen</h5>
                        <h2 class="mb-0"><?php echo round($avg_bmi, 1); ?></h2>
                        <p class="card-text">
                            <?php
                            if ($avg_bmi < 18.5) {
                                echo 'Insuffisance pondérale';
                            } elseif ($avg_bmi < 25) {
                                echo 'Poids normal';
                            } elseif ($avg_bmi < 30) {
                                echo 'Surpoids';
                            } else {
                                echo 'Obésité';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique des calories -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Évolution des calories</h5>
            </div>
            <div class="card-body">
                <canvas id="calorieChart" height="200"></canvas>
            </div>
        </div>

        <!-- Graphique de l'IMC -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Évolution de l'IMC</h5>
            </div>
            <div class="card-body">
                <canvas id="bmiChart" height="200"></canvas>
            </div>
        </div>

        <!-- Historique calorique détaillé -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Historique calorique détaillé</h5>
            </div>
            <div class="card-body">
                <?php if (empty($calorie_history)): ?>
                    <div class="alert alert-info">
                        Aucune donnée calorique disponible pour la période sélectionnée.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Calories consommées</th>
                                    <th>Calories brûlées</th>
                                    <th>Bilan calorique</th>
                                    <th>Protéines (g)</th>
                                    <th>Glucides (g)</th>
                                    <th>Lipides (g)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($calorie_history as $entry): ?>
                                    <?php 
                                    $calories_in = round($entry['calories_in'] ?? 0);
                                    $calories_out = round($entry['calories_out'] ?? 0);
                                    $net_calories = $calories_in - $calories_out;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($entry['formatted_date']); ?></td>
                                        <td><?php echo htmlspecialchars($calories_in); ?></td>
                                        <td><?php echo htmlspecialchars($calories_out); ?></td>
                                        <td class="<?php echo $net_calories <= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo htmlspecialchars($net_calories); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(round($entry['protein'] ?? 0, 1)); ?></td>
                                        <td><?php echo htmlspecialchars(round($entry['carbs'] ?? 0, 1)); ?></td>
                                        <td><?php echo htmlspecialchars(round($entry['fat'] ?? 0, 1)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historique IMC détaillé -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Historique IMC détaillé</h5>
            </div>
            <div class="card-body">
                <?php if (empty($bmi_history)): ?>
                    <div class="alert alert-info">
                        Aucune donnée IMC disponible.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Poids (kg)</th>
                                    <th>Taille (cm)</th>
                                    <th>IMC</th>
                                    <th>Catégorie</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bmi_history as $entry): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($entry['formatted_date']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['weight']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['height']); ?></td>
                                        <td><?php echo htmlspecialchars(round($entry['bmi'], 1)); ?></td>
                                        <td>
                                            <?php
                                            $bmi = $entry['bmi'];
                                            if ($bmi < 18.5) {
                                                echo '<span class="badge bg-info">Insuffisance pondérale</span>';
                                            } elseif ($bmi < 25) {
                                                echo '<span class="badge bg-success">Poids normal</span>';
                                            } elseif ($bmi < 30) {
                                                echo '<span class="badge bg-warning text-dark">Surpoids</span>';
                                            } else {
                                                echo '<span class="badge bg-danger">Obésité</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Graphique des calories
        const calorieCtx = document.getElementById('calorieChart').getContext('2d');
        const calorieChart = new Chart(calorieCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    {
                        label: 'Calories consommées',
                        data: <?php echo json_encode($calories_in); ?>,
                        backgroundColor: 'rgba(13, 110, 253, 0.5)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Calories brûlées',
                        data: <?php echo json_encode($calories_out); ?>,
                        backgroundColor: 'rgba(25, 135, 84, 0.5)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Bilan calorique',
                        data: <?php echo json_encode($net_calories); ?>,
                        backgroundColor: 'rgba(255, 193, 7, 0.5)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1,
                        type: 'line'
                    },
                    {
                        label: 'Objectif calorique',
                        data: Array(<?php echo count($dates); ?>).fill(<?php echo $calorie_goal; ?>),
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        type: 'line',
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Calories (kcal)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });

        // Graphique de l'IMC
        const bmiCtx = document.getElementById('bmiChart').getContext('2d');
        const bmiChart = new Chart(bmiCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($bmi_dates); ?>,
                datasets: [
                    {
                        label: 'IMC',
                        data: <?php echo json_encode($bmi_values); ?>,
                        backgroundColor: 'rgba(108, 117, 125, 0.5)',
                        borderColor: 'rgba(108, 117, 125, 1)',
                        borderWidth: 2,
                        tension: 0.1
                    },
                    {
                        label: 'Limite inférieure poids normal',
                        data: Array(<?php echo count($bmi_dates); ?>).fill(18.5),
                        borderColor: 'rgba(13, 202, 240, 1)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    },
                    {
                        label: 'Limite supérieure poids normal',
                        data: Array(<?php echo count($bmi_dates); ?>).fill(25),
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    },
                    {
                        label: 'Limite obésité',
                        data: Array(<?php echo count($bmi_dates); ?>).fill(30),
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'IMC'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
