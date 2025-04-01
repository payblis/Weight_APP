<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Définir le nom du site
define('SITE_NAME', 'MyFity');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$user = fetchOne($sql, [$user_id]);

// Récupérer les informations du profil utilisateur
$sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$user_profile = fetchOne($sql, [$user_id]);

if (!$user_profile) {
    $errors[] = "Veuillez compléter votre profil avant d'utiliser les suggestions.";
} else {
    // Calculer l'âge à partir de la date de naissance
    if (isset($user_profile['birth_date'])) {
        $user_profile['age'] = calculateAge($user_profile['birth_date']);
    } else {
        $errors[] = "Veuillez ajouter votre date de naissance dans votre profil.";
    }
}

// Récupérer l'objectif actuel
$sql = "SELECT * FROM goals 
        WHERE user_id = ? AND status = 'en_cours' 
        ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);

// Récupérer les aliments disponibles
$sql = "SELECT * FROM foods WHERE is_public = 1 ORDER BY name";
$available_foods = fetchAll($sql, []);

// Récupérer les exercices disponibles
$sql = "SELECT e.*, ec.name as category_name 
        FROM exercises e 
        JOIN exercise_categories ec ON e.category_id = ec.id 
        WHERE e.is_public = 1 
        ORDER BY ec.name, e.name";
$available_exercises = fetchAll($sql, []);

// Récupérer les préférences alimentaires
$favorite_foods = [];
$blacklisted_foods = [];

$sql = "SELECT custom_food, preference_type FROM food_preferences WHERE user_id = ?";
$preferences = fetchAll($sql, [$user_id]);

foreach ($preferences as $pref) {
    if ($pref['preference_type'] === 'favorite') {
        $favorite_foods[] = $pref['custom_food'];
    } else if ($pref['preference_type'] === 'blacklist') {
        $blacklisted_foods[] = $pref['custom_food'];
    }
}

// Récupérer les besoins caloriques quotidiens
$sql = "SELECT * FROM user_calorie_needs 
        WHERE user_id = ? 
        ORDER BY calculation_date DESC LIMIT 1";
$calorie_needs = fetchOne($sql, [$user_id]);

// Initialiser les variables
$suggestion_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'alimentation';
$success_message = '';
$errors = [];

// Récupérer la clé API ChatGPT
$sql = "SELECT setting_value FROM settings WHERE setting_name = 'chatgpt_api_key'";
$api_key_setting = fetchOne($sql, []);
$api_key = $api_key_setting ? $api_key_setting['setting_value'] : '';

// Récupérer le profil de l'utilisateur
$sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$profile = fetchOne($sql, [$user_id]);

// Récupérer le dernier poids enregistré
$sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
$latest_weight = fetchOne($sql, [$user_id]);

// Récupérer le programme actif de l'utilisateur
$sql = "SELECT p.* FROM user_programs up 
        JOIN programs p ON up.program_id = p.id 
        WHERE up.user_id = ? AND up.status = 'actif' 
        ORDER BY up.created_at DESC LIMIT 1";
$active_program = fetchOne($sql, [$user_id]);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'generate_meal':
            try {
                if (empty($_POST['meal_type'])) {
                    throw new Exception("Veuillez sélectionner un type de repas");
                }

                $meal_type = $_POST['meal_type'];
                $meal_suggestion = generateMealSuggestion(
                    $profile,
                    $latest_weight['weight'],
                    $current_goal,
                    $active_program,
                    $favorite_foods,
                    $blacklisted_foods,
                    $meal_type
                );
                
                $meal_suggestion_success = "Suggestion générée avec succès !";
            } catch (Exception $e) {
                $meal_suggestion_error = $e->getMessage();
            }
            break;
            
        case 'generate_exercise':
            try {
                if (empty($_POST['exercise_type'])) {
                    throw new Exception("Veuillez sélectionner un type d'exercice");
                }

                $exercise_type = $_POST['exercise_type'];
                $exercise_suggestion = generateExerciseSuggestion(
                    $profile,
                    $latest_weight['weight'],
                    $current_goal,
                    $active_program,
                    $exercise_type
                );
                
                $exercise_suggestion_success = "Suggestion générée avec succès !";
            } catch (Exception $e) {
                $exercise_suggestion_error = $e->getMessage();
            }
            break;
    }
}

