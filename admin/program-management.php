<?php
// Démarrer la session
session_start();

// Debug: Afficher les informations sur le chemin
error_log("__DIR__ dans program-management.php: " . __DIR__);
error_log("Chemin d'inclusion actuel: " . getcwd());
error_log("Chemin d'inclusion PHP: " . get_include_path());

// Définir le chemin de base du site
$base_path = dirname(__DIR__);

// Inclure les fichiers nécessaires
$db_path = $base_path . '/config/database.php';
$functions_path = $base_path . '/includes/functions.php';
$admin_functions_path = $base_path . '/includes/admin_functions.php';

error_log("Chemin de base: " . $base_path);
error_log("Tentative d'inclusion de database.php: " . $db_path);
error_log("Tentative d'inclusion de functions.php: " . $functions_path);
error_log("Tentative d'inclusion de admin_functions.php: " . $admin_functions_path);

require_once $db_path;
require_once $functions_path;
require_once $admin_functions_path;

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Vérifier si l'utilisateur est un administrateur
$user_id = $_SESSION['user_id'];
if (!isAdmin($user_id)) {
    $_SESSION['error'] = "Accès refusé. Vous devez être administrateur pour accéder à cette page.";
    redirect('../dashboard.php');
}

// Récupérer la clé API ChatGPT
$chatgpt_api_key = '';
try {
    $sql = "SELECT setting_value FROM settings WHERE setting_name = 'chatgpt_api_key'";
    $result = fetchOne($sql, []);
    $chatgpt_api_key = $result ? $result['setting_value'] : '';
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération de la clé API: " . $e->getMessage();
}

// Initialiser les variables
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'create';
$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
$success_message = '';
$errors = [];

// Récupérer les détails du programme si en mode édition
$program = null;
if ($action === 'edit' && $program_id > 0) {
    try {
        $sql = "SELECT * FROM programs WHERE id = ?";
        $program = fetchOne($sql, [$program_id]);
        if (!$program) {
            $_SESSION['error'] = "Programme non trouvé.";
            redirect('../admin.php?section=programs');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la récupération du programme: " . $e->getMessage();
        redirect('../admin.php?section=programs');
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $type = sanitizeInput($_POST['type'] ?? 'complet');
    $calorie_adjustment = floatval($_POST['calorie_adjustment'] ?? 0);
    $protein_ratio = floatval($_POST['protein_ratio'] ?? 0.3);
    $carbs_ratio = floatval($_POST['carbs_ratio'] ?? 0.4);
    $fat_ratio = floatval($_POST['fat_ratio'] ?? 0.3);
    
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
    
    if ($protein_ratio + $carbs_ratio + $fat_ratio !== 1) {
        $errors[] = "La somme des ratios de macronutriments doit être égale à 1 (100%)";
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
                    redirect('../admin.php?section=programs');
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
                    redirect('../admin.php?section=programs');
                } else {
                    $errors[] = "Une erreur s'est produite lors de la création du programme";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Erreur: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'edit' ? 'Modifier' : 'Créer'; ?> un programme - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../navigation.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><?php echo $action === 'edit' ? 'Modifier' : 'Créer'; ?> un programme</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom du programme</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($program['name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($program['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label">Type de programme</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="complet" <?php echo ($program['type'] ?? '') === 'complet' ? 'selected' : ''; ?>>Complet (nutrition + exercice)</option>
                                    <option value="nutrition" <?php echo ($program['type'] ?? '') === 'nutrition' ? 'selected' : ''; ?>>Nutrition uniquement</option>
                                    <option value="exercice" <?php echo ($program['type'] ?? '') === 'exercice' ? 'selected' : ''; ?>>Exercice uniquement</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="calorie_adjustment" class="form-label">Ajustement calorique (%)</label>
                                <input type="number" class="form-control" id="calorie_adjustment" name="calorie_adjustment" 
                                       value="<?php echo $program['calorie_adjustment'] ?? 0; ?>" step="0.01" required>
                                <div class="form-text">Valeur positive pour un surplus, négative pour un déficit</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Répartition des macronutriments</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="protein_ratio" class="form-label">Protéines</label>
                                        <input type="number" class="form-control" id="protein_ratio" name="protein_ratio" 
                                               value="<?php echo ($program['protein_ratio'] ?? 0.3) * 100; ?>" step="0.1" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="carbs_ratio" class="form-label">Glucides</label>
                                        <input type="number" class="form-control" id="carbs_ratio" name="carbs_ratio" 
                                               value="<?php echo ($program['carbs_ratio'] ?? 0.4) * 100; ?>" step="0.1" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="fat_ratio" class="form-label">Lipides</label>
                                        <input type="number" class="form-control" id="fat_ratio" name="fat_ratio" 
                                               value="<?php echo ($program['fat_ratio'] ?? 0.3) * 100; ?>" step="0.1" required>
                                    </div>
                                </div>
                                <div id="macros-warning" class="form-text text-danger"></div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../admin.php?section=programs" class="btn btn-outline-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $action === 'edit' ? 'Mettre à jour' : 'Créer'; ?> le programme
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation des macronutriments
        document.addEventListener('DOMContentLoaded', function() {
            const proteinInput = document.getElementById('protein_ratio');
            const carbsInput = document.getElementById('carbs_ratio');
            const fatInput = document.getElementById('fat_ratio');
            const macrosWarning = document.getElementById('macros-warning');
            
            function validateMacros() {
                const protein = parseFloat(proteinInput.value) || 0;
                const carbs = parseFloat(carbsInput.value) || 0;
                const fat = parseFloat(fatInput.value) || 0;
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
            
            // Validation initiale
            validateMacros();
        });
    </script>
</body>
</html> 