<?php
require_once '../includes/config.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupération et validation de l'ID
$foodId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$foodId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

try {
    // Récupération des informations de l'aliment
    $stmt = $pdo->prepare("
        SELECT * FROM foods 
        WHERE id = ? AND (user_id = ? OR user_id IS NULL)
    ");
    $stmt->execute([$foodId, $_SESSION['user_id']]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$food) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Aliment non trouvé']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $food
    ]);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération de l'aliment : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération de l'aliment"]);
}
?> 