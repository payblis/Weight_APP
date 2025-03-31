<?php
session_start();
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action'
    ]);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Cette page ne peut être accédée que via un formulaire'
    ]);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$meal_name = $data['name'] ?? '';
$foods = $data['foods'] ?? [];

if (empty($meal_name) || empty($foods)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Données manquantes pour créer le repas'
    ]);
    exit;
}

try {
    // Créer le repas
    $sql = "INSERT INTO meals (user_id, notes, meal_type, log_date, total_calories, total_protein, total_carbs, total_fat, created_at) 
            VALUES (?, ?, 'dejeuner', CURDATE(), ?, ?, ?, ?, NOW())";
    
    // Calculer les totaux nutritionnels
    $totals = [
        'calories' => 0,
        'protein' => 0,
        'carbs' => 0,
        'fat' => 0
    ];
    
    foreach ($foods as $food) {
        $sql_food = "SELECT calories, protein, carbs, fat FROM foods WHERE id = ?";
        $food_data = fetchOne($sql_food, [$food['food_id']]);
        
        if ($food_data) {
            $quantity = $food['quantity'] / 100; // Convertir en pourcentage
            $totals['calories'] += $food_data['calories'] * $quantity;
            $totals['protein'] += $food_data['protein'] * $quantity;
            $totals['carbs'] += $food_data['carbs'] * $quantity;
            $totals['fat'] += $food_data['fat'] * $quantity;
        }
    }
    
    $meal_id = insert($sql, [
        $_SESSION['user_id'],
        $data['name'],
        round($totals['calories']),
        round($totals['protein'], 2),
        round($totals['carbs'], 2),
        round($totals['fat'], 2)
    ]);
    
    if (!$meal_id) {
        throw new Exception("Erreur lors de la création du repas");
    }
    
    // Ajouter les aliments au repas
    foreach ($foods as $food) {
        $sql = "INSERT INTO meal_foods (meal_id, food_id, quantity) VALUES (?, ?, ?)";
        insert($sql, [$meal_id, $food['food_id'], $food['quantity']]);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Repas créé avec succès',
        'meal_id' => $meal_id
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de la création du repas : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création du repas'
    ]);
} 