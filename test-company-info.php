<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/translation.php';

// Détecter la langue demandée
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fr';
$fromLang = 'fr';
$toLang = $lang;

// Démarrer la capture de sortie pour la traduction
ob_start();

include 'header.php';
?>

<main class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="display-4 fw-bold mb-4">Informations de la Société</h1>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h3 mb-4">Payblis SASU - Informations légales</h2>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="h5 fw-bold text-primary mb-3">Informations générales</h4>
                            <ul class="list-unstyled">
                                <li class="mb-2"><strong>Raison sociale :</strong> Payblis SASU</li>
                                <li class="mb-2"><strong>Adresse :</strong> 99 AVENUE ACHILLE PERETTI, 92200 NEUILLY-SUR-SEINE, France</li>
                                <li class="mb-2"><strong>SIREN :</strong> 950843516</li>
                                <li class="mb-2"><strong>Numéro de TVA :</strong> FR53950843516</li>
                                <li class="mb-2"><strong>Capital social :</strong> 1 000,00 €</li>
                                <li class="mb-2"><strong>Forme juridique :</strong> Société par Actions Simplifiée Unipersonnelle (SASU)</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4 class="h5 fw-bold text-primary mb-3">Contact</h4>
                            <ul class="list-unstyled">
                                <li class="mb-2"><strong>Email général :</strong> <a href="mailto:contact@myfity.com">contact@myfity.com</a></li>
                                <li class="mb-2"><strong>Support :</strong> <a href="mailto:support@myfity.com">support@myfity.com</a></li>
                                <li class="mb-2"><strong>Vie privée :</strong> <a href="mailto:privacy@myfity.com">privacy@myfity.com</a></li>
                                <li class="mb-2"><strong>API :</strong> <a href="mailto:api@myfity.com">api@myfity.com</a></li>
                                <li class="mb-2"><strong>Emploi :</strong> <a href="mailto:emploi@myfity.com">emploi@myfity.com</a></li>
                                <li class="mb-2"><strong>Site web :</strong> www.myfity.com</li>
                            </ul>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h3 class="h4 mb-3">Pages mises à jour</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="fw-bold text-success mb-2">Pages légales</h5>
                            <ul class="list-unstyled">
                                <li class="mb-1">✅ <a href="mentions-legales.php">Mentions légales</a></li>
                                <li class="mb-1">✅ <a href="conditions-generales.php">Conditions générales</a></li>
                                <li class="mb-1">✅ <a href="politique-vie-privee.php">Politique de confidentialité</a></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5 class="fw-bold text-success mb-2">Pages de contact</h5>
                            <ul class="list-unstyled">
                                <li class="mb-1">✅ <a href="contact.php">Contact</a></li>
                                <li class="mb-1">✅ <a href="emploi.php">Emploi</a></li>
                                <li class="mb-1">✅ <a href="api.php">API</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-info">
                        <h5 class="alert-heading">Vérification terminée</h5>
                        <p class="mb-0">Toutes les informations de la société Payblis SASU ont été mises à jour sur l'ensemble du site MyFity.</p>
                    </div>
                    
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<?php
// Récupérer le contenu de la page
$content = ob_get_contents();
ob_end_clean();

// Appliquer la traduction si nécessaire
if ($lang !== 'fr') {
    $translator = new TranslationManager();
    $translatedContent = $translator->translatePage($content, $fromLang, $toLang);
    echo $translatedContent;
} else {
    echo $content;
}
?> 