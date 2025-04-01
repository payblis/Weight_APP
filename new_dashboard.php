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
    // Utiliser les calories stockées dans le profil ou les calculer si non définies
    $calorie_goal = $profile['daily_calories'] ?? 0;
    
    if ($calorie_goal <= 0) {
        // Calculer le BMR
        $bmr = calculateBMR($latest_weight['weight'], $profile['height'], $profile['birth_date'], $profile['gender']);
        
        // Calculer le TDEE
        $tdee = calculateTDEE($bmr, $profile['activity_level']);
        
        // Ajuster selon le programme actif ou l'objectif
        if ($active_program) {
            $program_adjustment = $tdee * ($active_program['calorie_adjustment'] / 100);
            $calorie_goal = $tdee + $program_adjustment;
        } elseif ($current_goal) {
            $weight_diff = $current_goal['target_weight'] - $latest_weight['weight'];
            if ($weight_diff < 0) {
                // Perte de poids
                $calorie_goal = $tdee - 500;
            } elseif ($weight_diff > 0) {
                // Prise de poids
                $calorie_goal = $tdee + 500;
            } else {
                // Maintien
                $calorie_goal = $tdee;
            }
        } else {
            // Pas d'objectif ni de programme, utiliser le TDEE
            $calorie_goal = $tdee;
        }
        
        // Mettre à jour le profil avec les nouvelles calories
        $sql = "UPDATE user_profiles SET daily_calories = ? WHERE user_id = ?";
        update($sql, [$calorie_goal, $user_id]);
    }
    
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

// Section des invitations de groupe
$pending_invitations = getUserPendingInvitations($user_id);

