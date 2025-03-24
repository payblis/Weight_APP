<?php
require_once 'includes/config.php';
require_once 'includes/food_ai_manager.php';

// Vérification de la connexion
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données
$data = json_decode(file_get_contents('php://input'), true);
$mealType = $data['meal_type'] ?? '';
$preferences = $data['preferences'] ?? [];

// Validation
if (empty($mealType)) {
    http_response_code(400);
    echo json_encode(['error' => 'Type de repas requis']);
    exit;
}

try {
    $foodAIManager = new FoodAIManager($pdo);
    
    if ($mealType === 'all') {
        $suggestion = $foodAIManager->suggestDailyMeals($preferences);
    } else {
        $suggestion = $foodAIManager->suggestMeal($mealType, $preferences);
    }

    // Enregistrement de la suggestion dans la table ai_suggestions
    $stmt = $pdo->prepare("
        INSERT INTO ai_suggestions (user_id, type, content)
        VALUES (?, 'meal', ?)
    ");
    $stmt->execute([$_SESSION['user_id'], json_encode($suggestion)]);

    echo json_encode([
        'success' => true,
        'suggestion' => $suggestion
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 