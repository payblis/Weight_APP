<?php
require_once 'config/database.php';
require_once 'functions.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isLoggedIn() || !isAdmin($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$prompt = $data['prompt'] ?? '';

if (empty($prompt)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Le prompt est requis']);
    exit;
}

// Récupérer la clé API ChatGPT
try {
    $sql = "SELECT setting_value FROM settings WHERE setting_name = 'chatgpt_api_key'";
    $result = fetchOne($sql, []);
    $api_key = $result ? $result['setting_value'] : '';
    
    if (empty($api_key)) {
        throw new Exception('Clé API ChatGPT non configurée');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// Fonction pour appeler l'API ChatGPT
function callChatGPTAPI($prompt, $api_key) {
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
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout après 30 secondes
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactiver la vérification SSL pour le débogage
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        error_log("Erreur cURL: " . $curl_error);
        return false;
    }
    
    if ($http_code !== 200) {
        error_log("Erreur API ChatGPT (HTTP $http_code): " . $response);
        return false;
    }
    
    $response_data = json_decode($response, true);
    
    if (!isset($response_data['choices'][0]['message']['content'])) {
        error_log("Réponse invalide de l'API ChatGPT: " . $response);
        return false;
    }
    
    return $response_data['choices'][0]['message']['content'];
}

// Fonction pour générer un programme avec ChatGPT
function generateProgramWithChatGPT($program_name, $program_description, $program_type, $api_key) {
    if (empty($api_key)) {
        return [
            'success' => false,
            'error' => "La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur."
        ];
    }
    
    // Construire le prompt pour ChatGPT
    $prompt = "En tant qu'expert en nutrition et fitness, génère un programme détaillé avec les informations suivantes :\n\n";
    $prompt .= "Nom du programme : " . $program_name . "\n";
    $prompt .= "Description : " . $program_description . "\n";
    $prompt .= "Type de programme : " . $program_type . "\n\n";
    
    $prompt .= "Génère un programme complet avec :\n";
    $prompt .= "1. Objectifs du programme\n";
    $prompt .= "2. Recommandations nutritionnelles\n";
    $prompt .= "3. Programme d'exercices\n";
    $prompt .= "4. Conseils et astuces\n\n";
    
    $prompt .= "Format de réponse souhaité :\n";
    $prompt .= "OBJECTIFS\n";
    $prompt .= "- Liste des objectifs\n\n";
    $prompt .= "NUTRITION\n";
    $prompt .= "- Recommandations alimentaires\n";
    $prompt .= "- Exemples de repas\n";
    $prompt .= "- Conseils nutritionnels\n\n";
    $prompt .= "EXERCICES\n";
    $prompt .= "- Programme d'entraînement\n";
    $prompt .= "- Séries et répétitions\n";
    $prompt .= "- Temps de repos\n\n";
    $prompt .= "CONSEILS\n";
    $prompt .= "- Astuces pour le succès\n";
    $prompt .= "- Points d'attention\n";
    $prompt .= "- Progression recommandée";
    
    // Appeler l'API ChatGPT
    $response = callChatGPTAPI($prompt, $api_key);
    
    if ($response === false) {
        return [
            'success' => false,
            'error' => "Une erreur s'est produite lors de la génération du programme. Veuillez réessayer plus tard."
        ];
    }
    
    return [
        'success' => true,
        'content' => $response
    ];
}

// Traitement de la demande de génération de programme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_name = sanitizeInput($_POST['program_name'] ?? '');
    $program_description = sanitizeInput($_POST['program_description'] ?? '');
    $program_type = sanitizeInput($_POST['program_type'] ?? 'complet');
    
    if (empty($program_name) || empty($program_description)) {
        $errors[] = "Le nom et la description du programme sont requis.";
    } else {
        try {
            // Générer le contenu du programme avec ChatGPT
            $program_content = generateProgramWithChatGPT($program_name, $program_description, $program_type, $api_key);
            
            if (!$program_content['success']) {
                $errors[] = $program_content['error'];
            } else {
                // Insérer le programme dans la base de données
                $sql = "INSERT INTO programs (name, description, type, content, created_at) VALUES (?, ?, ?, ?, NOW())";
                $result = insert($sql, [$program_name, $program_description, $program_type, $program_content['content']]);
                
                if ($result) {
                    $success_message = "Le programme a été généré avec succès !";
                } else {
                    $errors[] = "Une erreur s'est produite lors de l'enregistrement du programme. Veuillez réessayer.";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur s'est produite: " . $e->getMessage();
            error_log("Erreur dans generate_program.php: " . $e->getMessage());
        }
    }
}

// Log des prompts
error_log("=== DÉBUT DE LA GÉNÉRATION DE PROGRAMME ===");
error_log("Prompt système: " . $system_prompt);
error_log("Prompt utilisateur: " . $user_prompt);
error_log("Clé API ChatGPT: " . substr($api_key, 0, 4) . "..." . substr($api_key, -4));

// Préparer la requête
$request_data = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $user_prompt]
    ],
    'temperature' => 0.7,
    'max_tokens' => 500
];

