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
                $result = update($sql, [$api_key]);
            } else {
                // Créer un nouveau paramètre
                $sql = "INSERT INTO settings (setting_name, setting_value, is_public, created_at) VALUES ('chatgpt_api_key', ?, 0, NOW())";
                $result = insert($sql, [$api_key]);
            }
            
            if ($result) {
                $success_message = "Clé API ChatGPT mise à jour avec succès";
            } else {
                $errors[] = "Une erreur s'est produite lors de la mise à jour de la clé API";
            }
        } catch (Exception $e) {
            $errors[] = "Erreur: " . $e->getMessage();
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
}

// Récupérer les données pour le tableau de bord administrateur
$total_users = 0;
$total_weight_logs = 0;
$total_food_logs = 0;
$total_exercise_logs = 0;
$recent_users = [];
$user_stats = [];

try {
    // Nombre total d'utilisateurs
    $sql = "SELECT COUNT(*) as count FROM users";
    $result = fetchOne($sql, []);
    $total_users = $result ? $result['count'] : 0;
    
    // Nombre total d'entrées de poids
    $sql = "SELECT COUNT(*) as count FROM weight_logs";
    $result = fetchOne($sql, []);
    $total_weight_logs = $result ? $result['count'] : 0;
    
    // Nombre total d'entrées alimentaires
    $sql = "SELECT COUNT(*) as count FROM food_logs";
    $result = fetchOne($sql, []);
    $total_food_logs = $result ? $result['count'] : 0;
    
    // Nombre total d'entrées d'exercice
    $sql = "SELECT COUNT(*) as count FROM exercise_logs";
    $result = fetchOne($sql, []);
    $total_exercise_logs = $result ? $result['count'] : 0;
    
    // Utilisateurs récents
    $sql = "SELECT u.*, DATE_FORMAT(u.created_at, '%d/%m/%Y') as formatted_date, 
            (SELECT COUNT(*) FROM weight_logs wl WHERE wl.user_id = u.id) as weight_count,
            (SELECT COUNT(*) FROM food_logs fl WHERE fl.user_id = u.id) as food_count,
            (SELECT COUNT(*) FROM exercise_logs el WHERE el.user_id = u.id) as exercise_count
            FROM users u 
            ORDER BY u.created_at DESC 
            LIMIT 10";
    $recent_users = fetchAll($sql, []);
    
    // Statistiques des utilisateurs
    $sql = "SELECT 
            (SELECT COUNT(*) FROM users WHERE role_id = 1) as admin_count,
            (SELECT COUNT(*) FROM users WHERE role_id = 2) as user_count,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as today_count,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as week_count,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as month_count";
    $user_stats = fetchOne($sql, []);
} catch (Exception $e) {
    $errors[] = "Erreur lors de la récupération des statistiques: " . $e->getMessage();
}

// Récupérer la clé API ChatGPT
$chatgpt_api_key = '';
try {
    $sql = "SELECT setting_value FROM settings WHERE setting_name = 'chatgpt_api_key'";
    $result = fetchOne($sql, []);
    $chatgpt_api_key = $result ? $result['setting_value'] : '';
} catch (Exception $e) {
    $errors[] = "Erreur lors de la récupération de la clé API: " . $e->getMessage();
}

// Récupérer les programmes
$programs = [];
try {
    $sql = "SELECT p.*, 
            (SELECT COUNT(*) FROM user_programs up WHERE up.program_id = p.id AND up.status = 'actif') as user_count,
            DATE_FORMAT(p.created_at, '%d/%m/%Y') as formatted_date
            FROM programs p 
            ORDER BY p.created_at DESC";
    $programs = fetchAll($sql, []);
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des programmes: " . $e->getMessage();
}

// Récupérer les détails d'un programme spécifique si demandé
$program_details = null;
if ($section === 'programs' && $action === 'edit' && isset($_GET['program_id'])) {
    $program_id = intval($_GET['program_id']);
    try {
        $sql = "SELECT * FROM programs WHERE id = ?";
        $program_details = fetchOne($sql, [$program_id]);
    } catch (Exception $e) {
        $errors[] = "Erreur lors de la récupération des détails du programme: " . $e->getMessage();
    }
}

