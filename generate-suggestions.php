<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$suggestion_type = $data['type'] ?? 'repas';

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];

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

try {
    // Générer la suggestion en fonction du type
    $suggestion_content = '';
    
    switch ($suggestion_type) {
        case 'repas':
        case 'alimentation':
            error_log("=== Début de la génération de suggestion de repas ===");
            // Construire le prompt pour ChatGPT
            $prompt = "En tant que nutritionniste expert, génère une suggestion de repas équilibré en tenant compte des informations suivantes :\n\n";
            
            if ($profile) {
                $prompt .= "Profil : " . ($profile['gender'] === 'homme' ? 'Homme' : 'Femme') . ", " . 
                          (date('Y') - date('Y', strtotime($profile['birth_date']))) . " ans\n";
            }
            
            if ($latest_weight) {
                $prompt .= "Poids actuel : " . number_format($latest_weight['weight'], 1) . " kg\n";
            }
            
            if ($current_goal) {
                $prompt .= "Objectif de poids : " . number_format($current_goal['target_weight'], 1) . " kg\n";
            }
            
            if ($active_program) {
                $prompt .= "Programme actif : " . $active_program['name'] . "\n";
            }
            
            if (!empty($favorite_foods)) {
                $prompt .= "Aliments préférés : " . implode(", ", $favorite_foods) . "\n";
            }
            
            if (!empty($blacklisted_foods)) {
                $prompt .= "Aliments à éviter : " . implode(", ", $blacklisted_foods) . "\n";
            }
            
            $prompt .= "\nVeuillez fournir une réponse au format JSON avec la structure suivante :\n";
            $prompt .= "{\n";
            $prompt .= "  \"nom_du_repas\": \"Nom du repas\",\n";
            $prompt .= "  \"valeurs_nutritionnelles\": {\n";
            $prompt .= "    \"calories\": 0,\n";
            $prompt .= "    \"proteines\": 0,\n";
            $prompt .= "    \"glucides\": 0,\n";
            $prompt .= "    \"lipides\": 0\n";
            $prompt .= "  },\n";
            $prompt .= "  \"ingredients\": [\n";
            $prompt .= "    {\n";
            $prompt .= "      \"nom\": \"Nom de l'ingrédient\",\n";
            $prompt .= "      \"quantite\": \"Quantité\",\n";
            $prompt .= "      \"calories\": 0,\n";
            $prompt .= "      \"proteines\": 0,\n";
            $prompt .= "      \"glucides\": 0,\n";
            $prompt .= "      \"lipides\": 0\n";
            $prompt .= "    }\n";
            $prompt .= "  ],\n";
            $prompt .= "  \"instructions\": [\n";
            $prompt .= "    \"Instruction 1\",\n";
            $prompt .= "    \"Instruction 2\"\n";
            $prompt .= "  ]\n";
            $prompt .= "}\n";
            
            error_log("Prompt généré : " . $prompt);
            
            // Appeler l'API ChatGPT
            $api_key = getSetting('chatgpt_api_key');
            if (empty($api_key)) {
                throw new Exception("La clé API ChatGPT n'est pas configurée");
            }
            
            $suggestion_content = callChatGPTAPI($prompt, $api_key);
            error_log("Réponse de l'API : " . $suggestion_content);
            
            // Vérifier que le contenu est un JSON valide
            $json_data = json_decode($suggestion_content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Erreur de parsing JSON : " . json_last_error_msg());
                throw new Exception("La réponse de l'API n'est pas un JSON valide");
            }
            break;
            
        default:
            throw new Exception("Type de suggestion non supporté");
    }
    
    // Stocker la suggestion dans la base de données
    if (!empty($suggestion_content)) {
        error_log("Insertion de la suggestion dans la base de données");
        $sql = "INSERT INTO ai_suggestions (user_id, suggestion_type, content, created_at) VALUES (?, ?, ?, NOW())";
        $result = insert($sql, [$user_id, $suggestion_type, $suggestion_content]);
        
        if ($result) {
            error_log("Suggestion insérée avec succès");
            // Rediriger vers my-coach.php avec un message de succès
            $_SESSION['success_message'] = "Suggestion générée avec succès";
            header('Location: my-coach.php');
            exit;
        } else {
            error_log("Erreur lors de l'insertion de la suggestion");
            throw new Exception("Erreur lors de l'enregistrement de la suggestion");
        }
    } else {
        error_log("Aucune suggestion générée");
        throw new Exception("Aucune suggestion générée");
    }
    
} catch (Exception $e) {
    error_log("Erreur dans generate-suggestions.php: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: my-coach.php');
    exit;
} 