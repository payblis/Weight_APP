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

if (!$foodId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

try {
    // Vérification que l'aliment appartient à l'utilisateur
    $checkStmt = $pdo->prepare("
        SELECT id FROM foods 
        WHERE id = ? AND user_id = ?
    ");
    $checkStmt->execute([$foodId, $_SESSION['user_id']]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }

    // Début de la transaction
    $pdo->beginTransaction();

    // Vérification si l'aliment est utilisé dans des repas
    $usageStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM meal_foods 
        WHERE food_id = ?
    ");
    $usageStmt->execute([$foodId]);
    $usageCount = $usageStmt->fetchColumn();

    if ($usageCount > 0) {
        // Si l'aliment est utilisé, on le marque comme supprimé au lieu de le supprimer
        $stmt = $pdo->prepare("
            UPDATE foods 
            SET deleted_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$foodId, $_SESSION['user_id']]);
    } else {
        // Si l'aliment n'est pas utilisé, on peut le supprimer définitivement
        $stmt = $pdo->prepare("
            DELETE FROM foods 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$foodId, $_SESSION['user_id']]);
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