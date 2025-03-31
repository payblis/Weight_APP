<?php
session_start();
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier si l'ID du repas est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de repas invalide']);
    exit;
}

$meal_id = intval($_GET['id']);

try {
    // Récupérer les détails du repas
    $sql = "SELECT * FROM predefined_meals WHERE id = ?";
    $meal = fetchOne($sql, [$meal_id]);
    
    if (!$meal) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Repas non trouvé']);
        exit;
    }
    
    // Récupérer les aliments associés
    $sql = "SELECT food_id, quantity FROM predefined_meal_foods WHERE predefined_meal_id = ?";
    $meal['foods'] = fetchAll($sql, [$meal_id]);
    
    echo json_encode(['success' => true, 'meal' => $meal]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du repas']);
} 