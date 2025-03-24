<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

// Récupération des informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Récupération des statistiques
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_logs,
        AVG(weight) as avg_weight,
        MIN(weight) as min_weight,
        MAX(weight) as max_weight
    FROM daily_logs 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Récupération des objectifs en cours
$stmt = $pdo->prepare("
    SELECT * FROM goals 
    WHERE user_id = ? 
    AND status = 'en cours' 
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->execute([$user_id]);
$active_goals = $stmt->fetchAll();

// Récupération des badges
$stmt = $pdo->prepare("
    SELECT * FROM achievements 
    WHERE user_id = ? 
    ORDER BY earned_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_achievements = $stmt->fetchAll();

// En-tête
require_once 'components/user_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Mon Profil</h1>

    <div class="row">
        <!-- Informations personnelles -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informations Personnelles</h6>
                </div>
                <div class="card-body">
                    <form id="profile-form" method="POST" action="api/update_profile.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="height" class="form-label">Taille (cm)</label>
                            <input type="number" class="form-control" id="height" name="height" value="<?php echo htmlspecialchars($user['height']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="birthdate" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($user['birthdate']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="gender" class="form-label">Genre</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="M" <?php echo $user['gender'] == 'M' ? 'selected' : ''; ?>>Homme</option>
                                <option value="F" <?php echo $user['gender'] == 'F' ? 'selected' : ''; ?>>Femme</option>
                                <option value="O" <?php echo $user['gender'] == 'O' ? 'selected' : ''; ?>>Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="activity_level" class="form-label">Niveau d'activité</label>
                            <select class="form-control" id="activity_level" name="activity_level">
                                <?php foreach (ACTIVITY_LEVELS as $key => $level): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $user['activity_level'] == $key ? 'selected' : ''; ?>>
                                        <?php echo $level; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Statistiques et Progrès -->
        <div class="col-xl-8 col-lg-7">
            <div class="row">
                <!-- Statistiques -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Statistiques</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="small text-gray-500">Nombre total d'entrées</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['total_logs']); ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="small text-gray-500">Poids moyen</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['avg_weight'], 1); ?> kg</div>
                            </div>
                            <div class="mb-3">
                                <div class="small text-gray-500">Poids minimum</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['min_weight'], 1); ?> kg</div>
                            </div>
                            <div class="mb-3">
                                <div class="small text-gray-500">Poids maximum</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['max_weight'], 1); ?> kg</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Objectifs en cours -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Objectifs en cours</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($active_goals)): ?>
                                <p class="text-center text-gray-500">Aucun objectif en cours</p>
                            <?php else: ?>
                                <?php foreach ($active_goals as $goal): ?>
                                    <div class="mb-3">
                                        <h6 class="font-weight-bold"><?php echo htmlspecialchars($goal['title']); ?></h6>
                                        <div class="progress mb-2">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $goal['progress']; ?>%"
                                                 aria-valuenow="<?php echo $goal['progress']; ?>" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-gray-500"><?php echo $goal['progress']; ?>% complété</small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Badges récents -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Badges récents</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (empty($recent_achievements)): ?>
                            <div class="col-12">
                                <p class="text-center text-gray-500">Aucun badge obtenu pour le moment</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_achievements as $achievement): ?>
                                <div class="col-md-4 col-sm-6 mb-4">
                                    <div class="text-center">
                                        <div class="badge-icon mb-2">
                                            <i class="fas <?php echo htmlspecialchars($achievement['icon']); ?> fa-2x text-primary"></i>
                                        </div>
                                        <h6 class="font-weight-bold"><?php echo htmlspecialchars($achievement['title']); ?></h6>
                                        <small class="text-gray-500">Obtenu le <?php echo date('d/m/Y', strtotime($achievement['earned_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('profile-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Succès', 'Votre profil a été mis à jour avec succès', 'success');
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    })
    .catch(error => {
        showToast('Erreur', 'Une erreur est survenue lors de la mise à jour', 'error');
        console.error('Error:', error);
    });
});
</script>

<?php require_once 'components/user_footer.php'; ?> 