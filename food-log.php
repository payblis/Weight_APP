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
                    $success_message = "Repas ajouté avec succès. Vous pouvez maintenant ajouter des aliments à ce repas.";
                    redirect("food-log.php?action=edit_meal&meal_id={$meal_id}");
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
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => "ID d'aliment invalide"]);
                exit;
            } else {
                $errors[] = "ID d'aliment invalide";
            }
        }
        
        if (empty($errors)) {
            try {
                // Vérifier si l'aliment existe et appartient à l'utilisateur
                $sql = "SELECT id, meal_id FROM food_logs WHERE id = ? AND user_id = ?";
                $food_log = fetchOne($sql, [$food_log_id, $user_id]);
                
                if (!$food_log) {
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => "Aliment introuvable ou vous n'avez pas les droits pour le supprimer"]);
                        exit;
                    } else {
                        $errors[] = "Aliment introuvable ou vous n'avez pas les droits pour le supprimer";
                    }
                } else {
                    // Supprimer l'aliment
                    $sql = "DELETE FROM food_logs WHERE id = ?";
                    $result = delete($sql, [$food_log_id]);
                    
                    if ($result) {
                        // Debug: Afficher l'ID de l'aliment supprimé
                        error_log("Aliment supprimé avec succès. ID: " . $food_log_id);
                        
                        // Mettre à jour les totaux du repas
                        updateMealTotals($food_log['meal_id']);
                        
                        // Mettre à jour le bilan calorique quotidien
                        updateDailyCalorieBalance($user_id);
                        
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true]);
                            exit;
                        } else {
                            $success_message = "Aliment supprimé du repas avec succès";
                            redirect("food-log.php");
                        }
                    } else {
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'error' => "Une erreur s'est produite lors de la suppression de l'aliment"]);
                            exit;
                        } else {
                            $errors[] = "Une erreur s'est produite lors de la suppression de l'aliment";
                        }
                    }
                }
            } catch (Exception $e) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                } else {
                    $errors[] = "Erreur: " . $e->getMessage();
                }
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
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => "ID de repas invalide"]);
                exit;
            } else {
                $errors[] = "ID de repas invalide";
            }
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
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => "Repas introuvable ou vous n'avez pas les droits pour le supprimer"]);
                        exit;
                    } else {
                        $errors[] = "Repas introuvable ou vous n'avez pas les droits pour le supprimer";
                    }
                } else {
                    error_log("Repas trouvé, début de la suppression");
                    
                    // Supprimer tous les aliments du repas
                    $sql = "DELETE FROM food_logs WHERE meal_id = ?";
                    error_log("SQL de suppression des aliments : " . $sql);
                    error_log("Paramètres : meal_id=" . $meal_id);
                    $food_delete_result = delete($sql, [$meal_id]);
                    error_log("Résultat de la suppression des aliments : " . ($food_delete_result ? "Succès" : "Échec"));
                    
                    // Supprimer le repas
                    $sql = "DELETE FROM meals WHERE id = ?";
                    error_log("SQL de suppression du repas : " . $sql);
                    error_log("Paramètres : meal_id=" . $meal_id);
                    $result = delete($sql, [$meal_id]);
                    error_log("Résultat de la suppression du repas : " . ($result ? "Succès" : "Échec"));
                    
                    if ($result) {
                        // Mettre à jour le bilan calorique quotidien
                        updateDailyCalorieBalance($user_id);
                        
                        error_log("Suppression réussie");
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true]);
                            exit;
                        } else {
                            $success_message = "Repas supprimé avec succès";
                            redirect("food-log.php");
                        }
                    } else {
                        error_log("Erreur lors de la suppression du repas");
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'error' => "Une erreur s'est produite lors de la suppression du repas"]);
                            exit;
                        } else {
                            $errors[] = "Une erreur s'est produite lors de la suppression du repas";
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Exception lors de la suppression : " . $e->getMessage());
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                } else {
                    $errors[] = "Erreur: " . $e->getMessage();
                }
            }
        }
        error_log("=== FIN DE LA SUPPRESSION DE REPAS ===");
    }
}