// Log de la requête complète
error_log("Données de la requête: " . json_encode($request_data, JSON_PRETTY_PRINT));

// Appeler l'API ChatGPT
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout après 30 secondes
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactiver la vérification SSL pour le débogage

// Log des options cURL
error_log("Options cURL configurées:");
error_log("- URL: https://api.openai.com/v1/chat/completions");
error_log("- Content-Type: application/json");
error_log("- Authorization: Bearer " . substr($api_key, 0, 4) . "..." . substr($api_key, -4));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
$curl_info = curl_getinfo($ch);
curl_close($ch);

// Log détaillé de la réponse
error_log("=== RÉPONSE DE L'API CHATGPT ===");
error_log("Code HTTP: " . $http_code);
error_log("Temps de réponse: " . $curl_info['total_time'] . " secondes");
error_log("Taille de la réponse: " . $curl_info['size_download'] . " octets");
error_log("Erreur cURL (si présente): " . $curl_error);
error_log("Réponse brute: " . $response);

if ($curl_error) {
    error_log("=== ERREUR CURL ===");
    error_log("Message d'erreur: " . $curl_error);
    error_log("Code d'erreur: " . $curl_info['curl_error']);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à l\'API ChatGPT: ' . $curl_error]);
    exit;
}

if ($http_code !== 200) {
    error_log("=== ERREUR HTTP ===");
    error_log("Code HTTP reçu: " . $http_code);
    error_log("Réponse complète: " . $response);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'appel à l\'API ChatGPT (HTTP ' . $http_code . ')']);
    exit;
}

if (empty($response)) {
    error_log("=== ERREUR RÉPONSE VIDE ===");
    error_log("La réponse de l'API est vide");
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'La réponse de l\'API est vide']);
    exit;
}

// Vérifier si la réponse est un JSON valide
$result = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("=== ERREUR DE DÉCODAGE JSON ===");
    error_log("Message d'erreur: " . json_last_error_msg());
    error_log("Code d'erreur: " . json_last_error());
    error_log("Réponse qui a causé l'erreur: " . $response);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Réponse invalide de l\'API ChatGPT: ' . json_last_error_msg()]);
    exit;
}

// Vérifier la structure de la réponse
if (!isset($result['choices']) || !is_array($result['choices']) || empty($result['choices'])) {
    error_log("=== ERREUR DE STRUCTURE DE RÉPONSE ===");
    error_log("Structure reçue: " . print_r($result, true));
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Structure de réponse invalide de l\'API ChatGPT']);
    exit;
}

if (!isset($result['choices'][0]['message']['content'])) {
    error_log("=== ERREUR DE CONTENU DE RÉPONSE ===");
    error_log("Structure reçue: " . print_r($result, true));
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Contenu de réponse manquant dans l\'API ChatGPT']);
    exit;
}

