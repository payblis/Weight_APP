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

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'appel à l\'API ChatGPT']);
    exit;
}

$result = json_decode($response, true);

if (!isset($result['choices'][0]['message']['content'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Réponse invalide de l\'API ChatGPT']);
    exit;
}

// Parser la réponse JSON de ChatGPT
$program_data = json_decode($result['choices'][0]['message']['content'], true);

if (!$program_data) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Format de réponse invalide']);
    exit;
}

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

// Vérifier que la somme des ratios est égale à 1
$total_ratio = $program['protein_ratio'] + $program['carbs_ratio'] + $program['fat_ratio'];
if (abs($total_ratio - 1) > 0.01) {
    // Ajuster les ratios pour qu'ils totalisent 1
    $program['protein_ratio'] /= $total_ratio;
    $program['carbs_ratio'] /= $total_ratio;
    $program['fat_ratio'] /= $total_ratio;
}

echo json_encode(['success' => true, 'program' => $program]); 