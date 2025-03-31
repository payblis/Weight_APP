<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier si l'ID du repas est fourni
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID du repas manquant']);
    exit;
}

try {
    // Récupérer les informations du repas
    $meal = fetchOne(
        "SELECT id, name, description, notes, is_public, calories, proteins, carbs, fats 
         FROM predefined_meals 
         WHERE id = ?",
        [$_GET['id']]
    );

    if (!$meal) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Repas non trouvé']);
        exit;
    }

    // Récupérer les aliments du repas
    $foods = fetchAll(
        "SELECT food_id, quantity 
         FROM predefined_meal_items 
         WHERE meal_id = ?",
        [$_GET['id']]
    );

    $meal['foods'] = $foods;

    echo json_encode(['success' => true, 'meal' => $meal]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
} 