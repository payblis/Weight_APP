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
    if (!isset($_POST['group_id']) || !isset($_POST['user_ids']) || !is_array($_POST['user_ids'])) {
        $_SESSION['error_message'] = "Données d'invitation invalides.";
        redirect('group.php?id=' . $_POST['group_id']);
    }

    $group_id = $_POST['group_id'];
    $invited_users = $_POST['user_ids'];
    $success_count = 0;
    $error_count = 0;

    // Vérifier si l'utilisateur est admin du groupe
    if (!isGroupAdmin($group_id, $user_id)) {
        $_SESSION['error_message'] = "Vous n'êtes pas autorisé à inviter des utilisateurs dans ce groupe.";
        redirect('group.php?id=' . $group_id);
    }

    foreach ($invited_users as $invited_user_id) {
        if (inviteUserToGroup($group_id, $user_id, $invited_user_id)) {
            $success_count++;
        } else {
            $error_count++;
        }
    }

    if ($success_count > 0) {
        $_SESSION['success_message'] = "$success_count invitation(s) envoyée(s) avec succès.";
    }
    if ($error_count > 0) {
        $_SESSION['error_message'] = "$error_count invitation(s) n'ont pas pu être envoyée(s).";
    }

    redirect('group.php?id=' . $group_id);
} 