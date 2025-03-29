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
$target_weight = '';
$target_date = date('Y-m-d', strtotime('+30 days'));
$notes = '';
$success_message = '';
$errors = [];

// Récupérer le dernier poids enregistré
$sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC, created_at DESC LIMIT 1";
$latest_weight = fetchOne($sql, [$user_id]);

// Récupérer l'objectif de poids actuel
$sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);

// Si un objectif existe, initialiser les variables avec ses valeurs
if ($current_goal) {
    $target_weight = $current_goal['target_weight'];
    $target_date = $current_goal['target_date'];
    $notes = $current_goal['notes'];
}

// Traitement du formulaire d'ajout/modification d'objectif
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_goal') {
    // Récupérer et nettoyer les données du formulaire
    $target_weight = sanitizeInput($_POST['target_weight'] ?? '');
    $target_date = sanitizeInput($_POST['target_date'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Validation des données
    if (empty($target_weight)) {
        $errors[] = "Le poids cible est requis";
    } elseif (!is_numeric($target_weight) || $target_weight <= 0) {
        $errors[] = "Le poids cible doit être un nombre positif";
    }
    
    if (empty($target_date)) {
        $errors[] = "La date cible est requise";
    } elseif (!validateDate($target_date)) {
        $errors[] = "La date cible n'est pas valide";
    } elseif (strtotime($target_date) <= time()) {
        $errors[] = "La date cible doit être dans le futur";
    }
    
    // Si aucune erreur, ajouter/modifier l'objectif
    if (empty($errors)) {
        // Si un objectif existe déjà, le marquer comme terminé
        if ($current_goal) {
            $sql = "UPDATE goals SET status = 'termine', updated_at = NOW() WHERE id = ?";
            update($sql, [$current_goal['id']]);
        }
        
        // Stocker l'objectif en session pour confirmation
        $_SESSION['pending_goal'] = [
            'target_weight' => $target_weight,
            'target_date' => $target_date,
            'notes' => $notes
        ];
        
        // Rediriger vers la page de confirmation
        redirect('confirm-goal.php');
    }
}

// Traitement de l'annulation d'un objectif
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $goal_id = (int)$_GET['id'];
    
    // Vérifier si l'objectif appartient à l'utilisateur
    $sql = "SELECT * FROM goals WHERE id = ? AND user_id = ?";
    $goal = fetchOne($sql, [$goal_id, $user_id]);
    
    if ($goal) {
        // Marquer l'objectif comme annulé
        $sql = "UPDATE goals SET status = 'annule', updated_at = NOW() WHERE id = ?";
        $result = update($sql, [$goal_id]);
        
        if ($result) {
            // Vérifier et mettre à jour le poids dans le profil
            $current_weight = ensureProfileWeight($user_id);
            
            if ($current_weight === null) {
                $errors[] = "Veuillez d'abord enregistrer votre poids avant de réinitialiser les calories.";
            } else {
                // Recalculer les calories
                recalculateCalories($user_id);
            }
            
            $success_message = "L'objectif a été annulé avec succès !";
            
            // Réinitialiser les variables
            $target_weight = '';
            $target_date = date('Y-m-d', strtotime('+30 days'));
            $notes = '';
            $current_goal = null;
        } else {
            $errors[] = "Une erreur s'est produite lors de l'annulation de l'objectif. Veuillez réessayer.";
        }
    } else {
        $errors[] = "Objectif non trouvé ou vous n'êtes pas autorisé à le modifier.";
    }
}

// Suppression d'un objectif
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $goal_id = (int)$_GET['id'];
    
    // Vérifier que l'objectif appartient à l'utilisateur
    $sql = "SELECT * FROM goals WHERE id = ? AND user_id = ?";
    $goal = fetchOne($sql, [$goal_id, $user_id]);
    
    if ($goal) {
        // Supprimer l'objectif
        $sql = "DELETE FROM goals WHERE id = ? AND user_id = ?";
        if (execute($sql, [$goal_id, $user_id])) {
            // Attendre un court instant pour s'assurer que la suppression est effectuée
            usleep(100000); // 100ms
            
            // Recalculer les calories
            if (recalculateCalories($user_id)) {
                $_SESSION['success'] = "Objectif supprimé avec succès.";
            } else {
                $_SESSION['error'] = "Objectif supprimé mais erreur lors du recalcul des calories.";
            }
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression de l'objectif.";
        }
    } else {
        $_SESSION['error'] = "Objectif non trouvé.";
    }
    
    redirect('goals.php');
}

// Récupérer l'historique des objectifs
$sql = "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y') as formatted_created_date, 
               DATE_FORMAT(target_date, '%d/%m/%Y') as formatted_target_date
        FROM goals 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10";
$goal_history = fetchAll($sql, [$user_id]);

