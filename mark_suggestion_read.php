<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suggestion_id'])) {
    $suggestion_id = (int)$_POST['suggestion_id'];
    $user_id = $_SESSION['user_id'];
    
    // Vérifier que la suggestion appartient à l'utilisateur
    $sql = "UPDATE ai_suggestions 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?";
    
    if (update($sql, [$suggestion_id, $user_id])) {
        $_SESSION['success_message'] = "Suggestion marquée comme lue.";
    } else {
        $_SESSION['error_message'] = "Erreur lors du marquage de la suggestion.";
    }
}

header('Location: suggestions.php');
exit; 