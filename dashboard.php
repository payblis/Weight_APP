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
$success_message = '';
$errors = [];

// Récupérer le profil de l'utilisateur
$sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$profile = fetchOne($sql, [$user_id]);

// Récupérer le dernier poids enregistré
$sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
$latest_weight = fetchOne($sql, [$user_id]);

// Récupérer l'objectif de poids actuel
$sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);

// Récupérer le programme actif de l'utilisateur
$sql = "SELECT p.*, up.id as user_program_id, DATE_FORMAT(up.created_at, '%d/%m/%Y') as formatted_start_date 
        FROM user_programs up 
        JOIN programs p ON up.program_id = p.id 
        WHERE up.user_id = ? AND up.status = 'actif' 
        ORDER BY up.created_at DESC LIMIT 1";
$active_program = fetchOne($sql, [$user_id]);

// Calculer les besoins caloriques quotidiens
$bmr = 0;
$tdee = 0;
$calorie_goal = 0;
$protein_goal = 0;
$carbs_goal = 0;
$fat_goal = 0;

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
    
    // Calculer l'objectif calorique en fonction du programme ou de l'objectif de poids
    if ($active_program) {
        // Utiliser les valeurs du programme
        $calorie_goal = $active_program['daily_calories'];
        
        // Calculer les objectifs de macronutriments en fonction des ratios du programme
        $protein_ratio = $active_program['protein_ratio'] / 100;
        $carbs_ratio = $active_program['carbs_ratio'] / 100;
        $fat_ratio = $active_program['fat_ratio'] / 100;
        
        $protein_goal = ($calorie_goal * $protein_ratio) / 4; // 4 calories par gramme de protéine
        $carbs_goal = ($calorie_goal * $carbs_ratio) / 4; // 4 calories par gramme de glucide
        $fat_goal = ($calorie_goal * $fat_ratio) / 9; // 9 calories par gramme de lipide
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
        
        // Répartition standard des macronutriments pour la perte de poids
        if ($current_goal['target_weight'] < $latest_weight['weight']) {
            $protein_goal = $latest_weight['weight'] * 2.2; // 2.2g de protéines par kg de poids corporel
            $fat_goal = ($calorie_goal * 0.25) / 9; // 25% des calories proviennent des lipides
            $carbs_goal = ($calorie_goal - ($protein_goal * 4) - ($fat_goal * 9)) / 4;
        }
        // Répartition standard des macronutriments pour la prise de poids
        elseif ($current_goal['target_weight'] > $latest_weight['weight']) {
            $protein_goal = $latest_weight['weight'] * 1.8; // 1.8g de protéines par kg de poids corporel
            $fat_goal = ($calorie_goal * 0.3) / 9; // 30% des calories proviennent des lipides
            $carbs_goal = ($calorie_goal - ($protein_goal * 4) - ($fat_goal * 9)) / 4;
        }
        // Répartition standard des macronutriments pour le maintien
        else {
            $protein_goal = $latest_weight['weight'] * 1.6; // 1.6g de protéines par kg de poids corporel
            $fat_goal = ($calorie_goal * 0.3) / 9; // 30% des calories proviennent des lipides
            $carbs_goal = ($calorie_goal - ($protein_goal * 4) - ($fat_goal * 9)) / 4;
        }
    } else {
        // Pas d'objectif défini, utiliser le TDEE comme objectif
        $calorie_goal = $tdee;
        
        // Répartition standard des macronutriments
        $protein_goal = $latest_weight['weight'] * 1.6; // 1.6g de protéines par kg de poids corporel
        $fat_goal = ($calorie_goal * 0.3) / 9; // 30% des calories proviennent des lipides
        $carbs_goal = ($calorie_goal - ($protein_goal * 4) - ($fat_goal * 9)) / 4;
    }
}

// Récupérer les données caloriques pour aujourd'hui
$today = date('Y-m-d');
$sql = "SELECT 
            SUM(CASE 
                WHEN fl.food_id IS NOT NULL THEN 
                    (SELECT f.calories * (fl.quantity / 100) FROM foods f WHERE f.id = fl.food_id)
                ELSE fl.custom_calories
            END) as calories_in,
            SUM(CASE 
                WHEN fl.food_id IS NOT NULL THEN 
                    (SELECT f.protein * (fl.quantity / 100) FROM foods f WHERE f.id = fl.food_id)
                ELSE fl.custom_protein
            END) as protein_in,
            SUM(CASE 
                WHEN fl.food_id IS NOT NULL THEN 
                    (SELECT f.carbs * (fl.quantity / 100) FROM foods f WHERE f.id = fl.food_id)
                ELSE fl.custom_carbs
            END) as carbs_in,
            SUM(CASE 
                WHEN fl.food_id IS NOT NULL THEN 
                    (SELECT f.fat * (fl.quantity / 100) FROM foods f WHERE f.id = fl.food_id)
                ELSE fl.custom_fat
            END) as fat_in
        FROM 
            food_logs fl
        WHERE 
            fl.user_id = ? AND 
            fl.log_date = ?";
