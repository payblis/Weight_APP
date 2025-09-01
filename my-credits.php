<?php
session_start();
require_once 'includes/translation.php';
require_once 'includes/credit_functions.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$userCredits = CreditManager::getUserCredits($userId);
$creditStats = CreditManager::getCreditStats($userId);
$purchaseHistory = CreditManager::getPurchaseHistory($userId, 10);
$usageHistory = CreditManager::getUsageHistory($userId, 10);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Crédits IA - MyFity</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold mb-2">
                        <i class="fas fa-coins text-warning"></i> Mes Crédits IA
                    </h1>
                    <p class="lead text-muted">
                        Gérez vos crédits et consultez votre historique d'utilisation
                    </p>
                </div>

                <!-- Statut des crédits -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="fw-bold mb-2">
                                    <i class="fas fa-robot text-primary me-2"></i>
                                    Solde actuel
                                </h4>
                                <p class="text-muted mb-0">
                                    <span class="fw-bold text-primary fs-1"><?php echo $userCredits['credits_balance']; ?></span> crédits disponibles
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="buy-credits.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus me-2"></i>Acheter des crédits
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body p-4">
                                <i class="fas fa-shopping-cart text-primary mb-3" style="font-size: 2rem;"></i>
                                <h5 class="fw-bold"><?php echo $creditStats['total_purchased']; ?></h5>
                                <p class="text-muted mb-0">Crédits achetés</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body p-4">
                                <i class="fas fa-chart-line text-success mb-3" style="font-size: 2rem;"></i>
                                <h5 class="fw-bold"><?php echo $creditStats['total_used']; ?></h5>
                                <p class="text-muted mb-0">Crédits utilisés</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body p-4">
                                <i class="fas fa-euro-sign text-warning mb-3" style="font-size: 2rem;"></i>
                                <h5 class="fw-bold"><?php echo number_format($creditStats['total_spent'], 2); ?>€</h5>
                                <p class="text-muted mb-0">Total dépensé</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body p-4">
                                <i class="fas fa-calculator text-info mb-3" style="font-size: 2rem;"></i>
                                <h5 class="fw-bold"><?php echo number_format($creditStats['average_cost_per_credit'], 2); ?>€</h5>
                                <p class="text-muted mb-0">Prix moyen/crédit</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historique des achats -->
                <?php if (!empty($purchaseHistory)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Historique des achats
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Crédits</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Carte</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchaseHistory as $purchase): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($purchase['created_at'])); ?></td>
                                        <td class="fw-bold"><?php echo $purchase['credits_amount']; ?> crédits</td>
                                        <td class="fw-bold"><?php echo number_format($purchase['amount_paid'], 2); ?>€</td>
                                        <td>
                                            <?php if ($purchase['payment_status'] === 'completed'): ?>
                                                <span class="badge bg-success">Payé</span>
                                            <?php elseif ($purchase['payment_status'] === 'pending'): ?>
                                                <span class="badge bg-warning">En attente</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Échoué</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($purchase['card_last4']): ?>
                                                <span class="text-muted">•••• <?php echo $purchase['card_last4']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Historique d'utilisation -->
                <?php if (!empty($usageHistory)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-history me-2"></i>
                            Historique d'utilisation
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Fonctionnalité</th>
                                        <th>Crédits utilisés</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usageHistory as $usage): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($usage['created_at'])); ?></td>
                                        <td>
                                            <?php
                                            $featureLabels = [
                                                'coaching_ai' => 'Coaching IA',
                                                'personalized_program' => 'Programme personnalisé',
                                                'nutrition_analysis' => 'Analyse nutritionnelle',
                                                'training_advice' => 'Conseil entraînement'
                                            ];
                                            echo $featureLabels[$usage['feature_used']] ?? $usage['feature_used'];
                                            ?>
                                        </td>
                                        <td class="fw-bold text-primary"><?php echo $usage['credits_used']; ?> crédits</td>
                                        <td>
                                            <?php if ($usage['description']): ?>
                                                <span class="text-muted"><?php echo htmlspecialchars(substr($usage['description'], 0, 50)); ?>...</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-cog me-2"></i>
                            Actions
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="buy-credits.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Acheter des crédits
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="dashboard.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-robot me-2"></i>Utiliser l'IA
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="contact.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-headset me-2"></i>Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations sur les crédits -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-light">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-question-circle text-primary me-2"></i>
                                    Comment utiliser vos crédits ?
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-comments text-primary me-2 mt-1"></i>
                                            <div>
                                                <strong>Coaching IA :</strong> 1 crédit par question
                                                <br><small class="text-muted">Posez des questions à notre IA de coaching</small>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="mb-3">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-dumbbell text-success me-2 mt-1"></i>
                                            <div>
                                                <strong>Programmes personnalisés :</strong> 3 crédits
                                                <br><small class="text-muted">Obtenez des plans d'entraînement sur mesure</small>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="mb-3">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-apple-alt text-warning me-2 mt-1"></i>
                                            <div>
                                                <strong>Analyses nutritionnelles :</strong> 2 crédits
                                                <br><small class="text-muted">Conseils alimentaires personnalisés</small>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="mb-3">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-chart-line text-info me-2 mt-1"></i>
                                            <div>
                                                <strong>Conseils d'entraînement :</strong> 2 crédits
                                                <br><small class="text-muted">Optimisez vos séances d'entraînement</small>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-light">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Informations importantes
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-clock text-warning me-2 mt-1"></i>
                                            <div>
                                                <strong>Pas d'expiration :</strong>
                                                <br><small class="text-muted">Vos crédits n'expirent jamais</small>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="mb-3">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-sync text-info me-2 mt-1"></i>
                                            <div>
                                                <strong>Utilisation flexible :</strong>
                                                <br><small class="text-muted">Utilisez-les quand vous voulez</small>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="mb-3">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-chart-line text-success me-2 mt-1"></i>
                                            <div>
                                                <strong>Économies :</strong>
                                                <br><small class="text-muted">Plus vous achetez, plus vous économisez</small>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="mb-3">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-headset text-primary me-2 mt-1"></i>
                                            <div>
                                                <strong>Support 24/7 :</strong>
                                                <br><small class="text-muted">Assistance disponible à tout moment</small>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
