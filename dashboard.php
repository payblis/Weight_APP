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
$sql = "SELECT u.*, up.*, 
        TIMESTAMPDIFF(YEAR, up.birth_date, CURDATE()) as age
        FROM users u 
        LEFT JOIN user_profiles up ON u.id = up.user_id 
        WHERE u.id = ?";
$user = fetchOne($sql, [$user_id]);

// Debug des données utilisateur
error_log("Données utilisateur pour le calcul d'eau :");
error_log("Poids : " . ($user['weight'] ?? 'non défini'));
error_log("Taille : " . ($user['height'] ?? 'non défini'));
error_log("Niveau d'activité : " . ($user['activity_level'] ?? 'non défini'));
error_log("Âge : " . ($user['age'] ?? 'non défini'));

// Calculer la recommandation d'hydratation
$water_goal = calculateWaterGoal($user);

// Mettre à jour le water_goal de l'utilisateur
$sql = "UPDATE users SET water_goal = ? WHERE id = ?";
update($sql, [$water_goal, $user_id]);
$user['water_goal'] = $water_goal;

// Récupérer la consommation d'eau du jour
$sql = "SELECT SUM(amount) as total_amount FROM water_logs WHERE user_id = ? AND log_date = CURDATE()";
$water_log = fetchOne($sql, [$user_id]);
$water_consumed = $water_log['total_amount'] ?? 0;
$water_percentage = $water_goal > 0 ? min(100, ($water_consumed / $water_goal) * 100) : 0;

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
    // Utiliser les calories stockées dans le profil
    $calorie_goal = $profile['daily_calories'] ?? 0;
    
    // Calculer les objectifs de macronutriments
    if ($active_program) {
        // Utiliser les ratios du programme
        $protein_ratio = $active_program['protein_ratio'];
        $carbs_ratio = $active_program['carbs_ratio'];
        $fat_ratio = $active_program['fat_ratio'];
    } else {
        // Utiliser les ratios par défaut du profil
        $protein_ratio = $profile['protein_ratio'] ?? 0.3;
        $carbs_ratio = $profile['carbs_ratio'] ?? 0.4;
        $fat_ratio = $profile['fat_ratio'] ?? 0.3;
    }
    
    // Calculer les objectifs de macronutriments en grammes
    $protein_goal = ($calorie_goal * $protein_ratio) / 4; // 4 calories par gramme de protéine
    $carbs_goal = ($calorie_goal * $carbs_ratio) / 4; // 4 calories par gramme de glucide
    $fat_goal = ($calorie_goal * $fat_ratio) / 9; // 9 calories par gramme de lipide
}

// Récupérer les données caloriques pour aujourd'hui
$today = date('Y-m-d');
$sql = "SELECT 
            SUM(m.total_calories) as calories_in,
            SUM(m.total_protein) as protein_in,
            SUM(m.total_carbs) as carbs_in,
            SUM(m.total_fat) as fat_in
        FROM meals m
        WHERE m.user_id = ? AND m.log_date = ?";
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
            m.log_date,
            DATE_FORMAT(m.log_date, '%d/%m') as formatted_date,
            SUM(m.total_calories) as calories_in,
            (SELECT SUM(el.calories_burned) FROM exercise_logs el WHERE el.user_id = ? AND el.log_date = m.log_date) as calories_out
        FROM meals m
        WHERE m.user_id = ? 
        GROUP BY m.log_date
        ORDER BY m.log_date DESC 
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