// Log du contenu de la réponse
$content = $result['choices'][0]['message']['content'];
error_log("=== CONTENU DE LA RÉPONSE ===");
error_log("Contenu brut: " . $content);

// Nettoyer le contenu pour s'assurer qu'il ne contient que du JSON valide
$content = trim($content);
if (strpos($content, '```json') !== false) {
    $content = preg_replace('/```json\s*|\s*```/', '', $content);
}
$content = trim($content);

// Parser la réponse JSON de ChatGPT
$program_data = json_decode($content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("=== ERREUR DE DÉCODAGE JSON DU PROGRAMME ===");
    error_log("Message d'erreur: " . json_last_error_msg());
    error_log("Code d'erreur: " . json_last_error());
    error_log("Contenu qui a causé l'erreur: " . $content);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Format de programme invalide: ' . json_last_error_msg()]);
    exit;
}

if (!$program_data) {
    error_log("=== ERREUR DE DONNÉES NULLES ===");
    error_log("Programme data est null ou vide");
    error_log("Contenu reçu: " . $content);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Format de réponse invalide']);
    exit;
}

// Vérifier que tous les champs requis sont présents
$required_fields = ['name', 'description', 'type', 'calorie_adjustment', 'protein_ratio', 'carbs_ratio', 'fat_ratio'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (!isset($program_data[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    error_log("=== ERREUR CHAMPS MANQUANTS ===");
    error_log("Champs manquants: " . implode(', ', $missing_fields));
    error_log("Données reçues: " . print_r($program_data, true));
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Champs manquants dans la réponse: ' . implode(', ', $missing_fields)]);
    exit;
}

// Log des données du programme
error_log("=== DONNÉES DU PROGRAMME ===");
error_log("Données brutes: " . print_r($program_data, true));

// Valider et formater les données
$program = [
    'name' => trim($program_data['name']),
    'description' => trim($program_data['description']),
    'type' => trim($program_data['type']),
    'calorie_adjustment' => floatval($program_data['calorie_adjustment']),
    'protein_ratio' => floatval($program_data['protein_ratio']),
    'carbs_ratio' => floatval($program_data['carbs_ratio']),
    'fat_ratio' => floatval($program_data['fat_ratio'])
];

// Log du programme formaté
error_log("=== PROGRAMME FORMATÉ ===");
error_log("Données formatées: " . print_r($program, true));

// Vérifier que la somme des ratios est égale à 1
$total_ratio = $program['protein_ratio'] + $program['carbs_ratio'] + $program['fat_ratio'];
if (abs($total_ratio - 1) > 0.01) {
    error_log("=== AJUSTEMENT DES RATIOS ===");
    error_log("Total des ratios avant ajustement: " . $total_ratio);
    // Ajuster les ratios pour qu'ils totalisent 1
    $program['protein_ratio'] /= $total_ratio;
    $program['carbs_ratio'] /= $total_ratio;
    $program['fat_ratio'] /= $total_ratio;
    error_log("Ratios après ajustement: " . print_r([
        'protein' => $program['protein_ratio'],
        'carbs' => $program['carbs_ratio'],
        'fat' => $program['fat_ratio']
    ], true));
}

// Log de la réponse finale
$final_response = ['success' => true, 'program' => $program];
error_log("=== RÉPONSE FINALE ===");
error_log("Réponse finale: " . json_encode($final_response, JSON_PRETTY_PRINT));

// S'assurer que la réponse est bien encodée en JSON
$json_response = json_encode($final_response);
if ($json_response === false) {
    error_log("=== ERREUR D'ENCODAGE JSON FINAL ===");
    error_log("Message d'erreur: " . json_last_error_msg());
    error_log("Code d'erreur: " . json_last_error());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'encodage de la réponse']);
    exit;
}

error_log("=== FIN DE LA GÉNÉRATION DE PROGRAMME ===");
echo $json_response; 