<?php
/**
 * Fonctions pour la gestion des programmes nutritionnels
 */

/**
 * Récupère tous les programmes nutritionnels disponibles
 * 
 * @param bool $include_system Inclure les programmes système
 * @param bool $include_user Inclure les programmes créés par les utilisateurs
 * @return array Liste des programmes nutritionnels
 */
function getAllNutritionPrograms($include_system = true, $include_user = true) {
    try {
        $where_clauses = [];
        $params = [];
        
        if ($include_system && !$include_user) {
            $where_clauses[] = "is_system = 1";
        } else if (!$include_system && $include_user) {
            $where_clauses[] = "is_system = 0";
        } else if (!$include_system && !$include_user) {
            return []; // Aucun programme à récupérer
        }
        
        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        
        $sql = "SELECT np.*, 
                u.username as creator_username,
                (SELECT COUNT(*) FROM user_profiles WHERE nutrition_program_id = np.id) as user_count
                FROM nutrition_programs np
                LEFT JOIN users u ON np.user_id = u.id
                $where_sql
                ORDER BY np.is_system DESC, np.created_at DESC";
        
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
        $sql = "SELECT np.*, 
                u.username as creator_username,
                (SELECT COUNT(*) FROM user_profiles WHERE nutrition_program_id = np.id) as user_count
                FROM nutrition_programs np
                LEFT JOIN users u ON np.user_id = u.id
                WHERE np.id = ?";
        
        $program = fetchOne($sql, [$program_id]);
        
        if (!$program) {
            return false;
        }
        
        // Récupérer les utilisateurs assignés à ce programme
        $sql = "SELECT u.id, u.username, up.first_name, up.last_name, 
                (SELECT weight FROM weight_logs WHERE user_id = u.id ORDER BY log_date DESC LIMIT 1) as current_weight
                FROM user_profiles up
                JOIN users u ON up.user_id = u.id
                WHERE up.nutrition_program_id = ?
                ORDER BY u.username";
        
        $program['assigned_users'] = fetchAll($sql, [$program_id]);
        
        return $program;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des détails du programme nutritionnel: " . $e->getMessage());
        return false;
    }
}

/**
 * Crée un nouveau programme nutritionnel
 * 
 * @param array $program_data Données du programme
 * @return int|false ID du programme créé ou false en cas d'erreur
 */
