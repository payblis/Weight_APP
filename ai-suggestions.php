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

// Récupérer l'objectif de poids actuel
$sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);

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

// Fonction pour appeler l'API ChatGPT
function callChatGPTAPI($prompt, $api_key) {
    error_log("=== DÉBUT DE L'APPEL À L'API CHATGPT ===");
    error_log("URL: https://api.openai.com/v1/chat/completions");
    error_log("Clé API (partielle): " . substr($api_key, 0, 4) . "..." . substr($api_key, -4));
    
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Tu es un expert en nutrition et en fitness qui fournit des conseils personnalisés et détaillés.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 1000
    ];
    
    error_log("Données de la requête: " . json_encode($data, JSON_PRETTY_PRINT));
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    error_log("Envoi de la requête cURL...");
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    error_log("Code HTTP reçu: " . $http_code);
    if ($curl_error) {
        error_log("ERREUR cURL: " . $curl_error);
        return false;
    }
    
    if ($http_code !== 200) {
        error_log("ERREUR HTTP: " . $response);
        return false;
    }
    
    $response_data = json_decode($response, true);
    
    if (!isset($response_data['choices'][0]['message']['content'])) {
        error_log("ERREUR: Structure de réponse invalide");
        error_log("Réponse reçue: " . $response);
        return false;
    }
    
    error_log("Réponse valide reçue de l'API");
    error_log("=== FIN DE L'APPEL À L'API CHATGPT ===");
    
    return $response_data['choices'][0]['message']['content'];
}

// Fonction pour générer une suggestion de repas
function generateMealSuggestion($profile, $latest_weight, $current_goal, $active_program, $favorite_foods, $blacklisted_foods) {
    global $api_key;
    
    error_log("=== DÉBUT DE LA GÉNÉRATION DE SUGGESTION DE REPAS ===");
    
    if (empty($api_key)) {
        error_log("ERREUR: Clé API ChatGPT manquante");
        return "La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur pour utiliser cette fonctionnalité.";
    }
    
    // Log des données d'entrée
    error_log("Données du profil: " . print_r($profile, true));
    error_log("Dernier poids: " . print_r($latest_weight, true));
    error_log("Objectif actuel: " . print_r($current_goal, true));
    error_log("Programme actif: " . print_r($active_program, true));
    error_log("Aliments préférés: " . print_r($favorite_foods, true));
    error_log("Aliments à éviter: " . print_r($blacklisted_foods, true));
    
    // Construire le prompt pour ChatGPT
    $prompt = "En tant que nutritionniste expert, génère un plan de repas personnalisé avec les informations suivantes :\n\n";
    $prompt .= "Profil : " . ($profile['gender'] === 'homme' ? 'Homme' : 'Femme') . ", " . 
               (date('Y') - date('Y', strtotime($profile['birth_date']))) . " ans\n";
    $prompt .= "Niveau d'activité : " . ucfirst($profile['activity_level']) . "\n";
    $prompt .= "Poids actuel : " . ($latest_weight ? $latest_weight['weight'] . " kg" : "Non renseigné") . "\n";
    $prompt .= "Objectif : " . ($current_goal ? $current_goal['target_weight'] . " kg" : "Non défini") . "\n";
    $prompt .= "Programme : " . ($active_program ? $active_program['name'] : "Aucun") . "\n";
    
    if (!empty($favorite_foods)) {
        $prompt .= "Aliments préférés : " . implode(", ", array_slice($favorite_foods, 0, 5)) . "\n";
    }
    
    if (!empty($blacklisted_foods)) {
        $prompt .= "Aliments à éviter : " . implode(", ", array_slice($blacklisted_foods, 0, 5)) . "\n";
    }
    
    $prompt .= "\nGénère un plan de repas équilibré avec :\n";
    $prompt .= "1. Petit-déjeuner (avec calories et macronutriments)\n";
    $prompt .= "2. Déjeuner (avec calories et macronutriments)\n";
    $prompt .= "3. Dîner (avec calories et macronutriments)\n";
    $prompt .= "4. Collations (2-3 par jour, avec calories et macronutriments)\n\n";
    
    $prompt .= "Format de réponse souhaité :\n";
    $prompt .= "PETIT-DÉJEUNER\n";
    $prompt .= "- Liste des aliments avec quantités\n";
    $prompt .= "- Calories totales : X kcal\n";
    $prompt .= "- Protéines : Xg\n";
    $prompt .= "- Glucides : Xg\n";
    $prompt .= "- Lipides : Xg\n\n";
    $prompt .= "[Répéter le même format pour chaque repas]";
    
    error_log("Prompt généré: " . $prompt);
    
    // Appeler l'API ChatGPT
    error_log("Appel de l'API ChatGPT...");
    $response = callChatGPTAPI($prompt, $api_key);
    
    if ($response === false) {
        error_log("ERREUR: Échec de l'appel à l'API ChatGPT");
        return "Une erreur s'est produite lors de la génération de la suggestion. Veuillez réessayer plus tard.";
    }
    
    error_log("Réponse reçue de l'API ChatGPT: " . $response);
    error_log("=== FIN DE LA GÉNÉRATION DE SUGGESTION DE REPAS ===");
    
    return $response;
}

