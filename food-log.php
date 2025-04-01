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
            SUM(fl.custom_fat) as total_fat,
            SUM(fl.custom_sodium) as total_sodium,
            SUM(fl.custom_sugar) as total_sugar
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
        SUM(fl.custom_fat) as total_fat,
        SUM(fl.custom_sodium) as total_sodium,
        SUM(fl.custom_sugar) as total_sugar
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
                // Afficher les aliments du petit déjeuner
            endforeach;
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
                // Afficher les aliments du déjeuner
            endforeach;
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
                // Afficher les aliments du dîner
            endforeach;
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
                // Afficher les aliments des snacks
            endforeach;
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
                <div class="font-bold"><?= number_format($daily_totals['total_sodium']) ?></div>
                <div class="text-sm text-gray-600">/ <?= number_format($goals['daily_sodium']) ?></div>
            </div>
            <div class="text-center">
                <div class="font-bold"><?= number_format($daily_totals['total_sugar'], 1) ?></div>
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
                <label for="food_search">Rechercher un aliment:</label>
                <input type="text" id="food_search" class="form-input" placeholder="Tapez pour rechercher...">
            </div>

            <div class="mb-4">
                <label for="quantity">Quantité (g):</label>
                <input type="number" name="quantity" id="quantity" class="form-input" value="100">
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
</script>

