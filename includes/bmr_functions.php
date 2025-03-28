<?php
// Fonctions améliorées pour le calcul du métabolisme de base (BMR) et l'ajustement des objectifs caloriques

/**
 * Calcule le métabolisme de base (BMR) en utilisant différentes formules
 * 
 * @param float $weight Poids en kg
 * @param float $height Taille en cm
 * @param int $age Âge en années
 * @param string $gender Genre ('homme' ou 'femme')
 * @param string $formula Formule à utiliser ('mifflin_st_jeor', 'harris_benedict', 'katch_mcardle')
 * @param float $body_fat_percentage Pourcentage de graisse corporelle (optionnel, pour Katch-McArdle)
 * @return float BMR en calories
 */
function calculateBasalMetabolicRate($weight, $height, $age, $gender, $formula = 'mifflin_st_jeor', $body_fat_percentage = null) {
    // Conversion et validation des données
    $weight = floatval($weight);
    $height = floatval($height);
    $age = intval($age);
    $gender = strtolower($gender);
    
    // Vérification des valeurs
    if ($weight <= 0 || $height <= 0 || $age <= 0) {
        return 0;
    }
    
    // Calcul du BMR selon la formule choisie
    switch ($formula) {
        case 'harris_benedict':
            // Formule de Harris-Benedict (1919)
            if ($gender === 'homme' || $gender === 'male') {
                return 13.7516 * $weight + 5.0033 * $height - 6.7550 * $age + 66.4730;
            } else {
                return 9.5634 * $weight + 1.8496 * $height - 4.6756 * $age + 655.0955;
            }
            
        case 'katch_mcardle':
            // Formule de Katch-McArdle (nécessite le % de graisse corporelle)
            if ($body_fat_percentage !== null) {
                // Calcul de la masse maigre (Lean Body Mass)
                $lbm = $weight * (1 - ($body_fat_percentage / 100));
                // Formule de Katch-McArdle
                return 370 + (21.6 * $lbm);
            } else {
                // Si le % de graisse n'est pas fourni, utiliser Mifflin-St Jeor par défaut
                goto mifflin_st_jeor;
            }
            
        case 'mifflin_st_jeor':
        default:
            // Formule de Mifflin-St Jeor (1990) - considérée comme la plus précise
            mifflin_st_jeor:
            if ($gender === 'homme' || $gender === 'male') {
                return (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
            } else {
                return (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
            }
    }
}

/**
 * Calcule la dépense énergétique totale quotidienne (TDEE) en fonction du niveau d'activité
 * 
 * @param float $bmr Métabolisme de base
 * @param string $activity_level Niveau d'activité
 * @return float TDEE en calories
 */
function calculateTotalDailyEnergyExpenditure($bmr, $activity_level) {
    // Facteurs de multiplication selon le niveau d'activité
    $activity_multipliers = [
        'sedentaire' => 1.2,      // Sédentaire (peu ou pas d'exercice)
        'leger' => 1.375,         // Légèrement actif (exercice léger 1-3 jours/semaine)
        'modere' => 1.55,         // Modérément actif (exercice modéré 3-5 jours/semaine)
        'actif' => 1.725,         // Très actif (exercice intense 6-7 jours/semaine)
        'tres_actif' => 1.9,      // Extrêmement actif (exercice très intense, travail physique)
        
        // Équivalents en anglais pour la compatibilité
        'sedentary' => 1.2,
        'light' => 1.375,
        'moderate' => 1.55,
        'active' => 1.725,
        'very_active' => 1.9
    ];
    
    // Récupérer le multiplicateur ou utiliser la valeur par défaut (sédentaire)
    $multiplier = $activity_multipliers[$activity_level] ?? 1.2;
    
    // Calculer et retourner la dépense énergétique totale
    return $bmr * $multiplier;
}

/**
 * Calcule l'objectif calorique en fonction de l'objectif de poids et du programme
 * 
 * @param float $tdee Dépense énergétique totale quotidienne
 * @param string $goal_type Type d'objectif ('perte', 'maintien', 'prise')
 * @param float $intensity Intensité (0.5 = modérée, 1 = standard, 1.5 = agressive)
 * @param int $program_id ID du programme (optionnel)
 * @return float Objectif calorique
 */
function calculateCalorieGoal($tdee, $goal_type, $intensity = 1, $program_id = null) {
    // Ajustement de base selon l'objectif
    $base_adjustment = 0;
    
    switch ($goal_type) {
        case 'perte':
        case 'perte_poids':
        case 'lose_weight':
            // Déficit calorique (500 calories = environ 0.5kg par semaine)
            $base_adjustment = -500 * $intensity;
            break;
            
        case 'perte_rapide':
        case 'seche':
        case 'lose_weight_fast':
            // Déficit calorique important
            $base_adjustment = -750 * $intensity;
            break;
            
        case 'prise':
        case 'prise_poids':
        case 'prise_muscle':
        case 'gain_weight':
        case 'gain_muscle':
            // Surplus calorique
            $base_adjustment = 500 * $intensity;
            break;
            
        case 'maintien':
        case 'equilibre':
        case 'maintain':
        default:
            // Maintien du poids
            $base_adjustment = 0;
            break;
    }
    
    // Ajustement supplémentaire si un programme est spécifié
    $program_adjustment = 0;
    
    if ($program_id) {
        try {
            // Récupérer l'ajustement calorique du programme
            $sql = "SELECT calorie_adjustment FROM programs WHERE id = ?";
            $program = fetchOne($sql, [$program_id]);
            
            if ($program && isset($program['calorie_adjustment'])) {
                $program_adjustment = intval($program['calorie_adjustment']);
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération de l'ajustement calorique du programme: " . $e->getMessage());
        }
    }
    
    // Calculer l'objectif calorique final
    $calorie_goal = $tdee + $base_adjustment + $program_adjustment;
    
    // S'assurer que l'objectif calorique ne descend pas en dessous du minimum recommandé
    $minimum_calories = 1200; // Minimum recommandé pour la plupart des adultes
    
    return max($minimum_calories, round($calorie_goal));
}

/**
 * Calcule la répartition des macronutriments en fonction de l'objectif et du programme
 * 
 * @param float $calorie_goal Objectif calorique
 * @param string $goal_type Type d'objectif
 * @param float $weight Poids en kg
 * @param int $program_id ID du programme (optionnel)
 * @return array Objectifs de macronutriments (protéines, lipides, glucides)
 */
function calculateMacronutrientGoals($calorie_goal, $goal_type, $weight, $program_id = null) {
    // Ratios par défaut selon l'objectif
    $protein_ratio = 0.30;
    $fat_ratio = 0.30;
    $carbs_ratio = 0.40;
    
    // Ajuster les ratios selon l'objectif
    switch ($goal_type) {
        case 'perte':
        case 'perte_poids':
        case 'lose_weight':
            $protein_ratio = 0.35;
            $fat_ratio = 0.30;
            $carbs_ratio = 0.35;
            break;
            
        case 'perte_rapide':
        case 'seche':
        case 'lose_weight_fast':
            $protein_ratio = 0.40;
            $fat_ratio = 0.35;
            $carbs_ratio = 0.25;
            break;
            
        case 'prise_muscle':
        case 'gain_muscle':
            $protein_ratio = 0.35;
            $fat_ratio = 0.25;
            $carbs_ratio = 0.40;
            break;
            
        case 'prise_poids':
        case 'gain_weight':
            $protein_ratio = 0.25;
            $fat_ratio = 0.25;
            $carbs_ratio = 0.50;
            break;
    }
    
    // Utiliser les ratios du programme si spécifié
    if ($program_id) {
        try {
            $sql = "SELECT protein_ratio, carbs_ratio, fat_ratio FROM programs WHERE id = ?";
            $program = fetchOne($sql, [$program_id]);
            
            if ($program) {
                if (isset($program['protein_ratio']) && $program['protein_ratio'] > 0) {
                    $protein_ratio = floatval($program['protein_ratio']);
                }
                
                if (isset($program['fat_ratio']) && $program['fat_ratio'] > 0) {
                    $fat_ratio = floatval($program['fat_ratio']);
                }
                
                if (isset($program['carbs_ratio']) && $program['carbs_ratio'] > 0) {
                    $carbs_ratio = floatval($program['carbs_ratio']);
                }
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des ratios de macronutriments du programme: " . $e->getMessage());
        }
    }
    
    // Calculer les grammes de chaque macronutriment
    // 1g de protéine = 4 calories
    // 1g de glucides = 4 calories
    // 1g de lipides = 9 calories
    
    // Pour les protéines, on peut aussi utiliser un calcul basé sur le poids corporel
    // Recommandation: 1.6-2.2g/kg pour perte de poids, 1.6-1.8g/kg pour maintien, 1.8-2.2g/kg pour prise de masse
    $protein_factor = 0;
    
    switch ($goal_type) {
        case 'perte_rapide':
        case 'seche':
        case 'lose_weight_fast':
            $protein_factor = 2.2;
            break;
            
        case 'perte':
        case 'perte_poids':
        case 'lose_weight':
            $protein_factor = 2.0;
            break;
            
        case 'prise_muscle':
        case 'gain_muscle':
            $protein_factor = 2.0;
            break;
            
        case 'prise_poids':
        case 'gain_weight':
            $protein_factor = 1.8;
            break;
            
        case 'maintien':
        case 'equilibre':
        case 'maintain':
        default:
            $protein_factor = 1.6;
            break;
    }
    
    // Calculer les protéines basées sur le poids corporel
    $protein_by_weight = $weight * $protein_factor;
    
    // Calculer les protéines basées sur le ratio calorique
    $protein_by_ratio = ($calorie_goal * $protein_ratio) / 4;
    
    // Utiliser la valeur la plus élevée pour les protéines
    $protein_g = max($protein_by_weight, $protein_by_ratio);
    
    // Calculer les calories restantes après les protéines
    $remaining_calories = $calorie_goal - ($protein_g * 4);
    
    // Ajuster les ratios de lipides et glucides pour les calories restantes
    $adjusted_fat_ratio = $fat_ratio / ($fat_ratio + $carbs_ratio);
    $adjusted_carbs_ratio = $carbs_ratio / ($fat_ratio + $carbs_ratio);
    
    // Calculer les grammes de lipides et glucides
    $fat_g = ($remaining_calories * $adjusted_fat_ratio) / 9;
    $carbs_g = ($remaining_calories * $adjusted_carbs_ratio) / 4;
    
    return [
        'protein_g' => round($protein_g),
        'fat_g' => round($fat_g),
        'carbs_g' => round($carbs_g),
        'protein_pct' => round($protein_ratio * 100),
        'fat_pct' => round($fat_ratio * 100),
        'carbs_pct' => round($carbs_ratio * 100)
    ];
}

/**
 * Calcule et met à jour les besoins caloriques d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $formula Formule à utiliser (optionnel)
 * @param int $program_id ID du programme (optionnel)
 * @return array|false Besoins caloriques calculés ou false en cas d'erreur
 */
function updateUserNutritionalNeeds($user_id, $formula = null, $program_id = null) {
    try {
        // Récupérer les informations de l'utilisateur
        $sql = "SELECT u.id, up.gender, up.birth_date, up.height, up.activity_level, up.preferred_bmr_formula,
                (SELECT weight FROM weight_logs WHERE user_id = u.id ORDER BY log_date DESC LIMIT 1) as current_weight,
                (SELECT goal_type FROM goals WHERE user_id = u.id AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1) as goal_type
                FROM users u
                JOIN user_profiles up ON u.id = up.user_id
                WHERE u.id = ?";
        $user = fetchOne($sql, [$user_id]);
        
        if (!$user || !isset($user['current_weight']) || !$user['current_weight']) {
            return false; // Impossible de calculer sans poids
        }
        
        // Calculer l'âge à partir de la date de naissance
        $birth_date = new DateTime($user['birth_date']);
        $today = new DateTime();
        $age = $birth_date->diff($today)->y;
        
        // Utiliser la formule spécifiée ou celle préférée par l'utilisateur
        $bmr_formula = $formula ?? $user['preferred_bmr_formula'] ?? 'mifflin_st_jeor';
        
        // Calculer le BMR
        $bmr = calculateBasalMetabolicRate(
            $user['current_weight'],
            $user['height'],
            $age,
            $user['gender'],
            $bmr_formula
        );
        
        // Calculer la dépense énergétique totale
        $tdee = calculateTotalDailyEnergyExpenditure($bmr, $user['activity_level']);
        
        // Déterminer l'objectif de l'utilisateur
        $goal_type = $user['goal_type'] ?? 'maintien';
        
        // Calculer l'objectif calorique
        $calorie_goal = calculateCalorieGoal($tdee, $goal_type, 1, $program_id);
        
        // Calculer les objectifs de macronutriments
        $macros = calculateMacronutrientGoals($calorie_goal, $goal_type, $user['current_weight'], $program_id);
        
        // Mettre à jour les besoins caloriques dans la base de données
        $sql = "SELECT id FROM user_calorie_needs WHERE user_id = ?";
        $existing = fetchOne($sql, [$user_id]);
        
        if ($existing) {
            $sql = "UPDATE user_calorie_needs 
                    SET bmr = ?, total_calories = ?, goal_calories = ?, 
                        protein_g = ?, carbs_g = ?, fat_g = ?, updated_at = NOW() 
                    WHERE user_id = ?";
            $result = update($sql, [
                round($bmr), 
                round($tdee), 
                round($calorie_goal), 
                $macros['protein_g'], 
                $macros['carbs_g'], 
                $macros['fat_g'], 
                $user_id
            ]);
        } else {
            $sql = "INSERT INTO user_calorie_needs 
                    (user_id, bmr, total_calories, goal_calories, protein_g, carbs_g, fat_g, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $result = insert($sql, [
                $user_id, 
                round($bmr), 
                round($tdee), 
                round($calorie_goal), 
                $macros['protein_g'], 
                $macros['carbs_g'], 
                $macros['fat_g']
            ]);
        }
        
        // Retourner les besoins calculés
        return [
            'bmr' => round($bmr),
            'tdee' => round($tdee),
            'calorie_goal' => round($calorie_goal),
            'protein_g' => $macros['protein_g'],
            'carbs_g' => $macros['carbs_g'],
            'fat_g' => $macros['fat_g'],
            'protein_pct' => $macros['protein_pct'],
            'carbs_pct' => $macros['carbs_pct'],
            'fat_pct' => $macros['fat_pct']
        ];
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour des besoins nutritionnels: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcule et met à jour le bilan calorique quotidien d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $date Date au format Y-m-d (optionnel, par défaut aujourd'hui)
 * @return array|false Bilan calorique ou false en cas d'erreur
 */
function updateDailyCalorieBalance($user_id, $date = null) {
    try {
        // Si aucune date n'est spécifiée, utiliser la date du jour
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        // Récupérer les calories consommées (repas)
        $sql = "SELECT COALESCE(SUM(
                    CASE 
                        WHEN fl.food_id IS NOT NULL THEN (f.calories * fl.quantity / 100)
                        WHEN fl.custom_calories IS NOT NULL THEN fl.custom_calories
                        ELSE 0
                    END
                ), 0) as total_consumed 
                FROM food_logs fl
                LEFT JOIN foods f ON fl.food_id = f.id
                WHERE fl.user_id = ? AND DATE(fl.log_date) = ?";
        $consumed = fetchOne($sql, [$user_id, $date]);
        $calories_consumed = $consumed ? intval($consumed['total_consumed']) : 0;
        
        // Récupérer les calories brûlées (exercices)
        $sql = "SELECT COALESCE(SUM(calories_burned), 0) as total_burned 
                FROM exercise_logs 
                WHERE user_id = ? AND DATE(log_date) = ?";
        $burned = fetchOne($sql, [$user_id, $date]);
        $calories_burned = $burned ? intval($burned['total_burned']) : 0;
        
        // Récupérer le métabolisme de base et les besoins caloriques de l'utilisateur
        $sql = "SELECT bmr, total_calories, goal_calories 
                FROM user_calorie_needs 
                WHERE user_id = ?";
        $needs = fetchOne($sql, [$user_id]);
        
        if (!$needs) {
            // Si les besoins n'existent pas, les calculer
            $calculated_needs = updateUserNutritionalNeeds($user_id);
            
            if ($calculated_needs) {
                $bmr = $calculated_needs['bmr'];
                $total_calories_burned = $calculated_needs['tdee'];
                $goal_calories = $calculated_needs['calorie_goal'];
            } else {
                // Valeurs par défaut si le calcul échoue
                $bmr = 0;
                $total_calories_burned = 0;
                $goal_calories = 0;
            }
        } else {
            $bmr = intval($needs['bmr']);
            $total_calories_burned = intval($needs['total_calories']);
            $goal_calories = intval($needs['goal_calories']);
        }
        
        // Calculer le bilan calorique
        $total_burned = $total_calories_burned + $calories_burned;
        $balance = $calories_consumed - $total_burned;
        $goal_difference = $calories_consumed - $goal_calories;
        
        // Enregistrer le bilan calorique dans l'historique
        $sql = "INSERT INTO calorie_balance_history 
                (user_id, log_date, calories_consumed, calories_burned, bmr_calories, total_burned, balance, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                calories_consumed = ?, calories_burned = ?, bmr_calories = ?, total_burned = ?, balance = ?, updated_at = NOW()";
        
        $result = insert($sql, [
            $user_id, $date, $calories_consumed, $calories_burned, $bmr, $total_burned, $balance,
            $calories_consumed, $calories_burned, $bmr, $total_burned, $balance
        ]);
        
        // Retourner le bilan calorique
        return [
            'date' => $date,
            'calories_consumed' => $calories_consumed,
            'calories_burned_exercise' => $calories_burned,
            'bmr_calories' => $bmr,
            'total_calories_burned' => $total_calories_burned,
            'total_burned' => $total_burned,
            'balance' => $balance,
            'goal_calories' => $goal_calories,
            'goal_difference' => $goal_difference
        ];
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour du bilan calorique: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère l'historique du bilan calorique d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $days Nombre de jours d'historique à récupérer
 * @return array Historique du bilan calorique
 */
function getCalorieBalanceHistory($user_id, $days = 30) {
    try {
        $sql = "SELECT cbh.*, DATE_FORMAT(cbh.log_date, '%d/%m/%Y') as formatted_date,
                (SELECT goal_calories FROM user_calorie_needs WHERE user_id = cbh.user_id) as goal_calories
                FROM calorie_balance_history cbh
                WHERE cbh.user_id = ? 
                AND cbh.log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY cbh.log_date DESC";
        
        return fetchAll($sql, [$user_id, $days]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de l'historique du bilan calorique: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les niveaux d'activité disponibles
 * 
 * @return array Liste des niveaux d'activité
 */
function getActivityLevels() {
    try {
        $sql = "SELECT * FROM activity_levels ORDER BY multiplier";
        return fetchAll($sql, []);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des niveaux d'activité: " . $e->getMessage());
        
        // Retourner les valeurs par défaut en cas d'erreur
        return [
            ['code' => 'sedentaire', 'name' => 'Sédentaire', 'multiplier' => 1.2, 'description' => 'Peu ou pas d\'exercice, travail de bureau'],
            ['code' => 'leger', 'name' => 'Légèrement actif', 'multiplier' => 1.375, 'description' => 'Exercice léger 1-3 jours par semaine'],
            ['code' => 'modere', 'name' => 'Modérément actif', 'multiplier' => 1.55, 'description' => 'Exercice modéré 3-5 jours par semaine'],
            ['code' => 'actif', 'name' => 'Très actif', 'multiplier' => 1.725, 'description' => 'Exercice intense 6-7 jours par semaine'],
            ['code' => 'tres_actif', 'name' => 'Extrêmement actif', 'multiplier' => 1.9, 'description' => 'Exercice très intense, travail physique ou entraînement 2x par jour']
        ];
    }
}

/**
 * Récupère les formules de calcul du BMR disponibles
 * 
 * @return array Liste des formules de calcul du BMR
 */
function getBMRFormulas() {
    try {
        $sql = "SELECT * FROM bmr_formulas WHERE is_active = 1 ORDER BY id";
        return fetchAll($sql, []);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des formules de calcul du BMR: " . $e->getMessage());
        
        // Retourner les valeurs par défaut en cas d'erreur
        return [
            ['name' => 'mifflin_st_jeor', 'description' => 'Formule de Mifflin-St Jeor (1990): considérée comme la plus précise pour la plupart des individus'],
            ['name' => 'harris_benedict', 'description' => 'Formule de Harris-Benedict (1919): l\'une des plus anciennes et des plus utilisées'],
            ['name' => 'katch_mcardle', 'description' => 'Formule de Katch-McArdle: prend en compte la masse maigre pour plus de précision chez les personnes musclées']
        ];
    }
}