// Traitement des actions GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("=== DÉBUT DU TRAITEMENT GET ===");
    error_log("Action GET : " . $action);
    error_log("ID du repas : " . $meal_id);
    
    if ($action === 'get_stats') {
        $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');
        
        // Récupérer les repas du jour
        $sql = "SELECT * FROM meals WHERE user_id = ? AND log_date = ?";
        $meals = fetchAll($sql, [$user_id, $date]);
        
        // Récupérer les calories des exercices
        $sql = "SELECT COALESCE(SUM(calories_burned), 0) as total_burned 
                FROM exercise_logs 
                WHERE user_id = ? AND DATE(log_date) = ?";
        $exercise_calories = fetchOne($sql, [$user_id, $date])['total_burned'] ?? 0;
        
        // Récupérer l'objectif calorique
        $sql = "SELECT daily_calories as goal_calories FROM user_profiles WHERE user_id = ?";
        echo "<div class='debug-message'>";
        echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
        echo "<span class='debug-category'>[SQL]</span> ";
        echo "Requête objectif calorique : " . $sql;
        echo "<br>Paramètres : user_id=" . $user_id;
        echo "</div>";
        
        $daily_goal_result = fetchOne($sql, [$user_id]);
        
        // Si aucun objectif n'est trouvé, calculer et créer un objectif par défaut
        if (!$daily_goal_result) {
            echo "<div class='debug-message'>";
            echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
            echo "<span class='debug-category'>[CALCUL]</span> ";
            echo "Calcul de l'objectif calorique par défaut...";
            echo "</div>";
            
            // Récupérer les informations de l'utilisateur
            $sql = "SELECT * FROM users WHERE id = ?";
            $user_info = fetchOne($sql, [$user_id]);
            
            if ($user_info) {
                // Calculer le BMR (Basal Metabolic Rate) avec la formule de Mifflin-St Jeor
                $weight = $user_info['weight'] ?? 70; // poids en kg
                $height = $user_info['height'] ?? 170; // taille en cm
                $age = $user_info['age'] ?? 30; // âge
                $gender = $user_info['gender'] ?? 'M'; // genre
                
                // BMR = (10 × poids en kg) + (6,25 × taille en cm) - (5 × âge en années) + 5 (homme) ou -161 (femme)
                $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age);
                $bmr += ($gender === 'M') ? 5 : -161;
                
                // Calculer l'objectif calorique (BMR * 1.2 pour un mode de vie sédentaire)
                $daily_goal = round($bmr * 1.2);
                
                echo "<div class='debug-message'>";
                echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                echo "<span class='debug-category'>[CALCUL]</span> ";
                echo "BMR calculé : " . round($bmr) . " kcal";
                echo "<br>Objectif calorique calculé : " . $daily_goal . " kcal";
                echo "</div>";
                
                // Insérer l'objectif dans la table user_profiles
                $sql = "INSERT INTO user_profiles (user_id, daily_calories, created_at) VALUES (?, ?, NOW())";
                $result = insert($sql, [$user_id, $daily_goal]);
                
                if ($result) {
                    echo "<div class='debug-message debug-success'>";
                    echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                    echo "<span class='debug-category'>[SUCCÈS]</span> ";
                    echo "Objectif calorique créé avec succès : " . $daily_goal . " kcal";
                    echo "</div>";
                    
                    $daily_goal_result = ['goal_calories' => $daily_goal];
                } else {
                    echo "<div class='debug-message debug-error'>";
                    echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                    echo "<span class='debug-category'>[ERREUR]</span> ";
                    echo "Échec de la création de l'objectif calorique";
                    echo "</div>";
                }
            } else {
                echo "<div class='debug-message debug-error'>";
                echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                echo "<span class='debug-category'>[ERREUR]</span> ";
                echo "Impossible de trouver les informations de l'utilisateur";
                echo "</div>";
            }
        }
        
        echo "<div class='debug-message'>";
        echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
        echo "<span class='debug-category'>[OBJECTIF]</span> ";
        echo "Objectif calorique : " . ($daily_goal_result ? $daily_goal_result['goal_calories'] : 0);
        echo "<br>Résultat complet : " . print_r($daily_goal_result, true);
        echo "</div>";
        
        // Calculer les totaux
        $total_calories = array_sum(array_column($meals, 'total_calories'));
        $total_protein = array_sum(array_column($meals, 'total_protein'));
        $total_carbs = array_sum(array_column($meals, 'total_carbs'));
        $total_fat = array_sum(array_column($meals, 'total_fat'));
        $remaining_calories = $daily_goal - $total_calories + $exercise_calories;
        
        // Retourner les données en JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'daily_goal' => $daily_goal,
            'total_calories' => $total_calories,
            'exercise_calories' => $exercise_calories,
            'remaining_calories' => $remaining_calories,
            'total_protein' => round($total_protein, 1),
            'total_carbs' => round($total_carbs, 1),
            'total_fat' => round($total_fat, 1)
        ]);
        exit;
    }
    
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
                $food_delete_result = delete($sql, [$meal_id]);
                error_log("Résultat de la suppression des aliments : " . ($food_delete_result ? "Succès" : "Échec"));
                
                // Supprimer le repas
                $sql = "DELETE FROM meals WHERE id = ?";
                error_log("SQL de suppression du repas : " . $sql);
                error_log("Paramètres : meal_id=" . $meal_id);
                $result = delete($sql, [$meal_id]);
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

// Récupérer les repas de l'utilisateur
$date_filter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');
error_log("=== RÉCUPÉRATION DES REPAS ===");
error_log("Date filtrée : " . $date_filter);
error_log("User ID : " . $user_id);

$sql = "SELECT m.*, 
        (SELECT COUNT(*) FROM food_logs fl WHERE fl.meal_id = m.id) as food_count
        FROM meals m 
        WHERE m.user_id = ? AND m.log_date = ? 
        ORDER BY FIELD(m.meal_type, 'petit_dejeuner', 'dejeuner', 'diner', 'collation', 'autre')";
error_log("SQL de récupération des repas : " . $sql);
error_log("Paramètres : user_id=" . $user_id . ", date=" . $date_filter);
$meals = fetchAll($sql, [$user_id, $date_filter]);
error_log("Nombre de repas trouvés : " . count($meals));
error_log("=== FIN DE LA RÉCUPÉRATION DES REPAS ===");

// Récupérer les repas prédéfinis
$sql = "SELECT * FROM predefined_meals 
        WHERE user_id = ? OR is_public = 1 
        ORDER BY name";
$predefined_meals = fetchAll($sql, [$user_id]);

