<?php
// Configuration de base
session_start();
require_once '../database/db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Déconnexion de l'utilisateur
session_unset();
session_destroy();

// Rediriger vers la page d'accueil
header("Location: ../index.php");
exit();
?>
