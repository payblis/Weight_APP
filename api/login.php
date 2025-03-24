<?php
// Configuration de base
session_start();
require_once '../database/db.php';

// Fonction pour nettoyer les données d'entrée
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et nettoyer les données du formulaire
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    
    // Validation des données
    $errors = [];
    
    // Vérifier si l'email existe
    $sql = "SELECT id, username, password FROM users WHERE email = ?";
    $user = fetchOne($sql, [$email]);
    
    if (!$user) {
        $errors[] = "Email ou mot de passe incorrect.";
    } else {
        // Vérifier le mot de passe
        if (!password_verify($password, $user['password'])) {
            $errors[] = "Email ou mot de passe incorrect.";
        }
    }
    
    // S'il n'y a pas d'erreurs, connecter l'utilisateur
    if (empty($errors)) {
        // Créer la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Rediriger vers le tableau de bord
        header("Location: ../dashboard.php");
        exit();
    } else {
        // S'il y a des erreurs, les stocker en session et rediriger vers la page de connexion
        $_SESSION['login_errors'] = $errors;
        $_SESSION['login_email'] = $email; // Conserver l'email pour le formulaire
        header("Location: ../login.php");
        exit();
    }
} else {
    // Si la page est accédée directement sans soumission de formulaire
    header("Location: ../login.php");
    exit();
}
?>
