<?php
require_once '../includes/config.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validation des données
$mealFoodId = isset($data['meal_food_id']) ? filter_var($data['meal_food_id'], FILTER_VALIDATE_INT) : null;
$quantity = isset($data['quantity']) ? filter_var($data['quantity'], FILTER_VALIDATE_FLOAT) : null;

if (!$mealFoodId || !$quantity || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Vérification que l'entrée appartient à l'utilisateur
    $checkStmt = $pdo->prepare("
        SELECT mf.id 
        FROM meal_foods mf
        JOIN meals m ON mf.meal_id = m.id
        WHERE mf.id = ? AND m.user_id = ?
    ");
    $checkStmt->execute([$mealFoodId, $_SESSION['user_id']]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }

    // Mise à jour de la quantité
    $updateStmt = $pdo->prepare("
        UPDATE meal_foods 
        SET quantity = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$quantity, $mealFoodId]);

    echo json_encode(['success' => true, 'message' => 'Quantité mise à jour avec succès']);

} catch (PDOException $e) {
    error_log("Erreur lors de la mise à jour de la quantité : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}
?> 