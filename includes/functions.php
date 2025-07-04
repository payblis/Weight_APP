<?php
// Inclure les fonctions utilitaires
require_once 'config/database.php';

/**
 * Nettoie les données d'entrée pour éviter les injections
 * 
 * @param string $data Données à nettoyer
 * @return string Données nettoyées
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Vérifie si l'utilisateur est connecté
 * 
 * @return bool True si l'utilisateur est connecté, false sinon
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Redirige vers une URL spécifiée
 * 
 * @param string $url URL de redirection
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Récupère l'ID de la dernière insertion
 * 
 * @return int|false ID de la dernière insertion ou false en cas d'erreur
 */
function getLastInsertId() {
    global $pdo;
    
    try {
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Erreur SQL: " . $e->getMessage());
        return false;
    }
}

/**
 * Formate une date au format français
 * 
 * @param string $date Date au format Y-m-d
 * @return string Date formatée
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Calcule l'IMC (Indice de Masse Corporelle)
 * 
 * @param float $weight Poids en kg
 * @param float $height Taille en cm
 * @return float IMC
 */
function calculateBMI($weight, $height) {
    $height_m = $height / 100; // Convertir cm en m
    return $weight / ($height_m * $height_m);
}

/**
 * Détermine la catégorie d'IMC
 * 
 * @param float $bmi IMC
 * @return string Catégorie d'IMC
 */
function getBMICategory($bmi) {
    if ($bmi < 18.5) {
        return 'Insuffisance pondérale';
    } elseif ($bmi >= 18.5 && $bmi < 25) {
        return 'Poids normal';
    } elseif ($bmi >= 25 && $bmi < 30) {
        return 'Surpoids';
    } elseif ($bmi >= 30 && $bmi < 35) {
        return 'Obésité modérée';
    } elseif ($bmi >= 35 && $bmi < 40) {
        return 'Obésité sévère';
    } else {
        return 'Obésité morbide';
    }
}

/**
 * Calcule le métabolisme de base (BMR) selon la formule de Mifflin-St Jeor
 * 
 * @param float $weight Poids en kg
 * @param float $height Taille en cm
 * @param mixed $age Âge en années ou date de naissance (Y-m-d)
 * @param string $gender Genre ('homme' ou 'femme')
 * @return float BMR en calories
 */
