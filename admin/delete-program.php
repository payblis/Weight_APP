<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Vérifier si l'ID du programme est fourni
if (!isset($_GET['id'])) {
    redirect('program-management.php');
}

$program_id = (int)$_GET['id'];

try {
    // Vérifier si le programme existe
    $sql = "SELECT * FROM programs WHERE id = ?";
    $program = fetchOne($sql, [$program_id]);
    
    if (!$program) {
        $_SESSION['error'] = "Programme non trouvé.";
        redirect('program-management.php');
    }
    
    // Désactiver le programme pour tous les utilisateurs qui le suivent
    $sql = "UPDATE user_programs SET status = 'inactif', updated_at = NOW() WHERE program_id = ? AND status = 'actif'";
    update($sql, [$program_id]);
    
    // Supprimer le programme
    $sql = "DELETE FROM programs WHERE id = ?";
    $result = update($sql, [$program_id]);
    
    if ($result) {
        $_SESSION['success'] = "Le programme a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Une erreur s'est produite lors de la suppression du programme.";
    }
} catch (Exception $e) {
    error_log("Erreur dans delete-program.php: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur s'est produite lors de la suppression du programme.";
}

redirect('program-management.php'); 