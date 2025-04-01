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
$sql = "SELECT up.*, u.gender, u.birth_date 
        FROM user_profiles up 
        JOIN users u ON up.user_id = u.id 
        WHERE up.user_id = ?";
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
$sql = "SELECT food_id, preference_type 
        FROM food_preferences 
        WHERE user_id = ?";
$food_preferences = fetchAll($sql, [$user_id]);

// Récupérer les besoins caloriques quotidiens
$sql = "SELECT * FROM user_calorie_needs 
        WHERE user_id = ? 
        ORDER BY calculation_date DESC LIMIT 1";
$calorie_needs = fetchOne($sql, [$user_id]);

// Initialiser les variables
$suggestion_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'repas';
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

// Récupérer les préférences alimentaires de l'utilisateur
$sql = "SELECT * FROM food_preferences WHERE user_id = ?";
$preferences = fetchAll($sql, [$user_id]);

// Organiser les préférences par type
$favorite_foods = [];
$blacklisted_foods = [];

foreach ($preferences as $pref) {
    if ($pref['preference_type'] === 'favori') {
        if ($pref['food_id']) {
            $sql = "SELECT name FROM foods WHERE id = ?";
            $food_info = fetchOne($sql, [$pref['food_id']]);
            $favorite_foods[] = $food_info ? $food_info['name'] : 'Aliment inconnu';
        } else {
            $favorite_foods[] = $pref['custom_food'];
        }
    } elseif ($pref['preference_type'] === 'blacklist') {
        if ($pref['food_id']) {
            $sql = "SELECT name FROM foods WHERE id = ?";
            $food_info = fetchOne($sql, [$pref['food_id']]);
            $blacklisted_foods[] = $food_info ? $food_info['name'] : 'Aliment inconnu';
        } else {
            $blacklisted_foods[] = $pref['custom_food'];
        }
    }
}

