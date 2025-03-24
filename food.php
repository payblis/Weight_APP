<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

// Récupération de la date sélectionnée (aujourd'hui par défaut)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$user_id = $_SESSION['user_id'];

try {
    // Récupération des repas de la journée
    $mealsStmt = $pdo->prepare("
        SELECT m.*, mt.name as meal_type_name,
               f.name as food_name, f.calories, f.proteins, f.carbs, f.fats,
               mf.quantity
        FROM meals m
        JOIN meal_types mt ON m.meal_type_id = mt.id
        LEFT JOIN meal_foods mf ON m.id = mf.meal_id
        LEFT JOIN foods f ON mf.food_id = f.id
        WHERE m.user_id = ? AND DATE(m.date) = ?
        ORDER BY m.date ASC
    ");
    $mealsStmt->execute([$user_id, $selected_date]);
    $meals = [];
    $daily_totals = [
        'calories' => 0,
        'proteins' => 0,
        'carbs' => 0,
        'fats' => 0
    ];

    // Organisation des repas par type
    while ($row = $mealsStmt->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($meals[$row['meal_type_id']])) {
            $meals[$row['meal_type_id']] = [
                'name' => $row['meal_type_name'],
                'time' => date('H:i', strtotime($row['date'])),
                'foods' => [],
                'totals' => [
                    'calories' => 0,
                    'proteins' => 0,
                    'carbs' => 0,
                    'fats' => 0
                ]
            ];
        }

        if ($row['food_name']) {
            $food = [
                'name' => $row['food_name'],
                'quantity' => $row['quantity'],
                'calories' => $row['calories'] * $row['quantity'],
                'proteins' => $row['proteins'] * $row['quantity'],
                'carbs' => $row['carbs'] * $row['quantity'],
                'fats' => $row['fats'] * $row['quantity']
            ];

            $meals[$row['meal_type_id']]['foods'][] = $food;
            $meals[$row['meal_type_id']]['totals']['calories'] += $food['calories'];
            $meals[$row['meal_type_id']]['totals']['proteins'] += $food['proteins'];
            $meals[$row['meal_type_id']]['totals']['carbs'] += $food['carbs'];
            $meals[$row['meal_type_id']]['totals']['fats'] += $food['fats'];

            $daily_totals['calories'] += $food['calories'];
            $daily_totals['proteins'] += $food['proteins'];
            $daily_totals['carbs'] += $food['carbs'];
            $daily_totals['fats'] += $food['fats'];
        }
    }

    // Récupération de l'objectif calorique
    $goalStmt = $pdo->prepare("
        SELECT daily_calories 
        FROM user_settings 
        WHERE user_id = ?
    ");
    $goalStmt->execute([$user_id]);
    $calorie_goal = $goalStmt->fetchColumn() ?: 2000; // Valeur par défaut si non définie

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Une erreur est survenue'];
}

require_once 'components/user_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Journal alimentaire</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMealModal">
            <i class="fas fa-plus me-2"></i>Ajouter un repas
        </button>
    </div>

    <!-- Sélecteur de date -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <button class="btn btn-outline-primary" onclick="changeDate(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                </div>
                <div class="col">
                    <input type="date" class="form-control" id="dateSelector" value="<?php echo $selected_date; ?>"
                           onchange="window.location.href='?date=' + this.value">
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary" onclick="changeDate(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Résumé nutritionnel -->
    <div class="row">
        <!-- Calories -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Calories
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($daily_totals['calories']); ?> / <?php echo number_format($calorie_goal); ?> kcal
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-fire fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Protéines -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Protéines
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($daily_totals['proteins'], 1); ?> g
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-drumstick-bite fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Glucides -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Glucides
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($daily_totals['carbs'], 1); ?> g
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bread-slice fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lipides -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Lipides
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($daily_totals['fats'], 1); ?> g
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cheese fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des repas -->
    <div class="row">
        <div class="col-12">
            <?php if (empty($meals)): ?>
                <div class="card shadow mb-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-utensils fa-3x text-gray-300 mb-3"></i>
                        <p class="mb-0">Aucun repas enregistré pour cette journée</p>
                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addMealModal">
                            Ajouter un repas
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($meals as $meal): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?php echo htmlspecialchars($meal['name']); ?> - 
                                <?php echo $meal['time']; ?>
                            </h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-primary" onclick="editMeal(<?php echo $meal['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteMeal(<?php echo $meal['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($meal['foods'])): ?>
                                <p class="text-muted mb-0">Aucun aliment enregistré</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
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
                                                    <td><?php echo $food['quantity']; ?></td>
                                                    <td><?php echo number_format($food['calories']); ?> kcal</td>
                                                    <td><?php echo number_format($food['proteins'], 1); ?> g</td>
                                                    <td><?php echo number_format($food['carbs'], 1); ?> g</td>
                                                    <td><?php echo number_format($food['fats'], 1); ?> g</td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="table-active">
                                                <td colspan="2"><strong>Total</strong></td>
                                                <td><strong><?php echo number_format($meal['totals']['calories']); ?> kcal</strong></td>
                                                <td><strong><?php echo number_format($meal['totals']['proteins'], 1); ?> g</strong></td>
                                                <td><strong><?php echo number_format($meal['totals']['carbs'], 1); ?> g</strong></td>
                                                <td><strong><?php echo number_format($meal['totals']['fats'], 1); ?> g</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Ajout/Modification de repas -->
<div class="modal fade" id="addMealModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un repas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="mealForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="mealType" class="form-label">Type de repas</label>
                            <select class="form-control" id="mealType" name="meal_type_id" required>
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM meal_types ORDER BY id");
                                $stmt->execute();
                                $meal_types = $stmt->fetchAll();
                                foreach ($meal_types as $type):
                                ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="mealTime" class="form-label">Heure</label>
                            <input type="time" class="form-control" id="mealTime" name="time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Aliments</label>
                        <div id="foodsList">
                            <div class="food-item row mb-2">
                                <div class="col-md-6">
                                    <select class="form-control food-select" name="foods[]" required>
                                        <option value="">Sélectionner un aliment</option>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT * FROM foods ORDER BY name");
                                        $stmt->execute();
                                        $foods = $stmt->fetchAll();
                                        foreach ($foods as $food):
                                        ?>
                                            <option value="<?php echo $food['id']; ?>"><?php echo htmlspecialchars($food['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="quantities[]" placeholder="Quantité" step="0.1" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger remove-food">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary mt-2" onclick="addFoodRow()">
                            <i class="fas fa-plus me-1"></i>Ajouter un aliment
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveMeal()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
let mealModal;

// Changement de date
function changeDate(offset) {
    const date = new Date(document.getElementById('dateSelector').value);
    date.setDate(date.getDate() + offset);
    window.location.href = '?date=' + date.toISOString().split('T')[0];
}

// Ajout d'une ligne d'aliment
function addFoodRow() {
    const template = document.querySelector('.food-item').cloneNode(true);
    template.querySelector('.food-select').value = '';
    template.querySelector('input[name="quantities[]"]').value = '';
    document.getElementById('foodsList').appendChild(template);
    
    // Gestionnaire pour le bouton de suppression
    template.querySelector('.remove-food').addEventListener('click', function() {
        if (document.querySelectorAll('.food-item').length > 1) {
            this.closest('.food-item').remove();
        }
    });
}

// Sauvegarde d'un repas
async function saveMeal() {
    const form = document.getElementById('mealForm');
    const formData = new FormData(form);
    formData.append('date', document.getElementById('dateSelector').value);
    
    try {
        const response = await fetch('api/add_meal.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Succès', 'Le repas a été enregistré', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    }
}

// Suppression d'un repas
async function deleteMeal(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce repas ?')) {
        try {
            const response = await fetch('api/delete_meal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Succès', 'Le repas a été supprimé', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Erreur', 'Une erreur est survenue', 'error');
        }
    }
}

// Édition d'un repas
async function editMeal(id) {
    try {
        const response = await fetch(`api/get_meal.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('mealType').value = data.meal.meal_type_id;
            document.getElementById('mealTime').value = data.meal.time;
            document.getElementById('notes').value = data.meal.notes || '';
            
            // Suppression des lignes d'aliments existantes sauf la première
            const foodsList = document.getElementById('foodsList');
            while (foodsList.children.length > 1) {
                foodsList.removeChild(foodsList.lastChild);
            }
            
            // Ajout des aliments du repas
            data.meal.foods.forEach((food, index) => {
                if (index === 0) {
                    // Première ligne
                    foodsList.querySelector('.food-select').value = food.food_id;
                    foodsList.querySelector('input[name="quantities[]"]').value = food.quantity;
                } else {
                    // Nouvelles lignes
                    addFoodRow();
                    const newRow = foodsList.lastChild;
                    newRow.querySelector('.food-select').value = food.food_id;
                    newRow.querySelector('input[name="quantities[]"]').value = food.quantity;
                }
            });
            
            mealModal.show();
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    mealModal = new bootstrap.Modal(document.getElementById('addMealModal'));
    
    // Gestionnaire pour les boutons de suppression d'aliment
    document.querySelectorAll('.remove-food').forEach(button => {
        button.addEventListener('click', function() {
            if (document.querySelectorAll('.food-item').length > 1) {
                this.closest('.food-item').remove();
            }
        });
    });
    
    // Réinitialisation du formulaire à l'ouverture du modal
    document.getElementById('addMealModal').addEventListener('show.bs.modal', function() {
        document.getElementById('mealForm').reset();
        document.getElementById('mealTime').value = new Date().toTimeString().substr(0, 5);
        
        // Suppression des lignes d'aliments supplémentaires
        const foodsList = document.getElementById('foodsList');
        while (foodsList.children.length > 1) {
            foodsList.removeChild(foodsList.lastChild);
        }
    });
});
</script>

<?php require_once 'components/user_footer.php'; ?> 