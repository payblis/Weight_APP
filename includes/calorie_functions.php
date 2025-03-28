<?php
// Fonction pour calculer le métabolisme de base (BMR) en utilisant l'équation de Mifflin-St Jeor
function calculateBMR($weight, $height, $age, $gender, $activity_level) {
    // Conversion du poids en kg et de la taille en cm si nécessaire
    $weight = floatval($weight);
    $height = floatval($height);
    $age = intval($age);
    
    // Calcul du BMR selon le genre
    if (strtolower($gender) === 'homme' || strtolower($gender) === 'male') {
        $bmr = 10 * $weight + 6.25 * $height - 5 * $age + 5;
    } else {
        $bmr = 10 * $weight + 6.25 * $height - 5 * $age - 161;
    }
    
    // Ajustement en fonction du niveau d'activité
    $activity_multipliers = [
        'sedentary' => 1.2,      // Sédentaire (peu ou pas d'exercice)
        'light' => 1.375,        // Légèrement actif (exercice léger 1-3 jours/semaine)
        'moderate' => 1.55,      // Modérément actif (exercice modéré 3-5 jours/semaine)
        'active' => 1.725,       // Très actif (exercice intense 6-7 jours/semaine)
        'very_active' => 1.9     // Extrêmement actif (exercice très intense, travail physique)
    ];
    
    $multiplier = $activity_multipliers[$activity_level] ?? 1.2; // Par défaut sédentaire
    
    // Calcul des calories totales brûlées au repos
    $total_calories = $bmr * $multiplier;
    
    return [
        'bmr' => round($bmr),                    // Métabolisme de base
        'total_calories' => round($total_calories) // Calories totales avec niveau d'activité
    ];
}

// Fonction pour calculer l'IMC (Indice de Masse Corporelle)
function calculateBMI($weight, $height) {
    // Conversion de la taille en mètres si elle est en cm
    $height_m = $height / 100;
    
    // Calcul de l'IMC
    $bmi = $weight / ($height_m * $height_m);
    
    // Détermination de la catégorie d'IMC
    $category = '';
    if ($bmi < 18.5) {
        $category = 'Insuffisance pondérale';
    } elseif ($bmi >= 18.5 && $bmi < 25) {
        $category = 'Poids normal';
    } elseif ($bmi >= 25 && $bmi < 30) {
        $category = 'Surpoids';
    } elseif ($bmi >= 30 && $bmi < 35) {
        $category = 'Obésité modérée';
    } elseif ($bmi >= 35 && $bmi < 40) {
        $category = 'Obésité sévère';
    } else {
        $category = 'Obésité morbide';
    }
    
    return [
        'bmi' => round($bmi, 1),
        'category' => $category
    ];
}

// Fonction pour calculer les besoins caloriques en fonction de l'objectif
function calculateCalorieNeeds($bmr, $goal) {
    switch ($goal) {
        case 'lose_weight_fast':
            // Déficit calorique important pour perte de poids rapide (-25%)
            return round($bmr * 0.75);
        case 'lose_weight':
            // Déficit calorique modéré pour perte de poids (-15%)
            return round($bmr * 0.85);
        case 'maintain':
            // Maintien du poids
            return round($bmr);
        case 'gain_muscle':
            // Surplus calorique pour prise de muscle (+15%)
            return round($bmr * 1.15);
        case 'gain_weight':
            // Surplus calorique important pour prise de poids (+25%)
            return round($bmr * 1.25);
        default:
            // Par défaut, maintien du poids
            return round($bmr);
    }
}