// Fonction pour générer une suggestion d'exercice
function generateExerciseSuggestion($profile, $latest_weight, $current_goal, $active_program) {
    global $api_key;
    
    if (empty($api_key)) {
        return "La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur pour utiliser cette fonctionnalité.";
    }
    
    // Construire le prompt pour ChatGPT
    $prompt = "En tant que coach sportif expert, génère un programme d'exercices personnalisé avec les informations suivantes :\n\n";
    $prompt .= "Profil : " . ($profile['gender'] === 'homme' ? 'Homme' : 'Femme') . ", " . 
               (date('Y') - date('Y', strtotime($profile['birth_date']))) . " ans\n";
    $prompt .= "Niveau d'activité : " . ucfirst($profile['activity_level']) . "\n";
    $prompt .= "Poids actuel : " . ($latest_weight ? $latest_weight['weight'] . " kg" : "Non renseigné") . "\n";
    $prompt .= "Objectif : " . ($current_goal ? $current_goal['target_weight'] . " kg" : "Non défini") . "\n";
    $prompt .= "Programme : " . ($active_program ? $active_program['name'] : "Aucun") . "\n\n";
    $prompt .= "Génère un programme d'exercices adapté avec cardio, renforcement musculaire et flexibilité. Inclus les séries, répétitions et temps de repos.";
    
    // Appeler l'API ChatGPT
    $response = callChatGPTAPI($prompt, $api_key);
    
    if ($response === false) {
        return "Une erreur s'est produite lors de la génération de la suggestion. Veuillez réessayer plus tard.";
    }
    
    return $response;
}

