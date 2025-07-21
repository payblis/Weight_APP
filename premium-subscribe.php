<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/translation.php';

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fr';
$fromLang = 'fr';
$toLang = $lang;

ob_start();
include 'header.php';
?>
<main class="py-5">
    <div class="container" style="max-width: 500px;">
        <?php showUserStatusBadge(); ?>
        <div class="text-center mb-4">
            <h1 class="display-5 fw-bold mb-2"><i class="fas fa-gem text-warning"></i> Devenir Premium</h1>
            <p class="lead text-muted">Débloquez toutes les fonctionnalités avancées de MyFity pour 9,99€/mois.</p>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <form autocomplete="off">
                    <div class="mb-3">
                        <label for="cardName" class="form-label">Nom sur la carte</label>
                        <input type="text" class="form-control" id="cardName" placeholder="Jean Dupont" required>
                    </div>
                    <div class="mb-3">
                        <label for="cardNumber" class="form-label">Numéro de carte</label>
                        <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" pattern="[0-9 ]{19}" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="cardExpiry" class="form-label">Expiration</label>
                            <input type="text" class="form-control" id="cardExpiry" placeholder="MM/AA" maxlength="5" pattern="[0-9]{2}/[0-9]{2}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cardCVC" class="form-label">CVC</label>
                            <input type="text" class="form-control" id="cardCVC" placeholder="123" maxlength="4" pattern="[0-9]{3,4}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="email" placeholder="votre@email.com" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold" disabled>Activer Premium (bientôt disponible)</button>
                </form>
                <div class="alert alert-info mt-4 mb-0 text-center">
                    <i class="fas fa-lock me-1"></i> Paiement 100% sécurisé (factice, aucune donnée n'est transmise)
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