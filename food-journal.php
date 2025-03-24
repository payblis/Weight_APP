<?php
require_once 'includes/config.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupération de la date (aujourd'hui par défaut ou date sélectionnée)
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$userId = $_SESSION['user_id'];

try {
    // Récupération des objectifs caloriques de l'utilisateur
    $goalStmt = $pdo->prepare("
        SELECT daily_calories 
        FROM user_preferences 
        WHERE user_id = ?
    ");
    $goalStmt->execute([$userId]);
    $calorieGoal = $goalStmt->fetchColumn() ?: 2000; // Valeur par défaut si non définie

    // Récupération des repas de la journée
    $mealsStmt = $pdo->prepare("
        SELECT 
            m.id,
            m.meal_type,
            m.time,
            SUM(f.calories * mf.quantity) as total_calories,
            SUM(f.proteins * mf.quantity) as total_proteins,
            SUM(f.carbs * mf.quantity) as total_carbs,
            SUM(f.fats * mf.quantity) as total_fats
        FROM meals m
        LEFT JOIN meal_foods mf ON m.id = mf.meal_id
        LEFT JOIN foods f ON mf.food_id = f.id
        WHERE m.user_id = ? AND DATE(m.time) = ?
        GROUP BY m.id, m.meal_type, m.time
        ORDER BY m.time ASC
    ");
    $mealsStmt->execute([$userId, $date]);
    $meals = $mealsStmt->fetchAll();

    // Calcul des totaux journaliers
    $dailyTotals = [
        'calories' => 0,
        'proteins' => 0,
        'carbs' => 0,
        'fats' => 0
    ];

    foreach ($meals as $meal) {
        $dailyTotals['calories'] += $meal['total_calories'];
        $dailyTotals['proteins'] += $meal['total_proteins'];
        $dailyTotals['carbs'] += $meal['total_carbs'];
        $dailyTotals['fats'] += $meal['total_fats'];
    }

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Erreur lors de la récupération des données'
    ];
}

include 'components/user_header.php';
?>

<div class="container-fluid">
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Journal alimentaire</h1>
        <div class="d-flex gap-2">
            <input type="date" class="form-control" id="dateSelector" value="<?php echo $date; ?>" onchange="changeDate(this.value)">
            <button class="btn btn-primary" onclick="addMeal()">
                <i class="fas fa-plus me-2"></i>Ajouter un repas
            </button>
        </div>
    </div>

    <!-- Résumé journalier -->
    <div class="row mb-4">
        <!-- Calories -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-label">Calories</div>
                            <div class="stat-value">
                                <?php echo number_format($dailyTotals['calories']); ?> / <?php echo number_format($calorieGoal); ?>
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo min(100, ($dailyTotals['calories'] / $calorieGoal) * 100); ?>%"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-fire stat-icon text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Protéines -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-label">Protéines</div>
                            <div class="stat-value"><?php echo number_format($dailyTotals['proteins']); ?> g</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-drumstick-bite stat-icon text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Glucides -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-label">Glucides</div>
                            <div class="stat-value"><?php echo number_format($dailyTotals['carbs']); ?> g</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bread-slice stat-icon text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lipides -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-label">Lipides</div>
                            <div class="stat-value"><?php echo number_format($dailyTotals['fats']); ?> g</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cheese stat-icon text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des repas -->
    <div class="row">
        <div class="col-12">
            <?php foreach (MEAL_TYPES as $type => $label): ?>
                <?php
                $mealOfType = array_filter($meals, function($meal) use ($type) {
                    return $meal['meal_type'] === $type;
                });
                $meal = !empty($mealOfType) ? reset($mealOfType) : null;
                ?>
                <div class="meal-card mb-4">
                    <div class="meal-header d-flex justify-content-between align-items-center">
                        <h5 class="meal-title">
                            <i class="fas fa-utensils me-2"></i><?php echo $label; ?>
                        </h5>
                        <?php if ($meal): ?>
                            <span class="text-muted"><?php echo number_format($meal['total_calories']); ?> kcal</span>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-primary" onclick="addFoodToMeal('<?php echo $type; ?>')">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <?php if ($meal): ?>
                        <div class="meal-body">
                            <?php
                            // Récupération des aliments du repas
                            $foodsStmt = $pdo->prepare("
                                SELECT 
                                    f.name,
                                    f.brand,
                                    f.calories,
                                    f.proteins,
                                    f.carbs,
                                    f.fats,
                                    mf.quantity,
                                    mf.id as meal_food_id
                                FROM meal_foods mf
                                JOIN foods f ON mf.food_id = f.id
                                WHERE mf.meal_id = ?
                                ORDER BY mf.created_at ASC
                            ");
                            $foodsStmt->execute([$meal['id']]);
                            $foods = $foodsStmt->fetchAll();
                            ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Aliment</th>
                                            <th>Quantité</th>
                                            <th>Calories</th>
                                            <th>P/G/L</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($foods as $food): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($food['name']); ?>
                                                    <?php if ($food['brand']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($food['brand']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $food['quantity']; ?></td>
                                                <td><?php echo number_format($food['calories'] * $food['quantity']); ?> kcal</td>
                                                <td>
                                                    <?php echo number_format($food['proteins'] * $food['quantity']); ?>g /
                                                    <?php echo number_format($food['carbs'] * $food['quantity']); ?>g /
                                                    <?php echo number_format($food['fats'] * $food['quantity']); ?>g
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editFoodQuantity(<?php echo $food['meal_food_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="removeFood(<?php echo $food['meal_food_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="meal-body text-center text-muted py-4">
                            <p>Aucun aliment enregistré pour ce repas</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal d'ajout d'aliment -->
<div class="modal fade" id="addFoodModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un aliment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="foodSearch" placeholder="Rechercher un aliment...">
                </div>
                <div id="searchResults" class="list-group"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de quantité -->
<div class="modal fade" id="quantityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quantité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quantityForm">
                <input type="hidden" id="foodId">
                <input type="hidden" id="mealType">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Quantité</label>
                        <input type="number" class="form-control" id="quantity" min="0.1" step="0.1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentMealType = null;

// Changement de date
function changeDate(date) {
    window.location.href = `?date=${date}`;
}

// Recherche d'aliments
const foodSearch = document.getElementById('foodSearch');
if (foodSearch) {
    foodSearch.addEventListener('input', debounce(async (e) => {
        const query = e.target.value;
        if (query.length >= 2) {
            try {
                const response = await fetch(`/api/search_food.php?q=${encodeURIComponent(query)}`);
                const foods = await response.json();
                
                const results = document.getElementById('searchResults');
                results.innerHTML = '';
                
                foods.forEach(food => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${food.name}</strong>
                                ${food.brand ? `<br><small class="text-muted">${food.brand}</small>` : ''}
                            </div>
                            <div class="text-end">
                                <div>${food.calories} kcal</div>
                                <small class="text-muted">
                                    P: ${food.proteins}g / G: ${food.carbs}g / L: ${food.fats}g
                                </small>
                            </div>
                        </div>
                    `;
                    item.onclick = () => selectFood(food.id);
                    results.appendChild(item);
                });
            } catch (error) {
                console.error('Erreur:', error);
                showToast('Erreur', 'Erreur lors de la recherche', 'error');
            }
        }
    }, 300));
}

// Sélection d'un aliment
function selectFood(foodId) {
    document.getElementById('foodId').value = foodId;
    document.getElementById('mealType').value = currentMealType;
    bootstrap.Modal.getInstance(document.getElementById('addFoodModal')).hide();
    const quantityModal = new bootstrap.Modal(document.getElementById('quantityModal'));
    quantityModal.show();
}

// Ajout d'un aliment à un repas
function addFoodToMeal(mealType) {
    currentMealType = mealType;
    document.getElementById('foodSearch').value = '';
    document.getElementById('searchResults').innerHTML = '';
    const modal = new bootstrap.Modal(document.getElementById('addFoodModal'));
    modal.show();
}

// Gestion du formulaire de quantité
document.getElementById('quantityForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const foodId = document.getElementById('foodId').value;
    const mealType = document.getElementById('mealType').value;
    const quantity = document.getElementById('quantity').value;
    
    try {
        const response = await fetch('/api/add_food_to_meal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                food_id: foodId,
                meal_type: mealType,
                quantity: quantity,
                date: '<?php echo $date; ?>'
            })
        });
        
        if (!response.ok) {
            throw new Error('Erreur réseau');
        }
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Succès', 'Aliment ajouté avec succès', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    } finally {
        bootstrap.Modal.getInstance(document.getElementById('quantityModal')).hide();
    }
});

// Modification de la quantité d'un aliment
async function editFoodQuantity(mealFoodId) {
    try {
        const response = await fetch(`/api/get_meal_food.php?id=${mealFoodId}`);
        const data = await response.json();
        
        if (data.success) {
            const quantity = prompt('Nouvelle quantité:', data.quantity);
            if (quantity !== null) {
                await updateFoodQuantity(mealFoodId, quantity);
            }
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    }
}

// Mise à jour de la quantité
async function updateFoodQuantity(mealFoodId, quantity) {
    try {
        const response = await fetch('/api/update_food_quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                meal_food_id: mealFoodId,
                quantity: quantity
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Succès', 'Quantité mise à jour', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    }
}

// Suppression d'un aliment
function removeFood(mealFoodId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet aliment ?')) {
        fetch('/api/remove_food_from_meal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                meal_food_id: mealFoodId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Succès', 'Aliment supprimé', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur', 'Une erreur est survenue', 'error');
        });
    }
}
</script>

<?php include 'components/user_footer.php'; ?> 