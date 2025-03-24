<?php
require_once 'includes/config.php';

// Vérification de la connexion
if (!isLoggedIn()) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Vous devez être connecté pour accéder à cette page'
    ];
    header('Location: login.php');
    exit;
}

// Traitement de l'ajout d'un objectif
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startWeight = floatval($_POST['start_weight']);
    $targetWeight = floatval($_POST['target_weight']);
    $weeklyGoal = floatval($_POST['weekly_goal']);
    $startDate = $_POST['start_date'];
    $targetDate = $_POST['target_date'];

    $errors = [];

    // Validations
    if ($startWeight <= 0 || $startWeight > 300) {
        $errors[] = "Le poids de départ doit être compris entre 0 et 300 kg";
    }
    if ($targetWeight <= 0 || $targetWeight > 300) {
        $errors[] = "Le poids cible doit être compris entre 0 et 300 kg";
    }
    if ($weeklyGoal <= 0 || $weeklyGoal > 2) {
        $errors[] = "L'objectif hebdomadaire doit être compris entre 0 et 2 kg";
    }
    if (!strtotime($startDate) || !strtotime($targetDate)) {
        $errors[] = "Dates invalides";
    }
    if (strtotime($targetDate) <= strtotime($startDate)) {
        $errors[] = "La date cible doit être postérieure à la date de début";
    }

    if (empty($errors)) {
        try {
            // Désactivation des objectifs précédents
            $stmt = $pdo->prepare("
                UPDATE weight_goals 
                SET status = 'abandoned' 
                WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$_SESSION['user_id']]);

            // Ajout du nouvel objectif
            $stmt = $pdo->prepare("
                INSERT INTO weight_goals 
                (user_id, start_weight, target_weight, weekly_goal, start_date, target_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $startWeight,
                $targetWeight,
                $weeklyGoal,
                $startDate,
                $targetDate
            ]);

            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Nouvel objectif ajouté avec succès'
            ];
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'enregistrement de l'objectif";
        }
    }
}

// Récupération des objectifs
$stmt = $pdo->prepare("
    SELECT * FROM weight_goals 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$goals = $stmt->fetchAll();

// Récupération du dernier poids enregistré
$stmt = $pdo->prepare("
    SELECT weight 
    FROM daily_logs 
    WHERE user_id = ? 
    ORDER BY date DESC 
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$lastWeight = $stmt->fetchColumn() ?: '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Objectifs - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Nouvel objectif</h5>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php echo implode('<br>', $errors); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="start_weight" class="form-label">Poids de départ (kg)</label>
                                <input type="number" class="form-control" id="start_weight" name="start_weight" 
                                       step="0.1" required value="<?php echo $lastWeight; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="target_weight" class="form-label">Poids cible (kg)</label>
                                <input type="number" class="form-control" id="target_weight" name="target_weight" 
                                       step="0.1" required>
                            </div>

                            <div class="mb-3">
                                <label for="weekly_goal" class="form-label">Objectif hebdomadaire (kg)</label>
                                <input type="number" class="form-control" id="weekly_goal" name="weekly_goal" 
                                       step="0.1" max="2" required>
                                <small class="text-muted">Maximum recommandé : 1 kg par semaine</small>
                            </div>

                            <div class="mb-3">
                                <label for="start_date" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="target_date" class="form-label">Date cible</label>
                                <input type="date" class="form-control" id="target_date" name="target_date" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Définir l'objectif</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Historique des objectifs</h5>
                        
                        <?php if (empty($goals)): ?>
                            <p class="text-muted">Aucun objectif défini</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($goals as $goal): ?>
                                    <div class="list-group-item">
                                        <h6 class="mb-1">
                                            <?php echo number_format($goal['start_weight'], 1); ?> kg → 
                                            <?php echo number_format($goal['target_weight'], 1); ?> kg
                                            <span class="badge bg-<?php 
                                                echo $goal['status'] === 'active' ? 'success' : 
                                                    ($goal['status'] === 'completed' ? 'primary' : 'secondary');
                                            ?>">
                                                <?php echo $goal['status']; ?>
                                            </span>
                                        </h6>
                                        <p class="mb-1">
                                            Objectif : <?php echo number_format($goal['weekly_goal'], 1); ?> kg/semaine
                                        </p>
                                        <small class="text-muted">
                                            Du <?php echo date('d/m/Y', strtotime($goal['start_date'])); ?>
                                            au <?php echo date('d/m/Y', strtotime($goal['target_date'])); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Calcul automatique de la date cible basé sur l'objectif hebdomadaire
            function updateTargetDate() {
                const startWeight = parseFloat($('#start_weight').val()) || 0;
                const targetWeight = parseFloat($('#target_weight').val()) || 0;
                const weeklyGoal = parseFloat($('#weekly_goal').val()) || 0;
                const startDate = new Date($('#start_date').val());

                if (startWeight && targetWeight && weeklyGoal && startDate) {
                    const weightDiff = Math.abs(startWeight - targetWeight);
                    const weeks = Math.ceil(weightDiff / weeklyGoal);
                    const targetDate = new Date(startDate);
                    targetDate.setDate(targetDate.getDate() + (weeks * 7));
                    
                    $('#target_date').val(targetDate.toISOString().split('T')[0]);
                }
            }

            $('#start_weight, #target_weight, #weekly_goal, #start_date').on('change', updateTargetDate);
        });
    </script>
</body>
</html> 