// Récupérer les repas prédéfinis créés par l'administrateur
$predefined_meals = [];
try {
    $sql = "SELECT pm.*, 
            (SELECT COUNT(*) FROM predefined_meal_foods pmf WHERE pmf.predefined_meal_id = pm.id) as food_count,
            DATE_FORMAT(pm.created_at, '%d/%m/%Y') as formatted_date
            FROM predefined_meals pm 
            WHERE pm.created_by_admin = 1
            ORDER BY pm.created_at DESC";
    $predefined_meals = fetchAll($sql, []);
} catch (Exception $e) {
    $errors[] = "Erreur lors de la récupération des repas prédéfinis: " . $e->getMessage();
}

// Récupérer les détails d'un repas prédéfini spécifique si demandé
$predefined_meal_details = null;
$predefined_meal_foods = [];
if ($section === 'predefined_meals' && $action === 'edit_meal' && isset($_GET['meal_id'])) {
    $predefined_meal_id = intval($_GET['meal_id']);
    try {
        $sql = "SELECT * FROM predefined_meals WHERE id = ? AND created_by_admin = 1";
        $predefined_meal_details = fetchOne($sql, [$predefined_meal_id]);
        
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
    } catch (Exception $e) {
        $errors[] = "Erreur lors de la récupération des détails du repas prédéfini: " . $e->getMessage();
    }
}

// Récupérer tous les utilisateurs
$all_users = [];
if ($section === 'users') {
    try {
        $sql = "SELECT u.*, r.name as role_name, 
                DATE_FORMAT(u.created_at, '%d/%m/%Y') as formatted_date,
                (SELECT COUNT(*) FROM weight_logs wl WHERE wl.user_id = u.id) as weight_count,
                (SELECT COUNT(*) FROM food_logs fl WHERE fl.user_id = u.id) as food_count,
                (SELECT COUNT(*) FROM exercise_logs el WHERE el.user_id = u.id) as exercise_count,
                (SELECT MAX(log_date) FROM weight_logs wl WHERE wl.user_id = u.id) as last_weight_date
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id
                ORDER BY u.created_at DESC";
        $all_users = fetchAll($sql, []);
    } catch (Exception $e) {
        $errors[] = "Erreur lors de la récupération des utilisateurs: " . $e->getMessage();
    }
}

// Récupérer les rôles utilisateur
$user_roles = [];
try {
    $sql = "SELECT * FROM roles ORDER BY id";
    $user_roles = fetchAll($sql, []);
} catch (Exception $e) {
    $errors[] = "Erreur lors de la récupération des rôles: " . $e->getMessage();
}

