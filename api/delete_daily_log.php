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
$logId = isset($data['log_id']) ? filter_var($data['log_id'], FILTER_VALIDATE_INT) : null;
$userId = $_SESSION['user_id'];

if (!$logId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

try {
    // Vérification que l'entrée appartient à l'utilisateur
    $checkStmt = $pdo->prepare("
        SELECT id, date FROM daily_logs 
        WHERE id = ? AND user_id = ?
    ");
    $checkStmt->execute([$logId, $userId]);
    $log = $checkStmt->fetch();
    
    if (!$log) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }

    // Suppression de l'entrée
    $stmt = $pdo->prepare("
        DELETE FROM daily_logs 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$logId, $userId]);

    // Si c'était l'entrée la plus récente, mettre à jour le poids actuel avec l'entrée précédente
    $latestStmt = $pdo->prepare("
        SELECT weight 
        FROM daily_logs 
        WHERE user_id = ? AND date <= ?
        ORDER BY date DESC 
        LIMIT 1
    ");
    $latestStmt->execute([$userId, $log['date']]);
    $latestWeight = $latestStmt->fetch();

    if ($latestWeight) {
        $updateUserStmt = $pdo->prepare("
            UPDATE users 
            SET current_weight = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updateUserStmt->execute([$latestWeight['weight'], $userId]);
    }

    echo json_encode(['success' => true, 'message' => 'Entrée supprimée avec succès']);

} catch (PDOException $e) {
    error_log("Erreur lors de la suppression du poids : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
}
?> 