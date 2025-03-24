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
    $meal_id = isset($_POST['meal_id']) && !empty($_POST['meal_id']) ? (int)sanitize_input($_POST['meal_id']) : null;
    $meal_name = isset($_POST['meal_name']) ? sanitize_input($_POST['meal_name']) : '';
    $calories = (float)sanitize_input($_POST['calories']);
    $meal_time = sanitize_input($_POST['meal_time']);
    $log_date = sanitize_input($_POST['log_date']);
    $notes = isset($_POST['notes']) ? sanitize_input($_POST['notes']) : '';
    $user_id = $_SESSION['user_id'];
    
    // Validation des données
    $errors = [];
    
    if ($meal_id === null && empty($meal_name)) {
        $errors[] = "Veuillez sélectionner un repas ou saisir un nom de repas personnalisé.";
    }
    
    if ($calories <= 0) {
        $errors[] = "Les calories doivent être supérieures à 0.";
    }
    
    if (!in_array($meal_time, ['petit-déjeuner', 'déjeuner', 'dîner', 'collation'])) {
        $errors[] = "Moment du repas invalide.";
    }
    
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $log_date)) {
        $errors[] = "Format de date invalide.";
    }
    
    // S'il n'y a pas d'erreurs, enregistrer le repas
    if (empty($errors)) {
        try {
            // Insérer le repas dans la base de données
            $sql = "INSERT INTO meal_logs (user_id, meal_id, meal_name, calories, log_date, meal_time, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = executeQuery($sql, [$user_id, $meal_id, $meal_name, $calories, $log_date, $meal_time, $notes]);
            
            // Rediriger vers la page des repas
            header("Location: ../meals.php?success=1");
            exit();
        } catch (Exception $e) {
            $errors[] = "Erreur lors de l'enregistrement du repas: " . $e->getMessage();
        }
    }
    
    // S'il y a des erreurs, les stocker en session et rediriger
    if (!empty($errors)) {
        $_SESSION['meal_log_errors'] = $errors;
        header("Location: ../meals.php?error=1");
        exit();
    }
} else {
    // Si la requête est en GET, renvoyer les données de repas pour l'utilisateur
    $user_id = $_SESSION['user_id'];
    
    // Récupérer la liste des repas disponibles
    $sql = "SELECT * FROM meals ORDER BY name";
    $meals = fetchAll($sql, []);
    
    // Récupérer l'historique des repas de l'utilisateur
    $sql = "SELECT ml.*, m.name as meal_name_from_db 
            FROM meal_logs ml 
            LEFT JOIN meals m ON ml.meal_id = m.id 
            WHERE ml.user_id = ? 
            ORDER BY ml.log_date DESC, 
                CASE ml.meal_time 
                    WHEN 'petit-déjeuner' THEN 1 
                    WHEN 'déjeuner' THEN 2 
                    WHEN 'dîner' THEN 3 
                    WHEN 'collation' THEN 4 
                END";
    $meal_logs = fetchAll($sql, [$user_id]);
    
    // Récupérer les statistiques nutritionnelles pour aujourd'hui
    $sql = "SELECT SUM(calories) as daily_calories_consumed 
            FROM meal_logs 
            WHERE user_id = ? AND log_date = CURDATE()";
    $daily_stats = fetchOne($sql, [$user_id]);
    
    // Récupérer l'objectif calorique quotidien
    $sql = "SELECT daily_calorie_target 
            FROM custom_programs 
            WHERE user_id = ? 
            AND CURDATE() BETWEEN start_date AND end_date 
            ORDER BY id DESC LIMIT 1";
    $program = fetchOne($sql, [$user_id]);
    
    $daily_calorie_target = $program ? $program['daily_calorie_target'] : 2000; // Valeur par défaut
    
    // Préparer les données à renvoyer
    $response = [
        'meals' => $meals,
        'meal_logs' => $meal_logs,
        'daily_stats' => [
            'daily_calories_consumed' => $daily_stats ? $daily_stats['daily_calories_consumed'] : 0,
            'daily_calorie_target' => $daily_calorie_target,
            'calories_remaining' => $daily_calorie_target - ($daily_stats ? $daily_stats['daily_calories_consumed'] : 0)
        ]
    ];
    
    // Renvoyer les données au format JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