function createNutritionProgram($program_data) {
    try {
        // Valider les données requises
        if (!isset($program_data['name']) || empty($program_data['name']) ||
            !isset($program_data['goal_type']) || !in_array($program_data['goal_type'], ['perte_poids', 'prise_poids', 'maintien'])) {
            return false;
        }
        
        // Valeurs par défaut
        $user_id = $program_data['user_id'] ?? null;
        $description = $program_data['description'] ?? '';
        $calorie_adjustment = isset($program_data['calorie_adjustment']) ? intval($program_data['calorie_adjustment']) : 0;
        $is_system = isset($program_data['is_system']) ? ($program_data['is_system'] ? 1 : 0) : 0;
        
        // S'assurer que les pourcentages de macronutriments totalisent 100%
        $protein_pct = isset($program_data['protein_pct']) ? intval($program_data['protein_pct']) : 30;
        $carbs_pct = isset($program_data['carbs_pct']) ? intval($program_data['carbs_pct']) : 40;
        $fat_pct = isset($program_data['fat_pct']) ? intval($program_data['fat_pct']) : 30;
        
        $total_pct = $protein_pct + $carbs_pct + $fat_pct;
        
        if ($total_pct != 100) {
            // Ajuster proportionnellement
            $factor = 100 / $total_pct;
            $protein_pct = round($protein_pct * $factor);
            $carbs_pct = round($carbs_pct * $factor);
            $fat_pct = 100 - $protein_pct - $carbs_pct; // Assurer que le total est exactement 100%
        }
        
        // Créer le programme
        $sql = "INSERT INTO nutrition_programs 
                (user_id, name, description, goal_type, calorie_adjustment, 
                protein_pct, carbs_pct, fat_pct, is_system, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        return insert($sql, [
            $user_id, 
            $program_data['name'], 
            $description, 
            $program_data['goal_type'], 
            $calorie_adjustment, 
            $protein_pct, 
            $carbs_pct, 
            $fat_pct, 
            $is_system
        ]);
    } catch (Exception $e) {
        error_log("Erreur lors de la création du programme nutritionnel: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour un programme nutritionnel existant
 * 
 * @param int $program_id ID du programme
 * @param array $program_data Données du programme
 * @return bool True si la mise à jour a réussi, false sinon
 */
function updateNutritionProgram($program_id, $program_data) {
    try {
        // Récupérer le programme existant
        $sql = "SELECT * FROM nutrition_programs WHERE id = ?";
        $existing = fetchOne($sql, [$program_id]);
        
        if (!$existing) {
            return false;
        }
        
        // Vérifier les permissions (seul le créateur ou un admin peut modifier)
        if (!isAdmin($_SESSION['user_id']) && $existing['user_id'] != $_SESSION['user_id'] && !$existing['is_system']) {
            return false;
        }
        
        // Préparer les données à mettre à jour
        $updates = [];
        $params = [];
        
        if (isset($program_data['name']) && !empty($program_data['name'])) {
            $updates[] = "name = ?";
            $params[] = $program_data['name'];
        }
        
        if (isset($program_data['description'])) {
            $updates[] = "description = ?";
            $params[] = $program_data['description'];
        }
        
        if (isset($program_data['goal_type']) && in_array($program_data['goal_type'], ['perte_poids', 'prise_poids', 'maintien'])) {
            $updates[] = "goal_type = ?";
            $params[] = $program_data['goal_type'];
        }
        
        if (isset($program_data['calorie_adjustment'])) {
            $updates[] = "calorie_adjustment = ?";
            $params[] = intval($program_data['calorie_adjustment']);
        }
        
        // Mettre à jour les macronutriments si tous les pourcentages sont fournis
        if (isset($program_data['protein_pct']) && isset($program_data['carbs_pct']) && isset($program_data['fat_pct'])) {
            $protein_pct = intval($program_data['protein_pct']);
            $carbs_pct = intval($program_data['carbs_pct']);
            $fat_pct = intval($program_data['fat_pct']);
            
            $total_pct = $protein_pct + $carbs_pct + $fat_pct;
            
            if ($total_pct != 100) {
                // Ajuster proportionnellement
                $factor = 100 / $total_pct;
                $protein_pct = round($protein_pct * $factor);
                $carbs_pct = round($carbs_pct * $factor);
                $fat_pct = 100 - $protein_pct - $carbs_pct; // Assurer que le total est exactement 100%
            }
            
            $updates[] = "protein_pct = ?";
            $params[] = $protein_pct;
            
            $updates[] = "carbs_pct = ?";
            $params[] = $carbs_pct;
            
            $updates[] = "fat_pct = ?";
            $params[] = $fat_pct;
        }
        
        if (isset($program_data['is_system']) && isAdmin($_SESSION['user_id'])) {
            $updates[] = "is_system = ?";
            $params[] = $program_data['is_system'] ? 1 : 0;
        }
        
        if (empty($updates)) {
            return true; // Rien à mettre à jour
        }
        
        $updates[] = "updated_at = NOW()";
        $update_sql = implode(", ", $updates);
        
        $sql = "UPDATE nutrition_programs SET $update_sql WHERE id = ?";
        $params[] = $program_id;
        
        $result = update($sql, $params);
        
        if ($result) {
            // Mettre à jour les besoins nutritionnels des utilisateurs assignés à ce programme
            $sql = "SELECT user_id FROM user_profiles WHERE nutrition_program_id = ?";
            $users = fetchAll($sql, [$program_id]);
            
            foreach ($users as $user) {
                updateUserNutritionalNeeds($user['user_id']);
            }
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour du programme nutritionnel: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un programme nutritionnel
 * 
 * @param int $program_id ID du programme
 * @return bool True si la suppression a réussi, false sinon
 */
function deleteNutritionProgram($program_id) {
    try {
        // Récupérer le programme existant
        $sql = "SELECT * FROM nutrition_programs WHERE id = ?";
        $existing = fetchOne($sql, [$program_id]);
        
        if (!$existing) {
            return false;
        }
        
        // Vérifier les permissions (seul le créateur ou un admin peut supprimer)
        if (!isAdmin($_SESSION['user_id']) && $existing['user_id'] != $_SESSION['user_id']) {
            return false;
        }
        
        // Vérifier si des utilisateurs sont assignés à ce programme
        $sql = "SELECT COUNT(*) as user_count FROM user_profiles WHERE nutrition_program_id = ?";
        $result = fetchOne($sql, [$program_id]);
        
        if ($result && $result['user_count'] > 0) {
            // Réassigner les utilisateurs au programme par défaut
            $default_program_id = null;
            
            // Trouver un programme système par défaut
            $sql = "SELECT id FROM nutrition_programs WHERE is_system = 1 AND id != ? ORDER BY created_at LIMIT 1";
            $default = fetchOne($sql, [$program_id]);
            
            if ($default) {
                $default_program_id = $default['id'];
            }
            
            // Mettre à jour les profils utilisateurs
            $sql = "UPDATE user_profiles SET nutrition_program_id = ? WHERE nutrition_program_id = ?";
            update($sql, [$default_program_id, $program_id]);
            
            // Mettre à jour les besoins nutritionnels des utilisateurs concernés
            $sql = "SELECT user_id FROM user_profiles WHERE nutrition_program_id IS NULL";
            $users = fetchAll($sql, []);
            
            foreach ($users as $user) {
                updateUserNutritionalNeeds($user['user_id']);
            }
        }
        
        // Supprimer le programme
        $sql = "DELETE FROM nutrition_programs WHERE id = ?";
        return delete($sql, [$program_id]) > 0;
    } catch (Exception $e) {
        error_log("Erreur lors de la suppression du programme nutritionnel: " . $e->getMessage());
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
        // Vérifier que le programme existe
        $sql = "SELECT id FROM nutrition_programs WHERE id = ?";
        $program = fetchOne($sql, [$program_id]);
        
        if (!$program) {
            return false;
        }
        
        // Mettre à jour le profil de l'utilisateur
        $sql = "UPDATE user_profiles SET nutrition_program_id = ?, updated_at = NOW() WHERE user_id = ?";
        $result = update($sql, [$program_id, $user_id]);
        
        if ($result) {
            // Mettre à jour les besoins nutritionnels de l'utilisateur
            updateUserNutritionalNeeds($user_id);
        }
        
        return $result > 0;
    } catch (Exception $e) {
        error_log("Erreur lors de l'assignation du programme nutritionnel: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les utilisateurs assignés à un programme nutritionnel
 * 
 * @param int $program_id ID du programme
 * @return array Liste des utilisateurs
 */
function getUsersAssignedToProgram($program_id) {
    try {
        $sql = "SELECT u.id, u.username, u.email, u.role_id, r.name as role_name,
                up.first_name, up.last_name, up.gender, up.birth_date, up.height, up.activity_level,
                (SELECT weight FROM weight_logs WHERE user_id = u.id ORDER BY log_date DESC LIMIT 1) as current_weight,
                (SELECT log_date FROM weight_logs WHERE user_id = u.id ORDER BY log_date DESC LIMIT 1) as last_weight_date
                FROM user_profiles up
                JOIN users u ON up.user_id = u.id
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE up.nutrition_program_id = ?
                ORDER BY u.username";
        
        return fetchAll($sql, [$program_id]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des utilisateurs assignés au programme: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les programmes nutritionnels recommandés pour un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Liste des programmes recommandés
 */
function getRecommendedPrograms($user_id) {
    try {
        // Récupérer le profil de l'utilisateur
        $sql = "SELECT up.*, 
                (SELECT weight FROM weight_logs WHERE user_id = up.user_id ORDER BY log_date DESC LIMIT 1) as current_weight,
                (SELECT target_weight FROM goals WHERE user_id = up.user_id AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1) as target_weight
                FROM user_profiles up
                WHERE up.user_id = ?";
        
        $profile = fetchOne($sql, [$user_id]);
        
        if (!$profile || !isset($profile['current_weight'])) {
            return [];
        }
        
        // Déterminer le type d'objectif en fonction du poids actuel et du poids cible
        $goal_type = 'maintien';
        
        if (isset($profile['target_weight']) && $profile['target_weight'] > 0) {
            if ($profile['target_weight'] < $profile['current_weight']) {
                $goal_type = 'perte_poids';
            } else if ($profile['target_weight'] > $profile['current_weight']) {
                $goal_type = 'prise_poids';
            }
        }
        
        // Récupérer les programmes correspondant à cet objectif
        $sql = "SELECT * FROM nutrition_programs 
                WHERE goal_type = ? AND (is_system = 1 OR user_id = ?)
                ORDER BY is_system DESC, created_at DESC";
        
        return fetchAll($sql, [$goal_type, $user_id]);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des programmes recommandés: " . $e->getMessage());
        return [];
    }
}

/**
 * Duplique un programme nutritionnel existant
 * 
 * @param int $program_id ID du programme à dupliquer
 * @param int $user_id ID de l'utilisateur qui crée la copie
 * @param string $new_name Nouveau nom pour la copie (optionnel)
 * @return int|false ID du nouveau programme ou false en cas d'erreur
 */
function duplicateNutritionProgram($program_id, $user_id, $new_name = null) {
    try {
        // Récupérer le programme existant
        $sql = "SELECT * FROM nutrition_programs WHERE id = ?";
        $existing = fetchOne($sql, [$program_id]);
        
        if (!$existing) {
            return false;
        }
        
        // Créer un nouveau nom si non spécifié
        if ($new_name === null) {
            $new_name = $existing['name'] . ' (copie)';
        }
        
        // Créer une copie du programme
        $sql = "INSERT INTO nutrition_programs 
                (user_id, name, description, goal_type, calorie_adjustment, 
                protein_pct, carbs_pct, fat_pct, is_system, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
        
        return insert($sql, [
            $user_id, 
            $new_name, 
            $existing['description'], 
            $existing['goal_type'], 
            $existing['calorie_adjustment'], 
            $existing['protein_pct'], 
            $existing['carbs_pct'], 
            $existing['fat_pct']
        ]);
    } catch (Exception $e) {
        error_log("Erreur lors de la duplication du programme nutritionnel: " . $e->getMessage());
        return false;
    }
}

/**
 * Crée des programmes nutritionnels système par défaut
 * 
 * @return bool True si la création a réussi, false sinon
 */
function createDefaultNutritionPrograms() {
    try {
        // Vérifier si des programmes système existent déjà
        $sql = "SELECT COUNT(*) as count FROM nutrition_programs WHERE is_system = 1";
        $result = fetchOne($sql, []);
        
        if ($result && $result['count'] > 0) {
            return true; // Des programmes système existent déjà
        }
        
        // Créer les programmes par défaut
        $default_programs = [
            [
                'name' => 'Perte de poids légère',
                'description' => 'Programme conçu pour une perte de poids progressive et durable avec un déficit calorique modéré.',
                'goal_type' => 'perte_poids',
                'calorie_adjustment' => 300,
                'protein_pct' => 30,
                'carbs_pct' => 40,
                'fat_pct' => 30
            ],
            [
                'name' => 'Perte de poids modérée',
                'description' => 'Programme conçu pour une perte de poids plus rapide avec un déficit calorique plus important.',
                'goal_type' => 'perte_poids',
                'calorie_adjustment' => 500,
                'protein_pct' => 35,
                'carbs_pct' => 35,
                'fat_pct' => 30
            ],
            [
                'name' => 'Perte de poids intensive',
                'description' => 'Programme conçu pour une perte de poids rapide avec un déficit calorique important. À suivre sur une courte période.',
                'goal_type' => 'perte_poids',
                'calorie_adjustment' => 750,
                'protein_pct' => 40,
                'carbs_pct' => 30,
                'fat_pct' => 30
            ],
            [
                'name' => 'Maintien du poids',
                'description' => 'Programme équilibré conçu pour maintenir votre poids actuel tout en assurant une alimentation saine.',
                'goal_type' => 'maintien',
                'calorie_adjustment' => 0,
                'protein_pct' => 25,
                'carbs_pct' => 50,
                'fat_pct' => 25
            ],
            [
                'name' => 'Prise de masse légère',
                'description' => 'Programme conçu pour une prise de masse progressive avec un surplus calorique modéré.',
                'goal_type' => 'prise_poids',
                'calorie_adjustment' => 300,
                'protein_pct' => 30,
                'carbs_pct' => 50,
                'fat_pct' => 20
            ],
            [
                'name' => 'Prise de masse modérée',
                'description' => 'Programme conçu pour une prise de masse plus rapide avec un surplus calorique plus important.',
                'goal_type' => 'prise_poids',
                'calorie_adjustment' => 500,
                'protein_pct' => 25,
                'carbs_pct' => 55,
                'fat_pct' => 20
            ],
            [
                'name' => 'Régime cétogène',
                'description' => 'Programme à faible teneur en glucides et riche en graisses, conçu pour induire la cétose.',
                'goal_type' => 'perte_poids',
                'calorie_adjustment' => 400,
                'protein_pct' => 25,
                'carbs_pct' => 5,
                'fat_pct' => 70
            ],
            [
                'name' => 'Régime méditerranéen',
                'description' => 'Programme basé sur le régime méditerranéen, riche en graisses saines, légumes et protéines maigres.',
                'goal_type' => 'maintien',
                'calorie_adjustment' => 0,
                'protein_pct' => 20,
                'carbs_pct' => 50,
                'fat_pct' => 30
            ]
        ];
        
        foreach ($default_programs as $program) {
            $sql = "INSERT INTO nutrition_programs 
                    (user_id, name, description, goal_type, calorie_adjustment, 
                    protein_pct, carbs_pct, fat_pct, is_system, created_at) 
                    VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            
            insert($sql, [
                $program['name'], 
                $program['description'], 
                $program['goal_type'], 
                $program['calorie_adjustment'], 
                $program['protein_pct'], 
                $program['carbs_pct'], 
                $program['fat_pct']
            ]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de la création des programmes nutritionnels par défaut: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère un rapport de performance pour un programme nutritionnel
 * 
 * @param int $program_id ID du programme
 * @return array Rapport de performance
 */
function generateProgramPerformanceReport($program_id) {
    try {
        // Récupérer les détails du programme
        $program = getNutritionProgramDetails($program_id);
        
        if (!$program) {
            return false;
        }
        
        $report = [
            'program' => $program,
            'user_count' => $program['user_count'],
            'success_metrics' => [],
            'weight_changes' => [],
            'adherence_rate' => 0,
            'avg_duration' => 0
        ];
        
        // Si aucun utilisateur n'est assigné, retourner le rapport de base
        if ($program['user_count'] == 0) {
            return $report;
        }
        
        // Récupérer les utilisateurs assignés
        $users = getUsersAssignedToProgram($program_id);
        
        if (empty($users)) {
            return $report;
        }
        
        // Calculer les métriques de succès
        $total_users = count($users);
        $successful_users = 0;
        $total_weight_change = 0;
        $total_duration = 0;
        $adherence_data = [];
        
        foreach ($users as $user) {
            // Récupérer l'historique de poids
            $sql = "SELECT weight, log_date 
                    FROM weight_logs 
                    WHERE user_id = ? 
                    ORDER BY log_date";
            
            $weight_logs = fetchAll($sql, [$user['id']]);
            
            if (count($weight_logs) < 2) {
                continue; // Pas assez de données pour analyser
            }
            
            // Calculer le changement de poids
            $first_weight = $weight_logs[0]['weight'];
            $last_weight = $weight_logs[count($weight_logs) - 1]['weight'];
            $weight_change = $last_weight - $first_weight;
            
            // Calculer la durée
            $first_date = new DateTime($weight_logs[0]['log_date']);
            $last_date = new DateTime($weight_logs[count($weight_logs) - 1]['log_date']);
            $duration = $first_date->diff($last_date)->days;
            
            if ($duration == 0) {
                $duration = 1; // Éviter la division par zéro
            }
            
            // Déterminer si l'utilisateur a atteint son objectif
            $success = false;
            
            if ($program['goal_type'] == 'perte_poids' && $weight_change < 0) {
                $success = true;
            } else if ($program['goal_type'] == 'prise_poids' && $weight_change > 0) {
                $success = true;
            } else if ($program['goal_type'] == 'maintien' && abs($weight_change) < 2) {
                $success = true;
            }
            
            if ($success) {
                $successful_users++;
            }
            
            // Calculer l'adhérence au programme (basée sur la régularité des entrées)
            $expected_entries = ceil($duration / 7); // Une entrée par semaine attendue
            $actual_entries = count($weight_logs);
            $adherence = min(100, ($actual_entries / $expected_entries) * 100);
            
            $adherence_data[] = $adherence;
            
            // Ajouter aux totaux
            $total_weight_change += abs($weight_change);
            $total_duration += $duration;
            
            // Ajouter aux données de changement de poids
            $report['weight_changes'][] = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'first_weight' => $first_weight,
                'last_weight' => $last_weight,
                'weight_change' => $weight_change,
                'duration' => $duration,
                'success' => $success
            ];
        }
        
        // Calculer les moyennes
        if ($total_users > 0) {
            $report['success_metrics'] = [
                'success_rate' => round(($successful_users / $total_users) * 100),
                'avg_weight_change' => round($total_weight_change / $total_users, 1),
                'avg_weekly_change' => round(($total_weight_change / $total_users) / ($total_duration / 7), 1)
            ];
            
            $report['avg_duration'] = round($total_duration / $total_users);
            
            if (!empty($adherence_data)) {
                $report['adherence_rate'] = round(array_sum($adherence_data) / count($adherence_data));
            }
        }
        
        return $report;
    } catch (Exception $e) {
        error_log("Erreur lors de la génération du rapport de performance: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère des suggestions de programmes nutritionnels pour un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Liste des suggestions de programmes
 */
function suggestNutritionPrograms($user_id) {
    try {
        // Récupérer le profil de l'utilisateur
        $sql = "SELECT up.*, 
                (SELECT weight FROM weight_logs WHERE user_id = up.user_id ORDER BY log_date DESC LIMIT 1) as current_weight,
                (SELECT height FROM user_profiles WHERE user_id = ?) as height,
                (SELECT gender FROM user_profiles WHERE user_id = ?) as gender,
                (SELECT birth_date FROM user_profiles WHERE user_id = ?) as birth_date,
                (SELECT target_weight FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1) as target_weight,
                (SELECT target_date FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1) as target_date
                FROM user_profiles up
                WHERE up.user_id = ?";
        
        $profile = fetchOne($sql, [$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
        
        if (!$profile || !isset($profile['current_weight']) || !isset($profile['height']) || $profile['height'] <= 0) {
            return [
                'error' => 'Informations de profil insuffisantes',
                'suggestions' => []
            ];
        }
        
        // Calculer l'IMC
        $height_m = $profile['height'] / 100;
        $bmi = $profile['current_weight'] / ($height_m * $height_m);
        
        // Déterminer le type d'objectif en fonction du poids actuel et du poids cible
        $goal_type = 'maintien';
        $weight_difference = 0;
        $weekly_goal = 0;
        
        if (isset($profile['target_weight']) && $profile['target_weight'] > 0) {
            $weight_difference = $profile['target_weight'] - $profile['current_weight'];
            
            if ($weight_difference < -1) {
                $goal_type = 'perte_poids';
            } else if ($weight_difference > 1) {
                $goal_type = 'prise_poids';
            }
            
            // Calculer l'objectif hebdomadaire si une date cible est définie
            if (isset($profile['target_date'])) {
                $target_date = new DateTime($profile['target_date']);
                $today = new DateTime();
                
                if ($target_date > $today) {
                    $weeks = ceil($today->diff($target_date)->days / 7);
                    
                    if ($weeks > 0) {
                        $weekly_goal = abs($weight_difference) / $weeks;
                    }
                }
            }
        }
        
        // Déterminer le niveau d'intensité recommandé
        $intensity = 'modérée';
        
        if ($weekly_goal > 0) {
            if ($weekly_goal < 0.5) {
                $intensity = 'légère';
            } else if ($weekly_goal > 1) {
                $intensity = 'intensive';
            }
        } else {
            // Si pas d'objectif hebdomadaire, baser sur l'IMC
            if ($goal_type == 'perte_poids') {
                if ($bmi > 30) {
                    $intensity = 'intensive';
                } else if ($bmi > 25) {
                    $intensity = 'modérée';
                } else {
                    $intensity = 'légère';
                }
            } else if ($goal_type == 'prise_poids') {
                if ($bmi < 18.5) {
                    $intensity = 'modérée';
                } else {
                    $intensity = 'légère';
                }
            }
        }
        
        // Récupérer les programmes correspondant à cet objectif et cette intensité
        $programs = [];
        
        if ($goal_type == 'perte_poids') {
            $calorie_adjustment = 0;
            
            switch ($intensity) {
                case 'légère':
                    $calorie_adjustment = 300;
                    break;
                case 'modérée':
                    $calorie_adjustment = 500;
                    break;
                case 'intensive':
                    $calorie_adjustment = 750;
                    break;
            }
            
            // Rechercher des programmes similaires
            $sql = "SELECT * FROM nutrition_programs 
                    WHERE goal_type = ? AND ABS(calorie_adjustment - ?) <= 100 AND (is_system = 1 OR user_id = ?)
                    ORDER BY ABS(calorie_adjustment - ?), is_system DESC, created_at DESC
                    LIMIT 3";
            
            $programs = fetchAll($sql, [$goal_type, $calorie_adjustment, $user_id, $calorie_adjustment]);
        } else if ($goal_type == 'prise_poids') {
            $calorie_adjustment = 0;
            
            switch ($intensity) {
                case 'légère':
                    $calorie_adjustment = 300;
                    break;
                case 'modérée':
                    $calorie_adjustment = 500;
                    break;
                case 'intensive':
                    $calorie_adjustment = 700;
                    break;
            }
            
            // Rechercher des programmes similaires
            $sql = "SELECT * FROM nutrition_programs 
                    WHERE goal_type = ? AND ABS(calorie_adjustment - ?) <= 100 AND (is_system = 1 OR user_id = ?)
                    ORDER BY ABS(calorie_adjustment - ?), is_system DESC, created_at DESC
                    LIMIT 3";
            
            $programs = fetchAll($sql, [$goal_type, $calorie_adjustment, $user_id, $calorie_adjustment]);
        } else {
            // Pour le maintien, récupérer les programmes de maintien
            $sql = "SELECT * FROM nutrition_programs 
                    WHERE goal_type = 'maintien' AND (is_system = 1 OR user_id = ?)
                    ORDER BY is_system DESC, created_at DESC
                    LIMIT 3";
            
            $programs = fetchAll($sql, [$user_id]);
        }
        
        // Si aucun programme n'est trouvé, suggérer la création d'un programme personnalisé
        if (empty($programs)) {
            // Calculer les besoins caloriques de base
            $bmr = 0;
            $age = 30; // Valeur par défaut
            
            if (isset($profile['birth_date'])) {
                $birth_date = new DateTime($profile['birth_date']);
                $today = new DateTime();
                $age = $birth_date->diff($today)->y;
            }
            
            $gender = $profile['gender'] ?? 'homme';
            
            // Formule de Mifflin-St Jeor
            if ($gender === 'homme') {
                $bmr = (10 * $profile['current_weight']) + (6.25 * $profile['height']) - (5 * $age) + 5;
            } else {
                $bmr = (10 * $profile['current_weight']) + (6.25 * $profile['height']) - (5 * $age) - 161;
            }
            
            // Appliquer le multiplicateur d'activité
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
            
            // Créer un programme personnalisé
            $calorie_adjustment = 0;
            
            if ($goal_type == 'perte_poids') {
                switch ($intensity) {
                    case 'légère':
                        $calorie_adjustment = 300;
                        break;
                    case 'modérée':
                        $calorie_adjustment = 500;
                        break;
                    case 'intensive':
                        $calorie_adjustment = 750;
                        break;
                }
            } else if ($goal_type == 'prise_poids') {
                switch ($intensity) {
                    case 'légère':
                        $calorie_adjustment = 300;
                        break;
                    case 'modérée':
                        $calorie_adjustment = 500;
                        break;
                    case 'intensive':
                        $calorie_adjustment = 700;
                        break;
                }
            }
            
            // Déterminer la répartition des macronutriments en fonction de l'objectif
            $protein_pct = 30;
            $carbs_pct = 40;
            $fat_pct = 30;
            
            if ($goal_type == 'perte_poids') {
                $protein_pct = 35;
                $carbs_pct = 35;
                $fat_pct = 30;
            } else if ($goal_type == 'prise_poids') {
                $protein_pct = 25;
                $carbs_pct = 50;
                $fat_pct = 25;
            }
            
            $custom_program = [
                'id' => 'custom',
                'name' => 'Programme personnalisé recommandé',
                'description' => 'Programme personnalisé créé en fonction de votre profil et de vos objectifs.',
                'goal_type' => $goal_type,
                'calorie_adjustment' => $calorie_adjustment,
                'protein_pct' => $protein_pct,
                'carbs_pct' => $carbs_pct,
                'fat_pct' => $fat_pct,
                'is_custom_suggestion' => true,
                'tdee' => $tdee,
                'goal_calories' => $goal_type == 'perte_poids' ? $tdee - $calorie_adjustment : 
                                  ($goal_type == 'prise_poids' ? $tdee + $calorie_adjustment : $tdee)
            ];
            
            $programs[] = $custom_program;
        }
        
        return [
            'goal_type' => $goal_type,
            'intensity' => $intensity,
            'weekly_goal' => round($weekly_goal, 1),
            'weight_difference' => round($weight_difference, 1),
            'bmi' => round($bmi, 1),
            'suggestions' => $programs
        ];
    } catch (Exception $e) {
        error_log("Erreur lors de la génération des suggestions de programmes: " . $e->getMessage());
        return [
            'error' => $e->getMessage(),
            'suggestions' => []
        ];
    }
}

/**
 * Génère un plan de repas hebdomadaire basé sur un programme nutritionnel
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $program_id ID du programme nutritionnel
 * @return array Plan de repas hebdomadaire
 */
function generateMealPlan($user_id, $program_id) {
    try {
        // Récupérer les détails du programme
        $program = getNutritionProgramDetails($program_id);
        
        if (!$program) {
            return false;
        }
        
        // Récupérer les besoins caloriques de l'utilisateur
        $sql = "SELECT * FROM user_calorie_needs WHERE user_id = ?";
        $calorie_needs = fetchOne($sql, [$user_id]);
        
        if (!$calorie_needs) {
            // Mettre à jour les besoins nutritionnels
            updateUserNutritionalNeeds($user_id);
            
            // Récupérer à nouveau
            $calorie_needs = fetchOne($sql, [$user_id]);
            
            if (!$calorie_needs) {
                return false;
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
        
        // Définir la répartition calorique par repas
        $meal_distribution = [
            'petit_dejeuner' => 0.25, // 25% des calories quotidiennes
            'dejeuner' => 0.35,       // 35% des calories quotidiennes
            'diner' => 0.30,          // 30% des calories quotidiennes
            'collation' => 0.10       // 10% des calories quotidiennes
        ];
        
        // Créer le plan de repas pour chaque jour de la semaine
        $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $meal_plan = [];
        
        foreach ($days as $day) {
            $daily_meals = [];
            
            foreach ($meal_distribution as $meal_type => $percentage) {
                $target_calories = round($calorie_needs['goal_calories'] * $percentage);
                
                // Récupérer les repas prédéfinis correspondant aux critères
                $sql = "SELECT pm.*, 
                        (SELECT COUNT(*) FROM user_favorite_meals ufm WHERE ufm.predefined_meal_id = pm.id AND ufm.user_id = ?) as is_favorite
                        FROM predefined_meals pm
                        WHERE pm.meal_type = ? 
                        AND pm.calories BETWEEN ? AND ?
                        AND (pm.is_public = 1 OR pm.created_by_user = ?)
                        ORDER BY 
                            CASE WHEN pm.created_by_user = ? THEN 1 ELSE 0 END DESC,
                            is_favorite DESC,
                            ABS(pm.calories - ?) ASC
                        LIMIT 3";
                
                $min_calories = $target_calories * 0.9;
                $max_calories = $target_calories * 1.1;
                
                $meals = fetchAll($sql, [
                    $user_id, 
                    $meal_type, 
                    $min_calories, 
                    $max_calories, 
                    $user_id, 
                    $user_id, 
                    $target_calories
                ]);
                
                // Filtrer les repas contenant des allergènes ou intolérances
                $filtered_meals = [];
                
                foreach ($meals as $meal) {
                    $contains_allergen = false;
                    
                    // Vérifier les aliments du repas
                    $sql = "SELECT pmi.*, f.name as food_name, f.category as food_category
                            FROM predefined_meal_items pmi
                            JOIN foods f ON pmi.food_id = f.id
                            WHERE pmi.predefined_meal_id = ?";
                    
                    $meal_items = fetchAll($sql, [$meal['id']]);
                    
                    foreach ($meal_items as $item) {
                        // Vérifier les allergies et intolérances
                        if (in_array($item['food_id'], $allergies) || in_array($item['food_id'], $intolerances)) {
                            $contains_allergen = true;
                            break;
                        }
                        
                        // Vérifier les catégories d'allergies et intolérances
                        $category_key = "category:" . $item['food_category'];
                        if (in_array($category_key, $allergies) || in_array($category_key, $intolerances)) {
                            $contains_allergen = true;
                            break;
                        }
                    }
                    
                    if (!$contains_allergen) {
                        $filtered_meals[] = $meal;
                    }
                }
                
                // Si aucun repas n'est disponible après filtrage, créer un repas générique
                if (empty($filtered_meals)) {
                    $filtered_meals[] = [
                        'id' => 'generic_' . $meal_type,
                        'name' => ucfirst($meal_type) . ' équilibré',
                        'description' => 'Repas équilibré adapté à vos besoins caloriques et nutritionnels.',
                        'meal_type' => $meal_type,
                        'calories' => $target_calories,
                        'protein' => round(($target_calories * ($program['protein_pct'] / 100)) / 4), // 4 calories par gramme de protéine
                        'carbs' => round(($target_calories * ($program['carbs_pct'] / 100)) / 4),     // 4 calories par gramme de glucide
                        'fat' => round(($target_calories * ($program['fat_pct'] / 100)) / 9),         // 9 calories par gramme de lipide
                        'is_generic' => true
                    ];
                }
                
                // Sélectionner un repas aléatoirement parmi les repas filtrés
                $selected_meal = $filtered_meals[array_rand($filtered_meals)];
                
                // Ajouter les détails des aliments si ce n'est pas un repas générique
                if (!isset($selected_meal['is_generic'])) {
                    $sql = "SELECT pmi.*, f.name as food_name, f.category as food_category
                            FROM predefined_meal_items pmi
                            JOIN foods f ON pmi.food_id = f.id
                            WHERE pmi.predefined_meal_id = ?";
                    
                    $selected_meal['items'] = fetchAll($sql, [$selected_meal['id']]);
                }
                
                $daily_meals[$meal_type] = $selected_meal;
            }
            
            // Calculer les totaux nutritionnels pour la journée
            $daily_totals = [
                'calories' => 0,
                'protein' => 0,
                'carbs' => 0,
                'fat' => 0
            ];
            
            foreach ($daily_meals as $meal) {
                $daily_totals['calories'] += $meal['calories'];
                $daily_totals['protein'] += $meal['protein'];
                $daily_totals['carbs'] += $meal['carbs'];
                $daily_totals['fat'] += $meal['fat'];
            }
            
            $meal_plan[$day] = [
                'meals' => $daily_meals,
                'totals' => $daily_totals
            ];
        }
        
        return [
            'program' => $program,
            'calorie_needs' => $calorie_needs,
            'meal_plan' => $meal_plan
        ];
    } catch (Exception $e) {
        error_log("Erreur lors de la génération du plan de repas: " . $e->getMessage());
        return false;
    }
}

/**
 * Enregistre un plan de repas hebdomadaire pour un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param array $meal_plan Plan de repas à enregistrer
 * @param string $start_date Date de début du plan (format Y-m-d)
 * @return bool True si l'enregistrement a réussi, false sinon
 */
function saveMealPlan($user_id, $meal_plan, $start_date) {
    try {
        if (!isset($meal_plan['meal_plan']) || empty($meal_plan['meal_plan'])) {
            return false;
        }
        
        // Convertir la date de début en objet DateTime
        $start_date_obj = new DateTime($start_date);
        
        // Commencer une transaction
        beginTransaction();
        
        // Enregistrer le plan de repas
        $sql = "INSERT INTO meal_plans (user_id, start_date, program_id, created_at) 
                VALUES (?, ?, ?, NOW())";
        
        $program_id = isset($meal_plan['program']['id']) ? $meal_plan['program']['id'] : null;
        $meal_plan_id = insert($sql, [$user_id, $start_date, $program_id]);
        
        if (!$meal_plan_id) {
            rollbackTransaction();
            return false;
        }
        
        // Enregistrer chaque jour du plan
        $day_index = 0;
        
        foreach ($meal_plan['meal_plan'] as $day => $daily_plan) {
            // Calculer la date du jour
            $day_date = clone $start_date_obj;
            $day_date->modify("+$day_index days");
            
            // Enregistrer le jour
            $sql = "INSERT INTO meal_plan_days (meal_plan_id, day_name, day_date, total_calories, 
                    total_protein, total_carbs, total_fat, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $day_id = insert($sql, [
                $meal_plan_id, 
                $day, 
                $day_date->format('Y-m-d'), 
                $daily_plan['totals']['calories'], 
                $daily_plan['totals']['protein'], 
                $daily_plan['totals']['carbs'], 
                $daily_plan['totals']['fat']
            ]);
            
            if (!$day_id) {
                rollbackTransaction();
                return false;
            }
            
            // Enregistrer chaque repas du jour
            foreach ($daily_plan['meals'] as $meal_type => $meal) {
                $predefined_meal_id = null;
                
                if (isset($meal['id']) && !isset($meal['is_generic']) && is_numeric($meal['id'])) {
                    $predefined_meal_id = $meal['id'];
                }
                
                $sql = "INSERT INTO meal_plan_meals (meal_plan_day_id, meal_type, name, description, 
                        calories, protein, carbs, fat, predefined_meal_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $meal_id = insert($sql, [
                    $day_id, 
                    $meal_type, 
                    $meal['name'], 
                    $meal['description'] ?? '', 
                    $meal['calories'], 
                    $meal['protein'], 
                    $meal['carbs'], 
                    $meal['fat'], 
                    $predefined_meal_id
                ]);
                
                if (!$meal_id) {
                    rollbackTransaction();
                    return false;
                }
                
                // Enregistrer les aliments du repas si disponibles
                if (isset($meal['items']) && is_array($meal['items'])) {
                    foreach ($meal['items'] as $item) {
                        $sql = "INSERT INTO meal_plan_items (meal_plan_meal_id, food_id, quantity, created_at) 
                                VALUES (?, ?, ?, NOW())";
                        
                        $item_id = insert($sql, [
                            $meal_id, 
                            $item['food_id'], 
                            $item['quantity']
                        ]);
                        
                        if (!$item_id) {
                            rollbackTransaction();
                            return false;
                        }
                    }
                }
            }
            
            $day_index++;
        }
        
        // Valider la transaction
        commitTransaction();
        
        return $meal_plan_id;
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        rollbackTransaction();
        error_log("Erreur lors de l'enregistrement du plan de repas: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les plans de repas d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param bool $include_expired Inclure les plans expirés
 * @return array Liste des plans de repas
 */
function getUserMealPlans($user_id, $include_expired = false) {
    try {
        $params = [$user_id];
        $where_sql = "mp.user_id = ?";
        
        if (!$include_expired) {
            $where_sql .= " AND mp.start_date <= CURDATE() AND DATE_ADD(mp.start_date, INTERVAL 6 DAY) >= CURDATE()";
        }
        
        $sql = "SELECT mp.*, np.name as program_name, np.goal_type,
                DATE_ADD(mp.start_date, INTERVAL 6 DAY) as end_date,
                (SELECT AVG(total_calories) FROM meal_plan_days WHERE meal_plan_id = mp.id) as avg_daily_calories
                FROM meal_plans mp
                LEFT JOIN nutrition_programs np ON mp.program_id = np.id
                WHERE $where_sql
                ORDER BY mp.start_date DESC";
        
        $plans = fetchAll($sql, $params);
        
        // Ajouter des informations supplémentaires à chaque plan
        foreach ($plans as &$plan) {
            // Calculer le statut du plan
            $start_date = new DateTime($plan['start_date']);
            $end_date = new DateTime($plan['end_date']);
            $today = new DateTime();
            
            if ($today < $start_date) {
                $plan['status'] = 'à_venir';
            } else if ($today > $end_date) {
                $plan['status'] = 'terminé';
            } else {
                $plan['status'] = 'en_cours';
                $plan['current_day'] = min(6, $today->diff($start_date)->days);
            }
            
            // Récupérer le nombre de jours suivis
            $sql = "SELECT COUNT(DISTINCT mpd.id) as followed_days
                    FROM meal_plan_days mpd
                    JOIN meal_plan_meals mpm ON mpd.id = mpm.meal_plan_day_id
                    JOIN meals m ON m.predefined_meal_id = mpm.predefined_meal_id AND m.user_id = ? AND m.log_date = mpd.day_date
                    WHERE mpd.meal_plan_id = ?";
            
            $followed = fetchOne($sql, [$user_id, $plan['id']]);
            $plan['followed_days'] = $followed ? $followed['followed_days'] : 0;
            
            // Calculer le taux d'adhérence
            $total_days = 7;
            $elapsed_days = 0;
            
            if ($plan['status'] == 'en_cours') {
                $elapsed_days = $plan['current_day'] + 1;
            } else if ($plan['status'] == 'terminé') {
                $elapsed_days = $total_days;
            }
            
            $plan['adherence_rate'] = $elapsed_days > 0 ? round(($plan['followed_days'] / $elapsed_days) * 100) : 0;
        }
        
        return $plans;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des plans de repas: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les détails d'un plan de repas
 * 
 * @param int $meal_plan_id ID du plan de repas
 * @param int $user_id ID de l'utilisateur (pour vérification)
 * @return array|false Détails du plan de repas ou false en cas d'erreur
 */
function getMealPlanDetails($meal_plan_id, $user_id) {
    try {
        // Vérifier que le plan appartient à l'utilisateur
        $sql = "SELECT mp.*, np.name as program_name, np.goal_type, np.description as program_description,
                DATE_ADD(mp.start_date, INTERVAL 6 DAY) as end_date
                FROM meal_plans mp
                LEFT JOIN nutrition_programs np ON mp.program_id = np.id
                WHERE mp.id = ? AND mp.user_id = ?";
        
        $plan = fetchOne($sql, [$meal_plan_id, $user_id]);
        
        if (!$plan) {
            return false;
        }
        
        // Récupérer les jours du plan
        $sql = "SELECT * FROM meal_plan_days 
                WHERE meal_plan_id = ? 
                ORDER BY day_date";
        
        $days = fetchAll($sql, [$meal_plan_id]);
        
        // Récupérer les repas pour chaque jour
        foreach ($days as &$day) {
            $sql = "SELECT * FROM meal_plan_meals 
                    WHERE meal_plan_day_id = ? 
                    ORDER BY FIELD(meal_type, 'petit_dejeuner', 'dejeuner', 'collation', 'diner')";
            
            $meals = fetchAll($sql, [$day['id']]);
            
            // Récupérer les aliments pour chaque repas
            foreach ($meals as &$meal) {
                if ($meal['predefined_meal_id']) {
                    $sql = "SELECT mpi.*, f.name as food_name, f.category as food_category
                            FROM meal_plan_items mpi
                            JOIN foods f ON mpi.food_id = f.id
                            WHERE mpi.meal_plan_meal_id = ?";
                    
                    $meal['items'] = fetchAll($sql, [$meal['id']]);
                    
                    // Vérifier si ce repas a été consommé
                    $sql = "SELECT id FROM meals 
                            WHERE user_id = ? AND log_date = ? AND predefined_meal_id = ?";
                    
                    $consumed = fetchOne($sql, [$user_id, $day['day_date'], $meal['predefined_meal_id']]);
                    $meal['consumed'] = $consumed ? true : false;
                } else {
                    $meal['items'] = [];
                    $meal['consumed'] = false;
                }
            }
            
            $day['meals'] = $meals;
            
            // Vérifier si ce jour a été entièrement suivi
            $day['fully_followed'] = true;
            
            foreach ($meals as $meal) {
                if (!$meal['consumed'] && $meal['predefined_meal_id']) {
                    $day['fully_followed'] = false;
                    break;
                }
            }
        }
        
        $plan['days'] = $days;
        
        // Calculer le statut du plan
        $start_date = new DateTime($plan['start_date']);
        $end_date = new DateTime($plan['end_date']);
        $today = new DateTime();
        
        if ($today < $start_date) {
            $plan['status'] = 'à_venir';
        } else if ($today > $end_date) {
            $plan['status'] = 'terminé';
        } else {
            $plan['status'] = 'en_cours';
            $plan['current_day'] = min(6, $today->diff($start_date)->days);
        }
        
        // Calculer le taux d'adhérence
        $followed_days = 0;
        
        foreach ($days as $day) {
            if ($day['fully_followed']) {
                $followed_days++;
            }
        }
        
        $total_days = 7;
        $elapsed_days = 0;
        
        if ($plan['status'] == 'en_cours') {
            $elapsed_days = $plan['current_day'] + 1;
        } else if ($plan['status'] == 'terminé') {
            $elapsed_days = $total_days;
        }
        
        $plan['followed_days'] = $followed_days;
        $plan['adherence_rate'] = $elapsed_days > 0 ? round(($followed_days / $elapsed_days) * 100) : 0;
        
        return $plan;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des détails du plan de repas: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un plan de repas
 * 
 * @param int $meal_plan_id ID du plan de repas
 * @param int $user_id ID de l'utilisateur (pour vérification)
 * @return bool True si la suppression a réussi, false sinon
 */
function deleteMealPlan($meal_plan_id, $user_id) {
    try {
        // Vérifier que le plan appartient à l'utilisateur
        $sql = "SELECT id FROM meal_plans WHERE id = ? AND user_id = ?";
        $plan = fetchOne($sql, [$meal_plan_id, $user_id]);
        
        if (!$plan) {
            return false;
        }
        
        // Commencer une transaction
        beginTransaction();
        
        // Récupérer les jours du plan
        $sql = "SELECT id FROM meal_plan_days WHERE meal_plan_id = ?";
        $days = fetchAll($sql, [$meal_plan_id]);
        
        foreach ($days as $day) {
            // Récupérer les repas du jour
            $sql = "SELECT id FROM meal_plan_meals WHERE meal_plan_day_id = ?";
            $meals = fetchAll($sql, [$day['id']]);
            
            foreach ($meals as $meal) {
                // Supprimer les aliments du repas
                $sql = "DELETE FROM meal_plan_items WHERE meal_plan_meal_id = ?";
                execute($sql, [$meal['id']]);
            }
            
            // Supprimer les repas du jour
            $sql = "DELETE FROM meal_plan_meals WHERE meal_plan_day_id = ?";
            execute($sql, [$day['id']]);
        }
        
        // Supprimer les jours du plan
        $sql = "DELETE FROM meal_plan_days WHERE meal_plan_id = ?";
        execute($sql, [$meal_plan_id]);
        
        // Supprimer le plan
        $sql = "DELETE FROM meal_plans WHERE id = ?";
        $result = delete($sql, [$meal_plan_id]) > 0;
        
        // Valider ou annuler la transaction
        if ($result) {
            commitTransaction();
        } else {
            rollbackTransaction();
        }
        
        return $result;
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        rollbackTransaction();
        error_log("Erreur lors de la suppression du plan de repas: " . $e->getMessage());
        return false;
    }
}
