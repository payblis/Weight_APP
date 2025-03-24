<?php
require_once 'includes/config.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupération des aliments personnalisés de l'utilisateur
$userId = $_SESSION['user_id'];

try {
    // Récupération des aliments avec pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    // Recherche si un terme est spécifié
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $whereClause = '';
    $params = [$userId];

    if ($search !== '') {
        $whereClause = "AND (name LIKE ? OR brand LIKE ?)";
        array_push($params, "%{$search}%", "%{$search}%");
    }

    // Compte total des aliments
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM foods 
        WHERE user_id = ? {$whereClause}
    ");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Récupération des aliments
    $stmt = $pdo->prepare("
        SELECT * 
        FROM foods 
        WHERE user_id = ? {$whereClause}
        ORDER BY name ASC 
        LIMIT ? OFFSET ?
    ");
    array_push($params, $perPage, $offset);
    $stmt->execute($params);
    $foods = $stmt->fetchAll();

    // Calcul des pages
    $totalPages = ceil($total / $perPage);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des aliments : " . $e->getMessage());
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Erreur lors de la récupération des aliments'
    ];
}

include 'components/user_header.php';
?>

<div class="container-fluid">
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Base de données d'aliments</h1>
        <button class="btn btn-primary" onclick="showAddFoodModal()">
            <i class="fas fa-plus me-2"></i>Ajouter un aliment
        </button>
    </div>

    <!-- Barre de recherche -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="mb-0">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Rechercher un aliment..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des aliments -->
    <div class="card shadow">
        <div class="card-body">
            <?php if (empty($foods)): ?>
                <div class="text-center text-muted py-4">
                    <p>Aucun aliment trouvé</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Marque</th>
                                <th>Calories</th>
                                <th>Protéines</th>
                                <th>Glucides</th>
                                <th>Lipides</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($foods as $food): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($food['name']); ?></td>
                                    <td><?php echo htmlspecialchars($food['brand'] ?? '-'); ?></td>
                                    <td><?php echo number_format($food['calories']); ?> kcal</td>
                                    <td><?php echo number_format($food['proteins'], 1); ?> g</td>
                                    <td><?php echo number_format($food['carbs'], 1); ?> g</td>
                                    <td><?php echo number_format($food['fats'], 1); ?> g</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editFood(<?php echo $food['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteFood(<?php echo $food['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                    Précédent
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                                    Suivant
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal d'ajout/modification d'aliment -->
<div class="modal fade" id="foodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un aliment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="foodForm">
                <input type="hidden" id="foodId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" id="foodName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Marque</label>
                        <input type="text" class="form-control" id="foodBrand">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Calories (pour 100g/100ml)</label>
                        <input type="number" class="form-control" id="foodCalories" min="0" step="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Protéines (g pour 100g/100ml)</label>
                        <input type="number" class="form-control" id="foodProteins" min="0" step="0.1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Glucides (g pour 100g/100ml)</label>
                        <input type="number" class="form-control" id="foodCarbs" min="0" step="0.1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lipides (g pour 100g/100ml)</label>
                        <input type="number" class="form-control" id="foodFats" min="0" step="0.1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Affichage du modal d'ajout
function showAddFoodModal() {
    document.getElementById('foodId').value = '';
    document.getElementById('foodName').value = '';
    document.getElementById('foodBrand').value = '';
    document.getElementById('foodCalories').value = '';
    document.getElementById('foodProteins').value = '';
    document.getElementById('foodCarbs').value = '';
    document.getElementById('foodFats').value = '';
    
    document.querySelector('#foodModal .modal-title').textContent = 'Ajouter un aliment';
    const modal = new bootstrap.Modal(document.getElementById('foodModal'));
    modal.show();
}

// Modification d'un aliment
async function editFood(foodId) {
    try {
        const response = await fetch(`/api/get_food.php?id=${foodId}`);
        const data = await response.json();
        
        if (data.success) {
            const food = data.data;
            document.getElementById('foodId').value = food.id;
            document.getElementById('foodName').value = food.name;
            document.getElementById('foodBrand').value = food.brand || '';
            document.getElementById('foodCalories').value = food.calories;
            document.getElementById('foodProteins').value = food.proteins;
            document.getElementById('foodCarbs').value = food.carbs;
            document.getElementById('foodFats').value = food.fats;
            
            document.querySelector('#foodModal .modal-title').textContent = 'Modifier un aliment';
            const modal = new bootstrap.Modal(document.getElementById('foodModal'));
            modal.show();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    }
}

// Suppression d'un aliment
function deleteFood(foodId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet aliment ?')) {
        fetch('/api/delete_food.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                food_id: foodId
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

// Gestion du formulaire
document.getElementById('foodForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const foodId = document.getElementById('foodId').value;
    const foodData = {
        name: document.getElementById('foodName').value,
        brand: document.getElementById('foodBrand').value,
        calories: parseFloat(document.getElementById('foodCalories').value),
        proteins: parseFloat(document.getElementById('foodProteins').value),
        carbs: parseFloat(document.getElementById('foodCarbs').value),
        fats: parseFloat(document.getElementById('foodFats').value)
    };
    
    try {
        const response = await fetch('/api/save_food.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: foodId || null,
                ...foodData
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Succès', foodId ? 'Aliment modifié' : 'Aliment ajouté', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    } finally {
        bootstrap.Modal.getInstance(document.getElementById('foodModal')).hide();
    }
});
</script>

<?php include 'components/user_footer.php'; ?> 