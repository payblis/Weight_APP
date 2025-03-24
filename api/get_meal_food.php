<?php
require_once '../includes/config.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupération et validation de l'ID
$mealFoodId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$mealFoodId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

try {
    // Récupération des informations de l'aliment dans le repas
    $stmt = $pdo->prepare("
        SELECT mf.*, f.name, f.brand, f.calories, f.proteins, f.carbs, f.fats
        FROM meal_foods mf
        JOIN meals m ON mf.meal_id = m.id
        JOIN foods f ON mf.food_id = f.id
        WHERE mf.id = ? AND m.user_id = ?
    ");
    $stmt->execute([$mealFoodId, $_SESSION['user_id']]);
    $mealFood = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mealFood) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Aliment non trouvé']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $mealFood
    ]);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération de l'aliment : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération de l'aliment"]);
}
?> 