// Gérer la suppression de suggestion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $suggestion_id = (int)$_GET['id'];
    $sql = "DELETE FROM ai_suggestions WHERE id = ? AND user_id = ?";
    $result = delete($sql, [$suggestion_id, $user_id]);
    
    if ($result) {
        $success_message = "La suggestion a été supprimée avec succès.";
    } else {
        $errors[] = "Une erreur s'est produite lors de la suppression de la suggestion.";
    }
    
    // Rediriger pour éviter la soumission multiple
    redirect("suggestions.php?type=" . urlencode($suggestion_type));
}

// Récupérer les suggestions existantes
$sql = "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y à %H:%i') as formatted_date FROM ai_suggestions 
        WHERE user_id = ? AND suggestion_type = ? 
        ORDER BY created_at DESC 
        LIMIT 10";
$suggestions = fetchAll($sql, [$user_id, $suggestion_type]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggestions IA - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @media (max-width: 768px) {
            .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }
            .nav-tabs::-webkit-scrollbar {
                display: none;
            }
            .nav-tabs .nav-link {
                white-space: nowrap;
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
            .card {
                margin-bottom: 1rem;
            }
            .accordion-button {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            .btn-group .btn {
                width: 100%;
                margin: 0;
            }
            .d-flex.justify-content-end {
                flex-direction: column;
                gap: 0.5rem;
            }
            .d-flex.justify-content-end .btn {
                width: 100%;
                margin: 0;
            }
            .card-header {
                padding: 0.75rem;
            }
            .card-header h5 {
                font-size: 1.1rem;
                margin: 0;
            }
            .card-body {
                padding: 0.75rem;
            }
            .small {
                font-size: 0.8rem;
            }
            .mb-4 {
                margin-bottom: 1rem !important;
            }
            .mt-4 {
                margin-top: 1rem !important;
            }
            .py-4 {
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }
        }
        .shopping-list {
            white-space: pre-wrap;
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
            margin: 0;
        }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12 col-md-8">
                <h1 class="mb-4">Suggestions IA</h1>
            </div>
            <div class="col-12 col-md-4 text-md-end mb-3 mb-md-0">
                <div class="d-grid d-md-block gap-2">
                    <a href="preferences.php" class="btn btn-primary">
                        <i class="fas fa-cog me-1"></i>Préférences
                    </a>
                    <a href="food-management.php" class="btn btn-success">
                        <i class="fas fa-apple-alt me-1"></i>Aliments
                    </a>
                </div>
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

        <!-- Onglets de navigation -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'alimentation' ? 'active' : ''; ?>" href="suggestions.php?type=alimentation">
                    <i class="fas fa-utensils me-1"></i>Repas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'exercice' ? 'active' : ''; ?>" href="suggestions.php?type=exercice">
                    <i class="fas fa-running me-1"></i>Exercices
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'programme' ? 'active' : ''; ?>" href="suggestions.php?type=programme">
                    <i class="fas fa-calendar-alt me-1"></i>Programmes
                </a>
            </li>
        </ul>

        <div class="row">
            <!-- Formulaire de génération de suggestion -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Suggestions personnalisées</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($meal_suggestion_error)): ?>
                            <div class="alert alert-danger"><?php echo $meal_suggestion_error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($meal_suggestion_success)): ?>
                            <div class="alert alert-success"><?php echo $meal_suggestion_success; ?></div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <input type="hidden" name="action" value="generate_meal">
                            <div class="mb-3">
                                <label for="meal_type" class="form-label">Type de suggestion</label>
                                <select class="form-select" id="meal_type" name="meal_type" required>
                                    <option value="">Sélectionnez un type</option>
                                    <option value="petit_dejeuner">Petit-déjeuner</option>
                                    <option value="dejeuner">Déjeuner</option>
                                    <option value="diner">Dîner</option>
                                    <option value="collation">Collation</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Générer une suggestion</button>
                        </form>

                        <?php if (isset($meal_suggestion)): ?>
                            <div class="mt-4">
                                <h6>Suggestion générée :</h6>
                                <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars($meal_suggestion); ?></pre>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($exercise_suggestion_error)): ?>
                            <div class="alert alert-danger"><?php echo $exercise_suggestion_error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($exercise_suggestion_success)): ?>
                            <div class="alert alert-success"><?php echo $exercise_suggestion_success; ?></div>
                        <?php endif; ?>

                        <form method="post" action="" class="mt-4">
                            <input type="hidden" name="action" value="generate_exercise">
                            <div class="mb-3">
                                <label for="exercise_type" class="form-label">Type d'exercice</label>
                                <select class="form-select" id="exercise_type" name="exercise_type" required>
                                    <option value="">Sélectionnez un type</option>
                                    <option value="cardio">Cardio</option>
                                    <option value="musculation">Musculation</option>
                                    <option value="flexibilite">Flexibilité</option>
                                    <option value="equilibre">Équilibre</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Générer une suggestion</button>
                        </form>

                        <?php if (isset($exercise_suggestion)): ?>
                            <div class="mt-4">
                                <h6>Suggestion générée :</h6>
                                <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars($exercise_suggestion); ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Liste des suggestions -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <?php 
                            if ($suggestion_type === 'alimentation') {
                                echo 'Suggestions de repas';
                            } elseif ($suggestion_type === 'exercice') {
                                echo 'Suggestions d\'exercices';
                            } else {
                                echo 'Programmes personnalisés';
                            }
                            ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($suggestions)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Aucune suggestion disponible. Utilisez le formulaire pour en générer.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="suggestionsAccordion">
                                <?php foreach ($suggestions as $index => $suggestion): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <span>Suggestion du <?php echo $suggestion['formatted_date']; ?></span>
                                                    <?php if ($suggestion['is_implemented']): ?>
                                                        <span class="badge bg-success ms-2">Implémenté</span>
                                                    <?php endif; ?>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#suggestionsAccordion">
                                            <div class="accordion-body">
                                                <div class="mb-3">
                                                    <?php echo nl2br(htmlspecialchars($suggestion['content'])); ?>
                                                </div>
                                                <div class="d-flex flex-column flex-md-row justify-content-end gap-2">
                                                    <?php if (!$suggestion['is_read']): ?>
                                                        <form method="POST" action="mark_suggestion_read.php" style="display: inline;">
                                                            <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-success w-100">
                                                                <i class="fas fa-check me-1"></i>Marquer comme lu
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if (!$suggestion['is_implemented']): ?>
                                                        <form method="POST" action="mark_suggestion_implemented.php" style="display: inline;">
                                                            <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                                                <i class="fas fa-check-double me-1"></i>Marquer comme implémenté
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <a href="suggestions.php?type=<?php echo urlencode($suggestion_type); ?>&action=delete&id=<?php echo $suggestion['id']; ?>" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette suggestion ?');">
                                                        <i class="fas fa-trash me-1"></i>Supprimer
                                                    </a>
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
        </div>
        
        <!-- Informations sur l'utilisation des suggestions -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Comment utiliser les suggestions IA</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-4 mb-4 mb-md-0">
                        <h6><i class="fas fa-utensils me-1"></i>Suggestions de repas</h6>
                        <p>
                            Les suggestions de repas sont générées en fonction de votre profil, vos objectifs de poids et vos préférences alimentaires.
                            Elles tiennent compte des aliments que vous aimez et évitent ceux que vous n'aimez pas.
                        </p>
                        <p>
                            <strong>Conseil :</strong> Ajoutez plus de préférences alimentaires pour obtenir des suggestions plus personnalisées.
                        </p>
                    </div>
                    <div class="col-12 col-md-4 mb-4 mb-md-0">
                        <h6><i class="fas fa-running me-1"></i>Suggestions d'exercices</h6>
                        <p>
                            Les suggestions d'exercices sont adaptées à votre niveau d'activité et vos objectifs.
                            Elles proposent un équilibre entre cardio, renforcement musculaire et flexibilité.
                        </p>
                        <p>
                            <strong>Conseil :</strong> Mettez à jour votre niveau d'activité dans votre profil pour des suggestions plus précises.
                        </p>
                    </div>
                    <div class="col-12 col-md-4">
                        <h6><i class="fas fa-calendar-alt me-1"></i>Programmes personnalisés</h6>
                        <p>
                            Les programmes personnalisés combinent nutrition et exercice pour créer un plan complet adapté à vos objectifs.
                            Cette fonctionnalité utilise l'API ChatGPT pour une personnalisation avancée.
                        </p>
                        <p>
                            <strong>Conseil :</strong> La clé API ChatGPT est gérée par l'administrateur de l'application.
                            Contactez-le si vous souhaitez utiliser les fonctionnalités d'IA.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('suggestionForm').addEventListener('submit', function(e) {
            // Afficher le loader
            document.getElementById('loader').classList.remove('d-none');
            document.getElementById('generateButton').disabled = true;
            
            // Désactiver le bouton pendant la génération
            const button = document.getElementById('generateButton');
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Génération en cours...';
        });
    </script>
</body>
</html> 