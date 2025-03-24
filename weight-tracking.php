<?php
require_once 'includes/config.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupération des données de l'utilisateur
$userId = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    // Compte total des entrées
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM daily_logs WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $totalEntries = $countStmt->fetchColumn();
    $totalPages = ceil($totalEntries / $perPage);

    // Récupération des entrées pour la page courante
    $logsStmt = $pdo->prepare("
        SELECT dl.*, 
               COALESCE(LAG(weight) OVER (ORDER BY date), weight) as previous_weight
        FROM daily_logs dl
        WHERE user_id = ?
        ORDER BY date DESC
        LIMIT ? OFFSET ?
    ");
    $logsStmt->execute([$userId, $perPage, $offset]);
    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques
    $statsStmt = $pdo->prepare("
        SELECT 
            MIN(weight) as min_weight,
            MAX(weight) as max_weight,
            AVG(weight) as avg_weight,
            MIN(date) as start_date,
            MAX(date) as end_date
        FROM daily_logs
        WHERE user_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Une erreur est survenue'];
}

include 'components/user_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Suivi du poids</h1>
        <button class="btn btn-primary" onclick="showAddWeightModal()">
            <i class="fas fa-plus me-2"></i>Ajouter une entrée
        </button>
    </div>

    <!-- Cartes statistiques -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Poids minimum</div>
                    <div class="stat-value"><?php echo number_format($stats['min_weight'], 1); ?> kg</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Poids maximum</div>
                    <div class="stat-value"><?php echo number_format($stats['max_weight'], 1); ?> kg</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Poids moyen</div>
                    <div class="stat-value"><?php echo number_format($stats['avg_weight'], 1); ?> kg</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Période</div>
                    <div class="stat-value small">
                        <?php 
                        echo date('d/m/Y', strtotime($stats['start_date']));
                        echo ' - ';
                        echo date('d/m/Y', strtotime($stats['end_date']));
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des entrées -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Poids</th>
                            <th>Variation</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($log['date'])); ?></td>
                                <td><?php echo number_format($log['weight'], 1); ?> kg</td>
                                <td>
                                    <?php
                                    $diff = $log['weight'] - $log['previous_weight'];
                                    $class = $diff > 0 ? 'text-danger' : ($diff < 0 ? 'text-success' : 'text-muted');
                                    $icon = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'right');
                                    if ($diff != 0) {
                                        echo "<span class='$class'>";
                                        echo "<i class='fas fa-arrow-$icon me-1'></i>";
                                        echo number_format(abs($diff), 1);
                                        echo " kg</span>";
                                    } else {
                                        echo "<span class='text-muted'>-</span>";
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['notes'] ?? ''); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-2" 
                                            onclick="editWeight(<?php echo htmlspecialchars(json_encode($log)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="deleteWeight(<?php echo $log['id']; ?>)">
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
                <nav>
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Précédent</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal d'ajout/modification -->
<div class="modal fade" id="weightModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une entrée</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="weightForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="weightId">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" id="weightDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poids (kg)</label>
                        <input type="number" class="form-control" name="weight" id="weightValue" 
                               step="0.1" min="30" max="300" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="weightNotes" rows="3"></textarea>
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
let weightModal;

document.addEventListener('DOMContentLoaded', function() {
    weightModal = new bootstrap.Modal(document.getElementById('weightModal'));
    
    // Gestionnaire de formulaire
    document.getElementById('weightForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id = formData.get('id');
        const url = id ? '/api/update_weight.php' : '/api/add_weight.php';
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Succès', 'Données enregistrées avec succès', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showToast('Erreur', 'Une erreur est survenue', 'error');
        }
    });
});

function showAddWeightModal() {
    document.getElementById('weightId').value = '';
    document.getElementById('weightDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('weightValue').value = '';
    document.getElementById('weightNotes').value = '';
    document.querySelector('#weightModal .modal-title').textContent = 'Ajouter une entrée';
    weightModal.show();
}

function editWeight(log) {
    document.getElementById('weightId').value = log.id;
    document.getElementById('weightDate').value = log.date;
    document.getElementById('weightValue').value = log.weight;
    document.getElementById('weightNotes').value = log.notes || '';
    document.querySelector('#weightModal .modal-title').textContent = 'Modifier l\'entrée';
    weightModal.show();
}

async function deleteWeight(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette entrée ?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/delete_weight.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Succès', 'Entrée supprimée avec succès', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    }
}
</script>

<?php include 'components/user_footer.php'; ?> 