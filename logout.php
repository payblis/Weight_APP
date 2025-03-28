<?php
// Démarrer la session
session_start();

// Supprimer le cookie de connexion automatique s'il existe
if (isset($_COOKIE['remember_token'])) {
    // Supprimer le token de la base de données
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    
    $token = $_COOKIE['remember_token'];
    $sql = "DELETE FROM remember_tokens WHERE token = ?";
    delete($sql, [$token]);
    
    // Supprimer le cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header('Location: login.php');
exit;
?>
