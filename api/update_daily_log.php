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

// Récupération et validation des données
$logId = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
$weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
$date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
$notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
$userId = $_SESSION['user_id'];

if (!$logId || !$weight || !$date || $weight < 30 || $weight > 300) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Vérification que l'entrée appartient à l'utilisateur
    $checkStmt = $pdo->prepare("
        SELECT id FROM daily_logs 
        WHERE id = ? AND user_id = ?
    ");
    $checkStmt->execute([$logId, $userId]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }

    // Mise à jour de l'entrée
    $stmt = $pdo->prepare("
        UPDATE daily_logs 
        SET weight = ?, date = ?, notes = ?, updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$weight, $date, $notes, $logId, $userId]);

    // Si c'est l'entrée la plus récente, mettre à jour le poids actuel de l'utilisateur
    $latestStmt = $pdo->prepare("
        SELECT id 
        FROM daily_logs 
        WHERE user_id = ? 
        ORDER BY date DESC 
        LIMIT 1
    ");
    $latestStmt->execute([$userId]);
    $latestLog = $latestStmt->fetch();

    if ($latestLog && $latestLog['id'] == $logId) {
        $updateUserStmt = $pdo->prepare("
            UPDATE users 
            SET current_weight = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updateUserStmt->execute([$weight, $userId]);
    }

    echo json_encode(['success' => true, 'message' => 'Entrée modifiée avec succès']);

} catch (PDOException $e) {
    error_log("Erreur lors de la modification du poids : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
}
?> 