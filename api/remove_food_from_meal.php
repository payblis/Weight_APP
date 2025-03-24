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

if (!$mealFoodId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

try {
    // Vérification que l'entrée appartient à l'utilisateur
    $checkStmt = $pdo->prepare("
        SELECT mf.id, m.id as meal_id
        FROM meal_foods mf
        JOIN meals m ON mf.meal_id = m.id
        WHERE mf.id = ? AND m.user_id = ?
    ");
    $checkStmt->execute([$mealFoodId, $_SESSION['user_id']]);
    $mealFood = $checkStmt->fetch();
    
    if (!$mealFood) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }

    // Début de la transaction
    $pdo->beginTransaction();

    // Suppression de l'aliment du repas
    $deleteStmt = $pdo->prepare("
        DELETE FROM meal_foods 
        WHERE id = ?
    ");
    $deleteStmt->execute([$mealFoodId]);

    // Vérification s'il reste des aliments dans le repas
    $checkMealStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM meal_foods 
        WHERE meal_id = ?
    ");
    $checkMealStmt->execute([$mealFood['meal_id']]);
    $remainingFoods = $checkMealStmt->fetchColumn();

    // Si le repas est vide, on le supprime
    if ($remainingFoods == 0) {
        $deleteMealStmt = $pdo->prepare("
            DELETE FROM meals 
            WHERE id = ?
        ");
        $deleteMealStmt->execute([$mealFood['meal_id']]);
    }

    // Validation de la transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Aliment supprimé avec succès']);

} catch (PDOException $e) {
    // Annulation de la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur lors de la suppression de l'aliment : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la suppression de l'aliment"]);
}
?> 