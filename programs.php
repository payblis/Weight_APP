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
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Vérifier et mettre à jour la structure de la table si nécessaire
$sql = "SHOW COLUMNS FROM programs LIKE 'calorie_adjustment'";
$result = fetchOne($sql, []);

if (!$result) {
    // Ajouter les colonnes manquantes
    $sql = "ALTER TABLE programs 
            ADD COLUMN calorie_adjustment FLOAT DEFAULT 0 AFTER description,
            ADD COLUMN protein_ratio FLOAT DEFAULT 0.3 AFTER calorie_adjustment,
            ADD COLUMN carbs_ratio FLOAT DEFAULT 0.4 AFTER protein_ratio,
            ADD COLUMN fat_ratio FLOAT DEFAULT 0.3 AFTER carbs_ratio";
    execute($sql);
}

// Gérer l'activation/désactivation des programmes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    error_log("=== DÉBUT DU DÉBOGAGE DÉTAILLÉ ===");
    error_log("Session ID : " . session_id());
    error_log("User ID from session : " . $_SESSION['user_id']);
    error_log("POST data : " . print_r($_POST, true));
    error_log("GET data : " . print_r($_GET, true));
    
    $program_id = (int)$_POST['program_id'];
    $user_id = $_SESSION['user_id'];
    
    error_log("Program ID converti : " . $program_id);
    error_log("User ID utilisé : " . $user_id);
    
    try {
        $pdo->beginTransaction();
        
        if ($_POST['action'] === 'activate') {
            error_log("=== DÉBOGAGE ACTIVATION ===");
            error_log("Tentative d'activation du programme ID : " . $program_id);
            
            // 1. Désactiver TOUS les programmes existants pour l'user
            $sql = "UPDATE user_programs SET status = 'inactif' WHERE user_id = ?";
            error_log("SQL de désactivation initiale : " . $sql);
            error_log("Paramètres : user_id=" . $user_id);
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$user_id]);
            error_log("Résultat de la désactivation initiale : " . ($result ? "Succès" : "Échec"));
            
            // 2. Insérer le NOUVEAU programme actif
            $sql = "INSERT INTO user_programs (user_id, program_id, status, created_at, updated_at) VALUES (?, ?, 'actif', NOW(), NOW())";
            error_log("SQL d'insertion : " . $sql);
            error_log("Paramètres : user_id=" . $user_id . ", program_id=" . $program_id);
            error_log("Classe de pdo dans insert : " . get_class($pdo));
            error_log("Hash de l'objet PDO dans insert : " . spl_object_hash($pdo));
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$user_id, $program_id]);
            error_log("Résultat de l'insertion : " . ($result ? "Succès" : "Échec"));
            
            if ($result) {
                // Récupérer l'ID du programme inséré
                $insertedId = $pdo->lastInsertId();
                error_log("Programme inséré avec ID : " . $insertedId);
                
                // 3. Vérification avec la même connexion PDO
                $stmt = $pdo->prepare("SELECT * FROM user_programs WHERE id = ?");
                $stmt->execute([$insertedId]);
                $check = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Check direct dans même PDO : " . print_r($check, true));
                
                // Récupérer les valeurs du programme
                $sql = "SELECT * FROM programs WHERE id = ?";
                $program = fetchOne($sql, [$program_id]);
                
                if ($program) {
                    error_log("Valeurs du programme récupérées : " . print_r($program, true));
                    
                    // Mettre à jour le profil utilisateur avec les valeurs du programme
                    $sql = "UPDATE user_profiles SET 
                            daily_calories = ?,
                            protein_ratio = ?,
                            carbs_ratio = ?,
                            fat_ratio = ?
                            WHERE user_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $update_result = $stmt->execute([
                        $program['daily_calories'],
                        $program['protein_ratio'],
                        $program['carbs_ratio'],
                        $program['fat_ratio'],
                        $user_id
                    ]);
                    error_log("Mise à jour du profil utilisateur avec les valeurs : daily_calories=" . $program['daily_calories'] . 
                             ", protein_ratio=" . $program['protein_ratio'] . 
                             ", carbs_ratio=" . $program['carbs_ratio'] . 
                             ", fat_ratio=" . $program['fat_ratio']);
                    error_log("Résultat de la mise à jour : " . ($update_result ? "Succès" : "Échec"));
                    
                    // 4. Recalcul calories
                    error_log("Programme activé avec succès, appel de recalculateCalories");
                    if (recalculateCalories($user_id)) {
                        error_log("Recalcul des calories réussi");
                    } else {
                        error_log("Échec du recalcul des calories");
                    }
                } else {
                    error_log("Programme non trouvé avec l'ID : " . $program_id);
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Programme activé avec succès.";
            } else {
                error_log("Échec de l'activation du programme");
                $pdo->rollBack();
                $_SESSION['error'] = "Erreur lors de l'activation du programme.";
            }
        } elseif ($_POST['action'] === 'deactivate') {
            error_log("=== DÉBOGAGE DÉSACTIVATION ===");
            error_log("Tentative de désactivation du programme ID : " . $program_id);
            
            // Vérifier le programme avant désactivation
            $sql = "SELECT * FROM user_programs WHERE user_id = ? AND program_id = ?";
            $program_to_deactivate = fetchOne($sql, [$user_id, $program_id]);
            error_log("Programme à désactiver : " . print_r($program_to_deactivate, true));
            
            // Désactiver le programme
            $sql = "UPDATE user_programs SET status = 'inactif' WHERE user_id = ? AND program_id = ?";
            error_log("SQL de désactivation : " . $sql);
            error_log("Paramètres : user_id=" . $user_id . ", program_id=" . $program_id);
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$user_id, $program_id]);
            error_log("Résultat de la désactivation : " . ($result ? "Succès" : "Échec"));
            
            if ($result) {
                // Vérifier le programme après désactivation
                $sql = "SELECT * FROM user_programs WHERE user_id = ? AND program_id = ?";
                $program_after_deactivation = fetchOne($sql, [$user_id, $program_id]);
                error_log("Programme après désactivation : " . print_r($program_after_deactivation, true));
                
                error_log("Programme désactivé avec succès, appel de recalculateCalories");
                // Recalculer les calories et les ratios
                if (recalculateCalories($user_id)) {
                    error_log("Recalcul des calories réussi");
                } else {
                    error_log("Échec du recalcul des calories");
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Programme désactivé avec succès.";
            } else {
                error_log("Échec de la désactivation du programme");
                $pdo->rollBack();
                $_SESSION['error'] = "Erreur lors de la désactivation du programme.";
            }
        }
        
        error_log("=== Fin de la gestion de l'action POST ===");
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la gestion du programme : " . $e->getMessage());
        $pdo->rollBack();
        $_SESSION['error'] = "Une erreur est survenue lors de la gestion du programme.";
    }
    
    // Rediriger vers la page des programmes
    header("Location: programs.php");
    exit;
}

