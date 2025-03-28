<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Récupérer la clé API ChatGPT
$sql = "SELECT setting_value FROM settings WHERE setting_name = 'chatgpt_api_key'";
$api_key_setting = fetchOne($sql, []);
$api_key = $api_key_setting ? $api_key_setting['setting_value'] : '';

// Initialiser les variables
$success_message = '';
$errors = [];

// Traitement de la création d'un nouveau programme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $program_name = sanitizeInput($_POST['program_name'] ?? '');
        $program_description = sanitizeInput($_POST['program_description'] ?? '');
        $program_type = sanitizeInput($_POST['program_type'] ?? 'complet'); // complet, nutrition, exercice
        $calorie_adjustment = (float)($_POST['calorie_adjustment'] ?? 0);
        $protein_ratio = (float)($_POST['protein_ratio'] ?? 0.3);
        $carbs_ratio = (float)($_POST['carbs_ratio'] ?? 0.4);
        $fat_ratio = (float)($_POST['fat_ratio'] ?? 0.3);
        
        if (empty($program_name)) {
            $errors[] = "Le nom du programme est requis";
        }
        
        if (empty($api_key)) {
            $errors[] = "La clé API ChatGPT n'est pas configurée";
        }
        
        if (empty($errors)) {
            // Générer le contenu du programme avec ChatGPT
            $prompt = "En tant qu'expert en nutrition et fitness, crée un programme détaillé avec les spécifications suivantes :\n\n";
            $prompt .= "Type de programme : " . ucfirst($program_type) . "\n";
            $prompt .= "Ajustement calorique : " . $calorie_adjustment . "%\n";
            $prompt .= "Répartition des macronutriments :\n";
            $prompt .= "- Protéines : " . ($protein_ratio * 100) . "%\n";
            $prompt .= "- Glucides : " . ($carbs_ratio * 100) . "%\n";
            $prompt .= "- Lipides : " . ($fat_ratio * 100) . "%\n\n";
            $prompt .= "Génère un programme complet et détaillé qui inclut :\n";
            if ($program_type === 'complet' || $program_type === 'nutrition') {
                $prompt .= "- Un plan alimentaire détaillé avec les repas et les portions\n";
                $prompt .= "- Les recommandations nutritionnelles\n";
            }
            if ($program_type === 'complet' || $program_type === 'exercice') {
                $prompt .= "- Un programme d'exercices avec les séries et répétitions\n";
                $prompt .= "- Les conseils d'entraînement\n";
            }
            
            $response = callChatGPTAPI($prompt, $api_key);
            
            if ($response === false) {
                $errors[] = "Une erreur s'est produite lors de la génération du programme";
            } else {
                // Insérer le programme dans la base de données
                $sql = "INSERT INTO programs (name, description, type, content, calorie_adjustment, protein_ratio, carbs_ratio, fat_ratio, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $result = insert($sql, [
                    $program_name,
                    $program_description,
                    $program_type,
                    $response,
                    $calorie_adjustment,
                    $protein_ratio,
                    $carbs_ratio,
                    $fat_ratio
                ]);
                
                if ($result) {
                    $success_message = "Le programme a été créé avec succès !";
                } else {
                    $errors[] = "Une erreur s'est produite lors de l'enregistrement du programme";
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = "Une erreur s'est produite : " . $e->getMessage();
        error_log("Erreur dans program-management.php: " . $e->getMessage());
    }
}

// Récupérer la liste des programmes
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM user_programs up WHERE up.program_id = p.id AND up.status = 'actif') as active_users
        FROM programs p 
        ORDER BY p.created_at DESC";
$programs = fetchAll($sql, []);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Programmes - Administration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'admin-navigation.php'; ?>

    <div class="container py-4">
        <h1 class="mb-4">Gestion des Programmes</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire de création de programme -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Créer un nouveau programme</h5>
            </div>
            <div class="card-body">
                <?php if (empty($api_key)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        La clé API ChatGPT n'est pas configurée. Veuillez la configurer dans les paramètres.
                    </div>
                <?php else: ?>
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="program_name" class="form-label">Nom du programme</label>
                                <input type="text" class="form-control" id="program_name" name="program_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="program_type" class="form-label">Type de programme</label>
                                <select class="form-select" id="program_type" name="program_type" required>
                                    <option value="complet">Complet (Nutrition + Exercice)</option>
                                    <option value="nutrition">Nutrition uniquement</option>
                                    <option value="exercice">Exercice uniquement</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="program_description" class="form-label">Description</label>
                            <textarea class="form-control" id="program_description" name="program_description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="calorie_adjustment" class="form-label">Ajustement calorique (%)</label>
                                <input type="number" class="form-control" id="calorie_adjustment" name="calorie_adjustment" value="0" step="0.1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Répartition des macronutriments</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Protéines</span>
                                    <input type="number" class="form-control" name="protein_ratio" value="0.3" step="0.1" min="0" max="1">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Glucides</span>
                                    <input type="number" class="form-control" name="carbs_ratio" value="0.4" step="0.1" min="0" max="1">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text">Lipides</span>
                                    <input type="number" class="form-control" name="fat_ratio" value="0.3" step="0.1" min="0" max="1">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Créer le programme
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Liste des programmes -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Programmes disponibles</h5>
            </div>
            <div class="card-body">
                <?php if (empty($programs)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i>
                        Aucun programme disponible.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Type</th>
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
                                            <?php
                                            switch ($program['type']) {
                                                case 'complet':
                                                    echo '<span class="badge bg-primary">Complet</span>';
                                                    break;
                                                case 'nutrition':
                                                    echo '<span class="badge bg-info">Nutrition</span>';
                                                    break;
                                                case 'exercice':
                                                    echo '<span class="badge bg-warning">Exercice</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $program['active_users']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($program['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewProgram<?php echo $program['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteProgram<?php echo $program['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modals pour voir et supprimer les programmes -->
    <?php foreach ($programs as $program): ?>
        <!-- Modal pour voir le programme -->
        <div class="modal fade" id="viewProgram<?php echo $program['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo htmlspecialchars($program['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($program['description'])); ?></p>
                        
                        <h6>Contenu du programme</h6>
                        <div class="program-content">
                            <?php echo nl2br(htmlspecialchars($program['content'])); ?>
                        </div>
                        
                        <h6 class="mt-3">Paramètres</h6>
                        <ul class="list-unstyled">
                            <li>Ajustement calorique : <?php echo $program['calorie_adjustment']; ?>%</li>
                            <li>Protéines : <?php echo ($program['protein_ratio'] * 100); ?>%</li>
                            <li>Glucides : <?php echo ($program['carbs_ratio'] * 100); ?>%</li>
                            <li>Lipides : <?php echo ($program['fat_ratio'] * 100); ?>%</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal pour supprimer le programme -->
        <div class="modal fade" id="deleteProgram<?php echo $program['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer le programme</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer le programme "<?php echo htmlspecialchars($program['name']); ?>" ?</p>
                        <p class="text-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Cette action est irréversible et affectera tous les utilisateurs qui suivent ce programme.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <a href="delete-program.php?id=<?php echo $program['id']; ?>" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Supprimer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 