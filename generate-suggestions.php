<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    error_log("❌ Utilisateur non connecté");
    $_SESSION['error_message'] = "Vous devez être connecté pour effectuer cette action";
    header('Location: my-coach.php');
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Requête non-POST détectée");
    $_SESSION['error_message'] = "Cette page ne peut être accédée que via un formulaire";
    header('Location: my-coach.php');
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$suggestion_type = $data['type'] ?? 'alimentation';

// Définir l'encodage UTF-8 pour les logs
mb_internal_encoding('UTF-8');

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT u.*, p.name as program_name, p.description as program_description 
        FROM users u 
        LEFT JOIN user_programs up ON u.id = up.user_id AND up.status = 'actif'
        LEFT JOIN programs p ON up.program_id = p.id 
        WHERE u.id = ?";
$user = fetchOne($sql, [$user_id]);
error_log("=== Début de la génération de suggestion ===");
error_log("User ID: " . $user_id);
error_log("Type de suggestion: " . $suggestion_type);

// Récupérer le profil de l'utilisateur
$sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$profile = fetchOne($sql, [$user_id]);
error_log("Profil utilisateur: " . print_r($profile, true));

// Récupérer le dernier poids enregistré
$sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
$latest_weight = fetchOne($sql, [$user_id]);
error_log("Dernier poids: " . print_r($latest_weight, true));

// Récupérer l'objectif de poids actuel
$sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);
error_log("Objectif actuel: " . print_r($current_goal, true));

// Récupérer le programme actif de l'utilisateur
$sql = "SELECT p.* FROM user_programs up 
        JOIN programs p ON up.program_id = p.id 
        WHERE up.user_id = ? AND up.status = 'actif' 
        ORDER BY up.created_at DESC LIMIT 1";
$active_program = fetchOne($sql, [$user_id]);
error_log("Programme actif: " . print_r($active_program, true));

// Récupérer les préférences alimentaires de l'utilisateur
$sql = "SELECT * FROM food_preferences WHERE user_id = ?";
$preferences = fetchAll($sql, [$user_id]);
error_log("Préférences alimentaires: " . print_r($preferences, true));

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

