<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID du repas manquant');
    }

    $meal_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Récupération du repas et de ses aliments
    $stmt = $pdo->prepare("
        SELECT m.*, mt.name as meal_type_name,
               mf.food_id, mf.quantity,
               f.name as food_name, f.calories, f.proteins, f.carbs, f.fats
        FROM meals m
        JOIN meal_types mt ON m.meal_type_id = mt.id
        LEFT JOIN meal_foods mf ON m.id = mf.meal_id
        LEFT JOIN foods f ON mf.food_id = f.id
        WHERE m.id = ? AND m.user_id = ?
    ");
    $stmt->execute([$meal_id, $user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        throw new Exception('Repas non trouvé');
    }

    // Organisation des données
    $meal = [
        'id' => $rows[0]['id'],
        'meal_type_id' => $rows[0]['meal_type_id'],
        'meal_type_name' => $rows[0]['meal_type_name'],
        'date' => $rows[0]['date'],
        'time' => date('H:i', strtotime($rows[0]['date'])),
        'notes' => $rows[0]['notes'],
        'foods' => []
    ];

    foreach ($rows as $row) {
        if ($row['food_id']) {
            $meal['foods'][] = [
                'food_id' => $row['food_id'],
                'name' => $row['food_name'],
                'quantity' => $row['quantity'],
                'calories' => $row['calories'],
                'proteins' => $row['proteins'],
                'carbs' => $row['carbs'],
                'fats' => $row['fats']
            ];
        }
    }

    echo json_encode(['success' => true, 'meal' => $meal]);

} catch (Exception $e) {
    error_log("Erreur lors de la récupération du repas : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 