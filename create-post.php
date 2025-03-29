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
    
    // Vérifier si le post est pour un groupe et si l'utilisateur en est membre
    if ($visibility === 'group' && $group_id) {
        if (!isGroupMember($group_id, $user_id)) {
            $_SESSION['error_message'] = "Vous n'êtes pas membre de ce groupe.";
            redirect('community.php');
        }
    }
    
    if (createCommunityPost($user_id, $post_type, $content, $reference_id, $reference_type, $visibility, $group_id)) {
        $_SESSION['success_message'] = "Votre post a été publié avec succès !";
    } else {
        $_SESSION['error_message'] = "Une erreur est survenue lors de la publication du post.";
    }
    
    // Rediriger vers la page appropriée
    if ($visibility === 'group' && $group_id) {
        redirect("group.php?id=$group_id");
    } else {
        redirect('community.php');
    }
} 