// Traitement de la demande de suggestion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suggestion_type = sanitizeInput($_POST['suggestion_type'] ?? 'repas');
    
    if (!in_array($suggestion_type, ['repas', 'exercice', 'programme'])) {
        $errors[] = "Type de suggestion invalide";
    } else {
        try {
            $suggestion_content = '';
            
            switch ($suggestion_type) {
                case 'repas':
                    $suggestion_content = generateMealSuggestion($profile, $latest_weight, $current_goal, $active_program, $favorite_foods, $blacklisted_foods);
                    break;
                    
                case 'exercice':
                    $suggestion_content = generateExerciseSuggestion($profile, $latest_weight, $current_goal, $active_program);
                    break;
                    
                case 'programme':
                    if (empty($api_key)) {
                        $errors[] = "La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur.";
                        break;
                    }
                    $meal_suggestion = generateMealSuggestion($profile, $latest_weight, $current_goal, $active_program, $favorite_foods, $blacklisted_foods);
                    $exercise_suggestion = generateExerciseSuggestion($profile, $latest_weight, $current_goal, $active_program);
                    
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
                $sql = "INSERT INTO ai_suggestions (user_id, suggestion_type, content, created_at) VALUES (?, ?, ?, NOW())";
                $result = insert($sql, [$user_id, $suggestion_type, $suggestion_content]);
                
                if ($result) {
                    $success_message = "Votre suggestion a été générée avec succès !";
                } else {
                    $errors[] = "Une erreur s'est produite lors de l'enregistrement de la suggestion. Veuillez réessayer.";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur s'est produite: " . $e->getMessage();
            error_log("Erreur dans ai-suggestions.php: " . $e->getMessage());
        }
    }
}

// Récupérer les suggestions existantes
$sql = "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y à %H:%i') as formatted_date FROM ai_suggestions 
        WHERE user_id = ? AND suggestion_type = ? 
        ORDER BY created_at DESC 
        LIMIT 10";
$suggestions = fetchAll($sql, [$user_id, $suggestion_type]);

// Traitement de l'application d'une suggestion
if (isset($_GET['action']) && $_GET['action'] === 'apply' && isset($_GET['id'])) {
    $suggestion_id = (int)$_GET['id'];
    
    // Vérifier si la suggestion appartient à l'utilisateur
    $sql = "SELECT * FROM ai_suggestions WHERE id = ? AND user_id = ?";
    $suggestion = fetchOne($sql, [$suggestion_id, $user_id]);
    
    if ($suggestion) {
        // Marquer la suggestion comme appliquée
        $sql = "UPDATE ai_suggestions SET is_applied = 1, updated_at = NOW() WHERE id = ?";
        $result = update($sql, [$suggestion_id]);
        
        if ($result) {
            $success_message = "La suggestion a été marquée comme appliquée !";
            
            // Récupérer les suggestions mises à jour
            $sql = "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y à %H:%i') as formatted_date FROM ai_suggestions 
                    WHERE user_id = ? AND suggestion_type = ? 
                    ORDER BY created_at DESC 
                    LIMIT 10";
            $suggestions = fetchAll($sql, [$user_id, $suggestion_type]);
        } else {
            $errors[] = "Une erreur s'est produite lors de la mise à jour de la suggestion. Veuillez réessayer.";
        }
    } else {
        $errors[] = "Suggestion non trouvée ou vous n'êtes pas autorisé à la modifier.";
    }
}

// Traitement de la suppression d'une suggestion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $suggestion_id = (int)$_GET['id'];
    
    // Vérifier si la suggestion appartient à l'utilisateur
    $sql = "SELECT * FROM ai_suggestions WHERE id = ? AND user_id = ?";
    $suggestion = fetchOne($sql, [$suggestion_id, $user_id]);
    
    if ($suggestion) {
        // Supprimer la suggestion
        $sql = "DELETE FROM ai_suggestions WHERE id = ?";
        $result = update($sql, [$suggestion_id]); // Utiliser update pour exécuter la requête DELETE
        
        if ($result) {
            $success_message = "La suggestion a été supprimée avec succès !";
            
            // Récupérer les suggestions mises à jour
            $sql = "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y à %H:%i') as formatted_date FROM ai_suggestions 
                    WHERE user_id = ? AND suggestion_type = ? 
                    ORDER BY created_at DESC 
                    LIMIT 10";
            $suggestions = fetchAll($sql, [$user_id, $suggestion_type]);
        } else {
            $errors[] = "Une erreur s'est produite lors de la suppression de la suggestion. Veuillez réessayer.";
        }
    } else {
        $errors[] = "Suggestion non trouvée ou vous n'êtes pas autorisé à la supprimer.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggestions IA - Weight Tracker</title>
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
                <a class="nav-link <?php echo $suggestion_type === 'repas' ? 'active' : ''; ?>" href="ai-suggestions.php?type=repas">
                    <i class="fas fa-utensils me-1"></i>Suggestions de repas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'exercice' ? 'active' : ''; ?>" href="ai-suggestions.php?type=exercice">
                    <i class="fas fa-running me-1"></i>Suggestions d'exercices
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'programme' ? 'active' : ''; ?>" href="ai-suggestions.php?type=programme">
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
                            <form method="POST" action="ai-suggestions.php?type=<?php echo htmlspecialchars($suggestion_type); ?>" id="suggestionForm">
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
                                <p class="mt-2">Génération de la suggestion en cours...</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <h6>Informations utilisées :</h6>
                            <ul class="small">
                                <?php if ($profile): ?>
                                    <li>Profil : <?php echo $profile['gender'] === 'homme' ? 'Homme' : 'Femme'; ?>, <?php echo isset($profile['birth_date']) ? (date('Y') - date('Y', strtotime($profile['birth_date']))) : '?'; ?> ans</li>
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
                                                    <?php if ($suggestion['is_applied']): ?>
                                                        <span class="badge bg-success ms-2">Appliqué</span>
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
                                                    <?php if (!$suggestion['is_applied']): ?>
                                                        <a href="ai-suggestions.php?type=<?php echo urlencode($suggestion_type); ?>&action=apply&id=<?php echo $suggestion['id']; ?>" class="btn btn-sm btn-success me-2">
                                                            <i class="fas fa-check me-1"></i>Marquer comme appliqué
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="ai-suggestions.php?type=<?php echo urlencode($suggestion_type); ?>&action=delete&id=<?php echo $suggestion['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette suggestion ?');">
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('suggestionForm');
            const generateButton = document.getElementById('generateButton');
            const loader = document.getElementById('loader');

            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Désactiver le bouton et afficher le loader
                    generateButton.disabled = true;
                    generateButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Génération en cours...';
                    loader.classList.remove('d-none');
                    
                    // Récupérer les données du formulaire
                    const formData = new FormData(form);
                    
                    // Envoyer la requête
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Recharger la page pour afficher la nouvelle suggestion
                        window.location.reload();
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Une erreur est survenue lors de la génération de la suggestion. Veuillez réessayer.');
                    })
                    .finally(() => {
                        // Réactiver le bouton et cacher le loader
                        generateButton.disabled = false;
                        generateButton.innerHTML = '<i class="fas fa-robot me-1"></i>Générer une suggestion';
                        loader.classList.add('d-none');
                    });
                });
            }
        });
    </script>
</body>
</html>
