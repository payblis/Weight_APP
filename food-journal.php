<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

// Récupération de la date sélectionnée (aujourd'hui par défaut)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Récupération des objectifs caloriques de l'utilisateur
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT daily_calorie_goal FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$daily_calorie_goal = $user['daily_calorie_goal'];

// Récupération des repas de la journée
$stmt = $pdo->prepare("
    SELECT m.*, mt.name as meal_type_name,
           SUM(mf.quantity * f.calories) as total_calories,
           SUM(mf.quantity * f.proteins) as total_proteins,
           SUM(mf.quantity * f.carbs) as total_carbs,
           SUM(mf.quantity * f.fats) as total_fats
    FROM meals m
    LEFT JOIN meal_types mt ON m.meal_type_id = mt.id
    LEFT JOIN meal_foods mf ON m.id = mf.meal_id
    LEFT JOIN foods f ON mf.food_id = f.id
    WHERE m.user_id = ? AND DATE(m.date) = ?
    GROUP BY m.id
    ORDER BY m.date ASC
");
$stmt->execute([$user_id, $selected_date]);
$meals = $stmt->fetchAll();

// Calcul des totaux de la journée
$daily_totals = [
    'calories' => 0,
    'proteins' => 0,
    'carbs' => 0,
    'fats' => 0
];

foreach ($meals as $meal) {
    $daily_totals['calories'] += $meal['total_calories'];
    $daily_totals['proteins'] += $meal['total_proteins'];
    $daily_totals['carbs'] += $meal['total_carbs'];
    $daily_totals['fats'] += $meal['total_fats'];
}

// En-tête
require_once 'components/user_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Journal alimentaire</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMealModal">
            <i class="fas fa-plus"></i> Ajouter un repas
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

    <div class="row">
        <!-- Résumé nutritionnel -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Résumé nutritionnel</h6>
                </div>
                <div class="card-body">
                    <!-- Calories -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Calories</span>
                            <span><?php echo number_format($daily_totals['calories']); ?> / <?php echo number_format($daily_calorie_goal); ?> kcal</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo min(100, ($daily_totals['calories'] / $daily_calorie_goal) * 100); ?>%"
                                 aria-valuenow="<?php echo $daily_totals['calories']; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="<?php echo $daily_calorie_goal; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Protéines -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Protéines</span>
                            <span><?php echo number_format($daily_totals['proteins']); ?>g</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: <?php echo min(100, ($daily_totals['proteins'] / ($daily_calorie_goal * 0.3 / 4)) * 100); ?>%">
                            </div>
                        </div>
                    </div>

                    <!-- Glucides -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Glucides</span>
                            <span><?php echo number_format($daily_totals['carbs']); ?>g</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: <?php echo min(100, ($daily_totals['carbs'] / ($daily_calorie_goal * 0.5 / 4)) * 100); ?>%">
                            </div>
                        </div>
                    </div>

                    <!-- Lipides -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Lipides</span>
                            <span><?php echo number_format($daily_totals['fats']); ?>g</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-danger" role="progressbar" 
                                 style="width: <?php echo min(100, ($daily_totals['fats'] / ($daily_calorie_goal * 0.2 / 9)) * 100); ?>%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des repas -->
        <div class="col-xl-8 col-lg-7">
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
                                <?php echo htmlspecialchars($meal['meal_type_name']); ?> - 
                                <?php echo date('H:i', strtotime($meal['date'])); ?>
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
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
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
                                        <?php
                                        $stmt = $pdo->prepare("
                                            SELECT f.name, mf.quantity, 
                                                   mf.quantity * f.calories as calories,
                                                   mf.quantity * f.proteins as proteins,
                                                   mf.quantity * f.carbs as carbs,
                                                   mf.quantity * f.fats as fats
                                            FROM meal_foods mf
                                            JOIN foods f ON mf.food_id = f.id
                                            WHERE mf.meal_id = ?
                                        ");
                                        $stmt->execute([$meal['id']]);
                                        $foods = $stmt->fetchAll();
                                        
                                        foreach ($foods as $food):
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($food['name']); ?></td>
                                                <td><?php echo number_format($food['quantity'], 0); ?>g</td>
                                                <td><?php echo number_format($food['calories'], 0); ?> kcal</td>
                                                <td><?php echo number_format($food['proteins'], 1); ?>g</td>
                                                <td><?php echo number_format($food['carbs'], 1); ?>g</td>
                                                <td><?php echo number_format($food['fats'], 1); ?>g</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="2">Total</td>
                                            <td><?php echo number_format($meal['total_calories'], 0); ?> kcal</td>
                                            <td><?php echo number_format($meal['total_proteins'], 1); ?>g</td>
                                            <td><?php echo number_format($meal['total_carbs'], 1); ?>g</td>
                                            <td><?php echo number_format($meal['total_fats'], 1); ?>g</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php if ($meal['notes']): ?>
                                <div class="mt-3">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($meal['notes'])); ?></small>
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
                <h5 class="modal-title" id="modalTitle">Ajouter un repas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="mealForm">
                    <input type="hidden" id="mealId" name="id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="mealType" class="form-label">Type de repas</label>
                            <select class="form-control" id="mealType" name="meal_type_id" required>
                                <?php foreach (MEAL_TYPES as $id => $name): ?>
                                    <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
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
                            <!-- Les aliments seront ajoutés ici dynamiquement -->
                        </div>
                        <button type="button" class="btn btn-outline-primary mt-2" onclick="addFoodRow()">
                            <i class="fas fa-plus"></i> Ajouter un aliment
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
let foodSearchTimeout;
const modal = new bootstrap.Modal(document.getElementById('addMealModal'));

// Changement de date
function changeDate(offset) {
    const date = new Date(document.getElementById('dateSelector').value);
    date.setDate(date.getDate() + offset);
    window.location.href = '?date=' + date.toISOString().split('T')[0];
}

// Ajout d'une ligne d'aliment
function addFoodRow(food = null) {
    const row = document.createElement('div');
    row.className = 'food-row row mb-2';
    row.innerHTML = `
        <div class="col-md-6">
            <div class="food-search position-relative">
                <input type="text" class="form-control" placeholder="Rechercher un aliment" 
                       onkeyup="searchFood(this)" ${food ? `value="${food.name}"` : ''}>
                <input type="hidden" name="food_ids[]" value="${food ? food.id : ''}" required>
                <div class="food-results position-absolute w-100 d-none"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <input type="number" class="form-control" name="quantities[]" placeholder="Quantité" 
                       value="${food ? food.quantity : ''}" required>
                <span class="input-group-text">g</span>
            </div>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger" onclick="removeFoodRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    document.getElementById('foodsList').appendChild(row);
}

// Suppression d'une ligne d'aliment
function removeFoodRow(button) {
    button.closest('.food-row').remove();
}

// Recherche d'aliments
function searchFood(input) {
    clearTimeout(foodSearchTimeout);
    const resultsDiv = input.parentElement.querySelector('.food-results');
    const query = input.value.trim();
    
    if (query.length < 2) {
        resultsDiv.classList.add('d-none');
        return;
    }
    
    foodSearchTimeout = setTimeout(() => {
        fetch(`api/search_food.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.foods.length > 0) {
                    resultsDiv.innerHTML = data.foods.map(food => `
                        <div class="p-2 border-bottom hover-bg-light cursor-pointer" 
                             onclick="selectFood(this, ${food.id}, '${food.name}')">
                            ${food.name}
                        </div>
                    `).join('');
                    resultsDiv.classList.remove('d-none');
                } else {
                    resultsDiv.classList.add('d-none');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultsDiv.classList.add('d-none');
            });
    }, 300);
}

