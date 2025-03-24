<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('ID du repas manquant');
    }

    $meal_id = $data['id'];
    $user_id = $_SESSION['user_id'];

    // Vérification que le repas appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM meals WHERE id = ? AND user_id = ?");
    $stmt->execute([$meal_id, $user_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Repas non trouvé');
    }

    // Début de la transaction
    $pdo->beginTransaction();

    // Suppression des aliments du repas
    $stmt = $pdo->prepare("DELETE FROM meal_foods WHERE meal_id = ?");
    $stmt->execute([$meal_id]);

    // Suppression du repas
    $stmt = $pdo->prepare("DELETE FROM meals WHERE id = ?");
    $stmt->execute([$meal_id]);

    // Validation de la transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Repas supprimé avec succès']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur lors de la suppression du repas : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 