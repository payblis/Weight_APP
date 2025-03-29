<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si l'ID du post est fourni
if (!isset($_GET['post_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID du post manquant']);
    exit;
}

$post_id = (int)$_GET['post_id'];

// Récupérer le nombre de commentaires
$sql = "SELECT COUNT(*) as count FROM post_comments WHERE post_id = ?";
$result = fetchOne($sql, [$post_id]);

// Retourner le nombre de commentaires au format JSON
header('Content-Type: application/json');
echo json_encode(['count' => $result['count']]); 