// Calculer les statistiques pour l'objectif actuel
$progress = 0;
$remaining_days = 0;
$daily_loss_needed = 0;
$completion_percentage = 0;

if ($current_goal && $latest_weight) {
    // Debug: Afficher les informations de base
    error_log("=== Début du calcul de la progression ===");
    error_log("Poids le plus récent: " . $latest_weight['weight']);
    error_log("Poids cible: " . $current_goal['target_weight']);
    error_log("Date de création de l'objectif: " . $current_goal['created_at']);
    
    // Récupérer le poids initial (le plus ancien poids enregistré depuis la création de l'objectif)
    $sql = "SELECT weight FROM weight_logs WHERE user_id = ? AND log_date >= ? ORDER BY log_date ASC LIMIT 1";
    $start_weight_log = fetchOne($sql, [$user_id, $current_goal['created_at']]);
    $start_weight = $start_weight_log ? $start_weight_log['weight'] : $latest_weight['weight'];
    
    error_log("Poids initial: " . $start_weight);
    
    $weight_diff = $start_weight - $current_goal['target_weight'];
    $total_days = (strtotime($current_goal['target_date']) - strtotime($current_goal['created_at'])) / (60 * 60 * 24);
    $elapsed_days = (time() - strtotime($current_goal['created_at'])) / (60 * 60 * 24);
    $remaining_days = max(0, $total_days - $elapsed_days);
    
    error_log("Différence de poids à perdre: " . $weight_diff);
    error_log("Jours totaux: " . $total_days);
    error_log("Jours écoulés: " . $elapsed_days);
    error_log("Jours restants: " . $remaining_days);
    
    // Si l'objectif est de perdre du poids
    if ($weight_diff > 0) {
        error_log("Objectif: Perte de poids");
        // Récupérer le poids le plus récent
        $sql = "SELECT * FROM weight_logs WHERE user_id = ? AND log_date >= ? ORDER BY log_date DESC LIMIT 1";
        $current_weight_log = fetchOne($sql, [$user_id, $current_goal['created_at']]);
        
        if ($current_weight_log) {
            $current_weight = $current_weight_log['weight'];
            $actual_loss = $start_weight - $current_weight;
            $progress = ($actual_loss / $weight_diff) * 100;
            $completion_percentage = min(100, max(0, $progress));
            
            error_log("Poids actuel: " . $current_weight);
            error_log("Perte réelle: " . $actual_loss);
            error_log("Progression calculée: " . $progress);
            error_log("Pourcentage final: " . $completion_percentage);
            
            if ($remaining_days > 0) {
                $remaining_weight = $current_weight - $current_goal['target_weight'];
                $daily_loss_needed = $remaining_weight / $remaining_days;
                error_log("Poids restant à perdre: " . $remaining_weight);
                error_log("Perte quotidienne nécessaire: " . $daily_loss_needed);
            }
        } else {
            error_log("Aucun poids trouvé depuis la création de l'objectif");
        }
    }
    // Si l'objectif est de prendre du poids
    elseif ($weight_diff < 0) {
        error_log("Objectif: Prise de poids");
        $weight_diff = abs($weight_diff);
        
        // Récupérer le poids le plus récent
        $sql = "SELECT * FROM weight_logs WHERE user_id = ? AND log_date >= ? ORDER BY log_date DESC LIMIT 1";
        $current_weight_log = fetchOne($sql, [$user_id, $current_goal['created_at']]);
        
        if ($current_weight_log) {
            $current_weight = $current_weight_log['weight'];
            $actual_gain = $current_weight - $start_weight;
            $progress = ($actual_gain / $weight_diff) * 100;
            $completion_percentage = min(100, max(0, $progress));
            
            error_log("Poids actuel: " . $current_weight);
            error_log("Gain réel: " . $actual_gain);
            error_log("Progression calculée: " . $progress);
            error_log("Pourcentage final: " . $completion_percentage);
            
            if ($remaining_days > 0) {
                $remaining_weight = $current_goal['target_weight'] - $current_weight;
                $daily_loss_needed = $remaining_weight / $remaining_days; // En réalité, c'est un gain quotidien
                error_log("Poids restant à prendre: " . $remaining_weight);
                error_log("Gain quotidien nécessaire: " . $daily_loss_needed);
            }
        } else {
            error_log("Aucun poids trouvé depuis la création de l'objectif");
        }
    }
    error_log("=== Fin du calcul de la progression ===");
}

// Récupérer les programmes disponibles
$sql = "SELECT * FROM programs ORDER BY name";
$available_programs = fetchAll($sql);

// Récupérer le programme actif de l'utilisateur
$sql = "SELECT p.*, up.id as user_program_id, DATE_FORMAT(up.created_at, '%d/%m/%Y') as formatted_start_date 
        FROM user_programs up 
        JOIN programs p ON up.program_id = p.id 
        WHERE up.user_id = ? AND up.status = 'actif' 
        ORDER BY up.created_at DESC LIMIT 1";
