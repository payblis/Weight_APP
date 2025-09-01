<?php
session_start();
require_once 'includes/translation.php';
require_once 'includes/subscription_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fr';
$fromLang = 'fr';
$toLang = $lang;
ob_start();
include 'header.php';

$userId = $_SESSION['user_id'];
$isPremium = SubscriptionManager::isUserPremium($userId);
$subscription = SubscriptionManager::getUserSubscription($userId);
$paymentHistory = SubscriptionManager::getUserPaymentHistory($userId, 5);

// Traitement de l'annulation d'abonnement
if (isset($_POST['cancel_subscription']) && $subscription) {
    $cancelled = SubscriptionManager::cancelSubscription($subscription['id'], $userId);
    if ($cancelled) {
        header('Location: my-subscription.php?cancelled=1');
        exit;
    }
}
?>
<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <h1 class="display-5 fw-bold mb-2">
                        <?php if ($isPremium): ?>
                            <i class="fas fa-gem text-warning"></i> Mon Abonnement Premium
                        <?php else: ?>
                            <i class="fas fa-user text-secondary"></i> Mon Statut
                        <?php endif; ?>
                    </h1>
                    <p class="lead text-muted">
                        <?php if ($isPremium): ?>
                            Gérez votre abonnement Premium et consultez votre historique de paiements
                        <?php else: ?>
                            Passez à Premium pour débloquer toutes les fonctionnalités avancées
                        <?php endif; ?>
                    </p>
                </div>

                <?php if (isset($_GET['cancelled'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    Votre abonnement a été annulé avec succès. Vous conservez l'accès Premium jusqu'à la fin de votre période de facturation.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($isPremium && $subscription): ?>
                <!-- Statut Premium Actif -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="fw-bold mb-2">
                                    <i class="fas fa-gem text-warning me-2"></i>
                                    Abonnement Premium Actif
                                </h4>
                                <p class="text-muted mb-0">
                                    <?php echo $subscription['plan_type'] === 'mensuel' ? 'Facturation mensuelle' : 'Facturation annuelle'; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <span class="badge bg-success fs-6">Actif</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Détails de l'abonnement -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="fw-bold mb-0"><i class="fas fa-info-circle me-2"></i>Détails de l'abonnement</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Plan :</span>
                                    <span class="fw-bold">
                                        <?php 
                                        $planLabels = [
                                            'mensuel' => 'Abonnement Mensuel',
                                            'annuel' => 'Abonnement Annuel',
                                            'famille' => 'Abonnement Famille'
                                        ];
                                        echo $planLabels[$subscription['plan_type']] ?? $subscription['plan_type'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Montant :</span>
                                    <span class="fw-bold text-primary"><?php echo number_format($subscription['amount'], 2); ?>€</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Date de début :</span>
                                    <span><?php echo date('d/m/Y', strtotime($subscription['start_date'])); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Date de fin :</span>
                                    <span><?php echo date('d/m/Y', strtotime($subscription['end_date'])); ?></span>
                                </div>
                            </div>
                            <?php if ($subscription['card_last4']): ?>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Carte :</span>
                                    <span>•••• <?php echo $subscription['card_last4']; ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Renouvellement :</span>
                                    <span><?php echo $subscription['auto_renew'] ? 'Automatique' : 'Manuel'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historique des paiements -->
                <?php if (!empty($paymentHistory)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="fw-bold mb-0"><i class="fas fa-history me-2"></i>Historique des paiements</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Plan</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paymentHistory as $payment): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($payment['created_at'])); ?></td>
                                        <td class="fw-bold"><?php echo number_format($payment['amount'], 2); ?>€</td>
                                        <td>
                                            <?php 
                                            $planLabels = [
                                                'mensuel' => 'Mensuel',
                                                'annuel' => 'Annuel',
                                                'famille' => 'Famille'
                                            ];
                                            echo $planLabels[$payment['plan_type']] ?? $payment['plan_type'];
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($payment['status'] === 'completed'): ?>
                                                <span class="badge bg-success">Payé</span>
                                            <?php elseif ($payment['status'] === 'pending'): ?>
                                                <span class="badge bg-warning">En attente</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Échoué</span>
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
                        <h5 class="fw-bold mb-3"><i class="fas fa-cog me-2"></i>Actions</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    <i class="fas fa-times me-2"></i>Annuler l'abonnement
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="premium.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-gem me-2"></i>Changer de plan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <!-- Statut Gratuit -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 text-center">
                        <div class="mb-4">
                            <i class="fas fa-user text-secondary" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Compte Gratuit</h4>
                        <p class="text-muted mb-4">
                            Vous utilisez actuellement la version gratuite de MyFity. 
                            Passez à Premium pour débloquer toutes les fonctionnalités avancées !
                        </p>
                        <a href="premium.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-gem me-2"></i>Devenir Premium
                        </a>
                    </div>
                </div>

                <!-- Fonctionnalités Premium -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="fw-bold mb-0"><i class="fas fa-star me-2"></i>Fonctionnalités Premium</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Programmes personnalisés</h6>
                                        <p class="text-muted small mb-0">Entraînements adaptés à vos objectifs</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Coaching IA</h6>
                                        <p class="text-muted small mb-0">Conseils intelligents personnalisés</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Analyses avancées</h6>
                                        <p class="text-muted small mb-0">Rapports détaillés de progression</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Communauté exclusive</h6>
                                        <p class="text-muted small mb-0">Groupes privés et défis Premium</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Modal d'annulation -->
<?php if ($isPremium && $subscription): ?>
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Annuler l'abonnement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir annuler votre abonnement Premium ?</p>
                <ul class="mb-3">
                    <li>Vous conserverez l'accès Premium jusqu'au <?php echo date('d/m/Y', strtotime($subscription['end_date'])); ?></li>
                    <li>Aucun remboursement ne sera effectué</li>
                    <li>Vous pourrez réactiver votre abonnement à tout moment</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="cancel_subscription" class="btn btn-danger">
                        Confirmer l'annulation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
<?php
$content = ob_get_contents();
ob_end_clean();
if ($lang !== 'fr') {
    $translator = new TranslationManager();
    $translatedContent = $translator->translatePage($content, $fromLang, $toLang);
    echo $translatedContent;
} else {
    echo $content;
}
?>
