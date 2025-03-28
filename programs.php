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

// Initialiser les variables
$success_message = '';
$errors = [];

// Traitement de la sélection d'un programme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['program_id'])) {
    try {
        $program_id = (int)$_POST['program_id'];
        
        // Vérifier si le programme existe
        $sql = "SELECT * FROM programs WHERE id = ?";
        $program = fetchOne($sql, [$program_id]);
        
        if (!$program) {
            $errors[] = "Programme non trouvé.";
        } else {
            // Désactiver le programme actuel de l'utilisateur s'il en a un
            $sql = "UPDATE user_programs SET status = 'inactif', updated_at = NOW() 
                    WHERE user_id = ? AND status = 'actif'";
            update($sql, [$user_id]);
            
            // Activer le nouveau programme
            $sql = "INSERT INTO user_programs (user_id, program_id, status, created_at) 
                    VALUES (?, ?, 'actif', NOW())";
            $result = insert($sql, [$user_id, $program_id]);
            
            if ($result) {
                // Récupérer le profil de l'utilisateur
                $sql = "SELECT * FROM user_profiles WHERE user_id = ?";
                $profile = fetchOne($sql, [$user_id]);
                
                if ($profile) {
                    // Calculer le BMR de base
                    $bmr = calculateBMR($profile['weight'], $profile['height'], $profile['birth_date'], $profile['gender']);
                    
                    // Calculer le TDEE
                    $tdee = calculateTDEE($bmr, $profile['activity_level']);
                    
                    // Ajuster les calories selon le programme
                    $adjusted_calories = $tdee * (1 + ($program['calorie_adjustment'] / 100));
                    
                    // Mettre à jour les objectifs de l'utilisateur
                    $sql = "UPDATE user_profiles SET 
                            daily_calories = ?,
                            protein_ratio = ?,
                            carbs_ratio = ?,
                            fat_ratio = ?,
                            updated_at = NOW()
                            WHERE user_id = ?";
                    update($sql, [
                        $adjusted_calories,
                        $program['protein_ratio'],
                        $program['carbs_ratio'],
                        $program['fat_ratio'],
                        $user_id
                    ]);
                    
                    $success_message = "Le programme a été activé avec succès ! Vos objectifs ont été ajustés.";
                }
            } else {
                $errors[] = "Une erreur s'est produite lors de l'activation du programme.";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Une erreur s'est produite : " . $e->getMessage();
        error_log("Erreur dans programs.php: " . $e->getMessage());
    }
}

// Traitement de la désactivation d'un programme
if (isset($_GET['action']) && $_GET['action'] === 'deactivate' && isset($_GET['id'])) {
    try {
        $program_id = (int)$_GET['id'];
        
        // Vérifier si le programme appartient à l'utilisateur
        $sql = "SELECT * FROM user_programs WHERE program_id = ? AND user_id = ? AND status = 'actif'";
        $user_program = fetchOne($sql, [$program_id, $user_id]);
        
        if ($user_program) {
            // Désactiver le programme
            $sql = "UPDATE user_programs SET status = 'inactif', updated_at = NOW() 
                    WHERE program_id = ? AND user_id = ?";
            $result = update($sql, [$program_id, $user_id]);
            
            if ($result) {
                // Récupérer le profil de l'utilisateur
                $sql = "SELECT * FROM user_profiles WHERE user_id = ?";
                $profile = fetchOne($sql, [$user_id]);
                
                if ($profile) {
                    // Calculer le BMR de base
                    $bmr = calculateBMR($profile['weight'], $profile['height'], $profile['birth_date'], $profile['gender']);
                    
                    // Calculer le TDEE
                    $tdee = calculateTDEE($bmr, $profile['activity_level']);
                    
                    // Réinitialiser les objectifs aux valeurs par défaut
                    $sql = "UPDATE user_profiles SET 
                            daily_calories = ?,
                            protein_ratio = 0.3,
                            carbs_ratio = 0.4,
                            fat_ratio = 0.3,
                            updated_at = NOW()
                            WHERE user_id = ?";
                    update($sql, [$tdee, $user_id]);
                    
                    $success_message = "Le programme a été désactivé avec succès ! Vos objectifs ont été réinitialisés.";
                }
            } else {
                $errors[] = "Une erreur s'est produite lors de la désactivation du programme.";
            }
        } else {
            $errors[] = "Programme non trouvé ou vous n'êtes pas autorisé à le modifier.";
        }
    } catch (Exception $e) {
        $errors[] = "Une erreur s'est produite : " . $e->getMessage();
        error_log("Erreur dans programs.php: " . $e->getMessage());
    }
}

// Récupérer le programme actif de l'utilisateur
$sql = "SELECT p.* FROM user_programs up 
        JOIN programs p ON up.program_id = p.id 
        WHERE up.user_id = ? AND up.status = 'actif'";
$active_program = fetchOne($sql, [$user_id]);

// Récupérer la liste des programmes disponibles
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
    <title>Programmes - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
        <h1 class="mb-4">Programmes disponibles</h1>

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

        <?php if ($active_program): ?>
            <!-- Programme actif -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Votre programme actif</h5>
                </div>
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($active_program['name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($active_program['description']); ?></p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Type de programme</h6>
                            <?php
                            switch ($active_program['type']) {
                                case 'complet':
                                    echo '<span class="badge bg-primary">Complet (Nutrition + Exercice)</span>';
                                    break;
                                case 'nutrition':
                                    echo '<span class="badge bg-info">Nutrition uniquement</span>';
                                    break;
                                case 'exercice':
                                    echo '<span class="badge bg-warning">Exercice uniquement</span>';
                                    break;
                            }
                            ?>
                        </div>
                        <div class="col-md-6">
                            <h6>Objectifs ajustés</h6>
                            <ul class="list-unstyled">
                                <li>Ajustement calorique : <?php echo $active_program['calorie_adjustment']; ?>%</li>
                                <li>Protéines : <?php echo ($active_program['protein_ratio'] * 100); ?>%</li>
                                <li>Glucides : <?php echo ($active_program['carbs_ratio'] * 100); ?>%</li>
                                <li>Lipides : <?php echo ($active_program['fat_ratio'] * 100); ?>%</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Contenu du programme</h6>
                        <div class="program-content">
                            <?php echo nl2br(htmlspecialchars($active_program['content'])); ?>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="programs.php?action=deactivate&id=<?php echo $active_program['id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Êtes-vous sûr de vouloir quitter ce programme ? Vos objectifs seront réinitialisés.');">
                            <i class="fas fa-times me-1"></i>Quitter le programme
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Liste des programmes disponibles -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Programmes disponibles</h5>
            </div>
            <div class="card-body">
                <?php if (empty($programs)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i>
                        Aucun programme disponible pour le moment.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($programs as $program): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($program['name']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($program['description']); ?></p>
                                        
                                        <div class="mb-3">
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
                                        </div>
                                        
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-users me-1"></i><?php echo $program['active_users']; ?> utilisateurs actifs</li>
                                            <li><i class="fas fa-fire me-1"></i>Ajustement calorique : <?php echo $program['calorie_adjustment']; ?>%</li>
                                            <li><i class="fas fa-drumstick-bite me-1"></i>Protéines : <?php echo ($program['protein_ratio'] * 100); ?>%</li>
                                            <li><i class="fas fa-bread-slice me-1"></i>Glucides : <?php echo ($program['carbs_ratio'] * 100); ?>%</li>
                                            <li><i class="fas fa-cheese me-1"></i>Lipides : <?php echo ($program['fat_ratio'] * 100); ?>%</li>
                                        </ul>
                                        
                                        <button type="button" 
                                                class="btn btn-info btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewProgram<?php echo $program['id']; ?>">
                                            <i class="fas fa-eye me-1"></i>Voir le détail
                                        </button>
                                        
                                        <?php if (!$active_program): ?>
                                            <form method="post" action="" class="d-inline">
                                                <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check me-1"></i>Choisir ce programme
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modals pour voir les détails des programmes -->
    <?php foreach ($programs as $program): ?>
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
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
