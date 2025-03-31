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

if (empty($data['id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID du repas manquant'
    ]);
    exit;
}

try {
    // Supprimer d'abord les aliments associés
    $sql = "DELETE FROM predefined_meal_foods WHERE predefined_meal_id = ?";
    delete($sql, [$data['id']]);
    
    // Supprimer le repas
    $sql = "DELETE FROM predefined_meals WHERE id = ?";
    $success = delete($sql, [$data['id']]);
    
    if (!$success) {
        throw new Exception("Erreur lors de la suppression du repas");
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Repas supprimé avec succès'
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de la suppression du repas prédéfini : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression du repas'
    ]);
} 