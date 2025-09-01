<?php
header('Content-Type: application/json');
require_once 'includes/credit_functions.php';

// Récupérer le nombre de crédits
$credits = isset($_POST['credits']) ? intval($_POST['credits']) : 50;

// Valider le nombre de crédits
if ($credits < 1 || $credits > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre de crédits invalide']);
    exit;
}

// Calculer le prix personnalisé
$priceData = CreditManager::calculateCustomPrice($credits);

// Retourner les données au format JSON
echo json_encode($priceData);
?>
