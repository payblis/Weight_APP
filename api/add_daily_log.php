<?php
require_once '../includes/config.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupération et validation des données
$weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
$notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
$date = date('Y-m-d');
$userId = $_SESSION['user_id'];

if (!$weight || $weight < 30 || $weight > 300) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Poids invalide']);
    exit;
}

try {
    // Vérification si une entrée existe déjà pour cette date
    $checkStmt = $pdo->prepare("
        SELECT id FROM daily_logs 
        WHERE user_id = ? AND date = ?
    ");
    $checkStmt->execute([$userId, $date]);
    
    if ($checkStmt->fetch()) {
        // Mise à jour de l'entrée existante
        $stmt = $pdo->prepare("
            UPDATE daily_logs 
            SET weight = ?, notes = ?, updated_at = NOW()
            WHERE user_id = ? AND date = ?
        ");
        $stmt->execute([$weight, $notes, $userId, $date]);
    } else {
        // Création d'une nouvelle entrée
        $stmt = $pdo->prepare("
            INSERT INTO daily_logs (user_id, date, weight, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$userId, $date, $weight, $notes]);
    }

    // Mise à jour du poids actuel de l'utilisateur
    $updateUserStmt = $pdo->prepare("
        UPDATE users 
        SET current_weight = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $updateUserStmt->execute([$weight, $userId]);

    // Vérification des objectifs de poids
    $goalStmt = $pdo->prepare("
        SELECT * FROM weight_goals 
        WHERE user_id = ? AND status = 'active'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $goalStmt->execute([$userId]);
    $goal = $goalStmt->fetch();

    if ($goal) {
        // Si l'objectif est atteint
        if (($goal['target_weight'] > $goal['start_weight'] && $weight >= $goal['target_weight']) ||
            ($goal['target_weight'] < $goal['start_weight'] && $weight <= $goal['target_weight'])) {
            
            // Marquer l'objectif comme atteint
            $updateGoalStmt = $pdo->prepare("
                UPDATE weight_goals 
                SET status = 'completed', completed_at = NOW()
                WHERE id = ?
            ");
            $updateGoalStmt->execute([$goal['id']]);

            // Créer une notification
            $notifStmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, created_at)
                VALUES (?, 'achievement', 'Objectif atteint !', 'Félicitations ! Vous avez atteint votre objectif de poids.', NOW())
            ");
            $notifStmt->execute([$userId]);

            // Vérifier et attribuer des badges
            $badgeStmt = $pdo->prepare("
                SELECT * FROM achievements 
                WHERE condition_type = 'weight_goal' 
                AND id NOT IN (SELECT achievement_id FROM user_achievements WHERE user_id = ?)
            ");
            $badgeStmt->execute([$userId]);
            $badges = $badgeStmt->fetchAll();

            foreach ($badges as $badge) {
                $earnBadgeStmt = $pdo->prepare("
                    INSERT INTO user_achievements (user_id, achievement_id, earned_at)
                    VALUES (?, ?, NOW())
                ");
                $earnBadgeStmt->execute([$userId, $badge['id']]);

                // Notification pour le badge
                $notifStmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, created_at)
                    VALUES (?, 'badge', 'Nouveau badge !', ?, NOW())
                ");
                $notifStmt->execute([$userId, "Vous avez gagné le badge : " . $badge['name']]);
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Entrée ajoutée avec succès']);

} catch (PDOException $e) {
    error_log("Erreur lors de l'ajout du poids : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
}
?> 