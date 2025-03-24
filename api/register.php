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

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et nettoyer les données du formulaire
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    $gender = sanitize_input($_POST['gender']);
    $age = (int)sanitize_input($_POST['age']);
    $height = (float)sanitize_input($_POST['height']);
    $initial_weight = (float)sanitize_input($_POST['initial_weight']);
    $target_weight = (float)sanitize_input($_POST['target_weight']);
    $activity_level = sanitize_input($_POST['activity_level']);
    
    // Validation des données
    $errors = [];
    
    // Vérifier si l'email existe déjà
    $sql = "SELECT id FROM users WHERE email = ?";
    $user = fetchOne($sql, [$email]);
    if ($user) {
        $errors[] = "Cet email est déjà utilisé.";
    }
    
    // Vérifier si le nom d'utilisateur existe déjà
    $sql = "SELECT id FROM users WHERE username = ?";
    $user = fetchOne($sql, [$username]);
    if ($user) {
        $errors[] = "Ce nom d'utilisateur est déjà utilisé.";
    }
    
    // Vérifier que les mots de passe correspondent
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    
    // Vérifier la complexité du mot de passe
    if (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }
    
    // Vérifier l'âge
    if ($age < 18 || $age > 100) {
        $errors[] = "L'âge doit être compris entre 18 et 100 ans.";
    }
    
    // Vérifier la taille
    if ($height < 100 || $height > 250) {
        $errors[] = "La taille doit être comprise entre 100 et 250 cm.";
    }
    
    // Vérifier les poids
    if ($initial_weight < 30 || $initial_weight > 300) {
        $errors[] = "Le poids initial doit être compris entre 30 et 300 kg.";
    }
    
    if ($target_weight < 30 || $target_weight > 300) {
        $errors[] = "Le poids cible doit être compris entre 30 et 300 kg.";
    }
    
    // Si aucune erreur, procéder à l'inscription
    if (empty($errors)) {
        try {
            // Démarrer une transaction
            $conn = getDbConnection();
            $conn->beginTransaction();
            
            // Hacher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insérer l'utilisateur dans la base de données
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username, $email, $hashed_password]);
            
            // Récupérer l'ID de l'utilisateur nouvellement créé
            $user_id = $conn->lastInsertId();
            
            // Insérer le profil de l'utilisateur
            $sql = "INSERT INTO user_profiles (user_id, gender, age, height, initial_weight, target_weight, activity_level) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $gender, $age, $height, $initial_weight, $target_weight, $activity_level]);
            
            // Enregistrer le poids initial dans les logs
            $today = date('Y-m-d');
            $sql = "INSERT INTO weight_logs (user_id, weight, log_date, notes) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $initial_weight, $today, "Poids initial"]);
            
            // Créer un programme personnalisé pour l'utilisateur
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+30 days'));
            
            // Calculer les besoins caloriques quotidiens
            $bmr = 0;
            if ($gender == 'homme') {
                // Formule de Mifflin-St Jeor pour les hommes
                $bmr = 10 * $initial_weight + 6.25 * $height - 5 * $age + 5;
            } else {
                // Formule de Mifflin-St Jeor pour les femmes
                $bmr = 10 * $initial_weight + 6.25 * $height - 5 * $age - 161;
            }
            
            // Ajuster en fonction du niveau d'activité
            $activity_multipliers = [
                'sédentaire' => 1.2,
                'légèrement actif' => 1.375,
                'modérément actif' => 1.55,
                'très actif' => 1.725,
                'extrêmement actif' => 1.9
            ];
            
            $tdee = $bmr * $activity_multipliers[$activity_level];
            
            // Pour perdre du poids, créer un déficit calorique
            $daily_calorie_target = $tdee - 500; // Déficit de 500 calories pour perdre environ 0.5kg par semaine
            
            // S'assurer que l'objectif calorique n'est pas trop bas
            $daily_calorie_target = max($daily_calorie_target, 1200); // Minimum 1200 calories par jour
            
            $sql = "INSERT INTO custom_programs (user_id, name, description, start_date, end_date, daily_calorie_target) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $user_id, 
                "Programme initial", 
                "Programme personnalisé basé sur vos objectifs de perte de poids", 
                $start_date, 
                $end_date, 
                $daily_calorie_target
            ]);
            
            // Valider la transaction
            $conn->commit();
            
            // Connecter l'utilisateur
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            
            // Rediriger vers le tableau de bord
            header("Location: ../dashboard.php");
            exit();
            
        } catch (PDOException $e) {
            // En cas d'erreur, annuler la transaction
            $conn->rollBack();
            $errors[] = "Erreur lors de l'inscription: " . $e->getMessage();
        }
    }
    
    // S'il y a des erreurs, les stocker en session et rediriger vers la page d'inscription
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_form_data'] = $_POST; // Conserver les données du formulaire
        header("Location: ../register.php");
        exit();
    }
} else {
    // Si la page est accédée directement sans soumission de formulaire
    header("Location: ../register.php");
    exit();
}
?>
