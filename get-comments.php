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
    
    $sql = "SELECT pc.*, u.username, u.avatar 
            FROM post_comments pc 
            JOIN users u ON pc.user_id = u.id 
            WHERE pc.post_id = ? 
            ORDER BY pc.created_at DESC";
    
    $comments = fetchAll($sql, [$post_id]);
    
    echo json_encode(['success' => true, 'comments' => $comments]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID du post manquant']);
} 