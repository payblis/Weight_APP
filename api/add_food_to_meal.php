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
$foodId = isset($data['food_id']) ? filter_var($data['food_id'], FILTER_VALIDATE_INT) : null;
$mealType = isset($data['meal_type']) ? $data['meal_type'] : null;
$quantity = isset($data['quantity']) ? filter_var($data['quantity'], FILTER_VALIDATE_FLOAT) : null;
$date = isset($data['date']) ? $data['date'] : date('Y-m-d');
$userId = $_SESSION['user_id'];

if (!$foodId || !$mealType || !$quantity || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Vérification que l'aliment existe
    $foodStmt = $pdo->prepare("
        SELECT id FROM foods 
        WHERE id = ? AND (user_id IS NULL OR user_id = ?)
    ");
    $foodStmt->execute([$foodId, $userId]);
    
    if (!$foodStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Aliment non trouvé']);
        exit;
    }

    // Début de la transaction
    $pdo->beginTransaction();

    // Recherche d'un repas existant pour ce type et cette date
    $mealStmt = $pdo->prepare("
        SELECT id 
        FROM meals 
        WHERE user_id = ? AND meal_type = ? AND DATE(time) = ?
    ");
    $mealStmt->execute([$userId, $mealType, $date]);
    $meal = $mealStmt->fetch();

    // Si le repas n'existe pas, on le crée
    if (!$meal) {
        $createMealStmt = $pdo->prepare("
            INSERT INTO meals (user_id, meal_type, time, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $createMealStmt->execute([$userId, $mealType, $date . ' ' . date('H:i:s')]);
        $mealId = $pdo->lastInsertId();
    } else {
        $mealId = $meal['id'];
    }

    // Ajout de l'aliment au repas
    $addFoodStmt = $pdo->prepare("
        INSERT INTO meal_foods (meal_id, food_id, quantity, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ");
    $addFoodStmt->execute([$mealId, $foodId, $quantity]);

    // Validation de la transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Aliment ajouté avec succès']);

} catch (PDOException $e) {
    // Annulation de la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur lors de l'ajout de l'aliment : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de l'ajout de l'aliment"]);
}
?> 