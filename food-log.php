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

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
    
    // Ajouter un repas
    if ($post_action === 'add_meal') {
        $meal_name = sanitizeInput($_POST['meal_name'] ?? '');
        $meal_type = sanitizeInput($_POST['meal_type'] ?? '');
        $log_date = sanitizeInput($_POST['log_date'] ?? date('Y-m-d'));
        $notes = sanitizeInput($_POST['notes'] ?? '');
        
        // Validation
        if (empty($meal_name)) {
            $errors[] = "Le nom du repas est requis";
        }
        
        if (empty($meal_type)) {
            $errors[] = "Le type de repas est requis";
        }
        
        if (empty($log_date) || !validateDate($log_date)) {
            $errors[] = "La date n'est pas valide";
        }
        
        if (empty($errors)) {
            try {
                // Insérer le repas
                $sql = "INSERT INTO meals (user_id, meal_name, meal_type, log_date, notes, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
                $meal_id = insert($sql, [$user_id, $meal_name, $meal_type, $log_date, $notes]);
                
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
                            $sql = "INSERT INTO predefined_meal_foods (predefined_meal_id, food_id, custom_food_name, quantity, custom_calories, custom_protein, custom_carbs, custom_fat, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                            insert($sql, [
                                $predefined_meal_id,
                                $food['food_id'],
                                $food['custom_food_name'],
                                $food['quantity'],
                                $food['custom_calories'],
                                $food['custom_protein'],
                                $food['custom_carbs'],
                                $food['custom_fat']
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
                        $sql = "SELECT * FROM predefined_meal_foods WHERE predefined_meal_id = ?";
                        $foods = fetchAll($sql, [$predefined_meal_id]);
                        
                        // Ajouter chaque aliment au nouveau repas
                        foreach ($foods as $food) {
                            $sql = "INSERT INTO food_logs (user_id, food_id, custom_food_name, quantity, custom_calories, custom_protein, custom_carbs, custom_fat, log_date, meal_id, is_part_of_meal, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
                            insert($sql, [
                                $user_id, 
                                $food['food_id'] ?? 0,  // Utiliser 0 si food_id n'existe pas
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
        $meal_id = intval($_POST['meal_id'] ?? 0);
        
        // Validation
        if ($meal_id <= 0) {
            $errors[] = "ID de repas invalide";
        }
        
        if (empty($errors)) {
            try {
                // Vérifier si le repas existe et appartient à l'utilisateur
                $sql = "SELECT id FROM meals WHERE id = ? AND user_id = ?";
                $meal = fetchOne($sql, [$meal_id, $user_id]);
                
                if (!$meal) {
                    $errors[] = "Repas introuvable ou vous n'avez pas les droits pour le supprimer";
                } else {
                    // Supprimer tous les aliments du repas
                    $sql = "DELETE FROM food_logs WHERE meal_id = ?";
                    update($sql, [$meal_id]);
                    
                    // Supprimer le repas
                    $sql = "DELETE FROM meals WHERE id = ?";
                    $result = update($sql, [$meal_id]);
                    
                    if ($result) {
                        $success_message = "Repas supprimé avec succès";
                        redirect("food-log.php");
                    } else {
                        $errors[] = "Une erreur s'est produite lors de la suppression du repas";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
}

// Récupérer les repas de l'utilisateur
$date_filter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');
$sql = "SELECT m.*, 
        (SELECT COUNT(*) FROM food_logs fl WHERE fl.meal_id = m.id) as food_count,
        (SELECT SUM(CASE 
            WHEN fl.food_id IS NOT NULL THEN 
                (SELECT f.calories * (fl.quantity / 100) FROM foods f WHERE f.id = fl.food_id)
            ELSE fl.custom_calories
        END) FROM food_logs fl WHERE fl.meal_id = m.id) as total_calories
        FROM meals m 
        WHERE m.user_id = ? AND m.log_date = ? 
        ORDER BY FIELD(m.meal_type, 'petit_dejeuner', 'dejeuner', 'diner', 'collation', 'autre')";
$meals = fetchAll($sql, [$user_id, $date_filter]);

// Récupérer les repas prédéfinis
$sql = "SELECT * FROM predefined_meals 
        WHERE user_id = ? OR is_public = 1 OR created_by_admin = 1 
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
                FROM predefined_meal_foods pmf 
                LEFT JOIN foods f ON pmf.food_id = f.id 
                WHERE pmf.predefined_meal_id = ? 
                ORDER BY pmf.created_at";
        $predefined_meal_foods = fetchAll($sql, [$predefined_meal_id]);
    }
}

// Récupérer l'historique des repas
$sql = "SELECT m.*, 
        DATE_FORMAT(m.log_date, '%d/%m/%Y') as formatted_date,
        (SELECT COUNT(*) FROM food_logs fl WHERE fl.meal_id = m.id) as food_count,
        (SELECT SUM(CASE 
            WHEN fl.food_id IS NOT NULL THEN 
                (SELECT f.calories * (fl.quantity / 100) FROM foods f WHERE f.id = fl.food_id)
            ELSE fl.custom_calories
        END) FROM food_logs fl WHERE fl.meal_id = m.id) as total_calories
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal alimentaire - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <!-- En-tête de la page -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-0">Journal alimentaire</h1>
                <p class="text-muted">Suivez vos repas et votre consommation de calories</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="btn-group">
                    <a href="food-log.php" class="btn btn-outline-primary">
                        <i class="fas fa-calendar-day me-1"></i>Aujourd'hui
                    </a>
                    <a href="food-log.php?action=add_meal" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Ajouter un repas
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($action === 'add_meal'): ?>
            <!-- Formulaire d'ajout de repas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Ajouter un nouveau repas</h5>
                </div>
                <div class="card-body">
                    <form action="food-log.php" method="POST" novalidate>
                        <input type="hidden" name="action" value="add_meal">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="meal_name" class="form-label">Nom du repas</label>
                                <input type="text" class="form-control" id="meal_name" name="meal_name" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="meal_type" class="form-label">Type de repas</label>
                                <select class="form-select" id="meal_type" name="meal_type" required>
                                    <option value="">Sélectionnez un type</option>
                                    <option value="petit_dejeuner">Petit déjeuner</option>
                                    <option value="dejeuner">Déjeuner</option>
                                    <option value="diner">Dîner</option>
                                    <option value="collation">Collation</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="log_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="log_date" name="log_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (optionnel)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="food-log.php" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Créer le repas</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Utiliser un repas prédéfini -->
            <?php if (!empty($predefined_meals)): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Ou utiliser un repas prédéfini</h5>
                    </div>
                    <div class="card-body">
                        <form action="food-log.php" method="POST" novalidate>
                            <input type="hidden" name="action" value="use_predefined_meal">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="predefined_meal_id" class="form-label">Repas prédéfini</label>
                                    <select class="form-select" id="predefined_meal_id" name="predefined_meal_id" required>
                                        <option value="">Sélectionnez un repas</option>
                                        <?php foreach ($predefined_meals as $pm): ?>
                                            <option value="<?php echo $pm['id']; ?>">
                                                <?php echo htmlspecialchars($pm['name']); ?>
                                                <?php if ($pm['created_by_admin']): ?> (Admin)<?php endif; ?>
                                                <?php if ($pm['is_public'] && $pm['user_id'] != $user_id): ?> (Public)<?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="meal_type_predefined" class="form-label">Type de repas</label>
                                    <select class="form-select" id="meal_type_predefined" name="meal_type" required>
                                        <option value="">Sélectionnez</option>
                                        <option value="petit_dejeuner">Petit déjeuner</option>
                                        <option value="dejeuner">Déjeuner</option>
                                        <option value="diner">Dîner</option>
                                        <option value="collation">Collation</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="log_date_predefined" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="log_date_predefined" name="log_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="#" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#viewPredefinedMealsModal">
                                    <i class="fas fa-eye me-1"></i>Voir les détails des repas prédéfinis
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-utensils me-1"></i>Utiliser ce repas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php elseif ($action === 'edit_meal' && $meal_details): ?>
            <!-- Édition d'un repas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        Éditer le repas: <?php echo htmlspecialchars($meal_details['name']); ?>
                        <span class="badge bg-primary ms-2"><?php echo getMealTypeName($meal_details['meal_type']); ?></span>
                    </h5>
                    <div>
                        <form action="food-log.php" method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete_meal">
                            <input type="hidden" name="meal_id" value="<?php echo $meal_details['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce repas ?')">
                                <i class="fas fa-trash me-1"></i>Supprimer
                            </button>
                        </form>
                        <button class="btn btn-sm btn-outline-success ms-2" data-bs-toggle="modal" data-bs-target="#savePredefinedMealModal">
                            <i class="fas fa-save me-1"></i>Enregistrer comme modèle
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($meal_details['log_date'])); ?></p>
                            <?php if (!empty($meal_details['notes'])): ?>
                                <p><strong>Notes:</strong> <?php echo htmlspecialchars($meal_details['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php
                            $total_calories = 0;
                            $total_protein = 0;
                            $total_carbs = 0;
                            $total_fat = 0;
                            
                            foreach ($meal_foods as $food) {
                                $nutrients = calculateNutrients($food);
                                $total_calories += $nutrients['calories'];
                                $total_protein += $nutrients['protein'];
                                $total_carbs += $nutrients['carbs'];
                                $total_fat += $nutrients['fat'];
                            }
                            ?>
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
                    
                    <!-- Liste des aliments du repas -->
                    <?php if (!empty($meal_foods)): ?>
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
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($meal_foods as $food): ?>
                                        <?php $nutrients = calculateNutrients($food); ?>
                                        <tr>
                                            <td>
                                                <?php if ($food['food_id']): ?>
                                                    <?php echo htmlspecialchars($food['food_name']); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($food['custom_food_name']); ?> <span class="badge bg-secondary">Personnalisé</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $food['quantity']; ?></td>
                                            <td><?php echo $nutrients['calories']; ?></td>
                                            <td><?php echo $nutrients['protein']; ?></td>
                                            <td><?php echo $nutrients['carbs']; ?></td>
                                            <td><?php echo $nutrients['fat']; ?></td>
                                            <td>
                                                <form action="food-log.php?action=edit_meal&meal_id=<?php echo $meal_details['id']; ?>" method="POST">
                                                    <input type="hidden" name="action" value="remove_food_from_meal">
                                                    <input type="hidden" name="food_log_id" value="<?php echo $food['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet aliment ?')">
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
                        <div class="alert alert-info">
                            Ce repas ne contient pas encore d'aliments. Ajoutez-en ci-dessous.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Formulaire d'ajout d'aliment -->
                    <h6 class="mb-3">Ajouter un aliment à ce repas:</h6>
                    <form action="food-log.php?action=edit_meal&meal_id=<?php echo $meal_details['id']; ?>" method="POST" novalidate>
                        <input type="hidden" name="action" value="add_food_to_meal">
                        <input type="hidden" name="meal_id" value="<?php echo $meal_details['id']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="food_id" class="form-label">Aliment</label>
                                <select class="form-select" id="food_id" name="food_id">
                                    <option value="">Sélectionnez un aliment ou saisissez un nom personnalisé</option>
                                    <?php foreach ($foods as $food): ?>
                                        <option value="<?php echo $food['id']; ?>" 
                                                data-calories="<?php echo $food['calories']; ?>"
                                                data-protein="<?php echo $food['protein']; ?>"
                                                data-carbs="<?php echo $food['carbs']; ?>"
                                                data-fat="<?php echo $food['fat']; ?>">
                                            <?php echo htmlspecialchars($food['name']); ?> 
                                            (<?php echo $food['calories']; ?> kcal/100g)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="custom_food_name" class="form-label">Nom d'aliment personnalisé (si non listé)</label>
                                <input type="text" class="form-control" id="custom_food_name" name="custom_food_name">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="quantity" class="form-label">Quantité (g)</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="100" min="1" required>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="custom_calories" class="form-label">Calories</label>
                                <input type="number" class="form-control" id="custom_calories" name="custom_calories" value="0" min="0" required>
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label for="custom_protein" class="form-label">Protéines (g)</label>
                                <input type="number" class="form-control" id="custom_protein" name="custom_protein" value="0" min="0" step="0.1">
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label for="custom_carbs" class="form-label">Glucides (g)</label>
                                <input type="number" class="form-control" id="custom_carbs" name="custom_carbs" value="0" min="0" step="0.1">
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label for="custom_fat" class="form-label">Lipides (g)</label>
                                <input type="number" class="form-control" id="custom_fat" name="custom_fat" value="0" min="0" step="0.1">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="food-log.php" class="btn btn-outline-secondary">Retour au journal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Ajouter l'aliment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Modal pour enregistrer comme repas prédéfini -->
            <div class="modal fade" id="savePredefinedMealModal" tabindex="-1" aria-labelledby="savePredefinedMealModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="savePredefinedMealModalLabel">Enregistrer comme repas prédéfini</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="food-log.php?action=edit_meal&meal_id=<?php echo $meal_details['id']; ?>" method="POST">
                            <input type="hidden" name="action" value="save_predefined_meal">
                            <input type="hidden" name="meal_id" value="<?php echo $meal_details['id']; ?>">
                            
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="predefined_name" class="form-label">Nom du repas prédéfini</label>
                                    <input type="text" class="form-control" id="predefined_name" name="predefined_name" value="<?php echo htmlspecialchars($meal_details['name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="predefined_description" class="form-label">Description (optionnel)</label>
                                    <textarea class="form-control" id="predefined_description" name="predefined_description" rows="3"></textarea>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_public" name="is_public">
                                    <label class="form-check-label" for="is_public">
                                        Rendre ce repas prédéfini public (visible par tous les utilisateurs)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-success">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
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
                    
                    foreach ($predefined_meal_foods as $food) {
                        $nutrients = calculateNutrients($food);
                        $total_calories += $nutrients['calories'];
                        $total_protein += $nutrients['protein'];
                        $total_carbs += $nutrients['carbs'];
                        $total_fat += $nutrients['fat'];
                    }
                    ?>
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
                                        <?php $nutrients = calculateNutrients($food); ?>
                                        <tr>
                                            <td>
                                                <?php if ($food['food_id']): ?>
                                                    <?php echo htmlspecialchars($food['food_name']); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($food['custom_food_name']); ?> <span class="badge bg-secondary">Personnalisé</span>
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
                                        <a href="food-log.php?action=edit_meal&meal_id=<?php echo $meal['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($meal['name']); ?></h6>
                                                <p class="text-muted small mb-0">
                                                    <span class="badge bg-primary"><?php echo getMealTypeName($meal['meal_type']); ?></span>
                                                    <span class="ms-2"><?php echo $meal['food_count']; ?> aliment(s)</span>
                                                </p>
                                            </div>
                                            <div class="text-end">
                                                <h6 class="mb-0"><?php echo round($meal['total_calories'] ?? 0); ?> kcal</h6>
                                                <button class="btn btn-sm btn-outline-primary mt-1">
                                                    <i class="fas fa-eye me-1"></i>Détails
                                                </button>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
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
                                                    <?php if ($pm['created_by_admin']): ?>
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
            <div class="card shadow-sm">
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
                                        <th>Repas</th>
                                        <th>Type</th>
                                        <th>Aliments</th>
                                        <th>Calories</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($meal_history as $meal): ?>
                                        <tr>
                                            <td><?php echo $meal['formatted_date']; ?></td>
                                            <td><?php echo htmlspecialchars($meal['name']); ?></td>
                                            <td><?php echo getMealTypeName($meal['meal_type']); ?></td>
                                            <td><?php echo $meal['food_count']; ?></td>
                                            <td><?php echo round($meal['total_calories'] ?? 0); ?> kcal</td>
                                            <td>
                                                <a href="food-log.php?action=edit_meal&meal_id=<?php echo $meal['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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
                                                <?php if ($pm['created_by_admin']): ?>
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
                                                <?php elseif ($pm['created_by_admin']): ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mise à jour automatique des valeurs nutritionnelles lors de la sélection d'un aliment
        document.addEventListener('DOMContentLoaded', function() {
            const foodSelect = document.getElementById('food_id');
            const quantityInput = document.getElementById('quantity');
            const caloriesInput = document.getElementById('custom_calories');
            const proteinInput = document.getElementById('custom_protein');
            const carbsInput = document.getElementById('custom_carbs');
            const fatInput = document.getElementById('custom_fat');
            
            if (foodSelect && quantityInput && caloriesInput) {
                function updateNutrients() {
                    const selectedOption = foodSelect.options[foodSelect.selectedIndex];
                    const quantity = parseFloat(quantityInput.value) || 0;
                    
                    if (selectedOption && selectedOption.value) {
                        const calories = parseFloat(selectedOption.dataset.calories) || 0;
                        const protein = parseFloat(selectedOption.dataset.protein) || 0;
                        const carbs = parseFloat(selectedOption.dataset.carbs) || 0;
                        const fat = parseFloat(selectedOption.dataset.fat) || 0;
                        
                        caloriesInput.value = Math.round((calories * quantity) / 100);
                        proteinInput.value = ((protein * quantity) / 100).toFixed(1);
                        carbsInput.value = ((carbs * quantity) / 100).toFixed(1);
                        fatInput.value = ((fat * quantity) / 100).toFixed(1);
                    }
                }
                
                foodSelect.addEventListener('change', updateNutrients);
                quantityInput.addEventListener('input', updateNutrients);
                
                // Initialiser les valeurs
                updateNutrients();
            }
        });
    </script>
</body>
</html>