// Récupérer les aliments disponibles
$sql = "SELECT * FROM foods ORDER BY name";
$foods = fetchAll($sql, []);

// Récupérer les détails d'un repas spécifique si demandé
$meal_details = null;
$meal_foods = [];

if ($action === 'edit_meal' && $meal_id > 0) {
    $sql = "SELECT * FROM meals WHERE id = ? AND user_id = ?";
    $meal_details = fetchOne($sql, [$meal_id, $user_id]);
    
    if ($meal_details) {
        $sql = "SELECT fl.*, 
                f.name as food_name, 
                f.calories as food_calories, 
                f.protein as food_protein, 
                f.carbs as food_carbs, 
                f.fat as food_fat
                FROM food_logs fl 
                LEFT JOIN foods f ON fl.food_id = f.id 
                WHERE fl.meal_id = ? 
                ORDER BY fl.created_at";
        $meal_foods = fetchAll($sql, [$meal_id]);
    }
}

// Récupérer les détails d'un repas prédéfini si demandé
$predefined_meal_details = null;
$predefined_meal_foods = [];

if ($action === 'view_predefined_meal' && $predefined_meal_id > 0) {
    $sql = "SELECT * FROM predefined_meals WHERE id = ? AND (user_id = ? OR is_public = 1 OR created_by_admin = 1)";
    $predefined_meal_details = fetchOne($sql, [$predefined_meal_id, $user_id]);
    
    if ($predefined_meal_details) {
        $sql = "SELECT pmf.*, 
                f.name as food_name, 
                f.calories as food_calories, 
                f.protein as food_protein, 
                f.carbs as food_carbs, 
                f.fat as food_fat
                FROM predefined_meal_items pmf 
                LEFT JOIN foods f ON pmf.food_id = f.id 
                WHERE pmf.predefined_meal_id = ? 
                ORDER BY pmf.created_at";
        $predefined_meal_foods = fetchAll($sql, [$predefined_meal_id]);
    }
}

// Récupérer l'historique des repas
$sql = "SELECT m.*, 
        DATE_FORMAT(m.log_date, '%d/%m/%Y') as formatted_date,
        (SELECT COUNT(*) FROM food_logs fl WHERE fl.meal_id = m.id) as food_count
        FROM meals m 
        WHERE m.user_id = ? 
        ORDER BY m.log_date DESC, FIELD(m.meal_type, 'petit_dejeuner', 'dejeuner', 'diner', 'collation', 'autre')
        LIMIT 50";
$meal_history = fetchAll($sql, [$user_id]);

// Fonction pour obtenir le nom du type de repas
function getMealTypeName($type) {
    $types = [
        'petit_dejeuner' => 'Petit déjeuner',
        'dejeuner' => 'Déjeuner',
        'diner' => 'Dîner',
        'collation' => 'Collation',
        'autre' => 'Autre'
    ];
    
    return $types[$type] ?? $type;
}

// Fonction pour calculer les macronutriments d'un aliment
function calculateNutrients($food) {
    $calories = 0;
    $protein = 0;
    $carbs = 0;
    $fat = 0;
    
    if (isset($food['food_id']) && $food['food_id'] > 0 && isset($food['food_calories'])) {
        // Aliment de la base de données
        $calories = ($food['food_calories'] * $food['quantity']) / 100;
        $protein = ($food['food_protein'] * $food['quantity']) / 100;
        $carbs = ($food['food_carbs'] * $food['quantity']) / 100;
        $fat = ($food['food_fat'] * $food['quantity']) / 100;
    } else {
        // Aliment personnalisé
        $calories = $food['custom_calories'];
        $protein = $food['custom_protein'];
        $carbs = $food['custom_carbs'];
        $fat = $food['custom_fat'];
    }
    
    return [
        'calories' => round($calories),
        'protein' => round($protein, 1),
        'carbs' => round($carbs, 1),
        'fat' => round($fat, 1)
    ];
}

// Fonction pour mettre à jour les totaux d'un repas
function updateMealTotals($meal_id) {
    global $db;
    
    // Debug: Afficher l'ID du repas
    error_log("Mise à jour des totaux pour le repas ID: " . $meal_id);
    
    // Debug: Vérifier les aliments du repas avant la mise à jour
    $sql = "SELECT fl.*, f.name as food_name, f.calories as food_calories, f.protein as food_protein, f.carbs as food_carbs, f.fat as food_fat
            FROM food_logs fl 
            LEFT JOIN foods f ON fl.food_id = f.id 
            WHERE fl.meal_id = ?";
    $foods = fetchAll($sql, [$meal_id]);
    error_log("Aliments dans le repas avant mise à jour: " . print_r($foods, true));
    
    // Calculer les totaux
    $total_calories = 0;
    $total_protein = 0;
    $total_carbs = 0;
    $total_fat = 0;
    
    foreach ($foods as $food) {
        if ($food['food_id'] > 0) {
            // Aliment de la base de données
            $total_calories += ($food['food_calories'] * $food['quantity']) / 100;
            $total_protein += ($food['food_protein'] * $food['quantity']) / 100;
            $total_carbs += ($food['food_carbs'] * $food['quantity']) / 100;
            $total_fat += ($food['food_fat'] * $food['quantity']) / 100;
        } else {
            // Aliment personnalisé
            $total_calories += $food['custom_calories'];
            $total_protein += $food['custom_protein'];
            $total_carbs += $food['custom_carbs'];
            $total_fat += $food['custom_fat'];
        }
    }
    
    // Mettre à jour les totaux dans la table meals
    $sql = "UPDATE meals 
            SET total_calories = ?, 
                total_protein = ?, 
                total_carbs = ?, 
                total_fat = ?
            WHERE id = ?";
            
    $result = update($sql, [
        round($total_calories),
        round($total_protein, 1),
        round($total_carbs, 1),
        round($total_fat, 1),
        $meal_id
    ]);
    
    // Debug: Vérifier les totaux après la mise à jour
    $sql = "SELECT total_calories, total_protein, total_carbs, total_fat FROM meals WHERE id = ?";
    $totals = fetchOne($sql, [$meal_id]);
    error_log("Totaux après mise à jour: " . print_r($totals, true));
    
    return $result;
}

