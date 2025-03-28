<?php
/**
 * Fonctions pour les historiques et suivis avancés
 */

/**
 * Récupère l'historique du bilan calorique quotidien d'un utilisateur
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
 * Récupère l'historique de l'IMC d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $limit Nombre d'entrées à récupérer
 * @return array Historique de l'IMC
 */
function getBMIHistory($user_id, $limit = 30) {
    try {
        $sql = "SELECT bh.*, DATE_FORMAT(bh.log_date, '%d/%m/%Y') as formatted_date 
                FROM bmi_history bh
                WHERE bh.user_id = ? 
                ORDER BY bh.log_date DESC 
                LIMIT ?";
        
        return fetchAll($sql, [$user_id, $limit]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de l'historique de l'IMC: " . $e->getMessage());
        return [];
    }
}

/**
 * Met à jour l'historique de l'IMC d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param float $weight Poids en kg
 * @param float $height Taille en cm
 * @param float $bmi IMC calculé
 * @param string $category Catégorie d'IMC
 * @param string $log_date Date de l'entrée (format Y-m-d)
 * @return int|false ID de l'entrée créée ou false en cas d'erreur
 */
function updateBMIHistory($user_id, $weight, $height, $bmi, $category, $log_date = null) {
    try {
        if ($log_date === null) {
            $log_date = date('Y-m-d');
        }
        
        // Vérifier si une entrée existe déjà pour cette date
        $sql = "SELECT id FROM bmi_history WHERE user_id = ? AND DATE(log_date) = ?";
        $existing = fetchOne($sql, [$user_id, $log_date]);
        
        if ($existing) {
            // Mettre à jour l'entrée existante
            $sql = "UPDATE bmi_history 
                    SET weight = ?, height = ?, bmi = ?, category = ?, updated_at = NOW() 
                    WHERE id = ?";
            $result = update($sql, [$weight, $height, $bmi, $category, $existing['id']]);
            return $result ? $existing['id'] : false;
        } else {
            // Créer une nouvelle entrée
            $sql = "INSERT INTO bmi_history 
                    (user_id, weight, height, bmi, category, log_date, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            return insert($sql, [$user_id, $weight, $height, $bmi, $category, $log_date]);
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour de l'historique IMC: " . $e->getMessage());
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
        $sql = "SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN fl.food_id IS NOT NULL THEN (f.calories * fl.quantity / 100)
                        WHEN fl.custom_calories IS NOT NULL THEN fl.custom_calories
                        ELSE 0
                    END
                ), 0) as total_consumed,
                COALESCE(SUM(
                    CASE 
                        WHEN fl.food_id IS NOT NULL THEN (f.protein * fl.quantity / 100)
                        WHEN fl.custom_protein IS NOT NULL THEN fl.custom_protein
                        ELSE 0
                    END
                ), 0) as total_protein,
                COALESCE(SUM(
                    CASE 
                        WHEN fl.food_id IS NOT NULL THEN (f.carbs * fl.quantity / 100)
                        WHEN fl.custom_carbs IS NOT NULL THEN fl.custom_carbs
                        ELSE 0
                    END
                ), 0) as total_carbs,
                COALESCE(SUM(
                    CASE 
                        WHEN fl.food_id IS NOT NULL THEN (f.fat * fl.quantity / 100)
                        WHEN fl.custom_fat IS NOT NULL THEN fl.custom_fat
                        ELSE 0
                    END
                ), 0) as total_fat
                FROM food_logs fl
                LEFT JOIN foods f ON fl.food_id = f.id
                WHERE fl.user_id = ? AND DATE(fl.log_date) = ?";
        
        $consumed = fetchOne($sql, [$user_id, $date]);
        $calories_consumed = $consumed ? intval($consumed['total_consumed']) : 0;
        $protein_consumed = $consumed ? floatval($consumed['total_protein']) : 0;
        $carbs_consumed = $consumed ? floatval($consumed['total_carbs']) : 0;
        $fat_consumed = $consumed ? floatval($consumed['total_fat']) : 0;
        
        // Récupérer les calories brûlées (exercices)
        $sql = "SELECT COALESCE(SUM(calories_burned), 0) as total_burned 
                FROM exercise_logs 
                WHERE user_id = ? AND DATE(log_date) = ?";
        $burned = fetchOne($sql, [$user_id, $date]);
        $calories_burned = $burned ? intval($burned['total_burned']) : 0;
        
        // Récupérer le métabolisme de base et les besoins caloriques de l'utilisateur
        $sql = "SELECT bmr, total_calories, goal_calories, protein_g, carbs_g, fat_g
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
                $protein_goal = $calculated_needs['protein_g'];
                $carbs_goal = $calculated_needs['carbs_g'];
                $fat_goal = $calculated_needs['fat_g'];
            } else {
                // Valeurs par défaut si le calcul échoue
                $bmr = 0;
                $total_calories_burned = 0;
                $goal_calories = 0;
                $protein_goal = 0;
                $carbs_goal = 0;
                $fat_goal = 0;
            }
        } else {
            $bmr = intval($needs['bmr']);
            $total_calories_burned = intval($needs['total_calories']);
            $goal_calories = intval($needs['goal_calories']);
            $protein_goal = intval($needs['protein_g']);
            $carbs_goal = intval($needs['carbs_g']);
            $fat_goal = intval($needs['fat_g']);
        }
        
        // Calculer le bilan calorique
        $total_burned = $total_calories_burned + $calories_burned;
        $balance = $calories_consumed - $total_burned;
        $goal_difference = $calories_consumed - $goal_calories;
        
        // Calculer les pourcentages de macronutriments
        $total_macros_calories = ($protein_consumed * 4) + ($carbs_consumed * 4) + ($fat_consumed * 9);
        
        if ($total_macros_calories > 0) {
            $protein_pct = round(($protein_consumed * 4 / $total_macros_calories) * 100);
            $carbs_pct = round(($carbs_consumed * 4 / $total_macros_calories) * 100);
            $fat_pct = round(($fat_consumed * 9 / $total_macros_calories) * 100);
        } else {
            $protein_pct = 0;
            $carbs_pct = 0;
            $fat_pct = 0;
        }
        
        // Calculer les pourcentages d'atteinte des objectifs
        $protein_goal_pct = $protein_goal > 0 ? round(($protein_consumed / $protein_goal) * 100) : 0;
        $carbs_goal_pct = $carbs_goal > 0 ? round(($carbs_consumed / $carbs_goal) * 100) : 0;
        $fat_goal_pct = $fat_goal > 0 ? round(($fat_consumed / $fat_goal) * 100) : 0;
        $calories_goal_pct = $goal_calories > 0 ? round(($calories_consumed / $goal_calories) * 100) : 0;
        
        // Enregistrer le bilan calorique dans l'historique
        $sql = "INSERT INTO calorie_balance_history 
                (user_id, log_date, calories_consumed, calories_burned, bmr_calories, total_burned, balance, 
                protein_consumed, carbs_consumed, fat_consumed, protein_goal, carbs_goal, fat_goal,
                protein_pct, carbs_pct, fat_pct, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                calories_consumed = ?, calories_burned = ?, bmr_calories = ?, total_burned = ?, balance = ?,
                protein_consumed = ?, carbs_consumed = ?, fat_consumed = ?, protein_goal = ?, carbs_goal = ?, fat_goal = ?,
                protein_pct = ?, carbs_pct = ?, fat_pct = ?, updated_at = NOW()";
        
        insert($sql, [
            $user_id, $date, $calories_consumed, $calories_burned, $bmr, $total_burned, $balance,
            $protein_consumed, $carbs_consumed, $fat_consumed, $protein_goal, $carbs_goal, $fat_goal,
            $protein_pct, $carbs_pct, $fat_pct,
            
            $calories_consumed, $calories_burned, $bmr, $total_burned, $balance,
            $protein_consumed, $carbs_consumed, $fat_consumed, $protein_goal, $carbs_goal, $fat_goal,
            $protein_pct, $carbs_pct, $fat_pct
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
            'goal_difference' => $goal_difference,
            'protein_consumed' => round($protein_consumed, 1),
            'carbs_consumed' => round($carbs_consumed, 1),
            'fat_consumed' => round($fat_consumed, 1),
            'protein_goal' => $protein_goal,
            'carbs_goal' => $carbs_goal,
            'fat_goal' => $fat_goal,
            'protein_pct' => $protein_pct,
            'carbs_pct' => $carbs_pct,
            'fat_pct' => $fat_pct,
            'protein_goal_pct' => $protein_goal_pct,
            'carbs_goal_pct' => $carbs_goal_pct,
            'fat_goal_pct' => $fat_goal_pct,
            'calories_goal_pct' => $calories_goal_pct
        ];
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour du bilan calorique: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les données pour les graphiques de progression
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $type Type de données (weight, calories, macros, bmi)
 * @param int $days Nombre de jours d'historique
 * @return array Données pour les graphiques
 */
function getProgressChartData($user_id, $type = 'weight', $days = 30) {
    try {
        $data = [];
        
        switch ($type) {
            case 'weight':
                // Données de poids
                $sql = "SELECT weight, DATE_FORMAT(log_date, '%d/%m/%Y') as date, log_date
                        FROM weight_logs
                        WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                        ORDER BY log_date";
                $data = fetchAll($sql, [$user_id, $days]);
                break;
                
            case 'calories':
                // Données de calories
                $sql = "SELECT calories_consumed, goal_calories, balance, DATE_FORMAT(log_date, '%d/%m/%Y') as date, log_date
                        FROM calorie_balance_history
                        WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                        ORDER BY log_date";
                $data = fetchAll($sql, [$user_id, $days]);
                break;
                
            case 'macros':
                // Données de macronutriments
                $sql = "SELECT protein_consumed, carbs_consumed, fat_consumed, 
                        protein_pct, carbs_pct, fat_pct,
                        DATE_FORMAT(log_date, '%d/%m/%Y') as date, log_date
                        FROM calorie_balance_history
                        WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                        ORDER BY log_date";
                $data = fetchAll($sql, [$user_id, $days]);
                break;
                
            case 'bmi':
                // Données d'IMC
                $sql = "SELECT bmi, category, DATE_FORMAT(log_date, '%d/%m/%Y') as date, log_date
                        FROM bmi_history
                        WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                        ORDER BY log_date";
                $data = fetchAll($sql, [$user_id, $days]);
                break;
                
            case 'exercise':
                // Données d'exercice
                $sql = "SELECT SUM(calories_burned) as total_burned, 
                        DATE_FORMAT(log_date, '%d/%m/%Y') as date, DATE(log_date) as log_date
                        FROM exercise_logs
                        WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                        GROUP BY DATE(log_date)
                        ORDER BY log_date";
                $data = fetchAll($sql, [$user_id, $days]);
                break;
        }
        
        return $data;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des données de progression: " . $e->getMessage());
        return [];
    }
}

/**
 * Génère les données pour le tableau de bord de l'utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Données du tableau de bord
 */
function generateUserDashboardData($user_id) {
    try {
        $dashboard = [];
        
        // Récupérer le profil de l'utilisateur
        $sql = "SELECT u.username, u.email, u.created_at, u.last_login,
                up.first_name, up.last_name, up.gender, up.birth_date, up.height, up.activity_level,
                (SELECT weight FROM weight_logs WHERE user_id = u.id ORDER BY log_date DESC LIMIT 1) as current_weight,
                (SELECT log_date FROM weight_logs WHERE user_id = u.id ORDER BY log_date DESC LIMIT 1) as last_weight_date
                FROM users u
                LEFT JOIN user_profiles up ON u.id = up.user_id
                WHERE u.id = ?";
        
        $dashboard['profile'] = fetchOne($sql, [$user_id]);
        
        // Calculer l'âge
        if (isset($dashboard['profile']['birth_date'])) {
            $birth_date = new DateTime($dashboard['profile']['birth_date']);
            $today = new DateTime();
            $dashboard['profile']['age'] = $birth_date->diff($today)->y;
        }
        
        // Récupérer les besoins caloriques
        $sql = "SELECT * FROM user_calorie_needs WHERE user_id = ?";
        $dashboard['calorie_needs'] = fetchOne($sql, [$user_id]);
        
        // Récupérer le bilan calorique du jour
        $dashboard['today_balance'] = updateDailyCalorieBalance($user_id);
        
        // Récupérer l'IMC actuel
        if (isset($dashboard['profile']['current_weight']) && isset($dashboard['profile']['height'])) {
            $dashboard['bmi'] = calculateBMI($dashboard['profile']['current_weight'], $dashboard['profile']['height']);
            
            // Mettre à jour l'historique de l'IMC
            updateBMIHistory(
                $user_id, 
                $dashboard['profile']['current_weight'], 
                $dashboard['profile']['height'], 
                $dashboard['bmi']['bmi'], 
                $dashboard['bmi']['category']
            );
        }
        
        // Récupérer les statistiques d'activité
        $sql = "SELECT 
                (SELECT COUNT(*) FROM weight_logs WHERE user_id = ?) as weight_entries,
                (SELECT COUNT(*) FROM food_logs WHERE user_id = ?) as food_entries,
                (SELECT COUNT(*) FROM exercise_logs WHERE user_id = ?) as exercise_entries,
                (SELECT COUNT(*) FROM meals WHERE user_id = ?) as meal_entries";
        
        $dashboard['activity_stats'] = fetchOne($sql, [$user_id, $user_id, $user_id, $user_id]);
        
        // Récupérer les données de progression pour les graphiques
        $dashboard['weight_chart'] = getProgressChartData($user_id, 'weight', 30);
        $dashboard['calories_chart'] = getProgressChartData($user_id, 'calories', 30);
        $dashboard['macros_chart'] = getProgressChartData($user_id, 'macros', 30);
        $dashboard['bmi_chart'] = getProgressChartData($user_id, 'bmi', 30);
        
        // Récupérer les repas d'aujourd'hui
        $dashboard['today_meals'] = getUserMeals($user_id);
        
        // Récupérer les exercices d'aujourd'hui
        $sql = "SELECT * FROM exercise_logs WHERE user_id = ? AND DATE(log_date) = CURDATE() ORDER BY log_date DESC";
        $dashboard['today_exercises'] = fetchAll($sql, [$user_id]);
        
        // Calculer les tendances
        if (count($dashboard['weight_chart']) >= 2) {
            $first_weight = $dashboard['weight_chart'][0]['weight'];
            $last_weight = $dashboard['weight_chart'][count($dashboard['weight_chart']) - 1]['weight'];
            $dashboard['weight_trend'] = $last_weight - $first_weight;
        }
        
        if (count($dashboard['calories_chart']) >= 7) {
            // Moyenne des 7 derniers jours
            $last_week = array_slice($dashboard['calories_chart'], -7);
            $total_calories = 0;
            $count = 0;
            
            foreach ($last_week as $day) {
                if (isset($day['calories_consumed'])) {
                    $total_calories += $day['calories_consumed'];
                    $count++;
                }
            }
            
            $dashboard['avg_daily_calories'] = $count > 0 ? round($total_calories / $count) : 0;
        }
        
        // Récupérer les objectifs de l'utilisateur
        $sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
        $dashboard['current_goal'] = fetchOne($sql, [$user_id]);
        
        if ($dashboard['current_goal'] && isset($dashboard['profile']['current_weight'])) {
            $dashboard['current_goal']['progress'] = 0;
            
            if ($dashboard['current_goal']['target_weight'] > 0) {
                $start_weight = $dashboard['current_goal']['start_weight'];
                $target_weight = $dashboard['current_goal']['target_weight'];
                $current_weight = $dashboard['profile']['current_weight'];
                
                // Calculer le pourcentage de progression
                $total_change = abs($target_weight - $start_weight);
                $current_change = abs($current_weight - $start_weight);
                
                if ($total_change > 0) {
                    $dashboard['current_goal']['progress'] = round(($current_change / $total_change) * 100);
                    
                    // Limiter à 100%
                    if ($dashboard['current_goal']['progress'] > 100) {
                        $dashboard['current_goal']['progress'] = 100;
                    }
                }
            }
        }
        
        return $dashboard;
    } catch (Exception $e) {
        error_log("Erreur lors de la génération des données du tableau de bord: " . $e->getMessage());
        return [];
    }
}

/**
 * Génère un rapport de progression pour une période donnée
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $start_date Date de début (format Y-m-d)
 * @param string $end_date Date de fin (format Y-m-d)
 * @return array Données du rapport
 */
function generateProgressReport($user_id, $start_date, $end_date) {
    try {
        $report = [];
        
        // Récupérer le profil de l'utilisateur
        $sql = "SELECT u.username, u.email,
                up.first_name, up.last_name, up.gender, up.birth_date, up.height, up.activity_level
                FROM users u
                LEFT JOIN user_profiles up ON u.id = up.user_id
                WHERE u.id = ?";
        
        $report['profile'] = fetchOne($sql, [$user_id]);
        
        // Récupérer les poids au début et à la fin de la période
        $sql = "SELECT weight FROM weight_logs 
                WHERE user_id = ? AND log_date <= ? 
                ORDER BY log_date DESC LIMIT 1";
        $start_weight = fetchOne($sql, [$user_id, $start_date]);
        
        $sql = "SELECT weight FROM weight_logs 
                WHERE user_id = ? AND log_date <= ? 
                ORDER BY log_date DESC LIMIT 1";
        $end_weight = fetchOne($sql, [$user_id, $end_date]);
        
        $report['weight_change'] = [
            'start_weight' => $start_weight ? $start_weight['weight'] : 0,
            'end_weight' => $end_weight ? $end_weight['weight'] : 0,
            'difference' => $start_weight && $end_weight ? $end_weight['weight'] - $start_weight['weight'] : 0
        ];
        
        // Récupérer les IMC au début et à la fin de la période
        $sql = "SELECT bmi FROM bmi_history 
                WHERE user_id = ? AND log_date <= ? 
                ORDER BY log_date DESC LIMIT 1";
        $start_bmi = fetchOne($sql, [$user_id, $start_date]);
        
        $sql = "SELECT bmi, category FROM bmi_history 
                WHERE user_id = ? AND log_date <= ? 
                ORDER BY log_date DESC LIMIT 1";
        $end_bmi = fetchOne($sql, [$user_id, $end_date]);
        
        $report['bmi_change'] = [
            'start_bmi' => $start_bmi ? $start_bmi['bmi'] : 0,
            'end_bmi' => $end_bmi ? $end_bmi['bmi'] : 0,
            'end_category' => $end_bmi ? $end_bmi['category'] : '',
            'difference' => $start_bmi && $end_bmi ? $end_bmi['bmi'] - $start_bmi['bmi'] : 0
        ];
        
        // Statistiques caloriques pour la période
        $sql = "SELECT 
                AVG(calories_consumed) as avg_calories_consumed,
                AVG(calories_burned) as avg_calories_burned,
                AVG(balance) as avg_balance,
                AVG(protein_consumed) as avg_protein,
                AVG(carbs_consumed) as avg_carbs,
                AVG(fat_consumed) as avg_fat,
                AVG(protein_pct) as avg_protein_pct,
                AVG(carbs_pct) as avg_carbs_pct,
                AVG(fat_pct) as avg_fat_pct
                FROM calorie_balance_history
                WHERE user_id = ? AND log_date BETWEEN ? AND ?";
        
        $report['calorie_stats'] = fetchOne($sql, [$user_id, $start_date, $end_date]);
        
        if ($report['calorie_stats']) {
            foreach ($report['calorie_stats'] as $key => $value) {
                if (strpos($key, 'avg_') === 0) {
                    $report['calorie_stats'][$key] = round($value, 1);
                }
            }
        }
        
        // Statistiques d'exercice pour la période
        $sql = "SELECT 
                COUNT(*) as total_exercises,
                SUM(duration) as total_duration,
                SUM(calories_burned) as total_calories_burned,
                AVG(calories_burned) as avg_calories_burned,
                COUNT(DISTINCT DATE(log_date)) as active_days
                FROM exercise_logs
                WHERE user_id = ? AND log_date BETWEEN ? AND ?";
        
        $report['exercise_stats'] = fetchOne($sql, [$user_id, $start_date, $end_date]);
        
        // Calculer le nombre total de jours dans la période
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $total_days = $interval->days + 1; // +1 pour inclure le jour de fin
        
        $report['period_info'] = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_days' => $total_days,
            'active_days' => $report['exercise_stats']['active_days'] ?? 0,
            'activity_rate' => $total_days > 0 ? round((($report['exercise_stats']['active_days'] ?? 0) / $total_days) * 100) : 0
        ];
        
        // Récupérer les données pour les graphiques
        $report['weight_chart'] = getProgressChartData($user_id, 'weight', $total_days);
        $report['calories_chart'] = getProgressChartData($user_id, 'calories', $total_days);
        $report['exercise_chart'] = getProgressChartData($user_id, 'exercise', $total_days);
        
        return $report;
    } catch (Exception $e) {
        error_log("Erreur lors de la génération du rapport de progression: " . $e->getMessage());
        return [];
    }
}

/**
 * Met à jour automatiquement l'IMC lorsqu'un nouveau poids est enregistré
 * 
 * @param int $user_id ID de l'utilisateur
 * @param float $weight Nouveau poids
 * @param string $log_date Date de l'entrée (format Y-m-d)
 * @return bool True si la mise à jour a réussi, false sinon
 */
function updateBMIOnWeightChange($user_id, $weight, $log_date = null) {
    try {
        // Récupérer la taille de l'utilisateur
        $sql = "SELECT height FROM user_profiles WHERE user_id = ?";
        $profile = fetchOne($sql, [$user_id]);
        
        if (!$profile || !isset($profile['height']) || $profile['height'] <= 0) {
            return false;
        }
        
        // Calculer l'IMC
        $bmi_data = calculateBMI($weight, $profile['height']);
        
        // Mettre à jour l'historique de l'IMC
        return updateBMIHistory(
            $user_id, 
            $weight, 
            $profile['height'], 
            $bmi_data['bmi'], 
            $bmi_data['category'],
            $log_date
        ) > 0;
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour de l'IMC: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère des données pour les widgets du tableau de bord
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Données des widgets
 */
function generateDashboardWidgets($user_id) {
    try {
        $widgets = [];
        
        // Widget de tendance de poids
        $sql = "SELECT 
                (SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1) as current_weight,
                (SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1 OFFSET 1) as previous_weight,
                (SELECT weight FROM weight_logs WHERE user_id = ? AND log_date <= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY log_date DESC LIMIT 1) as week_ago_weight,
                (SELECT weight FROM weight_logs WHERE user_id = ? AND log_date <= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY log_date DESC LIMIT 1) as month_ago_weight";
        
        $weight_data = fetchOne($sql, [$user_id, $user_id, $user_id, $user_id]);
        
        if ($weight_data) {
            $widgets['weight_trend'] = [
                'current' => $weight_data['current_weight'],
                'previous' => $weight_data['previous_weight'],
                'week_ago' => $weight_data['week_ago_weight'],
                'month_ago' => $weight_data['month_ago_weight'],
                'change_last' => $weight_data['previous_weight'] ? $weight_data['current_weight'] - $weight_data['previous_weight'] : 0,
                'change_week' => $weight_data['week_ago_weight'] ? $weight_data['current_weight'] - $weight_data['week_ago_weight'] : 0,
                'change_month' => $weight_data['month_ago_weight'] ? $weight_data['current_weight'] - $weight_data['month_ago_weight'] : 0
            ];
        }
        
        // Widget de tendance calorique
        $sql = "SELECT 
                AVG(calories_consumed) as avg_calories,
                AVG(balance) as avg_balance
                FROM calorie_balance_history
                WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        
        $calorie_data = fetchOne($sql, [$user_id]);
        
        if ($calorie_data) {
            $widgets['calorie_trend'] = [
                'avg_calories' => round($calorie_data['avg_calories']),
                'avg_balance' => round($calorie_data['avg_balance'])
            ];
            
            // Récupérer l'objectif calorique
            $sql = "SELECT goal_calories FROM user_calorie_needs WHERE user_id = ?";
            $needs = fetchOne($sql, [$user_id]);
            
            if ($needs) {
                $widgets['calorie_trend']['goal_calories'] = $needs['goal_calories'];
                $widgets['calorie_trend']['avg_difference'] = round($calorie_data['avg_calories'] - $needs['goal_calories']);
            }
        }
        
        // Widget d'activité physique
        $sql = "SELECT 
                COUNT(*) as total_exercises,
                SUM(duration) as total_duration,
                SUM(calories_burned) as total_calories,
                COUNT(DISTINCT DATE(log_date)) as active_days
                FROM exercise_logs
                WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        
        $exercise_data = fetchOne($sql, [$user_id]);
        
        if ($exercise_data) {
            $widgets['exercise_summary'] = [
                'total_exercises' => $exercise_data['total_exercises'],
                'total_duration' => $exercise_data['total_duration'],
                'total_calories' => $exercise_data['total_calories'],
                'active_days' => $exercise_data['active_days'],
                'activity_rate' => round(($exercise_data['active_days'] / 7) * 100)
            ];
        }
        
        // Widget de répartition des macronutriments
        $sql = "SELECT 
                AVG(protein_pct) as avg_protein_pct,
                AVG(carbs_pct) as avg_carbs_pct,
                AVG(fat_pct) as avg_fat_pct
                FROM calorie_balance_history
                WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        
        $macros_data = fetchOne($sql, [$user_id]);
        
        if ($macros_data) {
            $widgets['macros_distribution'] = [
                'protein_pct' => round($macros_data['avg_protein_pct']),
                'carbs_pct' => round($macros_data['avg_carbs_pct']),
                'fat_pct' => round($macros_data['avg_fat_pct'])
            ];
        }
        
        return $widgets;
    } catch (Exception $e) {
        error_log("Erreur lors de la génération des widgets du tableau de bord: " . $e->getMessage());
        return [];
    }
}

/**
 * Génère des suggestions personnalisées basées sur les données de l'utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Suggestions personnalisées
 */
function generatePersonalizedSuggestions($user_id) {
    try {
        $suggestions = [];
        
        // Récupérer les données de l'utilisateur
        $dashboard = generateUserDashboardData($user_id);
        
        // Suggestion basée sur la tendance de poids
        if (isset($dashboard['weight_trend'])) {
            $weight_trend = $dashboard['weight_trend'];
            
            // Récupérer l'objectif de l'utilisateur
            $current_goal = $dashboard['current_goal'] ?? null;
            
            if ($current_goal) {
                $goal_type = $current_goal['goal_type'];
                
                // Vérifier si la tendance de poids correspond à l'objectif
                if (($goal_type === 'perte_poids' && $weight_trend > 0) || 
                    ($goal_type === 'prise_poids' && $weight_trend < 0)) {
                    
                    $suggestions[] = [
                        'type' => 'weight_trend',
                        'title' => 'Ajustez votre apport calorique',
                        'message' => $goal_type === 'perte_poids' 
                            ? 'Votre poids augmente alors que votre objectif est de perdre du poids. Essayez de réduire votre apport calorique quotidien d\'environ 200-300 calories.'
                            : 'Votre poids diminue alors que votre objectif est de prendre du poids. Essayez d\'augmenter votre apport calorique quotidien d\'environ 200-300 calories.'
                    ];
                }
            }
        }
        
        // Suggestion basée sur la répartition des macronutriments
        if (isset($dashboard['today_balance'])) {
            $macros = $dashboard['today_balance'];
            
            if ($macros['protein_pct'] < 20) {
                $suggestions[] = [
                    'type' => 'macros',
                    'title' => 'Augmentez votre apport en protéines',
                    'message' => 'Votre apport en protéines est faible (moins de 20% de vos calories). Les protéines sont essentielles pour la récupération musculaire et la satiété. Essayez d\'inclure plus d\'aliments riches en protéines comme le poulet, les œufs, le poisson ou les légumineuses.'
                ];
            }
            
            if ($macros['fat_pct'] > 40) {
                $suggestions[] = [
                    'type' => 'macros',
                    'title' => 'Réduisez votre apport en lipides',
                    'message' => 'Votre apport en lipides est élevé (plus de 40% de vos calories). Bien que les graisses saines soient importantes, un excès peut contribuer à un apport calorique trop élevé. Essayez de réduire les aliments frits et les graisses saturées.'
                ];
            }
        }
        
        // Suggestion basée sur l'activité physique
        $sql = "SELECT COUNT(*) as exercise_count 
                FROM exercise_logs 
                WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        
        $exercise_data = fetchOne($sql, [$user_id]);
        
        if ($exercise_data && $exercise_data['exercise_count'] < 3) {
            $suggestions[] = [
                'type' => 'exercise',
                'title' => 'Augmentez votre activité physique',
                'message' => 'Vous avez enregistré moins de 3 séances d\'exercice au cours des 7 derniers jours. L\'activité physique régulière est essentielle pour la santé et la gestion du poids. Essayez d\'ajouter au moins 30 minutes d\'activité modérée 3 à 5 fois par semaine.'
            ];
        }
        
        // Suggestion basée sur la régularité des repas
        $sql = "SELECT COUNT(DISTINCT DATE(log_date)) as meal_days 
                FROM meals 
                WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        
        $meal_data = fetchOne($sql, [$user_id]);
        
        if ($meal_data && $meal_data['meal_days'] < 5) {
            $suggestions[] = [
                'type' => 'meals',
                'title' => 'Suivez régulièrement vos repas',
                'message' => 'Vous n\'avez pas enregistré vos repas tous les jours. Le suivi régulier de votre alimentation est l\'un des facteurs les plus importants pour atteindre vos objectifs. Essayez de noter tous vos repas, même les petites collations.'
            ];
        }
        
        // Suggestion basée sur l'IMC
        if (isset($dashboard['bmi'])) {
            $bmi = $dashboard['bmi']['bmi'];
            $category = $dashboard['bmi']['category'];
            
            if ($bmi > 25 && $bmi < 30) {
                $suggestions[] = [
                    'type' => 'bmi',
                    'title' => 'Votre IMC indique un surpoids',
                    'message' => 'Votre IMC actuel est de ' . $bmi . ', ce qui correspond à la catégorie "' . $category . '". Une légère perte de poids pourrait améliorer votre santé globale. Visez une perte de 0,5 à 1 kg par semaine en combinant une alimentation équilibrée et de l\'exercice régulier.'
                ];
            } else if ($bmi >= 30) {
                $suggestions[] = [
                    'type' => 'bmi',
                    'title' => 'Votre IMC indique une obésité',
                    'message' => 'Votre IMC actuel est de ' . $bmi . ', ce qui correspond à la catégorie "' . $category . '". Il est recommandé de consulter un professionnel de santé pour établir un plan de perte de poids adapté à votre situation.'
                ];
            } else if ($bmi < 18.5) {
                $suggestions[] = [
                    'type' => 'bmi',
                    'title' => 'Votre IMC indique une insuffisance pondérale',
                    'message' => 'Votre IMC actuel est de ' . $bmi . ', ce qui correspond à la catégorie "' . $category . '". Il est important d\'augmenter votre apport calorique de manière saine pour atteindre un poids normal. Concentrez-vous sur des aliments nutritifs et riches en calories.'
                ];
            }
        }
        
        return $suggestions;
    } catch (Exception $e) {
        error_log("Erreur lors de la génération des suggestions personnalisées: " . $e->getMessage());
        return [];
    }
}