// Sélection d'un aliment
function selectFood(div, foodId, foodName) {
    const row = div.closest('.food-row');
    const input = row.querySelector('input[type="text"]');
    const hiddenInput = row.querySelector('input[type="hidden"]');
    
    input.value = foodName;
    hiddenInput.value = foodId;
    div.parentElement.classList.add('d-none');
}

// Édition d'un repas
function editMeal(mealId) {
    fetch(`api/get_meal.php?id=${mealId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Modifier le repas';
                document.getElementById('mealId').value = data.meal.id;
                document.getElementById('mealType').value = data.meal.meal_type_id;
                document.getElementById('mealTime').value = data.meal.date.split(' ')[1].substr(0, 5);
                document.getElementById('notes').value = data.meal.notes;
                
                document.getElementById('foodsList').innerHTML = '';
                data.foods.forEach(food => addFoodRow(food));
                
                modal.show();
            } else {
                showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Erreur', 'Une erreur est survenue', 'error');
        });
}

// Suppression d'un repas
function deleteMeal(mealId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce repas ?')) {
        fetch('api/delete_meal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: mealId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Succès', 'Le repas a été supprimé', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Erreur', 'Une erreur est survenue', 'error');
        });
    }
}

// Enregistrement d'un repas
function saveMeal() {
    const formData = new FormData(document.getElementById('mealForm'));
    formData.append('date', document.getElementById('dateSelector').value);
    
    const mealId = formData.get('id');
    const endpoint = mealId ? 'api/update_meal.php' : 'api/add_meal.php';
    
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Succès', 'Le repas a été enregistré', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Réinitialisation du formulaire à l'ouverture du modal
    document.getElementById('addMealModal').addEventListener('show.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Ajouter un repas';
        document.getElementById('mealForm').reset();
        document.getElementById('mealId').value = '';
        document.getElementById('mealTime').value = new Date().toTimeString().substr(0, 5);
        document.getElementById('foodsList').innerHTML = '';
        addFoodRow();
    });
    
    // Fermeture des résultats de recherche au clic en dehors
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.food-search')) {
            document.querySelectorAll('.food-results').forEach(div => div.classList.add('d-none'));
        }
    });
});
</script>

<style>
.food-results {
    max-height: 200px;
    overflow-y: auto;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    z-index: 1000;
}

.food-results > div:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.hover-bg-light:hover {
    background-color: #f8f9fa;
}

.cursor-pointer {
    cursor: pointer;
}
</style>

<?php require_once 'components/user_footer.php'; ?> 