// Fonction pour mettre à jour le bilan calorique quotidien
function updateDailyCalorieBalance($user_id) {
    global $pdo;
    
    try {
        // Récupérer la date du jour
        $today = date('Y-m-d');
        
        // Récupérer les calories des repas du jour
        $sql = "SELECT COALESCE(SUM(total_calories), 0) as total_calories 
                FROM meals 
                WHERE user_id = ? AND log_date = ?";
        $meals_result = fetchOne($sql, [$user_id, $today]);
        $total_calories = $meals_result['total_calories'];
        
        // Récupérer les calories brûlées par l'exercice
        $sql = "SELECT COALESCE(SUM(calories_burned), 0) as total_burned 
                FROM exercise_logs 
                WHERE user_id = ? AND DATE(log_date) = ?";
        $exercise_result = fetchOne($sql, [$user_id, $today]);
        $exercise_calories = $exercise_result['total_burned'];
        
        // Récupérer l'objectif calorique
        $sql = "SELECT goal_calories FROM user_profiles WHERE user_id = ?";
        $goal_result = fetchOne($sql, [$user_id]);
        $daily_goal = $goal_result ? $goal_result['goal_calories'] : 0;
        
        // Calculer le bilan
        $balance = $daily_goal - $total_calories + $exercise_calories;
        
        // Mettre à jour ou insérer le bilan dans la table daily_calorie_balance
        $sql = "INSERT INTO daily_calorie_balance (user_id, date, total_calories, exercise_calories, daily_goal, balance) 
                VALUES (?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                total_calories = VALUES(total_calories),
                exercise_calories = VALUES(exercise_calories),
                daily_goal = VALUES(daily_goal),
                balance = VALUES(balance)";
        
        $result = update($sql, [$user_id, $today, $total_calories, $exercise_calories, $daily_goal, $balance]);
        
        error_log("Mise à jour du bilan calorique : " . print_r([
            'user_id' => $user_id,
            'date' => $today,
            'total_calories' => $total_calories,
            'exercise_calories' => $exercise_calories,
            'daily_goal' => $daily_goal,
            'balance' => $balance
        ], true));
        
        return $result;
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour du bilan calorique : " . $e->getMessage());
        return false;
    }
}

// Ajouter une fonction de débogage structurée
function debugLog($category, $message, $data = null, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] [$category] $message";
    if ($data !== null) {
        $log .= "\nData: " . print_r($data, true);
    }
    error_log($log);
    
    // Ajouter au panneau de débogage si on est dans le contexte web
    if (isset($_SERVER['REQUEST_URI'])) {
        echo "<div class='debug-message debug-{$type}'>";
        echo "<span class='debug-time'>[$timestamp]</span>";
        echo "<span class='debug-category'>[$category]</span> ";
        echo htmlspecialchars($message);
        if ($data !== null) {
            echo "<pre class='debug-data'>" . htmlspecialchars(print_r($data, true)) . "</pre>";
        }
        echo "</div>";
    }
}

// Fonction pour déboguer les requêtes SQL
function debugQuery($sql, $params = [], $result = null) {
    debugLog('SQL', "Query: $sql", [
        'params' => $params,
        'result' => $result
    ]);
}

// Fonction pour déboguer les données utilisateur
function debugUserData($user_id, $data) {
    debugLog('USER', "User ID: $user_id", $data);
}

// Fonction pour déboguer les repas
function debugMealData($meal_id, $data) {
    debugLog('MEAL', "Meal ID: $meal_id", $data);
}

// Fonction pour déboguer les aliments
function debugFoodData($food_id, $data) {
    debugLog('FOOD', "Food ID: $food_id", $data);
}

// Fonction pour déboguer les stats
function debugStats($data) {
    debugLog('STATS', "Stats Update", $data);
}

// Déboguer les calories brûlées
$sql = "SELECT SUM(calories_burned) as calories_out FROM exercise_logs WHERE user_id = ? AND log_date = ?";
echo "<div class='debug-message'>";
echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
echo "<span class='debug-category'>[SQL]</span> ";
echo "Requête calories brûlées : " . $sql;
echo "<br>Paramètres : user_id=" . $user_id . ", date=" . $date_filter;
echo "</div>";

$today_exercise = fetchOne($sql, [$user_id, $date_filter]);
echo "<div class='debug-message'>";
echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
echo "<span class='debug-category'>[EXERCICES]</span> ";
echo "Calories brûlées aujourd'hui : " . ($today_exercise ? round($today_exercise['calories_out'] ?? 0) : 0);
echo "<br>Résultat complet : " . print_r($today_exercise, true);
echo "</div>";

