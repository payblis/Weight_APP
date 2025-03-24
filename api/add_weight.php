<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$user_id = $_SESSION['user_id'];
$required_fields = ['weight', 'date'];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'Champs requis manquants']);
        exit;
    }
}

try {
    // Vérification si un poids existe déjà pour cette date
    $stmt = $pdo->prepare("
        SELECT id 
        FROM daily_logs 
        WHERE user_id = ? AND DATE(date) = ?
    ");
    $stmt->execute([$user_id, $_POST['date']]);
    
    if ($stmt->fetch()) {
        // Mise à jour du poids existant
        $stmt = $pdo->prepare("
            UPDATE daily_logs 
            SET weight = ?,
                notes = ?,
                updated_at = NOW()
            WHERE user_id = ? AND DATE(date) = ?
        ");
        
        $stmt->execute([
            floatval($_POST['weight']),
            $_POST['notes'] ?? null,
            $user_id,
            $_POST['date']
        ]);
    } else {
        // Insertion d'un nouveau poids
        $stmt = $pdo->prepare("
            INSERT INTO daily_logs (
                user_id,
                date,
                weight,
                notes,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $_POST['date'],
            floatval($_POST['weight']),
            $_POST['notes'] ?? null
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Poids enregistré avec succès']);
} catch (PDOException $e) {
    error_log("Erreur SQL: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du poids']);
}
?> 