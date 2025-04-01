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

// Traitement du formulaire d'ajout/modification d'exercice
$exercise_id = '';
$duration = '';
$intensity = 'moderee';
$calories_burned = '';
$log_date = date('Y-m-d');
$notes = '';
$custom_exercise_name = '';
$success_message = '';
$errors = [];

// Vérifier s'il s'agit d'une modification
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($edit_id > 0) {
    // Récupérer l'entrée à modifier
    $sql = "SELECT * FROM exercise_logs WHERE id = ? AND user_id = ?";
    $exercise_log = fetchOne($sql, [$edit_id, $user_id]);
    
    if ($exercise_log) {
        $exercise_id = $exercise_log['exercise_id'];
        $duration = $exercise_log['duration'];
        $intensity = $exercise_log['intensity'];
        $calories_burned = $exercise_log['calories_burned'];
        $log_date = $exercise_log['log_date'];
        $notes = $exercise_log['notes'];
        $custom_exercise_name = $exercise_log['custom_exercise_name'];
    } else {
        redirect('exercise-log.php');
    }
}

// Récupérer les catégories d'exercices
$sql = "SELECT * FROM exercise_categories ORDER BY name";
$categories = fetchAll($sql, []);

// Récupérer les exercices
$sql = "SELECT * FROM exercises ORDER BY name";
$exercises = fetchAll($sql, []);

// Organiser les exercices par catégorie
$exercises_by_category = [];
foreach ($exercises as $exercise) {
    $category_id = $exercise['category_id'];
    if (!isset($exercises_by_category[$category_id])) {
        $exercises_by_category[$category_id] = [];
    }
    $exercises_by_category[$category_id][] = $exercise;
}

// Gérer la suppression d'un exercice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['exercise_id'])) {
        $exercise_id = (int)$_POST['exercise_id'];
        error_log("Tentative de suppression de l'exercice - Exercise ID: $exercise_id, User ID: $user_id");
        
        // Vérifier que l'exercice appartient bien à l'utilisateur
        $sql = "SELECT id FROM exercise_logs WHERE id = ? AND user_id = ?";
        $exercise = fetchOne($sql, [$exercise_id, $user_id]);
        
        if ($exercise) {
            $sql = "DELETE FROM exercise_logs WHERE id = ? AND user_id = ?";
            $result = update($sql, [$exercise_id, $user_id]);
            error_log("Résultat de la suppression de l'exercice: " . ($result ? "succès" : "échec"));
            if ($result) {
                $_SESSION['success_message'] = "Exercice supprimé avec succès.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la suppression de l'exercice.";
            }
        } else {
            error_log("Tentative de suppression d'un exercice non autorisé");
            $_SESSION['error_message'] = "Vous n'êtes pas autorisé à supprimer cet exercice.";
        }
        redirect('exercise-log.php');
    }
}

// Récupérer les exercices de l'utilisateur
$sql = "SELECT el.*, e.name as exercise_name,
        (SELECT COUNT(*) FROM community_posts WHERE reference_id = el.id AND post_type = 'exercise') as share_count
        FROM exercise_logs el
        LEFT JOIN exercises e ON el.exercise_id = e.id
        WHERE el.user_id = ? 
        ORDER BY el.log_date DESC, el.created_at DESC";
$exercises = fetchAll($sql, [$user_id]);
error_log("Nombre d'exercices récupérés: " . count($exercises));

