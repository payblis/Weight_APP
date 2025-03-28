<?php
/**
 * Fonctions pour le système de gestion des repas amélioré
 */

/**
 * Crée un nouveau repas
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $name Nom du repas
 * @param string $meal_type Type de repas (petit_dejeuner, dejeuner, diner, collation)
 * @param string $log_date Date du repas (format Y-m-d)
 * @param string $description Description du repas (optionnel)
 * @return int|false ID du repas créé ou false en cas d'erreur
 */
function createMeal($user_id, $name, $meal_type, $log_date, $description = '') {
    try {
        $sql = "INSERT INTO meals (user_id, name, description, meal_type, log_date, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        return insert($sql, [$user_id, $name, $description, $meal_type, $log_date]);
    } catch (Exception $e) {
        error_log("Erreur lors de la création du repas: " . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute un aliment à un repas
 * 
 * @param int $meal_id ID du repas
 * @param int $food_id ID de l'aliment (0 pour un aliment personnalisé)
 * @param float $quantity Quantité en grammes
 * @param string $custom_food_name Nom de l'aliment personnalisé (si food_id = 0)
 * @param int $custom_calories Calories de l'aliment personnalisé
 * @param float $custom_protein Protéines de l'aliment personnalisé
 * @param float $custom_carbs Glucides de l'aliment personnalisé
 * @param float $custom_fat Lipides de l'aliment personnalisé
 * @return int|false ID de l'entrée alimentaire créée ou false en cas d'erreur
 */
function addFoodToMeal($meal_id, $food_id, $quantity, $custom_food_name = '', $custom_calories = 0, $custom_protein = 0, $custom_carbs = 0, $custom_fat = 0) {
    try {
        // Récupérer les informations du repas
        $sql = "SELECT user_id, log_date FROM meals WHERE id = ?";
        $meal = fetchOne($sql, [$meal_id]);
        
        if (!$meal) {
            return false;
        }
        
        $user_id = $meal['user_id'];
        $log_date = $meal['log_date'];
        
        // Insérer l'aliment dans le journal alimentaire
        $sql = "INSERT INTO food_logs (user_id, food_id, custom_name, quantity, custom_calories, custom_protein, custom_carbs, custom_fat, log_date, meal_id, is_part_of_meal, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
        
        $food_log_id = insert($sql, [
            $user_id, 
            $food_id > 0 ? $food_id : null, 
            !empty($custom_food_name) ? $custom_food_name : null, 
            $quantity, 
            $custom_calories, 
            $custom_protein, 
            $custom_carbs, 
            $custom_fat, 
            $log_date,
            $meal_id
        ]);
        
        if ($food_log_id) {
            // Mettre à jour les totaux du repas
            updateMealTotals($meal_id);
        }
        
        return $food_log_id;
    } catch (Exception $e) {
        error_log("Erreur lors de l'ajout d'un aliment au repas: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour les totaux nutritionnels d'un repas
 * 
 * @param int $meal_id ID du repas
 * @return bool True si la mise à jour a réussi, false sinon
 */
function updateMealTotals($meal_id) {
    try {
        // Calculer les totaux à partir des aliments du repas
        $sql = "SELECT 
                SUM(CASE 
                    WHEN fl.food_id IS NOT NULL THEN (f.calories * fl.quantity / 100)
                    WHEN fl.custom_calories IS NOT NULL THEN fl.custom_calories
                    ELSE 0
                END) as total_calories,
                
                SUM(CASE 
                    WHEN fl.food_id IS NOT NULL THEN (f.protein * fl.quantity / 100)
                    WHEN fl.custom_protein IS NOT NULL THEN fl.custom_protein
                    ELSE 0
                END) as total_protein,
                
                SUM(CASE 
                    WHEN fl.food_id IS NOT NULL THEN (f.carbs * fl.quantity / 100)
                    WHEN fl.custom_carbs IS NOT NULL THEN fl.custom_carbs
                    ELSE 0
                END) as total_carbs,
                
                SUM(CASE 
                    WHEN fl.food_id IS NOT NULL THEN (f.fat * fl.quantity / 100)
                    WHEN fl.custom_fat IS NOT NULL THEN fl.custom_fat
                    ELSE 0
                END) as total_fat
                
                FROM food_logs fl
                LEFT JOIN foods f ON fl.food_id = f.id
                WHERE fl.meal_id = ? AND fl.is_part_of_meal = 1";
        
        $totals = fetchOne($sql, [$meal_id]);
        
        if (!$totals) {
            $totals = [
                'total_calories' => 0,
                'total_protein' => 0,
                'total_carbs' => 0,
                'total_fat' => 0
            ];
        }
        
        // Mettre à jour les totaux du repas
        $sql = "UPDATE meals 
                SET total_calories = ?, total_protein = ?, total_carbs = ?, total_fat = ?, updated_at = NOW() 
                WHERE id = ?";
        
        return update($sql, [
            round($totals['total_calories']),
            round($totals['total_protein'], 1),
            round($totals['total_carbs'], 1),
            round($totals['total_fat'], 1),
            $meal_id
        ]) > 0;
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour des totaux du repas: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un aliment d'un repas
 * 
 * @param int $food_log_id ID de l'entrée alimentaire
 * @return bool True si la suppression a réussi, false sinon
 */
function removeFoodFromMeal($food_log_id) {
    try {
        // Récupérer l'ID du repas avant de supprimer l'entrée
        $sql = "SELECT meal_id FROM food_logs WHERE id = ?";
        $food_log = fetchOne($sql, [$food_log_id]);
        
        if (!$food_log || !$food_log['meal_id']) {
            return false;
        }
        
        $meal_id = $food_log['meal_id'];
        
        // Supprimer l'entrée alimentaire
        $sql = "DELETE FROM food_logs WHERE id = ?";
        $result = delete($sql, [$food_log_id]);
        
        if ($result) {
            // Mettre à jour les totaux du repas
            updateMealTotals($meal_id);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Erreur lors de la suppression d'un aliment du repas: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les détails d'un repas avec ses aliments
 * 
 * @param int $meal_id ID du repas
 * @return array|false Détails du repas ou false en cas d'erreur
 */
function getMealDetails($meal_id) {
    try {
        // Récupérer les informations du repas
        $sql = "SELECT m.*, DATE_FORMAT(m.log_date, '%d/%m/%Y') as formatted_date 
                FROM meals m 
                WHERE m.id = ?";
        $meal = fetchOne($sql, [$meal_id]);
        
        if (!$meal) {
            return false;
        }
        
        // Récupérer les aliments du repas
        $sql = "SELECT fl.*, 
                f.name as food_name, f.calories as food_calories, f.protein as food_protein, f.carbs as food_carbs, f.fat as food_fat,
                CASE 
                    WHEN fl.food_id IS NOT NULL THEN (f.calories * fl.quantity / 100)
                    WHEN fl.custom_calories IS NOT NULL THEN fl.custom_calories
                    ELSE 0
                END as calculated_calories,
                CASE 
                    WHEN fl.food_id IS NOT NULL THEN (f.protein * fl.quantity / 100)
                    WHEN fl.custom_protein IS NOT NULL THEN fl.custom_protein
                    ELSE 0
                END as calculated_protein,
                CASE 
                    WHEN fl.food_id IS NOT NULL THEN (f.carbs * fl.quantity / 100)
                    WHEN fl.custom_carbs IS NOT NULL THEN fl.custom_carbs
                    ELSE 0
                END as calculated_carbs,
                CASE 
                    WHEN fl.food_id IS NOT NULL THEN (f.fat * fl.quantity / 100)
                    WHEN fl.custom_fat IS NOT NULL THEN fl.custom_fat
                    ELSE 0
                END as calculated_fat
                FROM food_logs fl
                LEFT JOIN foods f ON fl.food_id = f.id
                WHERE fl.meal_id = ? AND fl.is_part_of_meal = 1
                ORDER BY fl.created_at";
        
        $meal['foods'] = fetchAll($sql, [$meal_id]);
        
        return $meal;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des détails du repas: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les repas d'un utilisateur pour une date donnée
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $date Date au format Y-m-d (optionnel, par défaut aujourd'hui)
 * @return array Liste des repas
 */
function getUserMeals($user_id, $date = null) {
    try {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $sql = "SELECT m.*, 
                DATE_FORMAT(m.log_date, '%d/%m/%Y') as formatted_date,
                (SELECT COUNT(*) FROM food_logs fl WHERE fl.meal_id = m.id AND fl.is_part_of_meal = 1) as food_count
                FROM meals m 
                WHERE m.user_id = ? AND m.log_date = ?
                ORDER BY FIELD(m.meal_type, 'petit_dejeuner', 'dejeuner', 'diner', 'collation'), m.created_at";
        
        return fetchAll($sql, [$user_id, $date]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des repas de l'utilisateur: " . $e->getMessage());
        return [];
    }
}

/**
 * Crée un repas prédéfini à partir d'un repas existant
 * 
 * @param int $meal_id ID du repas existant
 * @param string $name Nom du repas prédéfini (optionnel, utilise le nom du repas existant par défaut)
 * @param string $description Description du repas prédéfini (optionnel)
 * @param bool $is_public Si le repas prédéfini est public (accessible à tous les utilisateurs)
 * @return int|false ID du repas prédéfini créé ou false en cas d'erreur
 */
function createPredefinedMealFromExisting($meal_id, $name = '', $description = '', $is_public = false) {
    try {
        // Récupérer les informations du repas existant
        $meal = getMealDetails($meal_id);
        
        if (!$meal) {
            return false;
        }
        
        // Utiliser le nom du repas existant si aucun nom n'est fourni
        if (empty($name)) {
            $name = $meal['name'];
        }
        
        // Créer le repas prédéfini
        $sql = "INSERT INTO predefined_meals (name, description, meal_type, calories, protein, carbs, fat, created_by_user, created_by_admin, is_public, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $predefined_meal_id = insert($sql, [
            $name,
            $description,
            $meal['meal_type'],
            $meal['total_calories'],
            $meal['total_protein'],
            $meal['total_carbs'],
            $meal['total_fat'],
            $meal['user_id'],
            0, // created_by_admin = false
            $is_public ? 1 : 0,
        ]);
        
        if (!$predefined_meal_id) {
            return false;
        }
        
        // Ajouter les aliments au repas prédéfini
        foreach ($meal['foods'] as $food) {
            $sql = "INSERT INTO predefined_meal_items (predefined_meal_id, food_id, custom_food_name, quantity, custom_calories, custom_protein, custom_carbs, custom_fat, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            insert($sql, [
                $predefined_meal_id,
                $food['food_id'],
                $food['custom_name'],
                $food['quantity'],
                $food['custom_calories'],
                $food['custom_protein'],
                $food['custom_carbs'],
                $food['custom_fat']
            ]);
        }
        
        return $predefined_meal_id;
    } catch (Exception $e) {
        error_log("Erreur lors de la création du repas prédéfini: " . $e->getMessage());
        return false;
    }
}

/**
 * Crée un repas à partir d'un repas prédéfini
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $predefined_meal_id ID du repas prédéfini
 * @param string $log_date Date du repas (format Y-m-d)
 * @return int|false ID du repas créé ou false en cas d'erreur
 */
function createMealFromPredefined($user_id, $predefined_meal_id, $log_date) {
    try {
        // Récupérer les informations du repas prédéfini
        $sql = "SELECT * FROM predefined_meals WHERE id = ?";
        $predefined_meal = fetchOne($sql, [$predefined_meal_id]);
        
        if (!$predefined_meal) {
            return false;
        }
        
        // Créer le repas
        $sql = "INSERT INTO meals (user_id, name, description, meal_type, log_date, total_calories, total_protein, total_carbs, total_fat, is_predefined, predefined_meal_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())";
        
        $meal_id = insert($sql, [
            $user_id,
            $predefined_meal['name'],
            $predefined_meal['description'],
            $predefined_meal['meal_type'],
            $log_date,
            $predefined_meal['calories'],
            $predefined_meal['protein'],
            $predefined_meal['carbs'],
            $predefined_meal['fat'],
            $predefined_meal_id
        ]);
        
        if (!$meal_id) {
            return false;
        }
        
        // Récupérer les aliments du repas prédéfini
        $sql = "SELECT pmi.*, f.name as food_name 
                FROM predefined_meal_items pmi
                LEFT JOIN foods f ON pmi.food_id = f.id
                WHERE pmi.predefined_meal_id = ?";
        
        $items = fetchAll($sql, [$predefined_meal_id]);
        
        // Ajouter les aliments au repas
        foreach ($items as $item) {
            $sql = "INSERT INTO food_logs (user_id, food_id, custom_name, quantity, custom_calories, custom_protein, custom_carbs, custom_fat, log_date, meal_id, is_part_of_meal, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            
            insert($sql, [
                $user_id,
                $item['food_id'],
                $item['custom_food_name'],
                $item['quantity'],
                $item['custom_calories'],
                $item['custom_protein'],
                $item['custom_carbs'],
                $item['custom_fat'],
                $log_date,
                $meal_id
            ]);
        }
        
        return $meal_id;
    } catch (Exception $e) {
        error_log("Erreur lors de la création du repas à partir d'un repas prédéfini: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les repas prédéfinis disponibles pour un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $meal_type Type de repas (optionnel)
 * @param bool $include_public Inclure les repas publics
 * @return array Liste des repas prédéfinis
 */
function getAvailablePredefinedMeals($user_id, $meal_type = null, $include_public = true) {
    try {
        $params = [$user_id];
        $where_clauses = ["(created_by_user = ?"];
        
        if ($include_public) {
            $where_clauses[] = "is_public = 1";
        }
        
        $where_sql = implode(" OR ", $where_clauses) . ")";
        
        if ($meal_type) {
            $where_sql .= " AND meal_type = ?";
            $params[] = $meal_type;
        }
        
        $sql = "SELECT pm.*, 
                (SELECT COUNT(*) FROM predefined_meal_items pmi WHERE pmi.predefined_meal_id = pm.id) as item_count,
                (SELECT COUNT(*) FROM user_favorite_meals ufm WHERE ufm.predefined_meal_id = pm.id AND ufm.user_id = ?) as is_favorite
                FROM predefined_meals pm
                WHERE $where_sql
                ORDER BY pm.created_at DESC";
        
        // Ajouter l'ID utilisateur pour la sous-requête is_favorite
        array_unshift($params, $user_id);
        
        return fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des repas prédéfinis: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les détails d'un repas prédéfini
 * 
 * @param int $predefined_meal_id ID du repas prédéfini
 * @return array|false Détails du repas prédéfini ou false en cas d'erreur
 */
function getPredefinedMealDetails($predefined_meal_id) {
    try {
        // Récupérer les informations du repas prédéfini
        $sql = "SELECT pm.*, u.username as created_by_username 
                FROM predefined_meals pm
                LEFT JOIN users u ON pm.created_by_user = u.id
                WHERE pm.id = ?";
        
        $meal = fetchOne($sql, [$predefined_meal_id]);
        
        if (!$meal) {
            return false;
        }
        
        // Récupérer les aliments du repas prédéfini
        $sql = "SELECT pmi.*, 
                f.name as food_name, f.calories as food_calories, f.protein as food_protein, f.carbs as food_carbs, f.fat as food_fat,
                CASE 
                    WHEN pmi.food_id IS NOT NULL THEN (f.calories * pmi.quantity / 100)
                    WHEN pmi.custom_calories IS NOT NULL THEN pmi.custom_calories
                    ELSE 0
                END as calculated_calories,
                CASE 
                    WHEN pmi.food_id IS NOT NULL THEN (f.protein * pmi.quantity / 100)
                    WHEN pmi.custom_protein IS NOT NULL THEN pmi.custom_protein
                    ELSE 0
                END as calculated_protein,
                CASE 
                    WHEN pmi.food_id IS NOT NULL THEN (f.carbs * pmi.quantity / 100)
                    WHEN pmi.custom_carbs IS NOT NULL THEN pmi.custom_carbs
                    ELSE 0
                END as calculated_carbs,
                CASE 
                    WHEN pmi.food_id IS NOT NULL THEN (f.fat * pmi.quantity / 100)
                    WHEN pmi.custom_fat IS NOT NULL THEN pmi.custom_fat
                    ELSE 0
                END as calculated_fat
                FROM predefined_meal_items pmi
                LEFT JOIN foods f ON pmi.food_id = f.id
                WHERE pmi.predefined_meal_id = ?
                ORDER BY pmi.created_at";
        
        $meal['items'] = fetchAll($sql, [$predefined_meal_id]);
        
        return $meal;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des détails du repas prédéfini: " . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute ou supprime un repas prédéfini des favoris d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $predefined_meal_id ID du repas prédéfini
 * @param bool $is_favorite True pour ajouter aux favoris, false pour supprimer
 * @return bool True si l'opération a réussi, false sinon
 */
function toggleFavoriteMeal($user_id, $predefined_meal_id, $is_favorite) {
    try {
        if ($is_favorite) {
            // Ajouter aux favoris
            $sql = "INSERT IGNORE INTO user_favorite_meals (user_id, predefined_meal_id, created_at) 
                    VALUES (?, ?, NOW())";
            return insert($sql, [$user_id, $predefined_meal_id]) > 0;
        } else {
            // Supprimer des favoris
            $sql = "DELETE FROM user_favorite_meals 
                    WHERE user_id = ? AND predefined_meal_id = ?";
            return delete($sql, [$user_id, $predefined_meal_id]) > 0;
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la modification des favoris: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les repas favoris d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Liste des repas favoris
 */
function getUserFavoriteMeals($user_id) {
    try {
        $sql = "SELECT pm.*, 
                (SELECT COUNT(*) FROM predefined_meal_items pmi WHERE pmi.predefined_meal_id = pm.id) as item_count,
                1 as is_favorite
                FROM predefined_meals pm
                JOIN user_favorite_meals ufm ON pm.id = ufm.predefined_meal_id
                WHERE ufm.user_id = ?
                ORDER BY ufm.created_at DESC";
        
        return fetchAll($sql, [$user_id]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des repas favoris: " . $e->getMessage());
        return [];
    }
}

/**
 * Importe les macronutriments d'un aliment via l'API ChatGPT
 * 
 * @param string $food_name Nom de l'aliment
 * @param float $quantity Quantité en grammes
 * @param string $api_key Clé API ChatGPT
 * @return array|false Macronutriments de l'aliment ou false en cas d'erreur
 */
function importFoodNutrientsFromChatGPT($food_name, $quantity, $api_key) {
    try {
        // Récupérer la clé API ChatGPT si non fournie
        if (empty($api_key)) {
            $sql = "SELECT value FROM settings WHERE setting_name = 'chatgpt_api_key'";
            $setting = fetchOne($sql, []);
            
            if (!$setting || empty($setting['value'])) {
                return false;
            }
            
            $api_key = $setting['value'];
        }
        
        // Préparer la requête à l'API ChatGPT
        $prompt = "Donne-moi les informations nutritionnelles pour {$quantity}g de {$food_name}. Réponds uniquement avec un objet JSON contenant les calories (kcal), protéines (g), glucides (g) et lipides (g). Format: {\"calories\": X, \"protein\": X, \"carbs\": X, \"fat\": X}";
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es un assistant nutritionnel qui fournit des informations précises sur les aliments.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3,
            'max_tokens' => 150
        ];
        
        // Initialiser cURL
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);
        
        // Exécuter la requête
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            error_log("Erreur API ChatGPT: HTTP code $http_code, réponse: $response");
            return false;
        }
        
        // Traiter la réponse
        $response_data = json_decode($response, true);
        
        if (!isset($response_data['choices'][0]['message']['content'])) {
            error_log("Réponse API ChatGPT invalide: " . json_encode($response_data));
            return false;
        }
        
        // Extraire les informations nutritionnelles
        $content = $response_data['choices'][0]['message']['content'];
        
        // Rechercher un objet JSON dans la réponse
        preg_match('/\{.*\}/s', $content, $matches);
        
        if (empty($matches)) {
            error_log("Aucun objet JSON trouvé dans la réponse: $content");
            return false;
        }
        
        $nutrients = json_decode($matches[0], true);
        
        if (!$nutrients || !isset($nutrients['calories'])) {
            error_log("Objet JSON invalide dans la réponse: " . $matches[0]);
            return false;
        }
        
        // Normaliser les valeurs
        return [
            'calories' => intval($nutrients['calories']),
            'protein' => floatval($nutrients['protein']),
            'carbs' => floatval($nutrients['carbs']),
            'fat' => floatval($nutrients['fat'])
        ];
    } catch (Exception $e) {
        error_log("Erreur lors de l'importation des macronutriments via ChatGPT: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcule les statistiques des repas d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $days Nombre de jours à analyser
 * @return array Statistiques des repas
 */
function getMealStatistics($user_id, $days = 30) {
    try {
        // Statistiques générales
        $sql = "SELECT 
                COUNT(DISTINCT DATE(log_date)) as total_days,
                COUNT(*) as total_meals,
                AVG(total_calories) as avg_calories_per_meal,
                SUM(total_calories) / COUNT(DISTINCT DATE(log_date)) as avg_calories_per_day,
                AVG(total_protein) as avg_protein_per_meal,
                AVG(total_carbs) as avg_carbs_per_meal,
                AVG(total_fat) as avg_fat_per_meal
                FROM meals 
                WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        
        $general_stats = fetchOne($sql, [$user_id, $days]);
        
        // Statistiques par type de repas
        $sql = "SELECT 
                meal_type,
                COUNT(*) as count,
                AVG(total_calories) as avg_calories,
                AVG(total_protein) as avg_protein,
                AVG(total_carbs) as avg_carbs,
                AVG(total_fat) as avg_fat
                FROM meals 
                WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY meal_type";
        
        $meal_type_stats = fetchAll($sql, [$user_id, $days]);
        
        // Repas les plus fréquents
        $sql = "SELECT 
                name,
                COUNT(*) as count,
                AVG(total_calories) as avg_calories
                FROM meals 
                WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY name
                ORDER BY count DESC
                LIMIT 5";
        
        $frequent_meals = fetchAll($sql, [$user_id, $days]);
        
        return [
            'general' => $general_stats,
            'by_type' => $meal_type_stats,
            'frequent' => $frequent_meals
        ];
    } catch (Exception $e) {
        error_log("Erreur lors du calcul des statistiques des repas: " . $e->getMessage());
        return [];
    }
}