// Récupérer les aliments disponibles
$foods = [];
try {
    $sql = "SELECT * FROM foods ORDER BY name";
    $foods = fetchAll($sql, []);
} catch (Exception $e) {
    $errors[] = "Erreur lors de la récupération des aliments: " . $e->getMessage();
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

// Récupérer les programmes nutritionnels
$nutrition_programs = [];
try {
    $sql = "SELECT np.*, 
            (SELECT COUNT(*) FROM user_profiles up WHERE up.nutrition_program_id = np.id) as user_count,
            DATE_FORMAT(np.created_at, '%d/%m/%Y') as formatted_date
            FROM nutrition_programs np 
            ORDER BY np.created_at DESC";
    $nutrition_programs = fetchAll($sql, []);
} catch (Exception $e) {
    $errors[] = "Erreur lors de la récupération des programmes nutritionnels: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        
        .sidebar .nav-link {
            color: #495057;
            border-radius: 0;
            padding: 0.75rem 1.25rem;
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
        }
        
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
            color: #495057;
        }
        
        .sidebar .nav-link.active:hover {
            background-color: #0b5ed7;
            color: #fff;
        }
        
        .admin-content {
            padding: 1.5rem;
        }
        
        .stat-card {
            border-left: 4px solid;
            border-radius: 4px;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.primary {
            border-left-color: #0d6efd;
        }
        
        .stat-card.success {
            border-left-color: #198754;
        }
        
        .stat-card.warning {
            border-left-color: #ffc107;
        }
        
        .stat-card.danger {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $section === 'dashboard' ? 'active' : ''; ?>" href="admin.php?section=dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $section === 'users' ? 'active' : ''; ?>" href="admin.php?section=users">
                                <i class="fas fa-users me-2"></i>Utilisateurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $section === 'programs' ? 'active' : ''; ?>" href="admin.php?section=programs">
                                <i class="fas fa-dumbbell me-2"></i>Programmes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $section === 'api_settings' ? 'active' : ''; ?>" href="admin.php?section=api_settings">
                                <i class="fas fa-key me-2"></i>Paramètres API
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $section === 'reports' ? 'active' : ''; ?>" href="admin.php?section=reports">
                                <i class="fas fa-chart-bar me-2"></i>Rapports
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
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

                <?php if ($section === 'dashboard'): ?>
                    <!-- Dashboard -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Tableau de bord administrateur</h1>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow-sm h-100 stat-card primary">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Utilisateurs</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow-sm h-100 stat-card success">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Entrées de poids</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_weight_logs; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-weight fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow-sm h-100 stat-card warning">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Entrées alimentaires</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_food_logs; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-utensils fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow-sm h-100 stat-card danger">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Entrées d'exercice</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_exercise_logs; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-running fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h6 class="m-0 font-weight-bold">Utilisateurs récents</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nom d'utilisateur</th>
                                                    <th>Email</th>
                                                    <th>Date d'inscription</th>
                                                    <th>Activité</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_users as $user): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo $user['formatted_date']; ?></td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo $user['weight_count']; ?> poids</span>
                                                            <span class="badge bg-success"><?php echo $user['food_count']; ?> repas</span>
                                                            <span class="badge bg-warning"><?php echo $user['exercise_count']; ?> exercices</span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($recent_users)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">Aucun utilisateur récent</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h6 class="m-0 font-weight-bold">Statistiques des utilisateurs</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <div class="card border-left-primary shadow-sm h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Administrateurs</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $user_stats['admin_count'] ?? 0; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-4">
                                            <div class="card border-left-success shadow-sm h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Utilisateurs standard</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $user_stats['user_count'] ?? 0; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-user fa-2x text-gray-300"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-4">
                                            <div class="card border-left-info shadow-sm h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Aujourd'hui</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $user_stats['today_count'] ?? 0; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-4">
                                            <div class="card border-left-warning shadow-sm h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Cette semaine</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $user_stats['week_count'] ?? 0; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-4">
                                            <div class="card border-left-danger shadow-sm h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Ce mois</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $user_stats['month_count'] ?? 0; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'users'): ?>
                    <!-- Users management -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Gestion des utilisateurs</h1>
                    </div>
                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold">Liste des utilisateurs</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom d'utilisateur</th>
                                            <th>Email</th>
                                            <th>Rôle</th>
                                            <th>Date d'inscription</th>
                                            <th>Dernière activité</th>
                                            <th>Statistiques</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $user['role_id'] == 1 ? 'bg-danger' : 'bg-primary'; ?>">
                                                        <?php echo htmlspecialchars($user['role_name']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $user['formatted_date']; ?></td>
                                                <td><?php echo $user['last_weight_date'] ? date('d/m/Y', strtotime($user['last_weight_date'])) : '—'; ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $user['weight_count']; ?> poids</span>
                                                    <span class="badge bg-success"><?php echo $user['food_count']; ?> repas</span>
                                                    <span class="badge bg-warning"><?php echo $user['exercise_count']; ?> exercices</span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Edit User Modal -->
                                            <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editUserModalLabel<?php echo $user['id']; ?>">Modifier l'utilisateur: <?php echo htmlspecialchars($user['username']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="admin.php?section=users" method="POST">
                                                            <input type="hidden" name="action" value="update_user_role">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="role_id<?php echo $user['id']; ?>" class="form-label">Rôle</label>
                                                                    <select class="form-select" id="role_id<?php echo $user['id']; ?>" name="role_id" required>
                                                                        <?php foreach ($user_roles as $role): ?>
                                                                            <option value="<?php echo $role['id']; ?>" <?php echo $user['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($role['name']); ?> - <?php echo htmlspecialchars($role['description']); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="alert alert-info">
                                                                    <p class="mb-0"><strong>Statistiques de l'utilisateur:</strong></p>
                                                                    <ul class="mb-0">
                                                                        <li>Entrées de poids: <?php echo $user['weight_count']; ?></li>
                                                                        <li>Entrées alimentaires: <?php echo $user['food_count']; ?></li>
                                                                        <li>Entrées d'exercice: <?php echo $user['exercise_count']; ?></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($all_users)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">Aucun utilisateur trouvé</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'programs'): ?>
                    <!-- Programs management -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Gestion des programmes</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="admin/program-management.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus me-1"></i>Créer un nouveau programme
                            </a>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold">Liste des programmes</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Utilisateurs actifs</th>
                                            <th>Date de création</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($programs as $program): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($program['name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo isset($program['type']) ? ($program['type'] === 'complet' ? 'primary' : ($program['type'] === 'nutrition' ? 'success' : 'warning')) : 'secondary'; ?>">
                                                        <?php echo isset($program['type']) ? ucfirst($program['type']) : 'Non défini'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars(substr($program['description'], 0, 100)) . (strlen($program['description']) > 100 ? '...' : ''); ?></td>
                                                <td><?php echo $program['user_count']; ?></td>
                                                <td><?php echo $program['formatted_date']; ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="admin/program-management.php?action=edit&program_id=<?php echo $program['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteProgramModal<?php echo $program['id']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- Delete Program Modal -->
                                            <div class="modal fade" id="deleteProgramModal<?php echo $program['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Supprimer le programme</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Êtes-vous sûr de vouloir supprimer ce programme ? Cette action est irréversible.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <form action="admin/delete-program.php" method="POST">
                                                                <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($programs)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Aucun programme trouvé</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'predefined_meals'): ?>
                    <!-- Predefined Meals Management -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Gestion des repas prédéfinis</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="admin.php?section=predefined_meals&action=create" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus me-1"></i>Créer un repas prédéfini
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($action === 'create'): ?>
                        <!-- Create Predefined Meal Form -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h6 class="m-0 font-weight-bold">Créer un nouveau repas prédéfini</h6>
                            </div>
                            <div class="card-body">
                                <form action="admin.php?section=predefined_meals" method="POST" novalidate>
                                    <input type="hidden" name="action" value="create_predefined_meal">
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nom du repas</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_public" name="is_public" checked>
                                        <label class="form-check-label" for="is_public">Rendre ce repas public (visible par tous les utilisateurs)</label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="admin.php?section=predefined_meals" class="btn btn-outline-secondary">Annuler</a>
                                        <button type="submit" class="btn btn-primary">Créer le repas</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php elseif ($action === 'edit_meal' && $predefined_meal_details): ?>
                        <!-- Edit Predefined Meal -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold">
                                    Éditer le repas prédéfini: <?php echo htmlspecialchars($predefined_meal_details['name']); ?>
                                    <?php if ($predefined_meal_details['is_public']): ?>
                                        <span class="badge bg-success ms-2">Public</span>
                                    <?php endif; ?>
                                </h6>
                                <form action="admin.php?section=predefined_meals" method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete_predefined_meal">
                                    <input type="hidden" name="predefined_meal_id" value="<?php echo $predefined_meal_details['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce repas prédéfini ?')">
                                        <i class="fas fa-trash me-1"></i>Supprimer
                                    </button>
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
                                
                                <!-- Liste des aliments du repas -->
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
                                                    <th>Actions</th>
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
                                                        <td>
                                                            <form action="admin.php?section=predefined_meals&action=edit_meal&meal_id=<?php echo $predefined_meal_details['id']; ?>" method="POST">
                                                                <input type="hidden" name="action" value="remove_food_from_predefined_meal">
                                                                <input type="hidden" name="food_id" value="<?php echo $food['id']; ?>">
                                                                <input type="hidden" name="predefined_meal_id" value="<?php echo $predefined_meal_details['id']; ?>">
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
                                <form action="admin.php?section=predefined_meals&action=edit_meal&meal_id=<?php echo $predefined_meal_details['id']; ?>" method="POST" novalidate>
                                    <input type="hidden" name="action" value="add_food_to_predefined_meal">
                                    <input type="hidden" name="predefined_meal_id" value="<?php echo $predefined_meal_details['id']; ?>">
                                    
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
                                        <a href="admin.php?section=predefined_meals" class="btn btn-outline-secondary">Retour à la liste</a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Ajouter l'aliment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Predefined Meals List -->
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
                                                <th>Aliments</th>
                                                <th>Visibilité</th>
                                                <th>Date de création</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($predefined_meals as $meal): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($meal['name']); ?></td>
                                                    <td>
                                                        <?php 
                                                            echo !empty($meal['description']) 
                                                                ? htmlspecialchars(substr($meal['description'], 0, 50)) . (strlen($meal['description']) > 50 ? '...' : '') 
                                                                : '—'; 
                                                        ?>
                                                    </td>
                                                    <td><?php echo $meal['food_count']; ?></td>
                                                    <td>
                                                        <?php if ($meal['is_public']): ?>
                                                            <span class="badge bg-success">Public</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Privé</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $meal['formatted_date']; ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="admin.php?section=predefined_meals&action=edit_meal&meal_id=<?php echo $meal['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteMealModal<?php echo $meal['id']; ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Delete Meal Modal -->
                                                <div class="modal fade" id="deleteMealModal<?php echo $meal['id']; ?>" tabindex="-1" aria-labelledby="deleteMealModalLabel<?php echo $meal['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteMealModalLabel<?php echo $meal['id']; ?>">Confirmer la suppression</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Êtes-vous sûr de vouloir supprimer le repas prédéfini "<?php echo htmlspecialchars($meal['name']); ?>" ?</p>
                                                                <p class="text-danger">Cette action est irréversible.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <form action="admin.php?section=predefined_meals" method="POST">
                                                                    <input type="hidden" name="action" value="delete_predefined_meal">
                                                                    <input type="hidden" name="predefined_meal_id" value="<?php echo $meal['id']; ?>">
                                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (empty($predefined_meals)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">Aucun repas prédéfini trouvé</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php elseif ($section === 'api_settings'): ?>
                    <!-- API Settings -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Paramètres API</h1>
                    </div>
                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold">Clé API ChatGPT</h6>
                        </div>
                        <div class="card-body">
                            <form action="admin.php?section=api_settings" method="POST" novalidate>
                                <input type="hidden" name="action" value="update_api_key">
                                
                                <div class="mb-3">
                                    <label for="api_key" class="form-label">Clé API</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="api_key" name="api_key" value="<?php echo htmlspecialchars($chatgpt_api_key); ?>" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Cette clé API sera utilisée pour toutes les fonctionnalités d'IA de l'application.</div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">Comment obtenir une clé API ChatGPT</h6>
                                    <ol class="mb-0">
                                        <li>Créez un compte sur <a href="https://platform.openai.com/" target="_blank">OpenAI Platform</a></li>
                                        <li>Accédez à la section "API Keys" dans votre compte</li>
                                        <li>Cliquez sur "Create new secret key"</li>
                                        <li>Copiez la clé générée et collez-la ci-dessus</li>
                                    </ol>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Enregistrer la clé API</button>
                            </form>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'reports'): ?>
                    <!-- Reports -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Rapports et statistiques</h1>
                    </div>
                    
                    <div class="alert alert-info">
                        <p class="mb-0">Cette section est en cours de développement. Les rapports détaillés seront disponibles prochainement.</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h6 class="m-0 font-weight-bold">Statistiques des utilisateurs</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <tbody>
                                                <tr>
                                                    <td>Nombre total d'utilisateurs</td>
                                                    <td><strong><?php echo $total_users; ?></strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Administrateurs</td>
                                                    <td><strong><?php echo $user_stats['admin_count'] ?? 0; ?></strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Utilisateurs standard</td>
                                                    <td><strong><?php echo $user_stats['user_count'] ?? 0; ?></strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Nouveaux utilisateurs aujourd'hui</td>
                                                    <td><strong><?php echo $user_stats['today_count'] ?? 0; ?></strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Nouveaux utilisateurs cette semaine</td>
                                                    <td><strong><?php echo $user_stats['week_count'] ?? 0; ?></strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Nouveaux utilisateurs ce mois</td>
                                                    <td><strong><?php echo $user_stats['month_count'] ?? 0; ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h6 class="m-0 font-weight-bold">Statistiques d'activité</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <tbody>
                                                <tr>
                                                    <td>Nombre total d'entrées de poids</td>
                                                    <td><strong><?php echo $total_weight_logs; ?></strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Nombre total d'entrées alimentaires</td>
                                                    <td><strong><?php echo $total_food_logs; ?></strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Nombre total d'entrées d'exercice</td>
                                                    <td><strong><?php echo $total_exercise_logs; ?></strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Nombre de programmes actifs</td>
                                                    <td><strong><?php echo count($programs); ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle API key visibility
        document.addEventListener('DOMContentLoaded', function() {
            const apiKeyInput = document.getElementById('api_key');
            const toggleButton = document.getElementById('toggleApiKey');
            
            if (apiKeyInput && toggleButton) {
                toggleButton.addEventListener('click', function() {
                    if (apiKeyInput.type === 'password') {
                        apiKeyInput.type = 'text';
                        toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        apiKeyInput.type = 'password';
                        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                });
            }
            
            // Validate macronutrient percentages
            const proteinInput = document.getElementById('protein_pct');
            const carbsInput = document.getElementById('carbs_pct');
            const fatInput = document.getElementById('fat_pct');
            const macrosWarning = document.getElementById('macros-warning');
            
            if (proteinInput && carbsInput && fatInput && macrosWarning) {
                function validateMacros() {
                    const protein = parseInt(proteinInput.value) || 0;
                    const carbs = parseInt(carbsInput.value) || 0;
                    const fat = parseInt(fatInput.value) || 0;
                    const total = protein + carbs + fat;
                    
                    if (total !== 100) {
                        macrosWarning.textContent = `La somme des pourcentages doit être égale à 100%. Actuellement: ${total}%`;
                    } else {
                        macrosWarning.textContent = '';
                    }
                }
                
                proteinInput.addEventListener('input', validateMacros);
                carbsInput.addEventListener('input', validateMacros);
                fatInput.addEventListener('input', validateMacros);
                
                // Initial validation
                validateMacros();
            }
            
            // Update food nutrients based on selection
            const foodSelect = document.getElementById('food_id');
            const quantityInput = document.getElementById('quantity');
            const caloriesInput = document.getElementById('custom_calories');
            const proteinNutrientInput = document.getElementById('custom_protein');
            const carbsNutrientInput = document.getElementById('custom_carbs');
            const fatNutrientInput = document.getElementById('custom_fat');
            
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
                        proteinNutrientInput.value = ((protein * quantity) / 100).toFixed(1);
                        carbsNutrientInput.value = ((carbs * quantity) / 100).toFixed(1);
                        fatNutrientInput.value = ((fat * quantity) / 100).toFixed(1);
                    }
                }
                
                foodSelect.addEventListener('change', updateNutrients);
                quantityInput.addEventListener('input', updateNutrients);
                
                // Initial update
                updateNutrients();
            }
        });
    </script>
</body>
</html>