// Récupérer les statistiques
$stats = getExerciseStats($user_id);
error_log("Statistiques des exercices: " . print_r($stats, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $exercise_id = isset($_POST['exercise_id']) ? (int)$_POST['exercise_id'] : 0;
    $duration = sanitizeInput($_POST['duration'] ?? '');
    $intensity = sanitizeInput($_POST['intensity'] ?? '');
    $calories_burned = sanitizeInput($_POST['calories_burned'] ?? '');
    $log_date = sanitizeInput($_POST['log_date'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $custom_exercise_name = sanitizeInput($_POST['custom_exercise_name'] ?? '');
    
    // Validation des données
    if ($exercise_id <= 0 && empty($custom_exercise_name)) {
        $errors[] = "Veuillez sélectionner un exercice ou saisir un nom d'exercice personnalisé";
    }
    
    if (empty($duration)) {
        $errors[] = "La durée est requise";
    } elseif (!is_numeric($duration) || $duration <= 0) {
        $errors[] = "La durée doit être un nombre positif";
    }
    
    if (empty($intensity)) {
        $errors[] = "L'intensité est requise";
    }
    
    if (empty($calories_burned)) {
        $errors[] = "Les calories brûlées sont requises";
    } elseif (!is_numeric($calories_burned) || $calories_burned < 0) {
        $errors[] = "Les calories brûlées doivent être un nombre positif ou zéro";
    }
    
    if (empty($log_date)) {
        $errors[] = "La date est requise";
    } elseif (!validateDate($log_date)) {
        $errors[] = "La date n'est pas valide";
    }
    
    // Si aucune erreur, enregistrer les données
    if (empty($errors)) {
        if ($edit_id > 0) {
            // Mise à jour d'une entrée existante
            $sql = "UPDATE exercise_logs SET exercise_id = ?, duration = ?, intensity = ?, calories_burned = ?, log_date = ?, notes = ?, custom_exercise_name = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
            $params = [$exercise_id ?: null, $duration, $intensity, $calories_burned, $log_date, $notes, $custom_exercise_name, $edit_id, $user_id];
            
            $result = update($sql, $params);
            
            if ($result) {
                $success_message = "L'entrée d'exercice a été mise à jour avec succès !";
            } else {
                $errors[] = "Une erreur s'est produite lors de la mise à jour de l'entrée. Veuillez réessayer.";
            }
        } else {
            // Création d'une nouvelle entrée
            $sql = "INSERT INTO exercise_logs (user_id, exercise_id, duration, intensity, calories_burned, log_date, notes, custom_exercise_name, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $params = [$user_id, $exercise_id ?: null, $duration, $intensity, $calories_burned, $log_date, $notes, $custom_exercise_name];
            
            $result = insert($sql, $params);
            
            if ($result) {
                $success_message = "L'entrée d'exercice a été enregistrée avec succès !";
                $exercise_id = '';
                $duration = '';
                $intensity = 'moderee';
                $calories_burned = '';
                $log_date = date('Y-m-d');
                $notes = '';
                $custom_exercise_name = '';
            } else {
                $errors[] = "Une erreur s'est produite lors de l'enregistrement de l'entrée. Veuillez réessayer.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal d'exercices - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Optimisations mobiles */
        @media (max-width: 768px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .display-4 {
                font-size: 2rem;
            }
            
            .table-responsive {
                margin: 0 -15px;
            }
            
            .table td, .table th {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
            
            .btn-group {
                display: flex;
                gap: 0.5rem;
            }
            
            .btn-group .btn {
                padding: 0.25rem 0.5rem;
            }
            
            .modal-dialog {
                margin: 0.5rem;
            }
            
            .form-label {
                font-size: 0.9rem;
            }
            
            .form-control, .form-select {
                font-size: 0.9rem;
            }
        }
        
        /* Améliorations générales */
        .card {
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .btn-group .btn {
            border-radius: 0.25rem;
        }
        
        .exercise-details p {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Barre de navigation -->
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <!-- En-tête de la page -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-0">Journal d'exercices</h1>
                <p class="text-muted">Suivez vos activités physiques et calories brûlées</p>
            </div>
            <div class="col-md-4 text-md-end">
                <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || isset($_GET['edit'])): ?>
                    <a href="exercise-log.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                    </a>
                <?php else: ?>
                    <a href="exercise-log.php?action=add" class="btn btn-primary me-2" id="addExerciseBtn">
                        <i class="fas fa-plus-circle me-1"></i>Ajouter un exercice
                    </a>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="addEntryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-plus me-1"></i>Plus
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="addEntryDropdown">
                            <li><a class="dropdown-item" href="weight-log.php?action=add"><i class="fas fa-weight me-1"></i>Ajouter un poids</a></li>
                            <li><a class="dropdown-item" href="food-log.php?action=add"><i class="fas fa-utensils me-1"></i>Ajouter un repas</a></li>
                            <li><a class="dropdown-item" href="goals.php?action=add"><i class="fas fa-bullseye me-1"></i>Définir un objectif</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || isset($_GET['edit'])): ?>
            <!-- Formulaire d'ajout/modification -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0"><?php echo $edit_id > 0 ? 'Modifier un exercice' : 'Ajouter un exercice'; ?></h5>
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
                            
                            <form action="<?php echo $edit_id > 0 ? "exercise-log.php?edit={$edit_id}" : 'exercise-log.php?action=add'; ?>" method="POST" novalidate>
                                <div class="mb-3">
                                    <label for="exercise_type" class="form-label">Type d'exercice</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="exercise_type" id="exercise_type_predefined" value="predefined" <?php echo empty($custom_exercise_name) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="exercise_type_predefined">
                                            Exercice prédéfini
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="exercise_type" id="exercise_type_custom" value="custom" <?php echo !empty($custom_exercise_name) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="exercise_type_custom">
                                            Exercice personnalisé
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="predefined_exercise_section" class="mb-3 <?php echo !empty($custom_exercise_name) ? 'd-none' : ''; ?>">
                                    <label for="exercise_id" class="form-label">Exercice</label>
                                    <select class="form-select" id="exercise_id" name="exercise_id">
                                        <option value="">Sélectionnez un exercice</option>
                                        <?php foreach ($categories as $category): ?>
                                            <?php if (isset($exercises_by_category[$category['id']])): ?>
                                                <optgroup label="<?php echo htmlspecialchars($category['name']); ?>">
                                                    <?php foreach ($exercises_by_category[$category['id']] as $exercise): ?>
                                                        <option value="<?php echo $exercise['id']; ?>" data-calories="<?php echo $exercise['calories_per_hour']; ?>" <?php echo $exercise_id == $exercise['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($exercise['name']); ?> (<?php echo $exercise['calories_per_hour']; ?> cal/h)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div id="custom_exercise_section" class="mb-3 <?php echo empty($custom_exercise_name) ? 'd-none' : ''; ?>">
                                    <label for="custom_exercise_name" class="form-label">Nom de l'exercice personnalisé</label>
                                    <input type="text" class="form-control" id="custom_exercise_name" name="custom_exercise_name" value="<?php echo htmlspecialchars($custom_exercise_name); ?>">
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="duration" class="form-label">Durée (minutes)</label>
                                        <input type="number" class="form-control" id="duration" name="duration" value="<?php echo htmlspecialchars($duration); ?>" min="1" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="intensity" class="form-label">Intensité</label>
                                        <select class="form-select" id="intensity" name="intensity">
                                            <option value="faible" <?php echo $intensity === 'faible' ? 'selected' : ''; ?>>Faible</option>
                                            <option value="moderee" <?php echo $intensity === 'moderee' ? 'selected' : ''; ?>>Modérée</option>
                                            <option value="elevee" <?php echo $intensity === 'elevee' ? 'selected' : ''; ?>>Élevée</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="calories_burned" class="form-label">Calories brûlées</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="calories_burned" name="calories_burned" value="<?php echo htmlspecialchars($calories_burned); ?>" min="0" required>
                                            <span class="input-group-text">calories</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="log_date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="log_date" name="log_date" value="<?php echo htmlspecialchars($log_date); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="notes" class="form-label">Notes (optionnel)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i><?php echo $edit_id > 0 ? 'Mettre à jour' : 'Enregistrer'; ?>
                                    </button>
                                    <a href="exercise-log.php" class="btn btn-outline-secondary">Annuler</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Affichage des statistiques et du graphique -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-3">
                            <div class="display-4 text-primary mb-2">
                                <?php echo number_format($stats['total_calories']); ?>
                            </div>
                            <h5 class="card-title h6 mb-0">Calories brûlées</h5>
                            <p class="card-text small text-muted mb-0">
                                <i class="fas fa-fire me-1"></i>Total
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-3">
                            <div class="display-4 text-primary mb-2">
                                <?php echo number_format($stats['total_duration']); ?>
                            </div>
                            <h5 class="card-title h6 mb-0">Minutes d'exercice</h5>
                            <p class="card-text small text-muted mb-0">
                                <i class="fas fa-clock me-1"></i>Total
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-3">
                            <div class="display-4 text-primary mb-2">
                                <?php echo $stats['total_exercises']; ?>
                            </div>
                            <h5 class="card-title h6 mb-0">Séances d'exercice</h5>
                            <p class="card-text small text-muted mb-0">
                                <i class="fas fa-list-check me-1"></i>Total
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-3">
                            <div class="display-4 text-primary mb-2">
                                <?php 
                                    $avg_calories = $stats['total_exercises'] > 0 ? round($stats['total_calories'] / $stats['total_exercises']) : 0;
                                    echo number_format($avg_calories);
                                ?>
                            </div>
                            <h5 class="card-title h6 mb-0">Calories par séance</h5>
                            <p class="card-text small text-muted mb-0">
                                <i class="fas fa-calculator me-1"></i>Moyenne
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des exercices -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Exercice</th>
                            <th class="text-center">Durée</th>
                            <th class="text-center">Intensité</th>
                            <th class="text-center">Calories</th>
                            <th>Notes</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exercises)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">Aucun exercice enregistré</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exercises as $exercise): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo date('d/m/Y', strtotime($exercise['log_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($exercise['custom_exercise_name'] ?: $exercise['exercise_name']); ?></td>
                                    <td class="text-center"><?php echo $exercise['duration']; ?> min</td>
                                    <td class="text-center"><?php echo ucfirst($exercise['intensity']); ?></td>
                                    <td class="text-center"><?php echo number_format($exercise['calories_burned']); ?></td>
                                    <td class="text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($exercise['notes']); ?></td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-success share-btn" 
                                                    data-exercise-id="<?php echo $exercise['id']; ?>"
                                                    data-exercise-name="<?php echo htmlspecialchars($exercise['custom_exercise_name'] ?: $exercise['exercise_name']); ?>"
                                                    data-duration="<?php echo $exercise['duration']; ?>"
                                                    data-intensity="<?php echo $exercise['intensity']; ?>"
                                                    data-calories="<?php echo $exercise['calories_burned']; ?>"
                                                    data-notes="<?php echo htmlspecialchars($exercise['notes']); ?>">
                                                <i class="fas fa-share-alt"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet exercice ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="exercise_id" value="<?php echo $exercise['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pied de page -->
    <footer class="bg-light py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; 2023 Weight Tracker. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-muted me-3">Conditions d'utilisation</a>
                    <a href="#" class="text-muted me-3">Confidentialité</a>
                    <a href="#" class="text-muted">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal de partage -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Partager l'exercice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="shareForm" action="create-post.php" method="POST">
                        <input type="hidden" name="post_type" value="exercise">
                        <input type="hidden" name="reference_id" id="share_exercise_id">
                        <input type="hidden" name="visibility" value="public">
                        
                        <div class="mb-3">
                            <label class="form-label">Message (optionnel)</label>
                            <textarea class="form-control" name="content" rows="3"></textarea>
                        </div>
                        
                        <div class="exercise-details mb-3">
                            <p class="mb-1"><strong>Exercice:</strong> <span id="share_exercise_name"></span></p>
                            <p class="mb-1"><strong>Durée:</strong> <span id="share_exercise_duration"></span> minutes</p>
                            <p class="mb-1"><strong>Intensité:</strong> <span id="share_exercise_intensity"></span></p>
                            <p class="mb-1"><strong>Calories brûlées:</strong> <span id="share_exercise_calories"></span> kcal</p>
                            <p class="mb-0"><strong>Notes:</strong> <span id="share_exercise_notes"></span></p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script>
        // Initialiser le sélecteur de date
        flatpickr("#log_date", {
            locale: "fr",
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
        
        // Gestion du type d'exercice (prédéfini ou personnalisé)
        document.querySelectorAll('input[name="exercise_type"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                const predefinedSection = document.getElementById('predefined_exercise_section');
                const customSection = document.getElementById('custom_exercise_section');
                
                if (this.value === 'predefined') {
                    predefinedSection.classList.remove('d-none');
                    customSection.classList.add('d-none');
                    document.getElementById('custom_exercise_name').value = '';
                } else {
                    predefinedSection.classList.add('d-none');
                    customSection.classList.remove('d-none');
                    document.getElementById('exercise_id').value = '';
                }
            });
        });
        
        // Calcul automatique des calories brûlées
        document.getElementById('exercise_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const caloriesPerHour = selectedOption.getAttribute('data-calories');
            const duration = document.getElementById('duration').value;
            const intensity = document.getElementById('intensity').value;
            
            if (caloriesPerHour && duration) {
                let intensityFactor = 1;
                if (intensity === 'faible') {
                    intensityFactor = 0.8;
                } else if (intensity === 'elevee') {
                    intensityFactor = 1.2;
                }
                
                const calories = Math.round((caloriesPerHour / 60) * duration * intensityFactor);
                document.getElementById('calories_burned').value = calories;
            }
        });
        
        document.getElementById('duration').addEventListener('input', function() {
            const exerciseId = document.getElementById('exercise_id');
            if (exerciseId.value) {
                const event = new Event('change');
                exerciseId.dispatchEvent(event);
            }
        });
        
        document.getElementById('intensity').addEventListener('change', function() {
            const exerciseId = document.getElementById('exercise_id');
            if (exerciseId.value) {
                const event = new Event('change');
                exerciseId.dispatchEvent(event);
            }
        });
        
        // Gestion du partage
        const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
        
        document.querySelectorAll('.share-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const exerciseId = this.dataset.exerciseId;
                const exerciseName = this.dataset.exerciseName;
                const duration = this.dataset.duration;
                const intensity = this.dataset.intensity;
                const calories = this.dataset.calories;
                const notes = this.dataset.notes;
                
                console.log('Données de l\'exercice:', { 
                    exerciseId, 
                    exerciseName, 
                    duration, 
                    intensity, 
                    calories, 
                    notes 
                });
                
                document.getElementById('share_exercise_id').value = exerciseId;
                document.getElementById('share_exercise_name').textContent = exerciseName;
                document.getElementById('share_exercise_duration').textContent = duration;
                document.getElementById('share_exercise_intensity').textContent = intensity.charAt(0).toUpperCase() + intensity.slice(1);
                document.getElementById('share_exercise_calories').textContent = calories;
                document.getElementById('share_exercise_notes').textContent = notes || 'Aucune note';
                
                shareModal.show();
            });
        });
    </script>
</body>
</html>
