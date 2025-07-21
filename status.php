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
        <div class="text-center mb-4">
            <h1 class="display-5 fw-bold mb-2"><i class="fas fa-user text-secondary"></i> Statut de l'abonnement</h1>
            <p class="lead text-muted">Votre statut actuel sur MyFity</p>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 text-center">
                <span class="badge bg-secondary fs-5 px-4 py-2"><i class="fas fa-user me-2"></i>Free</span>
                <p class="mt-3 mb-0">Vous bénéficiez actuellement de l'accès gratuit à toutes les fonctionnalités de base de MyFity.<br>Pour débloquer les fonctionnalités avancées, passez à l'offre Premium !</p>
                <a href="premium-subscribe.php" class="btn btn-warning mt-4 fw-bold"><i class="fas fa-gem me-1"></i> Devenir Premium</a>
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