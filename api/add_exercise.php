<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$user_id = $_SESSION['user_id'];
$required_fields = ['exercise_type_id', 'time', 'duration', 'intensity', 'date'];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'Champs requis manquants']);
        exit;
    }
}

try {
    // Création de la date complète
    $date = date('Y-m-d H:i:s', strtotime($_POST['date'] . ' ' . $_POST['time']));
    
    $stmt = $pdo->prepare("
        INSERT INTO exercises (
            user_id, 
            exercise_type_id, 
            date, 
            duration, 
            intensity, 
            notes
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        intval($_POST['exercise_type_id']),
        $date,
        intval($_POST['duration']),
        intval($_POST['intensity']),
        $_POST['notes'] ?? null
    ]);

    echo json_encode(['success' => true, 'message' => 'Exercice ajouté avec succès']);
} catch (PDOException $e) {
    error_log("Erreur SQL: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'exercice']);
}
?> 