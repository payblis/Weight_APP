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

IMPORTANT: Réponds UNIQUEMENT avec un objet JSON valide contenant ces informations, sans texte supplémentaire. Le format doit être exactement comme ceci:
{
    \"name\": \"Nom du programme\",
    \"description\": \"Description détaillée\",
    \"type\": \"complet\",
    \"calorie_adjustment\": -10,
    \"protein_ratio\": 0.3,
    \"carbs_ratio\": 0.4,
    \"fat_ratio\": 0.3
}";

$user_prompt = "Crée un programme basé sur cette description : " . $prompt;

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

if (!isset($result['choices'][0]['message']['content'])) {
    error_log("=== ERREUR DE STRUCTURE DE RÉPONSE ===");
    error_log("Structure reçue: " . print_r($result, true));
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Structure de réponse invalide de l\'API ChatGPT']);
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