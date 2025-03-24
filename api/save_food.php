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
$id = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : null;
$name = isset($data['name']) ? trim($data['name']) : null;
$brand = isset($data['brand']) ? trim($data['brand']) : null;
$calories = isset($data['calories']) ? filter_var($data['calories'], FILTER_VALIDATE_FLOAT) : null;
$proteins = isset($data['proteins']) ? filter_var($data['proteins'], FILTER_VALIDATE_FLOAT) : null;
$carbs = isset($data['carbs']) ? filter_var($data['carbs'], FILTER_VALIDATE_FLOAT) : null;
$fats = isset($data['fats']) ? filter_var($data['fats'], FILTER_VALIDATE_FLOAT) : null;

if (!$name || !$calories || !$proteins || !$carbs || !$fats) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Si un ID est fourni, vérifier que l'aliment appartient à l'utilisateur
    if ($id) {
        $checkStmt = $pdo->prepare("
            SELECT id FROM foods 
            WHERE id = ? AND user_id = ?
        ");
        $checkStmt->execute([$id, $_SESSION['user_id']]);
        
        if (!$checkStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }
    }

    // Début de la transaction
    $pdo->beginTransaction();

    if ($id) {
        // Mise à jour
        $stmt = $pdo->prepare("
            UPDATE foods 
            SET name = ?, brand = ?, calories = ?, proteins = ?, carbs = ?, fats = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([
            $name,
            $brand,
            $calories,
            $proteins,
            $carbs,
            $fats,
            $id,
            $_SESSION['user_id']
        ]);
    } else {
        // Création
        $stmt = $pdo->prepare("
            INSERT INTO foods (name, brand, calories, proteins, carbs, fats, user_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $name,
            $brand,
            $calories,
            $proteins,
            $carbs,
            $fats,
            $_SESSION['user_id']
        ]);
    }

    // Validation de la transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $id ? 'Aliment modifié avec succès' : 'Aliment ajouté avec succès'
    ]);

} catch (PDOException $e) {
    // Annulation de la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur lors de la sauvegarde de l'aliment : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la sauvegarde de l'aliment"]);
}
?> 