function calculateBMR($weight, $height, $age, $gender) {
    // Vérifier que toutes les valeurs sont valides
    if (!is_numeric($weight) || $weight <= 0 || !is_numeric($height) || $height <= 0 || 
        !in_array($gender, ['homme', 'femme'])) {
        error_log("Valeurs invalides pour le calcul du BMR - Poids: $weight, Taille: $height, Âge: $age, Genre: $gender");
        return 0;
    }
    
    // Calculer l'âge si une date est fournie
    if (is_string($age) && strpos($age, '-') !== false) {
        $birth_date = new DateTime($age);
        $today = new DateTime();
        $age = $birth_date->diff($today)->y;
    }
    
    // Vérifier que l'âge est valide
    if (!is_numeric($age) || $age <= 0 || $age > 120) {
        error_log("Âge invalide pour le calcul du BMR : " . $age);
        return 0;
    }
    
    error_log("Calcul du BMR avec - Poids: $weight, Taille: $height, Âge: $age, Genre: $gender");
    
    // Formule de Mifflin-St Jeor
    if ($gender === 'homme') {
        return (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
    } else {
        return (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
    }
}

/**
 * Calcule la dépense énergétique totale quotidienne (TDEE)
 * 
 * @param float $bmr Métabolisme de base
 * @param string $activity_level Niveau d'activité
 * @return float TDEE en calories
 */
function calculateTDEE($bmr, $activity_level) {
    // Facteurs d'activité
    $activity_factors = [
        'sedentaire' => 1.2,
        'leger' => 1.375,
        'modere' => 1.55,
        'actif' => 1.725,
        'tres_actif' => 1.9
    ];
    
    // Utiliser le facteur d'activité par défaut si le niveau n'est pas reconnu
    $factor = $activity_factors[$activity_level] ?? 1.55;
    
    return round($bmr * $factor);
}

/**
 * Calcule l'objectif calorique en fonction de l'objectif de poids
 * 
 * @param float $tdee Dépense énergétique totale quotidienne
 * @param string $goal_type Type d'objectif ('perte', 'maintien', 'prise')
 * @param float $intensity Intensité (0.5 = modérée, 1 = standard, 1.5 = agressive)
 * @param float $current_weight Poids actuel en kg
 * @param float $target_weight Poids cible en kg
 * @param string $target_date Date cible au format Y-m-d
 * @return float Objectif calorique
 */
function calculateCalorieGoal($tdee, $goal_type, $intensity = 1, $current_weight = null, $target_weight = null, $target_date = null) {
    // Si c'est un objectif de maintien, retourner le TDEE
    if ($goal_type === 'maintien') {
        return $tdee;
    }
    
    // Si on n'a pas toutes les informations nécessaires pour calculer l'ajustement
    if ($current_weight === null || $target_weight === null || $target_date === null) {
        // Utiliser l'ajustement standard (500 calories)
        return $goal_type === 'perte' ? $tdee - (500 * $intensity) : $tdee + (500 * $intensity);
    }
    
    // Calculer la différence de poids
    $weight_diff = $target_weight - $current_weight;
    
    // Calculer le nombre de jours jusqu'à l'objectif
    $target = new DateTime($target_date);
    $today = new DateTime();
    $days_until_goal = $today->diff($target)->days;
    
    if ($days_until_goal <= 0) {
        error_log("La date cible est dans le passé ou aujourd'hui");
        return $tdee;
    }
    
    // Calculer l'ajustement total nécessaire (1 kg = 7700 calories)
    $total_adjustment = $weight_diff * 7700;
    
    // Calculer l'ajustement quotidien
    $daily_adjustment = $total_adjustment / $days_until_goal;
    
    error_log("=== Calcul des calories pour l'objectif ===");
    error_log("Poids actuel : " . $current_weight);
    error_log("Poids cible : " . $target_weight);
    error_log("Différence de poids : " . $weight_diff . " kg");
    error_log("Jours jusqu'à l'objectif : " . $days_until_goal);
    error_log("Calories totales nécessaires : " . $total_adjustment);
    error_log("Ajustement quotidien pour l'objectif : " . $daily_adjustment);
    
    // Calculer les calories finales
    $final_calories = $tdee + $daily_adjustment;
    error_log("Calories finales : " . $final_calories);
    error_log("=== Fin du calcul des calories pour l'objectif ===");
    
    return $final_calories;
}

/**
 * Calcule les objectifs de macronutriments
 * 
 * @param float $calorie_goal Objectif calorique
 * @param float $weight Poids en kg
 * @param string $goal_type Type d'objectif ('perte', 'maintien', 'prise')
 * @return array Objectifs de macronutriments (protéines, lipides, glucides)
 */
function calculateMacroGoals($calorie_goal, $weight, $goal_type) {
    // Protéines: 1.6-2.2g/kg pour perte de poids, 1.6-1.8g/kg pour maintien, 1.8-2.2g/kg pour prise de masse
    $protein_factor = $goal_type === 'perte' ? 2.2 : ($goal_type === 'prise' ? 2.0 : 1.8);
    $protein_goal = $weight * $protein_factor;
    
    // Lipides: 20-35% des calories totales (utiliser 25% comme valeur par défaut)
    $fat_goal = ($calorie_goal * 0.25) / 9; // 9 calories par gramme de lipides
    
    // Glucides: le reste des calories
    $carbs_goal = ($calorie_goal - ($protein_goal * 4) - ($fat_goal * 9)) / 4; // 4 calories par gramme de glucides
    
    return [
        'protein' => round($protein_goal),
        'fat' => round($fat_goal),
        'carbs' => round($carbs_goal)
    ];
}

/**
 * Génère un token aléatoire
 * 
 * @param int $length Longueur du token
 * @return string Token généré
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Vérifie si une date est dans le futur
 * 
 * @param string $date Date au format Y-m-d
 * @return bool True si la date est dans le futur, false sinon
 */
function isFutureDate($date) {
    return strtotime($date) > time();
}

/**
 * Calcule le nombre de jours entre deux dates
 * 
 * @param string $start_date Date de début au format Y-m-d
 * @param string $end_date Date de fin au format Y-m-d
 * @return int Nombre de jours
 */
function daysBetween($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    return $interval->days;
}

/**
 * Calcule le taux de perte/gain de poids par semaine
 * 
 * @param float $start_weight Poids de départ
 * @param float $current_weight Poids actuel
 * @param int $days_elapsed Nombre de jours écoulés
 * @return float Taux de perte/gain de poids par semaine
 */
function calculateWeeklyRate($start_weight, $current_weight, $days_elapsed) {
    if ($days_elapsed <= 0) {
        return 0;
    }
    
    $weight_diff = $current_weight - $start_weight;
    $weeks_elapsed = $days_elapsed / 7;
    
    return $weight_diff / $weeks_elapsed;
}

/**
 * Estime la date d'achèvement d'un objectif de poids
 * 
 * @param float $current_weight Poids actuel
 * @param float $target_weight Poids cible
 * @param float $weekly_rate Taux de perte/gain de poids par semaine
 * @return string|null Date estimée au format Y-m-d ou null si le taux est nul
 */
function estimateCompletionDate($current_weight, $target_weight, $weekly_rate) {
    if ($weekly_rate == 0) {
        return null;
    }
    
    $weight_diff = $target_weight - $current_weight;
    $weeks_needed = $weight_diff / $weekly_rate;
    
    if ($weeks_needed < 0) {
        // Si le taux va dans la direction opposée à l'objectif
        return null;
    }
    
    $days_needed = $weeks_needed * 7;
    return date('Y-m-d', strtotime("+{$days_needed} days"));
}

/**
 * Valide une date au format Y-m-d
 * 
 * @param string $date Date à valider
 * @return bool True si la date est valide, false sinon
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Génère une couleur aléatoire au format hexadécimal
 * 
 * @return string Couleur au format hexadécimal
 */
function generateRandomColor() {
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

/**
 * Calcule le pourcentage de progression vers un objectif
 * 
 * @param float $start_value Valeur de départ
 * @param float $current_value Valeur actuelle
 * @param float $target_value Valeur cible
 * @return float Pourcentage de progression
 */
function calculateProgressPercentage($start_value, $current_value, $target_value) {
    $total_diff = $target_value - $start_value;
    
    if ($total_diff == 0) {
        return 100; // Éviter la division par zéro
    }
    
    $current_diff = $current_value - $start_value;
    $percentage = ($current_diff / $total_diff) * 100;
    
    // Limiter le pourcentage entre 0 et 100
    return max(0, min(100, $percentage));
}

/**
 * Génère un message de motivation basé sur le pourcentage de progression
 * 
 * @param float $percentage Pourcentage de progression
 * @return string Message de motivation
 */
function getMotivationalMessage($percentage) {
    if ($percentage <= 0) {
        return "C'est le début de votre parcours. Chaque petit pas compte !";
    } elseif ($percentage < 25) {
        return "Vous avez commencé ! Continuez, vous êtes sur la bonne voie.";
    } elseif ($percentage < 50) {
        return "Vous avez fait du chemin ! Continuez vos efforts, ça paie.";
    } elseif ($percentage < 75) {
        return "Plus de la moitié du chemin est parcourue. Vous pouvez être fier(e) !";
    } elseif ($percentage < 100) {
        return "Vous y êtes presque ! La ligne d'arrivée est en vue.";
    } else {
        return "Félicitations ! Vous avez atteint votre objectif. Quel accomplissement !";
    }
}

/**
 * Calcule le nombre de calories brûlées pour un exercice spécifique
 * 
 * @param float $base_calories Calories de base brûlées par heure
 * @param int $duration Durée en minutes
 * @param float $weight Poids en kg
 * @param string $intensity Intensité ('faible', 'moderee', 'elevee')
 * @return int Calories brûlées
 */
function calculateCaloriesBurned($base_calories, $duration, $weight, $intensity) {
    // Facteur d'intensité
    $intensity_factor = 1;
    if ($intensity === 'faible') {
        $intensity_factor = 0.8;
    } elseif ($intensity === 'elevee') {
        $intensity_factor = 1.2;
    }
    
    // Facteur de poids (normalisation par rapport à 70kg)
    $weight_factor = $weight / 70;
    
    // Calcul des calories brûlées
    $calories_per_hour = $base_calories * $intensity_factor * $weight_factor;
    $calories_burned = ($calories_per_hour / 60) * $duration;
    
    return round($calories_burned);
}

/**
 * Génère des recommandations alimentaires basées sur l'objectif
 * 
 * @param string $goal_type Type d'objectif ('perte', 'maintien', 'prise')
 * @return array Recommandations alimentaires
 */
function generateDietRecommendations($goal_type) {
    $recommendations = [
        'general' => '',
        'foods_to_include' => [],
        'foods_to_limit' => []
    ];
    
    if ($goal_type === 'perte') {
        $recommendations['general'] = "Pour perdre du poids de manière saine, concentrez-vous sur des aliments riches en nutriments mais faibles en calories. Privilégiez les protéines maigres et les fibres pour vous sentir rassasié plus longtemps.";
        $recommendations['foods_to_include'] = [
            'Légumes à volonté (surtout les légumes verts)',
            'Fruits frais (2-3 portions par jour)',
            'Protéines maigres (poulet, dinde, poisson, tofu)',
            'Légumineuses (lentilles, pois chiches)',
            'Céréales complètes en quantité modérée',
            'Produits laitiers faibles en gras'
        ];
        $recommendations['foods_to_limit'] = [
            'Aliments transformés et fast-food',
            'Sucres ajoutés et boissons sucrées',
            'Alcool',
            'Graisses saturées',
            'Aliments frits'
        ];
    } elseif ($goal_type === 'prise') {
        $recommendations['general'] = "Pour prendre du poids sainement, augmentez votre apport calorique avec des aliments nutritifs et denses en énergie. Privilégiez les protéines de qualité pour favoriser la prise de masse musculaire.";
        $recommendations['foods_to_include'] = [
            'Protéines de qualité (viandes, poissons, œufs, produits laitiers)',
            'Féculents complets (riz, pâtes, pain complet)',
            'Bonnes graisses (avocats, noix, huiles végétales)',
            'Fruits secs et oléagineux',
            'Smoothies protéinés',
            'Produits laitiers entiers'
        ];
        $recommendations['foods_to_limit'] = [
            'Aliments vides en nutriments',
            'Excès de sucres raffinés',
            'Aliments qui coupent l\'appétit'
        ];
    } else { // maintien
        $recommendations['general'] = "Pour maintenir votre poids, équilibrez votre apport calorique avec votre dépense énergétique. Adoptez une alimentation variée et équilibrée.";
        $recommendations['foods_to_include'] = [
            'Variété de fruits et légumes (5 portions par jour)',
            'Protéines maigres et végétales',
            'Céréales complètes',
            'Produits laitiers ou alternatives',
            'Graisses saines en quantité modérée'
        ];
        $recommendations['foods_to_limit'] = [
            'Aliments ultra-transformés',
            'Excès de sucres ajoutés',
            'Excès de sel',
            'Excès d\'alcool'
        ];
    }
    
    return $recommendations;
}

/**
 * Génère des recommandations d'exercices basées sur l'objectif
 * 
 * @param string $goal_type Type d'objectif ('perte', 'maintien', 'prise')
 * @return array Recommandations d'exercices
 */
function generateExerciseRecommendations($goal_type) {
    $recommendations = [
        'general' => '',
        'recommended_exercises' => [],
        'weekly_plan' => []
    ];
    
    if ($goal_type === 'perte') {
        $recommendations['general'] = "Pour perdre du poids efficacement, combinez des exercices cardiovasculaires pour brûler des calories avec des exercices de renforcement musculaire pour maintenir votre masse musculaire.";
        $recommendations['recommended_exercises'] = [
            'Cardio: course à pied, vélo, natation, HIIT',
            'Renforcement: circuits d\'entraînement, poids corporel, haltères légers',
            'Flexibilité: yoga, étirements'
        ];
        $recommendations['weekly_plan'] = [
            'Lundi: Cardio modéré (30-45 min) + renforcement haut du corps',
            'Mardi: HIIT (20-30 min)',
            'Mercredi: Repos actif (marche, yoga)',
            'Jeudi: Cardio modéré (30-45 min) + renforcement bas du corps',
            'Vendredi: HIIT (20-30 min)',
            'Samedi: Activité plus longue (60+ min, ex: randonnée, vélo)',
            'Dimanche: Repos complet ou yoga'
        ];
    } elseif ($goal_type === 'prise') {
        $recommendations['general'] = "Pour prendre de la masse musculaire, concentrez-vous sur les exercices de résistance progressive avec des poids suffisamment lourds. Limitez le cardio excessif qui pourrait brûler trop de calories.";
        $recommendations['recommended_exercises'] = [
            'Renforcement: exercices composés (squat, soulevé de terre, développé couché)',
            'Cardio modéré: pour la santé cardiovasculaire',
            'Récupération: étirements, massage, sommeil de qualité'
        ];
        $recommendations['weekly_plan'] = [
            'Lundi: Jambes et fessiers (squats, presse à cuisses)',
            'Mardi: Poitrine et triceps',
            'Mercredi: Repos ou cardio léger',
            'Jeudi: Dos et biceps',
            'Vendredi: Épaules et abdominaux',
            'Samedi: Exercices composés ou cardio modéré',
            'Dimanche: Repos complet'
        ];
    } else { // maintien
        $recommendations['general'] = "Pour maintenir votre poids et votre forme physique, adoptez une approche équilibrée combinant différents types d'exercices.";
        $recommendations['recommended_exercises'] = [
            'Cardio: 150 minutes par semaine intensité modérée',
            'Renforcement: 2-3 séances par semaine',
            'Flexibilité et équilibre: yoga, pilates, étirements'
        ];
        $recommendations['weekly_plan'] = [
            'Lundi: Cardio modéré (30 min)',
            'Mardi: Renforcement corps entier',
            'Mercredi: Yoga ou activité de loisir',
            'Jeudi: Cardio modéré à intense (30 min)',
            'Vendredi: Renforcement corps entier',
            'Samedi: Activité de plein air ou sportive',
            'Dimanche: Repos actif (marche, étirements)'
        ];
    }
    
    return $recommendations;
}

/**
 * Vérifie si une valeur est un nombre valide
 * 
 * @param mixed $value Valeur à vérifier
 * @return bool True si la valeur est un nombre valide, false sinon
 */
function isValidNumber($value) {
    return is_numeric($value) && $value >= 0;
}

/**
 * Tronque un texte à une longueur spécifiée
 * 
 * @param string $text Texte à tronquer
 * @param int $length Longueur maximale
 * @param string $suffix Suffixe à ajouter si le texte est tronqué
 * @return string Texte tronqué
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Vérifie et met à jour le poids dans user_profiles si nécessaire
 * @param int $user_id L'ID de l'utilisateur
 * @return float|null Le poids actuel (stocké ou récupéré)
 */
function ensureProfileWeight($user_id) {
    error_log("=== Début de la vérification du poids dans user_profiles ===");
    
    // Récupérer le profil de l'utilisateur
    $sql = "SELECT weight FROM user_profiles WHERE user_id = ?";
    $profile = fetchOne($sql, [$user_id]);
    
    if (!$profile) {
        error_log("Profil utilisateur non trouvé");
        return null;
    }
    
    // Si le poids n'est pas défini dans le profil
    if ($profile['weight'] === null) {
        error_log("Poids non défini dans user_profiles, récupération depuis weight_logs");
        
        // Récupérer le dernier poids enregistré
        $sql = "SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC, created_at DESC LIMIT 1";
        $latest_weight = fetchOne($sql, [$user_id]);
        
        if ($latest_weight) {
            // Mettre à jour le poids dans le profil
            $sql = "UPDATE user_profiles SET weight = ? WHERE user_id = ?";
            update($sql, [$latest_weight['weight'], $user_id]);
            error_log("Poids mis à jour dans user_profiles : " . $latest_weight['weight']);
            return $latest_weight['weight'];
        } else {
            error_log("Aucun poids trouvé dans weight_logs");
            return null;
        }
    }
    
    error_log("Poids déjà défini dans user_profiles : " . $profile['weight']);
    return $profile['weight'];
}

/**
 * Recalcule les calories en fonction du profil utilisateur et des objectifs
 * @param int $user_id L'ID de l'utilisateur
 * @return bool True si le calcul a réussi, false sinon
 */
function recalculateCalories($user_id) {
    error_log("=== Début du recalcul des calories ===");
    error_log("User ID : " . $user_id);
    
    global $pdo;
    
    // Récupérer le profil utilisateur
    $sql = "SELECT * FROM user_profiles WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        error_log("Profil utilisateur non trouvé");
        return false;
    }
    
    // Vérifier que toutes les données nécessaires sont présentes
    if (!isset($profile['weight']) || !isset($profile['height']) || 
        !isset($profile['birth_date']) || !isset($profile['gender']) || 
        !isset($profile['activity_level'])) {
        error_log("Données de profil incomplètes pour le calcul des calories");
        return false;
    }
    
    // Calculer l'âge à partir de la date de naissance
    $birth_date = new DateTime($profile['birth_date']);
    $today = new DateTime();
    $age = $birth_date->diff($today)->y;
    
    // Calculer le BMR
    $bmr = calculateBMR($profile['weight'], $profile['height'], $age, $profile['gender']);
    error_log("BMR calculé : " . $bmr);
    
    // Calculer le TDEE
    $tdee = calculateTDEE($bmr, $profile['activity_level']);
    error_log("TDEE initial calculé : " . $tdee);
    
    // Récupérer le programme actif
    $sql = "SELECT up.*, p.* 
            FROM user_programs up 
            JOIN programs p ON up.program_id = p.id 
            WHERE up.user_id = ? AND up.status = 'actif' 
            ORDER BY up.created_at DESC LIMIT 1";
    error_log("Requête pour récupérer le programme actif : " . $sql);
    $active_program = fetchOne($sql, [$user_id]);
    error_log("Programme actif trouvé : " . ($active_program ? "Oui" : "Non"));
    if ($active_program) {
        error_log("Détails du programme actif : " . print_r($active_program, true));
        // Appliquer l'ajustement calorique du programme sur le TDEE
        $calorie_adjustment = $active_program['calorie_adjustment'] / 100;
        $caloric_goal = $tdee * (1 + $calorie_adjustment);
        error_log("Calories après ajustement du programme : " . $caloric_goal);
        
        // Utiliser les ratios de macronutriments du programme
        $macro_ratios = [
            'protein' => $active_program['protein_ratio'],
            'carbs' => $active_program['carbs_ratio'],
            'fat' => $active_program['fat_ratio']
        ];
        error_log("Ratios de macronutriments du programme : " . print_r($macro_ratios, true));
    } else {
        error_log("Aucun programme actif trouvé, vérification de la requête SQL");
        // Vérifier si le programme existe avec un statut différent
        $sql = "SELECT up.*, p.* 
                FROM user_programs up 
                JOIN programs p ON up.program_id = p.id 
                WHERE up.user_id = ? 
                ORDER BY up.created_at DESC LIMIT 1";
        $last_program = fetchOne($sql, [$user_id]);
        if ($last_program) {
            error_log("Dernier programme trouvé : " . print_r($last_program, true));
        }
        // Récupérer l'objectif actif
        $sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $current_goal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current_goal) {
            error_log("Objectif actif trouvé, calcul des calories et ratios");
            // Calculer l'ajustement quotidien nécessaire pour atteindre l'objectif
            $weight_difference = $current_goal['target_weight'] - $profile['weight'];
            $days_to_target = (strtotime($current_goal['target_date']) - time()) / (24 * 60 * 60);
            $daily_adjustment = ($weight_difference * 7700) / $days_to_target;
            
            // Ajuster le TDEE avec l'ajustement quotidien
            $caloric_goal = $tdee + $daily_adjustment;
            error_log("Calories après ajustement de l'objectif : " . $caloric_goal);
            
            // Calculer les ratios de macronutriments selon l'objectif
            $macro_ratios = calculateMacroRatios($profile['weight'], 
                $current_goal['target_weight'],
                null, // Pas de programme actif
                $profile['activity_level']);
            
            // Avertir si l'ajustement est important
            if (abs($daily_adjustment) > 500) {
                error_log("ATTENTION : Ajustement calorique important de " . $daily_adjustment . " calories par jour");
            }
        } else {
            error_log("Ni programme ni objectif actif, utilisation du TDEE et ratios par défaut");
            $caloric_goal = $tdee;
            
            // Ratios par défaut (40% protéines, 30% glucides, 30% lipides)
            $macro_ratios = [
                'protein' => 0.40,
                'carbs' => 0.30,
                'fat' => 0.30
            ];
            error_log("Ratios de macronutriments par défaut : " . print_r($macro_ratios, true));
        }
    }
    
    // Mettre à jour le profil avec les nouvelles valeurs
    $sql = "UPDATE user_profiles SET 
            daily_calories = ?,
            protein_ratio = ?,
            carbs_ratio = ?,
            fat_ratio = ?
            WHERE user_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $caloric_goal,
        $macro_ratios['protein'],
        $macro_ratios['carbs'],
        $macro_ratios['fat'],
        $user_id
    ]);
    
    if ($result) {
        error_log("Profil mis à jour avec succès");
        error_log("=== Fin du recalcul des calories ===");
        return true;
    } else {
        error_log("Erreur lors de la mise à jour du profil : " . print_r($stmt->errorInfo(), true));
        error_log("=== Fin du recalcul des calories ===");
        return false;
    }
}

