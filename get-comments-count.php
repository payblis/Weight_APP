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

if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];
    $count = getPostCommentsCount($post_id);
    echo json_encode(['success' => true, 'count' => $count]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID du post manquant']);
} 