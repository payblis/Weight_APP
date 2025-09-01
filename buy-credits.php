<?php
session_start();
require_once 'includes/translation.php';
require_once 'includes/credit_functions.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php?redirect=buy-credits.php');
}

$userId = $_SESSION['user_id'];
$userCredits = CreditManager::getUserCredits($userId);
$creditPackages = CreditManager::getCreditPackages();
$selectedPackage = isset($_GET['package']) ? $_GET['package'] : 'medium';

// Gérer le package personnalisé
if ($selectedPackage === 'custom' && isset($_GET['credits'])) {
    $customCredits = intval($_GET['credits']);
    if ($customCredits >= 1 && $customCredits <= 1000) {
        $selectedPackageData = CreditManager::calculateCustomPrice($customCredits);
        $selectedPackageData['label'] = 'Pack Personnalisé';
        $selectedPackageData['description'] = 'Crédits personnalisés selon vos besoins';
    } else {
        $selectedPackage = 'medium';
        $selectedPackageData = $creditPackages[$selectedPackage];
    }
} else {
    // Vérifier si le package sélectionné existe
    if (!isset($creditPackages[$selectedPackage])) {
        $selectedPackage = 'medium';
    }
    $selectedPackageData = $creditPackages[$selectedPackage];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crédits IA - MyFity</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <main class="py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="fas fa-robot text-primary"></i> Crédits IA
                    </h1>
                    <p class="lead text-muted">
                        Achetez des crédits pour interagir avec notre IA de coaching personnalisé
                    </p>
                </div>

                <!-- Statut des crédits actuels -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="fw-bold mb-2">
                                    <i class="fas fa-coins text-warning me-2"></i>
                                    Vos crédits actuels
                                </h4>
                                <p class="text-muted mb-0">
                                    Solde disponible : <span class="fw-bold text-primary fs-4"><?php echo $userCredits['credits_balance']; ?></span> crédits
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="my-credits.php" class="btn btn-outline-primary">
                                    <i class="fas fa-history me-2"></i>Voir l'historique
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sélection des packages -->
                <div class="row g-4 mb-5">
                    <?php foreach ($creditPackages as $packageKey => $package): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm <?php echo $selectedPackage === $packageKey ? 'border-primary' : ''; ?>">
                            <?php if (isset($package['popular'])): ?>
                            <div class="card-header bg-primary text-white text-center py-2">
                                <small class="fw-bold">LE PLUS POPULAIRE</small>
                            </div>
                            <?php endif; ?>
                            <?php if ($package['bonus'] > 0): ?>
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-gift me-1"></i>+<?php echo $package['bonus']; ?> bonus
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="card-body p-4 text-center">
                                <h5 class="fw-bold mb-2"><?php echo $package['label']; ?></h5>
                                <div class="mb-3">
                                    <span class="display-6 fw-bold text-primary"><?php echo $package['credits'] + $package['bonus']; ?></span>
                                    <span class="text-muted"> crédits</span>
                                    <?php if ($package['bonus'] > 0): ?>
                                    <div class="small text-warning">
                                        <i class="fas fa-gift me-1"></i><?php echo $package['bonus_percent']; ?>% bonus offert
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <span class="h4 fw-bold"><?php echo number_format($package['price'], 2); ?>€</span>
                                    <div class="text-muted small">
                                        <?php echo number_format($package['price_per_credit'], 2); ?>€ par crédit
                                    </div>
                                    <?php if ($package['bonus'] > 0): ?>
                                    <div class="text-success small">
                                        <i class="fas fa-arrow-down me-1"></i>Économisez <?php echo $package['bonus_percent']; ?>%
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted small mb-3"><?php echo $package['description']; ?></p>
                                <a href="?package=<?php echo $packageKey; ?>" 
                                   class="btn <?php echo $selectedPackage === $packageKey ? 'btn-primary' : 'btn-outline-primary'; ?> w-100">
                                    <?php if ($selectedPackage === $packageKey): ?>
                                        <i class="fas fa-check me-2"></i>Sélectionné
                                    <?php else: ?>
                                        Choisir ce pack
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Slider personnalisé -->
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-header bg-light">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-sliders-h me-2"></i>
                            Crédits personnalisés
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <label for="customCredits" class="form-label fw-bold">
                                    Nombre de crédits : <span id="customCreditsValue">50</span>
                                </label>
                                <input type="range" class="form-range" id="customCredits" 
                                       min="1" max="1000" value="50" step="1">
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>1 crédit</span>
                                    <span>1000 crédits</span>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="h4 fw-bold text-primary mb-1" id="customPrice">25.00€</div>
                                        <div class="text-muted small mb-2" id="customPricePerCredit">0.50€ par crédit</div>
                                        <div class="text-warning small mb-2" id="customBonus" style="display: none;">
                                            <i class="fas fa-gift me-1"></i><span id="customBonusAmount">0</span> bonus
                                        </div>
                                        <div class="text-success small" id="customSavings" style="display: none;">
                                            <i class="fas fa-arrow-down me-1"></i>Économisez <span id="customSavingsPercent">0</span>%
                                        </div>
                                        <button type="button" class="btn btn-primary mt-2" id="selectCustom">
                                            <i class="fas fa-check me-2"></i>Sélectionner
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de paiement -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-credit-card me-2"></i>
                            Paiement - <?php echo $selectedPackageData['label']; ?>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['credit_errors']) && !empty($_SESSION['credit_errors'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Erreur de paiement :</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($_SESSION['credit_errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php 
                        unset($_SESSION['credit_errors']);
                        endif; 
                        ?>

                        <form id="creditPaymentForm" method="POST" action="process-credit-purchase.php">
                            <input type="hidden" name="package" value="<?php echo htmlspecialchars($selectedPackage); ?>">
                            <?php if ($selectedPackage === 'custom'): ?>
                            <input type="hidden" name="custom_credits" value="<?php echo htmlspecialchars($_GET['credits']); ?>">
                            <?php endif; ?>
                            
                            <!-- Résumé de la commande -->
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <h6 class="fw-bold mb-3">Résumé de votre commande</h6>
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span><?php echo $selectedPackageData['label']; ?> (<?php echo $selectedPackageData['credits']; ?> crédits)</span>
                                                <span class="fw-bold"><?php echo number_format($selectedPackageData['price'], 2); ?>€</span>
                                            </div>
                                            <?php if ($selectedPackageData['bonus'] > 0): ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-warning">
                                                    <i class="fas fa-gift me-1"></i>Bonus offert (<?php echo $selectedPackageData['bonus_percent']; ?>%)
                                                </span>
                                                <span class="text-warning fw-bold">+<?php echo $selectedPackageData['bonus']; ?> crédits</span>
                                            </div>
                                            <?php endif; ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Prix par crédit</span>
                                                <span class="text-muted"><?php echo number_format($selectedPackageData['price_per_credit'], 2); ?>€</span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="fw-bold">Total crédits reçus</span>
                                                <span class="fw-bold text-primary"><?php echo $selectedPackageData['credits'] + $selectedPackageData['bonus']; ?> crédits</span>
                                            </div>
                                            <div class="d-flex justify-content-between fw-bold">
                                                <span>Total à payer</span>
                                                <span class="text-primary"><?php echo number_format($selectedPackageData['price'], 2); ?>€</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="fw-bold mb-3">Informations personnelles</h6>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Adresse email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" 
                                               placeholder="votre@email.com" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations de paiement -->
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-credit-card me-2"></i>
                                Informations de paiement
                            </h6>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label for="cardName" class="form-label">Nom du titulaire de la carte</label>
                                    <input type="text" class="form-control" id="cardName" name="cardName" 
                                           placeholder="Nom du titulaire de la carte" required>
                                </div>
                                
                                <div class="col-12">
                                    <label for="cardNumber" class="form-label">Numéro de carte</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="cardNumber" name="cardNumber" 
                                               placeholder="1234 5678 9012 3456" maxlength="19" required>
                                        <span class="input-group-text" id="cardType">
                                            <i class="fas fa-credit-card text-muted"></i>
                                        </span>
                                    </div>
                                    <div class="form-text" id="cardNumberHelp"></div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="cardExpiry" class="form-label">Date d'expiration</label>
                                    <input type="text" class="form-control" id="cardExpiry" name="cardExpiry" 
                                           placeholder="MM/AA" maxlength="5" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="cardCVC" class="form-label">Code de sécurité</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="cardCVC" name="cardCVC" 
                                               placeholder="123" maxlength="4" required>
                                        <span class="input-group-text" id="cvcHelp" data-bs-toggle="tooltip" 
                                              title="Le code de sécurité se trouve au dos de votre carte">
                                            <i class="fas fa-question-circle"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Conditions -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="termsAccepted" required>
                                    <label class="form-check-label" for="termsAccepted">
                                        J'accepte les <a href="conditions-generales.php" target="_blank">conditions générales</a> 
                                        et la <a href="politique-vie-privee.php" target="_blank">politique de confidentialité</a>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
                                <i class="fas fa-robot me-2"></i>
                                Acheter <?php echo $selectedPackageData['credits']; ?> crédits pour <?php echo number_format($selectedPackageData['price'], 2); ?>€
                            </button>
                        </form>
                        
                        <div class="alert alert-info mt-4 mb-0 text-center">
                            <i class="fas fa-shield-alt me-1"></i> Paiement 100% sécurisé par SSL
                        </div>
                    </div>
                </div>

                <!-- Informations sur les crédits -->
                <div class="row mt-5">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-question-circle text-primary me-2"></i>
                                    Comment utiliser vos crédits ?
                                </h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        <strong>Coaching IA :</strong> 1 crédit par question
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        <strong>Programmes personnalisés :</strong> 3 crédits
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        <strong>Analyses nutritionnelles :</strong> 2 crédits
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        <strong>Conseils d'entraînement :</strong> 2 crédits
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Informations importantes
                                </h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-clock text-warning me-2"></i>
                                        Les crédits n'expirent jamais
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-sync text-info me-2"></i>
                                        Utilisez-les quand vous voulez
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-chart-line text-success me-2"></i>
                                        Plus vous achetez, plus vous économisez
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-headset text-primary me-2"></i>
                                        Support client disponible 24/7
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Gestion du slider personnalisé
    const customCreditsSlider = document.getElementById('customCredits');
    const customCreditsValue = document.getElementById('customCreditsValue');
    const customPrice = document.getElementById('customPrice');
    const customPricePerCredit = document.getElementById('customPricePerCredit');
    const customBonus = document.getElementById('customBonus');
    const customBonusAmount = document.getElementById('customBonusAmount');
    const customSavings = document.getElementById('customSavings');
    const customSavingsPercent = document.getElementById('customSavingsPercent');
    const selectCustomBtn = document.getElementById('selectCustom');

    // Fonction pour calculer le prix personnalisé
    function updateCustomPrice() {
        const credits = parseInt(customCreditsSlider.value);
        customCreditsValue.textContent = credits;
        
        // Appel AJAX pour calculer le prix
        fetch('calculate-custom-price.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'credits=' + credits
        })
        .then(response => response.json())
        .then(data => {
            customPrice.textContent = data.price + '€';
            customPricePerCredit.textContent = data.price_per_credit + '€ par crédit';
            
            if (data.bonus > 0) {
                customBonus.style.display = 'block';
                customBonusAmount.textContent = data.bonus;
            } else {
                customBonus.style.display = 'none';
            }
            
            if (data.savings_percent > 0) {
                customSavings.style.display = 'block';
                customSavingsPercent.textContent = data.savings_percent;
            } else {
                customSavings.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    }

    // Événement pour le slider
    customCreditsSlider.addEventListener('input', updateCustomPrice);
    
    // Événement pour sélectionner le pack personnalisé
    selectCustomBtn.addEventListener('click', function() {
        const credits = parseInt(customCreditsSlider.value);
        window.location.href = '?package=custom&credits=' + credits;
    });

    // Initialiser le prix personnalisé
    updateCustomPrice();

    // Formatage du numéro de carte
    const cardNumber = document.getElementById('cardNumber');
    const cardType = document.getElementById('cardType');
    const cardNumberHelp = document.getElementById('cardNumberHelp');

    cardNumber.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '');
        value = value.replace(/\D/g, '');
        
        // Formatage avec espaces
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        
        e.target.value = formattedValue;
        
        // Détection du type de carte
        detectCardType(value);
    });

    function detectCardType(number) {
        const patterns = {
            visa: /^4/,
            mastercard: /^5[1-5]/,
            amex: /^3[47]/,
            discover: /^6(?:011|5)/
        };

        for (const [type, pattern] of Object.entries(patterns)) {
            if (pattern.test(number)) {
                cardType.innerHTML = `<i class="fab fa-cc-${type} text-primary"></i>`;
                cardNumberHelp.textContent = `Carte ${type.charAt(0).toUpperCase() + type.slice(1)} détectée`;
                return;
            }
        }
        
        cardType.innerHTML = '<i class="fas fa-credit-card text-muted"></i>';
        cardNumberHelp.textContent = '';
    }

    // Formatage de la date d'expiration
    const cardExpiry = document.getElementById('cardExpiry');
    cardExpiry.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        
        e.target.value = value;
    });

    // Formatage du CVC
    const cardCVC = document.getElementById('cardCVC');
    cardCVC.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });

    // Validation du formulaire
    const form = document.getElementById('creditPaymentForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation basique
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Animation de chargement
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...';
        submitBtn.disabled = true;

        // Soumettre le formulaire
        setTimeout(() => {
            form.submit();
        }, 1000);
    });

    // Validation en temps réel
    const inputs = form.querySelectorAll('input[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    });
});
</script>

<style>
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
}

.btn-primary {
    background: linear-gradient(45deg, #0066cc, #0056d6);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(45deg, #0056d6, #0066cc);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 102, 204, 0.4);
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #ced4da;
}

.fab.fa-cc-visa { color: #1a1f71; }
.fab.fa-cc-mastercard { color: #eb001b; }
.fab.fa-cc-amex { color: #006fcf; }
.fab.fa-cc-discover { color: #ff6000; }

/* Styles pour le slider personnalisé */
.form-range {
    height: 8px;
    border-radius: 4px;
    background: linear-gradient(90deg, #e9ecef 0%, #0066cc 50%, #e9ecef 100%);
}

.form-range::-webkit-slider-thumb {
    background: #0066cc;
    border: 2px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

.form-range::-webkit-slider-thumb:hover {
    background: #0056d6;
    transform: scale(1.1);
}

.form-range::-moz-range-thumb {
    background: #0066cc;
    border: 2px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

.form-range::-moz-range-thumb:hover {
    background: #0056d6;
    transform: scale(1.1);
}

/* Animation pour les bonus */
.badge.bg-warning {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
    </style>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
