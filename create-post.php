<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("Tentative de création de post - POST data: " . print_r($_POST, true));
        
        // Récupérer les données du formulaire
        $content = $_POST['content'] ?? '';
        $visibility = $_POST['visibility'] ?? 'public';
        $group_id = $_POST['group_id'] ?? null;
        $post_type = $_POST['post_type'] ?? null;
        $reference_id = $_POST['reference_id'] ?? null;

        // Validation des données
        if (empty($content) && !$post_type) {
            error_log("Erreur: Le contenu est requis pour un post normal");
            $_SESSION['error'] = "Le contenu est requis pour un post normal.";
            redirect('community.php');
        }

        if ($visibility === 'group' && !$group_id) {
            error_log("Erreur: Un groupe doit être sélectionné pour la visibilité 'group'");
            $_SESSION['error'] = "Un groupe doit être sélectionné pour la visibilité 'group'.";
            redirect('community.php');
        }

        // Créer le post
        $result = createCommunityPost($user_id, $content, $visibility, $group_id, $post_type, $reference_id);
        
        if ($result) {
            error_log("Post créé avec succès");
            $_SESSION['success'] = "Votre post a été publié avec succès.";
        } else {
            error_log("Échec de la création du post");
            $_SESSION['error'] = "Une erreur est survenue lors de la publication du post.";
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la création du post: " . $e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors de la publication du post.";
    }
}

redirect('community.php'); 