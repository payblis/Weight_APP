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

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et nettoyer les données du formulaire
    $weight = (float)sanitize_input($_POST['weight']);
    $log_date = sanitize_input($_POST['log_date']);
    $notes = isset($_POST['notes']) ? sanitize_input($_POST['notes']) : '';
    $user_id = $_SESSION['user_id'];
    
    // Validation des données
    $errors = [];
    
    if ($weight < 30 || $weight > 300) {
        $errors[] = "Le poids doit être compris entre 30 et 300 kg.";
    }
    
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $log_date)) {
        $errors[] = "Format de date invalide.";
    }
    
    // S'il n'y a pas d'erreurs, enregistrer le poids
    if (empty($errors)) {
        try {
            // Insérer le poids dans la base de données
            $sql = "INSERT INTO weight_logs (user_id, weight, log_date, notes) VALUES (?, ?, ?, ?)";
            $stmt = executeQuery($sql, [$user_id, $weight, $log_date, $notes]);
            
            // Rediriger vers la page de suivi du poids
            header("Location: ../weight-log.php?success=1");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'enregistrement du poids: " . $e->getMessage();
        }
    }
    
    // S'il y a des erreurs, les stocker en session et rediriger
    if (!empty($errors)) {
        $_SESSION['weight_log_errors'] = $errors;
        header("Location: ../weight-log.php?error=1");
        exit();
    }
} else {
    // Si la requête est en GET, renvoyer les données de poids pour l'utilisateur
    $user_id = $_SESSION['user_id'];
    
    // Récupérer l'historique des poids
    $sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC";
    $weight_logs = fetchAll($sql, [$user_id]);
    
    // Récupérer le profil de l'utilisateur pour les poids initial et cible
    $sql = "SELECT initial_weight, target_weight FROM user_profiles WHERE user_id = ?";
    $profile = fetchOne($sql, [$user_id]);
    
    // Préparer les données à renvoyer
    $response = [
        'weight_logs' => $weight_logs,
        'initial_weight' => $profile['initial_weight'],
        'target_weight' => $profile['target_weight']
    ];
    
    // Renvoyer les données au format JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
