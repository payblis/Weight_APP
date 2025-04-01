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
if (!empty($pending_invitations)):
?>
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
    <style>
        .dashboard-container {
            max-width: 992px;
            margin: 0 auto;
        }
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 0;
            }
            .container {
                padding: 0.5rem;
            }
            .card {
                padding: 0.75rem;
                margin-bottom: 1rem;
            }
            .card-body {
                padding: 0.5rem;
            }
            .calories-container {
                flex-direction: row;
                align-items: flex-start;
                gap: 1rem;
                padding: 0;
            }
            .calories-details {
                padding-top: 0.5rem;
            }
            .progress-circle {
                width: 120px;
                height: 120px;
            }
            .progress-circle h2 {
                font-size: 1.5rem;
                margin-bottom: 0;
            }
            .progress-circle .text-muted {
                font-size: 0.8rem;
            }
            .card-title {
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }
            .text-muted {
                font-size: 0.8rem;
            }
            .calories-details .d-flex {
                margin-bottom: 0.5rem;
            }
            .calories-details .d-flex:last-child {
                margin-bottom: 0;
            }
            .calories-details i {
                font-size: 0.9rem;
            }
            .calories-details span {
                font-size: 0.9rem;
            }
            .calories-details strong {
                font-size: 0.9rem;
            }
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            height: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-bottom: 2rem;
        }
        .calories-container {
            display: flex;
            align-items: flex-start;
            gap: 2rem;
            padding: 1rem;
        }
        .calories-details {
            flex: 1;
            padding-top: 1rem;
        }
        .progress-circle {
            width: 180px;
            height: 180px;
            flex-shrink: 0;
        }
        @media (min-width: 768px) {
            .exercise-swiper .swiper-slide {
                width: 23%;
            }
        }
        .exercise-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin-bottom: 0;
        }
        .macro-circle {
            width: 100px;
            height: 100px;
            margin: 0 auto;
        }
        .water-drop {
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }
        .info-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .macro-goal {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .exercise-icon {
            width: 32px;
            height: 32px;
            background: #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }
        .swiper-pagination {
            position: relative;
            margin-top: 1rem;
        }
        .row {
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <div class="dashboard-container">
            <!-- En-tête avec logo -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <img src="assets/img/logo.png" alt="Logo" class="me-2" style="height: 40px;">
                    <h4 class="mb-0">Aujourd'hui</h4>
                </div>
            </div>

            <!-- Slider pour mobile -->
            <div class="swiper dashboard-swiper d-md-none">
                <div class="swiper-wrapper">
                    <!-- Slide Calories -->
                    <div class="swiper-slide">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title mb-3">Calories</h5>
                                <p class="text-muted small mb-3">Reste = Objectif - Aliments + Exercices</p>
                                
                                <div class="calories-container">
                                    <div class="progress-circle position-relative">
                                        <svg width="180" height="180" viewBox="0 0 200 200">
                                            <circle cx="100" cy="100" r="90" fill="none" stroke="#f0f0f0" stroke-width="8"/>
                                            <circle cx="100" cy="100" r="90" fill="none" stroke="#0d6efd" stroke-width="8"
                                                stroke-dasharray="<?php 
                                                    $calories_consumed = $calories_in - $calories_out;
                                                    $percentage = ($calories_consumed / $calorie_goal) * 100;
                                                    echo min(100, $percentage) * 5.65;
                                                ?> 565"
                                                transform="rotate(-90 100 100)"/>
                                        </svg>
                                        <div class="position-absolute top-50 start-50 translate-middle text-center">
                                            <h2 class="mb-0"><?php echo number_format($remaining_calories); ?></h2>
                                            <span class="text-muted">Reste</span>
                                        </div>
                                    </div>

                                    <div class="calories-details">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-flag text-primary me-2"></i>
                                                <span>Objectif de base</span>
                                            </div>
                                            <strong><?php echo number_format($calorie_goal); ?></strong>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-utensils text-success me-2"></i>
                                                <span>Aliments</span>
                                            </div>
                                            <strong><?php echo number_format($calories_in); ?></strong>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-fire text-danger me-2"></i>
                                                <span>Exercices</span>
                                            </div>
                                            <strong><?php echo number_format($calories_out); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide Macronutriments -->
                    <div class="swiper-slide">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title mb-4">Macronutriments</h5>
                                
                                <div class="row g-4">
                                    <div class="col-4 text-center">
                                        <div class="macro-circle position-relative">
                                            <svg width="100" height="100" viewBox="0 0 100 100">
                                                <circle cx="50" cy="50" r="45" fill="none" stroke="#f0f0f0" stroke-width="6"/>
                                                <circle cx="50" cy="50" r="45" fill="none" stroke="#198754" stroke-width="6"
                                                    stroke-dasharray="<?php echo min(100, ($protein_in / $protein_goal) * 100) * 2.83; ?> 283"
                                                    transform="rotate(-90 50 50)"/>
                                            </svg>
                                            <div class="position-absolute top-50 start-50 translate-middle">
                                                <strong><?php echo round(($protein_in / $protein_goal) * 100); ?>%</strong>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <h6 class="mb-0">Protéines</h6>
                                            <small class="text-muted"><?php echo number_format($protein_in, 1); ?>g</small>
                                            <div class="macro-goal">Objectif: <?php echo number_format($protein_goal, 1); ?>g</div>
                                        </div>
                                    </div>

                                    <div class="col-4 text-center">
                                        <div class="macro-circle position-relative">
                                            <svg width="100" height="100" viewBox="0 0 100 100">
                                                <circle cx="50" cy="50" r="45" fill="none" stroke="#f0f0f0" stroke-width="6"/>
                                                <circle cx="50" cy="50" r="45" fill="none" stroke="#dc3545" stroke-width="6"
                                                    stroke-dasharray="<?php echo min(100, ($carbs_in / $carbs_goal) * 100) * 2.83; ?> 283"
                                                    transform="rotate(-90 50 50)"/>
                                            </svg>
                                            <div class="position-absolute top-50 start-50 translate-middle">
                                                <strong><?php echo round(($carbs_in / $carbs_goal) * 100); ?>%</strong>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <h6 class="mb-0">Glucides</h6>
                                            <small class="text-muted"><?php echo number_format($carbs_in, 1); ?>g</small>
                                            <div class="macro-goal">Objectif: <?php echo number_format($carbs_goal, 1); ?>g</div>
                                        </div>
                                    </div>

                                    <div class="col-4 text-center">
                                        <div class="macro-circle position-relative">
                                            <svg width="100" height="100" viewBox="0 0 100 100">
                                                <circle cx="50" cy="50" r="45" fill="none" stroke="#f0f0f0" stroke-width="6"/>
                                                <circle cx="50" cy="50" r="45" fill="none" stroke="#ffc107" stroke-width="6"
                                                    stroke-dasharray="<?php echo min(100, ($fat_in / $fat_goal) * 100) * 2.83; ?> 283"
                                                    transform="rotate(-90 50 50)"/>
                                            </svg>
                                            <div class="position-absolute top-50 start-50 translate-middle">
                                                <strong><?php echo round(($fat_in / $fat_goal) * 100); ?>%</strong>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <h6 class="mb-0">Lipides</h6>
                                            <small class="text-muted"><?php echo number_format($fat_in, 1); ?>g</small>
                                            <div class="macro-goal">Objectif: <?php echo number_format($fat_goal, 1); ?>g</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-3">
                                    <!-- Suppression du bouton Premium -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide Hydratation -->
                    <div class="swiper-slide">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Hydratation</h5>
                                <div class="text-center mb-4">
                                    <div class="water-drop position-relative">
                                        <svg width="150" height="150" viewBox="0 0 100 100">
                                            <path d="M50,10 L70,40 A30,30 0 1,1 30,40 L50,10" 
                                                  fill="#f0f0f0" stroke="#0dcaf0" stroke-width="2"/>
                                            <path d="M50,10 L70,40 A30,30 0 1,1 30,40 L50,10" 
                                                  fill="#0dcaf0" stroke="none"
                                                  transform="translate(0,<?php echo 100 - min(100, $water_percentage); ?>)"/>
                                        </svg>
                                        <div class="position-absolute top-50 start-50 translate-middle">
                                            <strong><?php echo number_format($water_consumed, 1); ?>L</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <?php
                                    $water_amounts = [0.25, 0.5, 0.75, 1];
                                    foreach ($water_amounts as $amount):
                                    ?>
                                    <div class="col-6">
                                        <form action="water-log.php" method="POST">
                                            <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                                            <button type="submit" class="btn btn-outline-info w-100">
                                                +<?php echo number_format($amount, 2); ?>L
                                            </button>
                                        </form>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide Informations personnelles -->
                    <div class="swiper-slide">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Informations personnelles</h5>

                                <div class="row g-4">
                                    <!-- Poids actuel -->
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon bg-primary bg-opacity-10 me-3">
                                                <i class="fas fa-weight text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Poids actuel</div>
                                                <div class="fw-bold">
                                                    <?php echo $latest_weight ? number_format($latest_weight['weight'], 1) . ' kg' : '—'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Taille -->
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon bg-info bg-opacity-10 me-3">
                                                <i class="fas fa-ruler-vertical text-info"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Taille</div>
                                                <div class="fw-bold">
                                                    <?php echo $profile && $profile['height'] ? $profile['height'] . ' cm' : '—'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- IMC -->
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon bg-warning bg-opacity-10 me-3">
                                                <i class="fas fa-calculator text-warning"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">IMC</div>
                                                <?php if ($latest_weight && $profile && $profile['height'] > 0): 
                                                    $weight_kg = $latest_weight['weight'];
                                                    $height_m = $profile['height'] / 100;
                                                    $bmi = $weight_kg / ($height_m * $height_m);
                                                ?>
                                                    <div class="fw-bold"><?php echo number_format($bmi, 1); ?></div>
                                                    <small class="text-muted">
                                                        <?php
                                                        if ($bmi < 18.5) echo 'Insuffisance pondérale';
                                                        elseif ($bmi < 25) echo 'Poids normal';
                                                        elseif ($bmi < 30) echo 'Surpoids';
                                                        else echo 'Obésité';
                                                        ?>
                                                    </small>
                                                <?php else: ?>
                                                    <div class="fw-bold">—</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Objectif de poids -->
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon bg-success bg-opacity-10 me-3">
                                                <i class="fas fa-bullseye text-success"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Objectif</div>
                                                <div class="fw-bold">
                                                    <?php echo $current_goal ? number_format($current_goal['target_weight'], 1) . ' kg' : '—'; ?>
                                                </div>
                                                <?php if ($current_goal && $latest_weight): 
                                                    $diff = $current_goal['target_weight'] - $latest_weight['weight'];
                                                ?>
                                                    <small class="text-muted">
                                                        <?php
                                                        if ($diff < 0) echo 'Perdre ' . number_format(abs($diff), 1) . ' kg';
                                                        elseif ($diff > 0) echo 'Prendre ' . number_format($diff, 1) . ' kg';
                                                        else echo 'Maintenir le poids actuel';
                                                        ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($active_program): ?>
                                <div class="alert alert-info mt-4 mb-0">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-dumbbell fa-2x me-3"></i>
                                        <div>
                                            <div class="fw-bold">Programme actif: <?php echo htmlspecialchars($active_program['name']); ?></div>
                                            <small>Commencé le <?php echo $active_program['formatted_start_date']; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="d-flex gap-2 mt-4">
                                    <a href="profile.php" class="btn btn-outline-primary btn-sm flex-grow-1">
                                        <i class="fas fa-user-edit me-1"></i>Modifier le profil
                                    </a>
                                    <a href="goals.php" class="btn btn-outline-success btn-sm flex-grow-1">
                                        <i class="fas fa-bullseye me-1"></i>Gérer les objectifs
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>

            <!-- Slider pour les exercices (mobile et desktop) -->
            <div class="swiper exercise-swiper mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Activité physique</h5>
                </div>
                <div class="swiper-wrapper">
                    <!-- Durée d'exercice -->
                    <div class="swiper-slide">
                        <div class="exercise-card">
                            <div class="d-flex align-items-center">
                                <div class="exercise-icon">
                                    <i class="fas fa-clock text-info"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php 
                                        $sql = "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(duration))) as total_duration 
                                               FROM exercise_logs 
                                               WHERE user_id = ? AND log_date = CURDATE()";
                                        $duration = fetchOne($sql, [$user_id]);
                                        echo $duration['total_duration'] ?? '00:00';
                                    ?></div>
                                    <small class="text-muted">Durée d'exercice</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Calories brûlées -->
                    <div class="swiper-slide">
                        <div class="exercise-card">
                            <div class="d-flex align-items-center">
                                <div class="exercise-icon">
                                    <i class="fas fa-fire text-danger"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo number_format($calories_out); ?></div>
                                    <small class="text-muted">kcal brûlées</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>

            <!-- Version desktop -->
            <div class="row g-4 d-none d-md-flex">
                <!-- Première ligne : Calories et Macronutriments -->
                <div class="col-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Calories</h5>
                            <p class="text-muted small mb-3">Reste = Objectif - Aliments + Exercices</p>
                            
                            <div class="calories-container">
                                <div class="progress-circle position-relative">
                                    <svg width="180" height="180" viewBox="0 0 200 200">
                                        <circle cx="100" cy="100" r="90" fill="none" stroke="#f0f0f0" stroke-width="8"/>
                                        <circle cx="100" cy="100" r="90" fill="none" stroke="#0d6efd" stroke-width="8"
                                            stroke-dasharray="<?php 
                                                $calories_consumed = $calories_in - $calories_out;
                                                $percentage = ($calories_consumed / $calorie_goal) * 100;
                                                echo min(100, $percentage) * 5.65;
                                            ?> 565"
                                            transform="rotate(-90 100 100)"/>
                                    </svg>
                                    <div class="position-absolute top-50 start-50 translate-middle text-center">
                                        <h2 class="mb-0"><?php echo number_format($remaining_calories); ?></h2>
                                        <span class="text-muted">Reste</span>
                                    </div>
                                </div>

                                <div class="calories-details">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-flag text-primary me-2"></i>
                                            <span>Objectif de base</span>
                                        </div>
                                        <strong><?php echo number_format($calorie_goal); ?></strong>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-utensils text-success me-2"></i>
                                            <span>Aliments</span>
                                        </div>
                                        <strong><?php echo number_format($calories_in); ?></strong>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-fire text-danger me-2"></i>
                                            <span>Exercices</span>
                                        </div>
                                        <strong><?php echo number_format($calories_out); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Macronutriments</h5>
                            
                            <div class="row g-4">
                                <div class="col-4 text-center">
                                    <div class="macro-circle position-relative">
                                        <svg width="100" height="100" viewBox="0 0 100 100">
                                            <circle cx="50" cy="50" r="45" fill="none" stroke="#f0f0f0" stroke-width="6"/>
                                            <circle cx="50" cy="50" r="45" fill="none" stroke="#198754" stroke-width="6"
                                                stroke-dasharray="<?php echo min(100, ($protein_in / $protein_goal) * 100) * 2.83; ?> 283"
                                                transform="rotate(-90 50 50)"/>
                                        </svg>
                                        <div class="position-absolute top-50 start-50 translate-middle">
                                            <strong><?php echo round(($protein_in / $protein_goal) * 100); ?>%</strong>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <h6 class="mb-0">Protéines</h6>
                                        <small class="text-muted"><?php echo number_format($protein_in, 1); ?>g</small>
                                        <div class="macro-goal">Objectif: <?php echo number_format($protein_goal, 1); ?>g</div>
                                    </div>
                                </div>

                                <div class="col-4 text-center">
                                    <div class="macro-circle position-relative">
                                        <svg width="100" height="100" viewBox="0 0 100 100">
                                            <circle cx="50" cy="50" r="45" fill="none" stroke="#f0f0f0" stroke-width="6"/>
                                            <circle cx="50" cy="50" r="45" fill="none" stroke="#dc3545" stroke-width="6"
                                                stroke-dasharray="<?php echo min(100, ($carbs_in / $carbs_goal) * 100) * 2.83; ?> 283"
                                                transform="rotate(-90 50 50)"/>
                                        </svg>
                                        <div class="position-absolute top-50 start-50 translate-middle">
                                            <strong><?php echo round(($carbs_in / $carbs_goal) * 100); ?>%</strong>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <h6 class="mb-0">Glucides</h6>
                                        <small class="text-muted"><?php echo number_format($carbs_in, 1); ?>g</small>
                                        <div class="macro-goal">Objectif: <?php echo number_format($carbs_goal, 1); ?>g</div>
                                    </div>
                                </div>

                                <div class="col-4 text-center">
                                    <div class="macro-circle position-relative">
                                        <svg width="100" height="100" viewBox="0 0 100 100">
                                            <circle cx="50" cy="50" r="45" fill="none" stroke="#f0f0f0" stroke-width="6"/>
                                            <circle cx="50" cy="50" r="45" fill="none" stroke="#ffc107" stroke-width="6"
                                                stroke-dasharray="<?php echo min(100, ($fat_in / $fat_goal) * 100) * 2.83; ?> 283"
                                                transform="rotate(-90 50 50)"/>
                                        </svg>
                                        <div class="position-absolute top-50 start-50 translate-middle">
                                            <strong><?php echo round(($fat_in / $fat_goal) * 100); ?>%</strong>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <h6 class="mb-0">Lipides</h6>
                                        <small class="text-muted"><?php echo number_format($fat_in, 1); ?>g</small>
                                        <div class="macro-goal">Objectif: <?php echo number_format($fat_goal, 1); ?>g</div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <!-- Suppression du bouton Premium -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deuxième ligne : Hydratation et Informations personnelles -->
            <div class="row g-4 d-none d-md-flex">
                <div class="col-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Hydratation</h5>
                            
                            <div class="text-center mb-4">
                                <div class="water-drop position-relative">
                                    <svg width="150" height="150" viewBox="0 0 100 100">
                                        <path d="M50,10 L70,40 A30,30 0 1,1 30,40 L50,10" 
                                              fill="#f0f0f0" stroke="#0dcaf0" stroke-width="2"/>
                                        <path d="M50,10 L70,40 A30,30 0 1,1 30,40 L50,10" 
                                              fill="#0dcaf0" stroke="none"
                                              transform="translate(0,<?php echo 100 - min(100, $water_percentage); ?>)"/>
                                    </svg>
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <strong><?php echo number_format($water_consumed, 1); ?>L</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2">
                                <?php
                                $water_amounts = [0.25, 0.5, 0.75, 1];
                                foreach ($water_amounts as $amount):
                                ?>
                                <div class="col-6">
                                    <form action="water-log.php" method="POST">
                                        <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                                        <button type="submit" class="btn btn-outline-info w-100">
                                            +<?php echo number_format($amount, 2); ?>L
                                        </button>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Informations personnelles</h5>

                            <div class="row g-4">
                                <!-- Poids actuel -->
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="info-icon bg-primary bg-opacity-10 me-3">
                                            <i class="fas fa-weight text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted small">Poids actuel</div>
                                            <div class="fw-bold">
                                                <?php echo $latest_weight ? number_format($latest_weight['weight'], 1) . ' kg' : '—'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Taille -->
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="info-icon bg-info bg-opacity-10 me-3">
                                            <i class="fas fa-ruler-vertical text-info"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted small">Taille</div>
                                            <div class="fw-bold">
                                                <?php echo $profile && $profile['height'] ? $profile['height'] . ' cm' : '—'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- IMC -->
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="info-icon bg-warning bg-opacity-10 me-3">
                                            <i class="fas fa-calculator text-warning"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted small">IMC</div>
                                            <?php if ($latest_weight && $profile && $profile['height'] > 0): 
                                                $weight_kg = $latest_weight['weight'];
                                                $height_m = $profile['height'] / 100;
                                                $bmi = $weight_kg / ($height_m * $height_m);
                                            ?>
                                                <div class="fw-bold"><?php echo number_format($bmi, 1); ?></div>
                                                <small class="text-muted">
                                                    <?php
                                                    if ($bmi < 18.5) echo 'Insuffisance pondérale';
                                                    elseif ($bmi < 25) echo 'Poids normal';
                                                    elseif ($bmi < 30) echo 'Surpoids';
                                                    else echo 'Obésité';
                                                    ?>
                                                </small>
                                            <?php else: ?>
                                                <div class="fw-bold">—</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Objectif de poids -->
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="info-icon bg-success bg-opacity-10 me-3">
                                            <i class="fas fa-bullseye text-success"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted small">Objectif</div>
                                            <div class="fw-bold">
                                                <?php echo $current_goal ? number_format($current_goal['target_weight'], 1) . ' kg' : '—'; ?>
                                            </div>
                                            <?php if ($current_goal && $latest_weight): 
                                                $diff = $current_goal['target_weight'] - $latest_weight['weight'];
                                            ?>
                                                <small class="text-muted">
                                                    <?php
                                                    if ($diff < 0) echo 'Perdre ' . number_format(abs($diff), 1) . ' kg';
                                                    elseif ($diff > 0) echo 'Prendre ' . number_format($diff, 1) . ' kg';
                                                    else echo 'Maintenir le poids actuel';
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($active_program): ?>
                            <div class="alert alert-info mt-4 mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-dumbbell fa-2x me-3"></i>
                                    <div>
                                        <div class="fw-bold">Programme actif: <?php echo htmlspecialchars($active_program['name']); ?></div>
                                        <small>Commencé le <?php echo $active_program['formatted_start_date']; ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2 mt-4">
                                <a href="profile.php" class="btn btn-outline-primary btn-sm flex-grow-1">
                                    <i class="fas fa-user-edit me-1"></i>Modifier le profil
                                </a>
                                <a href="goals.php" class="btn btn-outline-success btn-sm flex-grow-1">
                                    <i class="fas fa-bullseye me-1"></i>Gérer les objectifs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialisation des sliders
        const dashboardSwiper = new Swiper('.dashboard-swiper', {
            slidesPerView: 'auto',
            spaceBetween: 20,
            centeredSlides: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            }
        });

        const exerciseSwiper = new Swiper('.exercise-swiper', {
            slidesPerView: 'auto',
            spaceBetween: 15,
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            },
            breakpoints: {
                768: {
                    slidesPerView: 4,
                    spaceBetween: 20
                }
            }
        });

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
