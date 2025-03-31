<?php
session_start();
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin($_SESSION['user_id'])) {
    redirect('../login.php');
}

// Récupérer les repas prédéfinis
$sql = "SELECT pm.*, u.username as created_by_name 
        FROM predefined_meals pm 
        JOIN users u ON pm.created_by = u.id 
        ORDER BY pm.created_at DESC";
$predefined_meals = fetchAll($sql);

// Récupérer les catégories d'aliments
$sql = "SELECT * FROM food_categories ORDER BY name";
$categories = fetchAll($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Repas Prédéfinis - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'admin_navigation.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des Repas Prédéfinis</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMealModal">
                <i class="fas fa-plus"></i> Nouveau Repas
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Calories</th>
                                <th>Protéines</th>
                                <th>Glucides</th>
                                <th>Lipides</th>
                                <th>Créé par</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($predefined_meals as $meal): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($meal['name']); ?></td>
                                <td><?php echo htmlspecialchars($meal['meal_type']); ?></td>
                                <td><?php echo $meal['total_calories']; ?></td>
                                <td><?php echo $meal['total_protein']; ?>g</td>
                                <td><?php echo $meal['total_carbs']; ?>g</td>
                                <td><?php echo $meal['total_fat']; ?>g</td>
                                <td><?php echo htmlspecialchars($meal['created_by_name']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="viewMealDetails(<?php echo $meal['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick="editMeal(<?php echo $meal['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deleteMeal(<?php echo $meal['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de création de repas -->
    <div class="modal fade" id="createMealModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Créer un Nouveau Repas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createMealForm">
                        <div class="mb-3">
                            <label class="form-label">Nom du repas</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type de repas</label>
                            <select class="form-select" name="meal_type" required>
                                <option value="petit_dejeuner">Petit-déjeuner</option>
                                <option value="dejeuner">Déjeuner</option>
                                <option value="diner">Dîner</option>
                                <option value="collation">Collation</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Aliments</label>
                            <div id="foodsList" class="list-group mb-3"></div>
                            <button type="button" class="btn btn-outline-primary" onclick="addFoodItem()">
                                <i class="fas fa-plus"></i> Ajouter un aliment
                            </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addFoodItem() {
            const foodsList = document.getElementById('foodsList');
            const foodItem = document.createElement('div');
            foodItem.className = 'list-group-item';
            foodItem.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-5">
                        <select class="form-select food-select" required>
                            <option value="">Sélectionner un aliment</option>
                            <?php foreach ($categories as $category): ?>
                                <optgroup label="<?php echo htmlspecialchars($category['name']); ?>">
                                    <?php
                                    $sql = "SELECT id, name FROM foods WHERE category_id = ? ORDER BY name";
                                    $foods = fetchAll($sql, [$category['id']]);
                                    foreach ($foods as $food):
                                    ?>
                                        <option value="<?php echo $food['id']; ?>">
                                            <?php echo htmlspecialchars($food['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="number" class="form-control quantity-input" placeholder="Quantité (g)" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.parentElement.remove()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            foodsList.appendChild(foodItem);
        }

        function saveMeal() {
            const form = document.getElementById('createMealForm');
            const foods = [];
            
            document.querySelectorAll('#foodsList .list-group-item').forEach(item => {
                const foodId = item.querySelector('.food-select').value;
                const quantity = item.querySelector('.quantity-input').value;
                if (foodId && quantity) {
                    foods.push({
                        food_id: foodId,
                        quantity: quantity
                    });
                }
            });

            const data = {
                name: form.querySelector('[name="name"]').value,
                description: form.querySelector('[name="description"]').value,
                meal_type: form.querySelector('[name="meal_type"]').value,
                foods: foods
            };

            fetch('create-predefined-meal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la création du repas');
                }
            })
            .catch(error => {
                alert('Erreur: ' + error.message);
            });
        }

        function viewMealDetails(mealId) {
            // Implémenter la visualisation des détails
        }

        function editMeal(mealId) {
            // Implémenter la modification
        }

        function deleteMeal(mealId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce repas ?')) {
                fetch('delete-predefined-meal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: mealId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    alert('Erreur: ' + error.message);
                });
            }
        }
    </script>
</body>
</html> 