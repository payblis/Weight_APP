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

// Vérifier si l'utilisateur est un administrateur
$sql = "SELECT role_id FROM users WHERE id = ?";
$user_role = fetchOne($sql, [$user_id]);
$is_admin = ($user_role && $user_role['role_id'] == 1);

// Initialiser les variables
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$meal_id = isset($_GET['meal_id']) ? intval($_GET['meal_id']) : 0;
$predefined_meal_id = isset($_GET['predefined_meal_id']) ? intval($_GET['predefined_meal_id']) : 0;
$success_message = '';
$errors = [];

error_log("=== DÉBUT DU TRAITEMENT ===");
error_log("Action : " . $action);
error_log("ID du repas : " . $meal_id);
error_log("ID du repas prédéfini : " . $predefined_meal_id);
error_log("GET : " . print_r($_GET, true));

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
    
    error_log("=== DÉBUT DU TRAITEMENT POST ===");
    error_log("Action POST détectée : " . $post_action);
    error_log("Données POST reçues : " . print_r($_POST, true));
    
    // Ajouter un repas
    if ($post_action === 'add_meal') {
        $meal_type = sanitizeInput($_POST['meal_type'] ?? '');
        $log_date = sanitizeInput($_POST['log_date'] ?? date('Y-m-d'));
        $notes = sanitizeInput($_POST['notes'] ?? '');
        
        // Validation
        if (empty($meal_type)) {
            $errors[] = "Le type de repas est requis";
        }
        
        if (empty($log_date) || !validateDate($log_date)) {
            $errors[] = "La date n'est pas valide";
        }
        
        if (empty($errors)) {
            try {
                // Insérer le repas
                $sql = "INSERT INTO meals (user_id, meal_type, log_date, notes, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                $meal_id = insert($sql, [$user_id, $meal_type, $log_date, $notes]);
                
                if ($meal_id) {
                    // Si c'est une suggestion d'IA, ajouter les valeurs nutritionnelles
                    if ($suggestion_id > 0) {
                        $sql = "SELECT content FROM ai_suggestions WHERE id = ? AND user_id = ?";
                        $suggestion = fetchOne($sql, [$suggestion_id, $user_id]);
                        
                        if ($suggestion) {
                            // Extraire les valeurs nutritionnelles
                            preg_match('/calories?\s*:?\s*(\d+)\s*kcal/i', $suggestion['content'], $calories_match);
                            preg_match('/prot[ée]ines?\s*:?\s*(\d+(?:\.\d+)?)\s*g/i', $suggestion['content'], $protein_match);
                            preg_match('/glucides?\s*:?\s*(\d+(?:\.\d+)?)\s*g/i', $suggestion['content'], $carbs_match);
                            preg_match('/lipides?\s*:?\s*(\d+(?:\.\d+)?)\s*g/i', $suggestion['content'], $fat_match);
                            
                            // Ajouter l'aliment avec les valeurs nutritionnelles
                            $sql = "INSERT INTO food_logs (user_id, custom_food_name, quantity, custom_calories, custom_protein, custom_carbs, custom_fat, log_date, meal_id, is_part_of_meal, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
                            insert($sql, [
                                $user_id,
                                "Suggestion IA #" . $suggestion_id,
                                100, // Quantité par défaut
                                isset($calories_match[1]) ? intval($calories_match[1]) : 0,
                                isset($protein_match[1]) ? floatval($protein_match[1]) : 0,
                                isset($carbs_match[1]) ? floatval($carbs_match[1]) : 0,
                                isset($fat_match[1]) ? floatval($fat_match[1]) : 0,
                                $log_date,
                                $meal_id
                            ]);
                            
                            // Mettre à jour les totaux du repas
                            updateMealTotals($meal_id);
                        }
                    }
                    
                    $success_message = "Repas ajouté avec succès";
                    redirect("food-log.php");
                } else {
                    $errors[] = "Une erreur s'est produite lors de l'ajout du repas";
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
    
    // Ajouter un aliment à un repas
    elseif ($post_action === 'add_food_to_meal') {
        $meal_id = intval($_POST['meal_id'] ?? 0);
        $food_id = intval($_POST['food_id'] ?? 0);
        $custom_food_name = sanitizeInput($_POST['custom_food_name'] ?? '');
        $quantity = floatval($_POST['quantity'] ?? 0);
        $custom_calories = intval($_POST['custom_calories'] ?? 0);
        $custom_protein = floatval($_POST['custom_protein'] ?? 0);
        $custom_carbs = floatval($_POST['custom_carbs'] ?? 0);
        $custom_fat = floatval($_POST['custom_fat'] ?? 0);
        
        // Validation
        if ($meal_id <= 0) {
            $errors[] = "ID de repas invalide";
        }
        
        if ($food_id <= 0 && empty($custom_food_name)) {
            $errors[] = "Vous devez sélectionner un aliment ou saisir un nom d'aliment personnalisé";
        }
        
        if ($quantity <= 0) {
            $errors[] = "La quantité doit être supérieure à 0";
        }
        
        if (empty($errors)) {
            try {
                // Récupérer la date du repas
                $sql = "SELECT log_date FROM meals WHERE id = ?";
                $meal = fetchOne($sql, [$meal_id]);
                
                if (!$meal) {
                    $errors[] = "Repas introuvable";
                } else {
                    // Insérer l'aliment dans le journal alimentaire
                    $sql = "INSERT INTO food_logs (user_id, food_id, custom_food_name, quantity, custom_calories, custom_protein, custom_carbs, custom_fat, log_date, meal_id, is_part_of_meal, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
                    $result = insert($sql, [
                        $user_id, 
                        $food_id > 0 ? $food_id : 0,  // Utiliser 0 au lieu de NULL
                        !empty($custom_food_name) ? $custom_food_name : '', 
                        $quantity, 
                        $custom_calories, 
                        $custom_protein, 
                        $custom_carbs, 
                        $custom_fat, 
                        $meal['log_date'],
                        $meal_id
                    ]);
                    
                    if ($result) {
                        // Debug: Afficher les informations de l'aliment ajouté
                        error_log("Aliment ajouté avec succès: " . print_r([
                            'food_id' => $food_id,
                            'custom_food_name' => $custom_food_name,
                            'quantity' => $quantity,
                            'custom_calories' => $custom_calories,
                            'custom_protein' => $custom_protein,
                            'custom_carbs' => $custom_carbs,
                            'custom_fat' => $custom_fat
                        ], true));
                        
                        // Mettre à jour les totaux du repas
                        updateMealTotals($meal_id);
                        $success_message = "Aliment ajouté au repas avec succès";
                    } else {
                        $errors[] = "Une erreur s'est produite lors de l'ajout de l'aliment";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
    
    // Enregistrer un repas prédéfini
    elseif ($post_action === 'save_predefined_meal') {
        $meal_id = intval($_POST['meal_id'] ?? 0);
        $predefined_name = sanitizeInput($_POST['predefined_name'] ?? '');
        $predefined_description = sanitizeInput($_POST['predefined_description'] ?? '');
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        
        // Validation
        if ($meal_id <= 0) {
            $errors[] = "ID de repas invalide";
        }
        
        if (empty($predefined_name)) {
            $errors[] = "Le nom du repas prédéfini est requis";
        }
        
        if (empty($errors)) {
            try {
                // Vérifier si le repas existe et appartient à l'utilisateur
                $sql = "SELECT id FROM meals WHERE id = ? AND user_id = ?";
                $meal = fetchOne($sql, [$meal_id, $user_id]);
                
                if (!$meal) {
                    $errors[] = "Repas introuvable ou vous n'avez pas les droits pour le modifier";
                } else {
                    // Créer le repas prédéfini
                    $sql = "INSERT INTO predefined_meals (user_id, name, description, is_public, created_at) 
                            VALUES (?, ?, ?, ?, NOW())";
                    $predefined_meal_id = insert($sql, [$user_id, $predefined_name, $predefined_description, $is_public]);
                    
                    if ($predefined_meal_id) {
                        // Récupérer tous les aliments du repas
                        $sql = "SELECT * FROM food_logs WHERE meal_id = ?";
                        $foods = fetchAll($sql, [$meal_id]);
                        
                        // Ajouter chaque aliment au repas prédéfini
                        foreach ($foods as $food) {
                            $sql = "INSERT INTO predefined_meal_items (predefined_meal_id, food_id, quantity, notes, created_at) 
                                    VALUES (?, ?, ?, ?, NOW())";
                            insert($sql, [
                                $predefined_meal_id,
                                $food['food_id'],
                                $food['quantity'],
                                json_encode([
                                    'custom_food_name' => $food['custom_food_name'],
                                    'custom_calories' => $food['custom_calories'],
                                    'custom_protein' => $food['custom_protein'],
                                    'custom_carbs' => $food['custom_carbs'],
                                    'custom_fat' => $food['custom_fat']
                                ])
                            ]);
                        }
                        
                        $success_message = "Repas prédéfini enregistré avec succès";
                    } else {
                        $errors[] = "Une erreur s'est produite lors de l'enregistrement du repas prédéfini";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
    
    // Utiliser un repas prédéfini
    elseif ($post_action === 'use_predefined_meal') {
        $predefined_meal_id = intval($_POST['predefined_meal_id'] ?? 0);
        $log_date = sanitizeInput($_POST['log_date'] ?? date('Y-m-d'));
        $meal_type = sanitizeInput($_POST['meal_type'] ?? '');
        
        // Validation
        if ($predefined_meal_id <= 0) {
            $errors[] = "ID de repas prédéfini invalide";
        }
        
        if (empty($log_date) || !validateDate($log_date)) {
            $errors[] = "La date n'est pas valide";
        }
        
        if (empty($meal_type)) {
            $errors[] = "Le type de repas est requis";
        }
        
        if (empty($errors)) {
            try {
                // Récupérer les informations du repas prédéfini
                $sql = "SELECT * FROM predefined_meals WHERE id = ? AND (user_id = ? OR is_public = 1 OR created_by_admin = 1)";
                $predefined_meal = fetchOne($sql, [$predefined_meal_id, $user_id]);
                
                if (!$predefined_meal) {
                    $errors[] = "Repas prédéfini introuvable ou vous n'avez pas les droits pour l'utiliser";
                } else {
                    // Créer un nouveau repas
                    $sql = "INSERT INTO meals (user_id, name, meal_type, log_date, notes, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())";
                    $new_meal_id = insert($sql, [
                        $user_id, 
                        $predefined_meal['name'], 
                        $meal_type, 
                        $log_date, 
                        "Créé à partir du repas prédéfini: " . $predefined_meal['name']
                    ]);
                    
                    if ($new_meal_id) {
                        // Récupérer tous les aliments du repas prédéfini
                        $sql = "SELECT * FROM predefined_meal_items WHERE predefined_meal_id = ?";
                        $foods = fetchAll($sql, [$predefined_meal_id]);
                        
                        // Ajouter chaque aliment au nouveau repas
                        foreach ($foods as $food) {
                            $custom_data = json_decode($food['notes'], true) ?? [];
                            
                            $sql = "INSERT INTO food_logs (user_id, food_id, custom_food_name, quantity, custom_calories, custom_protein, custom_carbs, custom_fat, log_date, meal_id, is_part_of_meal, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
                            insert($sql, [
                                $user_id, 
                                $food['food_id'] ?? 0,
                                $food['custom_food_name'] ?? '', 
                                $food['quantity'], 
                                $food['custom_calories'], 
                                $food['custom_protein'], 
                                $food['custom_carbs'], 
                                $food['custom_fat'], 
                                $log_date,
                                $new_meal_id
                            ]);
                        }
                        
                        // Debug: Afficher l'ID du nouveau repas
                        error_log("Mise à jour des totaux pour le nouveau repas ID: " . $new_meal_id);
                        
                        // Mettre à jour les totaux du nouveau repas
                        updateMealTotals($new_meal_id);
                        
                        $success_message = "Repas ajouté avec succès à partir du modèle prédéfini";
                        redirect("food-log.php");
                    } else {
                        $errors[] = "Une erreur s'est produite lors de la création du repas";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
    
    // Supprimer un aliment d'un repas
    elseif ($post_action === 'remove_food_from_meal') {
        $food_log_id = intval($_POST['food_log_id'] ?? 0);
        
        // Validation
        if ($food_log_id <= 0) {
            $errors[] = "ID d'aliment invalide";
        }
        
        if (empty($errors)) {
            try {
                // Vérifier si l'aliment existe et appartient à l'utilisateur
                $sql = "SELECT id FROM food_logs WHERE id = ? AND user_id = ?";
                $food_log = fetchOne($sql, [$food_log_id, $user_id]);
                
                if (!$food_log) {
                    $errors[] = "Aliment introuvable ou vous n'avez pas les droits pour le supprimer";
                } else {
                    // Supprimer l'aliment
                    $sql = "DELETE FROM food_logs WHERE id = ?";
                    $result = update($sql, [$food_log_id]);
                    
                    if ($result) {
                        // Debug: Afficher l'ID de l'aliment supprimé
                        error_log("Aliment supprimé avec succès. ID: " . $food_log_id);
                        
                        // Mettre à jour les totaux du repas
                        updateMealTotals($meal_id);
                        $success_message = "Aliment supprimé du repas avec succès";
                    } else {
                        $errors[] = "Une erreur s'est produite lors de la suppression de l'aliment";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
    
    // Supprimer un repas
    elseif ($post_action === 'delete_meal') {
        error_log("=== DÉBUT DE LA SUPPRESSION DE REPAS ===");
        $meal_id = intval($_POST['meal_id'] ?? 0);
        error_log("ID du repas à supprimer : " . $meal_id);
        
        // Validation
        if ($meal_id <= 0) {
            error_log("Erreur : ID de repas invalide");
            $errors[] = "ID de repas invalide";
        }
        
        if (empty($errors)) {
            try {
                // Vérifier si le repas existe et appartient à l'utilisateur
                $sql = "SELECT id FROM meals WHERE id = ? AND user_id = ?";
                error_log("SQL de vérification : " . $sql);
                error_log("Paramètres : meal_id=" . $meal_id . ", user_id=" . $user_id);
                $meal = fetchOne($sql, [$meal_id, $user_id]);
                
                if (!$meal) {
                    error_log("Erreur : Repas introuvable ou non autorisé");
                    $errors[] = "Repas introuvable ou vous n'avez pas les droits pour le supprimer";
                } else {
                    error_log("Repas trouvé, début de la suppression");
                    
                    // Supprimer tous les aliments du repas
                    $sql = "DELETE FROM food_logs WHERE meal_id = ?";
                    error_log("SQL de suppression des aliments : " . $sql);
                    error_log("Paramètres : meal_id=" . $meal_id);
                    $food_delete_result = update($sql, [$meal_id]);
                    error_log("Résultat de la suppression des aliments : " . ($food_delete_result ? "Succès" : "Échec"));
                    
                    // Supprimer le repas
                    $sql = "DELETE FROM meals WHERE id = ?";
                    error_log("SQL de suppression du repas : " . $sql);
                    error_log("Paramètres : meal_id=" . $meal_id);
                    $result = update($sql, [$meal_id]);
                    error_log("Résultat de la suppression du repas : " . ($result ? "Succès" : "Échec"));
                    
                    if ($result) {
                        error_log("Suppression réussie, redirection vers food-log.php");
                        $success_message = "Repas supprimé avec succès";
                        redirect("food-log.php");
                    } else {
                        error_log("Erreur lors de la suppression du repas");
                        $errors[] = "Une erreur s'est produite lors de la suppression du repas";
                    }
                }
            } catch (Exception $e) {
                error_log("Exception lors de la suppression : " . $e->getMessage());
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
        error_log("=== FIN DE LA SUPPRESSION DE REPAS ===");
    }
    
    // Supprimer une suggestion
    elseif ($post_action === 'delete_suggestion') {
        $suggestion_id = intval($_POST['suggestion_id'] ?? 0);
        
        // Validation
        if ($suggestion_id <= 0) {
            $errors[] = "ID de suggestion invalide";
        }
        
        if (empty($errors)) {
            try {
                // Vérifier si la suggestion existe et appartient à l'utilisateur
                $sql = "SELECT id FROM ai_suggestions WHERE id = ? AND user_id = ?";
                $suggestion = fetchOne($sql, [$suggestion_id, $user_id]);
                
                if (!$suggestion) {
                    $errors[] = "Suggestion introuvable ou vous n'avez pas les droits pour la supprimer";
                } else {
                    // Supprimer la suggestion
                    $sql = "DELETE FROM ai_suggestions WHERE id = ?";
                    $result = update($sql, [$suggestion_id]);
                    
                    if ($result) {
                        $success_message = "Suggestion supprimée avec succès";
                        redirect("food-log.php");
                    } else {
                        $errors[] = "Une erreur s'est produite lors de la suppression de la suggestion";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
}

// Traitement des actions GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("=== DÉBUT DU TRAITEMENT GET ===");
    error_log("Action GET : " . $action);
    error_log("ID du repas : " . $meal_id);
    
    if ($action === 'delete_meal' && $meal_id > 0) {
        error_log("=== DÉBUT DE LA SUPPRESSION DE REPAS VIA GET ===");
        try {
            // Vérifier si le repas existe et appartient à l'utilisateur
            $sql = "SELECT id, meal_type FROM meals WHERE id = ? AND user_id = ?";
            error_log("SQL de vérification : " . $sql);
            error_log("Paramètres : meal_id=" . $meal_id . ", user_id=" . $user_id);
            $meal = fetchOne($sql, [$meal_id, $user_id]);
            
            if (!$meal) {
                error_log("Erreur : Repas introuvable ou non autorisé");
                $errors[] = "Repas introuvable ou vous n'avez pas les droits pour le supprimer";
            } else {
                error_log("Repas trouvé, début de la suppression");
                
                // Supprimer tous les aliments du repas
                $sql = "DELETE FROM food_logs WHERE meal_id = ?";
                error_log("SQL de suppression des aliments : " . $sql);
                error_log("Paramètres : meal_id=" . $meal_id);
                $food_delete_result = update($sql, [$meal_id]);
                error_log("Résultat de la suppression des aliments : " . ($food_delete_result ? "Succès" : "Échec"));
                
                // Supprimer le repas
                $sql = "DELETE FROM meals WHERE id = ?";
                error_log("SQL de suppression du repas : " . $sql);
                error_log("Paramètres : meal_id=" . $meal_id);
                $result = update($sql, [$meal_id]);
                error_log("Résultat de la suppression du repas : " . ($result ? "Succès" : "Échec"));
                
                if ($result) {
                    error_log("Suppression réussie, redirection vers food-log.php");
                    $success_message = "Repas supprimé avec succès";
                    redirect("food-log.php");
                } else {
                    error_log("Erreur lors de la suppression du repas");
                    $errors[] = "Une erreur s'est produite lors de la suppression du repas";
                }
            }
        } catch (Exception $e) {
            error_log("Exception lors de la suppression : " . $e->getMessage());
            $errors[] = "Erreur: " . $e->getMessage();
        }
        error_log("=== FIN DE LA SUPPRESSION DE REPAS VIA GET ===");
    }
}

// Récupérer la date sélectionnée ou utiliser la date du jour
$selected_date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');
$previous_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));

// Récupérer les objectifs quotidiens de l'utilisateur
$sql = "SHOW TABLES LIKE 'user_goals'";
$table_exists = fetchOne($sql);

if (!$table_exists) {
    // Créer la table user_goals si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS user_goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        daily_calories INT DEFAULT 2000,
        daily_protein INT DEFAULT 50,
        daily_carbs INT DEFAULT 250,
        daily_fat INT DEFAULT 70,
        daily_sodium INT DEFAULT 2300,
        daily_sugar INT DEFAULT 50,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    try {
        $pdo->exec($sql);
        // Insérer les objectifs par défaut pour l'utilisateur
        $sql = "INSERT INTO user_goals (user_id) VALUES (?)";
        insert($sql, [$user_id]);
    } catch (Exception $e) {
        error_log("Erreur lors de la création de la table user_goals : " . $e->getMessage());
    }
}

// Récupérer les objectifs quotidiens de l'utilisateur
$sql = "SELECT daily_calories, daily_protein, daily_carbs, daily_fat, daily_sodium, daily_sugar FROM user_goals WHERE user_id = ?";
$goals = fetchOne($sql, [$user_id]) ?? [
    'daily_calories' => 2000,
    'daily_protein' => 50,
    'daily_carbs' => 250,
    'daily_fat' => 70,
    'daily_sodium' => 2300,
    'daily_sugar' => 50
];

// Fonction pour obtenir les repas d'un type spécifique pour la date sélectionnée
function getMealsByType($user_id, $date, $meal_type) {
    $sql = "SELECT m.*, 
            SUM(fl.custom_calories) as total_calories,
            SUM(fl.custom_protein) as total_protein,
            SUM(fl.custom_carbs) as total_carbs,
            SUM(fl.custom_fat) as total_fat
            FROM meals m 
            LEFT JOIN food_logs fl ON m.id = fl.meal_id
            WHERE m.user_id = ? AND m.log_date = ? AND m.meal_type = ?
            GROUP BY m.id";
    return fetchAll($sql, [$user_id, $date, $meal_type]);
}

// Récupérer les totaux de la journée
$sql = "SELECT 
        SUM(fl.custom_calories) as total_calories,
        SUM(fl.custom_protein) as total_protein,
        SUM(fl.custom_carbs) as total_carbs,
        SUM(fl.custom_fat) as total_fat
        FROM meals m 
        LEFT JOIN food_logs fl ON m.id = fl.meal_id
        WHERE m.user_id = ? AND m.log_date = ?";
$daily_totals = fetchOne($sql, [$user_id, $selected_date]) ?? [
    'total_calories' => 0,
    'total_protein' => 0,
    'total_carbs' => 0,
    'total_fat' => 0,
    'total_sodium' => 0,
    'total_sugar' => 0
];

// Récupérer les repas de la journée
$sql = "SELECT m.*, 
        COUNT(fl.id) as food_count,
        SUM(fl.custom_calories) as total_calories,
        SUM(fl.custom_protein) as total_protein,
        SUM(fl.custom_carbs) as total_carbs,
        SUM(fl.custom_fat) as total_fat
        FROM meals m 
        LEFT JOIN food_logs fl ON m.id = fl.meal_id
        WHERE m.user_id = ? AND m.log_date = ?
        GROUP BY m.id
        ORDER BY m.meal_type, m.created_at";
$meals = fetchAll($sql, [$user_id, $selected_date]);

// Récupérer les repas prédéfinis
$sql = "SELECT * FROM predefined_meals 
        WHERE user_id = ? OR is_public = 1 OR created_by_admin = 1
        ORDER BY created_by_admin DESC, is_public DESC, created_at DESC";
$predefined_meals = fetchAll($sql, [$user_id]);

// Récupérer l'historique des repas
$sql = "SELECT m.*, 
        COUNT(fl.id) as food_count,
        SUM(fl.custom_calories) as total_calories
        FROM meals m 
        LEFT JOIN food_logs fl ON m.id = fl.meal_id
        WHERE m.user_id = ?
        GROUP BY m.id
        ORDER BY m.log_date DESC, m.meal_type
        LIMIT 10";
$meal_history = fetchAll($sql, [$user_id]);

// Inclure l'en-tête
include 'header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- En-tête du journal -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Votre journal alimentaire pour:</h1>
        <div class="flex items-center space-x-4">
            <a href="?date=<?= $previous_date ?>" class="btn btn-secondary">
                <i class="fas fa-chevron-left"></i>
            </a>
            <div class="text-xl font-semibold">
                <?= date('d/m/Y', strtotime($selected_date)) ?>
            </div>
            <a href="?date=<?= $next_date ?>" class="btn btn-secondary">
                <i class="fas fa-chevron-right"></i>
            </a>
            <button class="btn btn-primary" onclick="showDatePicker()">
                <i class="fas fa-calendar"></i>
            </button>
        </div>
    </div>

    <!-- En-tête des colonnes nutritionnelles -->
    <div class="grid grid-cols-6 gap-4 bg-blue-900 text-white p-3 rounded-t-lg">
        <div>Calories<br>kcal</div>
        <div>Glucides<br>g</div>
        <div>Lipides<br>g</div>
        <div>Protéines<br>g</div>
        <div>Sodium<br>mg</div>
        <div>Sucres<br>g</div>
    </div>

    <!-- Section Petit Déjeuner -->
    <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Petit Déjeuner</h2>
            <button onclick="showAddFoodModal('petit_dejeuner')" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ajouter un aliment
            </button>
        </div>
        <?php
        $breakfast_meals = getMealsByType($user_id, $selected_date, 'petit_dejeuner');
        if (empty($breakfast_meals)): ?>
            <p class="text-gray-500 italic">Aucun aliment enregistré</p>
        <?php else:
            foreach ($breakfast_meals as $meal):
                // Récupérer les aliments du repas
                $sql = "SELECT fl.*, f.name as food_name 
                        FROM food_logs fl 
                        LEFT JOIN foods f ON fl.food_id = f.id 
                        WHERE fl.meal_id = ?";
                $foods = fetchAll($sql, [$meal['id']]);
                ?>
                <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                    <div class="grid grid-cols-6 gap-4">
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_calories']) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_carbs'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_fat'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_protein'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_sodium'] ?? 0) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_sugar'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <?php foreach ($foods as $food): ?>
                            <div class="flex justify-between items-center py-2 border-b">
                                <div>
                                    <?= $food['food_name'] ?: $food['custom_food_name'] ?>
                                    <span class="text-sm text-gray-500">(<?= $food['quantity'] ?>g)</span>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="editFood(<?= $food['id'] ?>)" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteFood(<?= $food['id'] ?>)" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach;
        endif;
        ?>
    </div>

    <!-- Section Déjeuner -->
    <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Déjeuner</h2>
            <button onclick="showAddFoodModal('dejeuner')" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ajouter un aliment
            </button>
        </div>
        <?php
        $lunch_meals = getMealsByType($user_id, $selected_date, 'dejeuner');
        if (empty($lunch_meals)): ?>
            <p class="text-gray-500 italic">Aucun aliment enregistré</p>
        <?php else:
            foreach ($lunch_meals as $meal):
                // Récupérer les aliments du repas
                $sql = "SELECT fl.*, f.name as food_name 
                        FROM food_logs fl 
                        LEFT JOIN foods f ON fl.food_id = f.id 
                        WHERE fl.meal_id = ?";
                $foods = fetchAll($sql, [$meal['id']]);
                ?>
                <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                    <div class="grid grid-cols-6 gap-4">
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_calories']) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_carbs'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_fat'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_protein'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_sodium'] ?? 0) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_sugar'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <?php foreach ($foods as $food): ?>
                            <div class="flex justify-between items-center py-2 border-b">
                                <div>
                                    <?= $food['food_name'] ?: $food['custom_food_name'] ?>
                                    <span class="text-sm text-gray-500">(<?= $food['quantity'] ?>g)</span>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="editFood(<?= $food['id'] ?>)" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteFood(<?= $food['id'] ?>)" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach;
        endif;
        ?>
    </div>

    <!-- Section Dîner -->
    <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Dîner</h2>
            <button onclick="showAddFoodModal('diner')" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ajouter un aliment
            </button>
        </div>
        <?php
        $dinner_meals = getMealsByType($user_id, $selected_date, 'diner');
        if (empty($dinner_meals)): ?>
            <p class="text-gray-500 italic">Aucun aliment enregistré</p>
        <?php else:
            foreach ($dinner_meals as $meal):
                // Récupérer les aliments du repas
                $sql = "SELECT fl.*, f.name as food_name 
                        FROM food_logs fl 
                        LEFT JOIN foods f ON fl.food_id = f.id 
                        WHERE fl.meal_id = ?";
                $foods = fetchAll($sql, [$meal['id']]);
                ?>
                <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                    <div class="grid grid-cols-6 gap-4">
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_calories']) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_carbs'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_fat'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_protein'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_sodium'] ?? 0) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_sugar'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <?php foreach ($foods as $food): ?>
                            <div class="flex justify-between items-center py-2 border-b">
                                <div>
                                    <?= $food['food_name'] ?: $food['custom_food_name'] ?>
                                    <span class="text-sm text-gray-500">(<?= $food['quantity'] ?>g)</span>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="editFood(<?= $food['id'] ?>)" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteFood(<?= $food['id'] ?>)" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach;
        endif;
        ?>
    </div>

    <!-- Section Snacks -->
    <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Snacks</h2>
            <button onclick="showAddFoodModal('snack')" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ajouter un aliment
            </button>
        </div>
        <?php
        $snack_meals = getMealsByType($user_id, $selected_date, 'snack');
        if (empty($snack_meals)): ?>
            <p class="text-gray-500 italic">Aucun aliment enregistré</p>
        <?php else:
            foreach ($snack_meals as $meal):
                // Récupérer les aliments du repas
                $sql = "SELECT fl.*, f.name as food_name 
                        FROM food_logs fl 
                        LEFT JOIN foods f ON fl.food_id = f.id 
                        WHERE fl.meal_id = ?";
                $foods = fetchAll($sql, [$meal['id']]);
                ?>
                <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                    <div class="grid grid-cols-6 gap-4">
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_calories']) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_carbs'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_fat'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_protein'], 1) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_sodium'] ?? 0) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold"><?= number_format($meal['total_sugar'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <?php foreach ($foods as $food): ?>
                            <div class="flex justify-between items-center py-2 border-b">
                                <div>
                                    <?= $food['food_name'] ?: $food['custom_food_name'] ?>
                                    <span class="text-sm text-gray-500">(<?= $food['quantity'] ?>g)</span>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="editFood(<?= $food['id'] ?>)" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteFood(<?= $food['id'] ?>)" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach;
        endif;
        ?>
    </div>

    <!-- Totaux de la journée -->
    <div class="mt-8">
        <div class="grid grid-cols-6 gap-4 bg-gray-100 p-4 rounded-lg">
            <div class="text-center">
                <div class="font-bold"><?= number_format($daily_totals['total_calories']) ?></div>
                <div class="text-sm text-gray-600">/ <?= number_format($goals['daily_calories']) ?></div>
            </div>
            <div class="text-center">
                <div class="font-bold"><?= number_format($daily_totals['total_carbs'], 1) ?></div>
                <div class="text-sm text-gray-600">/ <?= number_format($goals['daily_carbs']) ?></div>
            </div>
            <div class="text-center">
                <div class="font-bold"><?= number_format($daily_totals['total_fat'], 1) ?></div>
                <div class="text-sm text-gray-600">/ <?= number_format($goals['daily_fat']) ?></div>
            </div>
            <div class="text-center">
                <div class="font-bold"><?= number_format($daily_totals['total_protein'], 1) ?></div>
                <div class="text-sm text-gray-600">/ <?= number_format($goals['daily_protein']) ?></div>
            </div>
            <div class="text-center">
                <div class="font-bold"><?= number_format($daily_totals['total_sodium'] ?? 0) ?></div>
                <div class="text-sm text-gray-600">/ <?= number_format($goals['daily_sodium']) ?></div>
            </div>
            <div class="text-center">
                <div class="font-bold"><?= number_format($daily_totals['total_sugar'] ?? 0, 1) ?></div>
                <div class="text-sm text-gray-600">/ <?= number_format($goals['daily_sugar']) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un aliment -->
<div id="addFoodModal" class="modal">
    <div class="modal-content">
        <h2>Ajouter un aliment</h2>
        <form id="addFoodForm" method="POST">
            <input type="hidden" name="action" value="add_food_to_meal">
            <input type="hidden" name="meal_type" id="meal_type">
            
            <div class="mb-4">
                <label for="food_id">Sélectionner un aliment:</label>
                <select name="food_id" id="food_id" class="form-select" required>
                    <option value="">Sélectionnez un aliment</option>
                    <?php
                    $sql = "SELECT * FROM foods ORDER BY name";
                    $foods = fetchAll($sql);
                    foreach ($foods as $food): ?>
                        <option value="<?= $food['id'] ?>" 
                                data-calories="<?= $food['calories'] ?>"
                                data-protein="<?= $food['protein'] ?>"
                                data-carbs="<?= $food['carbs'] ?>"
                                data-fat="<?= $food['fat'] ?>">
                            <?= htmlspecialchars($food['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="custom_food_name">Ou saisir un aliment personnalisé:</label>
                <input type="text" name="custom_food_name" id="custom_food_name" class="form-input">
            </div>

            <div class="mb-4">
                <label for="quantity">Quantité (g):</label>
                <input type="number" name="quantity" id="quantity" class="form-input" value="100" required>
            </div>

            <div class="mb-4">
                <label for="custom_calories">Calories:</label>
                <input type="number" name="custom_calories" id="custom_calories" class="form-input" value="0">
            </div>

            <div class="mb-4">
                <label for="custom_protein">Protéines (g):</label>
                <input type="number" step="0.1" name="custom_protein" id="custom_protein" class="form-input" value="0">
            </div>

            <div class="mb-4">
                <label for="custom_carbs">Glucides (g):</label>
                <input type="number" step="0.1" name="custom_carbs" id="custom_carbs" class="form-input" value="0">
            </div>

            <div class="mb-4">
                <label for="custom_fat">Lipides (g):</label>
                <input type="number" step="0.1" name="custom_fat" id="custom_fat" class="form-input" value="0">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <button type="submit" class="btn btn-primary">Ajouter</button>
                <button type="button" onclick="closeAddFoodModal()" class="btn btn-secondary">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddFoodModal(mealType) {
    document.getElementById('meal_type').value = mealType;
    document.getElementById('addFoodModal').style.display = 'block';
}

function closeAddFoodModal() {
    document.getElementById('addFoodModal').style.display = 'none';
}

function showDatePicker() {
    // Implémenter l'ouverture du sélecteur de date
}

// Fermer le modal si on clique en dehors
window.onclick = function(event) {
    if (event.target == document.getElementById('addFoodModal')) {
        closeAddFoodModal();
    }
}

// Gestionnaire pour le sélecteur d'aliments
document.getElementById('food_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const quantity = document.getElementById('quantity').value;
    
    if (this.value) {
        // Récupérer les valeurs nutritionnelles de l'option sélectionnée
        const calories = selectedOption.dataset.calories;
        const protein = selectedOption.dataset.protein;
        const carbs = selectedOption.dataset.carbs;
        const fat = selectedOption.dataset.fat;
        
        // Calculer les valeurs en fonction de la quantité
        const ratio = quantity / 100;
        document.getElementById('custom_calories').value = Math.round(calories * ratio);
        document.getElementById('custom_protein').value = (protein * ratio).toFixed(1);
        document.getElementById('custom_carbs').value = (carbs * ratio).toFixed(1);
        document.getElementById('custom_fat').value = (fat * ratio).toFixed(1);
        
        // Vider le champ de nom personnalisé
        document.getElementById('custom_food_name').value = '';
    } else {
        // Réinitialiser les champs si aucun aliment n'est sélectionné
        document.getElementById('custom_calories').value = '0';
        document.getElementById('custom_protein').value = '0';
        document.getElementById('custom_carbs').value = '0';
        document.getElementById('custom_fat').value = '0';
    }
});

// Gestionnaire pour le champ de quantité
document.getElementById('quantity').addEventListener('input', function() {
    const foodSelect = document.getElementById('food_id');
    const selectedOption = foodSelect.options[foodSelect.selectedIndex];
    
    if (foodSelect.value) {
        const quantity = this.value;
        const calories = selectedOption.dataset.calories;
        const protein = selectedOption.dataset.protein;
        const carbs = selectedOption.dataset.carbs;
        const fat = selectedOption.dataset.fat;
        
        // Calculer les valeurs en fonction de la quantité
        const ratio = quantity / 100;
        document.getElementById('custom_calories').value = Math.round(calories * ratio);
        document.getElementById('custom_protein').value = (protein * ratio).toFixed(1);
        document.getElementById('custom_carbs').value = (carbs * ratio).toFixed(1);
        document.getElementById('custom_fat').value = (fat * ratio).toFixed(1);
    }
});

function editFood(foodId) {
    // Implémenter la modification d'un aliment
}

function deleteFood(foodId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet aliment ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="remove_food_from_meal">
            <input type="hidden" name="food_log_id" value="${foodId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'footer.php'; ?>
