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

// Récupérer les commentaires
$sql = "SELECT pc.*, u.username, u.avatar 
        FROM post_comments pc 
        JOIN users u ON pc.user_id = u.id 
        WHERE pc.post_id = ? 
        ORDER BY pc.created_at DESC";
$comments = fetchAll($sql, [$post_id]);

// Retourner les commentaires au format JSON
header('Content-Type: application/json');
echo json_encode(['comments' => $comments]); 