// Ajouter une requête pour voir les exercices individuels
$sql = "SELECT exercise_name, duration, calories_burned, log_date 
       FROM exercise_logs 
       WHERE user_id = ? AND log_date = ? 
       ORDER BY log_date DESC";
echo "<div class='debug-message'>";
echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
echo "<span class='debug-category'>[SQL]</span> ";
echo "Requête détails exercices : " . $sql;
echo "<br>Paramètres : user_id=" . $user_id . ", date=" . $date_filter;
echo "</div>";

$exercises = fetchAll($sql, [$user_id, $date_filter]);
echo "<div class='debug-message'>";
echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
echo "<span class='debug-category'>[EXERCICES]</span> ";
echo "Liste des exercices du jour :";
if (!empty($exercises)) {
    foreach ($exercises as $exercise) {
        echo "<br>- " . $exercise['exercise_name'] . " : " . 
             $exercise['duration'] . " min, " . 
             $exercise['calories_burned'] . " kcal";
    }
} else {
    echo "<br>Aucun exercice enregistré pour cette date";
}
echo "</div>";

// Vérifier la table exercise_logs
$sql = "SELECT COUNT(*) as total FROM exercise_logs WHERE user_id = ?";
echo "<div class='debug-message'>";
echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
echo "<span class='debug-category'>[SQL]</span> ";
echo "Requête total exercices : " . $sql;
echo "<br>Paramètres : user_id=" . $user_id;
echo "</div>";

