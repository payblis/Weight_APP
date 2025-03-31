<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']);
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
$suggestion_id = $data['id'] ?? null;

if (!$suggestion_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de suggestion manquant']);
    exit;
}

try {
    // Vérifier que la suggestion appartient à l'utilisateur
    $sql = "SELECT id FROM ai_suggestions WHERE id = ? AND user_id = ?";
    $suggestion = fetchOne($sql, [$suggestion_id, $_SESSION['user_id']]);
    
    if (!$suggestion) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Suggestion non trouvée']);
        exit;
    }
    
    // Supprimer la suggestion
    $sql = "DELETE FROM ai_suggestions WHERE id = ? AND user_id = ?";
    $result = execute($sql, [$suggestion_id, $_SESSION['user_id']]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Suggestion supprimée avec succès']);
    } else {
        throw new Exception("Erreur lors de la suppression de la suggestion");
    }
} catch (Exception $e) {
    error_log("Erreur dans delete-suggestion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la suppression']);
} 