// Traitement de la demande de suggestion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suggestion_type = sanitizeInput($_POST['suggestion_type'] ?? 'repas');
    
    if (!in_array($suggestion_type, ['repas', 'exercice', 'programme'])) {
        $errors[] = "Type de suggestion invalide";
    } else {
        try {
            $suggestion_content = '';
            
            // Vérifier que le profil est complet
            if (empty($user_profile) || !isset($user_profile['age'])) {
                $errors[] = "Veuillez compléter votre profil avant d'utiliser les suggestions.";
            } else {
                switch ($suggestion_type) {
                    case 'repas':
                        $suggestion_content = generateMealSuggestion($user_profile, $latest_weight, $current_goal, $active_program, $favorite_foods, $blacklisted_foods);
                        break;
                        
                    case 'exercice':
                        $suggestion_content = generateExerciseSuggestion($user_profile, $latest_weight, $current_goal, $active_program);
                        break;
                        
                    case 'programme':
                        if (empty($api_key)) {
                            $errors[] = "La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur.";
                            break;
                        }
                        $meal_suggestion = generateMealSuggestion($user_profile, $latest_weight, $current_goal, $active_program, $favorite_foods, $blacklisted_foods);
                        $exercise_suggestion = generateExerciseSuggestion($user_profile, $latest_weight, $current_goal, $active_program);
                        
                        if (strpos($meal_suggestion, "La clé API ChatGPT n'est pas configurée") !== false || 
                            strpos($exercise_suggestion, "La clé API ChatGPT n'est pas configurée") !== false) {
                            $errors[] = "La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur.";
                            break;
                        }
                        
                        $suggestion_content = "PROGRAMME ALIMENTAIRE :\n\n" . $meal_suggestion . "\n\n" . 
                                            "PROGRAMME D'EXERCICES :\n\n" . $exercise_suggestion;
                        break;
                }
                
                if (!empty($suggestion_content) && empty($errors)) {
                    $sql = "INSERT INTO ai_suggestions (user_id, suggestion_type, content, is_read, is_implemented, created_at) 
                            VALUES (?, ?, ?, 0, 0, NOW())";
                    $result = insert($sql, [$user_id, $suggestion_type, $suggestion_content]);
                    
                    if ($result) {
                        $success_message = "Votre suggestion a été générée avec succès !";
                    } else {
                        $errors[] = "Une erreur s'est produite lors de l'enregistrement de la suggestion. Veuillez réessayer.";
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur s'est produite: " . $e->getMessage();
            error_log("Erreur dans suggestions.php: " . $e->getMessage());
        }
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
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-4">Suggestions IA</h1>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="preferences.php" class="btn btn-primary">
                    <i class="fas fa-cog me-1"></i>Préférences
                </a>
                <a href="food-management.php" class="btn btn-success">
                    <i class="fas fa-apple-alt me-1"></i>Aliments
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

        <!-- Onglets de navigation -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'repas' ? 'active' : ''; ?>" href="suggestions.php?type=repas">
                    <i class="fas fa-utensils me-1"></i>Suggestions de repas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'exercice' ? 'active' : ''; ?>" href="suggestions.php?type=exercice">
                    <i class="fas fa-running me-1"></i>Suggestions d'exercices
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'programme' ? 'active' : ''; ?>" href="suggestions.php?type=programme">
                    <i class="fas fa-calendar-alt me-1"></i>Programmes personnalisés
                </a>
            </li>
        </ul>

        <div class="row">
            <!-- Formulaire de génération de suggestion -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Générer une suggestion</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($api_key)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur pour utiliser cette fonctionnalité.
                            </div>
                        <?php else: ?>
                            <form method="post" action="suggestions.php" id="suggestionForm">
                                <input type="hidden" name="suggestion_type" value="<?php echo htmlspecialchars($suggestion_type); ?>">
                                
                                <p>
                                    <?php if ($suggestion_type === 'repas'): ?>
                                        Générez des suggestions de repas adaptées à votre profil, vos objectifs et vos préférences alimentaires.
                                    <?php elseif ($suggestion_type === 'exercice'): ?>
                                        Générez des suggestions d'exercices adaptées à votre niveau d'activité et vos objectifs de poids.
                                    <?php else: ?>
                                        Générez un programme personnalisé complet basé sur votre profil, vos objectifs et vos préférences.
                                    <?php endif; ?>
                                </p>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary" id="generateButton">
                                        <i class="fas fa-robot me-1"></i>Générer une suggestion
                                    </button>
                                </div>
                            </form>

                            <!-- Loader -->
                            <div id="loader" class="text-center mt-3 d-none">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-2 mb-0">Génération de votre suggestion en cours...</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <h6>Informations utilisées :</h6>
                            <ul class="small">
                                <?php if ($profile): ?>
                                    <li>Profil : <?php echo $profile['gender'] === 'homme' ? 'Homme' : 'Femme'; ?>, <?php echo isset($profile['birth_date']) ? calculateAge($profile['birth_date']) : '?'; ?> ans</li>
                                <?php else: ?>
                                    <li>Profil : Non renseigné</li>
                                <?php endif; ?>
                                
                                <?php if ($latest_weight): ?>
                                    <li>Poids actuel : <?php echo number_format($latest_weight['weight'], 1); ?> kg</li>
                                <?php else: ?>
                                    <li>Poids actuel : Non renseigné</li>
                                <?php endif; ?>
                                
                                <?php if ($current_goal): ?>
                                    <li>Objectif : <?php echo number_format($current_goal['target_weight'], 1); ?> kg</li>
                                <?php else: ?>
                                    <li>Objectif : Non défini</li>
                                <?php endif; ?>
                                
                                <?php if ($active_program): ?>
                                    <li>Programme : <?php echo htmlspecialchars($active_program['name']); ?></li>
                                <?php else: ?>
                                    <li>Programme : Aucun</li>
                                <?php endif; ?>
                                
                                <li>Préférences : <?php echo count($favorite_foods) + count($blacklisted_foods); ?> aliments configurés</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Liste des suggestions -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <?php 
                            if ($suggestion_type === 'repas') {
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
                                                <div class="d-flex justify-content-end">
                                                    <?php if (!$suggestion['is_read']): ?>
                                                        <form method="POST" action="mark_suggestion_read.php" style="display: inline;">
                                                            <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-success me-2">
                                                                <i class="fas fa-check me-1"></i>Marquer comme lu
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if (!$suggestion['is_implemented']): ?>
                                                        <form method="POST" action="mark_suggestion_implemented.php" style="display: inline;">
                                                            <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-primary me-2">
                                                                <i class="fas fa-check-double me-1"></i>Marquer comme implémenté
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <a href="suggestions.php?type=<?php echo urlencode($suggestion_type); ?>&action=delete&id=<?php echo $suggestion['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette suggestion ?');">
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
                    <div class="col-md-4">
                        <h6><i class="fas fa-utensils me-1"></i>Suggestions de repas</h6>
                        <p>
                            Les suggestions de repas sont générées en fonction de votre profil, vos objectifs de poids et vos préférences alimentaires.
                            Elles tiennent compte des aliments que vous aimez et évitent ceux que vous n'aimez pas.
                        </p>
                        <p>
                            <strong>Conseil :</strong> Ajoutez plus de préférences alimentaires pour obtenir des suggestions plus personnalisées.
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-running me-1"></i>Suggestions d'exercices</h6>
                        <p>
                            Les suggestions d'exercices sont adaptées à votre niveau d'activité et vos objectifs.
                            Elles proposent un équilibre entre cardio, renforcement musculaire et flexibilité.
                        </p>
                        <p>
                            <strong>Conseil :</strong> Mettez à jour votre niveau d'activité dans votre profil pour des suggestions plus précises.
                        </p>
                    </div>
                    <div class="col-md-4">
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