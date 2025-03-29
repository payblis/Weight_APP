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
    if (!isset($_POST['invitation_id']) || !isset($_POST['action'])) {
        $_SESSION['error_message'] = "Données d'invitation invalides.";
        redirect('dashboard.php');
    }

    $invitation_id = $_POST['invitation_id'];
    $action = $_POST['action'];

    switch ($action) {
        case 'accept':
            if (acceptGroupInvitation($invitation_id, $user_id)) {
                $_SESSION['success_message'] = "Vous avez rejoint le groupe avec succès !";
            } else {
                $_SESSION['error_message'] = "Une erreur est survenue lors de l'acceptation de l'invitation.";
            }
            break;

        case 'reject':
            if (rejectGroupInvitation($invitation_id, $user_id)) {
                $_SESSION['success_message'] = "L'invitation a été refusée.";
            } else {
                $_SESSION['error_message'] = "Une erreur est survenue lors du refus de l'invitation.";
            }
            break;

        default:
            $_SESSION['error_message'] = "Action invalide.";
    }

    redirect('dashboard.php');
} 