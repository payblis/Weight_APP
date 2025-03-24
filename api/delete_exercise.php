<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

$user_id = $_SESSION['user_id'];
$exercise_id = intval($input['id']);

try {
    // Vérification que l'exercice appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM exercises WHERE id = ? AND user_id = ?");
    $stmt->execute([$exercise_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Exercice non trouvé']);
        exit;
    }

    // Suppression de l'exercice
    $stmt = $pdo->prepare("DELETE FROM exercises WHERE id = ? AND user_id = ?");
    $stmt->execute([$exercise_id, $user_id]);

    echo json_encode(['success' => true, 'message' => 'Exercice supprimé avec succès']);
} catch (PDOException $e) {
    error_log("Erreur SQL: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'exercice']);
}
?> 