// Calculer le nombre de jours d'affilée
$streak_days = 3; // Exemple statique, à remplacer par la vraie logique
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <div class="dashboard-container">
            <?php if (!empty($pending_invitations)): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope me-2"></i>Invitations de groupe
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($pending_invitations as $invitation): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-1">
                                    <i class="fas fa-users me-2"></i>
                                    <?php echo htmlspecialchars($invitation['group_name']); ?>
                                </h6>
                                <small class="text-muted">
                                    Invité par <?php echo htmlspecialchars($invitation['invited_by_name']); ?>
                                </small>
                            </div>
                            <div>
                                <form method="POST" action="handle-invitation.php" class="d-inline">
                                    <input type="hidden" name="invitation_id" value="<?php echo $invitation['id']; ?>">
                                    <button type="submit" name="action" value="accept" class="btn btn-sm btn-success me-2">
                                        <i class="fas fa-check me-1"></i>Accepter
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
                                        <i class="fas fa-times me-1"></i>Refuser
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- En-tête avec logo et jours d'affilée -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <div class="logo-container me-3">
                        <img src="assets/img/logo.png" alt="Logo" class="logo-img">
                    </div>
                    <h4 class="mb-0">Aujourd'hui</h4>
                </div>
                <div class="streak-container">
                    <div class="streak-count"><?php echo $streak_days; ?></div>
                    <div class="streak-label">JOURS<br>D'AFFILÉE</div>
                </div>
            </div>

            <!-- Version mobile -->
            <div class="mobile-dashboard d-md-none">
                <!-- Calories -->
                <div class="dashboard-card mb-4">
                    <h5 class="card-title">Calories</h5>
                    <div class="card-subtitle mb-3">Reste = Objectif - Aliments + Exercices</div>
                    
                    <div class="calories-container">
                        <div class="calories-circle">
                            <svg viewBox="0 0 36 36">
                                <path class="circle-bg"
                                    d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                                <path class="circle"
                                    d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                    stroke-dasharray="<?php 
                                        $calories_consumed = $calories_in - $calories_out;
                                        $percentage = min(100, max(0, ($calories_consumed / $calorie_goal) * 100));
                                        echo $percentage . ', 100';
                                    ?>"
                                />
                            </svg>
                            <div class="calories-value"><?php echo number_format($remaining_calories); ?></div>
                            <div class="calories-label">Reste</div>
                        </div>

                        <div class="calories-details">
                            <div class="detail-item">
                                <div class="detail-icon"><i class="fas fa-flag"></i></div>
                                <div class="detail-label">Objectif de base</div>
                                <div class="detail-value"><?php echo number_format($calorie_goal); ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-icon"><i class="fas fa-utensils"></i></div>
                                <div class="detail-label">Aliments</div>
                                <div class="detail-value"><?php echo number_format($calories_in); ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-icon"><i class="fas fa-fire"></i></div>
                                <div class="detail-label">Exercices</div>
                                <div class="detail-value"><?php echo number_format($calories_out); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Indicateurs de pagination -->
                    <div class="pagination-dots">
                        <span class="dot active"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                </div>
                
                <!-- Macronutriments -->
                <div class="dashboard-card mb-4">
                    <h5 class="card-title">Macronutriments</h5>
                    
                    <div class="macros-container">
                        <div class="macro-item">
                            <div class="macro-circle protein">
                                <svg viewBox="0 0 36 36">
                                    <path class="circle-bg"
                                        d="M18 2.0845
                                        a 15.9155 15.9155 0 0 1 0 31.831
                                        a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                    <path class="circle"
                                        d="M18 2.0845
                                        a 15.9155 15.9155 0 0 1 0 31.831
                                        a 15.9155 15.9155 0 0 1 0 -31.831"
                                        stroke-dasharray="<?php 
                                            $percentage = min(100, max(0, ($protein_in / $protein_goal) * 100));
                                            echo $percentage . ', 100';
                                        ?>"
                                    />
                                </svg>
                                <div class="macro-percentage"><?php echo round(($protein_in / $protein_goal) * 100); ?>%</div>
                            </div>
                            <div class="macro-name">Protéines</div>
                            <div class="macro-value"><?php echo number_format($protein_in, 1); ?>g</div>
                            <div class="macro-goal">Objectif: <?php echo number_format($protein_goal, 1); ?>g</div>
                        </div>
                        
                        <div class="macro-item">
                            <div class="macro-circle carbs">
                                <svg viewBox="0 0 36 36">
                                    <path class="circle-bg"
                                        d="M18 2.0845
                                        a 15.9155 15.9155 0 0 1 0 31.831
                                        a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                    <path class="circle"
                                        d="M18 2.0845
                                        a 15.9155 15.9155 0 0 1 0 31.831
                                        a 15.9155 15.9155 0 0 1 0 -31.831"
                                        stroke-dasharray="<?php 
                                            $percentage = min(100, max(0, ($carbs_in / $carbs_goal) * 100));
                                            echo $percentage . ', 100';
                                        ?>"
                                    />
                                </svg>
                                <div class="macro-percentage"><?php echo round(($carbs_in / $carbs_goal) * 100); ?>%</div>
                            </div>
                            <div class="macro-name">Glucides</div>
                            <div class="macro-value"><?php echo number_format($carbs_in, 1); ?>g</div>
                            <div class="macro-goal">Objectif: <?php echo number_format($carbs_goal, 1); ?>g</div>
                        </div>
                        
                        <div class="macro-item">
                            <div class="macro-circle fats">
                                <svg viewBox="0 0 36 36">
                                    <path class="circle-bg"
                                        d="M18 2.0845
                                        a 15.9155 15.9155 0 0 1 0 31.831
                                        a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                    <path class="circle"
                                        d="M18 2.0845
                                        a 15.9155 15.9155 0 0 1 0 31.831
                                        a 15.9155 15.9155 0 0 1 0 -31.831"
                                        stroke-dasharray="<?php 
                                            $percentage = min(100, max(0, ($fat_in / $fat_goal) * 100));
                                            echo $percentage . ', 100';
                                        ?>"
                                    />
                                </svg>
                                <div class="macro-percentage"><?php echo round(($fat_in / $fat_goal) * 100); ?>%</div>
                            </div>
                            <div class="macro-name">Lipides</div>
                            <div class="macro-value"><?php echo number_format($fat_in, 1); ?>g</div>
                            <div class="macro-goal">Objectif: <?php echo number_format($fat_goal, 1); ?>g</div>
                        </div>
                    </div>
                </div>
                
                <!-- Hydratation -->
                <div class="dashboard-card mb-4">
                    <h5 class="card-title">Hydratation</h5>
                    
                    <div class="hydration-container">
                        <div class="water-drop">
                            <svg viewBox="0 0 100 100">
                                <path class="drop-bg" d="M50,10 L70,40 A30,30 0 1,1 30,40 L50,10" />
                                <path class="drop-fill" d="M50,10 L70,40 A30,30 0 1,1 30,40 L50,10" 
                                      transform="translate(0,<?php echo 100 - min(100, $water_percentage); ?>)" />
                            </svg>
                            <div class="water-value"><?php echo number_format($water_consumed, 1); ?>L</div>
                        </div>
                        
                        <div class="water-actions">
                            <div class="row g-2">
                                <?php
                                $water_amounts = [0.25, 0.5, 0.75, 1];
                                foreach ($water_amounts as $amount):
                                ?>
                                <div class="col-6">
                                    <form action="water-log.php" method="POST">
                                        <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                                        <button type="submit" class="btn btn-water">
                                            +<?php echo number_format($amount, 2); ?>L
                                        </button>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations personnelles -->
                <div class="dashboard-card mb-4">
                    <h5 class="card-title">Informations personnelles</h5>
                    
                    <div class="info-grid">
                        <!-- Poids actuel -->
                        <div class="info-item">
                            <div class="info-icon weight-icon">
                                <i class="fas fa-weight"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Poids actuel</div>
                                <div class="info-value">
                                    <?php echo $latest_weight ? number_format($latest_weight['weight'], 1) . ' kg' : '—'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Taille -->
                        <div class="info-item">
                            <div class="info-icon height-icon">
                                <i class="fas fa-ruler-vertical"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Taille</div>
                                <div class="info-value">
                                    <?php echo $profile && $profile['height'] ? $profile['height'] . ' cm' : '—'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- IMC -->
                        <div class="info-item">
                            <div class="info-icon bmi-icon">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">IMC</div>
                                <?php if ($latest_weight && $profile && $profile['height'] > 0): 
                                    $weight_kg = $latest_weight['weight'];
                                    $height_m = $profile['height'] / 100;
                                    $bmi = $weight_kg / ($height_m * $height_m);
                                ?>
                                    <div class="info-value"><?php echo number_format($bmi, 1); ?></div>
                                    <div class="info-subtext">
                                        <?php
                                        if ($bmi < 18.5) echo 'Insuffisance pondérale';
                                        elseif ($bmi < 25) echo 'Poids normal';
                                        elseif ($bmi < 30) echo 'Surpoids';
                                        else echo 'Obésité';
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <div class="info-value">—</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Objectif de poids -->
                        <div class="info-item">
                            <div class="info-icon goal-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Objectif</div>
                                <div class="info-value">
                                    <?php echo $current_goal ? number_format($current_goal['target_weight'], 1) . ' kg' : '—'; ?>
                                </div>
                                <?php if ($current_goal && $latest_weight): 
                                    $diff = $current_goal['target_weight'] - $latest_weight['weight'];
                                ?>
                                    <div class="info-subtext">
                                        <?php
                                        if ($diff < 0) echo 'Perdre ' . number_format(abs($diff), 1) . ' kg';
                                        elseif ($diff > 0) echo 'Prendre ' . number_format($diff, 1) . ' kg';
                                        else echo 'Maintenir le poids actuel';
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($active_program): ?>
                    <div class="program-alert">
                        <div class="program-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="program-content">
                            <div class="program-name">Programme actif: <?php echo htmlspecialchars($active_program['name']); ?></div>
                            <div class="program-date">Commencé le <?php echo $active_program['formatted_start_date']; ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="profile-actions">
                        <a href="profile.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-edit"></i> Modifier le profil
                        </a>
                        <a href="goals.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-bullseye"></i> Gérer les objectifs
                        </a>
                    </div>
                </div>
            </div>

            <!-- Version desktop -->
            <div class="desktop-dashboard d-none d-md-block">
                <div class="row g-4">
                    <!-- Calories -->
                    <div class="col-md-6">
                        <div class="dashboard-card h-100">
                            <h5 class="card-title">Calories</h5>
                            <div class="card-subtitle mb-3">Reste = Objectif - Aliments + Exercices</div>
                            
                            <div class="calories-container">
                                <div class="calories-circle">
                                    <svg viewBox="0 0 36 36">
                                        <path class="circle-bg"
                                            d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                        />
                                        <path class="circle"
                                            d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                            stroke-dasharray="<?php 
                                                $calories_consumed = $calories_in - $calories_out;
                                                $percentage = min(100, max(0, ($calories_consumed / $calorie_goal) * 100));
                                                echo $percentage . ', 100';
                                            ?>"
                                        />
                                    </svg>
                                    <div class="calories-value"><?php echo number_format($remaining_calories); ?></div>
                                    <div class="calories-label">Reste</div>
                                </div>

                                <div class="calories-details">
                                    <div class="detail-item">
                                        <div class="detail-icon"><i class="fas fa-flag"></i></div>
                                        <div class="detail-label">Objectif de base</div>
                                        <div class="detail-value"><?php echo number_format($calorie_goal); ?></div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-icon"><i class="fas fa-utensils"></i></div>
                                        <div class="detail-label">Aliments</div>
                                        <div class="detail-value"><?php echo number_format($calories_in); ?></div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-icon"><i class="fas fa-fire"></i></div>
                                        <div class="detail-label">Exercices</div>
                                        <div class="detail-value"><?php echo number_format($calories_out); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Macronutriments -->
                    <div class="col-md-6">
                        <div class="dashboard-card h-100">
                            <h5 class="card-title">Macronutriments</h5>
                            
                            <div class="macros-container">
                                <div class="macro-item">
                                    <div class="macro-circle protein">
                                        <svg viewBox="0 0 36 36">
                                            <path class="circle-bg"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                            />
                                            <path class="circle"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                                stroke-dasharray="<?php 
                                                    $percentage = min(100, max(0, ($protein_in / $protein_goal) * 100));
                                                    echo $percentage . ', 100';
                                                ?>"
                                            />
                                        </svg>
                                        <div class="macro-percentage"><?php echo round(($protein_in / $protein_goal) * 100); ?>%</div>
                                    </div>
                                    <div class="macro-name">Protéines</div>
                                    <div class="macro-value"><?php echo number_format($protein_in, 1); ?>g</div>
                                    <div class="macro-goal">Objectif: <?php echo number_format($protein_goal, 1); ?>g</div>
                                </div>
                                
                                <div class="macro-item">
                                    <div class="macro-circle carbs">
                                        <svg viewBox="0 0 36 36">
                                            <path class="circle-bg"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                            />
                                            <path class="circle"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                                stroke-dasharray="<?php 
                                                    $percentage = min(100, max(0, ($carbs_in / $carbs_goal) * 100));
                                                    echo $percentage . ', 100';
                                                ?>"
                                            />
                                        </svg>
                                        <div class="macro-percentage"><?php echo round(($carbs_in / $carbs_goal) * 100); ?>%</div>
                                    </div>
                                    <div class="macro-name">Glucides</div>
                                    <div class="macro-value"><?php echo number_format($carbs_in, 1); ?>g</div>
                                    <div class="macro-goal">Objectif: <?php echo number_format($carbs_goal, 1); ?>g</div>
                                </div>
                                
                                <div class="macro-item">
                                    <div class="macro-circle fats">
                                        <svg viewBox="0 0 36 36">
                                            <path class="circle-bg"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                            />
                                            <path class="circle"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                                stroke-dasharray="<?php 
                                                    $percentage = min(100, max(0, ($fat_in / $fat_goal) * 100));
                                                    echo $percentage . ', 100';
                                                ?>"
                                            />
                                        </svg>
                                        <div class="macro-percentage"><?php echo round(($fat_in / $fat_goal) * 100); ?>%</div>
                                    </div>
                                    <div class="macro-name">Lipides</div>
                                    <div class="macro-value"><?php echo number_format($fat_in, 1); ?>g</div>
                                    <div class="macro-goal">Objectif: <?php echo number_format($fat_goal, 1); ?>g</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-4 mt-4">
                    <!-- Hydratation -->
                    <div class="col-md-6">
                        <div class="dashboard-card h-100">
                            <h5 class="card-title">Hydratation</h5>
                            
                            <div class="hydration-container">
                                <div class="water-drop">
                                    <svg viewBox="0 0 100 100">
                                        <path class="drop-bg" d="M50,10 L70,40 A30,30 0 1,1 30,40 L50,10" />
                                        <path class="drop-fill" d="M50,10 L70,40 A30,30 0 1,1 30,40 L50,10" 
                                              transform="translate(0,<?php echo 100 - min(100, $water_percentage); ?>)" />
                                    </svg>
                                    <div class="water-value"><?php echo number_format($water_consumed, 1); ?>L</div>
                                </div>
                                
                                <div class="water-actions">
                                    <div class="row g-2">
                                        <?php
                                        $water_amounts = [0.25, 0.5, 0.75, 1];
                                        foreach ($water_amounts as $amount):
                                        ?>
                                        <div class="col-6">
                                            <form action="water-log.php" method="POST">
                                                <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                                                <button type="submit" class="btn btn-water">
                                                    +<?php echo number_format($amount, 2); ?>L
                                                </button>
                                            </form>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informations personnelles -->
                    <div class="col-md-6">
                        <div class="dashboard-card h-100">
                            <h5 class="card-title">Informations personnelles</h5>
                            
                            <div class="info-grid">
                                <!-- Poids actuel -->
                                <div class="info-item">
                                    <div class="info-icon weight-icon">
                                        <i class="fas fa-weight"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Poids actuel</div>
                                        <div class="info-value">
                                            <?php echo $latest_weight ? number_format($latest_weight['weight'], 1) . ' kg' : '—'; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Taille -->
                                <div class="info-item">
                                    <div class="info-icon height-icon">
                                        <i class="fas fa-ruler-vertical"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Taille</div>
                                        <div class="info-value">
                                            <?php echo $profile && $profile['height'] ? $profile['height'] . ' cm' : '—'; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- IMC -->
                                <div class="info-item">
                                    <div class="info-icon bmi-icon">
                                        <i class="fas fa-calculator"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">IMC</div>
                                        <?php if ($latest_weight && $profile && $profile['height'] > 0): 
                                            $weight_kg = $latest_weight['weight'];
                                            $height_m = $profile['height'] / 100;
                                            $bmi = $weight_kg / ($height_m * $height_m);
                                        ?>
                                            <div class="info-value"><?php echo number_format($bmi, 1); ?></div>
                                            <div class="info-subtext">
                                                <?php
                                                if ($bmi < 18.5) echo 'Insuffisance pondérale';
                                                elseif ($bmi < 25) echo 'Poids normal';
                                                elseif ($bmi < 30) echo 'Surpoids';
                                                else echo 'Obésité';
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="info-value">—</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Objectif de poids -->
                                <div class="info-item">
                                    <div class="info-icon goal-icon">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Objectif</div>
                                        <div class="info-value">
                                            <?php echo $current_goal ? number_format($current_goal['target_weight'], 1) . ' kg' : '—'; ?>
                                        </div>
                                        <?php if ($current_goal && $latest_weight): 
                                            $diff = $current_goal['target_weight'] - $latest_weight['weight'];
                                        ?>
                                            <div class="info-subtext">
                                                <?php
                                                if ($diff < 0) echo 'Perdre ' . number_format(abs($diff), 1) . ' kg';
                                                elseif ($diff > 0) echo 'Prendre ' . number_format($diff, 1) . ' kg';
                                                else echo 'Maintenir le poids actuel';
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($active_program): ?>
                            <div class="program-alert">
                                <div class="program-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="program-content">
                                    <div class="program-name">Programme actif: <?php echo htmlspecialchars($active_program['name']); ?></div>
                                    <div class="program-date">Commencé le <?php echo $active_program['formatted_start_date']; ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="profile-actions">
                                <a href="profile.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-user-edit"></i> Modifier le profil
                                </a>
                                <a href="goals.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-bullseye"></i> Gérer les objectifs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Graphiques et statistiques -->
                <div class="row g-4 mt-4">
                    <!-- Évolution du poids -->
                    <?php if (count($weight_values) > 1): ?>
                    <div class="col-md-6">
                        <div class="dashboard-card h-100">
                            <h5 class="card-title">Évolution du poids</h5>
                            <div class="chart-container">
                                <canvas id="weightChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Bilan calorique -->
                    <?php if (count($calorie_dates) > 1): ?>
                    <div class="col-md-6">
                        <div class="dashboard-card h-100">
                            <h5 class="card-title">Bilan calorique</h5>
                            <div class="chart-container">
                                <canvas id="calorieChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Suggestions IA -->
                <?php if (!empty($meal_suggestions) || !empty($exercise_suggestions)): ?>
                <div class="row g-4 mt-4">
                    <?php if (!empty($meal_suggestions)): ?>
                    <div class="col-md-6">
                        <div class="dashboard-card h-100">
                            <h5 class="card-title">Suggestions de repas</h5>
                            <div class="suggestions-container">
                                <?php foreach ($meal_suggestions as $suggestion): ?>
                                <div class="suggestion-item">
                                    <div class="suggestion-icon">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                    <div class="suggestion-content">
                                        <div class="suggestion-title"><?php echo htmlspecialchars($suggestion['title']); ?></div>
                                        <div class="suggestion-text"><?php echo htmlspecialchars($suggestion['content']); ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($exercise_suggestions)): ?>
                    <div class="col-md-6">
                        <div class="dashboard-card h-100">
                            <h5 class="card-title">Suggestions d'exercices</h5>
                            <div class="suggestions-container">
                                <?php foreach ($exercise_suggestions as $suggestion): ?>
                                <div class="suggestion-item">
                                    <div class="suggestion-icon">
                                        <i class="fas fa-dumbbell"></i>
                                    </div>
                                    <div class="suggestion-content">
                                        <div class="suggestion-title"><?php echo htmlspecialchars($suggestion['title']); ?></div>
                                        <div class="suggestion-text"><?php echo htmlspecialchars($suggestion['content']); ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                        backgroundColor: 'rgba(220, 53, 69, 0.2)',
                        borderColor: 'rgba(220, 53, 69, 1)',
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
            function shouldShowNotification(notification) {
                const now = new Date();
                const currentHours = now.getHours();
                const currentMinutes = now.getMinutes();
                const currentTime = currentHours * 60 + currentMinutes;
                
                const [startHours, startMinutes] = notification.dataset.startTime.split(':').map(Number);
                const startTime = startHours * 60 + startMinutes;
                
                const [endHours, endMinutes] = notification.dataset.endTime.split(':').map(Number);
                const endTime = endHours * 60 + endMinutes;
                
                console.log('Vérification de la notification:', {
                    message: notification.dataset.message,
                    currentTime: `${currentHours}:${currentMinutes}`,
                    startTime: notification.dataset.startTime,
                    endTime: notification.dataset.endTime,
                    currentMinutes: currentTime,
                    startMinutes: startTime,
                    endMinutes: endTime,
                    priority: notification.dataset.priority
                });
                
                // Afficher la notification si :
                // 1. L'heure actuelle est après l'heure de début
                // 2. L'heure actuelle est avant l'heure de fin OU le repas est en retard (plus de 2h après l'heure de début)
                const isLate = currentTime - startTime > 120; // Plus de 2 heures de retard
                const shouldShow = currentTime >= startTime && (currentTime <= endTime || isLate);
                
                console.log('Décision d\'affichage:', {
                    message: notification.dataset.message,
                    shouldShow: shouldShow,
                    isLate: isLate,
                    timeDiff: currentTime - startTime
                });
                
                return shouldShow;
            }

            // Fonction pour mettre à jour l'affichage des notifications
            function updateNotifications() {
                const notifications = document.querySelectorAll('.meal-notification');
                console.log("=== Mise à jour des notifications ===");
                console.log("Nombre de notifications trouvées : " + notifications.length);
                
                notifications.forEach(notification => {
                    if (shouldShowNotification(notification)) {
                        notification.style.display = 'block';
                        // Mettre à jour la classe d'urgence si nécessaire
                        if (notification.dataset.priority === '2') {
                            notification.classList.remove('alert-warning');
                            notification.classList.add('alert-danger');
                        }
                        console.log("Notification affichée : " + notification.dataset.message);
                    } else {
                        notification.style.display = 'none';
                        console.log("Notification masquée : " + notification.dataset.message);
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
