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
$weight = '';
$log_date = date('Y-m-d');
$notes = '';
$success_message = '';
$errors = [];

// Traitement du formulaire d'ajout de poids
if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_weight') || 
    (isset($_GET['action']) && $_GET['action'] === 'add')) {
    // Récupérer et nettoyer les données du formulaire
    $weight = sanitizeInput($_POST['weight'] ?? '');
    $log_date = sanitizeInput($_POST['log_date'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Validation des données
    if (empty($weight)) {
        $errors[] = "Le poids est requis";
    } elseif (!is_numeric($weight) || $weight <= 0) {
        $errors[] = "Le poids doit être un nombre positif";
    }
    
    if (empty($log_date)) {
        $errors[] = "La date est requise";
    } elseif (!validateDate($log_date)) {
        $errors[] = "La date n'est pas valide";
    }
    
    // Si aucune erreur, ajouter l'entrée de poids
    if (empty($errors)) {
        // Vérifier si une entrée existe déjà pour cette date
        $sql = "SELECT id FROM weight_logs WHERE user_id = ? AND log_date = ?";
        $existing_entry = fetchOne($sql, [$user_id, $log_date]);
        
        if ($existing_entry) {
            // Mettre à jour l'entrée existante
            $sql = "UPDATE weight_logs SET weight = ?, notes = ?, updated_at = NOW() WHERE id = ?";
            $result = update($sql, [$weight, $notes, $existing_entry['id']]);
            
            if ($result) {
                // Mettre à jour le poids dans user_profiles
                $sql = "UPDATE user_profiles SET weight = ?, updated_at = NOW() WHERE user_id = ?";
                update($sql, [$weight, $user_id]);
                
                // Vérifier si un objectif est atteint
                $sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
                $current_goal = fetchOne($sql, [$user_id]);
                
                if ($current_goal) {
                    $weight_diff = abs($weight - $current_goal['target_weight']);
                    
                    // Si l'utilisateur est à moins de 1,2kg de son objectif
                    if ($weight_diff <= 1.2 && $weight_diff > 0.1) {
                        $_SESSION['encouragement_message'] = "Bravo ! Vous êtes à moins de 1,2kg de votre objectif de " . number_format($current_goal['target_weight'], 1) . " kg. Continuez comme ça !";
                    }
                    
                    // Si l'objectif est atteint (différence de moins de 0.1 kg)
                    if ($weight_diff < 0.1) {
                        // Marquer l'objectif comme atteint
                        $sql = "UPDATE goals SET status = 'atteint', updated_at = NOW() WHERE id = ?";
                        update($sql, [$current_goal['id']]);
                        
                        // Calculer le BMR de base
                        $bmr = calculateBMR($weight, $profile['height'], $profile['birth_date'], $profile['gender']);
                        
                        // Calculer le TDEE (calories de base)
                        $tdee = calculateTDEE($bmr, $profile['activity_level']);
                        
                        // Mettre à jour les objectifs de l'utilisateur pour le maintien
                        $sql = "UPDATE user_profiles SET 
                                daily_calories = ?,
                                protein_ratio = 0.3,
                                carbs_ratio = 0.4,
                                fat_ratio = 0.3,
                                updated_at = NOW()
                                WHERE user_id = ?";
                        update($sql, [$tdee, $user_id]);
                        
                        $_SESSION['goal_achieved'] = true;
                        $_SESSION['goal_message'] = "Félicitations ! Vous avez atteint votre objectif de " . number_format($current_goal['target_weight'], 1) . " kg !";
                    }
                }
                
                $success_message = "Votre poids a été mis à jour avec succès !";
            } else {
                $errors[] = "Une erreur s'est produite lors de la mise à jour du poids. Veuillez réessayer.";
            }
        } else {
            // Créer une nouvelle entrée
            $sql = "INSERT INTO weight_logs (user_id, weight, log_date, notes, created_at) VALUES (?, ?, ?, ?, NOW())";
            $result = insert($sql, [$user_id, $weight, $log_date, $notes]);
            
            if ($result) {
                // Mettre à jour le poids dans user_profiles
                $sql = "UPDATE user_profiles SET weight = ?, updated_at = NOW() WHERE user_id = ?";
                update($sql, [$weight, $user_id]);
                
                // Vérifier si un objectif est atteint
                $sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
                $current_goal = fetchOne($sql, [$user_id]);
                
                if ($current_goal) {
                    $weight_diff = abs($weight - $current_goal['target_weight']);
                    
                    // Si l'utilisateur est à moins de 1,2kg de son objectif
                    if ($weight_diff <= 1.2 && $weight_diff > 0.1) {
                        $_SESSION['encouragement_message'] = "Bravo ! Vous êtes à moins de 1,2kg de votre objectif de " . number_format($current_goal['target_weight'], 1) . " kg. Continuez comme ça !";
                    }
                    
                    // Si l'objectif est atteint (différence de moins de 0.1 kg)
                    if ($weight_diff < 0.1) {
                        // Marquer l'objectif comme atteint
                        $sql = "UPDATE goals SET status = 'atteint', updated_at = NOW() WHERE id = ?";
                        update($sql, [$current_goal['id']]);
                        
                        // Calculer le BMR de base
                        $bmr = calculateBMR($weight, $profile['height'], $profile['birth_date'], $profile['gender']);
                        
                        // Calculer le TDEE (calories de base)
                        $tdee = calculateTDEE($bmr, $profile['activity_level']);
                        
                        // Mettre à jour les objectifs de l'utilisateur pour le maintien
                        $sql = "UPDATE user_profiles SET 
                                daily_calories = ?,
                                protein_ratio = 0.3,
                                carbs_ratio = 0.4,
                                fat_ratio = 0.3,
                                updated_at = NOW()
                                WHERE user_id = ?";
                        update($sql, [$tdee, $user_id]);
                        
                        $_SESSION['goal_achieved'] = true;
                        $_SESSION['goal_message'] = "Félicitations ! Vous avez atteint votre objectif de " . number_format($current_goal['target_weight'], 1) . " kg !";
                    }
                }
                
                $success_message = "Votre poids a été enregistré avec succès !";
            } else {
                $errors[] = "Une erreur s'est produite lors de l'enregistrement du poids. Veuillez réessayer.";
            }
        }
    }
}

// Ajouter le traitement de la confirmation de mise à jour des calories
if (isset($_POST['action']) && $_POST['action'] === 'update_calories' && isset($_SESSION['pending_calories_update'])) {
    $pending_update = $_SESSION['pending_calories_update'];
    
    if ($_POST['confirm'] === 'yes') {
        // Mettre à jour uniquement les calories
        $sql = "UPDATE user_profiles SET 
                daily_calories = ?,
                updated_at = NOW()
                WHERE user_id = ?";
        update($sql, [
            $pending_update['tdee'],
            $user_id
        ]);
        $success_message = "Vos besoins caloriques ont été mis à jour avec succès ! Les macronutriments seront automatiquement ajustés en fonction de vos ratios actuels.";
    } else {
        $success_message = "Vos besoins caloriques n'ont pas été mis à jour.";
    }
    
    // Nettoyer la session
    unset($_SESSION['pending_calories_update']);
    redirect('weight-log.php');
}

// Récupérer le profil de l'utilisateur
$sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$profile = fetchOne($sql, [$user_id]);

// Si le profil n'existe pas, le créer
if (!$profile) {
    $sql = "INSERT INTO user_profiles (user_id, gender, birth_date, height, activity_level, created_at) 
            VALUES (?, 'homme', '1990-01-01', 170, 'modere', NOW())";
    insert($sql, [$user_id]);
    
    // Récupérer le profil nouvellement créé
    $sql = "SELECT * FROM user_profiles WHERE user_id = ?";
    $profile = fetchOne($sql, [$user_id]);
}

// Récupérer le dernier poids enregistré
$sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC, created_at DESC LIMIT 1";
$latest_weight = fetchOne($sql, [$user_id]);

// Récupérer l'objectif de poids actuel
$sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);

// Récupérer les entrées de poids des 7 derniers jours
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-6 days'));

$sql = "SELECT * FROM weight_logs WHERE user_id = ? AND log_date BETWEEN ? AND ? ORDER BY log_date";
$recent_weights = fetchAll($sql, [$user_id, $start_date, $end_date]);

// Récupérer les entrées alimentaires du jour
$today = date('Y-m-d');
$sql = "SELECT fl.*, f.name as food_name, f.calories, f.protein, f.carbs, f.fat, m.meal_type 
        FROM food_logs fl 
        LEFT JOIN foods f ON fl.food_id = f.id 
        LEFT JOIN meals m ON fl.meal_id = m.id 
        WHERE fl.user_id = ? AND fl.log_date = ? 
        ORDER BY m.meal_type, fl.created_at";
$today_foods = fetchAll($sql, [$user_id, $today]);

// Récupérer les exercices du jour
$sql = "SELECT el.*, e.name as exercise_name 
        FROM exercise_logs el 
        LEFT JOIN exercises e ON el.exercise_id = e.id 
        WHERE el.user_id = ? AND el.log_date = ? 
        ORDER BY el.created_at";
$today_exercises = fetchAll($sql, [$user_id, $today]);

// Calculer les totaux pour la journée
$total_calories_in = 0;
$total_calories_out = 0;
$total_protein = 0;
$total_carbs = 0;
$total_fat = 0;

foreach ($today_foods as $food) {
    if (isset($food['calories'])) {
        $total_calories_in += $food['calories'] * ($food['quantity'] / 100);
        $total_protein += $food['protein'] * ($food['quantity'] / 100);
        $total_carbs += $food['carbs'] * ($food['quantity'] / 100);
        $total_fat += $food['fat'] * ($food['quantity'] / 100);
    } elseif (isset($food['custom_calories'])) {
        $total_calories_in += $food['custom_calories'];
        $total_protein += $food['custom_protein'] ?? 0;
        $total_carbs += $food['custom_carbs'] ?? 0;
        $total_fat += $food['custom_fat'] ?? 0;
    }
}

foreach ($today_exercises as $exercise) {
    $total_calories_out += $exercise['calories_burned'] ?? 0;
}

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
    
    // Vérifier si un objectif est atteint
    if ($current_goal) {
        $weight_diff = abs($latest_weight['weight'] - $current_goal['target_weight']);
        
        // Si l'objectif est atteint (différence de moins de 0.1 kg)
        if ($weight_diff < 0.1) {
            // Marquer l'objectif comme atteint
            $sql = "UPDATE goals SET status = 'atteint', updated_at = NOW() WHERE id = ?";
            update($sql, [$current_goal['id']]);
            
            // Stocker le poids actuel dans user_profiles
            $sql = "UPDATE user_profiles SET weight = ? WHERE user_id = ?";
            update($sql, [$latest_weight['weight'], $user_id]);
            
            // Calculer le BMR de base
            $bmr = calculateBMR($latest_weight['weight'], $profile['height'], $profile['birth_date'], $profile['gender']);
            
            // Calculer le TDEE (calories de base)
            $tdee = calculateTDEE($bmr, $profile['activity_level']);
            
            // Mettre à jour les objectifs de l'utilisateur pour le maintien
            $sql = "UPDATE user_profiles SET 
                    daily_calories = ?,
                    protein_ratio = 0.3,
                    carbs_ratio = 0.4,
                    fat_ratio = 0.3,
                    updated_at = NOW()
                    WHERE user_id = ?";
            update($sql, [$tdee, $user_id]);
        }
    }
    
    // Calculer l'objectif calorique en fonction de l'objectif de poids
    if ($current_goal) {
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

// Calculer les objectifs de macronutriments
$protein_goal = $latest_weight ? $latest_weight['weight'] * 2 : 0; // 2g de protéines par kg de poids corporel
$fat_goal = $calorie_goal * 0.25 / 9; // 25% des calories provenant des graisses (9 calories par gramme)
$carbs_goal = ($calorie_goal - ($protein_goal * 4) - ($fat_goal * 9)) / 4; // Le reste en glucides (4 calories par gramme)

// Préparer les données pour les graphiques
$weight_dates = [];
$weight_values = [];

foreach ($recent_weights as $weight) {
    $weight_dates[] = date('d/m', strtotime($weight['log_date']));
    $weight_values[] = $weight['weight'];
}

// Si moins de 7 jours de données, compléter avec des valeurs nulles
if (count($weight_dates) < 7) {
    for ($i = count($weight_dates); $i < 7; $i++) {
        $date = date('d/m', strtotime("-" . (6 - $i) . " days"));
        array_unshift($weight_dates, $date);
        array_unshift($weight_values, null);
    }
}

// Récupérer les dernières entrées de journal
$sql = "SELECT 'weight' as type, log_date, weight as value, notes, created_at 
        FROM weight_logs 
        WHERE user_id = ? 
        UNION 
        SELECT 'food' as type, log_date, NULL as value, notes, created_at 
        FROM food_logs 
        WHERE user_id = ? 
        UNION 
        SELECT 'exercise' as type, log_date, NULL as value, notes, created_at 
        FROM exercise_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";
$recent_logs = fetchAll($sql, [$user_id, $user_id, $user_id]);

// Récupérer les statistiques générales
$sql = "SELECT COUNT(*) as total_days, 
               MIN(log_date) as first_day, 
               MAX(log_date) as last_day, 
               MIN(weight) as min_weight, 
               MAX(weight) as max_weight 
        FROM weight_logs 
        WHERE user_id = ?";
$weight_stats = fetchOne($sql, [$user_id]);

$total_days_tracked = $weight_stats ? $weight_stats['total_days'] : 0;
$days_streak = 0;

if ($latest_weight) {
    // Calculer la série de jours consécutifs
    $current_date = new DateTime();
    $log_date = new DateTime($latest_weight['log_date']);
    $diff = $current_date->diff($log_date);
    
    if ($diff->days == 0) {
        // Le dernier enregistrement est aujourd'hui, calculer la série
        $sql = "SELECT log_date FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC";
        $all_dates = fetchAll($sql, [$user_id]);
        
        $days_streak = 1; // Aujourd'hui compte comme 1
        $prev_date = $current_date;
        
        foreach ($all_dates as $index => $date_entry) {
            if ($index === 0) continue; // Sauter le premier (aujourd'hui)
            
            $curr_date = new DateTime($date_entry['log_date']);
            $date_diff = $prev_date->diff($curr_date);
            
            if ($date_diff->days == 1) {
                $days_streak++;
                $prev_date = $curr_date;
            } else {
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de poids - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @keyframes confetti {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(100vh) rotate(360deg); }
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f00;
            animation: confetti 3s linear forwards;
            z-index: 9999;
        }
        
        .goal-achieved-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9998;
            justify-content: center;
            align-items: center;
        }
        
        .goal-achieved-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            animation: scaleIn 0.5s ease-out;
        }
        
        @keyframes scaleIn {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <!-- Barre de navigation -->
    <?php include 'navigation.php'; ?>

    <!-- Modal de félicitations -->
    <div id="goalAchievedModal" class="goal-achieved-modal">
        <div class="goal-achieved-content">
            <i class="fas fa-trophy fa-3x text-warning mb-3"></i>
            <h2 class="mb-3">Félicitations !</h2>
            <p class="mb-4"><?php echo $_SESSION['goal_message'] ?? ''; ?></p>
            <button class="btn btn-primary" onclick="closeGoalAchievedModal()">Continuer</button>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="container py-4">
        <?php if (isset($_SESSION['encouragement_message'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-star me-2"></i>
                <?php echo $_SESSION['encouragement_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['encouragement_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['goal_achieved'])): ?>
            <script>
                function createConfetti() {
                    const colors = ['#f00', '#0f0', '#00f', '#ff0', '#f0f', '#0ff'];
                    for (let i = 0; i < 50; i++) {
                        const confetti = document.createElement('div');
                        confetti.className = 'confetti';
                        confetti.style.left = Math.random() * 100 + 'vw';
                        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                        confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                        document.body.appendChild(confetti);
                        
                        // Supprimer le confetti après l'animation
                        setTimeout(() => {
                            confetti.remove();
                        }, 5000);
                    }
                }
                
                function showGoalAchievedModal() {
                    document.getElementById('goalAchievedModal').style.display = 'flex';
                    createConfetti();
                }
                
                function closeGoalAchievedModal() {
                    document.getElementById('goalAchievedModal').style.display = 'none';
                }
                
                // Afficher le modal et le confetti immédiatement
                showGoalAchievedModal();
            </script>
            <?php unset($_SESSION['goal_achieved']); ?>
        <?php endif; ?>

        <!-- En-tête du tableau de bord -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-0">Suivi de poids</h1>
                <p class="text-muted">Suivez votre progression et atteignez vos objectifs</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="weight-log.php?action=add" class="btn btn-primary me-2" id="addWeightBtn">
                    <i class="fas fa-plus-circle me-1"></i>Ajouter un poids
                </a>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="addEntryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-plus me-1"></i>Plus
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="addEntryDropdown">
                        <li><a class="dropdown-item" href="food-log.php?action=add"><i class="fas fa-utensils me-1"></i>Ajouter un repas</a></li>
                        <li><a class="dropdown-item" href="exercise-log.php?action=add"><i class="fas fa-running me-1"></i>Ajouter un exercice</a></li>
                        <li><a class="dropdown-item" href="goals.php?action=add"><i class="fas fa-bullseye me-1"></i>Définir un objectif</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <?php if (isset($_SESSION['pending_calories_update'])): ?>
                    <form action="weight-log.php" method="POST" class="mt-2">
                        <input type="hidden" name="action" value="update_calories">
                        <div class="d-flex gap-2">
                            <button type="submit" name="confirm" value="yes" class="btn btn-success btn-sm">
                                <i class="fas fa-check me-1"></i>Oui, mettre à jour
                            </button>
                            <button type="submit" name="confirm" value="no" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Non, garder les actuels
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
            <!-- Formulaire d'ajout de poids -->
            <div class="row mb-4">
                <div class="col-lg-8 mx-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Ajouter une entrée de poids</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form action="weight-log.php" method="POST" novalidate>
                                <input type="hidden" name="action" value="add_weight">
                                
                                <div class="mb-3">
                                    <label for="weight" class="form-label">Poids (kg)</label>
                                    <input type="number" class="form-control" id="weight" name="weight" value="<?php echo is_array($weight) ? htmlspecialchars($weight[0] ?? '') : htmlspecialchars($weight); ?>" min="30" max="300" step="0.1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="log_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="log_date" name="log_date" value="<?php echo $log_date instanceof DateTime ? $log_date->format('Y-m-d') : htmlspecialchars($log_date); ?>" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="notes" class="form-label">Notes (optionnel)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Enregistrer
                                    </button>
                                    <a href="weight-log.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Annuler
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Affichage du suivi de poids -->
            <div class="row">
                <!-- Graphique d'évolution du poids -->
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Évolution du poids</h5>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary active" data-period="week">7 jours</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-period="month">30 jours</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-period="year">Année</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="weightChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Statistiques de poids -->
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Statistiques</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Poids actuel</h6>
                                <div class="d-flex align-items-baseline">
                                    <h2 class="mb-0 me-2"><?php echo $latest_weight ? number_format($latest_weight['weight'], 1) : '—'; ?></h2>
                                    <span class="text-muted">kg</span>
                                </div>
                            </div>
                            
                            <?php if ($latest_weight && $current_goal): ?>
                                <div class="mb-4">
                                    <h6 class="text-muted mb-2">Objectif</h6>
                                    <div class="d-flex align-items-baseline">
                                        <h4 class="mb-0 me-2"><?php echo number_format($current_goal['target_weight'], 1); ?></h4>
                                        <span class="text-muted">kg</span>
                                    </div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <?php
                                        $progress = 0;
                                        $weight_diff = abs($latest_weight['weight'] - $current_goal['target_weight']);
                                        
                                        // Récupérer le poids initial (le plus ancien poids enregistré depuis la création de l'objectif)
                                        $sql = "SELECT weight FROM weight_logs 
                                                WHERE user_id = ? 
                                                AND log_date >= ? 
                                                ORDER BY log_date ASC 
                                                LIMIT 1";
                                        $start_weight = fetchOne($sql, [$user_id, $current_goal['created_at']]);
                                        $start_weight = $start_weight ? $start_weight['weight'] : $latest_weight['weight'];
                                        
                                        $total_diff = abs($start_weight - $current_goal['target_weight']);
                                        
                                        if ($total_diff > 0) {
                                            $progress = 100 - ($weight_diff / $total_diff * 100);
                                            $progress = max(0, min(100, $progress));
                                        }
                                        ?>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php
                                        $remaining = abs($latest_weight['weight'] - $current_goal['target_weight']);
                                        echo number_format($progress, 1) . '% complété, ' . number_format($remaining, 1) . ' kg restants';
                                        ?>
                                    </small>
                                </div>
                            <?php elseif (!$current_goal): ?>
                                <div class="mb-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Vous n'avez pas encore défini d'objectif de poids.
                                    </div>
                                    <a href="goals.php?action=add" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-bullseye me-1"></i>Définir un objectif
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">IMC (Indice de Masse Corporelle)</h6>
                                <?php
                                $bmi = 0;
                                $bmi_category = '';
                                
                                if ($latest_weight && $profile && $profile['height'] > 0) {
                                    $weight_kg = $latest_weight['weight'];
                                    $height_m = $profile['height'] / 100;
                                    $bmi = $weight_kg / ($height_m * $height_m);
                                    
                                    if ($bmi < 18.5) {
                                        $bmi_category = 'Insuffisance pondérale';
                                        $bmi_color = 'text-warning';
                                    } elseif ($bmi < 25) {
                                        $bmi_category = 'Poids normal';
                                        $bmi_color = 'text-success';
                                    } elseif ($bmi < 30) {
                                        $bmi_category = 'Surpoids';
                                        $bmi_color = 'text-warning';
                                    } else {
                                        $bmi_category = 'Obésité';
                                        $bmi_color = 'text-danger';
                                    }
                                }
                                ?>
                                <div class="d-flex align-items-baseline">
                                    <h4 class="mb-0 me-2"><?php echo $bmi > 0 ? number_format($bmi, 1) : '—'; ?></h4>
                                    <span class="<?php echo $bmi > 0 ? $bmi_color : 'text-muted'; ?>"><?php echo $bmi_category; ?></span>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <h6 class="text-muted mb-2">Poids min</h6>
                                    <h5 class="mb-0"><?php echo $weight_stats && $weight_stats['min_weight'] ? number_format($weight_stats['min_weight'], 1) . ' kg' : '—'; ?></h5>
                                </div>
                                <div class="col-6">
                                    <h6 class="text-muted mb-2">Poids max</h6>
                                    <h5 class="mb-0"><?php echo $weight_stats && $weight_stats['max_weight'] ? number_format($weight_stats['max_weight'], 1) . ' kg' : '—'; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Historique des entrées de poids -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Historique des entrées</h5>
                            <a href="weight-log.php?action=add" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus-circle me-1"></i>Ajouter
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Poids</th>
                                            <th>Variation</th>
                                            <th>IMC</th>
                                            <th>Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 30";
                                        $weight_history = fetchAll($sql, [$user_id]);
                                        
                                        if (empty($weight_history)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-weight fa-3x mb-3"></i>
                                                        <p>Vous n'avez pas encore enregistré de poids.</p>
                                                        <a href="weight-log.php?action=add" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-plus-circle me-1"></i>Ajouter votre premier poids
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php 
                                            $prev_weight = null;
                                            foreach ($weight_history as $index => $entry): 
                                                // Calculer la variation
                                                $variation = 0;
                                                $variation_class = '';
                                                
                                                if ($index < count($weight_history) - 1) {
                                                    $prev_weight = $weight_history[$index + 1]['weight'];
                                                    $variation = $entry['weight'] - $prev_weight;
                                                    
                                                    if ($variation > 0) {
                                                        $variation_class = 'text-danger';
                                                    } elseif ($variation < 0) {
                                                        $variation_class = 'text-success';
                                                    }
                                                }
                                                
                                                // Calculer l'IMC
                                                $entry_bmi = 0;
                                                $entry_bmi_class = '';
                                                
                                                if ($profile && $profile['height'] > 0) {
                                                    $height_m = $profile['height'] / 100;
                                                    $entry_bmi = $entry['weight'] / ($height_m * $height_m);
                                                    
                                                    if ($entry_bmi < 18.5) {
                                                        $entry_bmi_class = 'text-warning';
                                                    } elseif ($entry_bmi < 25) {
                                                        $entry_bmi_class = 'text-success';
                                                    } elseif ($entry_bmi < 30) {
                                                        $entry_bmi_class = 'text-warning';
                                                    } else {
                                                        $entry_bmi_class = 'text-danger';
                                                    }
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($entry['log_date'])); ?></td>
                                                    <td><strong><?php echo number_format($entry['weight'], 1); ?> kg</strong></td>
                                                    <td class="<?php echo $variation_class; ?>">
                                                        <?php 
                                                        if ($variation != 0) {
                                                            echo ($variation > 0 ? '+' : '') . number_format($variation, 1) . ' kg';
                                                        } else {
                                                            echo '—';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="<?php echo $entry_bmi_class; ?>">
                                                        <?php echo $entry_bmi > 0 ? number_format($entry_bmi, 1) : '—'; ?>
                                                    </td>
                                                    <td><?php echo !empty($entry['notes']) ? htmlspecialchars($entry['notes']) : '—'; ?></td>
                                                    <td>
                                                        <a href="weight-log.php?action=edit&id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteEntryModal" data-entry-id="<?php echo $entry['id']; ?>">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

    <!-- Modal de suppression d'entrée -->
    <div class="modal fade" id="deleteEntryModal" tabindex="-1" aria-labelledby="deleteEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteEntryModalLabel">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cette entrée de poids ? Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form action="weight-log.php" method="POST">
                        <input type="hidden" name="action" value="delete_weight">
                        <input type="hidden" name="entry_id" id="deleteEntryId" value="">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Initialiser le sélecteur de date
        flatpickr("#log_date", {
            locale: "fr",
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
        
        // Initialiser le graphique de poids
        const weightCtx = document.getElementById('weightChart');
        if (weightCtx) {
            const weightChart = new Chart(weightCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($weight_dates); ?>,
                    datasets: [{
                        label: 'Poids (kg)',
                        data: <?php echo json_encode($weight_values); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.1,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return value + ' kg';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw + ' kg';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Gérer la suppression d'entrée
        document.getElementById('deleteEntryModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const entryId = button.getAttribute('data-entry-id');
            document.getElementById('deleteEntryId').value = entryId;
        });
    </script>
    <!-- Script pour réparer les boutons d'action -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion du bouton "Ajouter un poids"
            const addWeightBtn = document.getElementById('addWeightBtn');
            if (addWeightBtn) {
                addWeightBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'weight-log.php?action=add';
                });
            }
            
            // Gestion du bouton "Plus" et de son dropdown
            const addEntryDropdown = document.getElementById('addEntryDropdown');
            if (addEntryDropdown) {
                // S'assurer que les liens du dropdown fonctionnent correctement
                document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        window.location.href = this.getAttribute('href');
                    });
                });
            }
        });
    </script>
</body>
</html>
