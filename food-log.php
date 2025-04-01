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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal alimentaire - Weight Tracker</title>
    <!-- Debug: Vérification du chargement des CSS -->
    <style>
        /* Debug: Style de test */
        .debug-test {
            background-color: red;
            color: white;
            padding: 10px;
            margin: 10px;
        }
        
        /* Styles spécifiques pour le tableau */
        .table {
            width: 100%;
            margin-bottom: 1rem;
            background-color: white;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border-top: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }
        
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Debug: Vérification du chemin du CSS -->
    <script>
        window.addEventListener('load', function() {
            console.log('=== Debug CSS ===');
            const styles = document.styleSheets;
            for (let i = 0; i < styles.length; i++) {
                console.log('StyleSheet ' + i + ':', styles[i].href);
            }
        });
    </script>
</head>
<body>
    <!-- Debug: Élément de test -->
    <div class="debug-test">Test de chargement CSS</div>
    
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
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
            <a href="food-log.php?action=add_meal" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Ajouter un repas
            </a>
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

        <?php if ($action === 'add_meal'): ?>
            <!-- Formulaire d'ajout de repas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Ajouter un nouveau repas</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="food-log.php" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="add_meal">
                        
                        <div class="mb-3">
                            <label for="meal_type" class="form-label">Type de repas</label>
                            <select class="form-select" id="meal_type" name="meal_type" required>
                                <option value="">Sélectionnez un type de repas</option>
                                <option value="petit_dejeuner">Petit déjeuner</option>
                                <option value="dejeuner">Déjeuner</option>
                                <option value="diner">Dîner</option>
                                <option value="collation">Collation</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="log_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="log_date" name="log_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (optionnel)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="food-log.php" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Ajouter le repas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($action === 'edit_meal' && $meal_details): ?>
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
                            <a href="food-log.php?action=add_meal&meal_type=<?php echo $type; ?>&date=<?php echo $date_filter; ?>">
                                <i class="fas fa-plus me-1"></i>Ajouter un aliment
                            </a>
                        </div>
                    </div>

                    <?php if (empty($type_meals)): ?>
                        <div class="meal-empty">
                            Aucun aliment enregistré pour ce repas
                        </div>
                    <?php else: ?>
                        <?php foreach ($type_meals as $meal): ?>
                            <div class="nutrient-grid">
                                <div class="nutrient-item">
                                    <div class="nutrient-value"><?php echo number_format($meal['total_calories']); ?></div>
                                    <div class="nutrient-label">Calories</div>
                                </div>
                                <div class="nutrient-item">
                                    <div class="nutrient-value"><?php echo number_format($meal['total_carbs'], 1); ?></div>
                                    <div class="nutrient-label">Glucides (g)</div>
                                </div>
                                <div class="nutrient-item">
                                    <div class="nutrient-value"><?php echo number_format($meal['total_fat'], 1); ?></div>
                                    <div class="nutrient-label">Lipides (g)</div>
                                </div>
                                <div class="nutrient-item">
                                    <div class="nutrient-value"><?php echo number_format($meal['total_protein'], 1); ?></div>
                                    <div class="nutrient-label">Protéines (g)</div>
                                </div>
                                <div class="nutrient-item">
                                    <div class="nutrient-value"><?php echo number_format($meal['total_sodium'] ?? 0, 1); ?></div>
                                    <div class="nutrient-label">Sodium (mg)</div>
                                </div>
                                <div class="nutrient-item">
                                    <div class="nutrient-value"><?php echo number_format($meal['total_sugar'] ?? 0, 1); ?></div>
                                    <div class="nutrient-label">Sucres (g)</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-2">
                                <a href="food-log.php?action=edit_meal&meal_id=<?php echo $meal['id']; ?>" class="btn btn-sm btn-primary me-2">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="food-log.php?action=delete_meal&meal_id=<?php echo $meal['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce repas ?')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Totaux journaliers -->
            <div class="daily-totals">
                <div class="totals-grid">
                    <div class="total-item">
                        <div class="total-value">
                            <?php
                            $total_calories = array_sum(array_column($meals, 'total_calories'));
                            echo number_format($total_calories);
                            ?>
                        </div>
                        <div class="total-goal">Calories</div>
                    </div>
                    <div class="total-item">
                        <div class="total-value">
                            <?php
                            $total_carbs = array_sum(array_column($meals, 'total_carbs'));
                            echo number_format($total_carbs, 1);
                            ?>
                        </div>
                        <div class="total-goal">Glucides (g)</div>
                    </div>
                    <div class="total-item">
                        <div class="total-value">
                            <?php
                            $total_fat = array_sum(array_column($meals, 'total_fat'));
                            echo number_format($total_fat, 1);
                            ?>
                        </div>
                        <div class="total-goal">Lipides (g)</div>
                    </div>
                    <div class="total-item">
                        <div class="total-value">
                            <?php
                            $total_protein = array_sum(array_column($meals, 'total_protein'));
                            echo number_format($total_protein, 1);
                            ?>
                        </div>
                        <div class="total-goal">Protéines (g)</div>
                    </div>
                    <div class="total-item">
                        <div class="total-value">
                            <?php
                            $total_sodium = array_sum(array_map(function($meal) {
                                return $meal['total_sodium'] ?? 0;
                            }, $meals));
                            echo number_format($total_sodium, 1);
                            ?>
                        </div>
                        <div class="total-goal">Sodium (mg)</div>
                    </div>
                    <div class="total-item">
                        <div class="total-value">
                            <?php
                            $total_sugar = array_sum(array_map(function($meal) {
                                return $meal['total_sugar'] ?? 0;
                            }, $meals));
                            echo number_format($total_sugar, 1);
                            ?>
                        </div>
                        <div class="total-goal">Sucres (g)</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestionnaire pour le sélecteur d'aliments
            const foodSelect = document.getElementById('food_id');
            if (foodSelect) {
                foodSelect.addEventListener('change', function() {
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
                    }
                });
            }
            
            // Gestionnaire pour le champ de quantité
            const quantityInput = document.getElementById('quantity');
            if (quantityInput) {
                quantityInput.addEventListener('input', function() {
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
            }
        });
    </script>
</body>
</html>