$today_food = fetchOne($sql, [$user_id, $today]);

$sql = "SELECT SUM(calories_burned) as calories_out FROM exercise_logs WHERE user_id = ? AND log_date = ?";
$today_exercise = fetchOne($sql, [$user_id, $today]);

$calories_in = $today_food ? round($today_food['calories_in'] ?? 0) : 0;
$calories_out = $today_exercise ? round($today_exercise['calories_out'] ?? 0) : 0;
$net_calories = $calories_in - $calories_out;
$remaining_calories = $calorie_goal - $net_calories;

$protein_in = $today_food ? round($today_food['protein_in'] ?? 0, 1) : 0;
$carbs_in = $today_food ? round($today_food['carbs_in'] ?? 0, 1) : 0;
$fat_in = $today_food ? round($today_food['fat_in'] ?? 0, 1) : 0;

$protein_remaining = max(0, round($protein_goal - $protein_in, 1));
$carbs_remaining = max(0, round($carbs_goal - $carbs_in, 1));
$fat_remaining = max(0, round($fat_goal - $fat_in, 1));

// Récupérer les données de poids pour le graphique
$sql = "SELECT log_date, weight, DATE_FORMAT(log_date, '%d/%m') as formatted_date 
        FROM weight_logs 
        WHERE user_id = ? 
        ORDER BY log_date DESC 
        LIMIT 10";
$weight_data = fetchAll($sql, [$user_id]);
$weight_data = array_reverse($weight_data);

$weight_dates = [];
$weight_values = [];

foreach ($weight_data as $data) {
    $weight_dates[] = $data['formatted_date'];
    $weight_values[] = $data['weight'];
}

// Récupérer les données caloriques pour le graphique
$sql = "SELECT 
            fl.log_date,
            DATE_FORMAT(fl.log_date, '%d/%m') as formatted_date,
            SUM(CASE 
                WHEN fl.food_id IS NOT NULL THEN 
                    (SELECT f.calories * (fl.quantity / 100) FROM foods f WHERE f.id = fl.food_id)
                ELSE fl.custom_calories
            END) as calories_in,
            (SELECT SUM(el.calories_burned) FROM exercise_logs el WHERE el.user_id = ? AND el.log_date = fl.log_date) as calories_out
        FROM 
            food_logs fl
        WHERE 
            fl.user_id = ? 
        GROUP BY 
            fl.log_date
        ORDER BY 
            fl.log_date DESC 
        LIMIT 7";
$calorie_data = fetchAll($sql, [$user_id, $user_id]);
$calorie_data = array_reverse($calorie_data);

$calorie_dates = [];
$calories_in_values = [];
$calories_out_values = [];
$net_calories_values = [];

foreach ($calorie_data as $data) {
    $calorie_dates[] = $data['formatted_date'];
    $calories_in_values[] = round($data['calories_in'] ?? 0);
    $calories_out_values[] = round($data['calories_out'] ?? 0);
    $net_calories_values[] = round(($data['calories_in'] ?? 0) - ($data['calories_out'] ?? 0));
}

// Récupérer les suggestions de repas récentes
$sql = "SELECT * FROM ai_suggestions 
        WHERE user_id = ? AND suggestion_type = 'repas' 
        ORDER BY created_at DESC 
        LIMIT 3";
$meal_suggestions = fetchAll($sql, [$user_id]);

// Récupérer les suggestions d'exercice récentes
$sql = "SELECT * FROM ai_suggestions 
        WHERE user_id = ? AND suggestion_type = 'exercice' 
        ORDER BY created_at DESC 
        LIMIT 3";
