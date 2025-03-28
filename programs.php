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
$program_id = '';
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+12 weeks'));
$notes = '';
$success_message = '';
$errors = [];

// Traitement du formulaire d'inscription à un programme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_program') {
    // Récupérer et nettoyer les données du formulaire
    $program_id = isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0;
    $start_date = sanitizeInput($_POST['start_date'] ?? date('Y-m-d'));
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Validation des données
    if (empty($program_id) || $program_id <= 0) {
        $errors[] = "Veuillez sélectionner un programme valide";
    }
    
    if (empty($start_date) || !validateDate($start_date)) {
        $errors[] = "La date de début n'est pas valide";
    }
    
    // Si aucune erreur, inscrire l'utilisateur au programme
    if (empty($errors)) {
        try {
            // Récupérer les informations du programme
            $sql = "SELECT * FROM programs WHERE id = ?";
            $program = fetchOne($sql, [$program_id]);
            
            if (!$program) {
                $errors[] = "Le programme sélectionné n'existe pas";
            } else {
                // Calculer la date de fin en fonction de la durée du programme
                $duration_weeks = $program['duration_weeks'];
                $end_date = date('Y-m-d', strtotime($start_date . " + $duration_weeks weeks"));
                
                // Vérifier si l'utilisateur est déjà inscrit à un programme actif
                $sql = "SELECT * FROM user_programs WHERE user_id = ? AND status = 'en_cours'";
                $active_program = fetchOne($sql, [$user_id]);
                
                if ($active_program) {
                    // Mettre fin au programme actif
                    $sql = "UPDATE user_programs SET status = 'abandonne', notes = CONCAT(notes, ' (Remplacé par un nouveau programme)'), updated_at = NOW() WHERE id = ?";
                    update($sql, [$active_program['id']]);
                }
                
                // Inscrire l'utilisateur au nouveau programme
                $sql = "INSERT INTO user_programs (user_id, program_id, start_date, end_date, status, notes, created_at) 
                        VALUES (?, ?, ?, ?, 'en_cours', ?, NOW())";
                $result = insert($sql, [$user_id, $program_id, $start_date, $end_date, $notes]);
                
                if ($result) {
                    $success_message = "Vous êtes maintenant inscrit au programme " . htmlspecialchars($program['name']) . " !";
                    $program_id = '';
                    $notes = '';
                } else {
                    $errors[] = "Une erreur s'est produite lors de l'inscription au programme. Veuillez réessayer.";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur s'est produite: " . $e->getMessage();
            error_log("Erreur d'inscription au programme: " . $e->getMessage());
        }
    }
}

// Traitement de l'abandon d'un programme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quit_program') {
    $user_program_id = isset($_POST['user_program_id']) ? (int)$_POST['user_program_id'] : 0;
    
    if ($user_program_id > 0) {
        $sql = "UPDATE user_programs SET status = 'abandonne', updated_at = NOW() WHERE id = ? AND user_id = ?";
        $result = update($sql, [$user_program_id, $user_id]);
        
        if ($result) {
            $success_message = "Vous avez abandonné le programme avec succès.";
        } else {
            $errors[] = "Une erreur s'est produite lors de l'abandon du programme.";
        }
    }
}

// Récupérer le programme actif de l'utilisateur
$sql = "SELECT up.*, p.name as program_name, p.description, p.program_type, p.calorie_adjustment, 
               p.protein_ratio, p.carbs_ratio, p.fat_ratio, p.duration_weeks 
        FROM user_programs up 
        JOIN programs p ON up.program_id = p.id 
        WHERE up.user_id = ? AND up.status = 'en_cours' 
        ORDER BY up.created_at DESC 
        LIMIT 1";
$active_program = fetchOne($sql, [$user_id]);

// Récupérer l'historique des programmes de l'utilisateur
$sql = "SELECT up.*, p.name as program_name, p.program_type 
        FROM user_programs up 
        JOIN programs p ON up.program_id = p.id 
        WHERE up.user_id = ? AND (up.status != 'en_cours' OR up.id != ?) 
        ORDER BY up.created_at DESC";