/**
 * Exécute une requête SQL sans retourner de résultats
 * @param string $sql La requête SQL à exécuter
 * @param array $params Les paramètres de la requête
 * @return bool True si la requête a réussi, false sinon
 */
function execute($sql, $params = []) {
    global $pdo;
    try {
        error_log("Exécution de la requête execute : " . $sql);
        error_log("Paramètres : " . print_r($params, true));
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        error_log("Résultat de execute : " . ($result ? "Succès" : "Échec"));
        return $result;
    } catch (PDOException $e) {
        error_log("Erreur SQL dans execute: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcule les ratios de macronutriments en fonction de l'objectif et de l'activité
 * @param float $current_weight Poids actuel
 * @param float $target_weight Poids cible
 * @param array|null $active_program Programme actif
 * @param string $activity_level Niveau d'activité
 * @return array Ratios de macronutriments
 */
function calculateMacroRatios($current_weight, $target_weight, $active_program, $activity_level) {
    error_log("=== Début du calcul des ratios de macronutriments ===");
    error_log("Poids actuel : " . $current_weight);
    error_log("Poids cible : " . $target_weight);
    error_log("Niveau d'activité : " . $activity_level);
    
    // Si un programme actif existe, utiliser ses ratios
    if ($active_program) {
        error_log("Programme actif trouvé, utilisation des ratios du programme");
        $base_ratios = [
            'protein' => $active_program['protein_ratio'] / 100,  // Convertir le pourcentage en décimal
            'carbs' => $active_program['carbs_ratio'] / 100,
            'fat' => $active_program['fat_ratio'] / 100
        ];
        error_log("Ratios du programme : " . print_r($base_ratios, true));
    } else {
        // Déterminer si c'est une perte ou prise de poids
        $is_weight_loss = $target_weight < $current_weight;
        error_log("Type d'objectif : " . ($is_weight_loss ? "Perte de poids" : "Prise de poids"));
        
        // Base ratios selon le type d'objectif
        if ($is_weight_loss) {
            // Perte de poids : plus de protéines pour préserver la masse musculaire
            $base_ratios = [
                'protein' => 0.40,  // 40% protéines
                'carbs' => 0.30,    // 30% glucides
                'fat' => 0.30       // 30% lipides
            ];
            error_log("Ratios de base pour la perte de poids : " . print_r($base_ratios, true));
        } else {
            // Prise de poids : plus de glucides pour l'énergie
            $base_ratios = [
                'protein' => 0.30,  // 30% protéines
                'carbs' => 0.50,    // 50% glucides
                'fat' => 0.20       // 20% lipides
            ];
            error_log("Ratios de base pour la prise de poids : " . print_r($base_ratios, true));
        }
        
        // Ajuster selon le niveau d'activité
        error_log("Ajustement selon le niveau d'activité");
        switch ($activity_level) {
            case 'sedentaire':
                // Réduire les glucides pour les sédentaires
                $base_ratios['carbs'] -= 0.05;
                $base_ratios['protein'] += 0.05;
                error_log("Ajustement pour niveau sédentaire : " . print_r($base_ratios, true));
                break;
            case 'modere':
                error_log("Niveau modéré, pas d'ajustement nécessaire");
                break;
            case 'actif':
                // Augmenter légèrement les glucides
                $base_ratios['carbs'] += 0.05;
                $base_ratios['fat'] -= 0.05;
                error_log("Ajustement pour niveau actif : " . print_r($base_ratios, true));
                break;
            case 'tres_actif':
                // Augmenter davantage les glucides
                $base_ratios['carbs'] += 0.10;
                $base_ratios['fat'] -= 0.10;
                error_log("Ajustement pour niveau très actif : " . print_r($base_ratios, true));
                break;
        }
    }
    
    // Normaliser les ratios pour qu'ils totalisent 1
    $total = array_sum($base_ratios);
    $base_ratios['protein'] /= $total;
    $base_ratios['carbs'] /= $total;
    $base_ratios['fat'] /= $total;
    
    error_log("Ratios finaux normalisés : " . print_r($base_ratios, true));
    error_log("=== Fin du calcul des ratios de macronutriments ===");
    
    return $base_ratios;
}

/**
 * Calcule la recommandation d'hydratation quotidienne en litres
 * Formule : (poids * 0.025) + (activité * 0.3) + (taille * 0.008)
 * @param array $user Les données de l'utilisateur
 * @return float La quantité d'eau recommandée en litres
 */
function calculateWaterGoal($user) {
    // Vérifier si l'utilisateur a un poids défini
    if (!isset($user['weight']) || $user['weight'] <= 0) {
        error_log("Poids non défini pour le calcul d'eau");
        return 2.5; // Valeur par défaut
    }

    // Calculer la base en fonction du poids (30 ml/kg)
    $base_water = $user['weight'] * 0.03; // Conversion en litres

    // Limiter entre 1.5 et 4 litres
    $water_goal = max(1.5, min(4, $base_water));

    error_log("Calcul de l'objectif d'eau pour l'utilisateur : " . print_r($user, true));
    error_log("Base (poids) : {$base_water}L");
    error_log("Valeur finale après limites : {$water_goal}L");

    return $water_goal;
}

/**
 * Vérifie les notifications de repas pour l'utilisateur
 * @param int $user_id L'ID de l'utilisateur
 * @return array Les notifications à afficher
 */
function checkMealNotifications($user_id) {
    $notifications = [];
    
    // Définir le fuseau horaire local
    date_default_timezone_set('Europe/Paris');
    
    // Récupérer la date et l'heure actuelles
    $now = new DateTime();
    $current_time = $now->format('H:i:s');
    $current_date = $now->format('Y-m-d');
    
    error_log("=== Début de la vérification des notifications ===");
    error_log("Heure actuelle (locale) : " . $current_time);
    error_log("Date actuelle : " . $current_date);
    
    // Vérifier la dernière réinitialisation
    $sql = "SELECT last_notification_reset FROM users WHERE id = ?";
    $user = fetchOne($sql, [$user_id]);
    $last_reset = $user['last_notification_reset'] ?? null;
    
    error_log("Dernière réinitialisation : " . $last_reset);
    
    // Si c'est un nouveau jour, réinitialiser les notifications
    if ($last_reset !== $current_date) {
        $sql = "UPDATE users SET last_notification_reset = ? WHERE id = ?";
        update($sql, [$current_date, $user_id]);
        error_log("Réinitialisation des notifications pour le nouveau jour");
    }
    
    // Récupérer les préférences de notifications
    error_log("=== Vérification de la table meal_notification_preferences ===");
    if (!tableExists('meal_notification_preferences')) {
        error_log("La table meal_notification_preferences n'existe pas");
        return [];
    }
    error_log("La table meal_notification_preferences existe");
    
    $sql = "SELECT * FROM meal_notification_preferences WHERE user_id = ? AND is_active = TRUE";
    error_log("Exécution de la requête : " . $sql);
    error_log("Paramètres : " . json_encode([$user_id]));
    
    $preferences = fetchAll($sql, [$user_id]);
    
    error_log("Préférences de notifications trouvées : " . count($preferences));
    if (count($preferences) > 0) {
        error_log("Détail des préférences : " . json_encode($preferences));
    }
    
    foreach ($preferences as $pref) {
        error_log("=== Vérification de la préférence ===");
        error_log("Type de repas : " . $pref['meal_type']);
        error_log("Heure de début : " . $pref['start_time']);
        error_log("Heure de fin : " . $pref['end_time']);
        error_log("ID de la préférence : " . $pref['id']);
        error_log("User ID : " . $pref['user_id']);
        error_log("Is active : " . $pref['is_active']);
        
        // Convertir les heures en objets DateTime pour la comparaison
        $start_time = DateTime::createFromFormat('H:i:s', $pref['start_time']);
        $end_time = DateTime::createFromFormat('H:i:s', $pref['end_time']);
        $current = DateTime::createFromFormat('H:i:s', $current_time);
        
        error_log("L'heure actuelle est " . ($current >= $start_time ? "après" : "avant") . " l'heure de début");
        
        // Vérifier si l'heure actuelle est dans la plage horaire ou si le repas est en retard
        if ($current >= $start_time) {
            // Vérifier si un repas a déjà été enregistré aujourd'hui pour ce type
            $sql = "SELECT COUNT(*) as count FROM meals 
                    WHERE user_id = ? 
                    AND meal_type = ? 
                    AND DATE(log_date) = ?";
            $result = fetchOne($sql, [$user_id, $pref['meal_type'], $current_date]);
            $meal_count = $result['count'];
            
            error_log("Nombre de repas enregistrés aujourd'hui pour ce type : " . $meal_count);
            
            if ($meal_count == 0) {
                error_log("Aucun repas enregistré, création de la notification");
                
                // Calculer la priorité en fonction du temps écoulé depuis l'heure de début
                $time_diff = $current->getTimestamp() - $start_time->getTimestamp();
                $hours_diff = $time_diff / 3600;
                
                $priority = 1; // Priorité normale par défaut
                if ($hours_diff >= 2) {
                    $priority = 2; // Priorité urgente si plus de 2 heures ont passé
                }
                
                $notification = [
                    'type' => 'meal_reminder',
                    'message' => "N'oubliez pas de prendre votre " . getMealTypeLabel($pref['meal_type']) . " !",
                    'meal_type' => $pref['meal_type'],
                    'start_time' => $pref['start_time'],
                    'end_time' => $pref['end_time'],
                    'priority' => $priority,
                    'action_url' => "food-log.php?action=add&meal_type=" . $pref['meal_type']
                ];
                
                error_log("Notification ajoutée : " . json_encode($notification));
                $notifications[] = $notification;
            }
        }
    }
    
    error_log("=== Fin de la vérification des notifications ===");
    error_log("Nombre total de notifications : " . count($notifications));
    
    return $notifications;
}

/**
 * Convertit le type de repas en label français
 * 
 * @param string $meal_type Type de repas (petit_dejeuner, dejeuner, diner)
 * @return string Label en français
 */
function getMealTypeLabel($meal_type) {
    $labels = [
        'petit_dejeuner' => 'petit-déjeuner',
        'dejeuner' => 'déjeuner',
        'diner' => 'dîner'
    ];
    
    return $labels[$meal_type] ?? $meal_type;
}

/**
 * Crée un post communautaire
 * @param int $user_id ID de l'utilisateur
 * @param string $post_type Type de post (meal, exercise, program, goal, message)
 * @param string $content Contenu du post
 * @param int|null $reference_id ID de référence (meal_id, exercise_id, etc.)
 * @param string|null $reference_type Type de référence
 * @param string $visibility Visibilité du post (public ou group)
 * @param int|null $group_id ID du groupe si la visibilité est 'group'
 * @return bool
 */
function createCommunityPost($user_id, $post_type, $content, $reference_id = null, $reference_type = null, $visibility = 'public', $group_id = null) {
    global $pdo;
    
    try {
        // Si la visibilité est 'group', s'assurer que le group_id est fourni
        if ($visibility === 'group' && !$group_id) {
            error_log("Tentative de création d'un post de groupe sans group_id");
            return false;
        }
        
        // Si la visibilité est 'group', vérifier si le groupe existe
        if ($visibility === 'group' && $group_id) {
            // Vérifier si le groupe existe
            $sql = "SELECT id FROM community_groups WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$group_id]);
            if (!$stmt->fetch()) {
                error_log("Tentative de création d'un post pour un groupe inexistant (ID: $group_id)");
                return false;
            }
            
            // Vérifier si l'utilisateur est membre du groupe
            if (!isGroupMember($group_id, $user_id)) {
                error_log("Tentative de création d'un post par un non-membre du groupe (User ID: $user_id, Group ID: $group_id)");
                return false;
            }
        }
        
        // Si la visibilité est 'public', s'assurer que group_id est null
        if ($visibility === 'public') {
            $group_id = null;
        }
        
        $sql = "INSERT INTO community_posts (user_id, post_type, content, reference_id, reference_type, visibility, group_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id, $post_type, $content, $reference_id, $reference_type, $visibility, $group_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la création du post : " . $e->getMessage());
        throw $e; // Propager l'erreur pour une gestion plus fine dans create-post.php
    }
}

/**
 * Ajoute ou supprime un like sur un post
 * @param int $post_id ID du post
 * @param int $user_id ID de l'utilisateur
 * @return array
 */
function togglePostLike($post_id, $user_id) {
    try {
        error_log("Tentative de basculement du like - Post ID: $post_id, User ID: $user_id");
        
        // Vérifier si le post existe
        $sql = "SELECT id FROM community_posts WHERE id = ?";
        $post = fetchOne($sql, [$post_id]);
        if (!$post) {
            error_log("Erreur: Le post $post_id n'existe pas");
            return false;
        }

        // Vérifier si l'utilisateur a déjà liké le post
        $sql = "SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?";
        $existing_like = fetchOne($sql, [$post_id, $user_id]);

        if ($existing_like) {
            // Supprimer le like
            $sql = "DELETE FROM post_likes WHERE post_id = ? AND user_id = ?";
            $result = execute($sql, [$post_id, $user_id]);
            error_log("Suppression du like: " . ($result ? "succès" : "échec"));
        } else {
            // Ajouter le like
            $sql = "INSERT INTO post_likes (post_id, user_id, created_at) VALUES (?, ?, NOW())";
            $result = insert($sql, [$post_id, $user_id]);
            error_log("Ajout du like: " . ($result ? "succès" : "échec"));
        }

        return $result;
    } catch (Exception $e) {
        error_log("Erreur lors du basculement du like: " . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute un commentaire à un post
 * @param int $post_id ID du post
 * @param int $user_id ID de l'utilisateur
 * @param string $content Contenu du commentaire
 * @return array
 */
function addComment($post_id, $user_id, $content) {
    try {
        error_log("Tentative d'ajout de commentaire - Post ID: $post_id, User ID: $user_id");
        
        // Vérifier si le post existe
        $sql = "SELECT id FROM community_posts WHERE id = ?";
        $post = fetchOne($sql, [$post_id]);
        if (!$post) {
            error_log("Erreur: Le post $post_id n'existe pas");
            return false;
        }

        // Insérer le commentaire
        $sql = "INSERT INTO post_comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
        $result = insert($sql, [$post_id, $user_id, $content]);
        
        error_log("Résultat de l'insertion du commentaire: " . ($result ? "succès" : "échec"));
        return $result;
    } catch (Exception $e) {
        error_log("Erreur lors de l'ajout du commentaire: " . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute ou supprime un abonnement entre utilisateurs
 * @param int $following_id ID de l'utilisateur à suivre
 * @param int $follower_id ID de l'utilisateur qui suit
 * @return array
 */
function toggleFollow($following_id, $follower_id) {
    try {
        error_log("Tentative de basculement de l'abonnement - Following ID: $following_id, Follower ID: $follower_id");
        
        // Vérifier si l'utilisateur à suivre existe
        $sql = "SELECT id FROM users WHERE id = ?";
        $user = fetchOne($sql, [$following_id]);
        if (!$user) {
            error_log("Erreur: L'utilisateur $following_id n'existe pas");
            return false;
        }

        // Vérifier si l'abonnement existe déjà
        $sql = "SELECT id FROM user_follows WHERE following_id = ? AND follower_id = ?";
        $existing_follow = fetchOne($sql, [$following_id, $follower_id]);

        if ($existing_follow) {
            // Supprimer l'abonnement
            $sql = "DELETE FROM user_follows WHERE following_id = ? AND follower_id = ?";
            $result = execute($sql, [$following_id, $follower_id]);
            error_log("Suppression de l'abonnement: " . ($result ? "succès" : "échec"));
        } else {
            // Ajouter l'abonnement
            $sql = "INSERT INTO user_follows (following_id, follower_id, created_at) VALUES (?, ?, NOW())";
            $result = insert($sql, [$following_id, $follower_id]);
            error_log("Ajout de l'abonnement: " . ($result ? "succès" : "échec"));
        }

        return $result;
    } catch (Exception $e) {
        error_log("Erreur lors du basculement de l'abonnement: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère le nombre de likes d'un post
 * @param int $post_id ID du post
 * @return int
 */
function getPostLikesCount($post_id) {
    global $db;
    
    $sql = "SELECT likes_count FROM community_posts WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$post_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['likes_count'] : 0;
}

/**
 * Récupère le nombre de commentaires d'un post
 * @param int $post_id ID du post
 * @return int
 */
function getPostCommentsCount($post_id) {
    global $db;
    
    $sql = "SELECT comments_count FROM community_posts WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$post_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['comments_count'] : 0;
}

/**
 * Crée un nouveau groupe
 * @param int $user_id ID de l'utilisateur qui crée le groupe
 * @param string $name Nom du groupe
 * @param string $description Description du groupe
 * @return bool|int ID du groupe si succès, false sinon
 */
function createGroup($user_id, $name, $description) {
    try {
        // Créer le groupe
        $sql = "INSERT INTO community_groups (name, description, created_by) VALUES (?, ?, ?)";
        $group_id = insert($sql, [$name, $description, $user_id]);
        
        if ($group_id) {
            // Ajouter le créateur comme admin du groupe
            $sql = "INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')";
            if (insert($sql, [$group_id, $user_id])) {
                return $group_id;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Erreur lors de la création du groupe : " . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute un membre à un groupe
 * @param int $group_id ID du groupe
 * @param int $user_id ID de l'utilisateur à ajouter
 * @param string $role Rôle dans le groupe (admin ou member)
 * @return bool
 */
function addGroupMember($group_id, $user_id, $role = 'member') {
    $sql = "INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)";
    return insert($sql, [$group_id, $user_id, $role]);
}

/**
 * Retire un membre d'un groupe
 * @param int $group_id ID du groupe
 * @param int $user_id ID de l'utilisateur à retirer
 * @return bool
 */
function removeGroupMember($group_id, $user_id) {
    $sql = "DELETE FROM group_members WHERE group_id = ? AND user_id = ?";
    return execute($sql, [$group_id, $user_id]);
}

/**
 * Vérifie si un utilisateur est membre d'un groupe
 * @param int $group_id ID du groupe
 * @param int $user_id ID de l'utilisateur
 * @return bool
 */
function isGroupMember($group_id, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM group_members WHERE group_id = ? AND user_id = ?";
    $result = fetchOne($sql, [$group_id, $user_id]);
    return $result['count'] > 0;
}

/**
 * Vérifie si un utilisateur est admin d'un groupe
 * @param int $group_id ID du groupe
 * @param int $user_id ID de l'utilisateur
 * @return bool
 */
function isGroupAdmin($group_id, $user_id) {
    $sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?";
    $result = fetchOne($sql, [$group_id, $user_id]);
    return $result && $result['role'] === 'admin';
}

/**
 * Récupère les groupes d'un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @return array Liste des groupes
 */
function getUserGroups($user_id) {
    $sql = "SELECT g.*, gm.role 
            FROM community_groups g 
            JOIN group_members gm ON g.id = gm.group_id 
            WHERE gm.user_id = ?";
    return fetchAll($sql, [$user_id]);
}

/**
 * Récupère les membres d'un groupe
 * @param int $group_id ID du groupe
 * @return array Liste des membres
 */
function getGroupMembers($group_id) {
    $sql = "SELECT u.*, gm.role, gm.joined_at 
            FROM users u 
            JOIN group_members gm ON u.id = gm.user_id 
            WHERE gm.group_id = ?";
    return fetchAll($sql, [$group_id]);
}

/**
 * Modifie la fonction pour récupérer les posts en tenant compte de la visibilité
 */
function getCommunityPosts($user_id, $limit = 20) {
    $sql = "SELECT cp.*, u.username, u.avatar,
            CASE 
                WHEN cp.post_type = 'meal' THEN m.total_calories
                WHEN cp.post_type = 'exercise' THEN el.calories_burned
                ELSE NULL
            END as calories,
            CASE 
                WHEN cp.post_type = 'meal' THEN m.notes
                WHEN cp.post_type = 'exercise' THEN el.notes
                ELSE NULL
            END as notes,
            CASE 
                WHEN cp.post_type = 'program' THEN p.name
                WHEN cp.post_type = 'goal' THEN g.target_weight
                ELSE NULL
            END as reference_name
            FROM community_posts cp
            JOIN users u ON cp.user_id = u.id
            LEFT JOIN meals m ON cp.reference_id = m.id AND cp.post_type = 'meal'
            LEFT JOIN exercise_logs el ON cp.reference_id = el.id AND cp.post_type = 'exercise'
            LEFT JOIN programs p ON cp.reference_id = p.id AND cp.post_type = 'program'
            LEFT JOIN goals g ON cp.reference_id = g.id AND cp.post_type = 'goal'
            WHERE cp.visibility = 'public' 
            OR (cp.visibility = 'group' AND cp.group_id IN (
                SELECT group_id FROM group_members WHERE user_id = ?
            ))
            ORDER BY cp.created_at DESC
            LIMIT ?";
    
    return fetchAll($sql, [$user_id, $limit]);
}

/**
 * Invite un utilisateur à rejoindre un groupe
 * @param int $group_id ID du groupe
 * @param int $invited_by ID de l'utilisateur qui invite
 * @param int $invited_user_id ID de l'utilisateur invité
 * @return bool
 */
function inviteUserToGroup($group_id, $invited_by, $invited_user_id) {
    // Vérifier si l'invitant est admin du groupe
    if (!isGroupAdmin($group_id, $invited_by)) {
        error_log("Tentative d'invitation par un non-admin (User ID: $invited_by, Group ID: $group_id)");
        return false;
    }
    
    // Vérifier si l'utilisateur est déjà membre du groupe
    if (isGroupMember($group_id, $invited_user_id)) {
        error_log("Tentative d'invitation d'un membre existant (User ID: $invited_user_id, Group ID: $group_id)");
        return false;
    }
    
    // Vérifier si une invitation est déjà en attente
    $sql = "SELECT id FROM group_invitations 
            WHERE group_id = ? AND invited_user_id = ? AND status = 'pending'";
    if (fetchOne($sql, [$group_id, $invited_user_id])) {
        error_log("Invitation déjà en attente (User ID: $invited_user_id, Group ID: $group_id)");
        return false;
    }
    
    // Créer l'invitation
    $sql = "INSERT INTO group_invitations (group_id, invited_by, invited_user_id, expires_at) 
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))";
    return insert($sql, [$group_id, $invited_by, $invited_user_id]);
}

/**
 * Accepte une invitation à rejoindre un groupe
 * @param int $invitation_id ID de l'invitation
 * @param int $user_id ID de l'utilisateur qui accepte
 * @return bool
 */
function acceptGroupInvitation($invitation_id, $user_id) {
    // Récupérer les informations de l'invitation
    $sql = "SELECT * FROM group_invitations WHERE id = ? AND invited_user_id = ? AND status = 'pending'";
    $invitation = fetchOne($sql, [$invitation_id, $user_id]);
    
    if (!$invitation) {
        error_log("Tentative d'acceptation d'une invitation invalide (Invitation ID: $invitation_id, User ID: $user_id)");
        return false;
    }
    
    // Vérifier si l'invitation n'a pas expiré
    if (strtotime($invitation['expires_at']) < time()) {
        error_log("Tentative d'acceptation d'une invitation expirée (Invitation ID: $invitation_id)");
        return false;
    }
    
    try {
        // Démarrer la transaction
        $pdo->beginTransaction();
        
        // Ajouter l'utilisateur au groupe
        $sql = "INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invitation['group_id'], $user_id]);
        
        // Mettre à jour le statut de l'invitation
        $sql = "UPDATE group_invitations SET status = 'accepted' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invitation_id]);
        
        // Valider la transaction
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        $pdo->rollBack();
        error_log("Erreur lors de l'acceptation de l'invitation : " . $e->getMessage());
        return false;
    }
}

/**
 * Rejette une invitation à rejoindre un groupe
 * @param int $invitation_id ID de l'invitation
 * @param int $user_id ID de l'utilisateur qui rejette
 * @return bool
 */
function rejectGroupInvitation($invitation_id, $user_id) {
    $sql = "UPDATE group_invitations 
            SET status = 'rejected' 
            WHERE id = ? AND invited_user_id = ? AND status = 'pending'";
    return execute($sql, [$invitation_id, $user_id]);
}

/**
 * Récupère les invitations en attente d'un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @return array Liste des invitations
 */
function getUserPendingInvitations($user_id) {
    $sql = "SELECT gi.*, cg.name as group_name, u.username as invited_by_name 
            FROM group_invitations gi
            JOIN community_groups cg ON gi.group_id = cg.id
            JOIN users u ON gi.invited_by = u.id
            WHERE gi.invited_user_id = ? AND gi.status = 'pending'
            ORDER BY gi.created_at DESC";
    return fetchAll($sql, [$user_id]);
}

// Fonction pour supprimer un repas
function deleteMeal($meal_id, $user_id) {
    try {
        error_log("Tentative de suppression du repas - Meal ID: $meal_id, User ID: $user_id");
        
        // Vérifier si le repas appartient à l'utilisateur
        $sql = "SELECT id FROM meals WHERE id = ? AND user_id = ?";
        $meal = fetchOne($sql, [$meal_id, $user_id]);
        if (!$meal) {
            error_log("Erreur: Le repas $meal_id n'appartient pas à l'utilisateur $user_id");
            return false;
        }

        // Supprimer les aliments associés
        $sql = "DELETE FROM meal_foods WHERE meal_id = ?";
        execute($sql, [$meal_id]);

        // Supprimer le repas
        $sql = "DELETE FROM meals WHERE id = ?";
        $result = execute($sql, [$meal_id]);
        
        error_log("Résultat de la suppression du repas: " . ($result ? "succès" : "échec"));
        return $result;
    } catch (Exception $e) {
        error_log("Erreur lors de la suppression du repas: " . $e->getMessage());
        return false;
    }
}

// Fonction pour supprimer un exercice
function deleteExercise($exercise_id, $user_id) {
    try {
        error_log("Tentative de suppression de l'exercice - Exercise ID: $exercise_id, User ID: $user_id");
        
        // Vérifier si l'exercice appartient à l'utilisateur
        $sql = "SELECT id FROM exercise_logs WHERE id = ? AND user_id = ?";
        $exercise = fetchOne($sql, [$exercise_id, $user_id]);
        if (!$exercise) {
            error_log("Erreur: L'exercice $exercise_id n'appartient pas à l'utilisateur $user_id");
            return false;
        }

        // Supprimer l'exercice
        $sql = "DELETE FROM exercise_logs WHERE id = ? AND user_id = ?";
        $result = execute($sql, [$exercise_id, $user_id]);
        
        error_log("Résultat de la suppression de l'exercice: " . ($result ? "succès" : "échec"));
        return $result;
    } catch (Exception $e) {
        error_log("Erreur lors de la suppression de l'exercice: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si un utilisateur a déjà liké un post
 * @param int $post_id ID du post
 * @param int $user_id ID de l'utilisateur
 * @return bool True si l'utilisateur a liké le post, false sinon
 */
function isPostLiked($post_id, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM post_likes WHERE post_id = ? AND user_id = ?";
    $result = fetchOne($sql, [$post_id, $user_id]);
    return $result['count'] > 0;
}

/**
 * Récupère les statistiques d'exercice pour un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Tableau contenant les statistiques d'exercice
 */
function getExerciseStats($user_id) {
    try {
        // Récupérer les statistiques de base
        $sql = "SELECT 
                COUNT(*) as total_exercises,
                SUM(duration) as total_duration,
                SUM(calories_burned) as total_calories
                FROM exercise_logs
                WHERE user_id = ?";
        
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si aucune donnée n'est trouvée, retourner des valeurs par défaut
        if (!$stats) {
            return [
                'total_exercises' => 0,
                'total_duration' => 0,
                'total_calories' => 0
            ];
        }
        
        // Convertir les valeurs en nombres
        $stats['total_exercises'] = (int)$stats['total_exercises'];
        $stats['total_duration'] = (int)$stats['total_duration'];
        $stats['total_calories'] = (int)$stats['total_calories'];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des statistiques d'exercice: " . $e->getMessage());
        return [
            'total_exercises' => 0,
            'total_duration' => 0,
            'total_calories' => 0
        ];
    }
}

// Fonction pour obtenir les suggestions de repas
function getMealSuggestions($user_id) {
    try {
        error_log("=== Début de getMealSuggestions ===");
        error_log("User ID: " . $user_id);
        
        // Récupérer l'objectif de l'utilisateur
        $sql = "SELECT g.* FROM goals g WHERE g.user_id = ? AND g.status = 'active'";
        $goal = fetchOne($sql, [$user_id]);
        error_log("Objectif trouvé : " . ($goal ? "Oui" : "Non"));
        
        // Récupérer les suggestions d'IA
        $sql = "SELECT id, content, created_at FROM ai_suggestions 
                WHERE user_id = ? AND suggestion_type IN ('repas', 'alimentation') 
                ORDER BY created_at DESC LIMIT 5";
        $suggestions = fetchAll($sql, [$user_id]);
        error_log("Nombre de suggestions trouvées : " . count($suggestions));
        
        // Ajouter les informations nutritionnelles pour chaque suggestion
        foreach ($suggestions as &$suggestion) {
            error_log("=== Traitement de la suggestion " . $suggestion['id'] . " ===");
            
            // Parser le JSON de la suggestion
            $data = json_decode($suggestion['content'], true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("❌ Erreur de parsing JSON : " . json_last_error_msg());
                error_log("Contenu brut : " . $suggestion['content']);
                continue;
            }
            
            error_log("✅ JSON parsé avec succès : " . print_r($data, true));
            
            // Formater la suggestion
            $suggestion['name'] = $data['nom_du_repas'] ?? 'Repas sans nom';
            $suggestion['totals'] = [
                'calories' => $data['valeurs_nutritionnelles']['calories'] ?? 0,
                'protein' => $data['valeurs_nutritionnelles']['proteines'] ?? 0,
                'carbs' => $data['valeurs_nutritionnelles']['glucides'] ?? 0,
                'fat' => $data['valeurs_nutritionnelles']['lipides'] ?? 0
            ];
            $suggestion['description'] = [
                'ingredients' => $data['ingredients'] ?? [],
                'instructions' => $data['instructions'] ?? []
            ];
            
            error_log("Description finale : " . print_r($suggestion['description'], true));
            
            // Ajouter des informations sur la compatibilité avec l'objectif
            if ($goal) {
                $suggestion['goal_compatibility'] = calculateGoalCompatibility($suggestion['totals'], $goal);
                error_log("Compatibilité avec l'objectif : " . $suggestion['goal_compatibility']);
            }
        }
        
        error_log("=== Fin de getMealSuggestions ===");
        return $suggestions;
    } catch (Exception $e) {
        error_log("Erreur dans getMealSuggestions: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère un paramètre de l'application
 * @param string $setting_name Le nom du paramètre
 * @return string|null La valeur du paramètre ou null si non trouvé
 */
function getSetting($setting_name) {
    global $db;
    
    $sql = "SELECT setting_value FROM settings WHERE setting_name = ?";
    $result = fetchOne($sql, [$setting_name]);
    
    return $result ? $result['setting_value'] : null;
}

/**
 * Appelle l'API ChatGPT pour générer une suggestion
 * @param string $prompt Le prompt à envoyer à l'API
 * @param string $api_key La clé API ChatGPT
 * @return string La réponse de l'API
 */
function callChatGPTAPI($prompt, $api_key) {
    $url = 'https://api.openai.com/v1/chat/completions';
    
    // Ajouter des instructions spécifiques pour les macronutriments
    $prompt .= "\n\nIMPORTANT : Pour chaque ingrédient, vous devez fournir les valeurs nutritionnelles pour la quantité exacte spécifiée :\n";
    $prompt .= "- calories : nombre de calories pour la quantité spécifiée\n";
    $prompt .= "- proteines : grammes de protéines pour la quantité spécifiée\n";
    $prompt .= "- glucides : grammes de glucides pour la quantité spécifiée\n";
    $prompt .= "- lipides : grammes de lipides pour la quantité spécifiée\n\n";
    $prompt .= "CRITÈRES DE COHÉRENCE STRICTS :\n";
    $prompt .= "1. Les valeurs pour chaque ingrédient doivent être réalistes et cohérentes avec les aliments courants\n";
    $prompt .= "2. Pour les ingrédients comme l'huile d'olive, utiliser des mesures standard (ex: 1 cuillère à soupe = 15ml)\n";
    $prompt .= "3. Pour les épices et aromates (sel, poivre, herbes), ne pas inclure les valeurs nutritionnelles car négligeables\n\n";
    $prompt .= "Exemple de format pour un ingrédient :\n";
    $prompt .= "{\n";
    $prompt .= "  \"nom\": \"Poulet\",\n";
    $prompt .= "  \"quantité\": \"200g\",\n";
    $prompt .= "  \"calories\": 330,  // Calories pour 200g de poulet\n";
    $prompt .= "  \"proteines\": 62,  // Protéines pour 200g de poulet\n";
    $prompt .= "  \"glucides\": 0,    // Glucides pour 200g de poulet\n";
    $prompt .= "  \"lipides\": 8      // Lipides pour 200g de poulet\n";
    $prompt .= "}\n";
    
    $data = [
        'model' => 'gpt-4',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Vous êtes un nutritionniste expert qui fournit des suggestions de repas avec des valeurs nutritionnelles précises pour chaque ingrédient. Fournissez uniquement les valeurs pour chaque ingrédient, pas les totaux.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2000
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        error_log("❌ Erreur API ChatGPT - Code HTTP: " . $http_code);
        error_log("Réponse: " . $response);
        throw new Exception("Erreur lors de l'appel à l'API ChatGPT");
    }
    
    $result = json_decode($response, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        error_log("❌ Réponse API invalide: " . print_r($result, true));
        throw new Exception("Réponse invalide de l'API ChatGPT");
    }
    
    return $result['choices'][0]['message']['content'];
}

/**
 * Analyse le contenu JSON d'une suggestion et gère les erreurs
 * 
 * @param string $content Contenu JSON de la suggestion
 * @return array Données parsées
 * @throws Exception Si le parsing échoue
 */
function parseSuggestionContent($content) {
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erreur de parsing JSON: " . json_last_error_msg());
    }
    return $data;
}

/**
 * Calcule les valeurs nutritionnelles totales à partir des ingrédients
 * 
 * @param array $ingredients Liste des ingrédients
 * @return array Totaux nutritionnels
 */
function calculateTotalNutrition($ingredients) {
    $totals = [
        'calories' => 0,
        'protein' => 0,
        'carbs' => 0,
        'fat' => 0
    ];
    
    foreach ($ingredients as $ingredient) {
        $food_id = createOrGetFood($ingredient['nom']);
        $nutrition = calculateNutritionForQuantity($food_id, $ingredient['quantité']);
        
        $totals['calories'] += $nutrition['calories'];
        $totals['protein'] += $nutrition['protein'];
        $totals['carbs'] += $nutrition['carbs'];
        $totals['fat'] += $nutrition['fat'];
    }
    
    return $totals;
}

/**
 * Crée un nouveau repas dans la base de données
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $meal_type Type de repas
 * @param string $notes Notes sur le repas
 * @param array $totals Valeurs nutritionnelles totales
 * @return int|false ID du repas créé ou false en cas d'erreur
 */
function createMealFromSuggestion($user_id, $meal_type, $notes, $totals) {
    $sql = "INSERT INTO meals (user_id, meal_type, log_date, notes, total_calories, total_protein, total_carbs, total_fat) 
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
            
    return insert($sql, [
        $user_id,
        $meal_type,
        $notes,
        $totals['calories'],
        $totals['protein'],
        $totals['carbs'],
        $totals['fat']
    ]);
}

/**
 * Ajoute les ingrédients à un repas
 * 
 * @param int $meal_id ID du repas
 * @param array $ingredients Liste des ingrédients
 * @return bool True si succès, false sinon
 */
function addIngredientsToMeal($meal_id, $ingredients) {
    foreach ($ingredients as $ingredient) {
        $food_id = createOrGetFood($ingredient['nom']);
        $nutrition = calculateNutritionForQuantity($food_id, $ingredient['quantité']);
        
        $sql = "INSERT INTO meal_foods (meal_id, food_id, quantity, calories, protein, carbs, fat) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
                
        if (!insert($sql, [
            $meal_id,
            $food_id,
            $ingredient['quantité'],
            $nutrition['calories'],
            $nutrition['protein'],
            $nutrition['carbs'],
            $nutrition['fat']
        ])) {
            return false;
        }
    }
    
    return true;
}

/**
 * Récupère une suggestion par son ID
 * 
 * @param int $suggestion_id ID de la suggestion
 * @param int $user_id ID de l'utilisateur
 * @return array|false Données de la suggestion ou false si non trouvée
 */
function fetchSuggestion($suggestion_id, $user_id) {
    $sql = "SELECT content FROM ai_suggestions WHERE id = ? AND user_id = ?";
    return fetchOne($sql, [$suggestion_id, $user_id]);
}

/**
 * Calcule l'âge à partir d'une date de naissance
 * 
 * @param string $birth_date Date de naissance au format YYYY-MM-DD
 * @return int Âge calculé
 */
function calculateAge($birth_date) {
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    $age = $birth->diff($today);
    return $age->y;
}

/**
 * Exécute une requête de suppression
 * 
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return bool True si la suppression a réussi, false sinon
 */
function deleteRecord($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (Exception $e) {
        error_log("Erreur SQL: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère une suggestion de repas personnalisée
 * 
 * @param array $profile Profil de l'utilisateur
 * @param array $latest_weight Dernier poids enregistré
 * @param array $current_goal Objectif actuel
 * @param array $active_program Programme actif
 * @param array $favorite_foods Aliments préférés
 * @param array $blacklisted_foods Aliments à éviter
 * @return string Suggestion de repas
 */
function generateMealSuggestion($profile, $latest_weight, $current_goal, $active_program, $favorite_foods, $blacklisted_foods, $meal_type) {
    try {
        // Récupérer la clé API ChatGPT
        $sql = "SELECT setting_value FROM settings WHERE setting_name = 'chatgpt_api_key'";
        $api_key_setting = fetchOne($sql, []);
        $api_key = $api_key_setting ? $api_key_setting['setting_value'] : '';

        if (empty($api_key)) {
            return "La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur.";
        }

        // Récupérer les objectifs nutritionnels de l'utilisateur
        $sql = "SELECT daily_calories, protein_ratio, carbs_ratio, fat_ratio FROM user_profiles WHERE user_id = ?";
        $nutrition_goals = fetchOne($sql, [$profile['user_id']]);

        // Calculer les calories et macros maximales pour ce repas selon le type
        $meal_ratios = [
            'petit_dejeuner' => 0.25,  // 25% des calories quotidiennes
            'dejeuner' => 0.35,        // 35% des calories quotidiennes
            'diner' => 0.30,           // 30% des calories quotidiennes
            'collation' => 0.10        // 10% des calories quotidiennes
        ];

        $max_calories = round($nutrition_goals['daily_calories'] * $meal_ratios[$meal_type]);
        $max_protein = round(($max_calories * $nutrition_goals['protein_ratio']) / 4);  // 4 calories par gramme de protéine
        $max_carbs = round(($max_calories * $nutrition_goals['carbs_ratio']) / 4);      // 4 calories par gramme de glucide
        $max_fat = round(($max_calories * $nutrition_goals['fat_ratio']) / 9);          // 9 calories par gramme de lipide

        // Définir les règles spécifiques selon le type de repas
        $meal_rules = [
            'petit_dejeuner' => [
                'description' => "un petit-déjeuner équilibré et énergétique",
                'aliments_typiques' => "céréales, fruits, produits laitiers, œufs, pain complet",
                'conseils' => "Privilégiez les glucides complexes pour l'énergie, les protéines pour la satiété, et les fruits pour les vitamines"
            ],
            'dejeuner' => [
                'description' => "un déjeuner complet et rassasiant",
                'aliments_typiques' => "viandes maigres, poissons, légumes, féculents complets",
                'conseils' => "Incluez une source de protéines, des légumes et des féculents complets"
            ],
            'diner' => [
                'description' => "un dîner léger et digeste",
                'aliments_typiques' => "poissons, légumes, soupes, salades",
                'conseils' => "Privilégiez les protéines légères et les légumes, évitez les féculents en trop grande quantité"
            ],
            'collation' => [
                'description' => "une collation saine et légère",
                'aliments_typiques' => "fruits, yaourt, fruits secs, noix",
                'conseils' => "Choisissez des aliments riches en nutriments mais faibles en calories"
            ]
        ];

        // Construire le prompt
        $prompt = "Tu es un nutritionniste expert. Donne-moi une suggestion de {$meal_rules[$meal_type]['description']} sous forme de **JSON strict**, sans aucun texte en dehors du JSON. Voici le format exact à respecter :

{
  \"nom_du_repas\": \"...\",
  \"ingredients\": [
    {\"quantité\": \"...\", \"nom\": \"...\"},
    ...
  ],
  \"valeurs_nutritionnelles\": {
    \"calories\": ...,
    \"proteines\": ...,
    \"glucides\": ...,
    \"lipides\": ...
  }
}

IMPORTANT : Les valeurs nutritionnelles DOIVENT respecter strictement ces limites :
- Calories maximales : {$max_calories} kcal
- Protéines maximales : {$max_protein} g
- Glucides maximaux : {$max_carbs} g
- Lipides maximaux : {$max_fat} g

Profil de l'utilisateur :
- Poids : {$profile['weight']} kg
- Taille : {$profile['height']} cm
- Âge : {$profile['age']} ans
- Niveau d'activité : {$profile['activity_level']}
- Programme/Objectif : " . ($active_program ? $active_program['name'] : 'Aucun programme actif') . " (" . ($active_program ? $active_program['description'] : '') . ")

Aliments préférés : " . implode(", ", $favorite_foods) . "
Aliments à éviter : " . implode(", ", $blacklisted_foods) . "

RÈGLES STRICTES POUR UN {$meal_rules[$meal_type]['description']} :
1. Les valeurs nutritionnelles DOIVENT être inférieures ou égales aux limites maximales
2. Utilisez des portions raisonnables pour chaque ingrédient
3. Privilégiez les aliments préférés de l'utilisateur
4. Évitez absolument les aliments blacklistés
5. Adaptez les quantités pour respecter les limites nutritionnelles
6. Vérifiez que la somme des calories des ingrédients correspond aux calories totales
7. Assurez-vous que les macros (protéines, glucides, lipides) sont cohérentes avec les calories
8. Utilisez principalement des aliments typiques pour ce type de repas : {$meal_rules[$meal_type]['aliments_typiques']}
9. Suivez ces conseils spécifiques : {$meal_rules[$meal_type]['conseils']}
10. Pour le petit-déjeuner : incluez des glucides complexes et des protéines
11. Pour le déjeuner : incluez une source de protéines, des légumes et des féculents
12. Pour le dîner : privilégiez les protéines légères et les légumes
13. Pour la collation : choisissez des aliments riches en nutriments mais faibles en calories

N'ajoute rien d'autre que ce JSON dans ta réponse. Assure-toi que les valeurs nutritionnelles respectent strictement les limites maximales.";

        // Logger le prompt
        error_log("=== Prompt envoyé à l'API pour generateMealSuggestion ===");
        error_log($prompt);
        error_log("=== Fin du prompt ===");

        // Appeler l'API ChatGPT
        $response = callChatGPTAPI($prompt, $api_key);
        
        if (empty($response)) {
            return "Une erreur s'est produite lors de la génération de la suggestion.";
        }

        // Logger la réponse
        error_log("=== Réponse reçue de l'API pour generateMealSuggestion ===");
        error_log($response);
        error_log("=== Fin de la réponse ===");

        // Vérifier que la réponse est un JSON valide
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erreur de parsing JSON dans generateMealSuggestion: " . json_last_error_msg());
            error_log("Réponse brute: " . $response);
            return "Une erreur s'est produite lors du traitement de la suggestion.";
        }

        // Vérifier que tous les champs requis sont présents
        if (!isset($data['nom_du_repas']) || !isset($data['ingredients']) || 
            !isset($data['valeurs_nutritionnelles'])) {
            error_log("Données JSON incomplètes dans generateMealSuggestion");
            return "La suggestion générée est incomplète.";
        }

        // Vérifier que les valeurs nutritionnelles respectent les limites
        $nutrition = $data['valeurs_nutritionnelles'];
        if ($nutrition['calories'] > $max_calories || 
            $nutrition['proteines'] > $max_protein || 
            $nutrition['glucides'] > $max_carbs || 
            $nutrition['lipides'] > $max_fat) {
            error_log("Les valeurs nutritionnelles dépassent les limites maximales");
            error_log("Calories: {$nutrition['calories']} > {$max_calories}");
            error_log("Protéines: {$nutrition['proteines']} > {$max_protein}");
            error_log("Glucides: {$nutrition['glucides']} > {$max_carbs}");
            error_log("Lipides: {$nutrition['lipides']} > {$max_fat}");
            return "La suggestion générée dépasse les limites nutritionnelles autorisées.";
        }

        // Formater la suggestion pour l'affichage
        $suggestion = "SUGGESTION DE " . strtoupper(str_replace('_', ' ', $meal_type)) . " : {$data['nom_du_repas']}\n\n";
        
        // Ajouter les informations utilisées pour la génération
        $suggestion .= "Informations utilisées pour la génération :\n";
        $suggestion .= "Profil :\n";
        $suggestion .= "Genre : " . ucfirst($profile['gender']) . "\n";
        $suggestion .= "Âge : {$profile['age']} ans\n";
        $suggestion .= "Taille : {$profile['height']} cm\n";
        $suggestion .= "Niveau d'activité : " . ucfirst($profile['activity_level']) . "\n";
        $suggestion .= "Poids actuel : {$profile['weight']} kg\n";
        $suggestion .= "Objectif : " . ($current_goal ? $current_goal['name'] : "Non défini") . "\n";
        $suggestion .= "Programme : " . ($active_program ? $active_program['name'] : "Aucun") . "\n";
        $suggestion .= "Préférences alimentaires :\n";
        $suggestion .= "Aliments préférés : " . count($favorite_foods) . " configurés\n";
        $suggestion .= "Aliments à éviter : " . count($blacklisted_foods) . " configurés\n";
        $suggestion .= "Limites nutritionnelles pour " . ucfirst(str_replace('_', ' ', $meal_type)) . " :\n";
        $suggestion .= "Calories maximales : {$max_calories} kcal\n";
        $suggestion .= "Protéines maximales : {$max_protein} g\n";
        $suggestion .= "Glucides maximaux : {$max_carbs} g\n";
        $suggestion .= "Lipides maximaux : {$max_fat} g\n\n";
        
        $suggestion .= "Valeurs nutritionnelles :\n";
        $suggestion .= "- Calories : {$data['valeurs_nutritionnelles']['calories']} kcal\n";
        $suggestion .= "- Protéines : {$data['valeurs_nutritionnelles']['proteines']} g\n";
        $suggestion .= "- Glucides : {$data['valeurs_nutritionnelles']['glucides']} g\n";
        $suggestion .= "- Lipides : {$data['valeurs_nutritionnelles']['lipides']} g\n\n";
        
        $suggestion .= "Ingrédients :\n";
        foreach ($data['ingredients'] as $ingredient) {
            $suggestion .= "- {$ingredient['quantité']} {$ingredient['nom']}\n";
        }

        return $suggestion;
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de la suggestion de repas : " . $e->getMessage());
        return "Une erreur s'est produite lors de la génération de la suggestion.";
    }
}

/**
 * Génère une suggestion d'exercice personnalisée
 * 
 * @param array $profile Profil de l'utilisateur
 * @param array $latest_weight Dernier poids enregistré
 * @param array $current_goal Objectif actuel
 * @param array $active_program Programme actif
 * @return string Suggestion d'exercice
 */
function generateExerciseSuggestion($profile, $latest_weight, $current_goal, $active_program) {
    try {
        // Récupérer la clé API ChatGPT
        $sql = "SELECT setting_value FROM settings WHERE setting_name = 'chatgpt_api_key'";
        $api_key_setting = fetchOne($sql, []);
        $api_key = $api_key_setting ? $api_key_setting['setting_value'] : '';

        if (empty($api_key)) {
            return "La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur.";
        }

        // Construire le prompt
        $prompt = "Tu es un coach sportif expert. Donne-moi une suggestion d'exercices sous forme de **JSON strict**, sans aucun texte en dehors du JSON. Voici le format exact à respecter :

{
  \"nom_du_programme\": \"...\",
  \"exercices\": [
    {
      \"nom\": \"...\",
      \"duree\": \"...\",
      \"intensite\": \"...\",
      \"calories_brûlées\": ...,
      \"description\": \"...\"
    },
    ...
  ]
}

Profil de l'utilisateur :
- Poids : {$profile['weight']} kg
- Taille : {$profile['height']} cm
- Âge : {$profile['age']} ans
- Niveau d'activité : {$profile['activity_level']}
- Programme/Objectif : " . ($active_program ? $active_program['name'] : 'Aucun programme actif') . " (" . ($active_program ? $active_program['description'] : '') . ")

N'ajoute rien d'autre que ce JSON dans ta réponse. Ne pas inclure de suggestions de repas ou d'informations nutritionnelles.";

        // Logger le prompt
        error_log("=== Prompt envoyé à l'API pour generateExerciseSuggestion ===");
        error_log($prompt);
        error_log("=== Fin du prompt ===");

        // Appeler l'API ChatGPT
        $response = callChatGPTAPI($prompt, $api_key);
        
        if (empty($response)) {
            return "Une erreur s'est produite lors de la génération de la suggestion.";
        }

        // Logger la réponse
        error_log("=== Réponse reçue de l'API pour generateExerciseSuggestion ===");
        error_log($response);
        error_log("=== Fin de la réponse ===");

        // Vérifier que la réponse est un JSON valide
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erreur de parsing JSON dans generateExerciseSuggestion: " . json_last_error_msg());
            error_log("Réponse brute: " . $response);
            return "Une erreur s'est produite lors du traitement de la suggestion.";
        }

        // Vérifier que tous les champs requis sont présents
        if (!isset($data['nom_du_programme']) || !isset($data['exercices'])) {
            error_log("Données JSON incomplètes dans generateExerciseSuggestion");
            return "La suggestion générée est incomplète.";
        }

        // Vérifier que la réponse ne contient pas de suggestions de repas
        $response_lower = strtolower($response);
        $food_keywords = [
            'repas', 'aliment', 'nutrition', 'protéine', 'glucide', 'lipide', 'ingrédient',
            'menu', 'cuisine', 'recette', 'manger', 'boire', 'déjeuner', 'dîner', 'collation'
        ];
        
        foreach ($food_keywords as $keyword) {
            if (strpos($response_lower, $keyword) !== false) {
                error_log("La réponse contient des suggestions de repas non autorisées (mot-clé trouvé: $keyword)");
                return "La suggestion générée contient des informations non autorisées.";
            }
        }

        // Formater la suggestion pour l'affichage
        $suggestion = "PROGRAMME D'EXERCICES : {$data['nom_du_programme']}\n\n";
        
        $suggestion .= "Exercices proposés :\n";
        foreach ($data['exercices'] as $exercice) {
            if (!isset($exercice['nom']) || !isset($exercice['duree']) || 
                !isset($exercice['intensite']) || !isset($exercice['calories_brûlées']) || 
                !isset($exercice['description'])) {
                continue; // Ignorer les exercices incomplets
            }
            
            $suggestion .= "\n{$exercice['nom']} :\n";
            $suggestion .= "- Durée : {$exercice['duree']}\n";
            $suggestion .= "- Intensité : {$exercice['intensite']}\n";
            $suggestion .= "- Calories brûlées : {$exercice['calories_brûlées']} kcal\n";
            $suggestion .= "- Description : {$exercice['description']}\n";
        }

        return $suggestion;
    } catch (Exception $e) {
        error_log("Erreur lors de la génération de la suggestion d'exercice : " . $e->getMessage());
        return "Une erreur s'est produite lors de la génération de la suggestion.";
    }
}

/**
 * Construit l'URL actuelle avec le paramètre de langue
 */
function getCurrentUrlWithLang($lang) {
    $currentUrl = $_SERVER['REQUEST_URI'];
    
    // Supprimer le paramètre lang existant s'il y en a un
    $currentUrl = preg_replace('/[?&]lang=[^&]*/', '', $currentUrl);
    
    // Ajouter le nouveau paramètre lang
    $separator = (strpos($currentUrl, '?') !== false) ? '&' : '?';
    return $currentUrl . $separator . 'lang=' . $lang;
}