$exercise_suggestions = fetchAll($sql, [$user_id]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <!-- En-tête de la page -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-0">Tableau de bord</h1>
                <p class="text-muted">Bienvenue, <?php echo htmlspecialchars($user['username']); ?></p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="weight-log.php?action=add" class="btn btn-primary">
                    <i class="fas fa-weight me-1"></i>Ajouter un poids
                </a>
                <a href="food-log.php?action=add" class="btn btn-success">
                    <i class="fas fa-utensils me-1"></i>Ajouter un repas
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

        <!-- Résumé du jour -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Résumé du jour</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stat-icon bg-primary text-white">
                                            <i class="fas fa-fire"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">Calories consommées</h6>
                                        <h4 class="mb-0"><?php echo number_format($calories_in); ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stat-icon bg-success text-white">
                                            <i class="fas fa-running"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">Calories brûlées</h6>
                                        <h4 class="mb-0"><?php echo number_format($calories_out); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stat-icon bg-info text-white">
                                            <i class="fas fa-balance-scale"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">Bilan calorique</h6>
                                        <h4 class="mb-0"><?php echo number_format($net_calories); ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stat-icon bg-warning text-white">
                                            <i class="fas fa-bullseye"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">Objectif calorique</h6>
                                        <h4 class="mb-0"><?php echo number_format($calorie_goal); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="progress mb-2" style="height: 20px;">
                            <?php 
                            $percentage = min(100, max(0, ($net_calories / $calorie_goal) * 100));
                            $bg_class = $percentage > 100 ? 'bg-danger' : 'bg-success';
                            ?>
                            <div class="progress-bar <?php echo $bg_class; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo number_format($percentage, 1); ?>%
                            </div>
                        </div>
                        
                        <p class="text-center mb-3">
                            <?php if ($remaining_calories > 0): ?>
                                <span class="text-success">Il vous reste <?php echo number_format($remaining_calories); ?> calories à consommer aujourd'hui</span>
                            <?php else: ?>
                                <span class="text-danger">Vous avez dépassé votre objectif de <?php echo number_format(abs($remaining_calories)); ?> calories</span>
                            <?php endif; ?>
                        </p>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <h6>Protéines</h6>
                                <div class="d-flex justify-content-center align-items-center">
                                    <h5 class="mb-0 me-2"><?php echo $protein_in; ?>g</h5>
                                    <small class="text-muted">/ <?php echo round($protein_goal); ?>g</small>
                                </div>
                                <?php if ($protein_remaining > 0): ?>
                                    <small class="text-success">Reste: <?php echo $protein_remaining; ?>g</small>
                                <?php else: ?>
                                    <small class="text-success">Objectif atteint</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-4">
                                <h6>Glucides</h6>
                                <div class="d-flex justify-content-center align-items-center">
                                    <h5 class="mb-0 me-2"><?php echo $carbs_in; ?>g</h5>
                                    <small class="text-muted">/ <?php echo round($carbs_goal); ?>g</small>
                                </div>
                                <?php if ($carbs_remaining > 0): ?>
                                    <small class="text-success">Reste: <?php echo $carbs_remaining; ?>g</small>
                                <?php else: ?>
                                    <small class="text-success">Objectif atteint</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-4">
                                <h6>Lipides</h6>
                                <div class="d-flex justify-content-center align-items-center">
                                    <h5 class="mb-0 me-2"><?php echo $fat_in; ?>g</h5>
                                    <small class="text-muted">/ <?php echo round($fat_goal); ?>g</small>
                                </div>
                                <?php if ($fat_remaining > 0): ?>
                                    <small class="text-success">Reste: <?php echo $fat_remaining; ?>g</small>
                                <?php else: ?>
                                    <small class="text-success">Objectif atteint</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="row">
                            <div class="col-6">
                                <a href="food-log.php" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-utensils me-1"></i>Journal alimentaire
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="exercise-log.php" class="btn btn-outline-success btn-sm w-100">
                                    <i class="fas fa-running me-1"></i>Journal d'exercices
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Informations personnelles</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stat-icon bg-primary text-white">
                                            <i class="fas fa-weight"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">Poids actuel</h6>
                                        <h4 class="mb-0">
                                            <?php echo $latest_weight ? number_format($latest_weight['weight'], 1) . ' kg' : '—'; ?>
                                        </h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stat-icon bg-info text-white">
                                            <i class="fas fa-ruler-vertical"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">Taille</h6>
                                        <h4 class="mb-0">
                                            <?php echo $profile && $profile['height'] ? $profile['height'] . ' cm' : '—'; ?>
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stat-icon bg-warning text-white">
                                            <i class="fas fa-calculator"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">IMC</h6>
                                        <?php if ($latest_weight && $profile && $profile['height'] > 0): ?>
                                            <?php 
                                            $weight_kg = $latest_weight['weight'];
                                            $height_m = $profile['height'] / 100;
                                            $bmi = $weight_kg / ($height_m * $height_m);
                                            ?>
                                            <h4 class="mb-0"><?php echo number_format($bmi, 1); ?></h4>
                                            <small class="text-muted">
                                                <?php
                                                if ($bmi < 18.5) {
                                                    echo 'Insuffisance pondérale';
                                                } elseif ($bmi < 25) {
                                                    echo 'Poids normal';
                                                } elseif ($bmi < 30) {
                                                    echo 'Surpoids';
                                                } else {
                                                    echo 'Obésité';
                                                }
                                                ?>
                                            </small>
                                        <?php else: ?>
                                            <h4 class="mb-0">—</h4>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stat-icon bg-danger text-white">
                                            <i class="fas fa-bullseye"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">Objectif de poids</h6>
                                        <h4 class="mb-0">
                                            <?php echo $current_goal ? number_format($current_goal['target_weight'], 1) . ' kg' : '—'; ?>
                                        </h4>
                                        <?php if ($current_goal && $latest_weight): ?>
                                            <small class="text-muted">
                                                <?php 
                                                $diff = $current_goal['target_weight'] - $latest_weight['weight'];
                                                if ($diff < 0) {
                                                    echo 'Perdre ' . number_format(abs($diff), 1) . ' kg';
                                                } elseif ($diff > 0) {
                                                    echo 'Prendre ' . number_format($diff, 1) . ' kg';
                                                } else {
                                                    echo 'Maintenir le poids actuel';
                                                }
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($active_program): ?>
                            <div class="alert alert-info mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-dumbbell fa-2x me-3"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">Programme actif: <?php echo htmlspecialchars($active_program['name']); ?></h6>
                                        <p class="mb-0 small">Commencé le <?php echo $active_program['formatted_start_date']; ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="row">
                            <div class="col-6">
                                <a href="profile.php" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-user-edit me-1"></i>Modifier le profil
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="goals.php" class="btn btn-outline-success btn-sm w-100">
                                    <i class="fas fa-bullseye me-1"></i>Gérer les objectifs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Graphiques -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Évolution du poids</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($weight_values) > 1): ?>
                            <canvas id="weightChart" height="250"></canvas>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Pas assez de données pour afficher le graphique. Ajoutez au moins deux entrées de poids.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="weight-log.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-history me-1"></i>Voir l'historique complet
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Bilan calorique</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($calorie_dates) > 1): ?>
                            <canvas id="calorieChart" height="250"></canvas>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Pas assez de données pour afficher le graphique. Ajoutez des entrées alimentaires et d'exercices.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="calorie-history.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-history me-1"></i>Voir l'historique complet
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Suggestions IA -->
        <div class="row">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Suggestions de repas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($meal_suggestions)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Aucune suggestion de repas disponible. Visitez la page des suggestions IA pour en générer.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($meal_suggestions as $suggestion): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Suggestion du <?php echo date('d/m/Y', strtotime($suggestion['created_at'])); ?></h6>
                                            <small><?php echo $suggestion['is_applied'] ? '<span class="badge bg-success">Appliqué</span>' : '<span class="badge bg-secondary">Non appliqué</span>'; ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($suggestion['content'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="ai-suggestions.php" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-robot me-1"></i>Obtenir plus de suggestions
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Suggestions d'exercices</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($exercise_suggestions)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Aucune suggestion d'exercice disponible. Visitez la page des suggestions IA pour en générer.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($exercise_suggestions as $suggestion): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Suggestion du <?php echo date('d/m/Y', strtotime($suggestion['created_at'])); ?></h6>
                                            <small><?php echo $suggestion['is_applied'] ? '<span class="badge bg-success">Appliqué</span>' : '<span class="badge bg-secondary">Non appliqué</span>'; ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($suggestion['content'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="ai-suggestions.php" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-robot me-1"></i>Obtenir plus de suggestions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Graphique d'évolution du poids
        <?php if (count($weight_values) > 1): ?>
        const weightCtx = document.getElementById('weightChart').getContext('2d');
        const weightChart = new Chart(weightCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($weight_dates); ?>,
                datasets: [{
                    label: 'Poids (kg)',
                    data: <?php echo json_encode($weight_values); ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.2)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return value + ' kg';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        // Graphique du bilan calorique
        <?php if (count($calorie_dates) > 1): ?>
        const calorieCtx = document.getElementById('calorieChart').getContext('2d');
        const calorieChart = new Chart(calorieCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($calorie_dates); ?>,
                datasets: [
                    {
                        label: 'Calories consommées',
                        data: <?php echo json_encode($calories_in_values); ?>,
                        backgroundColor: 'rgba(25, 135, 84, 0.5)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Calories brûlées',
                        data: <?php echo json_encode($calories_out_values); ?>,
                        backgroundColor: 'rgba(220, 53, 69, 0.5)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Bilan calorique',
                        data: <?php echo json_encode($net_calories_values); ?>,
                        backgroundColor: 'rgba(13, 110, 253, 0.5)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 2,
                        type: 'line'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' kcal';
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
