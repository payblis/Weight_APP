<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/admin_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$user = fetchOne($sql, [$user_id]);

// Vérifier si l'utilisateur est un administrateur
$is_admin = isAdmin($user_id);

// Rediriger si l'utilisateur n'est pas administrateur
if (!$is_admin) {
    $_SESSION['error'] = "Accès refusé. Vous devez être administrateur pour accéder à cette page.";
    redirect('dashboard.php');
}

// Initialiser les variables
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$section = isset($_GET['section']) ? sanitizeInput($_GET['section']) : 'dashboard';
$success_message = '';
$errors = [];

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
    
    // Mise à jour de la clé API ChatGPT
    if ($post_action === 'update_api_key') {
        $api_key = sanitizeInput($_POST['api_key'] ?? '');
        
        try {
            // Vérifier si le paramètre existe déjà
            $sql = "SELECT id FROM settings WHERE setting_name = 'chatgpt_api_key'";
            $setting = fetchOne($sql, []);
            
            if ($setting) {
                // Mettre à jour le paramètre existant
                $sql = "UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_name = 'chatgpt_api_key'";
                executeQuery($sql, [$api_key]);
            } else {
                // Créer un nouveau paramètre
                $sql = "INSERT INTO settings (setting_name, setting_value) VALUES ('chatgpt_api_key', ?)";
                executeQuery($sql, [$api_key]);
            }
            
            $_SESSION['success'] = "La clé API a été mise à jour avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la mise à jour de la clé API : " . $e->getMessage();
        }
    }
    
    // Gestion des repas prédéfinis
    if ($post_action === 'create_meal_plan') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $total_calories = (int)($_POST['total_calories'] ?? 0);
        $protein = (float)($_POST['protein'] ?? 0);
        $carbs = (float)($_POST['carbs'] ?? 0);
        $fat = (float)($_POST['fat'] ?? 0);
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        
        try {
            $sql = "INSERT INTO meal_plans (name, description, total_calories, protein, carbs, fat, is_public, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $meal_plan_id = executeQuery($sql, [$name, $description, $total_calories, $protein, $carbs, $fat, $is_public, $user_id]);
            
            // Gérer les aliments du plan
            if (isset($_POST['foods']) && is_array($_POST['foods'])) {
                foreach ($_POST['foods'] as $food) {
                    $food_id = (int)($food['id'] ?? 0);
                    $quantity = (float)($food['quantity'] ?? 0);
                    $unit = sanitizeInput($food['unit'] ?? '');
                    $meal_type = sanitizeInput($food['meal_type'] ?? '');
                    
                    if ($food_id > 0) {
                        $sql = "INSERT INTO meal_plan_items (meal_plan_id, food_id, quantity, unit, meal_type) 
                                VALUES (?, ?, ?, ?, ?)";
                        executeQuery($sql, [$meal_plan_id, $food_id, $quantity, $unit, $meal_type]);
                    }
                }
            }
            
            $_SESSION['success'] = "Le plan de repas a été créé avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la création du plan de repas : " . $e->getMessage();
        }
    }
    
    if ($post_action === 'update_meal_plan') {
        $meal_plan_id = (int)($_POST['meal_plan_id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $total_calories = (int)($_POST['total_calories'] ?? 0);
        $protein = (float)($_POST['protein'] ?? 0);
        $carbs = (float)($_POST['carbs'] ?? 0);
        $fat = (float)($_POST['fat'] ?? 0);
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        
        try {
            $sql = "UPDATE meal_plans SET name = ?, description = ?, total_calories = ?, protein = ?, 
                    carbs = ?, fat = ?, is_public = ? WHERE id = ?";
            executeQuery($sql, [$name, $description, $total_calories, $protein, $carbs, $fat, $is_public, $meal_plan_id]);
            
            // Supprimer les anciens aliments
            $sql = "DELETE FROM meal_plan_items WHERE meal_plan_id = ?";
            executeQuery($sql, [$meal_plan_id]);
            
            // Ajouter les nouveaux aliments
            if (isset($_POST['foods']) && is_array($_POST['foods'])) {
                foreach ($_POST['foods'] as $food) {
                    $food_id = (int)($food['id'] ?? 0);
                    $quantity = (float)($food['quantity'] ?? 0);
                    $unit = sanitizeInput($food['unit'] ?? '');
                    $meal_type = sanitizeInput($food['meal_type'] ?? '');
                    
                    if ($food_id > 0) {
                        $sql = "INSERT INTO meal_plan_items (meal_plan_id, food_id, quantity, unit, meal_type) 
                                VALUES (?, ?, ?, ?, ?)";
                        executeQuery($sql, [$meal_plan_id, $food_id, $quantity, $unit, $meal_type]);
                    }
                }
            }
            
            $_SESSION['success'] = "Le plan de repas a été mis à jour avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la mise à jour du plan de repas : " . $e->getMessage();
        }
    }
    
    if ($post_action === 'delete_meal_plan') {
        $meal_plan_id = (int)($_POST['meal_plan_id'] ?? 0);
        
        try {
            // Supprimer d'abord les aliments associés
            $sql = "DELETE FROM meal_plan_items WHERE meal_plan_id = ?";
            executeQuery($sql, [$meal_plan_id]);
            
            // Puis supprimer le plan
            $sql = "DELETE FROM meal_plans WHERE id = ?";
            executeQuery($sql, [$meal_plan_id]);
            
            $_SESSION['success'] = "Le plan de repas a été supprimé avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la suppression du plan de repas : " . $e->getMessage();
        }
    }
    
    // Création d'un programme nutritionnel
    elseif ($post_action === 'create_program') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $calorie_adjustment = intval($_POST['calorie_adjustment'] ?? 0);
        $protein_ratio = floatval($_POST['protein_ratio'] ?? 30);
        $carbs_ratio = floatval($_POST['carbs_ratio'] ?? 40);
        $fat_ratio = floatval($_POST['fat_ratio'] ?? 30);
        
        // Validation
        if (empty($name)) {
            $errors[] = "Le nom du programme est requis";
        }
        
        if ($protein_ratio + $carbs_ratio + $fat_ratio !== 100) {
            $errors[] = "La somme des ratios de macronutriments doit être égale à 100%";
        }
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO nutrition_programs (name, description, calorie_adjustment, protein_ratio, carbs_ratio, fat_ratio, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $result = insert($sql, [$name, $description, $calorie_adjustment, $protein_ratio, $carbs_ratio, $fat_ratio, $user_id]);
                
                if ($result) {
                    $success_message = "Programme nutritionnel créé avec succès";
                } else {
                    $errors[] = "Une erreur s'est produite lors de la création du programme";
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
    
    // Mise à jour d'un programme nutritionnel
    elseif ($post_action === 'update_program') {
        $program_id = intval($_POST['program_id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $calorie_adjustment = intval($_POST['calorie_adjustment'] ?? 0);
        $protein_ratio = floatval($_POST['protein_ratio'] ?? 30);
        $carbs_ratio = floatval($_POST['carbs_ratio'] ?? 40);
        $fat_ratio = floatval($_POST['fat_ratio'] ?? 30);
        
        // Validation
        if ($program_id <= 0) {
            $errors[] = "ID de programme invalide";
        }
        
        if (empty($name)) {
            $errors[] = "Le nom du programme est requis";
        }
        
        if ($protein_ratio + $carbs_ratio + $fat_ratio !== 100) {
            $errors[] = "La somme des ratios de macronutriments doit être égale à 100%";
        }
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE nutrition_programs 
                        SET name = ?, description = ?, calorie_adjustment = ?, 
                            protein_ratio = ?, carbs_ratio = ?, fat_ratio = ?, 
                            updated_at = NOW() 
                        WHERE id = ?";
                $result = update($sql, [$name, $description, $calorie_adjustment, 
                                       $protein_ratio, $carbs_ratio, $fat_ratio, $program_id]);
                
                if ($result) {
                    $success_message = "Programme nutritionnel mis à jour avec succès";
                } else {
                    $errors[] = "Une erreur s'est produite lors de la mise à jour du programme";
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
    
    // Suppression d'un programme nutritionnel
    elseif ($post_action === 'delete_program') {
        $program_id = intval($_POST['program_id'] ?? 0);
        
        if ($program_id <= 0) {
            $errors[] = "ID de programme invalide";
        } else {
            try {
                // Vérifier si des utilisateurs sont assignés à ce programme
                $sql = "SELECT COUNT(*) as count FROM user_profiles WHERE nutrition_program_id = ?";
                $result = fetchOne($sql, [$program_id]);
                
                if ($result['count'] > 0) {
                    $errors[] = "Impossible de supprimer ce programme car des utilisateurs y sont assignés";
                } else {
                    $sql = "DELETE FROM nutrition_programs WHERE id = ?";
                    $result = delete($sql, [$program_id]);
                    
                    if ($result) {
                        $success_message = "Programme nutritionnel supprimé avec succès";
                    } else {
                        $errors[] = "Une erreur s'est produite lors de la suppression du programme";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
    
    // Mise à jour du rôle d'un utilisateur
    elseif ($post_action === 'update_user_role') {
        $user_id_to_update = intval($_POST['user_id'] ?? 0);
        $role_id = intval($_POST['role_id'] ?? 0);
        
        // Validation
        if ($user_id_to_update <= 0) {
            $errors[] = "ID d'utilisateur invalide";
        }
        
        if ($role_id <= 0) {
            $errors[] = "ID de rôle invalide";
        }
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE users SET role_id = ? WHERE id = ?";
                $result = update($sql, [$role_id, $user_id_to_update]);
                
                if ($result) {
                    $success_message = "Rôle de l'utilisateur mis à jour avec succès";
                } else {
                    $errors[] = "Une erreur s'est produite lors de la mise à jour du rôle";
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
    
    // Création d'un repas prédéfini
    elseif ($post_action === 'create_meal') {
        if (!isset($_POST['name'], $_POST['foods'])) {
            setError("Données manquantes pour créer le repas");
        } else {
            try {
                $db->beginTransaction();

                // Calculer les totaux nutritionnels
                $totalCalories = $totalProteins = $totalCarbs = $totalFats = 0;
                foreach ($_POST['foods'] as $food) {
                    $foodData = fetchOne("SELECT calories, proteins, carbs, fats FROM foods WHERE id = ?", [$food['food_id']]);
                    if ($foodData) {
                        $quantity = floatval($food['quantity']) / 100;
                        $totalCalories += $foodData['calories'] * $quantity;
                        $totalProteins += $foodData['proteins'] * $quantity;
                        $totalCarbs += $foodData['carbs'] * $quantity;
                        $totalFats += $foodData['fats'] * $quantity;
                    }
                }

                // Insérer le repas
                $sql = "INSERT INTO predefined_meals (name, description, notes, is_public, calories, proteins, carbs, fats, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'] ?? '',
                    $_POST['notes'] ?? '',
                    isset($_POST['is_public']) ? 1 : 0,
                    round($totalCalories, 2),
                    round($totalProteins, 2),
                    round($totalCarbs, 2),
                    round($totalFats, 2)
                ]);
                
                $mealId = $db->lastInsertId();

                // Insérer les aliments du repas
                $sql = "INSERT INTO predefined_meal_items (meal_id, food_id, quantity) VALUES (?, ?, ?)";
                $stmt = $db->prepare($sql);
                foreach ($_POST['foods'] as $food) {
                    $stmt->execute([$mealId, $food['food_id'], $food['quantity']]);
                }

                $db->commit();
                setSuccess("Repas prédéfini créé avec succès");
            } catch (Exception $e) {
                $db->rollBack();
                setError("Erreur lors de la création du repas : " . $e->getMessage());
            }
        }
    }
    
    // Mise à jour d'un repas prédéfini
    elseif ($post_action === 'update_meal') {
        if (!isset($_POST['meal_id'], $_POST['name'], $_POST['foods'])) {
            setError("Données manquantes pour modifier le repas");
        } else {
            try {
                $db->beginTransaction();

                // Calculer les totaux nutritionnels
                $totalCalories = $totalProteins = $totalCarbs = $totalFats = 0;
                foreach ($_POST['foods'] as $food) {
                    $foodData = fetchOne("SELECT calories, proteins, carbs, fats FROM foods WHERE id = ?", [$food['food_id']]);
                    if ($foodData) {
                        $quantity = floatval($food['quantity']) / 100;
                        $totalCalories += $foodData['calories'] * $quantity;
                        $totalProteins += $foodData['proteins'] * $quantity;
                        $totalCarbs += $foodData['carbs'] * $quantity;
                        $totalFats += $foodData['fats'] * $quantity;
                    }
                }

                // Mettre à jour le repas
                $sql = "UPDATE predefined_meals 
                        SET name = ?, description = ?, notes = ?, is_public = ?, 
                            calories = ?, proteins = ?, carbs = ?, fats = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'] ?? '',
                    $_POST['notes'] ?? '',
                    isset($_POST['is_public']) ? 1 : 0,
                    round($totalCalories, 2),
                    round($totalProteins, 2),
                    round($totalCarbs, 2),
                    round($totalFats, 2),
                    $_POST['meal_id']
                ]);

                // Supprimer les anciens aliments
                $sql = "DELETE FROM predefined_meal_items WHERE meal_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['meal_id']]);

                // Insérer les nouveaux aliments
                $sql = "INSERT INTO predefined_meal_items (meal_id, food_id, quantity) VALUES (?, ?, ?)";
                $stmt = $db->prepare($sql);
                foreach ($_POST['foods'] as $food) {
                    $stmt->execute([$_POST['meal_id'], $food['food_id'], $food['quantity']]);
                }

                $db->commit();
                setSuccess("Repas prédéfini modifié avec succès");
            } catch (Exception $e) {
                $db->rollBack();
                setError("Erreur lors de la modification du repas : " . $e->getMessage());
            }
        }
    }
    
    // Suppression d'un repas prédéfini
    elseif ($post_action === 'delete_meal') {
        if (!isset($_POST['meal_id'])) {
            setError("ID du repas manquant");
        } else {
            try {
                $db->beginTransaction();

                // Supprimer les aliments du repas
                $sql = "DELETE FROM predefined_meal_items WHERE meal_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['meal_id']]);

                // Supprimer le repas
                $sql = "DELETE FROM predefined_meals WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['meal_id']]);

                $db->commit();
                setSuccess("Repas prédéfini supprimé avec succès");
            } catch (Exception $e) {
                $db->rollBack();
                setError("Erreur lors de la suppression du repas : " . $e->getMessage());
            }
        }
    }
}

// Traitement des actions pour la gestion des programmes
if ($section === 'program_management' || $section === 'delete_program') {
    // Initialiser les variables
    $action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'create';
    $program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
    $errors = [];

    // Récupérer les détails du programme si en mode édition
    $program = null;
    if ($action === 'edit' && $program_id > 0) {
        try {
            $sql = "SELECT * FROM programs WHERE id = ?";
            $program = fetchOne($sql, [$program_id]);
            if (!$program) {
                $_SESSION['error'] = "Programme non trouvé.";
                redirect('admin.php?section=programs');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la récupération du programme: " . $e->getMessage();
            redirect('admin.php?section=programs');
        }
    }

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $type = sanitizeInput($_POST['type'] ?? 'complet');
        $calorie_adjustment = floatval($_POST['calorie_adjustment'] ?? 0);
        // Les valeurs sont déjà en pourcentage dans le formulaire
        $protein_ratio = floatval($_POST['protein_ratio'] ?? 30) / 100;
        $carbs_ratio = floatval($_POST['carbs_ratio'] ?? 40) / 100;
        $fat_ratio = floatval($_POST['fat_ratio'] ?? 30) / 100;
        
        // Validation
        if (empty($name)) {
            $errors[] = "Le nom du programme est requis";
        }
        
        if (empty($description)) {
            $errors[] = "La description du programme est requise";
        }
        
        if (!in_array($type, ['complet', 'nutrition', 'exercice'])) {
            $errors[] = "Type de programme invalide";
        }
        
        // Vérifier que la somme des pourcentages est égale à 100
        $total_percentage = floatval($_POST['protein_ratio'] ?? 30) + 
                          floatval($_POST['carbs_ratio'] ?? 40) + 
                          floatval($_POST['fat_ratio'] ?? 30);
        
        if (abs($total_percentage - 100) > 0.01) {
            $errors[] = "La somme des pourcentages de macronutriments doit être égale à 100%";
        }
        
        if (empty($errors)) {
            try {
                if ($action === 'edit' && $program_id > 0) {
                    // Mise à jour du programme
                    $sql = "UPDATE programs SET 
                            name = ?, 
                            description = ?, 
                            type = ?, 
                            calorie_adjustment = ?, 
                            protein_ratio = ?, 
                            carbs_ratio = ?, 
                            fat_ratio = ?,
                            updated_at = NOW()
                            WHERE id = ?";
                    $result = update($sql, [
                        $name, 
                        $description, 
                        $type, 
                        $calorie_adjustment, 
                        $protein_ratio, 
                        $carbs_ratio, 
                        $fat_ratio,
                        $program_id
                    ]);
                    
                    if ($result) {
                        $_SESSION['success'] = "Programme mis à jour avec succès";
                        redirect('admin.php?section=programs');
                    } else {
                        $errors[] = "Une erreur s'est produite lors de la mise à jour du programme";
                    }
                } else {
                    // Création d'un nouveau programme
                    $sql = "INSERT INTO programs (name, description, type, calorie_adjustment, protein_ratio, carbs_ratio, fat_ratio, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    $result = insert($sql, [
                        $name, 
                        $description, 
                        $type, 
                        $calorie_adjustment, 
                        $protein_ratio, 
                        $carbs_ratio, 
                        $fat_ratio
                    ]);
                    
                    if ($result) {
                        $_SESSION['success'] = "Programme créé avec succès";
                        redirect('admin.php?section=programs');
                    } else {
                        $errors[] = "Une erreur s'est produite lors de la création du programme";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
}

// Traitement de la suppression d'un programme
if ($section === 'delete_program' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_id = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;
    
    if ($program_id > 0) {
        try {
            // Mettre à jour le statut des utilisateurs qui suivent ce programme
            $sql = "UPDATE user_programs SET status = 'inactif' WHERE program_id = ?";
            update($sql, [$program_id]);
            
            // Supprimer le programme
            $sql = "DELETE FROM programs WHERE id = ?";
            $result = delete($sql, [$program_id]);
            
            if ($result) {
                $_SESSION['success'] = "Programme supprimé avec succès";
            } else {
                $_SESSION['error'] = "Une erreur s'est produite lors de la suppression du programme";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la suppression du programme: " . $e->getMessage();
        }
    }
    
    redirect('admin.php?section=programs');
}

// Traitement des actions pour la gestion des repas prédéfinis
if ($section === 'predefined_meals') {
    // Récupérer la liste des repas prédéfinis
    try {
        $sql = "SELECT pm.*, 
                       COALESCE(SUM(f.calories * pmi.quantity / 100), 0) as total_calories,
                       COALESCE(SUM(f.proteins * pmi.quantity / 100), 0) as total_protein,
                       COALESCE(SUM(f.carbs * pmi.quantity / 100), 0) as total_carbs,
                       COALESCE(SUM(f.fats * pmi.quantity / 100), 0) as total_fat
                FROM predefined_meals pm
                LEFT JOIN predefined_meal_items pmi ON pm.id = pmi.meal_id
                LEFT JOIN foods f ON pmi.food_id = f.id
                GROUP BY pm.id
                ORDER BY pm.name";
        $predefined_meals = fetchAll($sql);

        // Récupérer toutes les catégories d'aliments
        $sql = "SELECT * FROM food_categories ORDER BY name";
        $categories = fetchAll($sql);
    } catch (Exception $e) {
        setError("Erreur lors de la récupération des données : " . $e->getMessage());
        $predefined_meals = [];
        $categories = [];
    }
?>
    <!-- Predefined meals management -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Gestion des repas prédéfinis</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createMealModal">
                <i class="fas fa-plus me-1"></i>Nouveau repas
            </button>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="m-0 font-weight-bold">Liste des repas prédéfinis</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Calories</th>
                            <th>Protéines</th>
                            <th>Glucides</th>
                            <th>Lipides</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($predefined_meals as $meal): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($meal['name']); ?></td>
                                <td>
                                    <div style="max-height: 3em; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($meal['description'] ?? ''); ?>
                                    </div>
                                </td>
                                <td><?php echo number_format($meal['total_calories'], 0); ?> kcal</td>
                                <td><?php echo number_format($meal['total_protein'], 1); ?>g</td>
                                <td><?php echo number_format($meal['total_carbs'], 1); ?>g</td>
                                <td><?php echo number_format($meal['total_fat'], 1); ?>g</td>
                                <td>
                                    <span class="badge <?php echo $meal['is_public'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $meal['is_public'] ? 'Public' : 'Privé'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editMeal(<?php echo $meal['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteMeal(<?php echo $meal['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($predefined_meals)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Aucun repas prédéfini trouvé</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create/Edit Meal Modal -->
    <div class="modal fade" id="createMealModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mealModalTitle">Créer un repas prédéfini</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="mealForm" method="POST">
                        <input type="hidden" name="action" value="create_meal">
                        <input type="hidden" name="meal_id" id="mealId">

                        <div class="mb-3">
                            <label for="mealName" class="form-label">Nom du repas</label>
                            <input type="text" class="form-control" id="mealName" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="mealDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="mealDescription" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="mealNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="mealNotes" name="notes" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="isPublic" name="is_public">
                                <label class="form-check-label" for="isPublic">Rendre public</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Aliments</label>
                            <div id="foodsList" class="list-group mb-3"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addFoodItem()">
                                <i class="fas fa-plus"></i> Ajouter un aliment
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="saveMeal()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Meal Modal -->
    <div class="modal fade" id="deleteMealModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Supprimer le repas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer ce repas ?</p>
                    <p class="text-danger">Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_meal">
                        <input type="hidden" name="meal_id" id="deleteMealId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour ajouter un aliment à la liste
        function addFoodItem() {
            const foodsList = document.getElementById('foodsList');
            const foodItem = document.createElement('div');
            foodItem.className = 'list-group-item';
            const index = foodsList.children.length;
            
            foodItem.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-5">
                        <select class="form-select" name="foods[${index}][food_id]" required>
                            <option value="">Sélectionner un aliment</option>
                            <?php foreach ($categories as $category): ?>
                                <optgroup label="<?php echo htmlspecialchars($category['name']); ?>">
                                    <?php
                                    $sql = "SELECT id, name FROM foods WHERE category_id = ? ORDER BY name";
                                    $foods = fetchAll($sql, [$category['id']]);
                                    foreach ($foods as $food):
                                    ?>
                                        <option value="<?php echo $food['id']; ?>">
                                            <?php echo htmlspecialchars($food['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="number" class="form-control" name="foods[${index}][quantity]" 
                               placeholder="Quantité (g)" min="0" step="0.1" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.parentElement.remove()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            foodsList.appendChild(foodItem);
        }

        // Fonction pour éditer un repas
        function editMeal(mealId) {
            fetch(`admin/get-predefined-meal.php?id=${mealId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const meal = data.meal;
                        document.getElementById('mealModalTitle').textContent = 'Modifier le repas prédéfini';
                        document.getElementById('mealId').value = meal.id;
                        document.getElementById('mealName').value = meal.name;
                        document.getElementById('mealDescription').value = meal.description || '';
                        document.getElementById('mealNotes').value = meal.notes || '';
                        document.getElementById('isPublic').checked = meal.is_public == 1;
                        
                        // Mettre à jour le formulaire pour l'édition
                        const form = document.getElementById('mealForm');
                        form.action.value = 'update_meal';
                        
                        // Vider et remplir la liste des aliments
                        const foodsList = document.getElementById('foodsList');
                        foodsList.innerHTML = '';
                        
                        meal.foods.forEach((food, index) => {
                            addFoodItem();
                            const lastItem = foodsList.lastElementChild;
                            lastItem.querySelector('select').value = food.food_id;
                            lastItem.querySelector('input[type="number"]').value = food.quantity;
                        });
                        
                        new bootstrap.Modal(document.getElementById('createMealModal')).show();
                    } else {
                        alert('Erreur lors du chargement du repas');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement du repas');
                });
        }

        // Fonction pour supprimer un repas
        function deleteMeal(mealId) {
            document.getElementById('deleteMealId').value = mealId;
            new bootstrap.Modal(document.getElementById('deleteMealModal')).show();
        }

        // Fonction pour sauvegarder un repas
        function saveMeal() {
            const form = document.getElementById('mealForm');
            if (form.checkValidity()) {
                form.submit();
            } else {
                form.reportValidity();
            }
        }

        // Réinitialiser le formulaire lors de l'ouverture du modal de création
        document.getElementById('createMealModal').addEventListener('show.bs.modal', function (event) {
            if (!event.relatedTarget) return; // Ne pas réinitialiser si c'est une édition
            
            const form = document.getElementById('mealForm');
            form.reset();
            form.action.value = 'create_meal';
            document.getElementById('mealId').value = '';
            document.getElementById('mealModalTitle').textContent = 'Créer un repas prédéfini';
            document.getElementById('foodsList').innerHTML = '';
            addFoodItem(); // Ajouter un premier aliment vide
        });
    </script>
<?php } ?>
