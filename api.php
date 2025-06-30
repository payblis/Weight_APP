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
<main class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-4">API MyFity</h1>
            <p class="lead text-muted">Intégrez les données MyFity dans vos applications et services !</p>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-bold mb-3">Fonctionnalités de l'API</h3>
                        <ul class="text-muted">
                            <li>Accès aux bases de données aliments et nutriments</li>
                            <li>Connexion à des applications tierces (fitness, santé...)</li>
                            <li>Export des journaux alimentaires et d'activité</li>
                            <li>Création de bots et d'intégrations personnalisées</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-bold mb-3">Documentation & Support</h3>
                        <p class="text-muted">La documentation complète de l'API est disponible sur demande. Pour toute question technique ou demande d'accès, contactez notre équipe développeur.</p>
                        <a href="mailto:dev@myfity.com" class="btn btn-primary">Contacter l'équipe API</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-primary text-white rounded p-5 text-center">
            <h2 class="fw-bold mb-3">Prêt à connecter votre app ?</h2>
            <a href="mailto:dev@myfity.com" class="btn btn-light btn-lg">Demander un accès API</a>
        </div>
        <div class="col-lg-8">
            <h2 class="h3 mb-4">À propos de l'API MyFity</h2>
            <p>L'API MyFity est développée et maintenue par Payblis SASU, une société spécialisée dans les applications de santé et de bien-être.</p>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <h5 class="fw-bold text-primary">Informations légales</h5>
                    <ul class="list-unstyled">
                        <li><strong>Raison sociale :</strong> Payblis SASU</li>
                        <li><strong>Adresse :</strong> 99 AVENUE ACHILLE PERETTI, 92200 NEUILLY-SUR-SEINE, France</li>
                        <li><strong>SIREN :</strong> 950843516</li>
                        <li><strong>TVA :</strong> FR53950843516</li>
                        <li><strong>Capital :</strong> 1 000,00 €</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5 class="fw-bold text-primary">Support API</h5>
                    <ul class="list-unstyled">
                        <li><strong>Email :</strong> <a href="mailto:api@myfity.com">api@myfity.com</a></li>
                        <li><strong>Documentation :</strong> <a href="#">docs.myfity.com</a></li>
                        <li><strong>Status :</strong> <a href="#">status.myfity.com</a></li>
                    </ul>
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