<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

// Récupération des entrées de poids
$user_id = $_SESSION['user_id'];

// Récupération des statistiques
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_entries,
        MIN(weight) as min_weight,
        MAX(weight) as max_weight,
        AVG(weight) as avg_weight,
        (SELECT weight FROM daily_logs WHERE user_id = ? ORDER BY date DESC LIMIT 1) as current_weight,
        (SELECT weight FROM daily_logs WHERE user_id = ? ORDER BY date DESC LIMIT 1,1) as previous_weight
    FROM daily_logs 
    WHERE user_id = ?
");
$stmt->execute([$user_id, $user_id, $user_id]);
$stats = $stmt->fetch();

// Calcul de la variation de poids
$weight_change = 0;
$weight_change_percentage = 0;
if ($stats['current_weight'] && $stats['previous_weight']) {
    $weight_change = $stats['current_weight'] - $stats['previous_weight'];
    $weight_change_percentage = ($weight_change / $stats['previous_weight']) * 100;
}

// Récupération des dernières entrées
$stmt = $pdo->prepare("
    SELECT dl.*, 
           COALESCE(LAG(weight) OVER (ORDER BY date), weight) as prev_weight
    FROM daily_logs dl
    WHERE user_id = ?
    ORDER BY date DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_logs = $stmt->fetchAll();

// Récupération des données pour le graphique
$stmt = $pdo->prepare("
    SELECT date, weight, notes
    FROM daily_logs
    WHERE user_id = ?
    ORDER BY date ASC
");
$stmt->execute([$user_id]);
$chart_data = $stmt->fetchAll();

// En-tête
require_once 'components/user_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Suivi du poids</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWeightModal">
            <i class="fas fa-plus"></i> Ajouter une entrée
        </button>
    </div>

    <!-- Cartes de statistiques -->
    <div class="row">
        <!-- Poids actuel -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Poids actuel</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['current_weight'], 1); ?> kg
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-weight fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Variation -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-<?php echo $weight_change <= 0 ? 'success' : 'danger'; ?> shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-<?php echo $weight_change <= 0 ? 'success' : 'danger'; ?> text-uppercase mb-1">
                                Variation
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                echo $weight_change > 0 ? '+' : '';
                                echo number_format($weight_change, 1) . ' kg';
                                echo ' (' . number_format($weight_change_percentage, 1) . '%)';
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas <?php echo $weight_change <= 0 ? 'fa-arrow-down' : 'fa-arrow-up'; ?> fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- IMC -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">IMC</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $height_m = $user['height'] / 100;
                                $bmi = $stats['current_weight'] / ($height_m * $height_m);
                                echo number_format($bmi, 1);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calculator fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nombre d'entrées -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Entrées totales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_entries']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Graphique d'évolution -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Évolution du poids</h6>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateChartPeriod('week')">7 jours</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateChartPeriod('month')">30 jours</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateChartPeriod('year')">1 an</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateChartPeriod('all')">Tout</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="weightChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dernières entrées -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Dernières entrées</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Poids</th>
                                    <th>Variation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($log['date'])); ?></td>
                                        <td><?php echo number_format($log['weight'], 1); ?> kg</td>
                                        <td>
                                            <?php
                                            $daily_change = $log['weight'] - $log['prev_weight'];
                                            $change_class = $daily_change <= 0 ? 'text-success' : 'text-danger';
                                            echo '<span class="' . $change_class . '">';
                                            echo $daily_change > 0 ? '+' : '';
                                            echo number_format($daily_change, 1);
                                            echo '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editLog(<?php echo htmlspecialchars(json_encode($log)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteLog(<?php echo $log['id']; ?>)">
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
    </div>
</div>

<!-- Modal Ajout/Modification -->
<div class="modal fade" id="addWeightModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Ajouter une entrée</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="weightForm">
                    <input type="hidden" id="logId" name="id">
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="weight" class="form-label">Poids (kg)</label>
                        <input type="number" class="form-control" id="weight" name="weight" step="0.1" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveLog()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Données pour le graphique
const chartData = <?php echo json_encode($chart_data); ?>;
let weightChart;

// Initialisation du graphique
function initChart(data) {
    const ctx = document.getElementById('weightChart').getContext('2d');
    weightChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(entry => entry.date),
            datasets: [{
                label: 'Poids (kg)',
                data: data.map(entry => entry.weight),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const notes = data[context.dataIndex].notes;
                            return notes ? `Notes: ${notes}` : '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
}

// Mise à jour de la période du graphique
function updateChartPeriod(period) {
    const now = new Date();
    let filteredData = [...chartData];
    
    switch(period) {
        case 'week':
            const weekAgo = new Date(now.setDate(now.getDate() - 7));
            filteredData = chartData.filter(entry => new Date(entry.date) >= weekAgo);
            break;
        case 'month':
            const monthAgo = new Date(now.setMonth(now.getMonth() - 1));
            filteredData = chartData.filter(entry => new Date(entry.date) >= monthAgo);
            break;
        case 'year':
            const yearAgo = new Date(now.setFullYear(now.getFullYear() - 1));
            filteredData = chartData.filter(entry => new Date(entry.date) >= yearAgo);
            break;
    }
    
    weightChart.data.labels = filteredData.map(entry => entry.date);
    weightChart.data.datasets[0].data = filteredData.map(entry => entry.weight);
    weightChart.update();
}

// Édition d'une entrée
function editLog(log) {
    document.getElementById('modalTitle').textContent = 'Modifier l'entrée';
    document.getElementById('logId').value = log.id;
    document.getElementById('date').value = log.date;
    document.getElementById('weight').value = log.weight;
    document.getElementById('notes').value = log.notes;
    
    const modal = new bootstrap.Modal(document.getElementById('addWeightModal'));
    modal.show();
}

// Suppression d'une entrée
function deleteLog(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette entrée ?')) {
        fetch('api/delete_daily_log.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Succès', 'L\'entrée a été supprimée', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
            }
        })
        .catch(error => {
            showToast('Erreur', 'Une erreur est survenue', 'error');
            console.error('Error:', error);
        });
    }
}

// Enregistrement d'une entrée
function saveLog() {
    const formData = new FormData(document.getElementById('weightForm'));
    const logId = formData.get('id');
    const endpoint = logId ? 'api/update_daily_log.php' : 'api/add_daily_log.php';
    
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Succès', 'L\'entrée a été enregistrée', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    })
    .catch(error => {
        showToast('Erreur', 'Une erreur est survenue', 'error');
        console.error('Error:', error);
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation du graphique
    initChart(chartData);
    
    // Réinitialisation du formulaire à l'ouverture du modal
    document.getElementById('addWeightModal').addEventListener('show.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Ajouter une entrée';
        document.getElementById('weightForm').reset();
        document.getElementById('logId').value = '';
        document.getElementById('date').value = new Date().toISOString().split('T')[0];
    });
});
</script>

<?php require_once 'components/user_footer.php'; ?> 