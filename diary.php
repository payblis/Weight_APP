<?php
require_once 'includes/config.php';
require_once 'includes/food_manager.php';

// Redirection si non connecté
redirectIfNotLoggedIn();

$foodManager = new FoodManager($pdo);

// Récupération de la date (aujourd'hui par défaut)
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

try {
    // Récupération des repas de la journée
    $meals = $foodManager->getMealsByDate($_SESSION['user_id'], $date);
    
    // Calcul des totaux nutritionnels
    $nutrition = $foodManager->getDailyNutrition($_SESSION['user_id'], $date);
    
} catch (Exception $e) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => $e->getMessage()
    ];
}

include 'components/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Journal Alimentaire</h1>
                <a href="add-meal.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter un repas
                </a>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-4">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" class="form-control" 
                                   value="<?php echo htmlspecialchars($date); ?>" onchange="this.form.submit()">
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($nutrition)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Résumé Nutritionnel</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="nutrition-stat">
                                <h4>Calories</h4>
                                <p><?php echo round($nutrition['total_calories']); ?> kcal</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="nutrition-stat">
                                <h4>Protéines</h4>
                                <p><?php echo round($nutrition['total_proteins'], 1); ?> g</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="nutrition-stat">
                                <h4>Glucides</h4>
                                <p><?php echo round($nutrition['total_carbs'], 1); ?> g</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="nutrition-stat">
                                <h4>Lipides</h4>
                                <p><?php echo round($nutrition['total_fats'], 1); ?> g</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($meals)): ?>
                <?php foreach ($meals as $meal): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?php echo ucfirst($meal['meal_type']); ?>
                            <small class="text-muted">
                                <?php
                                $total_calories = 0;
                                foreach ($meal['foods'] as $food) {
                                    $total_calories += $food['calories'] * $food['servings'];
                                }
                                echo round($total_calories) . ' kcal';
                                ?>
                            </small>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Aliment</th>
                                        <th>Quantité</th>
                                        <th>Calories</th>
                                        <th>Protéines</th>
                                        <th>Glucides</th>
                                        <th>Lipides</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($meal['foods'] as $food): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($food['name']); ?></td>
                                        <td>
                                            <?php 
                                            echo $food['servings'] . ' × ' . 
                                                 $food['serving_size'] . ' ' . 
                                                 $food['serving_unit']; 
                                            ?>
                                        </td>
                                        <td><?php echo round($food['calories'] * $food['servings']); ?> kcal</td>
                                        <td><?php echo round($food['proteins'] * $food['servings'], 1); ?> g</td>
                                        <td><?php echo round($food['carbs'] * $food['servings'], 1); ?> g</td>
                                        <td><?php echo round($food['fats'] * $food['servings'], 1); ?> g</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($meal['notes'])): ?>
                        <div class="meal-notes mt-3">
                            <strong>Notes :</strong>
                            <p><?php echo nl2br(htmlspecialchars($meal['notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="alert alert-info">
                Aucun repas enregistré pour cette date.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.nutrition-stat {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.nutrition-stat h4 {
    margin-bottom: 0.5rem;
    color: var(--primary-color);
}

.nutrition-stat p {
    font-size: 1.25rem;
    margin-bottom: 0;
    font-weight: bold;
}

.meal-notes {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
}

.meal-notes p {
    margin-bottom: 0;
}
</style>

<?php include 'components/footer.php'; ?> 