$total_exercises = fetchOne($sql, [$user_id]);
echo "<div class='debug-message'>";
echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
echo "<span class='debug-category'>[EXERCICES]</span> ";
echo "Total des exercices enregistrés : " . ($total_exercises ? $total_exercises['total'] : 0);
echo "<br>Résultat complet : " . print_r($total_exercises, true);
echo "</div>";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal alimentaire - Weight Tracker</title>
    <!-- Prévention du cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .debug-panel {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
        }
        .debug-panel h4 {
            margin-bottom: 10px;
            color: #495057;
        }
        .debug-message {
            margin-bottom: 5px;
            padding: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        .debug-message:last-child {
            border-bottom: none;
        }
        .debug-time {
            color: #6c757d;
            margin-right: 5px;
        }
        .debug-category {
            color: #0d6efd;
            margin-right: 5px;
            font-weight: bold;
        }
        .debug-error {
            color: #dc3545;
            background-color: #f8d7da;
        }
        .debug-success {
            color: #28a745;
            background-color: #d4edda;
        }
        .debug-data {
            margin-top: 5px;
            padding: 5px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            font-size: 11px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
        <!-- Panneau de débogage des calories et exercices -->
        <div class="debug-panel">
            <h4>Debug Calories & Exercices</h4>
            <div id="debugContent">
                <?php
                // Déboguer les calories consommées
                $sql = "SELECT 
                    SUM(m.total_calories) as calories_in,
                    SUM(m.total_protein) as protein_in,
                    SUM(m.total_carbs) as carbs_in,
                    SUM(m.total_fat) as fat_in
                FROM meals m
                WHERE m.user_id = ? AND m.log_date = ?";
                echo "<div class='debug-message'>";
                echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                echo "<span class='debug-category'>[SQL]</span> ";
                echo "Requête calories consommées : " . $sql;
                echo "<br>Paramètres : user_id=" . $user_id . ", date=" . $date_filter;
                echo "</div>";
                
                $today_food = fetchOne($sql, [$user_id, $date_filter]);
                echo "<div class='debug-message'>";
                echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                echo "<span class='debug-category'>[CALORIES]</span> ";
                echo "Calories consommées aujourd'hui : " . ($today_food ? round($today_food['calories_in'] ?? 0) : 0);
                echo "<br>Résultat complet : " . print_r($today_food, true);
                echo "</div>";

                // Déboguer les calories brûlées
                $sql = "SELECT SUM(calories_burned) as calories_out FROM exercise_logs WHERE user_id = ? AND log_date = ?";
                echo "<div class='debug-message'>";
                echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                echo "<span class='debug-category'>[SQL]</span> ";
                echo "Requête calories brûlées : " . $sql;
                echo "<br>Paramètres : user_id=" . $user_id . ", date=" . $date_filter;
                echo "</div>";
                
                $today_exercise = fetchOne($sql, [$user_id, $date_filter]);
                echo "<div class='debug-message'>";
                echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                echo "<span class='debug-category'>[EXERCICES]</span> ";
                echo "Calories brûlées aujourd'hui : " . ($today_exercise ? round($today_exercise['calories_out'] ?? 0) : 0);
                echo "<br>Résultat complet : " . print_r($today_exercise, true);
                echo "</div>";

                // Ajouter une requête pour voir les exercices individuels
                $sql = "SELECT exercise_name, duration, calories_burned, log_date 
                       FROM exercise_logs 
                       WHERE user_id = ? AND log_date = ? 
                       ORDER BY log_date DESC";
                echo "<div class='debug-message'>";
                echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                echo "<span class='debug-category'>[SQL]</span> ";
                echo "Requête détails exercices : " . $sql;
                echo "<br>Paramètres : user_id=" . $user_id . ", date=" . $date_filter;
                echo "</div>";
                
                $exercises = fetchAll($sql, [$user_id, $date_filter]);
                echo "<div class='debug-message'>";
                echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                echo "<span class='debug-category'>[EXERCICES]</span> ";
                echo "Liste des exercices du jour :";
                if (!empty($exercises)) {
                    foreach ($exercises as $exercise) {
                        echo "<br>- " . $exercise['exercise_name'] . " : " . 
                             $exercise['duration'] . " min, " . 
                             $exercise['calories_burned'] . " kcal";
                    }
                } else {
                    echo "<br>Aucun exercice enregistré pour cette date";
                }
                echo "</div>";

                // Vérifier la table exercise_logs
                $sql = "SELECT COUNT(*) as total FROM exercise_logs WHERE user_id = ?";
                echo "<div class='debug-message'>";
                echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                echo "<span class='debug-category'>[SQL]</span> ";
                echo "Requête total exercices : " . $sql;
                echo "<br>Paramètres : user_id=" . $user_id;
                echo "</div>";
                
                $total_exercises = fetchOne($sql, [$user_id]);
                echo "<div class='debug-message'>";
                echo "<span class='debug-time'>[" . date('H:i:s') . "]</span>";
                echo "<span class='debug-category'>[EXERCICES]</span> ";
                echo "Total des exercices enregistrés : " . ($total_exercises ? $total_exercises['total'] : 0);
                echo "<br>Résultat complet : " . print_r($total_exercises, true);
                echo "</div>";
                ?>
            </div>
        </div>

        <!-- Stats globales -->
        <div class="global-stats">
            <div class="stats-grid">
                <div class="stats-item">
                    <div class="stats-value" id="daily-goal"><?php echo $daily_goal; ?></div>
                    <div class="stats-label">Objectif</div>
                </div>
                <div class="stats-item">
                    <div class="stats-operation">-</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value" id="total-calories"><?php echo array_sum(array_column($meals, 'total_calories')); ?></div>
                    <div class="stats-label">Aliments</div>
                </div>
                <div class="stats-item">
                    <div class="stats-operation">+</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value" id="exercise-calories"><?php echo $exercise_calories; ?></div>
                    <div class="stats-label">Exercice</div>
                </div>
                <div class="stats-item">
                    <div class="stats-operation">=</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value" id="remaining-calories"><?php echo $daily_goal - array_sum(array_column($meals, 'total_calories')) + $exercise_calories; ?></div>
                    <div class="stats-label">Restants</div>
                </div>
            </div>
            <div class="macros-grid">
                <div class="macro-item">
                    <div class="macro-value" id="total-protein"><?php echo round(array_sum(array_column($meals, 'total_protein')), 1); ?></div>
                    <div class="macro-label">Protéines (g)</div>
                </div>
                <div class="macro-item">
                    <div class="macro-value" id="total-carbs"><?php echo round(array_sum(array_column($meals, 'total_carbs')), 1); ?></div>
                    <div class="macro-label">Glucides (g)</div>
                </div>
                <div class="macro-item">
                    <div class="macro-value" id="total-fat"><?php echo round(array_sum(array_column($meals, 'total_fat')), 1); ?></div>
                    <div class="macro-label">Lipides (g)</div>
                </div>
            </div>
        </div>

        <div class="food-journal-header mb-4">
            <div class="date-navigation">
                <a href="?date=<?php echo date('Y-m-d', strtotime($date_filter . ' -1 day')); ?>" class="nav-arrow">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <div class="current-date">
                    <?php echo date('d/m/Y', strtotime($date_filter)); ?>
                </div>
                <a href="?date=<?php echo date('Y-m-d', strtotime($date_filter . ' +1 day')); ?>" class="nav-arrow">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($action === 'edit_meal' && $meal_details): ?>
            <!-- Édition d'un repas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Éditer le repas: <?php echo getMealTypeName($meal_details['meal_type']); ?></h5>
                </div>
                <div class="card-body">
                    <!-- Liste des aliments du repas -->
                    <?php if (!empty($meal_foods)): ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Aliment</th>
                                        <th>Quantité (g)</th>
                                        <th>Calories</th>
                                        <th>Protéines</th>
                                        <th>Glucides</th>
                                        <th>Lipides</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($meal_foods as $food): ?>
                                        <?php $nutrients = calculateNutrients($food); ?>
                                        <tr>
                                            <td><?php echo $food['food_id'] ? $food['food_name'] : $food['custom_food_name']; ?></td>
                                            <td><?php echo $food['quantity']; ?></td>
                                            <td><?php echo $nutrients['calories']; ?></td>
                                            <td><?php echo $nutrients['protein']; ?></td>
                                            <td><?php echo $nutrients['carbs']; ?></td>
                                            <td><?php echo $nutrients['fat']; ?></td>
                                            <td>
                                                <form action="food-log.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="remove_food_from_meal">
                                                    <input type="hidden" name="food_log_id" value="<?php echo $food['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet aliment ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">Aucun aliment dans ce repas</div>
                    <?php endif; ?>

                    <!-- Formulaire d'ajout d'aliment -->
                    <form action="food-log.php" method="POST">
                        <input type="hidden" name="action" value="add_food_to_meal">
                        <input type="hidden" name="meal_id" value="<?php echo $meal_details['id']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="food_id" class="form-label">Aliment</label>
                                <select class="form-select" id="food_id" name="food_id">
                                    <option value="">Sélectionnez un aliment</option>
                                    <?php foreach ($foods as $food): ?>
                                        <option value="<?php echo $food['id']; ?>" 
                                                data-calories="<?php echo $food['calories']; ?>"
                                                data-protein="<?php echo $food['protein']; ?>"
                                                data-carbs="<?php echo $food['carbs']; ?>"
                                                data-fat="<?php echo $food['fat']; ?>">
                                            <?php echo $food['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="custom_food_name" class="form-label">Ou nom personnalisé</label>
                                <input type="text" class="form-control" id="custom_food_name" name="custom_food_name">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label for="quantity" class="form-label">Quantité (g)</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="100" min="1">
                            </div>
                            <div class="col">
                                <label for="custom_calories" class="form-label">Calories</label>
                                <input type="number" class="form-control" id="custom_calories" name="custom_calories" value="0">
                            </div>
                            <div class="col">
                                <label for="custom_protein" class="form-label">Protéines (g)</label>
                                <input type="number" class="form-control" id="custom_protein" name="custom_protein" value="0" step="0.1">
                            </div>
                            <div class="col">
                                <label for="custom_carbs" class="form-label">Glucides (g)</label>
                                <input type="number" class="form-control" id="custom_carbs" name="custom_carbs" value="0" step="0.1">
                            </div>
                            <div class="col">
                                <label for="custom_fat" class="form-label">Lipides (g)</label>
                                <input type="number" class="form-control" id="custom_fat" name="custom_fat" value="0" step="0.1">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="food-log.php" class="btn btn-secondary">Retour</a>
                            <button type="submit" class="btn btn-primary">Ajouter l'aliment</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Affichage des repas par type -->
            <?php
            $meal_types = [
                'petit_dejeuner' => 'Petit Déjeuner',
                'dejeuner' => 'Déjeuner',
                'diner' => 'Dîner',
                'collation' => 'Collation'
            ];

            foreach ($meal_types as $type => $label):
                $type_meals = array_filter($meals, function($meal) use ($type) {
                    return $meal['meal_type'] === $type;
                });
            ?>
                <div class="meal-section">
                    <div class="meal-header">
                        <h3 class="meal-title"><?php echo $label; ?></h3>
                        <div class="meal-actions">
                            <?php if (empty($type_meals)): ?>
                                <form action="food-log.php" method="POST">
                                    <input type="hidden" name="action" value="add_meal">
                                    <input type="hidden" name="meal_type" value="<?php echo $type; ?>">
                                    <input type="hidden" name="date" value="<?php echo $date_filter; ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i>Créer le repas
                                    </button>
                                </form>
                            <?php else: 
                                $meal = reset($type_meals);
                            ?>
                                <div class="d-flex gap-2">
                                    <a href="food-log.php?action=edit_meal&meal_id=<?php echo $meal['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i>Ajouter un aliment
                                    </a>
                                    <form action="food-log.php" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce repas ?');">
                                        <input type="hidden" name="action" value="delete_meal">
                                        <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (empty($type_meals)): ?>
                        <div class="meal-empty">
                            Aucun repas enregistré
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Aliment</th>
                                        <th>Qté</th>
                                        <th>Cal</th>
                                        <th>Prot</th>
                                        <th>Gluc</th>
                                        <th>Lip</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $meal = reset($type_meals);
                                    $sql = "SELECT fl.*, f.name as food_name, f.calories, f.protein, f.carbs, f.fat 
                                           FROM food_logs fl 
                                           LEFT JOIN foods f ON fl.food_id = f.id 
                                           WHERE fl.meal_id = ?";
                                    $meal_foods = fetchAll($sql, [$meal['id']]);
                                    
                                    foreach ($meal_foods as $food):
                                        $calories = $food['food_id'] ? ($food['calories'] * $food['quantity'] / 100) : $food['custom_calories'];
                                        $protein = $food['food_id'] ? ($food['protein'] * $food['quantity'] / 100) : $food['custom_protein'];
                                        $carbs = $food['food_id'] ? ($food['carbs'] * $food['quantity'] / 100) : $food['custom_carbs'];
                                        $fat = $food['food_id'] ? ($food['fat'] * $food['quantity'] / 100) : $food['custom_fat'];
                                    ?>
                                        <tr>
                                            <td><?php echo $food['food_id'] ? $food['food_name'] : $food['custom_food_name']; ?></td>
                                            <td><?php echo $food['quantity']; ?>g</td>
                                            <td><?php echo round($calories); ?></td>
                                            <td><?php echo round($protein, 1); ?></td>
                                            <td><?php echo round($carbs, 1); ?></td>
                                            <td><?php echo round($fat, 1); ?></td>
                                            <td>
                                                <form action="food-log.php" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet aliment ?');">
                                                    <input type="hidden" name="action" value="remove_food_from_meal">
                                                    <input type="hidden" name="food_log_id" value="<?php echo $food['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour ajouter un message de débogage
            function addDebugMessage(message, category) {
                const content = document.getElementById('debugContent');
                const time = new Date().toLocaleTimeString();
                const messageElement = document.createElement('div');
                messageElement.className = 'debug-message';
                messageElement.innerHTML = `
                    <span class='debug-time'>[${time}]</span>
                    <span class='debug-category'>[${category}]</span>
                    ${message}
                `;
                content.appendChild(messageElement);
                content.scrollTop = content.scrollHeight;
            }

            // Modifier la fonction updateStats pour inclure le débogage
            function updateStats() {
                addDebugMessage("Début de la mise à jour des stats", 'CALORIES');
                fetch('food-log.php?action=get_stats&date=<?php echo $date_filter; ?>')
                    .then(response => {
                        addDebugMessage(`Réponse stats reçue : ${response.status}`, 'CALORIES');
                        if (!response.ok) {
                            throw new Error('Erreur réseau: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        addDebugMessage(`Données stats reçues : ${JSON.stringify(data)}`, 'CALORIES');
                        
                        // Mise à jour des calories
                        const dailyGoal = document.getElementById('daily-goal');
                        const totalCalories = document.getElementById('total-calories');
                        const exerciseCalories = document.getElementById('exercise-calories');
                        const remainingCalories = document.getElementById('remaining-calories');
                        
                        if (dailyGoal) {
                            addDebugMessage(`Objectif quotidien : ${data.daily_goal}`, 'CALORIES');
                            dailyGoal.textContent = data.daily_goal;
                        }
                        if (totalCalories) {
                            addDebugMessage(`Calories consommées : ${data.total_calories}`, 'CALORIES');
                            totalCalories.textContent = data.total_calories;
                        }
                        if (exerciseCalories) {
                            addDebugMessage(`Calories brûlées : ${data.exercise_calories}`, 'EXERCICES');
                            exerciseCalories.textContent = data.exercise_calories;
                        }
                        if (remainingCalories) {
                            addDebugMessage(`Calories restantes : ${data.remaining_calories}`, 'BILAN');
                            remainingCalories.textContent = data.remaining_calories;
                        }
                        
                        // Mise à jour des macronutriments
                        const totalProtein = document.getElementById('total-protein');
                        const totalCarbs = document.getElementById('total-carbs');
                        const totalFat = document.getElementById('total-fat');
                        
                        if (totalProtein) {
                            addDebugMessage(`Protéines : ${data.total_protein}g`, 'CALORIES');
                            totalProtein.textContent = data.total_protein;
                        }
                        if (totalCarbs) {
                            addDebugMessage(`Glucides : ${data.total_carbs}g`, 'CALORIES');
                            totalCarbs.textContent = data.total_carbs;
                        }
                        if (totalFat) {
                            addDebugMessage(`Lipides : ${data.total_fat}g`, 'CALORIES');
                            totalFat.textContent = data.total_fat;
                        }
                        
                        addDebugMessage("Stats mises à jour avec succès", 'CALORIES');
                    })
                    .catch(error => {
                        addDebugMessage(`Erreur stats : ${error.message}`, 'CALORIES');
                    });
            }

            // Modifier les gestionnaires d'événements pour la suppression
            document.querySelectorAll('form[action="food-log.php"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const action = this.querySelector('input[name="action"]').value;
                    if (action === 'remove_food_from_meal' || action === 'delete_meal') {
                        e.preventDefault();
                        addDebugMessage(`Début de la suppression - Action : ${action}`, 'CALORIES');
                        const formData = new FormData(this);
                        
                        fetch('food-log.php', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            addDebugMessage(`Réponse reçue : ${response.status}`, 'CALORIES');
                            if (!response.ok) {
                                throw new Error('Erreur réseau: ' + response.status);
                            }
                            return response.json();
                        })
                        .then(data => {
                            addDebugMessage(`Données reçues : ${JSON.stringify(data)}`, 'CALORIES');
                            if (data.success) {
                                if (action === 'remove_food_from_meal') {
                                    const row = this.closest('tr');
                                    if (row) {
                                        row.remove();
                                        addDebugMessage("Ligne supprimée avec succès", 'CALORIES');
                                    }
                                } else if (action === 'delete_meal') {
                                    const section = this.closest('.meal-section');
                                    if (section) {
                                        const mealContent = section.querySelector('.table-responsive');
                                        if (mealContent) {
                                            mealContent.innerHTML = `
                                                <div class="alert alert-info">Aucun repas enregistré</div>
                                            `;
                                            addDebugMessage("Contenu de la section mis à jour", 'CALORIES');
                                        }
                                        
                                        const mealActions = section.querySelector('.meal-actions');
                                        if (mealActions) {
                                            mealActions.innerHTML = `
                                                <form action="food-log.php" method="POST">
                                                    <input type="hidden" name="action" value="add_meal">
                                                    <input type="hidden" name="meal_type" value="${this.querySelector('input[name="meal_type"]')?.value || ''}">
                                                    <input type="hidden" name="date" value="<?php echo $date_filter; ?>">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-plus me-1"></i>Créer le repas
                                                    </button>
                                                </form>
                                            `;
                                            addDebugMessage("Boutons d'action mis à jour", 'CALORIES');
                                        }
                                    }
                                }
                                addDebugMessage("Mise à jour des stats après suppression", 'CALORIES');
                                updateStats();
                            } else {
                                throw new Error(data.error || 'Une erreur est survenue lors de la suppression');
                            }
                        })
                        .catch(error => {
                            addDebugMessage(`Erreur : ${error.message}`, 'CALORIES');
                            alert(error.message || 'Une erreur est survenue lors de la suppression');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
