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
    
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Récupérer le profil de l'utilisateur
        $sql = "SELECT * FROM user_profiles WHERE user_id = ?";
        error_log("Récupération du profil utilisateur");
        $profile = fetchOne($sql, [$user_id]);
        
        if (!$profile) {
            error_log("Profil utilisateur non trouvé pour le recalcul des calories");
            $pdo->rollBack();
            return false;
        }
        
        // Vérifier que toutes les valeurs nécessaires sont présentes
        if (!isset($profile['height']) || !isset($profile['birth_date']) || !isset($profile['gender']) || !isset($profile['activity_level'])) {
            error_log("Données du profil incomplètes pour le calcul des calories");
            $pdo->rollBack();
            return false;
        }
        
        // Vérifier et mettre à jour le poids si nécessaire
        $current_weight = ensureProfileWeight($user_id);
        if ($current_weight === null) {
            error_log("Impossible de récupérer le poids actuel pour le recalcul des calories");
            $pdo->rollBack();
            return false;
        }
        
        // Calculer le BMR
        $bmr = calculateBMR($current_weight, $profile['height'], $profile['birth_date'], $profile['gender']);
        error_log("BMR calculé : " . $bmr);
        
        // Vérifier que le BMR est positif
        if ($bmr <= 0) {
            error_log("BMR négatif ou nul : " . $bmr);
            $pdo->rollBack();
            return false;
        }
        
        // Calculer le TDEE
        $tdee = calculateTDEE($bmr, $profile['activity_level']);
        error_log("TDEE calculé : " . $tdee);
        
        // Vérifier que le TDEE est positif
        if ($tdee <= 0) {
            error_log("TDEE négatif ou nul : " . $tdee);
            $pdo->rollBack();
            return false;
        }
        
        // Récupérer l'objectif actuel
        $sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
        error_log("Récupération de l'objectif actuel");
        $goal = fetchOne($sql, [$user_id]);
        error_log("Résultat de la requête objectif : " . ($goal ? "Objectif trouvé" : "Aucun objectif trouvé"));
        
        // Vérifier si l'objectif existe toujours
        if ($goal) {
            $sql = "SELECT COUNT(*) as count FROM goals WHERE id = ? AND user_id = ?";
            $check = fetchOne($sql, [$goal['id'], $user_id]);
            if (!$check || $check['count'] == 0) {
                error_log("L'objectif n'existe plus, utilisation du TDEE");
                $goal = null;
            }
        }
        
        if ($goal) {
            // Calculer l'objectif calorique en fonction de l'objectif
            $calorie_goal = calculateCalorieGoal($tdee, $goal['goal_type'], $goal['intensity'], $current_weight, $goal['target_weight'], $goal['target_date']);
            error_log("Objectif calorique calculé : " . $calorie_goal);
            
            // Calculer l'ajustement quotidien
            $daily_adjustment = $calorie_goal - $tdee;
            
            // Avertir si l'ajustement est important (plus de 500 calories)
            if (abs($daily_adjustment) > 500) {
                error_log("ATTENTION : Ajustement calorique important détecté : " . $daily_adjustment);
            }
            
            // Mettre à jour les calories dans le profil
            $sql = "UPDATE user_profiles SET daily_calories = ? WHERE user_id = ?";
            if (!execute($sql, [$calorie_goal, $user_id])) {
                error_log("Échec de la mise à jour des calories");
                $pdo->rollBack();
                return false;
            }
            error_log("Calories mises à jour avec succès : " . $calorie_goal);
        } else {
            // Si pas d'objectif actif, utiliser le TDEE comme objectif
            error_log("Pas d'objectif actif, utilisation du TDEE comme objectif : " . $tdee);
            $sql = "UPDATE user_profiles SET daily_calories = ? WHERE user_id = ?";
            if (!execute($sql, [$tdee, $user_id])) {
                error_log("Échec de la mise à jour des calories avec TDEE");
                $pdo->rollBack();
                return false;
            }
            error_log("Calories mises à jour avec le TDEE : " . $tdee);
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors du recalcul des calories : " . $e->getMessage());
        $pdo->rollBack();
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