$program_history = fetchAll($sql, [$user_id, $active_program ? $active_program['id'] : 0]);

// Récupérer tous les programmes disponibles
$sql = "SELECT * FROM programs ORDER BY name";
$available_programs = fetchAll($sql, []);

// Récupérer le dernier poids enregistré
$sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
$latest_weight = fetchOne($sql, [$user_id]);

// Récupérer le profil de l'utilisateur
$sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$profile = fetchOne($sql, [$user_id]);

// Calculer les besoins caloriques et macronutriments si un programme actif existe
$daily_calories = 0;
$protein_goal = 0;
$carbs_goal = 0;
$fat_goal = 0;

if ($active_program && $latest_weight && $profile) {
    // Calculer le BMR (métabolisme de base) avec la formule de Mifflin-St Jeor
    $age = isset($profile['birth_date']) ? (date('Y') - date('Y', strtotime($profile['birth_date']))) : 30;
    
    if ($profile['gender'] === 'homme') {
        $bmr = 10 * $latest_weight['weight'] + 6.25 * $profile['height'] - 5 * $age + 5;
    } else {
        $bmr = 10 * $latest_weight['weight'] + 6.25 * $profile['height'] - 5 * $age - 161;
    }
    
    // Calculer le TDEE (dépense énergétique totale quotidienne)
    $activity_factors = [
        'sedentaire' => 1.2,
        'leger' => 1.375,
        'modere' => 1.55,
        'actif' => 1.725,
        'tres_actif' => 1.9
    ];
    
    $activity_factor = $activity_factors[$profile['activity_level']] ?? 1.2;
    $tdee = $bmr * $activity_factor;
    
    // Appliquer l'ajustement calorique du programme
    $daily_calories = $tdee + $active_program['calorie_adjustment'];
    
    // Calculer les objectifs de macronutriments
    $protein_goal = round(($daily_calories * $active_program['protein_ratio']) / 4); // 4 calories par gramme de protéines
    $carbs_goal = round(($daily_calories * $active_program['carbs_ratio']) / 4); // 4 calories par gramme de glucides
    $fat_goal = round(($daily_calories * $active_program['fat_ratio']) / 9); // 9 calories par gramme de lipides
}

// Récupérer les suggestions d'IA pour l'utilisateur
$sql = "SELECT * FROM ai_suggestions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$ai_suggestions = fetchAll($sql, [$user_id]);

// Fonction pour traduire le type de programme
function translateProgramType($type) {
    $translations = [
        'seche' => 'Sèche',
        'perte_rapide' => 'Perte de poids rapide',
        'prise_muscle' => 'Prise de muscle',
        'equilibre' => 'Équilibre',
        'personnalise' => 'Personnalisé'
    ];
    
    return $translations[$type] ?? $type;
}