$active_program = fetchOne($sql, [$user_id]);

// Traitement de l'inscription à un programme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_program') {
    $program_id = (int)($_POST['program_id'] ?? 0);
    
    if ($program_id <= 0) {
        $errors[] = "Veuillez sélectionner un programme valide";
    } else {
        // Vérifier si le programme existe
        $sql = "SELECT * FROM programs WHERE id = ?";
        $program = fetchOne($sql, [$program_id]);
        
        if (!$program) {
            $errors[] = "Le programme sélectionné n'existe pas";
        } else {
            // Si un programme est déjà actif, le désactiver
            if ($active_program) {
                $sql = "UPDATE user_programs SET status = 'termine', updated_at = NOW() WHERE id = ?";
                update($sql, [$active_program['user_program_id']]);
            }
            
            // Inscrire l'utilisateur au nouveau programme
            $sql = "INSERT INTO user_programs (user_id, program_id, status, created_at) 
                    VALUES (?, ?, 'actif', NOW())";
            $result = insert($sql, [$user_id, $program_id]);
            
            if ($result) {
                // Recalculer les calories
                recalculateCalories($user_id);
                
                $success_message = "Vous avez rejoint le programme " . htmlspecialchars($program['name']) . " avec succès !";
                
                // Récupérer le programme actif mis à jour
                $sql = "SELECT p.*, up.id as user_program_id, DATE_FORMAT(up.created_at, '%d/%m/%Y') as formatted_start_date 
                        FROM user_programs up 
                        JOIN programs p ON up.program_id = p.id 
                        WHERE up.id = ?";
                $active_program = fetchOne($sql, [$result]);
            } else {
                $errors[] = "Une erreur s'est produite lors de l'inscription au programme. Veuillez réessayer.";
            }
        }
    }
}

