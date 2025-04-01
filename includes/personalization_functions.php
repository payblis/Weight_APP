<?php
/**
 * Fonctions pour la personnalisation avancée
 */

/**
 * Enregistre ou met à jour les préférences alimentaires d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $preference_type Type de préférence (like, dislike, allergy, intolerance)
 * @param int $food_id ID de l'aliment (optionnel)
 * @param string $food_category Catégorie d'aliment (optionnel)
 * @param string $notes Notes supplémentaires (optionnel)
 * @return int|false ID de la préférence créée ou mise à jour, ou false en cas d'erreur
 */
function saveUserFoodPreference($user_id, $preference_type, $food_id = null, $food_category = null, $notes = '') {
    try {
        // Vérifier si la préférence existe déjà
        $params = [$user_id, $preference_type];
        $where_clauses = [];
        
        if ($food_id) {
            $where_clauses[] = "food_id = ?";
            $params[] = $food_id;
        } else {
            $where_clauses[] = "food_id IS NULL";
        }
        
        if ($food_category) {
            $where_clauses[] = "food_category = ?";
            $params[] = $food_category;
        } else {
            $where_clauses[] = "food_category IS NULL";
        }
        
        $where_sql = implode(" AND ", $where_clauses);
        
        $sql = "SELECT id FROM user_food_preferences 
                WHERE user_id = ? AND preference_type = ? AND $where_sql";
        
        $existing = fetchOne($sql, $params);
        
        if ($existing) {
            // Mettre à jour la préférence existante
            $sql = "UPDATE user_food_preferences 
                    SET notes = ?, updated_at = NOW() 
                    WHERE id = ?";
            $result = update($sql, [$notes, $existing['id']]);
            return $result ? $existing['id'] : false;
        } else {
            // Créer une nouvelle préférence
            $sql = "INSERT INTO user_food_preferences 
                    (user_id, preference_type, food_id, food_category, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            return insert($sql, [$user_id, $preference_type, $food_id, $food_category, $notes]);
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement des préférences alimentaires: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime une préférence alimentaire d'un utilisateur
 * 
 * @param int $preference_id ID de la préférence à supprimer
 * @param int $user_id ID de l'utilisateur (pour vérification)
 * @return bool True si la suppression a réussi, false sinon
 */
function deleteUserFoodPreference($preference_id, $user_id) {
    try {
        $sql = "DELETE FROM user_food_preferences 
                WHERE id = ? AND user_id = ?";
        return delete($sql, [$preference_id, $user_id]) > 0;
    } catch (Exception $e) {
        error_log("Erreur lors de la suppression de la préférence alimentaire: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les préférences alimentaires d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $preference_type Type de préférence (optionnel)
 * @return array Liste des préférences alimentaires
 */
function getUserFoodPreferences($user_id, $preference_type = null) {
    try {
        $params = [$user_id];
        $where_sql = "user_id = ?";
        
        if ($preference_type) {
            $where_sql .= " AND preference_type = ?";
            $params[] = $preference_type;
        }
        
        $sql = "SELECT ufp.*, 
                f.name as food_name, f.category as food_category_name
                FROM user_food_preferences ufp
                LEFT JOIN foods f ON ufp.food_id = f.id
                WHERE $where_sql
                ORDER BY ufp.preference_type, ufp.created_at DESC";
        
        return fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des préférences alimentaires: " . $e->getMessage());
        return [];
    }
}

/**
 * Vérifie si un aliment correspond aux préférences d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $food_id ID de l'aliment
 * @return array Résultat de la vérification
 */
function checkFoodAgainstPreferences($user_id, $food_id) {
    try {
        // Récupérer les informations de l'aliment
        $sql = "SELECT * FROM foods WHERE id = ?";
        $food = fetchOne($sql, [$food_id]);
        
        if (!$food) {
            return [
                'matches' => true,
                'warnings' => [],
                'alerts' => []
            ];
        }
        
        // Récupérer les préférences de l'utilisateur
        $preferences = getUserFoodPreferences($user_id);
        
        $warnings = [];
        $alerts = [];
        
        foreach ($preferences as $pref) {
            // Vérifier les correspondances directes par ID d'aliment
            if ($pref['food_id'] && $pref['food_id'] == $food_id) {
                if ($pref['preference_type'] == 'allergy' || $pref['preference_type'] == 'intolerance') {
                    $alerts[] = [
                        'type' => $pref['preference_type'],
                        'message' => "Cet aliment est marqué comme " . 
                                    ($pref['preference_type'] == 'allergy' ? 'allergène' : 'intolérance') . 
                                    " dans vos préférences."
                    ];
                } else if ($pref['preference_type'] == 'dislike') {
                    $warnings[] = [
                        'type' => 'dislike',
                        'message' => "Cet aliment est marqué comme non apprécié dans vos préférences."
                    ];
                }
            }
            
            // Vérifier les correspondances par catégorie
            if ($pref['food_category'] && $food['category'] == $pref['food_category']) {
                if ($pref['preference_type'] == 'allergy' || $pref['preference_type'] == 'intolerance') {
                    $alerts[] = [
                        'type' => $pref['preference_type'],
                        'message' => "Cet aliment appartient à la catégorie '" . $pref['food_category'] . 
                                    "' qui est marquée comme " . 
                                    ($pref['preference_type'] == 'allergy' ? 'allergène' : 'intolérance') . 
                                    " dans vos préférences."
                    ];
                } else if ($pref['preference_type'] == 'dislike') {
                    $warnings[] = [
                        'type' => 'dislike',
                        'message' => "Cet aliment appartient à la catégorie '" . $pref['food_category'] . 
                                    "' qui est marquée comme non appréciée dans vos préférences."
                    ];
                }
            }
        }
        
        return [
            'matches' => empty($alerts),
            'warnings' => $warnings,
            'alerts' => $alerts
        ];
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification des préférences alimentaires: " . $e->getMessage());
        return [
            'matches' => true,
            'warnings' => [],
            'alerts' => ["Erreur lors de la vérification des préférences: " . $e->getMessage()]
        ];
    }
}

/**
 * Crée ou met à jour un programme nutritionnel personnalisé
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $name Nom du programme
 * @param string $description Description du programme
 * @param string $goal_type Type d'objectif (perte_poids, prise_poids, maintien)
 * @param int $calorie_adjustment Ajustement calorique (positif ou négatif)
 * @param array $macros_ratio Ratio de macronutriments (protein_pct, carbs_pct, fat_pct)
 * @param int $program_id ID du programme existant (pour mise à jour)
 * @return int|false ID du programme créé ou mis à jour, ou false en cas d'erreur
 */
function saveNutritionProgram($user_id, $name, $description, $goal_type, $calorie_adjustment, $macros_ratio, $program_id = null) {
    try {
        // Valider les données
        if (empty($name) || !in_array($goal_type, ['perte_poids', 'prise_poids', 'maintien'])) {
            return false;
        }
        
        // S'assurer que les pourcentages de macronutriments totalisent 100%
        $protein_pct = isset($macros_ratio['protein_pct']) ? intval($macros_ratio['protein_pct']) : 30;
        $carbs_pct = isset($macros_ratio['carbs_pct']) ? intval($macros_ratio['carbs_pct']) : 40;
        $fat_pct = isset($macros_ratio['fat_pct']) ? intval($macros_ratio['fat_pct']) : 30;
        
        $total_pct = $protein_pct + $carbs_pct + $fat_pct;
        
        if ($total_pct != 100) {
            // Ajuster proportionnellement
            $factor = 100 / $total_pct;
            $protein_pct = round($protein_pct * $factor);
            $carbs_pct = round($carbs_pct * $factor);
            $fat_pct = 100 - $protein_pct - $carbs_pct; // Assurer que le total est exactement 100%
        }
        
        if ($program_id) {
            // Vérifier que le programme appartient à l'utilisateur ou est un programme système
            $sql = "SELECT id FROM nutrition_programs WHERE id = ? AND (user_id = ? OR is_system = 1)";
            $existing = fetchOne($sql, [$program_id, $user_id]);
            
            if (!$existing) {
                return false;
            }
            
            // Mettre à jour le programme existant
            $sql = "UPDATE nutrition_programs 
                    SET name = ?, description = ?, goal_type = ?, calorie_adjustment = ?, 
                    protein_pct = ?, carbs_pct = ?, fat_pct = ?, updated_at = NOW() 
                    WHERE id = ?";
            
            $result = update($sql, [
                $name, $description, $goal_type, $calorie_adjustment,
                $protein_pct, $carbs_pct, $fat_pct, $program_id
            ]);
            
            return $result ? $program_id : false;
        } else {
            // Créer un nouveau programme
            $sql = "INSERT INTO nutrition_programs 
                    (user_id, name, description, goal_type, calorie_adjustment, 
                    protein_pct, carbs_pct, fat_pct, is_system, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
            
            return insert($sql, [
                $user_id, $name, $description, $goal_type, $calorie_adjustment,
                $protein_pct, $carbs_pct, $fat_pct
            ]);
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement du programme nutritionnel: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un programme nutritionnel
 * 
 * @param int $program_id ID du programme à supprimer
 * @param int $user_id ID de l'utilisateur (pour vérification)
 * @return bool True si la suppression a réussi, false sinon
 */
function deleteNutritionProgram($program_id, $user_id) {
    try {
        // Vérifier que le programme appartient à l'utilisateur et n'est pas un programme système
        $sql = "DELETE FROM nutrition_programs 
                WHERE id = ? AND user_id = ? AND is_system = 0";
        return delete($sql, [$program_id, $user_id]) > 0;
    } catch (Exception $e) {
        error_log("Erreur lors de la suppression du programme nutritionnel: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les programmes nutritionnels disponibles pour un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param bool $include_system Inclure les programmes système
 * @return array Liste des programmes nutritionnels
 */
function getNutritionPrograms($user_id, $include_system = true) {
    try {
        $params = [$user_id];
        $where_sql = "user_id = ?";
        
        if ($include_system) {
            $where_sql .= " OR is_system = 1";
        }
        
        $sql = "SELECT * FROM nutrition_programs 
                WHERE $where_sql
                ORDER BY is_system DESC, created_at DESC";
        
        return fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des programmes nutritionnels: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les détails d'un programme nutritionnel
 * 
 * @param int $program_id ID du programme
 * @return array|false Détails du programme ou false en cas d'erreur
 */
function getNutritionProgramDetails($program_id) {
    try {
        $sql = "SELECT * FROM nutrition_programs WHERE id = ?";
        return fetchOne($sql, [$program_id]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des détails du programme nutritionnel: " . $e->getMessage());
        return false;
    }
}

/**
 * Assigne un programme nutritionnel à un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $program_id ID du programme
 * @return bool True si l'assignation a réussi, false sinon
 */
function assignNutritionProgramToUser($user_id, $program_id) {
    try {
        // Récupérer les détails du programme
        $program = getNutritionProgramDetails($program_id);
        
        if (!$program) {
            return false;
        }
        
        // Mettre à jour le profil de l'utilisateur
        $sql = "UPDATE user_profiles 
                SET nutrition_program_id = ? 
                WHERE user_id = ?";
        
        $result = update($sql, [$program_id, $user_id]);
        
        if ($result) {
            // Mettre à jour les besoins caloriques de l'utilisateur en fonction du programme
            updateUserNutritionalNeeds($user_id);
        }
        
        return $result > 0;
    } catch (Exception $e) {
        error_log("Erreur lors de l'assignation du programme nutritionnel: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour les besoins nutritionnels d'un utilisateur en fonction de son programme
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array|false Besoins nutritionnels mis à jour ou false en cas d'erreur
 */
function updateUserNutritionalNeeds($user_id) {
    try {
        // Récupérer le profil de l'utilisateur
        $sql = "SELECT up.*, 
                (SELECT weight FROM weight_logs WHERE user_id = up.user_id ORDER BY log_date DESC LIMIT 1) as current_weight,
                np.goal_type, np.calorie_adjustment, np.protein_pct, np.carbs_pct, np.fat_pct
                FROM user_profiles up
                LEFT JOIN nutrition_programs np ON up.nutrition_program_id = np.id
                WHERE up.user_id = ?";
        
        $profile = fetchOne($sql, [$user_id]);
        
        if (!$profile || !isset($profile['current_weight']) || $profile['current_weight'] <= 0) {
            return false;
        }
        
        // Calculer le BMR en fonction de la formule préférée
        $bmr_formula = $profile['preferred_bmr_formula'] ?? 'mifflin_st_jeor';
        $gender = $profile['gender'] ?? 'homme';
        $weight = $profile['current_weight'];
        $height = $profile['height'] ?? 170;
        $age = 0;
        
        if (isset($profile['birth_date'])) {
            $birth_date = new DateTime($profile['birth_date']);
            $today = new DateTime();
            $age = $birth_date->diff($today)->y;
        }
        
        // Calculer le BMR
        $bmr = 0;
        
        switch ($bmr_formula) {
            case 'harris_benedict':
                if ($gender === 'homme') {
                    $bmr = 88.362 + (13.397 * $weight) + (4.799 * $height) - (5.677 * $age);
                } else {
                    $bmr = 447.593 + (9.247 * $weight) + (3.098 * $height) - (4.330 * $age);
                }
                break;
                
            case 'katch_mcardle':
                // Estimation du pourcentage de graisse corporelle si non disponible
                $body_fat_pct = $profile['body_fat_pct'] ?? ($gender === 'homme' ? 15 : 25);
                $lean_mass = $weight * (1 - ($body_fat_pct / 100));
                $bmr = 370 + (21.6 * $lean_mass);
                break;
                
            case 'mifflin_st_jeor':
            default:
                if ($gender === 'homme') {
                    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
                } else {
                    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
                }
                break;
        }
        
        // Arrondir le BMR
        $bmr = round($bmr);
        
        // Calculer le TDEE (Total Daily Energy Expenditure) en fonction du niveau d'activité
        $activity_level = $profile['activity_level'] ?? 'sedentaire';
        $activity_multiplier = 1.2; // Sédentaire par défaut
        
        switch ($activity_level) {
            case 'leger':
                $activity_multiplier = 1.375;
                break;
            case 'modere':
                $activity_multiplier = 1.55;
                break;
            case 'actif':
                $activity_multiplier = 1.725;
                break;
            case 'tres_actif':
                $activity_multiplier = 1.9;
                break;
        }
        
        $tdee = round($bmr * $activity_multiplier);
        
        // Appliquer l'ajustement calorique en fonction du programme nutritionnel
        $goal_type = $profile['goal_type'] ?? 'maintien';
        $calorie_adjustment = $profile['calorie_adjustment'] ?? 0;
        
        $calorie_goal = $tdee;
        
        if ($goal_type === 'perte_poids') {
            $calorie_goal = $tdee - abs($calorie_adjustment);
        } else if ($goal_type === 'prise_poids') {
            $calorie_goal = $tdee + abs($calorie_adjustment);
        }
        
        // S'assurer que l'objectif calorique n'est pas trop bas
        $min_calories = $gender === 'homme' ? 1500 : 1200;
        if ($calorie_goal < $min_calories) {
            $calorie_goal = $min_calories;
        }
        
        // Calculer les objectifs de macronutriments
        $protein_pct = $profile['protein_pct'] ?? 30;
        $carbs_pct = $profile['carbs_pct'] ?? 40;
        $fat_pct = $profile['fat_pct'] ?? 30;
        
        $protein_calories = $calorie_goal * ($protein_pct / 100);
        $carbs_calories = $calorie_goal * ($carbs_pct / 100);
        $fat_calories = $calorie_goal * ($fat_pct / 100);
        
        $protein_g = round($protein_calories / 4); // 4 calories par gramme de protéine
        $carbs_g = round($carbs_calories / 4);     // 4 calories par gramme de glucide
        $fat_g = round($fat_calories / 9);         // 9 calories par gramme de lipide
        
        // Enregistrer les besoins nutritionnels
        $sql = "INSERT INTO user_calorie_needs 
                (user_id, bmr, activity_multiplier, total_calories, goal_type, calorie_adjustment, 
                goal_calories, protein_pct, carbs_pct, fat_pct, protein_g, carbs_g, fat_g, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                bmr = ?, activity_multiplier = ?, total_calories = ?, goal_type = ?, calorie_adjustment = ?, 
                goal_calories = ?, protein_pct = ?, carbs_pct = ?, fat_pct = ?, protein_g = ?, carbs_g = ?, fat_g = ?, 
                updated_at = NOW()";
        
        $params = [
            $user_id, $bmr, $activity_multiplier, $tdee, $goal_type, $calorie_adjustment,
            $calorie_goal, $protein_pct, $carbs_pct, $fat_pct, $protein_g, $carbs_g, $fat_g,
            
            $bmr, $activity_multiplier, $tdee, $goal_type, $calorie_adjustment,
            $calorie_goal, $protein_pct, $carbs_pct, $fat_pct, $protein_g, $carbs_g, $fat_g
        ];
        
        execute($sql, $params);
        
        // Retourner les besoins nutritionnels
        return [
            'bmr' => $bmr,
            'activity_multiplier' => $activity_multiplier,
            'tdee' => $tdee,
            'goal_type' => $goal_type,
            'calorie_adjustment' => $calorie_adjustment,
            'calorie_goal' => $calorie_goal,
            'protein_pct' => $protein_pct,
            'carbs_pct' => $carbs_pct,
            'fat_pct' => $fat_pct,
            'protein_g' => $protein_g,
            'carbs_g' => $carbs_g,
            'fat_g' => $fat_g
        ];
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour des besoins nutritionnels: " . $e->getMessage());
        return false;
    }
}

/**
 * Crée ou met à jour un objectif de poids pour un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param float $target_weight Poids cible en kg
 * @param string $target_date Date cible (format Y-m-d)
 * @param string $notes Notes supplémentaires (optionnel)
 * @param int $goal_id ID de l'objectif existant (pour mise à jour)
 * @return int|false ID de l'objectif créé ou mis à jour, ou false en cas d'erreur
 */
function saveWeightGoal($user_id, $target_weight, $target_date, $notes = '', $goal_id = null) {
    try {
        // Récupérer le poids actuel
        $sql = "SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
        $current_weight = fetchOne($sql, [$user_id]);
        
        if (!$current_weight) {
            return false;
        }
        
        $start_weight = $current_weight['weight'];
        
        if ($goal_id) {
            // Vérifier que l'objectif appartient à l'utilisateur
            $sql = "SELECT id FROM goals WHERE id = ? AND user_id = ?";
            $existing = fetchOne($sql, [$goal_id, $user_id]);
            
            if (!$existing) {
                return false;
            }
            
            // Mettre à jour l'objectif existant
            $sql = "UPDATE goals 
                    SET target_weight = ?, target_date = ?, notes = ?, updated_at = NOW() 
                    WHERE id = ?";
            
            $result = update($sql, [$target_weight, $target_date, $notes, $goal_id]);
            return $result ? $goal_id : false;
        } else {
            // Marquer les objectifs précédents comme terminés
            $sql = "UPDATE goals 
                    SET status = 'termine', updated_at = NOW() 
                    WHERE user_id = ? AND status = 'en_cours'";
            
            update($sql, [$user_id]);
            
            // Créer un nouvel objectif
            $sql = "INSERT INTO goals 
                    (user_id, start_weight, target_weight, start_date, target_date, notes, status, created_at) 
                    VALUES (?, ?, ?, CURDATE(), ?, ?, 'en_cours', NOW())";
            
            return insert($sql, [$user_id, $start_weight, $target_weight, $target_date, $notes]);
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement de l'objectif de poids: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les objectifs de poids d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $status Statut des objectifs à récupérer (en_cours, termine, tous)
 * @return array Liste des objectifs de poids
 */
function getUserWeightGoals($user_id, $status = 'tous') {
    try {
        $params = [$user_id];
        $where_sql = "user_id = ?";
        
        if ($status !== 'tous') {
            $where_sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT *, 
                DATEDIFF(target_date, start_date) as duration_days,
                DATEDIFF(CURDATE(), start_date) as days_elapsed,
                ABS(target_weight - start_weight) as weight_change_goal,
                (target_weight > start_weight) as is_gain
                FROM goals 
                WHERE $where_sql
                ORDER BY created_at DESC";
        
        $goals = fetchAll($sql, $params);
        
        // Calculer la progression pour chaque objectif
        foreach ($goals as &$goal) {
            // Récupérer le poids actuel
            $sql = "SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
            $current_weight = fetchOne($sql, [$user_id]);
            
            if ($current_weight) {
                $goal['current_weight'] = $current_weight['weight'];
                
                // Calculer la progression
                $total_change = abs($goal['target_weight'] - $goal['start_weight']);
                $current_change = abs($current_weight['weight'] - $goal['start_weight']);
                
                if ($total_change > 0) {
                    $goal['progress'] = round(($current_change / $total_change) * 100);
                    
                    // Vérifier si la direction du changement correspond à l'objectif
                    $is_on_track = true;
                    
                    if ($goal['is_gain'] && $current_weight['weight'] < $goal['start_weight']) {
                        $is_on_track = false;
                    } else if (!$goal['is_gain'] && $current_weight['weight'] > $goal['start_weight']) {
                        $is_on_track = false;
                    }
                    
                    if (!$is_on_track) {
                        $goal['progress'] = 0;
                    }
                    
                    // Limiter à 100%
                    if ($goal['progress'] > 100) {
                        $goal['progress'] = 100;
                    }
                } else {
                    $goal['progress'] = 0;
                }
                
                // Calculer le taux de progression temporelle
                if ($goal['duration_days'] > 0) {
                    $goal['time_progress'] = round(($goal['days_elapsed'] / $goal['duration_days']) * 100);
                    
                    // Limiter à 100%
                    if ($goal['time_progress'] > 100) {
                        $goal['time_progress'] = 100;
                    }
                } else {
                    $goal['time_progress'] = 0;
                }
                
                // Déterminer si l'objectif est en avance, en retard ou dans les temps
                if ($goal['progress'] >= $goal['time_progress']) {
                    $goal['status_vs_time'] = 'en_avance';
                } else if ($goal['progress'] >= ($goal['time_progress'] * 0.8)) {
                    $goal['status_vs_time'] = 'dans_les_temps';
                } else {
                    $goal['status_vs_time'] = 'en_retard';
                }
            }
        }
        
        return $goals;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des objectifs de poids: " . $e->getMessage());
        return [];
    }
}

/**
 * Génère des suggestions de repas en fonction des préférences de l'utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $meal_type Type de repas (petit_dejeuner, dejeuner, diner, collation)
 * @param int $calorie_target Objectif calorique pour le repas
 * @return array Liste des suggestions de repas
 */
function generateMealSuggestions($user_id, $meal_type, $calorie_target = 0) {
    try {
        // Récupérer les besoins caloriques de l'utilisateur si non spécifiés
        if ($calorie_target <= 0) {
            $sql = "SELECT goal_calories FROM user_calorie_needs WHERE user_id = ?";
            $needs = fetchOne($sql, [$user_id]);
            
            if ($needs) {
                // Répartir les calories en fonction du type de repas
                switch ($meal_type) {
                    case 'petit_dejeuner':
                        $calorie_target = round($needs['goal_calories'] * 0.25); // 25% des calories quotidiennes
                        break;
                    case 'dejeuner':
                        $calorie_target = round($needs['goal_calories'] * 0.35); // 35% des calories quotidiennes
                        break;
                    case 'diner':
                        $calorie_target = round($needs['goal_calories'] * 0.30); // 30% des calories quotidiennes
                        break;
                    case 'collation':
                        $calorie_target = round($needs['goal_calories'] * 0.10); // 10% des calories quotidiennes
                        break;
                    default:
                        $calorie_target = round($needs['goal_calories'] * 0.25);
                }
            } else {
                // Valeurs par défaut si les besoins caloriques ne sont pas disponibles
                switch ($meal_type) {
                    case 'petit_dejeuner':
                        $calorie_target = 500;
                        break;
                    case 'dejeuner':
                        $calorie_target = 700;
                        break;
                    case 'diner':
                        $calorie_target = 600;
                        break;
                    case 'collation':
                        $calorie_target = 200;
                        break;
                    default:
                        $calorie_target = 500;
                }
            }
        }
        
        // Récupérer les préférences alimentaires de l'utilisateur
        $preferences = getUserFoodPreferences($user_id);
        
        // Organiser les préférences par type
        $allergies = [];
        $intolerances = [];
        $dislikes = [];
        $likes = [];
        
        foreach ($preferences as $pref) {
            switch ($pref['preference_type']) {
                case 'allergy':
                    if ($pref['food_id']) {
                        $allergies[] = $pref['food_id'];
                    } else if ($pref['food_category']) {
                        $allergies[] = "category:" . $pref['food_category'];
                    }
                    break;
                case 'intolerance':
                    if ($pref['food_id']) {
                        $intolerances[] = $pref['food_id'];
                    } else if ($pref['food_category']) {
                        $intolerances[] = "category:" . $pref['food_category'];
                    }
                    break;
                case 'dislike':
                    if ($pref['food_id']) {
                        $dislikes[] = $pref['food_id'];
                    } else if ($pref['food_category']) {
                        $dislikes[] = "category:" . $pref['food_category'];
                    }
                    break;
                case 'like':
                    if ($pref['food_id']) {
                        $likes[] = $pref['food_id'];
                    } else if ($pref['food_category']) {
                        $likes[] = "category:" . $pref['food_category'];
                    }
                    break;
            }
        }
        
        // Construire la requête pour les repas prédéfinis
        $params = [];
        $where_clauses = ["meal_type = ?"];
        $params[] = $meal_type;
        
        // Filtrer par calories (±20%)
        $min_calories = $calorie_target * 0.8;
        $max_calories = $calorie_target * 1.2;
        $where_clauses[] = "calories BETWEEN ? AND ?";
        $params[] = $min_calories;
        $params[] = $max_calories;
        
        // Exclure les allergènes et intolérances
        $excluded_foods = array_merge($allergies, $intolerances);
        
        if (!empty($excluded_foods)) {
            $food_exclusion_sql = "";
            $category_exclusion_sql = "";
            
            foreach ($excluded_foods as $excluded) {
                if (strpos($excluded, "category:") === 0) {
                    $category = substr($excluded, 9);
                    $category_exclusion_sql .= " AND NOT EXISTS (
                        SELECT 1 FROM predefined_meal_items pmi
                        JOIN foods f ON pmi.food_id = f.id
                        WHERE pmi.predefined_meal_id = pm.id AND f.category = ?
                    )";
                    $params[] = $category;
                } else {
                    $food_exclusion_sql .= " AND NOT EXISTS (
                        SELECT 1 FROM predefined_meal_items pmi
                        WHERE pmi.predefined_meal_id = pm.id AND pmi.food_id = ?
                    )";
                    $params[] = $excluded;
                }
            }
            
            $where_clauses[] = "1=1" . $food_exclusion_sql . $category_exclusion_sql;
        }
        
        // Construire la requête finale
        $where_sql = implode(" AND ", $where_clauses);
        
        $sql = "SELECT pm.*, 
                (SELECT COUNT(*) FROM user_favorite_meals ufm WHERE ufm.predefined_meal_id = pm.id AND ufm.user_id = ?) as is_favorite
                FROM predefined_meals pm
                WHERE $where_sql AND (pm.is_public = 1 OR pm.created_by_user = ?)
                ORDER BY 
                    CASE WHEN pm.created_by_user = ? THEN 1 ELSE 0 END DESC,
                    is_favorite DESC,
                    ABS(pm.calories - ?) ASC
                LIMIT 10";
        
        array_unshift($params, $user_id);
        $params[] = $user_id;
        $params[] = $user_id;
        $params[] = $calorie_target;
        
        $suggestions = fetchAll($sql, $params);
        
        // Si moins de 5 suggestions, ajouter des repas personnalisés
        if (count($suggestions) < 5) {
            // Récupérer les aliments préférés de l'utilisateur
            $favorite_foods = [];
            
            foreach ($likes as $like) {
                if (strpos($like, "category:") === 0) {
                    $category = substr($like, 9);
                    $sql = "SELECT id, name, calories, protein, carbs, fat 
                            FROM foods 
                            WHERE category = ?
                            ORDER BY RAND()
                            LIMIT 5";
                    $foods = fetchAll($sql, [$category]);
                    $favorite_foods = array_merge($favorite_foods, $foods);
                } else {
                    $sql = "SELECT id, name, calories, protein, carbs, fat 
                            FROM foods 
                            WHERE id = ?";
                    $food = fetchOne($sql, [$like]);
                    if ($food) {
                        $favorite_foods[] = $food;
                    }
                }
            }
            
            // Si pas assez d'aliments préférés, ajouter des aliments populaires
            if (count($favorite_foods) < 5) {
                $sql = "SELECT f.id, f.name, f.calories, f.protein, f.carbs, f.fat
                        FROM foods f
                        JOIN (
                            SELECT food_id, COUNT(*) as usage_count
                            FROM food_logs
                            WHERE food_id IS NOT NULL
                            GROUP BY food_id
                            ORDER BY usage_count DESC
                            LIMIT 20
                        ) as popular ON f.id = popular.food_id
                        ORDER BY RAND()
                        LIMIT ?";
                
                $popular_foods = fetchAll($sql, [10 - count($favorite_foods)]);
                $favorite_foods = array_merge($favorite_foods, $popular_foods);
            }
            
            // Créer des combinaisons d'aliments pour atteindre l'objectif calorique
            $custom_suggestions = [];
            
            // Fonction récursive pour générer des combinaisons
            function generateCombinations($foods, $target, $current = [], $currentCalories = 0, $index = 0, &$results = [], $max_results = 3) {
                if (count($results) >= $max_results) {
                    return;
                }
                
                if ($currentCalories >= $target * 0.8 && $currentCalories <= $target * 1.2) {
                    $results[] = $current;
                    return;
                }
                
                if ($index >= count($foods)) {
                    return;
                }
                
                // Inclure l'aliment actuel
                $newCurrent = $current;
                $newCurrent[] = $foods[$index];
                generateCombinations($foods, $target, $newCurrent, $currentCalories + $foods[$index]['calories'], $index + 1, $results, $max_results);
                
                // Exclure l'aliment actuel
                generateCombinations($foods, $target, $current, $currentCalories, $index + 1, $results, $max_results);
            }
            
            $combinations = [];
            generateCombinations($favorite_foods, $calorie_target, [], 0, 0, $combinations, 5);
            
            // Convertir les combinaisons en suggestions
            foreach ($combinations as $combination) {
                $total_calories = 0;
                $total_protein = 0;
                $total_carbs = 0;
                $total_fat = 0;
                $food_names = [];
                
                foreach ($combination as $food) {
                    $total_calories += $food['calories'];
                    $total_protein += $food['protein'];
                    $total_carbs += $food['carbs'];
                    $total_fat += $food['fat'];
                    $food_names[] = $food['name'];
                }
                
                $custom_suggestions[] = [
                    'id' => 'custom_' . uniqid(),
                    'name' => ucfirst($meal_type) . ' personnalisé',
                    'description' => 'Suggestion basée sur vos préférences: ' . implode(', ', $food_names),
                    'meal_type' => $meal_type,
                    'calories' => $total_calories,
                    'protein' => $total_protein,
                    'carbs' => $total_carbs,
                    'fat' => $total_fat,
                    'is_custom_suggestion' => true,
                    'foods' => $combination
                ];
            }
            
            // Ajouter les suggestions personnalisées
            $suggestions = array_merge($suggestions, $custom_suggestions);
        }
        
        return $suggestions;
    } catch (Exception $e) {
        error_log("Erreur lors de la génération des suggestions de repas: " . $e->getMessage());
        return [];
    }
}