<?php
require_once 'includes/config.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupération des données de l'utilisateur
$userId = $_SESSION['user_id'];

try {
    // Récupération des exercices récents
    $exerciseStmt = $pdo->prepare("
        SELECT e.*, et.name as type_name, et.icon as type_icon
        FROM exercises e
        JOIN exercise_types et ON e.type_id = et.id
        WHERE e.user_id = ?
        ORDER BY e.date DESC
        LIMIT 10
    ");
    $exerciseStmt->execute([$userId]);
    $recentExercises = $exerciseStmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques hebdomadaires
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            SUM(duration) as total_duration,
            SUM(calories_burned) as total_calories,
            COUNT(DISTINCT DATE(date)) as active_days
        FROM exercises
        WHERE user_id = ?
        AND date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $statsStmt->execute([$userId]);
    $weeklyStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Types d'exercices disponibles
    $typesStmt = $pdo->prepare("SELECT * FROM exercise_types ORDER BY name");
    $typesStmt->execute();
    $exerciseTypes = $typesStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Une erreur est survenue'];
}

include 'components/user_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Activités physiques</h1>
        <button class="btn btn-primary" onclick="showAddExerciseModal()">
            <i class="fas fa-plus me-2"></i>Ajouter une activité
        </button>
    </div>

    <!-- Statistiques hebdomadaires -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Sessions cette semaine</div>
                    <div class="stat-value"><?php echo $weeklyStats['total_sessions']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Durée totale</div>
                    <div class="stat-value">
                        <?php echo floor($weeklyStats['total_duration'] / 60); ?> h 
                        <?php echo $weeklyStats['total_duration'] % 60; ?> min
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Calories brûlées</div>
                    <div class="stat-value">
                        <?php echo number_format($weeklyStats['total_calories']); ?> kcal
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Jours actifs</div>
                    <div class="stat-value"><?php echo $weeklyStats['active_days']; ?>/7</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exercices récents -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="m-0 font-weight-bold text-primary">Activités récentes</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recentExercises)): ?>
                <p class="text-muted">Aucune activité enregistrée récemment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Durée</th>
                                <th>Calories</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentExercises as $exercise): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($exercise['date'])); ?></td>
                                    <td>
                                        <i class="<?php echo $exercise['type_icon']; ?> me-2"></i>
                                        <?php echo $exercise['type_name']; ?>
                                    </td>
                                    <td><?php echo floor($exercise['duration'] / 60); ?>h <?php echo $exercise['duration'] % 60; ?>min</td>
                                    <td><?php echo number_format($exercise['calories_burned']); ?> kcal</td>
                                    <td><?php echo htmlspecialchars($exercise['notes'] ?? ''); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="editExercise(<?php echo htmlspecialchars(json_encode($exercise)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="deleteExercise(<?php echo $exercise['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal d'ajout/modification -->
<div class="modal fade" id="exerciseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une activité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="exerciseForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="exerciseId">
                    <div class="mb-3">
                        <label class="form-label">Type d'activité</label>
                        <select class="form-select" name="type_id" id="exerciseType" required>
                            <option value="">Choisir un type</option>
                            <?php foreach ($exerciseTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>">
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date et heure</label>
                        <input type="datetime-local" class="form-control" name="date" id="exerciseDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durée (minutes)</label>
                        <input type="number" class="form-control" name="duration" id="exerciseDuration" 
                               min="1" max="720" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Calories brûlées</label>
                        <input type="number" class="form-control" name="calories_burned" id="exerciseCalories" 
                               min="0" max="5000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="exerciseNotes" rows="3"></textarea>
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
let exerciseModal;

document.addEventListener('DOMContentLoaded', function() {
    exerciseModal = new bootstrap.Modal(document.getElementById('exerciseModal'));
    
    // Gestionnaire de formulaire
    document.getElementById('exerciseForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id = formData.get('id');
        const url = id ? '/api/update_exercise.php' : '/api/add_exercise.php';
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Succès', 'Activité enregistrée avec succès', 'success');
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

function showAddExerciseModal() {
    document.getElementById('exerciseId').value = '';
    document.getElementById('exerciseType').value = '';
    document.getElementById('exerciseDate').value = new Date().toISOString().slice(0, 16);
    document.getElementById('exerciseDuration').value = '';
    document.getElementById('exerciseCalories').value = '';
    document.getElementById('exerciseNotes').value = '';
    document.querySelector('#exerciseModal .modal-title').textContent = 'Ajouter une activité';
    exerciseModal.show();
}

function editExercise(exercise) {
    document.getElementById('exerciseId').value = exercise.id;
    document.getElementById('exerciseType').value = exercise.type_id;
    document.getElementById('exerciseDate').value = exercise.date.slice(0, 16);
    document.getElementById('exerciseDuration').value = exercise.duration;
    document.getElementById('exerciseCalories').value = exercise.calories_burned;
    document.getElementById('exerciseNotes').value = exercise.notes || '';
    document.querySelector('#exerciseModal .modal-title').textContent = 'Modifier l\'activité';
    exerciseModal.show();
}

async function deleteExercise(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette activité ?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/delete_exercise.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Succès', 'Activité supprimée avec succès', 'success');
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