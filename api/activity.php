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
    $activity_id = (int)sanitize_input($_POST['activity_id']);
    $duration_minutes = (int)sanitize_input($_POST['duration_minutes']);
    $log_date = sanitize_input($_POST['log_date']);
    $notes = isset($_POST['notes']) ? sanitize_input($_POST['notes']) : '';
    $user_id = $_SESSION['user_id'];
    
    // Validation des données
    $errors = [];
    
    if ($activity_id <= 0) {
        $errors[] = "Veuillez sélectionner une activité valide.";
    }
    
    if ($duration_minutes <= 0 || $duration_minutes > 1440) {
        $errors[] = "La durée doit être comprise entre 1 et 1440 minutes.";
    }
    
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $log_date)) {
        $errors[] = "Format de date invalide.";
    }
    
    // S'il n'y a pas d'erreurs, enregistrer l'activité
    if (empty($errors)) {
        try {
            // Récupérer les calories par heure pour cette activité
            $sql = "SELECT calories_per_hour FROM activities WHERE id = ?";
            $activity = fetchOne($sql, [$activity_id]);
            
            if (!$activity) {
                throw new Exception("Activité non trouvée.");
            }
            
            // Calculer les calories brûlées
            $calories_burned = ($activity['calories_per_hour'] / 60) * $duration_minutes;
            
            // Insérer l'activité dans la base de données
            $sql = "INSERT INTO activity_logs (user_id, activity_id, duration_minutes, log_date, calories_burned, notes) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = executeQuery($sql, [$user_id, $activity_id, $duration_minutes, $log_date, $calories_burned, $notes]);
            
            // Rediriger vers la page des activités
            header("Location: ../activities.php?success=1");
            exit();
        } catch (Exception $e) {
            $errors[] = "Erreur lors de l'enregistrement de l'activité: " . $e->getMessage();
        }
    }
    
    // S'il y a des erreurs, les stocker en session et rediriger
    if (!empty($errors)) {
        $_SESSION['activity_log_errors'] = $errors;
        header("Location: ../activities.php?error=1");
        exit();
    }
} else {
    // Si la requête est en GET, renvoyer les données d'activités pour l'utilisateur
    $user_id = $_SESSION['user_id'];
    
    // Récupérer la liste des activités disponibles
    $sql = "SELECT * FROM activities ORDER BY name";
    $activities = fetchAll($sql, []);
    
    // Récupérer l'historique des activités de l'utilisateur
    $sql = "SELECT al.*, a.name as activity_name, a.category 
            FROM activity_logs al 
            JOIN activities a ON al.activity_id = a.id 
            WHERE al.user_id = ? 
            ORDER BY al.log_date DESC";
    $activity_logs = fetchAll($sql, [$user_id]);
    
    // Récupérer les statistiques d'activité
    $sql = "SELECT COUNT(*) as total_activities, 
                  SUM(duration_minutes) as total_duration, 
                  SUM(calories_burned) as total_calories 
            FROM activity_logs 
            WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $stats = fetchOne($sql, [$user_id]);
    
    // Préparer les données à renvoyer
    $response = [
        'activities' => $activities,
        'activity_logs' => $activity_logs,
        'stats' => $stats
    ];
    
    // Renvoyer les données au format JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
