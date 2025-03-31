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
$foods = $data['foods'] ?? [];

if (empty($foods)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Aucun aliment à créer'
    ]);
    exit;
}

try {
    $created_foods = [];
    
    foreach ($foods as $food) {
        // Vérifier si l'aliment existe déjà
        $sql = "SELECT id FROM foods WHERE name = ?";
        $existing_food = fetchOne($sql, [$food['name']]);
        
        if ($existing_food) {
            // Si l'aliment existe, l'ajouter à la liste des aliments créés
            $created_foods[] = [
                'id' => $existing_food['id'],
                'name' => $food['name'],
                'calories' => $food['calories'],
                'protein' => $food['protein'],
                'carbs' => $food['carbs'],
                'fat' => $food['fat']
            ];
            continue;
        }
        
        // Créer le nouvel aliment
        $sql = "INSERT INTO foods (name, calories, protein, carbs, fat) VALUES (?, ?, ?, ?, ?)";
        $food_id = insert($sql, [
            $food['name'],
            $food['calories'],
            $food['protein'],
            $food['carbs'],
            $food['fat']
        ]);
        
        if ($food_id) {
            $created_foods[] = [
                'id' => $food_id,
                'name' => $food['name'],
                'calories' => $food['calories'],
                'protein' => $food['protein'],
                'carbs' => $food['carbs'],
                'fat' => $food['fat']
            ];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Aliments créés avec succès',
        'foods' => $created_foods
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de la création des aliments : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création des aliments'
    ]);
} 