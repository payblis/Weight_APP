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
    // Récupération des badges de l'utilisateur
    $userBadgesStmt = $pdo->prepare("
        SELECT 
            a.*,
            ua.earned_at,
            ua.progress,
            ua.completed
        FROM achievements a
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        ORDER BY ua.completed DESC, a.difficulty ASC, a.name ASC
    ");
    $userBadgesStmt->execute([$userId]);
    $userBadges = $userBadgesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques des badges
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_badges,
            SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_badges
        FROM user_achievements
        WHERE user_id = ?
    ");
    $statsStmt->execute([$userId]);
    $badgeStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Badges récemment débloqués
    $recentBadgesStmt = $pdo->prepare("
        SELECT a.* 
        FROM achievements a
        JOIN user_achievements ua ON a.id = ua.achievement_id
        WHERE ua.user_id = ? AND ua.completed = 1
        ORDER BY ua.earned_at DESC
        LIMIT 5
    ");
    $recentBadgesStmt->execute([$userId]);
    $recentBadges = $recentBadgesStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Une erreur est survenue'];
}

include 'components/user_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Badges et récompenses</h1>
    </div>

    <!-- Statistiques -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Badges débloqués</div>
                    <div class="stat-value">
                        <?php echo $badgeStats['completed_badges']; ?> / <?php echo $badgeStats['total_badges']; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Progression</div>
                    <div class="stat-value">
                        <?php 
                        $percentage = $badgeStats['total_badges'] > 0 
                            ? round(($badgeStats['completed_badges'] / $badgeStats['total_badges']) * 100) 
                            : 0;
                        echo $percentage . '%';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Dernier badge</div>
                    <div class="stat-value">
                        <?php if (!empty($recentBadges)): ?>
                            <i class="<?php echo $recentBadges[0]['icon']; ?> me-2"></i>
                            <?php echo $recentBadges[0]['name']; ?>
                        <?php else: ?>
                            Aucun badge débloqué
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des badges -->
    <div class="row">
        <?php foreach ($userBadges as $badge): ?>
            <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                <div class="card h-100 <?php echo $badge['completed'] ? 'border-success' : ''; ?>">
                    <div class="card-body text-center">
                        <div class="badge-icon mb-3">
                            <?php if ($badge['completed']): ?>
                                <i class="<?php echo $badge['icon']; ?> fa-3x text-success"></i>
                            <?php else: ?>
                                <i class="<?php echo $badge['icon']; ?> fa-3x text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($badge['name']); ?></h5>
                        <p class="card-text small text-muted">
                            <?php echo htmlspecialchars($badge['description']); ?>
                        </p>
                        <?php if ($badge['completed']): ?>
                            <div class="badge bg-success mb-2">Débloqué</div>
                            <small class="text-muted">
                                Le <?php echo date('d/m/Y', strtotime($badge['earned_at'])); ?>
                            </small>
                        <?php else: ?>
                            <div class="progress mb-2" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo $badge['progress']; ?>%"
                                     aria-valuenow="<?php echo $badge['progress']; ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted">
                                Progression : <?php echo $badge['progress']; ?>%
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'components/user_footer.php'; ?> 