try {
    // Générer la suggestion en fonction du type
    $suggestion_content = '';
    
    switch ($suggestion_type) {
        case 'repas':
        case 'alimentation':
            error_log("=== Début de la génération de suggestion de repas ===");
            // Construire le prompt avec les informations de l'utilisateur
            $prompt = "En tant que coach nutritionnel, génère une suggestion de repas adaptée au profil suivant :
- Poids : {$profile['weight']} kg
- Taille : {$profile['height']} cm
- Âge : " . calculateAge($profile['birth_date']) . " ans
- Niveau d'activité : {$profile['activity_level']}
- Programme/Objectif : " . ($active_program ? $active_program['name'] : 'Aucun programme actif') . " (" . ($active_program ? $active_program['description'] : '') . ")

La suggestion doit être :
1. Adaptée aux besoins nutritionnels de l'utilisateur en fonction de son profil
2. Respecter les objectifs du programme choisi
3. Être équilibrée en macronutriments
4. Être réaliste et facile à préparer

Réponds uniquement avec un objet JSON contenant les champs suivants :
{
    \"nom_du_repas\": \"Nom du repas\",
    \"ingredients\": [
        {
            \"nom\": \"Nom de l'ingrédient\",
            \"quantite\": \"Quantité avec unité (ex: 200g, 1 tasse)\",
            \"calories\": nombre entier,
            \"proteines\": nombre décimal,
            \"glucides\": nombre décimal,
            \"lipides\": nombre décimal
        }
    ]
}

Les valeurs nutritionnelles doivent correspondre à la quantité exacte spécifiée pour chaque ingrédient.";
            
            error_log("Prompt généré : " . mb_convert_encoding($prompt, 'UTF-8', 'auto'));
            
            // Appeler l'API ChatGPT
            $api_key = getSetting('chatgpt_api_key');
            if (empty($api_key)) {
                error_log("❌ Clé API ChatGPT manquante");
                throw new Exception("La clé API ChatGPT n'est pas configurée");
            }
            
            $suggestion_content = callChatGPTAPI($prompt, $api_key);
            error_log("Réponse de l'API : " . mb_convert_encoding($suggestion_content, 'UTF-8', 'auto'));
            
            // Vérifier que le contenu est un JSON valide
            $json_data = json_decode($suggestion_content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("❌ Erreur de parsing JSON : " . json_last_error_msg());
                throw new Exception("La réponse de l'API n'est pas un JSON valide");
            }

            // Vérifier la structure minimale requise
            if (!isset($json_data['nom_du_repas']) || !isset($json_data['ingredients'])) {
                error_log("❌ Structure JSON invalide : " . print_r($json_data, true));
                throw new Exception("La réponse de l'API ne contient pas tous les champs requis");
            }

            // Calculer les totaux nutritionnels à partir des ingrédients
            $totals = [
                'calories' => 0,
                'proteines' => 0,
                'glucides' => 0,
                'lipides' => 0
            ];

            foreach ($json_data['ingredients'] as $ingredient) {
                $totals['calories'] += $ingredient['calories'];
                $totals['proteines'] += $ingredient['proteines'];
                $totals['glucides'] += $ingredient['glucides'];
                $totals['lipides'] += $ingredient['lipides'];
            }

            // Arrondir les valeurs
            $totals['calories'] = round($totals['calories']);
            $totals['proteines'] = round($totals['proteines'], 1);
            $totals['glucides'] = round($totals['glucides'], 1);
            $totals['lipides'] = round($totals['lipides'], 1);

            // Ajouter les totaux au JSON
            $json_data['valeurs_nutritionnelles'] = $totals;

            // Reconvertir en JSON pour le stockage
            $suggestion_content = json_encode($json_data, JSON_UNESCAPED_UNICODE);
            error_log("JSON final à stocker : " . mb_convert_encoding($suggestion_content, 'UTF-8', 'auto'));
            break;
            
        default:
            throw new Exception("Type de suggestion non supporté");
    }
    
    // Stocker la suggestion dans la base de données
    if (!empty($suggestion_content)) {
        error_log("=== Début de l'insertion de la suggestion ===");
        error_log("User ID: " . $user_id);
        error_log("Type de suggestion: " . $suggestion_type);
        error_log("Contenu à insérer: " . mb_convert_encoding($suggestion_content, 'UTF-8', 'auto'));
        
        $sql = "INSERT INTO ai_suggestions (user_id, suggestion_type, content, created_at) VALUES (?, ?, ?, NOW())";
        error_log("SQL Query: " . $sql);
        error_log("Paramètres: " . print_r([$user_id, $suggestion_type, $suggestion_content], true));
        
        $suggestion_id = insert($sql, [$user_id, $suggestion_type, $suggestion_content]);
        
        if ($suggestion_id) {
            error_log("✅ Suggestion insérée avec succès. ID: " . $suggestion_id);
            // Vérifier que la suggestion a bien été insérée
            $check_sql = "SELECT * FROM ai_suggestions WHERE id = ?";
            $check_result = fetchOne($check_sql, [$suggestion_id]);
            error_log("Vérification de l'insertion: " . print_r($check_result, true));
            
            // Renvoyer une réponse JSON de succès
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Suggestion générée avec succès',
                'suggestion_id' => $suggestion_id,
                'content' => json_decode($suggestion_content, true)
            ]);
            exit;
        } else {
            error_log("❌ Erreur lors de l'insertion de la suggestion");
            error_log("Dernière erreur SQL: " . getLastError());
            throw new Exception("Erreur lors de l'enregistrement de la suggestion");
        }
    } else {
        error_log("❌ Aucune suggestion générée");
        throw new Exception("Aucune suggestion générée");
    }
    
} catch (Exception $e) {
    error_log("❌ Erreur dans generate-suggestions.php: " . mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto'));
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} 