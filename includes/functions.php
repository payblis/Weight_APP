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
        return $GLOBALS['pdo']->lastInsertId();
    } catch (PDOException $e) {
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
 * @return bool
 */
function createCommunityPost($user_id, $post_type, $content, $reference_id = null, $reference_type = null, $visibility = 'public', $group_id = null) {
    // Vérifier si le post est pour un groupe et si l'utilisateur en est membre
    if ($visibility === 'group' && $group_id) {
        if (!isGroupMember($group_id, $user_id)) {
            return false;
        }
    }
    
    $sql = "INSERT INTO community_posts (user_id, post_type, content, reference_id, reference_type, visibility, group_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    return insert($sql, [$user_id, $post_type, $content, $reference_id, $reference_type, $visibility, $group_id]);
}

/**
 * Ajoute ou supprime un like sur un post
 * @param int $post_id ID du post
 * @param int $user_id ID de l'utilisateur
 * @return array
 */
function togglePostLike($post_id, $user_id) {
    global $db;
    
    try {
        // Vérifier si l'utilisateur a déjà liké le post
        $sql = "SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$post_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            // Supprimer le like
            $sql = "DELETE FROM post_likes WHERE post_id = ? AND user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$post_id, $user_id]);
            
            // Mettre à jour le compteur de likes
            $sql = "UPDATE community_posts SET likes_count = likes_count - 1 WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$post_id]);
            
            return ['success' => true, 'likes_count' => getPostLikesCount($post_id), 'action' => 'unliked'];
        } else {
            // Ajouter le like
            $sql = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$post_id, $user_id]);
            
            // Mettre à jour le compteur de likes
            $sql = "UPDATE community_posts SET likes_count = likes_count + 1 WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$post_id]);
            
            return ['success' => true, 'likes_count' => getPostLikesCount($post_id), 'action' => 'liked'];
        }
    } catch (PDOException $e) {
        error_log("Erreur lors du toggle du like : " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
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
    global $db;
    
    try {
        // Ajouter le commentaire
        $sql = "INSERT INTO post_comments (post_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$post_id, $user_id, $content]);
        
        // Mettre à jour le compteur de commentaires
        $sql = "UPDATE community_posts SET comments_count = comments_count + 1 WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$post_id]);
        
        return ['success' => true, 'comments_count' => getPostCommentsCount($post_id)];
    } catch (PDOException $e) {
        error_log("Erreur lors de l'ajout du commentaire : " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Ajoute ou supprime un abonnement entre utilisateurs
 * @param int $following_id ID de l'utilisateur à suivre
 * @param int $follower_id ID de l'utilisateur qui suit
 * @return array
 */
function toggleFollow($following_id, $follower_id) {
    global $db;
    
    try {
        // Vérifier si l'utilisateur suit déjà
        $sql = "SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$follower_id, $following_id]);
        
        if ($stmt->rowCount() > 0) {
            // Supprimer l'abonnement
            $sql = "DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$follower_id, $following_id]);
            return ['success' => true, 'action' => 'unfollowed'];
        } else {
            // Ajouter l'abonnement
            $sql = "INSERT INTO user_follows (follower_id, following_id) VALUES (?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$follower_id, $following_id]);
            return ['success' => true, 'action' => 'followed'];
        }
    } catch (PDOException $e) {
        error_log("Erreur lors du toggle de l'abonnement : " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
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
    $sql = "INSERT INTO community_groups (name, description, created_by) VALUES (?, ?, ?)";
    $result = insert($sql, [$name, $description, $user_id]);
    
    if ($result) {
        $group_id = getLastInsertId();
        // Ajouter le créateur comme admin du groupe
        $sql = "INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')";
        insert($sql, [$group_id, $user_id]);
        return $group_id;
    }
    return false;
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
