<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

try {
    // Validation des données
    if (!isset($_POST['meal_type_id']) || !isset($_POST['time']) || !isset($_POST['date'])) {
        throw new Exception('Données manquantes');
    }

    $user_id = $_SESSION['user_id'];
    $meal_type_id = $_POST['meal_type_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $notes = $_POST['notes'] ?? '';
    $foods = $_POST['foods'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    // Validation des aliments
    if (empty($foods) || empty($quantities) || count($foods) !== count($quantities)) {
        throw new Exception('Données des aliments invalides');
    }

    // Début de la transaction
    $pdo->beginTransaction();

    // Insertion du repas
    $stmt = $pdo->prepare("
        INSERT INTO meals (user_id, meal_type_id, date, notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $datetime = $date . ' ' . $time;
    $stmt->execute([$user_id, $meal_type_id, $datetime, $notes]);
    $meal_id = $pdo->lastInsertId();

    // Insertion des aliments du repas
    $stmt = $pdo->prepare("
        INSERT INTO meal_foods (meal_id, food_id, quantity, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ");

    foreach ($foods as $i => $food_id) {
        if (!empty($food_id) && !empty($quantities[$i])) {
            $stmt->execute([$meal_id, $food_id, $quantities[$i]]);
        }
    }

    // Validation de la transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Repas ajouté avec succès']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur lors de l'ajout du repas : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 