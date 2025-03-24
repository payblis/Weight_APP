<?php
require_once 'includes/config.php';
require_once 'includes/food_manager.php';

// Vérification de la connexion
if (!isLoggedIn()) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Vous devez être connecté pour effectuer cette action'
    ];
    header('Location: login.php');
    exit;
}

$foodManager = new FoodManager($pdo);

// Traitement de l'ajout d'un repas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mealData = [
        'date' => $_POST['date'],
        'meal_type' => $_POST['meal_type'],
        'notes' => $_POST['notes'] ?? '',
        'foods' => []
    ];

    // Récupération des aliments du repas
    foreach ($_POST['foods'] as $index => $foodId) {
        if (!empty($foodId) && !empty($_POST['servings'][$index])) {
            $mealData['foods'][] = [
                'food_id' => $foodId,
                'servings' => floatval($_POST['servings'][$index])
            ];
        }
    }

    try {
        $foodManager->addMeal($_SESSION['user_id'], $mealData);
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Repas ajouté avec succès'
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

// Récupération de la liste des aliments pour l'autocomplétion
$foods = $foodManager->searchFood('');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un repas - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container mt-4">
        <h1>Ajouter un repas</h1>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Suggestion IA</h5>
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="preferences" placeholder="Préférences alimentaires (optionnel)">
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-primary" id="suggestMeal">
                                    <i class="fas fa-magic"></i> Suggérer un repas
                                </button>
                            </div>
                        </div>
                        <div id="suggestionResult" class="mt-3" style="display: none;">
                            <h6 class="suggestion-title"></h6>
                            <p class="suggestion-description"></p>
                            <div class="suggestion-foods"></div>
                            <button type="button" class="btn btn-success btn-sm mt-2" id="applySuggestion">
                                Appliquer cette suggestion
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="" id="mealForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="meal_type" class="form-label">Type de repas</label>
                    <select class="form-control" id="meal_type" name="meal_type" required>
                        <option value="breakfast">Petit-déjeuner</option>
                        <option value="lunch">Déjeuner</option>
                        <option value="dinner">Dîner</option>
                        <option value="snack">Collation</option>
                    </select>
                </div>
            </div>

            <div id="foodsContainer">
                <div class="food-entry row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Aliment</label>
                        <select class="form-control food-select" name="foods[]" required>
                            <option value="">Sélectionnez un aliment</option>
                            <?php foreach ($foods as $food): ?>
                            <option value="<?php echo $food['id']; ?>" 
                                    data-calories="<?php echo $food['calories']; ?>"
                                    data-proteins="<?php echo $food['proteins']; ?>"
                                    data-carbs="<?php echo $food['carbs']; ?>"
                                    data-fats="<?php echo $food['fats']; ?>"
                                    data-serving-size="<?php echo $food['serving_size']; ?>"
                                    data-serving-unit="<?php echo $food['serving_unit']; ?>">
                                <?php echo $food['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Portions</label>
                        <input type="number" class="form-control serving-input" name="servings[]" step="0.1" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger remove-food">Retirer</button>
                    </div>
                    <div class="col-12 mt-2 nutrition-info" style="display: none;">
                        <small class="text-muted">
                            Calories: <span class="calories">0</span> kcal |
                            Protéines: <span class="proteins">0</span>g |
                            Glucides: <span class="carbs">0</span>g |
                            Lipides: <span class="fats">0</span>g
                        </small>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-secondary mb-3" id="addFood">Ajouter un aliment</button>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total des nutriments</h5>
                    <p class="card-text">
                        Calories: <span id="totalCalories">0</span> kcal |
                        Protéines: <span id="totalProteins">0</span>g |
                        Glucides: <span id="totalCarbs">0</span>g |
                        Lipides: <span id="totalFats">0</span>g
                    </p>
                </div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Enregistrer le repas</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialisation de Select2
            $('.food-select').select2({
                placeholder: 'Sélectionnez un aliment',
                allowClear: true
            });

            // Fonction pour calculer les nutriments
            function calculateNutrition($container) {
                const $select = $container.find('.food-select');
                const $servingInput = $container.find('.serving-input');
                const servings = parseFloat($servingInput.val()) || 0;
                const $option = $select.find('option:selected');

                if ($option.val()) {
                    const calories = parseFloat($option.data('calories')) * servings;
                    const proteins = parseFloat($option.data('proteins')) * servings;
                    const carbs = parseFloat($option.data('carbs')) * servings;
                    const fats = parseFloat($option.data('fats')) * servings;

                    $container.find('.calories').text(calories.toFixed(1));
                    $container.find('.proteins').text(proteins.toFixed(1));
                    $container.find('.carbs').text(carbs.toFixed(1));
                    $container.find('.fats').text(fats.toFixed(1));
                    $container.find('.nutrition-info').show();
                } else {
                    $container.find('.nutrition-info').hide();
                }

                calculateTotalNutrition();
            }

            // Fonction pour calculer le total des nutriments
            function calculateTotalNutrition() {
                let totalCalories = 0;
                let totalProteins = 0;
                let totalCarbs = 0;
                let totalFats = 0;

                $('.food-entry').each(function() {
                    totalCalories += parseFloat($(this).find('.calories').text()) || 0;
                    totalProteins += parseFloat($(this).find('.proteins').text()) || 0;
                    totalCarbs += parseFloat($(this).find('.carbs').text()) || 0;
                    totalFats += parseFloat($(this).find('.fats').text()) || 0;
                });

                $('#totalCalories').text(totalCalories.toFixed(1));
                $('#totalProteins').text(totalProteins.toFixed(1));
                $('#totalCarbs').text(totalCarbs.toFixed(1));
                $('#totalFats').text(totalFats.toFixed(1));
            }

            // Événements pour le calcul des nutriments
            $(document).on('change', '.food-select, .serving-input', function() {
                calculateNutrition($(this).closest('.food-entry'));
            });

            // Ajout d'un nouvel aliment
            $('#addFood').click(function() {
                const $newFood = $('.food-entry:first').clone();
                $newFood.find('select').val('').select2();
                $newFood.find('input').val('');
                $newFood.find('.nutrition-info').hide();
                $('#foodsContainer').append($newFood);
            });

            // Suppression d'un aliment
            $(document).on('click', '.remove-food', function() {
                if ($('.food-entry').length > 1) {
                    $(this).closest('.food-entry').remove();
                    calculateTotalNutrition();
                }
            });

            // Fonction pour demander une suggestion
            $('#suggestMeal').click(function() {
                const preferences = $('#preferences').val().split(',').map(p => p.trim()).filter(p => p);
                const mealType = $('#meal_type').val();

                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Génération...');

                $.ajax({
                    url: 'suggest-meal.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        meal_type: mealType,
                        preferences: preferences
                    }),
                    success: function(response) {
                        const data = response.suggestion;
                        
                        // Affichage de la suggestion
                        $('#suggestionResult .suggestion-title').text(data.nom_du_repas);
                        $('#suggestionResult .suggestion-description').text(data.description);
                        
                        const foodsHtml = data.aliments.map(food => `
                            <div class="food-suggestion mb-2">
                                <strong>${food.nom}</strong> (${food.quantite}${food.unite})
                                <br>
                                <small class="text-muted">
                                    Calories: ${food.calories} kcal | 
                                    Protéines: ${food.proteines}g | 
                                    Glucides: ${food.glucides}g | 
                                    Lipides: ${food.lipides}g
                                </small>
                            </div>
                        `).join('');
                        
                        $('#suggestionResult .suggestion-foods').html(foodsHtml);
                        $('#suggestionResult').show();
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.error || 'Erreur lors de la génération';
                        alert(error);
                    },
                    complete: function() {
                        $('#suggestMeal').prop('disabled', false).html('<i class="fas fa-magic"></i> Suggérer un repas');
                    }
                });
            });

            // Application de la suggestion
            $('#applySuggestion').click(function() {
                // Suppression des aliments existants
                $('.food-entry:not(:first)').remove();
                $('.food-entry:first').find('select').val('').trigger('change');
                $('.food-entry:first').find('input').val('');
                
                // Ajout des aliments suggérés
                const foods = $('#suggestionResult .food-suggestion').each(function(index) {
                    if (index > 0) {
                        $('#addFood').click();
                    }
                    
                    const foodName = $(this).find('strong').text();
                    const $lastEntry = $('.food-entry:last');
                    
                    // Recherche de l'aliment dans la liste
                    const $option = $lastEntry.find('.food-select option').filter(function() {
                        return $(this).text().trim() === foodName;
                    });
                    
                    if ($option.length) {
                        $lastEntry.find('.food-select').val($option.val()).trigger('change');
                        // Extraction de la quantité depuis le texte
                        const quantityMatch = $(this).text().match(/\((\d+)/);
                        if (quantityMatch) {
                            $lastEntry.find('.serving-input').val(quantityMatch[1]).trigger('change');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html> 