// Fonction pour traduire le statut du programme
function translateProgramStatus($status) {
    $translations = [
        'en_cours' => 'En cours',
        'termine' => 'Terminé',
        'abandonne' => 'Abandonné'
    ];
    
    return $translations[$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programmes - Weight Tracker</title>
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
                <h1 class="mb-0">Programmes</h1>
                <p class="text-muted">Suivez un programme adapté à vos objectifs</p>
            </div>
            <div class="col-md-4 text-md-end">
                <?php if (!$active_program): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#joinProgramModal">
                    <i class="fas fa-plus-circle me-1"></i>Rejoindre un programme
                </button>
                <?php endif; ?>
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

        <div class="row">
            <!-- Programme actif -->
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-dumbbell text-primary me-2"></i>Programme actif</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($active_program): ?>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h4><?php echo htmlspecialchars($active_program['program_name']); ?></h4>
                                    <span class="badge bg-primary"><?php echo translateProgramType($active_program['program_type']); ?></span>
                                </div>
                                <form action="programs.php" method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="quit_program">
                                    <input type="hidden" name="user_program_id" value="<?php echo $active_program['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir abandonner ce programme ?')">
                                        <i class="fas fa-times me-1"></i>Abandonner
                                    </button>
                                </form>
                            </div>
                            
                            <p class="text-muted"><?php echo htmlspecialchars($active_program['description']); ?></p>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Période</h6>
                                    <p>
                                        <i class="far fa-calendar-alt me-1"></i>
                                        Du <?php echo date('d/m/Y', strtotime($active_program['start_date'])); ?> 
                                        au <?php echo date('d/m/Y', strtotime($active_program['end_date'])); ?>
                                        (<?php echo $active_program['duration_weeks']; ?> semaines)
                                    </p>
                                    
                                    <?php
                                    // Calculer la progression
                                    $start = new DateTime($active_program['start_date']);
                                    $end = new DateTime($active_program['end_date']);
                                    $now = new DateTime();
                                    $total_days = $start->diff($end)->days;
                                    $days_passed = $start->diff($now)->days;
                                    
                                    if ($days_passed < 0) {
                                        $progress = 0;
                                    } elseif ($days_passed > $total_days) {
                                        $progress = 100;
                                    } else {
                                        $progress = ($days_passed / $total_days) * 100;
                                    }
                                    ?>
                                    
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo round($progress); ?>% complété
                                    </small>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6>Ajustement calorique</h6>
                                    <p>
                                        <?php if ($active_program['calorie_adjustment'] > 0): ?>
                                            <span class="text-success">
                                                <i class="fas fa-plus-circle me-1"></i>
                                                +<?php echo $active_program['calorie_adjustment']; ?> calories/jour
                                            </span>
                                        <?php elseif ($active_program['calorie_adjustment'] < 0): ?>
                                            <span class="text-danger">
                                                <i class="fas fa-minus-circle me-1"></i>
                                                <?php echo $active_program['calorie_adjustment']; ?> calories/jour
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <i class="fas fa-equals me-1"></i>
                                                Maintien calorique
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <h6>Répartition des macronutriments</h6>
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <span class="badge bg-danger">Protéines</span>
                                            <p class="mb-0"><?php echo round($active_program['protein_ratio'] * 100); ?>%</p>
                                        </div>
                                        <div class="me-3">
                                            <span class="badge bg-warning">Glucides</span>
                                            <p class="mb-0"><?php echo round($active_program['carbs_ratio'] * 100); ?>%</p>
                                        </div>
                                        <div>
                                            <span class="badge bg-info">Lipides</span>
                                            <p class="mb-0"><?php echo round($active_program['fat_ratio'] * 100); ?>%</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($daily_calories > 0): ?>
                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Vos objectifs quotidiens</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-3 mb-md-0">
                                            <h6 class="text-muted">Calories</h6>
                                            <h4><?php echo round($daily_calories); ?> kcal</h4>
                                        </div>
                                        <div class="col-md-3 mb-3 mb-md-0">
                                            <h6 class="text-muted">Protéines</h6>
                                            <h4><?php echo $protein_goal; ?> g</h4>
                                        </div>
                                        <div class="col-md-3 mb-3 mb-md-0">
                                            <h6 class="text-muted">Glucides</h6>
                                            <h4><?php echo $carbs_goal; ?> g</h4>
                                        </div>
                                        <div class="col-md-3">
                                            <h6 class="text-muted">Lipides</h6>
                                            <h4><?php echo $fat_goal; ?> g</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($active_program['notes'])): ?>
                            <div class="mb-0">
                                <h6>Notes</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($active_program['notes'])); ?></p>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-dumbbell text-muted mb-3" style="font-size: 3rem;"></i>
                                <h4>Vous ne suivez aucun programme actuellement</h4>
                                <p class="text-muted mb-4">Rejoignez un programme pour atteindre vos objectifs plus efficacement</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#joinProgramModal">
                                    <i class="fas fa-plus-circle me-1"></i>Rejoindre un programme
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Suggestions d'IA -->
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-robot text-primary me-2"></i>Suggestions personnalisées</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ai_suggestions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-robot text-muted mb-3" style="font-size: 2rem;"></i>
                                <p class="mb-0">Aucune suggestion disponible pour le moment.</p>
                                <p class="text-muted">Continuez à utiliser l'application pour recevoir des suggestions personnalisées.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($ai_suggestions as $suggestion): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex">
                                            <?php if ($suggestion['suggestion_type'] === 'repas'): ?>
                                                <div class="flex-shrink-0 me-3">
                                                    <span class="badge rounded-pill bg-success p-2">
                                                        <i class="fas fa-utensils fa-lg"></i>
                                                    </span>
                                                </div>
                                            <?php elseif ($suggestion['suggestion_type'] === 'exercice'): ?>
                                                <div class="flex-shrink-0 me-3">
                                                    <span class="badge rounded-pill bg-danger p-2">
                                                        <i class="fas fa-running fa-lg"></i>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex-shrink-0 me-3">
                                                    <span class="badge rounded-pill bg-primary p-2">
                                                        <i class="fas fa-dumbbell fa-lg"></i>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($suggestion['content'])); ?></p>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($suggestion['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historique des programmes -->
        <?php if (!empty($program_history)): ?>
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-history text-primary me-2"></i>Historique des programmes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Programme</th>
                                        <th>Type</th>
                                        <th>Période</th>
                                        <th>Statut</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($program_history as $program): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($program['program_name']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo translateProgramType($program['program_type']); ?></span></td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($program['start_date'])); ?> - 
                                            <?php echo date('d/m/Y', strtotime($program['end_date'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($program['status'] === 'termine'): ?>
                                                <span class="badge bg-success"><?php echo translateProgramStatus($program['status']); ?></span>
                                            <?php elseif ($program['status'] === 'abandonne'): ?>
                                                <span class="badge bg-danger"><?php echo translateProgramStatus($program['status']); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-primary"><?php echo translateProgramStatus($program['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo !empty($program['notes']) ? htmlspecialchars(substr($program['notes'], 0, 50)) . (strlen($program['notes']) > 50 ? '...' : '') : '-'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de rejoindre un programme -->
    <div class="modal fade" id="joinProgramModal" tabindex="-1" aria-labelledby="joinProgramModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="joinProgramModalLabel">Rejoindre un programme</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="programs.php" method="POST" id="joinProgramForm">
                        <input type="hidden" name="action" value="join_program">
                        
                        <div class="mb-3">
                            <label for="program_id" class="form-label">Programme</label>
                            <select class="form-select" id="program_id" name="program_id" required>
                                <option value="">Sélectionnez un programme</option>
                                <?php foreach ($available_programs as $program): ?>
                                    <option value="<?php echo $program['id']; ?>" data-description="<?php echo htmlspecialchars($program['description']); ?>" data-duration="<?php echo $program['duration_weeks']; ?>">
                                        <?php echo htmlspecialchars($program['name']); ?> (<?php echo translateProgramType($program['program_type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="programDescription" class="alert alert-info mb-3" style="display: none;"></div>
                        
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (optionnel)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                        
                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Si vous suivez déjà un programme actif, celui-ci sera automatiquement abandonné.
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="joinProgramForm" class="btn btn-primary">Rejoindre</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser Flatpickr pour les sélecteurs de date
            flatpickr("#start_date", {
                dateFormat: "Y-m-d",
                minDate: "today"
            });
            
            // Afficher la description du programme sélectionné
            const programSelect = document.getElementById('program_id');
            const programDescription = document.getElementById('programDescription');
            
            programSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (this.value !== '') {
                    const description = selectedOption.getAttribute('data-description');
                    const duration = selectedOption.getAttribute('data-duration');
                    
                    programDescription.innerHTML = `
                        <p class="mb-1">${description}</p>
                        <p class="mb-0"><strong>Durée:</strong> ${duration} semaines</p>
                    `;
                    programDescription.style.display = 'block';
                } else {
                    programDescription.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
