<?php
require_once 'includes/config.php';
require_once 'includes/exercise_manager.php';

// Vérification de la connexion
if (!isLoggedIn()) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Vous devez être connecté pour effectuer cette action'
    ];
    header('Location: login.php');
    exit;
}

$exerciseManager = new ExerciseManager($pdo);

// Traitement de l'ajout d'une séance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workoutData = [
        'date' => $_POST['date'],
        'notes' => $_POST['notes'] ?? '',
        'exercises' => []
    ];

    // Récupération des exercices de la séance
    foreach ($_POST['exercises'] as $index => $exerciseId) {
        if (!empty($exerciseId)) {
            $workoutData['exercises'][] = [
                'exercise_id' => $exerciseId,
                'duration' => intval($_POST['duration'][$index]),
                'intensity' => $_POST['intensity'][$index],
                'calories_burned' => intval($_POST['calories_burned'][$index]),
                'sets' => !empty($_POST['sets'][$index]) ? intval($_POST['sets'][$index]) : null,
                'reps' => !empty($_POST['reps'][$index]) ? intval($_POST['reps'][$index]) : null,
                'weight' => !empty($_POST['weight'][$index]) ? floatval($_POST['weight'][$index]) : null,
                'notes' => $_POST['exercise_notes'][$index] ?? null
            ];
        }
    }

    try {
        $exerciseManager->addWorkoutSession($_SESSION['user_id'], $workoutData);
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Séance d\'exercice ajoutée avec succès'
        ];
        header('Location: dashboard.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// Récupération de la liste des exercices
$exercises = $exerciseManager->searchExercise('');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une séance - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container mt-4">
        <h1>Ajouter une séance d'exercice</h1>

        <form method="POST" action="" id="workoutForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div id="exercisesContainer">
                <div class="exercise-entry card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Exercice</label>
                                <select class="form-control exercise-select" name="exercises[]" required>
                                    <option value="">Sélectionnez un exercice</option>
                                    <?php foreach ($exercises as $exercise): ?>
                                    <option value="<?php echo $exercise['id']; ?>" 
                                            data-calories="<?php echo $exercise['calories_per_hour']; ?>"
                                            data-category="<?php echo $exercise['category']; ?>">
                                        <?php echo $exercise['name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Durée (min)</label>
                                <input type="number" class="form-control duration-input" name="duration[]" min="1" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Intensité</label>
                                <select class="form-control intensity-select" name="intensity[]" required>
                                    <option value="low">Faible</option>
                                    <option value="medium">Moyenne</option>
                                    <option value="high">Élevée</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Calories</label>
                                <input type="number" class="form-control calories-input" name="calories_burned[]" min="0" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-danger remove-exercise">Retirer</button>
                            </div>
                        </div>

                        <div class="row mt-3 strength-inputs" style="display: none;">
                            <div class="col-md-2">
                                <label class="form-label">Séries</label>
                                <input type="number" class="form-control" name="sets[]" min="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Répétitions</label>
                                <input type="number" class="form-control" name="reps[]" min="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Poids (kg)</label>
                                <input type="number" class="form-control" name="weight[]" step="0.5" min="0">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="exercise_notes[]" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-secondary mb-3" id="addExercise">Ajouter un exercice</button>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total de la séance</h5>
                    <p class="card-text">
                        Durée totale: <span id="totalDuration">0</span> minutes |
                        Calories brûlées: <span id="totalCalories">0</span> kcal
                    </p>
                </div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes générales de la séance</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Enregistrer la séance</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialisation de Select2
            $('.exercise-select').select2({
                placeholder: 'Sélectionnez un exercice',
                allowClear: true
            });

            // Fonction pour calculer les calories
            function calculateCalories($container) {
                const $select = $container.find('.exercise-select');
                const $option = $select.find('option:selected');
                const duration = parseInt($container.find('.duration-input').val()) || 0;
                const intensity = $container.find('.intensity-select').val();
                
                if ($option.val() && duration > 0) {
                    const caloriesPerHour = parseFloat($option.data('calories')) || 0;
                    let multiplier = 1;
                    
                    switch (intensity) {
                        case 'low': multiplier = 0.8; break;
                        case 'medium': multiplier = 1; break;
                        case 'high': multiplier = 1.2; break;
                    }
                    
                    const calories = Math.round((caloriesPerHour * duration * multiplier) / 60);
                    $container.find('.calories-input').val(calories);
                }

                calculateTotalWorkout();
            }

            // Fonction pour calculer le total de la séance
            function calculateTotalWorkout() {
                let totalDuration = 0;
                let totalCalories = 0;

                $('.exercise-entry').each(function() {
                    totalDuration += parseInt($(this).find('.duration-input').val()) || 0;
                    totalCalories += parseInt($(this).find('.calories-input').val()) || 0;
                });

                $('#totalDuration').text(totalDuration);
                $('#totalCalories').text(totalCalories);
            }

            // Gestion de l'affichage des champs force selon le type d'exercice
            function toggleStrengthInputs($container) {
                const $select = $container.find('.exercise-select');
                const $option = $select.find('option:selected');
                const category = $option.data('category');
                
                if (category === 'strength') {
                    $container.find('.strength-inputs').show();
                    $container.find('.strength-inputs input').prop('required', true);
                } else {
                    $container.find('.strength-inputs').hide();
                    $container.find('.strength-inputs input').prop('required', false);
                }
            }

            // Événements pour le calcul des calories
            $(document).on('change', '.exercise-select, .duration-input, .intensity-select', function() {
                const $container = $(this).closest('.exercise-entry');
                calculateCalories($container);
                if ($(this).hasClass('exercise-select')) {
                    toggleStrengthInputs($container);
                }
            });

            // Ajout d'un nouvel exercice
            $('#addExercise').click(function() {
                const $newExercise = $('.exercise-entry:first').clone();
                $newExercise.find('select').val('').select2();
                $newExercise.find('input').val('');
                $newExercise.find('textarea').val('');
                $newExercise.find('.strength-inputs').hide();
                $('#exercisesContainer').append($newExercise);
            });

            // Suppression d'un exercice
            $(document).on('click', '.remove-exercise', function() {
                if ($('.exercise-entry').length > 1) {
                    $(this).closest('.exercise-entry').remove();
                    calculateTotalWorkout();
                }
            });
        });
    </script>
</body>
</html> 