// Traitement de l'abandon d'un programme
if (isset($_GET['action']) && $_GET['action'] === 'leave_program' && isset($_GET['id'])) {
    $user_program_id = (int)$_GET['id'];
    
    // Vérifier si le programme appartient à l'utilisateur
    $sql = "SELECT * FROM user_programs WHERE id = ? AND user_id = ?";
    $user_program = fetchOne($sql, [$user_program_id, $user_id]);
    
    if ($user_program) {
        // Marquer le programme comme abandonné
        $sql = "UPDATE user_programs SET status = 'abandonne', updated_at = NOW() WHERE id = ?";
        $result = update($sql, [$user_program_id]);
        
        if ($result) {
            // Recalculer les calories
            recalculateCalories($user_id);
            
            $success_message = "Vous avez quitté le programme avec succès !";
            $active_program = null;
        } else {
            $errors[] = "Une erreur s'est produite lors de l'abandon du programme. Veuillez réessayer.";
        }
    } else {
        $errors[] = "Programme non trouvé ou vous n'êtes pas autorisé à le modifier.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Objectifs de poids - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-4">Objectifs de poids et programmes</h1>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="weight-log.php" class="btn btn-primary">
                    <i class="fas fa-weight me-1"></i>Journal de poids
                </a>
                <a href="calorie-history.php" class="btn btn-success">
                    <i class="fas fa-chart-line me-1"></i>Historique
                </a>
            </div>
        </div>

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

        <div class="row">
            <!-- Objectif de poids actuel -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Objectif de poids actuel</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($current_goal): ?>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h4 class="mb-0"><?php echo number_format($current_goal['target_weight'], 1); ?> kg</h4>
                                        <p class="text-muted mb-0">Objectif à atteindre avant le <?php echo date('d/m/Y', strtotime($current_goal['target_date'])); ?></p>
                                    </div>
                                    <a href="goals.php?action=cancel&id=<?php echo $current_goal['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler cet objectif ?');">
                                        <i class="fas fa-times me-1"></i>Annuler
                                    </a>
                                </div>
                                
                                <?php if ($latest_weight): ?>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_percentage; ?>%;" aria-valuenow="<?php echo $completion_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?php echo number_format($completion_percentage, 1); ?>%
                                        </div>
                                    </div>
                                    <p class="text-muted small">
                                        <?php 
                                        if ($current_goal['target_weight'] < $latest_weight['weight']) {
                                            echo 'Perte de poids: ';
                                            echo number_format($progress, 1) . '% complété, ' . number_format(abs($latest_weight['weight'] - $current_goal['target_weight']), 1) . ' kg restants';
                                        } elseif ($current_goal['target_weight'] > $latest_weight['weight']) {
                                            echo 'Prise de poids: ';
                                            echo number_format($progress, 1) . '% complété, ' . number_format(abs($current_goal['target_weight'] - $latest_weight['weight']), 1) . ' kg restants';
                                        } else {
                                            echo 'Objectif atteint !';
                                        }
                                        ?>
                                    </p>
                                    
                                    <?php if ($remaining_days > 0 && $daily_loss_needed != 0): ?>
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <?php 
                                            if ($daily_loss_needed > 0) {
                                                echo 'Pour atteindre votre objectif, vous devez prendre environ ' . number_format($daily_loss_needed, 2) . ' kg par jour.';
                                            } else {
                                                echo 'Pour atteindre votre objectif, vous devez perdre environ ' . number_format(abs($daily_loss_needed), 2) . ' kg par jour.';
                                            }
                                            ?>
                                            <br>
                                            Il vous reste <?php echo ceil($remaining_days); ?> jours.
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($current_goal['notes'])): ?>
                                        <div class="mt-3">
                                            <h6>Notes:</h6>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($current_goal['notes'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Vous n'avez pas encore enregistré votre poids. Veuillez le faire pour suivre votre progression.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Vous n'avez pas encore défini d'objectif de poids. Utilisez le formulaire ci-dessous pour en créer un.
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="goals.php">
                            <input type="hidden" name="action" value="update_goal">
                            
                            <div class="mb-3">
                                <label for="target_weight" class="form-label">Poids cible (kg)</label>
                                <input type="number" class="form-control" id="target_weight" name="target_weight" value="<?php echo htmlspecialchars($target_weight); ?>" min="30" max="300" step="0.1" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="target_date" class="form-label">Date cible</label>
                                <input type="date" class="form-control" id="target_date" name="target_date" value="<?php echo htmlspecialchars($target_date); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes (optionnel)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $current_goal ? 'Mettre à jour l\'objectif' : 'Créer un objectif'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Programmes -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Programmes</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($active_program): ?>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h4 class="mb-0"><?php echo htmlspecialchars($active_program['name']); ?></h4>
                                        <p class="text-muted mb-0">Commencé le <?php echo $active_program['formatted_start_date']; ?></p>
                                    </div>
                                    <a href="goals.php?action=leave_program&id=<?php echo $active_program['user_program_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir quitter ce programme ?');">
                                        <i class="fas fa-times me-1"></i>Quitter
                                    </a>
                                </div>
                                
                                <div class="alert alert-success">
                                    <div class="row">
                                        <div class="col-md-6 text-center">
                                            <h5 class="mb-0"><?php echo number_format($active_program['protein_ratio'], 1); ?>%</h5>
                                            <p class="small mb-0">protéines</p>
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <h5 class="mb-0"><?php echo number_format($active_program['carbs_ratio'], 1); ?>%</h5>
                                            <p class="small mb-0">glucides</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <p><?php echo nl2br(htmlspecialchars($active_program['description'])); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Vous n'avez pas encore rejoint de programme. Choisissez-en un ci-dessous pour commencer.
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="goals.php">
                            <input type="hidden" name="action" value="join_program">
                            
                            <div class="mb-3">
                                <label for="program_id" class="form-label">Choisir un programme</label>
                                <select class="form-select" id="program_id" name="program_id" required>
                                    <option value="">-- Sélectionner un programme --</option>
                                    <?php foreach ($available_programs as $program): ?>
                                        <option value="<?php echo $program['id']; ?>">
                                            <?php echo htmlspecialchars($program['name']); ?> 
                                            (<?php echo number_format($program['daily_calories']); ?> cal/jour)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">
                                    <?php echo $active_program ? 'Changer de programme' : 'Rejoindre ce programme'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historique des objectifs -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Historique des objectifs</h5>
            </div>
            <div class="card-body">
                <?php if (empty($goal_history)): ?>
                    <div class="alert alert-info">
                        Vous n'avez pas encore d'historique d'objectifs.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date de création</th>
                                    <th>Poids cible</th>
                                    <th>Date cible</th>
                                    <th>Statut</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($goal_history as $goal): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($goal['formatted_created_date']); ?></td>
                                        <td><?php echo number_format($goal['target_weight'], 1); ?> kg</td>
                                        <td><?php echo htmlspecialchars($goal['formatted_target_date']); ?></td>
                                        <td>
                                            <?php 
                                            switch ($goal['status']) {
                                                case 'en_cours':
                                                    echo '<span class="badge bg-primary">En cours</span>';
                                                    break;
                                                case 'termine':
                                                    echo '<span class="badge bg-success">Terminé</span>';
                                                    break;
                                                case 'annule':
                                                    echo '<span class="badge bg-danger">Annulé</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-secondary">Inconnu</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo !empty($goal['notes']) ? htmlspecialchars(substr($goal['notes'], 0, 50)) . (strlen($goal['notes']) > 50 ? '...' : '') : '—'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
