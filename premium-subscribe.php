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
        'desc' => 'Facturation mensuelle, annulation à tout moment.'
    ],
    'annuel' => [
        'label' => 'Abonnement Annuel',
        'price' => '59,99€',
        'desc' => 'Facturation annuelle, économisez 40%.'
    ],
    'famille' => [
        'label' => 'Abonnement Famille',
        'price' => '99,99€',
        'desc' => 'Jusqu’à 6 personnes, facturation annuelle.'
    ]
];
$selected = $plans[$plan] ?? $plans['mensuel'];
?>
<main class="py-5">
    <div class="container" style="max-width: 500px;">
        <div class="text-center mb-4">
            <h1 class="display-5 fw-bold mb-2"><i class="fas fa-gem text-warning"></i> Devenir Premium</h1>
            <p class="lead text-muted mb-1"><?php echo $selected['label']; ?> <span class="fw-bold text-primary ms-2"><?php echo $selected['price']; ?></span></p>
            <p class="small text-muted mb-0"><?php echo $selected['desc']; ?></p>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <form autocomplete="off" method="POST" action="premium-success.php">
                    <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan); ?>">
                    <div class="mb-3">
                        <label for="cardName" class="form-label">Nom sur la carte</label>
                        <input type="text" class="form-control" id="cardName" name="cardName" placeholder="Jean Dupont" required>
                    </div>
                    <div class="mb-3">
                        <label for="cardNumber" class="form-label">Numéro de carte</label>
                        <input type="text" class="form-control" id="cardNumber" name="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" pattern="[0-9 ]{19}" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="cardExpiry" class="form-label">Expiration</label>
                            <input type="text" class="form-control" id="cardExpiry" name="cardExpiry" placeholder="MM/AA" maxlength="5" pattern="[0-9]{2}/[0-9]{2}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cardCVC" class="form-label">CVC</label>
                            <input type="text" class="form-control" id="cardCVC" name="cardCVC" placeholder="123" maxlength="4" pattern="[0-9]{3,4}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="votre@email.com" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold">Activer Premium</button>
                </form>
                <div class="alert alert-info mt-4 mb-0 text-center">
                    <i class="fas fa-lock me-1"></i> Paiement 100% sécurisé
                </div>
            </div>
        </div>
        <div class="text-center">
            <a href="index.php" class="btn btn-outline-secondary">Retour à l'accueil</a>
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