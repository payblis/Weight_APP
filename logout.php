<?php
require_once 'includes/config.php';

// Destruction de la session
session_start();
session_destroy();

// Suppression du cookie de connexion automatique
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirection vers la page de connexion
header('Location: login.php');
exit; 