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

// Préparer le prompt pour ChatGPT
$system_prompt = "Tu es un expert en nutrition et en fitness. Crée un programme personnalisé basé sur la description fournie. 
Le programme doit inclure :
- Un nom approprié
- Une description détaillée
- Un type (complet, nutrition, ou exercice)
- Un ajustement calorique en pourcentage (-20 à +20)
- Une répartition des macronutriments (protéines, glucides, lipides) en pourcentage

Réponds uniquement avec un objet JSON contenant ces informations.";

$user_prompt = "Crée un programme basé sur cette description : " . $prompt;

// Appeler l'API ChatGPT
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $user_prompt]
    ],
    'temperature' => 0.7
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);

// Log de la requête
error_log("Requête envoyée à l'API ChatGPT: " . json_encode([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $user_prompt]
    ],
    'temperature' => 0.7
]));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Log de la réponse brute et des erreurs cURL
error_log("Code HTTP de la réponse: " . $http_code);
error_log("Erreur cURL (si présente): " . $curl_error);
error_log("Réponse brute de l'API ChatGPT: " . $response);

if ($curl_error) {
    error_log("Erreur cURL: " . $curl_error);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à l\'API ChatGPT: ' . $curl_error]);
    exit;
}

if ($http_code !== 200) {
    error_log("Erreur HTTP lors de l'appel à l'API ChatGPT: " . $http_code);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'appel à l\'API ChatGPT (HTTP ' . $http_code . ')']);
    exit;
}

$result = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Erreur de décodage JSON: " . json_last_error_msg());
    error_log("Réponse qui a causé l'erreur: " . $response);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Réponse invalide de l\'API ChatGPT: ' . json_last_error_msg()]);
    exit;
}

if (!isset($result['choices'][0]['message']['content'])) {
    error_log("Structure de réponse invalide: " . print_r($result, true));
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Structure de réponse invalide de l\'API ChatGPT']);
    exit;
}

// Log du contenu de la réponse
$content = $result['choices'][0]['message']['content'];
error_log("Contenu de la réponse ChatGPT: " . $content);

// Parser la réponse JSON de ChatGPT
$program_data = json_decode($content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Erreur de décodage JSON du programme: " . json_last_error_msg());
    error_log("Contenu qui a causé l'erreur: " . $content);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Format de programme invalide: ' . json_last_error_msg()]);
    exit;
}

if (!$program_data) {
    error_log("Programme data est null ou vide");
    error_log("Contenu reçu: " . $content);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Format de réponse invalide']);
    exit;
}

// Log des données du programme
error_log("Données du programme: " . print_r($program_data, true));

// Valider et formater les données
$program = [
    'name' => $program_data['name'] ?? '',
    'description' => $program_data['description'] ?? '',
    'type' => $program_data['type'] ?? 'complet',
    'calorie_adjustment' => floatval($program_data['calorie_adjustment'] ?? 0),
    'protein_ratio' => floatval($program_data['protein_ratio'] ?? 0.3),
    'carbs_ratio' => floatval($program_data['carbs_ratio'] ?? 0.4),
    'fat_ratio' => floatval($program_data['fat_ratio'] ?? 0.3)
];

// Log du programme formaté
error_log("Programme formaté: " . print_r($program, true));

// Vérifier que la somme des ratios est égale à 1
$total_ratio = $program['protein_ratio'] + $program['carbs_ratio'] + $program['fat_ratio'];
if (abs($total_ratio - 1) > 0.01) {
    // Ajuster les ratios pour qu'ils totalisent 1
    $program['protein_ratio'] /= $total_ratio;
    $program['carbs_ratio'] /= $total_ratio;
    $program['fat_ratio'] /= $total_ratio;
}

// Log de la réponse finale
$final_response = ['success' => true, 'program' => $program];
error_log("Réponse finale: " . json_encode($final_response));

// S'assurer que la réponse est bien encodée en JSON
$json_response = json_encode($final_response);
if ($json_response === false) {
    error_log("Erreur d'encodage JSON de la réponse finale: " . json_last_error_msg());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'encodage de la réponse']);
    exit;
}

echo $json_response; 