<?php include 'footer.php'; ?>
            
        <?php elseif ($action === 'view_predefined_meal' && $predefined_meal_details): ?>
            <!-- Affichage d'un repas prédéfini -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        Repas prédéfini: <?php echo htmlspecialchars($predefined_meal_details['name']); ?>
                        <?php if ($predefined_meal_details['created_by_admin']): ?>
                            <span class="badge bg-danger ms-2">Admin</span>
                        <?php endif; ?>
                        <?php if ($predefined_meal_details['is_public']): ?>
                            <span class="badge bg-success ms-2">Public</span>
                        <?php endif; ?>
                    </h5>
                    <form action="food-log.php" method="POST">
                        <input type="hidden" name="action" value="use_predefined_meal">
                        <input type="hidden" name="predefined_meal_id" value="<?php echo $predefined_meal_details['id']; ?>">
                        <input type="hidden" name="log_date" value="<?php echo date('Y-m-d'); ?>">
                        <div class="input-group">
                            <select class="form-select" name="meal_type" required>
                                <option value="">Type de repas</option>
                                <option value="petit_dejeuner">Petit déjeuner</option>
                                <option value="dejeuner">Déjeuner</option>
                                <option value="diner">Dîner</option>
                                <option value="collation">Collation</option>
                                <option value="autre">Autre</option>
                            </select>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-utensils me-1"></i>Utiliser ce repas
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (!empty($predefined_meal_details['description'])): ?>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($predefined_meal_details['description']); ?></p>
                    <?php endif; ?>
                    
                    <?php
                    $total_calories = 0;
                    $total_protein = 0;
                    $total_carbs = 0;
                    $total_fat = 0;
                    
                    foreach ($predefined_meal_foods as $food): ?>
                        <?php 
                        $custom_data = json_decode($food['notes'], true) ?? [];
                        $nutrients = calculateNutrients([
                            'food_id' => $food['food_id'],
                            'food_name' => $food['food_name'],
                            'food_calories' => $food['food_calories'],
                            'food_protein' => $food['food_protein'],
                            'food_carbs' => $food['food_carbs'],
                            'food_fat' => $food['food_fat'],
                            'quantity' => $food['quantity'],
                            'custom_food_name' => $custom_data['custom_food_name'] ?? '',
                            'custom_calories' => $custom_data['custom_calories'] ?? 0,
                            'custom_protein' => $custom_data['custom_protein'] ?? 0,
                            'custom_carbs' => $custom_data['custom_carbs'] ?? 0,
                            'custom_fat' => $custom_data['custom_fat'] ?? 0
                        ]); 
                        ?>
                        <tr>
                            <td>
                                <?php if ($food['food_id']): ?>
                                    <?php echo htmlspecialchars($food['food_name']); ?>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($custom_data['custom_food_name'] ?? ''); ?> <span class="badge bg-secondary">Personnalisé</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $food['quantity']; ?></td>
                            <td><?php echo $nutrients['calories']; ?></td>
                            <td><?php echo $nutrients['protein']; ?></td>
                            <td><?php echo $nutrients['carbs']; ?></td>
                            <td><?php echo $nutrients['fat']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-1"><strong>Total calories:</strong> <?php echo $total_calories; ?> kcal</p>
                                    <p class="mb-1"><strong>Protéines:</strong> <?php echo $total_protein; ?> g</p>
                                </div>
                                <div>
                                    <p class="mb-1"><strong>Glucides:</strong> <?php echo $total_carbs; ?> g</p>
                                    <p class="mb-1"><strong>Lipides:</strong> <?php echo $total_fat; ?> g</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Liste des aliments du repas prédéfini -->
                    <?php if (!empty($predefined_meal_foods)): ?>
                        <h6 class="mb-3">Aliments dans ce repas:</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Aliment</th>
                                        <th>Quantité (g)</th>
                                        <th>Calories</th>
                                        <th>Protéines (g)</th>
                                        <th>Glucides (g)</th>
                                        <th>Lipides (g)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($predefined_meal_foods as $food): ?>
                                        <?php 
                                        $custom_data = json_decode($food['notes'], true) ?? [];
                                        $nutrients = calculateNutrients([
                                            'food_id' => $food['food_id'],
                                            'food_name' => $food['food_name'],
                                            'food_calories' => $food['food_calories'],
                                            'food_protein' => $food['food_protein'],
                                            'food_carbs' => $food['food_carbs'],
                                            'food_fat' => $food['food_fat'],
                                            'quantity' => $food['quantity'],
                                            'custom_food_name' => $custom_data['custom_food_name'] ?? '',
                                            'custom_calories' => $custom_data['custom_calories'] ?? 0,
                                            'custom_protein' => $custom_data['custom_protein'] ?? 0,
                                            'custom_carbs' => $custom_data['custom_carbs'] ?? 0,
                                            'custom_fat' => $custom_data['custom_fat'] ?? 0
                                        ]); 
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if ($food['food_id']): ?>
                                                    <?php echo htmlspecialchars($food['food_name']); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($custom_data['custom_food_name'] ?? ''); ?> <span class="badge bg-secondary">Personnalisé</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $food['quantity']; ?></td>
                                            <td><?php echo $nutrients['calories']; ?></td>
                                            <td><?php echo $nutrients['protein']; ?></td>
                                            <td><?php echo $nutrients['carbs']; ?></td>
                                            <td><?php echo $nutrients['fat']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Ce repas prédéfini ne contient pas d'aliments.
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between">
                        <a href="food-log.php" class="btn btn-outline-secondary">Retour au journal</a>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Journal alimentaire principal -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Repas du jour</h5>
                            <form class="d-flex">
                                <input type="date" class="form-control me-2" name="date" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
                            </form>
                        </div>
                        <div class="card-body">
                            <?php if (empty($meals)): ?>
                                <div class="text-center py-4">
                                    <p class="mb-3">Aucun repas enregistré pour cette date.</p>
                                    <a href="food-log.php?action=add_meal" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i>Ajouter un repas
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($meals as $meal): ?>
                                        <?php error_log("Affichage du repas : " . print_r($meal, true)); ?>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h5 class="card-title mb-1">
                                                            <?php echo getMealTypeName($meal['meal_type']); ?>
                                                        </h5>
                                                        <p class="card-text mb-1">
                                                            <strong>Calories:</strong> <?php echo number_format($meal['total_calories']); ?> kcal
                                                        </p>
                                                        <?php if ($meal['notes']): ?>
                                                            <p class="card-text mb-1">
                                                                <strong>Notes:</strong> <?php echo htmlspecialchars($meal['notes']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y H:i', strtotime($meal['log_date'])); ?>
                                                        </small>
                                                    </div>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary share-meal-btn" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#shareMealModal"
                                                                data-meal-id="<?php echo $meal['id']; ?>"
                                                                data-meal-type="<?php echo $meal['meal_type']; ?>"
                                                                data-calories="<?php echo $meal['total_calories']; ?>"
                                                                data-notes="<?php echo htmlspecialchars($meal['notes'] ?? ''); ?>"
                                                                onclick="console.log('Partage de repas:', this.dataset);">
                                                            <i class="fas fa-share-alt me-1"></i>Partager
                                                        </button>
                                                        <a href="food-log.php?action=edit_meal&meal_id=<?php echo $meal['id']; ?>" 
                                                           class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="food-log.php?action=delete_meal&meal_id=<?php echo $meal['id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce repas ?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- Repas prédéfinis -->
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Repas prédéfinis</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($predefined_meals)): ?>
                                <div class="text-center py-4">
                                    <p class="mb-3">Aucun repas prédéfini disponible.</p>
                                    <p class="text-muted small">Créez des repas et enregistrez-les comme modèles pour les réutiliser facilement.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php 
                                    $displayed_count = 0;
                                    foreach ($predefined_meals as $pm): 
                                        if ($displayed_count >= 5) break;
                                        $displayed_count++;
                                    ?>
                                        <a href="food-log.php?action=view_predefined_meal&predefined_meal_id=<?php echo $pm['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($pm['name']); ?></h6>
                                                <p class="text-muted small mb-0">
                                                    <?php if ($pm['user_id'] == 1): ?>
                                                        <span class="badge bg-danger">Admin</span>
                                                    <?php endif; ?>
                                                    <?php if ($pm['is_public'] && $pm['user_id'] != $user_id): ?>
                                                        <span class="badge bg-success">Public</span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>Voir
                                            </button>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if (count($predefined_meals) > 5): ?>
                                    <div class="text-center mt-3">
                                        <button class="btn btn-link" data-bs-toggle="modal" data-bs-target="#viewPredefinedMealsModal">
                                            Voir tous les repas prédéfinis (<?php echo count($predefined_meals); ?>)
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historique des repas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Historique des repas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($meal_history)): ?>
                        <div class="text-center py-4">
                            <p>Aucun historique de repas disponible.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Aliments</th>
                                        <th>Calories</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($meals as $meal): ?>
                                        <?php error_log("Affichage du repas : " . print_r($meal, true)); ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($meal['log_date'])); ?></td>
                                            <td><?php echo getMealTypeName($meal['meal_type']); ?></td>
                                            <td><?php echo $meal['food_count']; ?> aliment(s)</td>
                                            <td><?php echo number_format($meal['total_calories']); ?> kcal</td>
                                            <td><?php echo $meal['notes'] ? htmlspecialchars($meal['notes']) : '—'; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary share-meal-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#shareMealModal"
                                                            data-meal-id="<?php echo $meal['id']; ?>"
                                                            data-meal-type="<?php echo $meal['meal_type']; ?>"
                                                            data-calories="<?php echo $meal['total_calories']; ?>"
                                                            data-notes="<?php echo htmlspecialchars($meal['notes'] ?? ''); ?>"
                                                            onclick="console.log('Partage de repas:', this.dataset);">
                                                        <i class="fas fa-share-alt me-1"></i>Partager
                                                    </button>
                                                    <a href="food-log.php?action=edit_meal&meal_id=<?php echo $meal['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="food-log.php?action=delete_meal&meal_id=<?php echo $meal['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce repas ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal pour voir tous les repas prédéfinis -->
    <div class="modal fade" id="viewPredefinedMealsModal" tabindex="-1" aria-labelledby="viewPredefinedMealsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPredefinedMealsModalLabel">Tous les repas prédéfinis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($predefined_meals)): ?>
                        <div class="text-center py-4">
                            <p>Aucun repas prédéfini disponible.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($predefined_meals as $pm): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($pm['name']); ?>
                                                <?php if ($pm['user_id'] == 1): ?>
                                                    <span class="badge bg-danger">Admin</span>
                                                <?php endif; ?>
                                                <?php if ($pm['is_public'] && $pm['user_id'] != $user_id): ?>
                                                    <span class="badge bg-success">Public</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($pm['description']) ? htmlspecialchars(substr($pm['description'], 0, 50)) . (strlen($pm['description']) > 50 ? '...' : '') : '—'; ?>
                                            </td>
                                            <td>
                                                <?php if ($pm['user_id'] == $user_id): ?>
                                                    <span class="badge bg-primary">Personnel</span>
                                                <?php elseif ($pm['user_id'] == 1): ?>
                                                    <span class="badge bg-danger">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Public</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="food-log.php?action=view_predefined_meal&predefined_meal_id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>Voir
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de partage de repas -->
    <div class="modal fade" id="shareMealModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Partager ce repas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="shareForm" action="create-post.php" method="POST">
                        <input type="hidden" name="post_type" value="meal">
                        <input type="hidden" name="reference_id" id="share_meal_id">
                        <input type="hidden" name="visibility" value="public">
                        
                        <div class="mb-3">
                            <label class="form-label">Message (optionnel)</label>
                            <textarea class="form-control" name="content" rows="3"></textarea>
                        </div>
                        
                        <div class="meal-details mb-3">
                            <p class="mb-1"><strong>Type:</strong> <span id="share_meal_type"></span></p>
                            <p class="mb-1"><strong>Calories:</strong> <span id="share_meal_calories"></span> kcal</p>
                            <p class="mb-0"><strong>Notes:</strong> <span id="share_meal_notes"></span></p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="shareForm" class="btn btn-primary">Partager</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'ajout de suggestion -->
    <div class="modal fade" id="addSuggestionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter le repas suggéré</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="food-log.php" method="POST">
                    <input type="hidden" name="action" value="add_meal">
                    <input type="hidden" name="suggestion_id" id="suggestion_id">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="meal_type_suggestion" class="form-label">Type de repas</label>
                            <select class="form-select" id="meal_type_suggestion" name="meal_type" required>
                                <option value="">Sélectionnez un type de repas</option>
                                <option value="petit_dejeuner">Petit déjeuner</option>
                                <option value="dejeuner">Déjeuner</option>
                                <option value="diner">Dîner</option>
                                <option value="collation">Collation</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="log_date_suggestion" class="form-label">Date</label>
                            <input type="date" class="form-control" id="log_date_suggestion" name="log_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="nutrition-info small text-muted">
                            <div class="d-flex justify-content-between">
                                <span>Calories: <span id="suggestion_calories"></span> kcal</span>
                                <span>Protéines: <span id="suggestion_protein"></span>g</span>
                                <span>Glucides: <span id="suggestion_carbs"></span>g</span>
                                <span>Lipides: <span id="suggestion_fat"></span>g</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter le repas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de détails de suggestion -->
    <div class="modal fade" id="viewSuggestionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de la suggestion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="mb-2">Ingrédients :</h6>
                        <ul class="list-unstyled mb-0" id="suggestion_ingredients">
                            <!-- Les ingrédients seront ajoutés ici via JavaScript -->
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="mb-2">Conseils :</h6>
                        <ul class="list-unstyled mb-0" id="suggestion_conseils">
                            <!-- Les conseils seront ajoutés ici via JavaScript -->
                        </ul>
                    </div>
                    
                    <div class="nutrition-info small text-muted">
                        <div class="d-flex justify-content-between">
                            <span>Calories: <span id="view_suggestion_calories"></span> kcal</span>
                            <span>Protéines: <span id="view_suggestion_protein"></span>g</span>
                            <span>Glucides: <span id="view_suggestion_carbs"></span>g</span>
                            <span>Lipides: <span id="view_suggestion_fat"></span>g</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
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
                    } else {
                        // Réinitialiser les champs si aucun aliment n'est sélectionné
                        document.getElementById('custom_calories').value = '0';
                        document.getElementById('custom_protein').value = '0';
                        document.getElementById('custom_carbs').value = '0';
                        document.getElementById('custom_fat').value = '0';
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
            
            // Gestionnaire pour le bouton de génération de suggestions
            const generateSuggestionsBtn = document.getElementById('generateSuggestionsBtn');
            if (generateSuggestionsBtn) {
                generateSuggestionsBtn.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    icon.classList.add('fa-spin');
                    this.disabled = true;
                    
                    fetch('generate-suggestions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            type: 'alimentation'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Erreur lors de la génération des suggestions : ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Une erreur est survenue lors de la génération des suggestions.');
                    })
                    .finally(() => {
                        icon.classList.remove('fa-spin');
                        this.disabled = false;
                    });
                });
            }
            
            // Gestionnaire pour les boutons d'ajout de suggestion
            document.querySelectorAll('.add-suggestion-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const suggestionId = this.dataset.suggestionId;
                    const calories = this.dataset.calories;
                    const protein = this.dataset.protein;
                    const carbs = this.dataset.carbs;
                    const fat = this.dataset.fat;
                    
                    // Mettre à jour les champs du modal
                    document.getElementById('suggestion_id').value = suggestionId;
                    document.getElementById('suggestion_calories').textContent = calories;
                    document.getElementById('suggestion_protein').textContent = protein;
                    document.getElementById('suggestion_carbs').textContent = carbs;
                    document.getElementById('suggestion_fat').textContent = fat;
                });
            });
            
            // Gestionnaire pour les boutons de visualisation de suggestion
            document.querySelectorAll('.view-suggestion-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const ingredients = JSON.parse(this.dataset.ingredients);
                    const conseils = JSON.parse(this.dataset.conseils);
                    
                    // Mettre à jour les ingrédients
                    const ingredientsList = document.getElementById('suggestion_ingredients');
                    ingredientsList.innerHTML = ingredients.map(ingredient => 
                        `<li><i class="fas fa-check text-success"></i> ${ingredient}</li>`
                    ).join('');
                    
                    // Mettre à jour les conseils
                    const conseilsList = document.getElementById('suggestion_conseils');
                    conseilsList.innerHTML = conseils.map(conseil => 
                        `<li><i class="fas fa-lightbulb text-warning"></i> ${conseil}</li>`
                    ).join('');
                    
                    // Mettre à jour les valeurs nutritionnelles
                    document.getElementById('view_suggestion_calories').textContent = this.dataset.calories;
                    document.getElementById('view_suggestion_protein').textContent = this.dataset.protein;
                    document.getElementById('view_suggestion_carbs').textContent = this.dataset.carbs;
                    document.getElementById('view_suggestion_fat').textContent = this.dataset.fat;
                });
            });
            
            // Gestionnaire pour le formulaire d'ajout de suggestion
            document.querySelector('#addSuggestionModal form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const suggestionId = document.getElementById('suggestion_id').value;
                const mealType = document.getElementById('meal_type_suggestion').value;
                const logDate = document.getElementById('log_date_suggestion').value;
                
                // Appeler la fonction PHP pour ajouter la suggestion comme repas
                fetch('add-suggestion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        suggestion_id: suggestionId,
                        meal_type: mealType,
                        log_date: logDate
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fermer le modal et recharger la page
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addSuggestionModal'));
                        modal.hide();
                        window.location.reload();
                    } else {
                        alert('Erreur lors de l\'ajout du repas : ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de l\'ajout du repas.');
                });
            });
        });
    </script>
</body>
</html>
