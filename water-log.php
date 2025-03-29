<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Traitement de l'ajout d'eau
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Gestion de la réinitialisation
    if (isset($_POST['action']) && $_POST['action'] === 'reset') {
        try {
            $sql = "DELETE FROM water_logs WHERE user_id = ? AND log_date = CURDATE()";
            $result = update($sql, [$user_id]);
            
            if ($result) {
                $_SESSION['success'] = "Votre consommation d'eau a été réinitialisée avec succès !";
            } else {
                $_SESSION['error'] = "Une erreur s'est produite lors de la réinitialisation.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Une erreur s'est produite : " . $e->getMessage();
            error_log("Erreur dans water-log.php (reset): " . $e->getMessage());
        }
    } else {
        // Gestion de l'ajout d'eau
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        
        // Validation
        if ($amount <= 0) {
            $_SESSION['error'] = "La quantité d'eau doit être supérieure à 0.";
        } else {
            try {
                // Vérifier si un log existe déjà pour aujourd'hui
                $sql = "SELECT id FROM water_logs WHERE user_id = ? AND log_date = CURDATE()";
                $existing_log = fetchOne($sql, [$user_id]);
                
                if ($existing_log) {
                    // Mettre à jour le log existant
                    $sql = "UPDATE water_logs SET amount = amount + ? WHERE id = ?";
                    $result = update($sql, [$amount, $existing_log['id']]);
                } else {
                    // Créer un nouveau log
                    $sql = "INSERT INTO water_logs (user_id, amount, log_date) VALUES (?, ?, CURDATE())";
                    $result = insert($sql, [$user_id, $amount]);
                }
                
                if ($result) {
                    $_SESSION['success'] = "Votre consommation d'eau a été enregistrée avec succès !";
                } else {
                    $_SESSION['error'] = "Une erreur s'est produite lors de l'enregistrement.";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Une erreur s'est produite : " . $e->getMessage();
                error_log("Erreur dans water-log.php: " . $e->getMessage());
            }
        }
    }
}

// Rediriger vers le dashboard
redirect('dashboard.php'); 