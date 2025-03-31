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

// Vérifier si l'ID du repas est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID du repas manquant ou invalide'
    ]);
    exit;
}

$meal_id = intval($_GET['id']);

try {
    // Récupérer les détails du repas
    $sql = "SELECT * FROM predefined_meals WHERE id = ?";
    $meal = fetchOne($sql, [$meal_id]);
    
    if (!$meal) {
        throw new Exception("Repas non trouvé");
    }
    
    // Récupérer les aliments associés au repas
    $sql = "SELECT pmf.*, f.name as food_name 
            FROM predefined_meal_foods pmf 
            LEFT JOIN foods f ON pmf.food_id = f.id 
            WHERE pmf.predefined_meal_id = ?";
    $foods = fetchAll($sql, [$meal_id]);
    
    // Ajouter les aliments au repas
    $meal['foods'] = $foods;
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'meal' => $meal
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du repas prédéfini : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération du repas'
    ]);
} 