<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_type = $_POST['post_type'] ?? 'message';
    $content = $_POST['content'] ?? '';
    $reference_id = $_POST['reference_id'] ?? null;
    $reference_type = $_POST['reference_type'] ?? null;
    $visibility = $_POST['visibility'] ?? 'public';
    $group_id = $_POST['group_id'] ?? null;
    
    // Si le post est pour un groupe, s'assurer que le group_id est fourni
    if ($visibility === 'group' && !$group_id) {
        $_SESSION['error_message'] = "Veuillez sélectionner un groupe pour ce post.";
        redirect('community.php');
    }
    
    try {
        if (createCommunityPost($user_id, $post_type, $content, $reference_id, $reference_type, $visibility, $group_id)) {
            $_SESSION['success_message'] = "Votre post a été publié avec succès !";
        } else {
            $_SESSION['error_message'] = "Une erreur est survenue lors de la publication du post. Vérifiez que vous êtes bien membre du groupe sélectionné.";
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la création du post : " . $e->getMessage());
        $_SESSION['error_message'] = "Une erreur est survenue lors de la publication du post. Veuillez réessayer.";
    }
    
    // Rediriger vers la page appropriée
    if ($visibility === 'group' && $group_id) {
        redirect("group.php?id=$group_id");
    } else {
        redirect('community.php');
    }
} 