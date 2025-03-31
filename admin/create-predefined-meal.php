<?php
session_start();
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Accès non autorisé'
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

if (empty($data['name']) || empty($data['foods'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Données manquantes'
    ]);
    exit;
}

try {
    // Calculer les totaux nutritionnels
    $totals = [
        'calories' => 0,
        'protein' => 0,
        'carbs' => 0,
        'fat' => 0
    ];
    
    foreach ($data['foods'] as $food) {
        $sql = "SELECT calories, protein, carbs, fat FROM foods WHERE id = ?";
        $food_data = fetchOne($sql, [$food['food_id']]);
        
        if ($food_data) {
            $quantity = $food['quantity'] / 100; // Convertir en pourcentage
            $totals['calories'] += $food_data['calories'] * $quantity;
            $totals['protein'] += $food_data['protein'] * $quantity;
            $totals['carbs'] += $food_data['carbs'] * $quantity;
            $totals['fat'] += $food_data['fat'] * $quantity;
        }
    }
    
    // Créer le repas prédéfini
    $sql = "INSERT INTO predefined_meals (name, description, total_calories, total_protein, total_carbs, total_fat, is_public, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $meal_id = insert($sql, [
        $data['name'],
        $data['description'] ?? '',
        round($totals['calories']),
        round($totals['protein'], 2),
        round($totals['carbs'], 2),
        round($totals['fat'], 2),
        $data['is_public'] ?? 0,
        $_SESSION['user_id']
    ]);
    
    if (!$meal_id) {
        throw new Exception("Erreur lors de la création du repas");
    }
    
    // Ajouter les aliments au repas
    foreach ($data['foods'] as $food) {
        $sql = "INSERT INTO predefined_meal_foods (predefined_meal_id, food_id, quantity) VALUES (?, ?, ?)";
        insert($sql, [$meal_id, $food['food_id'], $food['quantity']]);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Repas créé avec succès',
        'meal_id' => $meal_id
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de la création du repas prédéfini : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création du repas'
    ]);
} 