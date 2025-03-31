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
    $sql = "INSERT INTO meals (user_id, name, created_at) VALUES (?, ?, NOW())";
    $meal_id = insert($sql, [$_SESSION['user_id'], $meal_name]);
    
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