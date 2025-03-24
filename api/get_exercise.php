<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

$exercise_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT e.*, et.name as exercise_type_name
        FROM exercises e
        JOIN exercise_types et ON e.exercise_type_id = et.id
        WHERE e.id = ? AND e.user_id = ?
    ");
    $stmt->execute([$exercise_id, $user_id]);
    $exercise = $stmt->fetch();

    if ($exercise) {
        echo json_encode(['success' => true, 'exercise' => $exercise]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Exercice non trouvé']);
    }
} catch (PDOException $e) {
    error_log("Erreur SQL: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération de l\'exercice']);
}
?> 