// Suppression d'un programme
if ($action === 'delete' && isset($_GET['id'])) {
    $program_id = (int)$_GET['id'];
    
    // Vérifier que le programme appartient à l'utilisateur
    $sql = "SELECT * FROM programs WHERE id = ? AND user_id = ?";
    $program = fetchOne($sql, [$program_id, $user_id]);
    
    if ($program) {
        // Supprimer le programme
        $sql = "DELETE FROM programs WHERE id = ? AND user_id = ?";
        if (execute($sql, [$program_id, $user_id])) {
            // Attendre un court instant pour s'assurer que la suppression est effectuée
            usleep(100000); // 100ms
            
            // Recalculer les calories
            if (recalculateCalories($user_id)) {
                $_SESSION['success'] = "Programme supprimé avec succès.";
            } else {
                $_SESSION['error'] = "Programme supprimé mais erreur lors du recalcul des calories.";
            }
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression du programme.";
        }
    } else {
        $_SESSION['error'] = "Programme non trouvé.";
    }
    
    redirect('programs.php');
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

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
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
                    <p class="text-muted"><?php echo htmlspecialchars($active_program['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    
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
                            <?php echo isset($active_program['content']) ? nl2br(htmlspecialchars($active_program['content'])) : 'Aucun contenu disponible.'; ?>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <form method="post" action="" class="d-inline">
                            <input type="hidden" name="action" value="deactivate">
                            <input type="hidden" name="program_id" value="<?php echo $active_program['id']; ?>">
                            <button type="submit" 
                                    class="btn btn-danger" 
                                    onclick="return confirm('Êtes-vous sûr de vouloir quitter ce programme ? Vos objectifs seront réinitialisés.');">
                                <i class="fas fa-times me-1"></i>Quitter le programme
                            </button>
                        </form>
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
                                        <p class="card-text text-muted description-preview">
                                            <?php 
                                            $description = htmlspecialchars($program['description'], ENT_QUOTES, 'UTF-8');
                                            $short_desc = substr($description, 0, 150);
                                            echo nl2br($short_desc);
                                            if (strlen($description) > 150) {
                                                echo '...';
                                            }
                                            ?>
                                        </p>
                                        <?php if (strlen($description) > 150): ?>
                                            <button class="btn btn-link btn-sm p-0 show-more-btn" data-program-id="<?php echo $program['id']; ?>">
                                                Voir plus
                                            </button>
                                        <?php endif; ?>
                                        
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
                                        
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary share-program-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#shareProgramModal"
                                                    data-program-id="<?php echo $program['id']; ?>"
                                                    data-program-name="<?php echo htmlspecialchars($program['name']); ?>"
                                                    data-program-description="<?php echo htmlspecialchars($program['description'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-program-type="<?php echo $program['type']; ?>"
                                                    data-program-calories="<?php echo $program['calorie_adjustment']; ?>"
                                                    onclick="console.log('Partage de programme:', this.dataset);">
                                                <i class="fas fa-share-alt me-1"></i>Partager
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewProgram<?php echo $program['id']; ?>">
                                                <i class="fas fa-eye me-1"></i>Voir le détail
                                            </button>
                                            <?php if (!$active_program): ?>
                                                <form method="post" action="" class="d-inline">
                                                    <input type="hidden" name="action" value="activate">
                                                    <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check me-1"></i>Choisir ce programme
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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
                        <p><?php echo nl2br(htmlspecialchars($program['description'], ENT_QUOTES, 'UTF-8')); ?></p>
                        
                        <h6>Contenu du programme</h6>
                        <div class="program-content">
                            <?php echo isset($program['content']) ? nl2br(htmlspecialchars($program['content'])) : 'Aucun contenu disponible.'; ?>
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

    <!-- Modal de partage de programme -->
    <div class="modal fade" id="shareProgramModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Partager ce programme</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="create-post.php" method="POST">
                        <input type="hidden" name="post_type" value="program">
                        <input type="hidden" name="reference_id" id="share_program_id">
                        <div class="mb-3">
                            <label for="share_visibility" class="form-label">Visibilité</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="visibility" id="visibility_public" value="public" checked>
                                <label class="form-check-label" for="visibility_public">
                                    <i class="fas fa-globe me-1"></i>Public
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="visibility" id="visibility_group" value="group">
                                <label class="form-check-label" for="visibility_group">
                                    <i class="fas fa-users me-1"></i>Groupe
                                </label>
                            </div>
                            <div id="group_select" class="mt-2" style="display: none;">
                                <select class="form-select" name="group_id">
                                    <option value="">Sélectionnez un groupe</option>
                                    <?php foreach ($user_groups as $group): ?>
                                        <option value="<?php echo $group['id']; ?>">
                                            <?php echo htmlspecialchars($group['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="share_content" class="form-label">Message (optionnel)</label>
                            <textarea class="form-control" id="share_content" name="content" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Partager
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page chargée, recherche des boutons de partage...');
        
        // Gestion de la visibilité du post
        const visibilityInputs = document.querySelectorAll('input[name="visibility"]');
        const groupSelect = document.getElementById('group_select');
        
        visibilityInputs.forEach(input => {
            input.addEventListener('change', function() {
                groupSelect.style.display = this.value === 'group' ? 'block' : 'none';
            });
        });

        // Gestion du bouton de partage
        const shareButtons = document.querySelectorAll('.share-program-btn');
        console.log('Nombre de boutons de partage trouvés:', shareButtons.length);
        
        shareButtons.forEach(btn => {
            console.log('Configuration du bouton:', btn.dataset);
            btn.addEventListener('click', function() {
                console.log('Clic sur le bouton de partage');
                const programId = this.dataset.programId;
                const programName = this.dataset.programName;
                const programDescription = this.dataset.programDescription;
                const programType = this.dataset.programType;
                const programCalories = this.dataset.programCalories;
                
                console.log('Données du programme:', {
                    programId,
                    programName,
                    programDescription,
                    programType,
                    programCalories
                });
                
                // Mettre à jour les champs du formulaire
                document.getElementById('share_program_id').value = programId;
                
                // Pré-remplir le message
                const content = `Je viens de rejoindre le programme "${programName}"\n` +
                              `Type: ${programType}\n` +
                              `Ajustement calorique: ${programCalories}%\n` +
                              `Description: ${programDescription}`;
                document.getElementById('share_content').value = content;
                
                console.log('Formulaire mis à jour avec:', {
                    programId,
                    content
                });
            });
        });

        // Gestion des boutons "Voir plus"
        document.querySelectorAll('.show-more-btn').forEach(button => {
            button.addEventListener('click', function() {
                const programId = this.dataset.programId;
                const description = <?php echo json_encode($programs); ?>[programId - 1].description;
                const previewElement = this.previousElementSibling;
                
                if (this.textContent === 'Voir plus') {
                    previewElement.innerHTML = description.replace(/\n/g, '<br>');
                    this.textContent = 'Voir moins';
                } else {
                    const shortDesc = description.substring(0, 150) + '...';
                    previewElement.innerHTML = shortDesc.replace(/\n/g, '<br>');
                    this.textContent = 'Voir plus';
                }
            });
        });
    });
    </script>
</body>
</html>