// Fonction pour calculer la répartition des macronutriments en fonction de l'objectif
function calculateMacroDistribution($calories, $goal) {
    $macros = [];
    
    switch ($goal) {
        case 'lose_weight_fast':
            // Régime faible en glucides, riche en protéines
            $macros['protein_pct'] = 40;
            $macros['carbs_pct'] = 20;
            $macros['fat_pct'] = 40;
            break;
        case 'lose_weight':
            // Régime modéré en glucides, riche en protéines
            $macros['protein_pct'] = 35;
            $macros['carbs_pct'] = 30;
            $macros['fat_pct'] = 35;
            break;
        case 'maintain':
            // Régime équilibré
            $macros['protein_pct'] = 30;
            $macros['carbs_pct'] = 40;
            $macros['fat_pct'] = 30;
            break;
        case 'gain_muscle':
            // Régime riche en protéines et glucides
            $macros['protein_pct'] = 35;
            $macros['carbs_pct'] = 45;
            $macros['fat_pct'] = 20;
            break;
        case 'gain_weight':
            // Régime riche en calories
            $macros['protein_pct'] = 25;
            $macros['carbs_pct'] = 50;
            $macros['fat_pct'] = 25;
            break;
        default:
            // Par défaut, régime équilibré
            $macros['protein_pct'] = 30;
            $macros['carbs_pct'] = 40;
            $macros['fat_pct'] = 30;
    }
    
    // Calcul des grammes de chaque macronutriment
    // 1g de protéine = 4 calories
    // 1g de glucides = 4 calories
    // 1g de lipides = 9 calories
    $macros['protein_g'] = round(($calories * ($macros['protein_pct'] / 100)) / 4);
    $macros['carbs_g'] = round(($calories * ($macros['carbs_pct'] / 100)) / 4);
    $macros['fat_g'] = round(($calories * ($macros['fat_pct'] / 100)) / 9);
    
    return $macros;
}

