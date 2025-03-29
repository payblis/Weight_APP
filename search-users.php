<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Non autorisé']));
}

// Vérifier si une requête de recherche est fournie
if (!isset($_GET['q']) || strlen($_GET['q']) < 2) {
    http_response_code(400);
    exit(json_encode(['error' => 'Requête de recherche invalide']));
}

$query = $_GET['q'];
$user_id = $_SESSION['user_id'];

// Rechercher les utilisateurs
$sql = "SELECT id, username, avatar 
        FROM users 
        WHERE username LIKE ? AND id != ? 
        LIMIT 10";
$users = fetchAll($sql, ["%$query%", $user_id]);

// Retourner les résultats
header('Content-Type: application/json');
echo json_encode(['users' => $users]); 