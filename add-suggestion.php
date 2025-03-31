<?php
require_once 'includes/init.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['suggestion_id']) || !isset($data['meal_type']) || !isset($data['log_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
    // Ajouter la suggestion comme repas
    $result = addSuggestionAsMeal($_SESSION['user_id'], $data['suggestion_id'], $data['log_date']);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => $result['message']]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
} catch (Exception $e) {
    error_log("Erreur dans add-suggestion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'ajout du repas']);
} 