// Récupérer les notifications de repas
$meal_notifications = checkMealNotifications($user_id);
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
        <!-- Section des notifications importantes -->
        <div class="notifications-section mb-4">
            <?php if (!$current_goal): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important !</strong> Vous n'avez pas encore défini d'objectif de poids. 
                    <a href="goals.php" class="alert-link">Définissez un objectif</a> pour suivre votre progression et recevoir des recommandations personnalisées.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

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
        </div>

        <!-- Section des notifications de repas -->
        <div class="meal-notifications-section mb-4">
            <?php
            $notifications = checkMealNotifications($_SESSION['user_id']);
            foreach ($notifications as $notification): 
                $alertClass = $notification['priority'] == 2 ? 'alert-danger' : 'alert-warning';
                $icon = $notification['priority'] == 2 ? 'exclamation-triangle' : 'bell';
            ?>
                <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show meal-notification" 
                     role="alert"
                     data-start-time="<?php echo $notification['start_time']; ?>"
                     data-end-time="<?php echo $notification['end_time']; ?>"
                     data-priority="<?php echo $notification['priority']; ?>"
                     data-message="<?php echo htmlspecialchars($notification['message']); ?>">
                    <i class="fas fa-<?php echo $icon; ?> me-2"></i>
                    <?php echo $notification['message']; ?>
                    <a href="<?php echo $notification['action_url']; ?>" class="alert-link ms-2">
                        <i class="fas fa-plus-circle me-1"></i>Ajouter ce repas
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        </div>

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
        
        <!-- Après la section des objectifs -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tint me-2"></i>Hydratation
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h6>Objectif quotidien : <?php echo number_format($user['water_goal'], 1); ?> L</h6>
                        <div class="water-bottle-container position-relative" style="height: 400px; width: 200px; margin: 0 auto;">
                            <!-- Bouteille d'eau SVG -->
                            <svg viewBox="0 0 200 400" class="water-bottle" style="width: 100%; height: 100%;">
                                <!-- Corps de la bouteille -->
                                <path d="M60,50 L80,50 L90,20 L110,20 L120,50 L140,50 C140,50 145,60 145,70 L145,350 C145,360 140,370 140,370 L60,370 C60,370 55,360 55,350 L55,70 C55,60 60,50 60,50" 
                                      fill="none" stroke="#0dcaf0" stroke-width="3"/>
                                
                                <!-- Niveau d'eau -->
                                <rect x="60" y="<?php echo 370 - ($water_percentage * 3.2); ?>" 
                                      width="80" height="<?php echo $water_percentage * 3.2; ?>" 
                                      fill="rgba(13, 202, 240, 0.5)" rx="5"/>
                                
                                <!-- Marqueurs de niveau -->
                                <?php
                                $markers = [25, 50, 75, 100];
                                foreach ($markers as $marker):
                                    $y = 370 - ($marker * 3.2);
                                ?>
                                <line x1="55" y1="<?php echo $y; ?>" x2="145" y2="<?php echo $y; ?>" 
                                      stroke="#0dcaf0" stroke-width="2" stroke-dasharray="5,5"/>
                                <text x="150" y="<?php echo $y + 5; ?>" fill="#0dcaf0" font-size="12">
                                    <?php echo $marker; ?>%
                                </text>
                                <?php endforeach; ?>
                                
                                <!-- Goulot de la bouteille -->
                                <path d="M85,20 L90,10 L110,10 L115,20" 
                                      fill="none" stroke="#0dcaf0" stroke-width="3"/>
                            </svg>
                            
                            <!-- Affichage de la quantité -->
                            <div class="water-amount" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.5rem; font-weight: bold; color: #0dcaf0; text-shadow: 1px 1px 2px rgba(0,0,0,0.2);">
                                <?php echo number_format($water_consumed, 1); ?> L
                            </div>
                        </div>
                        <small class="text-muted mt-2">
                            <?php echo number_format($water_consumed, 1); ?> L sur <?php echo number_format($user['water_goal'], 1); ?> L
                        </small>
                    </div>
                    
                    <div class="row g-3">
                        <?php
                        // Paliers de remplissage (en cl)
                        $fill_levels = [25, 50, 75, 100];
                        foreach ($fill_levels as $level):
                            $amount = ($user['water_goal'] * $level) / 100; // Convertir le pourcentage en litres
                        ?>
                        <div class="col-6">
                            <form action="water-log.php" method="POST" class="h-100">
                                <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                                <button type="submit" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                    <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                    <span>Ajouter <?php echo $level; ?>%</span>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#resetWaterModal">
                            <i class="fas fa-undo me-1"></i>Réinitialiser le compteur
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de réinitialisation -->
        <div class="modal fade" id="resetWaterModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Réinitialiser le compteur d'eau</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir réinitialiser votre consommation d'eau du jour ?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <form action="water-log.php" method="POST" class="d-inline">
                            <input type="hidden" name="action" value="reset">
                            <button type="submit" class="btn btn-danger">Réinitialiser</button>
                        </form>
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

        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour formater l'heure en format 24h
            function formatTime(date) {
                return date.toLocaleTimeString('fr-FR', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: false 
                });
            }

            // Fonction pour convertir une heure en minutes depuis minuit
            function timeToMinutes(timeStr) {
                const [hours, minutes] = timeStr.split(':').map(Number);
                return hours * 60 + minutes;
            }

            // Fonction pour vérifier si une notification doit être affichée
            function shouldShowNotification(startTime, endTime) {
                const now = new Date();
                const currentTime = formatTime(now);
                const currentMinutes = timeToMinutes(currentTime);
                const startMinutes = timeToMinutes(startTime);
                
                console.log("=== Vérification de l'affichage de la notification ===");
                console.log("Heure actuelle (locale) : " + currentTime);
                console.log("Heure de début : " + startTime);
                console.log("Minutes actuelles depuis minuit : " + currentMinutes);
                console.log("Minutes de début depuis minuit : " + startMinutes);
                console.log("Fuseau horaire : " + Intl.DateTimeFormat().resolvedOptions().timeZone);
                
                // Si l'heure actuelle est après l'heure de début, afficher la notification
                const shouldShow = currentMinutes >= startMinutes;
                console.log("Notification doit être affichée : " + shouldShow);
                return shouldShow;
            }

            // Fonction pour mettre à jour l'affichage des notifications
            function updateNotifications() {
                const notifications = document.querySelectorAll('.meal-notification');
                console.log("=== Mise à jour des notifications ===");
                console.log("Nombre de notifications trouvées : " + notifications.length);
                
                notifications.forEach(notification => {
                    const startTime = notification.dataset.startTime;
                    const endTime = notification.dataset.endTime;
                    const priority = parseInt(notification.dataset.priority);
                    const message = notification.dataset.message;
                    
                    console.log("\nTraitement de la notification : " + message);
                    console.log("Priorité : " + priority);
                    console.log("Heure de début : " + startTime);
                    console.log("Heure de fin : " + endTime);
                    
                    if (shouldShowNotification(startTime, endTime)) {
                        notification.style.display = 'block';
                        // Mettre à jour la classe d'urgence si nécessaire
                        if (priority === 2) {
                            notification.classList.remove('alert-warning');
                            notification.classList.add('alert-danger');
                        }
                        console.log("Notification affichée : " + message);
                    } else {
                        notification.style.display = 'none';
                        console.log("Notification masquée : " + message);
                    }
                });
            }

            // Mettre à jour les notifications immédiatement et toutes les minutes
            updateNotifications();
            setInterval(updateNotifications, 60000);
        });
    </script>
</body>
</html>
