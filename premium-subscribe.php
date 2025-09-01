<?php
session_start();
require_once 'includes/translation.php';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fr';
$fromLang = 'fr';
$toLang = $lang;
ob_start();
include 'header.php';
$plan = isset($_GET['plan']) ? $_GET['plan'] : 'mensuel';
$plans = [
    'mensuel' => [
        'label' => 'Abonnement Mensuel',
        'price' => '9,99€',
        'desc' => 'Facturation mensuelle, annulation à tout moment.',
        'period' => 'mois'
    ],
    'annuel' => [
        'label' => 'Abonnement Annuel',
        'price' => '59,99€',
        'desc' => 'Facturation annuelle, économisez 40%.',
        'period' => 'an'
    ],
    'famille' => [
        'label' => 'Abonnement Famille',
        'price' => '99,99€',
        'desc' => 'Jusqu\'à 6 personnes, facturation annuelle.',
        'period' => 'an'
    ]
];
$selected = $plans[$plan] ?? $plans['mensuel'];
?>
<main class="py-5">
    <div class="container" style="max-width: 600px;">
        <div class="text-center mb-4">
            <h1 class="display-5 fw-bold mb-2"><i class="fas fa-gem text-warning"></i> Devenir Premium</h1>
            <p class="lead text-muted mb-1"><?php echo $selected['label']; ?> <span class="fw-bold text-primary ms-2"><?php echo $selected['price']; ?></span></p>
            <p class="small text-muted mb-0"><?php echo $selected['desc']; ?></p>
        </div>
        
        <?php if (isset($_SESSION['payment_errors']) && !empty($_SESSION['payment_errors'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Erreur de paiement :</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($_SESSION['payment_errors'] as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php 
        unset($_SESSION['payment_errors']);
        endif; 
        ?>
        
        <!-- Sélecteur de plan -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-2">
                    <?php foreach ($plans as $planKey => $planData): ?>
                    <div class="col-4">
                        <a href="?plan=<?php echo $planKey; ?>" 
                           class="btn btn-outline-primary w-100 <?php echo $plan === $planKey ? 'active' : ''; ?>">
                            <div class="small fw-bold"><?php echo $planData['price']; ?></div>
                            <div class="small text-muted">/<?php echo $planData['period']; ?></div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <form id="paymentForm" autocomplete="off" method="POST" action="process-payment.php">
                    <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan); ?>">
                    
                    <!-- Informations personnelles -->
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3"><i class="fas fa-user me-2"></i>Informations personnelles</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" placeholder="Jean" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Dupont" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="email" class="form-label">Adresse email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="votre@email.com" required>
                        </div>
                    </div>

                    <!-- Informations de paiement -->
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3"><i class="fas fa-credit-card me-2"></i>Informations de paiement</h5>
                        
                        <div class="mb-3">
                            <label for="cardName" class="form-label">Nom sur la carte</label>
                            <input type="text" class="form-control" id="cardName" name="cardName" placeholder="Jean Dupont" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cardNumber" class="form-label">Numéro de carte</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="cardNumber" name="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                                <span class="input-group-text" id="cardType">
                                    <i class="fas fa-credit-card text-muted"></i>
                                </span>
                            </div>
                            <div class="form-text" id="cardNumberHelp"></div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="cardExpiry" class="form-label">Date d'expiration</label>
                                <input type="text" class="form-control" id="cardExpiry" name="cardExpiry" placeholder="MM/AA" maxlength="5" required>
                            </div>
                            <div class="col-md-6">
                                <label for="cardCVC" class="form-label">Code de sécurité</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="cardCVC" name="cardCVC" placeholder="123" maxlength="4" required>
                                    <span class="input-group-text" id="cvcHelp" data-bs-toggle="tooltip" title="Le code de sécurité se trouve au dos de votre carte">
                                        <i class="fas fa-question-circle"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résumé de la commande -->
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3"><i class="fas fa-receipt me-2"></i>Résumé de la commande</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo $selected['label']; ?></span>
                                    <span class="fw-bold"><?php echo $selected['price']; ?></span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total</span>
                                    <span class="text-primary"><?php echo $selected['price']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Conditions -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="termsAccepted" required>
                            <label class="form-check-label" for="termsAccepted">
                                J'accepte les <a href="conditions-generales.php" target="_blank">conditions générales</a> et la <a href="politique-vie-privee.php" target="_blank">politique de confidentialité</a>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 fw-bold py-3" id="submitBtn">
                        <i class="fas fa-lock me-2"></i>Payer <?php echo $selected['price']; ?> - Paiement sécurisé
                    </button>
                </form>
                
                <div class="alert alert-info mt-4 mb-0 text-center">
                    <i class="fas fa-shield-alt me-1"></i> Paiement 100% sécurisé par SSL
                </div>
            </div>
        </div>
        
        <div class="text-center">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
            </a>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

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
    const form = document.getElementById('paymentForm');
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

        // Simulation d'un traitement de paiement
        setTimeout(() => {
            form.submit();
        }, 2000);
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

.btn-warning {
    background: linear-gradient(45deg, #ffc107, #ff8c00);
    border: none;
    transition: all 0.3s ease;
}

.btn-warning:hover {
    background: linear-gradient(45deg, #ff8c00, #ffc107);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 193, 7, 0.4);
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #ced4da;
}

.fab.fa-cc-visa { color: #1a1f71; }
.fab.fa-cc-mastercard { color: #eb001b; }
.fab.fa-cc-amex { color: #006fcf; }
.fab.fa-cc-discover { color: #ff6000; }
</style>

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