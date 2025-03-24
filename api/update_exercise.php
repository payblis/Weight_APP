<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$user_id = $_SESSION['user_id'];
$required_fields = ['id', 'exercise_type_id', 'time', 'duration', 'intensity', 'date'];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'Champs requis manquants']);
        exit;
    }
}

try {
    // Vérification que l'exercice appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM exercises WHERE id = ? AND user_id = ?");
    $stmt->execute([intval($_POST['id']), $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Exercice non trouvé']);
        exit;
    }

    // Création de la date complète
    $date = date('Y-m-d H:i:s', strtotime($_POST['date'] . ' ' . $_POST['time']));
    
    $stmt = $pdo->prepare("
        UPDATE exercises 
        SET exercise_type_id = ?,
            date = ?,
            duration = ?,
            intensity = ?,
            notes = ?
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([
        intval($_POST['exercise_type_id']),
        $date,
        intval($_POST['duration']),
        intval($_POST['intensity']),
        $_POST['notes'] ?? null,
        intval($_POST['id']),
        $user_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Exercice mis à jour avec succès']);
} catch (PDOException $e) {
    error_log("Erreur SQL: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'exercice']);
}
?> 