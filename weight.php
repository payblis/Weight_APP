<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

// Récupération de l'ID de l'utilisateur
$user_id = $_SESSION['user_id'];

try {
    // Récupération des statistiques de poids
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_entries,
            MIN(weight) as min_weight,
            MAX(weight) as max_weight,
            AVG(weight) as avg_weight
        FROM daily_logs
        WHERE user_id = ?
    ");
    $statsStmt->execute([$user_id]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Récupération du poids actuel et précédent
    $weightStmt = $pdo->prepare("
        SELECT weight, date
        FROM daily_logs
        WHERE user_id = ?
        ORDER BY date DESC
        LIMIT 2
    ");
    $weightStmt->execute([$user_id]);
    $weights = $weightStmt->fetchAll(PDO::FETCH_ASSOC);

    $current_weight = $weights[0]['weight'] ?? 0;
    $previous_weight = $weights[1]['weight'] ?? $current_weight;
    $weight_change = $current_weight - $previous_weight;
    $weight_change_percentage = $previous_weight > 0 ? ($weight_change / $previous_weight) * 100 : 0;

    // Récupération des 10 derniers poids pour le graphique
    $historyStmt = $pdo->prepare("
        SELECT weight, date
        FROM daily_logs
        WHERE user_id = ?
        ORDER BY date DESC
        LIMIT 10
    ");
    $historyStmt->execute([$user_id]);
    $weight_history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Une erreur est survenue'];
}

// En-tête
require_once 'components/user_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Suivi du poids</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWeightModal">
            <i class="fas fa-plus me-2"></i>Ajouter un poids
        </button>
    </div>

    <!-- Statistiques -->
    <div class="row">
        <!-- Poids actuel -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Poids actuel
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($current_weight, 1); ?> kg
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-weight fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Variation de poids -->
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
                                $sign = $weight_change >= 0 ? '+' : '';
                                echo $sign . number_format($weight_change, 1) . ' kg';
                                echo ' (' . $sign . number_format($weight_change_percentage, 1) . '%)';
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                // Récupération de la taille de l'utilisateur
                                $heightStmt = $pdo->prepare("SELECT height FROM users WHERE id = ?");
                                $heightStmt->execute([$user_id]);
                                $height = $heightStmt->fetchColumn();
                                
                                if ($height && $current_weight) {
                                    $height_m = $height / 100;
                                    $bmi = $current_weight / ($height_m * $height_m);
                                    echo number_format($bmi, 1);
                                } else {
                                    echo "N/A";
                                }
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

        <!-- Nombre total d'entrées -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total des entrées
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_entries']; ?>
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

    <!-- Graphique d'évolution -->
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Évolution du poids</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="weightChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques détaillées -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistiques détaillées</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-gray-500">Poids minimum</div>
                        <div class="font-weight-bold"><?php echo number_format($stats['min_weight'], 1); ?> kg</div>
                    </div>
                    <div class="mb-3">
                        <div class="small text-gray-500">Poids maximum</div>
                        <div class="font-weight-bold"><?php echo number_format($stats['max_weight'], 1); ?> kg</div>
                    </div>
                    <div class="mb-3">
                        <div class="small text-gray-500">Poids moyen</div>
                        <div class="font-weight-bold"><?php echo number_format($stats['avg_weight'], 1); ?> kg</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajout/Modification de poids -->
<div class="modal fade" id="addWeightModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un poids</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="weightForm">
                    <div class="mb-3">
                        <label for="weight" class="form-label">Poids (kg)</label>
                        <input type="number" class="form-control" id="weight" name="weight" step="0.1" required>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveWeight()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Données pour le graphique
const weightData = <?php echo json_encode(array_reverse($weight_history)); ?>;

// Initialisation du graphique
const ctx = document.getElementById('weightChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: weightData.map(entry => {
            const date = new Date(entry.date);
            return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
        }),
        datasets: [{
            label: 'Poids (kg)',
            data: weightData.map(entry => entry.weight),
            borderColor: 'rgb(78, 115, 223)',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});

// Fonction pour sauvegarder un nouveau poids
function saveWeight() {
    const formData = new FormData(document.getElementById('weightForm'));
    
    fetch('api/add_weight.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Succès', 'Le poids a été enregistré', 'success');
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
    document.getElementById('addWeightModal').addEventListener('show.bs.modal', function() {
        document.getElementById('weightForm').reset();
        document.getElementById('date').value = new Date().toISOString().split('T')[0];
    });
});
</script>

<?php require_once 'components/user_footer.php'; ?> 