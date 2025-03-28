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

// Vérifier si un objectif est en attente de confirmation
if (!isset($_SESSION['pending_goal'])) {
    redirect('goals.php');
}

$pending_goal = $_SESSION['pending_goal'];
$user_id = $_SESSION['user_id'];

// Traitement de la confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm'])) {
        // Calculer les calories finales
        $daily_calories = $pending_goal['tdee'] + $pending_goal['daily_adjustment'];
        
        // Vérifier que l'apport calorique reste positif
        if ($daily_calories <= 0) {
            $error = "L'apport calorique quotidien doit rester positif pour maintenir une bonne santé. Veuillez ajuster votre objectif.";
        } else {
            // Mettre à jour les objectifs de l'utilisateur
            $sql = "UPDATE user_profiles SET 
                    daily_calories = ?,
                    protein_ratio = 0.3,
                    carbs_ratio = 0.4,
                    fat_ratio = 0.3,
                    updated_at = NOW()
                    WHERE user_id = ?";
            update($sql, [$daily_calories, $user_id]);
            
            // Créer l'objectif
            $sql = "INSERT INTO goals (user_id, target_weight, target_date, notes, status, created_at) 
                    VALUES (?, ?, ?, ?, 'en_cours', NOW())";
            insert($sql, [
                $user_id,
                $pending_goal['target_weight'],
                $pending_goal['target_date']->format('Y-m-d'),
                $pending_goal['notes']
            ]);
            
            // Nettoyer la session
            unset($_SESSION['pending_goal']);
            
            // Rediriger vers la page des objectifs
            redirect('goals.php?success=1');
        }
    } else {
        // Si l'utilisateur annule, retourner à la page des objectifs
        unset($_SESSION['pending_goal']);
        redirect('goals.php');
    }
}

// Récupérer les informations de l'utilisateur pour la navigation
$sql = "SELECT * FROM users WHERE id = ?";
$user = fetchOne($sql, [$user_id]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer l'objectif - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">Confirmation de l'objectif</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Attention !</strong> L'ajustement calorique nécessaire pour atteindre votre objectif est important :
                            <strong><?php echo number_format(abs($pending_goal['daily_adjustment']), 0); ?> calories par jour</strong>
                        </div>
                        
                        <div class="mb-4">
                            <h6>Détails de l'objectif :</h6>
                            <ul class="list-unstyled">
                                <li><strong>Poids cible :</strong> <?php echo number_format($pending_goal['target_weight'], 1); ?> kg</li>
                                <li><strong>Date cible :</strong> <?php echo $pending_goal['target_date']->format('d/m/Y'); ?></li>
                                <li><strong>Calories de base (TDEE) :</strong> <?php echo number_format($pending_goal['tdee'], 0); ?> calories</li>
                                <li><strong>Calories finales :</strong> <?php echo number_format($pending_goal['tdee'] + $pending_goal['daily_adjustment'], 0); ?> calories</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Un ajustement calorique important peut être difficile à maintenir sur le long terme. 
                            Nous vous recommandons de :
                            <ul class="mb-0">
                                <li>Consulter un professionnel de santé avant de commencer</li>
                                <li>Augmenter progressivement l'activité physique</li>
                                <li>Suivre régulièrement votre progression</li>
                            </ul>
                        </div>
                        
                        <form method="post" class="d-grid gap-2">
                            <button type="submit" name="confirm" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i>Confirmer l'objectif
                            </button>
                            <button type="submit" name="cancel" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Annuler et modifier l'objectif
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 