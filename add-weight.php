<?php
require_once 'includes/config.php';

// Vérification de la connexion
if (!isLoggedIn()) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Vous devez être connecté pour effectuer cette action'
    ];
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = floatval($_POST['weight']);
    $date = $_POST['date'];
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];

    // Validations
    if ($weight <= 0 || $weight > 300) {
        $errors[] = "Le poids doit être compris entre 0 et 300 kg";
    }

    if (!strtotime($date)) {
        $errors[] = "Date invalide";
    }

    // Vérification si un poids existe déjà pour cette date
    $stmt = $pdo->prepare("
        SELECT id 
        FROM daily_logs 
        WHERE user_id = ? AND date = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $date]);

    if ($stmt->rowCount() > 0) {
        $errors[] = "Un poids a déjà été enregistré pour cette date";
    }

    if (empty($errors)) {
        try {
            // Début de la transaction
            $pdo->beginTransaction();

            // Ajout du nouveau poids
            $stmt = $pdo->prepare("
                INSERT INTO daily_logs (user_id, date, weight, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $date, $weight, $notes]);

            // Récupération de l'objectif actif
            $stmt = $pdo->prepare("
                SELECT start_weight, target_weight, start_date, target_date
                FROM weight_goals
                WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $goal = $stmt->fetch();

            // Si l'objectif est atteint
            if ($goal && $weight <= $goal['target_weight']) {
                // Marquer l'objectif comme complété
                $stmt = $pdo->prepare("
                    UPDATE weight_goals
                    SET status = 'completed'
                    WHERE user_id = ? AND status = 'active'
                ");
                $stmt->execute([$_SESSION['user_id']]);

                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => 'Félicitations ! Vous avez atteint votre objectif de poids !'
                ];
            } else {
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => 'Nouveau poids enregistré avec succès'
                ];
            }

            // Validation de la transaction
            $pdo->commit();

            // Redirection vers le tableau de bord
            header('Location: dashboard.php');
            exit;

        } catch (PDOException $e) {
            // Annulation de la transaction en cas d'erreur
            $pdo->rollBack();
            
            $errors[] = "Erreur lors de l'enregistrement du poids : " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => implode('<br>', $errors)
        ];
        header('Location: dashboard.php');
        exit;
    }
} else {
    // Si la méthode n'est pas POST, redirection vers le tableau de bord
    header('Location: dashboard.php');
    exit;
} 