<?php
session_start();
require_once 'includes/translation.php';
require_once 'includes/credit_functions.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté et a un achat réussi
if (!isLoggedIn() || !isset($_SESSION['credit_purchase_success'])) {
    redirect('buy-credits.php');
}

$purchaseData = $_SESSION['credit_purchase_success'];
$package = $purchaseData['package'];
$creditsAmount = $purchaseData['credits_amount'];
$amount = $purchaseData['amount'];
$transactionId = $purchaseData['transaction_id'];

$creditPackages = CreditManager::getCreditPackages();
$packageData = $creditPackages[$package] ?? null;

// Nettoyer les données de session
unset($_SESSION['credit_purchase_success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achat Réussi - MyFity</title>
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
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <div class="mb-4">
                        <i class="fas fa-robot text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h1 class="display-5 fw-bold mb-2 text-success">Achat réussi !</h1>
                    <p class="lead mb-4">Vos crédits IA ont été ajoutés à votre compte. Vous pouvez maintenant interagir avec notre IA de coaching !</p>
                </div>
                
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-3 text-center">Détails de votre achat</h4>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Package :</span>
                                    <span class="fw-bold"><?php echo $packageData ? $packageData['label'] : $package; ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Crédits achetés :</span>
                                    <span class="fw-bold text-primary"><?php echo $creditsAmount; ?></span>
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
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Prix par crédit :</span>
                                    <span class="text-muted"><?php echo $packageData ? number_format($packageData['price_per_credit'], 2) : '0.00'; ?>€</span>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-coins me-2"></i>
                            <strong>Félicitations !</strong> Vos <?php echo $creditsAmount; ?> crédits IA sont maintenant disponibles dans votre compte.
                        </div>
                        
                        <div class="row g-2 mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-2">Vous pouvez maintenant utiliser vos crédits pour :</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-comments text-primary me-2"></i><strong>Coaching IA :</strong> Poser des questions à notre IA (1 crédit)</li>
                                    <li><i class="fas fa-dumbbell text-success me-2"></i><strong>Programmes personnalisés :</strong> Obtenir des plans d'entraînement (3 crédits)</li>
                                    <li><i class="fas fa-apple-alt text-warning me-2"></i><strong>Analyses nutritionnelles :</strong> Conseils alimentaires personnalisés (2 crédits)</li>
                                    <li><i class="fas fa-chart-line text-info me-2"></i><strong>Conseils d'entraînement :</strong> Optimiser vos séances (2 crédits)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="my-credits.php" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-coins me-2"></i>Voir mes crédits
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-primary btn-lg me-3">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                    </a>
                    <a href="buy-credits.php" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-plus me-2"></i>Acheter plus de crédits
                    </a>
                </div>
                
                <!-- Informations supplémentaires -->
                <div class="row mt-5">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-lightbulb text-warning me-2"></i>
                                    Conseils d'utilisation
                                </h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Posez des questions précises pour de meilleures réponses
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Utilisez les crédits pour des conseils personnalisés
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Consultez votre historique d'utilisation
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Les crédits n'expirent jamais
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-headset text-primary me-2"></i>
                                    Besoin d'aide ?
                                </h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-question-circle text-info me-2"></i>
                                        <a href="#" class="text-decoration-none">FAQ sur les crédits IA</a>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-envelope text-success me-2"></i>
                                        <a href="contact.php" class="text-decoration-none">Contacter le support</a>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-book text-warning me-2"></i>
                                        <a href="#" class="text-decoration-none">Guide d'utilisation de l'IA</a>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-cog text-secondary me-2"></i>
                                        <a href="my-credits.php" class="text-decoration-none">Gérer mes crédits</a>
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
