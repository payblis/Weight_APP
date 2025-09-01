<?php
session_start();
require_once 'includes/translation.php';

// Vérifier si l'utilisateur est connecté et a un paiement réussi
if (!isset($_SESSION['user_id']) || !isset($_SESSION['payment_success'])) {
    header('Location: premium-subscribe.php');
    exit;
}

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fr';
$fromLang = 'fr';
$toLang = $lang;
ob_start();
include 'header.php';

$paymentData = $_SESSION['payment_success'];
$plan = $paymentData['plan'];
$amount = $paymentData['amount'];
$transactionId = $paymentData['transaction_id'];

$plans = [
    'mensuel' => [
        'label' => 'Abonnement Mensuel',
        'price' => '9,99€',
        'period' => 'mois'
    ],
    'annuel' => [
        'label' => 'Abonnement Annuel',
        'price' => '59,99€',
        'period' => 'an'
    ],
    'famille' => [
        'label' => 'Abonnement Famille',
        'price' => '99,99€',
        'period' => 'an'
    ]
];
$selected = $plans[$plan] ?? $plans['mensuel'];

// Nettoyer les données de session
unset($_SESSION['payment_success']);
?>
<main class="py-5">
    <div class="container" style="max-width: 600px;">
        <div class="text-center mb-4">
            <div class="mb-4">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
            </div>
            <h1 class="display-5 fw-bold mb-2 text-success">Paiement réussi !</h1>
            <p class="lead mb-4">Votre abonnement Premium est maintenant actif. Merci pour votre confiance !</p>
        </div>
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-3 text-center">Détails de votre abonnement</h4>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Plan :</span>
                            <span class="fw-bold"><?php echo $selected['label']; ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Montant :</span>
                            <span class="fw-bold text-primary"><?php echo number_format($amount, 2); ?>€</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Transaction :</span>
                            <span class="text-muted small"><?php echo substr($transactionId, 0, 12) . '...'; ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Date :</span>
                            <span class="text-muted"><?php echo date('d/m/Y H:i'); ?></span>
                        </div>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="alert alert-success mb-3">
                    <i class="fas fa-star me-2"></i>
                    <strong>Félicitations !</strong> Vous avez maintenant accès à toutes les fonctionnalités Premium de MyFity.
                </div>
                
                <div class="row g-2 mb-4">
                    <div class="col-12">
                        <h6 class="fw-bold mb-2">Fonctionnalités Premium débloquées :</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Programmes d'entraînement personnalisés</li>
                            <li><i class="fas fa-check text-success me-2"></i>Suivi nutritionnel avancé</li>
                            <li><i class="fas fa-check text-success me-2"></i>Coaching IA intelligent</li>
                            <li><i class="fas fa-check text-success me-2"></i>Communauté Premium exclusive</li>
                            <li><i class="fas fa-check text-success me-2"></i>Rapports détaillés et analyses</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center">
            <a href="dashboard.php" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-tachometer-alt me-2"></i>Accéder à mon tableau de bord
            </a>
            <a href="premium.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-gem me-2"></i>Découvrir les fonctionnalités Premium
            </a>
        </div>
    </div>
</main>
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