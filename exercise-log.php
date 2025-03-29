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

// Récupérer toutes les entrées d'exercices
$sql = "SELECT el.*, e.name as exercise_name, e.calories_per_hour 
        FROM exercise_logs el 
        LEFT JOIN exercises e ON el.exercise_id = e.id 
        WHERE el.user_id = ? 
        ORDER BY el.log_date DESC, el.created_at DESC";
$exercise_logs = fetchAll($sql, [$user_id]);

// Récupérer l'objectif de poids actuel
$sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);

// Calculer les statistiques
$total_entries = count($exercise_logs);
$total_duration = 0;
$total_calories = 0;

foreach ($exercise_logs as $log) {
    $total_duration += $log['duration'];
    $total_calories += $log['calories_burned'];
}

// Préparer les données pour le graphique
$chart_dates = [];
$chart_calories = [];
$chart_duration = [];

// Créer un tableau pour les 7 derniers jours
$end_date = new DateTime();
$start_date = new DateTime();
$start_date->modify('-6 days');

$period = new DatePeriod(
    $start_date,
    new DateInterval('P1D'),
    $end_date->modify('+1 day')
);

$daily_calories = [];
$daily_duration = [];

foreach ($period as $date) {
    $date_str = $date->format('Y-m-d');
    $daily_calories[$date_str] = 0;
    $daily_duration[$date_str] = 0;
}

// Remplir les données pour les jours où il y a des entrées
foreach ($exercise_logs as $log) {
    $log_date = $log['log_date'];
    if (isset($daily_calories[$log_date])) {
        $daily_calories[$log_date] += $log['calories_burned'];
        $daily_duration[$log_date] += $log['duration'];
    }
}

// Préparer les données pour le graphique
foreach ($daily_calories as $date => $calories) {
    $chart_dates[] = date('d/m', strtotime($date));
    $chart_calories[] = $calories;
    $chart_duration[] = $daily_duration[$date];
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
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-2">
                                <?php echo number_format($total_calories); ?>
                            </div>
                            <h5 class="card-title">Calories brûlées</h5>
                            <p class="card-text small text-muted">
                                <i class="fas fa-fire me-1"></i>Total
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-2">
                                <?php echo number_format($total_duration); ?>
                            </div>
                            <h5 class="card-title">Minutes d'exercice</h5>
                            <p class="card-text small text-muted">
                                <i class="fas fa-clock me-1"></i>Total
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3 mb-sm-0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-2">
                                <?php echo $total_entries; ?>
                            </div>
                            <h5 class="card-title">Séances d'exercice</h5>
                            <p class="card-text small text-muted">
                                <i class="fas fa-list-check me-1"></i>Total
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-2">
                                <?php 
                                    $avg_calories = $total_entries > 0 ? round($total_calories / $total_entries) : 0;
                                    echo number_format($avg_calories);
                                ?>
                            </div>
                            <h5 class="card-title">Calories par séance</h5>
                            <p class="card-text small text-muted">
                                <i class="fas fa-calculator me-1"></i>Moyenne
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphique d'activité -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Activité des 7 derniers jours</h5>
                </div>
                <div class="card-body">
                    <?php if (array_sum($chart_calories) > 0): ?>
                        <canvas id="exerciseChart" height="300"></canvas>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">Aucune donnée disponible</p>
                            <p class="text-muted">Commencez à enregistrer vos exercices pour voir l'évolution</p>
                            <a href="exercise-log.php?action=add" class="btn btn-primary mt-2">
                                <i class="fas fa-plus-circle me-1"></i>Ajouter un exercice
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tableau des entrées -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Historique des exercices</h5>
                        <a href="exercise-log.php?action=add" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus-circle me-1"></i>Ajouter
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Exercice</th>
                                    <th>Durée</th>
                                    <th>Intensité</th>
                                    <th>Calories</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($exercise_logs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <p class="text-muted mb-0">Aucun exercice enregistré</p>
                                            <small class="text-muted">Commencez à enregistrer vos exercices pour suivre votre activité physique</small>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($exercise_logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($log['log_date'])); ?></td>
                                            <td>
                                                <?php 
                                                    if (!empty($log['custom_exercise_name'])) {
                                                        echo htmlspecialchars($log['custom_exercise_name']);
                                                    } elseif (!empty($log['exercise_name'])) {
                                                        echo htmlspecialchars($log['exercise_name']);
                                                    } else {
                                                        echo '—';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo $log['duration']; ?> min</td>
                                            <td>
                                                <?php 
                                                    if ($log['intensity'] === 'faible') {
                                                        echo '<span class="badge bg-info">Faible</span>';
                                                    } elseif ($log['intensity'] === 'moderee') {
                                                        echo '<span class="badge bg-success">Modérée</span>';
                                                    } else {
                                                        echo '<span class="badge bg-warning">Élevée</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo number_format($log['calories_burned']); ?> cal</td>
                                            <td><?php echo $log['notes'] ? htmlspecialchars($log['notes']) : '—'; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary share-exercise-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#shareExerciseModal"
                                                            data-exercise-id="<?php echo $log['id']; ?>"
                                                            data-exercise-name="<?php echo htmlspecialchars($log['custom_exercise_name'] ?: $log['exercise_name']); ?>"
                                                            data-duration="<?php echo $log['duration']; ?>"
                                                            data-calories="<?php echo $log['calories_burned']; ?>"
                                                            data-notes="<?php echo htmlspecialchars($log['notes'] ?? ''); ?>"
                                                            onclick="console.log('Partage d\'exercice:', this.dataset);">
                                                        <i class="fas fa-share-alt me-1"></i>Partager
                                                    </button>
                                                    <a href="exercise-log.php?edit=<?php echo $log['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="exercise-log.php?delete=<?php echo $log['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet exercice ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
        
        <?php if (array_sum($chart_calories) > 0): ?>
        // Graphique d'activité
        const exerciseCtx = document.getElementById('exerciseChart').getContext('2d');
        const exerciseChart = new Chart(exerciseCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_dates); ?>,
                datasets: [
                    {
                        label: 'Calories brûlées',
                        data: <?php echo json_encode($chart_calories); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Durée (minutes)',
                        data: <?php echo json_encode($chart_duration); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Calories'
                        },
                        grid: {
                            borderDash: [2, 2]
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Minutes'
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
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
        const shareButtons = document.querySelectorAll('.share-exercise-btn');
        console.log('Nombre de boutons de partage trouvés:', shareButtons.length);
        
        shareButtons.forEach(btn => {
            console.log('Configuration du bouton:', btn.dataset);
            btn.addEventListener('click', function() {
                console.log('Clic sur le bouton de partage');
                const exerciseId = this.dataset.exerciseId;
                const exerciseName = this.dataset.exerciseName;
                const duration = this.dataset.duration;
                const calories = this.dataset.calories;
                const notes = this.dataset.notes;
                
                console.log('Données de l\'exercice:', {
                    exerciseId,
                    exerciseName,
                    duration,
                    calories,
                    notes
                });
                
                // Mettre à jour les champs du formulaire
                document.getElementById('share_exercise_id').value = exerciseId;
                
                // Pré-remplir le message
                let content = `Je viens de faire ${exerciseName} pendant ${duration} minutes (${calories} calories brûlées)`;
                if (notes) {
                    content += `\nNote: ${notes}`;
                }
                document.getElementById('share_content').value = content;
                
                console.log('Formulaire mis à jour avec:', {
                    exerciseId,
                    content
                });
            });
        });
    });
    </script>
</body>
</html>