// Fonction pour mettre à jour les besoins caloriques d'un utilisateur
function updateUserCalorieNeeds($user_id, $bmr, $total_calories, $goal_calories, $protein_g, $carbs_g, $fat_g) {
    try {
        // Vérifier si l'utilisateur a déjà des besoins caloriques enregistrés
        $sql = "SELECT id FROM user_calorie_needs WHERE user_id = ?";
        $existing = fetchOne($sql, [$user_id]);
        
        if ($existing) {
            // Mettre à jour les besoins existants
            $sql = "UPDATE user_calorie_needs 
                    SET bmr = ?, total_calories = ?, goal_calories = ?, 
                        protein_g = ?, carbs_g = ?, fat_g = ?, updated_at = NOW() 
                    WHERE user_id = ?";
            $result = update($sql, [$bmr, $total_calories, $goal_calories, $protein_g, $carbs_g, $fat_g, $user_id]);
        } else {
            // Créer de nouveaux besoins
            $sql = "INSERT INTO user_calorie_needs 
                    (user_id, bmr, total_calories, goal_calories, protein_g, carbs_g, fat_g, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $result = insert($sql, [$user_id, $bmr, $total_calories, $goal_calories, $protein_g, $carbs_g, $fat_g]);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour des besoins caloriques: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer les besoins caloriques d'un utilisateur
function getUserCalorieNeeds($user_id) {
    try {
        $sql = "SELECT * FROM user_calorie_needs WHERE user_id = ?";
        return fetchOne($sql, [$user_id]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des besoins caloriques: " . $e->getMessage());
        return null;
    }
}

// Fonction pour mettre à jour l'historique de l'IMC
function updateBMIHistory($user_id, $weight, $height, $bmi, $category) {
    try {
        $sql = "INSERT INTO bmi_history 
                (user_id, weight, height, bmi, category, log_date) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        return insert($sql, [$user_id, $weight, $height, $bmi, $category]);
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour de l'historique IMC: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer l'historique de l'IMC d'un utilisateur
function getBMIHistory($user_id, $limit = 10) {
    try {
        $sql = "SELECT *, DATE_FORMAT(log_date, '%d/%m/%Y') as formatted_date 
                FROM bmi_history 
                WHERE user_id = ? 
                ORDER BY log_date DESC 
                LIMIT ?";
        return fetchAll($sql, [$user_id, $limit]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de l'historique IMC: " . $e->getMessage());
        return [];
    }
}

// Fonction pour calculer le bilan calorique quotidien
function calculateDailyCalorieBalance($user_id, $date = null) {
    try {
        // Si aucune date n'est spécifiée, utiliser la date du jour
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        // Récupérer les calories consommées (repas)
        $sql = "SELECT SUM(calories) as total_consumed 
                FROM food_logs 
                WHERE user_id = ? AND DATE(log_date) = ?";
        $consumed = fetchOne($sql, [$user_id, $date]);
        $calories_consumed = $consumed ? intval($consumed['total_consumed']) : 0;
        
        // Récupérer les calories brûlées (exercices)
        $sql = "SELECT SUM(calories_burned) as total_burned 
                FROM exercise_logs 
                WHERE user_id = ? AND DATE(log_date) = ?";
        $burned = fetchOne($sql, [$user_id, $date]);
        $calories_burned = $burned ? intval($burned['total_burned']) : 0;
        
        // Récupérer le métabolisme de base de l'utilisateur
        $sql = "SELECT bmr, total_calories, goal_calories 
                FROM user_calorie_needs 
                WHERE user_id = ?";
        $needs = fetchOne($sql, [$user_id]);
        
        $bmr = $needs ? intval($needs['bmr']) : 0;
        $total_calories_burned = $needs ? intval($needs['total_calories']) : 0;
        $goal_calories = $needs ? intval($needs['goal_calories']) : 0;
        
        // Calculer le bilan calorique
        $total_burned = $total_calories_burned + $calories_burned;
        $balance = $calories_consumed - $total_burned;
        
        // Enregistrer le bilan calorique dans l'historique
        $sql = "INSERT INTO calorie_balance_history 
                (user_id, log_date, calories_consumed, calories_burned, bmr_calories, total_burned, balance) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                calories_consumed = ?, calories_burned = ?, bmr_calories = ?, total_burned = ?, balance = ?";
        
        insert($sql, [
            $user_id, $date, $calories_consumed, $calories_burned, $bmr, $total_burned, $balance,
            $calories_consumed, $calories_burned, $bmr, $total_burned, $balance
        ]);
        
        return [
            'calories_consumed' => $calories_consumed,
            'calories_burned_exercise' => $calories_burned,
            'bmr_calories' => $bmr,
            'total_calories_burned' => $total_burned,
            'balance' => $balance,
            'goal_calories' => $goal_calories
        ];
    } catch (Exception $e) {
        error_log("Erreur lors du calcul du bilan calorique: " . $e->getMessage());
        return [
            'calories_consumed' => 0,
            'calories_burned_exercise' => 0,
            'bmr_calories' => 0,
            'total_calories_burned' => 0,
            'balance' => 0,
            'goal_calories' => 0
        ];
    }
}

// Fonction pour récupérer l'historique du bilan calorique
function getCalorieBalanceHistory($user_id, $limit = 7) {
    try {
        $sql = "SELECT *, DATE_FORMAT(log_date, '%d/%m/%Y') as formatted_date 
                FROM calorie_balance_history 
                WHERE user_id = ? 
                ORDER BY log_date DESC 
                LIMIT ?";
        return fetchAll($sql, [$user_id, $limit]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de l'historique du bilan calorique: " . $e->getMessage());
        return [];
    }
}

// Fonction pour mettre à jour les besoins caloriques en fonction du programme
function updateCalorieNeedsFromProgram($user_id, $program_id) {
    try {
        // Récupérer les informations du programme
        $sql = "SELECT * FROM programs WHERE id = ?";
        $program = fetchOne($sql, [$program_id]);
        
        if (!$program) {
            return false;
        }
        
        // Récupérer les besoins caloriques actuels de l'utilisateur
        $sql = "SELECT * FROM user_calorie_needs WHERE user_id = ?";
        $needs = fetchOne($sql, [$user_id]);
        
        if (!$needs) {
            // Si l'utilisateur n'a pas encore de besoins caloriques, récupérer ses informations
            $sql = "SELECT * FROM users WHERE id = ?";
            $user = fetchOne($sql, [$user_id]);
            
            if (!$user) {
                return false;
            }
            
            // Récupérer les informations du profil utilisateur
            $sql = "SELECT * FROM user_profiles WHERE user_id = ?";
            $profile = fetchOne($sql, [$user_id]);
            
            if (!$profile) {
                return false;
            }
            
            // Calculer le BMR en fonction des informations du profil
            $weight = $profile['weight'];
            $height = $profile['height'];
            $age = date_diff(date_create($profile['birth_date']), date_create('now'))->y;
            $gender = $profile['gender'];
            $activity_level = $profile['activity_level'] ?? 'moderate';
            
            $bmr_data = calculateBMR($weight, $height, $age, $gender, $activity_level);
            $bmr = $bmr_data['bmr'];
            $total_calories = $bmr_data['total_calories'];
        } else {
            // Utiliser les valeurs existantes
            $bmr = $needs['bmr'];
            $total_calories = $needs['total_calories'];
        }
        
        // Utiliser les valeurs du programme pour les macronutriments
        $goal_calories = $program['daily_calories'];
        $protein_pct = $program['protein_pct'];
        $carbs_pct = $program['carbs_pct'];
        $fat_pct = $program['fat_pct'];
        
        // Calculer les grammes de macronutriments
        $protein_g = round(($goal_calories * ($protein_pct / 100)) / 4);
        $carbs_g = round(($goal_calories * ($carbs_pct / 100)) / 4);
        $fat_g = round(($goal_calories * ($fat_pct / 100)) / 9);
        
        // Mettre à jour les besoins caloriques de l'utilisateur
        return updateUserCalorieNeeds($user_id, $bmr, $total_calories, $goal_calories, $protein_g, $carbs_g, $fat_g);
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour des besoins caloriques depuis le programme: " . $e->getMessage());
        return false;
    }
}

// Fonction pour calculer et mettre à jour les besoins caloriques d'un utilisateur
function calculateAndUpdateUserCalorieNeeds($user_id) {
    try {
        // Récupérer les informations de l'utilisateur
        $sql = "SELECT * FROM users WHERE id = ?";
        $user = fetchOne($sql, [$user_id]);
        
        if (!$user) {
            return false;
        }
        
        // Récupérer les informations du profil utilisateur
        $sql = "SELECT * FROM user_profiles WHERE user_id = ?";
        $profile = fetchOne($sql, [$user_id]);
        
        if (!$profile) {
            return false;
        }
        
        // Récupérer l'objectif de l'utilisateur
        $sql = "SELECT * FROM user_goals WHERE user_id = ?";
        $goal_data = fetchOne($sql, [$user_id]);
        $goal = $goal_data ? $goal_data['goal_type'] : 'maintain';
        
        // Vérifier si l'utilisateur est inscrit à un programme
        $sql = "SELECT p.* FROM programs p 
                JOIN user_programs up ON p.id = up.program_id 
                WHERE up.user_id = ? AND up.is_active = 1";
        $program = fetchOne($sql, [$user_id]);
        
        if ($program) {
            // Si l'utilisateur est inscrit à un programme, utiliser les valeurs du programme
            return updateCalorieNeedsFromProgram($user_id, $program['id']);
        }
        
        // Calculer le BMR en fonction des informations du profil
        $weight = $profile['weight'];
        $height = $profile['height'];
        $age = date_diff(date_create($profile['birth_date']), date_create('now'))->y;
        $gender = $profile['gender'];
        $activity_level = $profile['activity_level'] ?? 'moderate';
        
        $bmr_data = calculateBMR($weight, $height, $age, $gender, $activity_level);
        $bmr = $bmr_data['bmr'];
        $total_calories = $bmr_data['total_calories'];
        
        // Calculer les besoins caloriques en fonction de l'objectif
        $goal_calories = calculateCalorieNeeds($total_calories, $goal);
        
        // Calculer la répartition des macronutriments
        $macros = calculateMacroDistribution($goal_calories, $goal);
        
        // Mettre à jour les besoins caloriques de l'utilisateur
        return updateUserCalorieNeeds(
            $user_id, 
            $bmr, 
            $total_calories, 
            $goal_calories, 
            $macros['protein_g'], 
            $macros['carbs_g'], 
            $macros['fat_g']
        );
    } catch (Exception $e) {
        error_log("Erreur lors du calcul et de la mise à jour des besoins caloriques: " . $e->getMessage());
        return false;
    }
}
