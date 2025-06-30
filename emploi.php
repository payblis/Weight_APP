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
            <h1 class="display-4 fw-bold mb-4">Rejoignez l'équipe MyFity</h1>
            <p class="lead text-muted">Envie de contribuer à la santé et au bien-être de milliers de personnes ?</p>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-bold mb-3">Développeur PHP/JS</h3>
                        <p class="text-muted">Participez au développement de notre plateforme web et mobile. Expérience requise : 2 ans.</p>
                        <a href="mailto:jobs@myfity.com" class="btn btn-primary btn-sm">Postuler</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-bold mb-3">Coach Nutrition & Fitness</h3>
                        <p class="text-muted">Accompagnez nos utilisateurs dans l'atteinte de leurs objectifs. Diplôme en nutrition ou sport exigé.</p>
                        <a href="mailto:jobs@myfity.com" class="btn btn-primary btn-sm">Postuler</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-primary text-white rounded p-5 text-center">
            <h2 class="fw-bold mb-3">Pourquoi MyFity ?</h2>
            <p class="lead mb-4">Startup dynamique, équipe passionnée, impact positif sur la santé publique, télétravail possible.</p>
            <a href="mailto:jobs@myfity.com" class="btn btn-light btn-lg">Envoyer une candidature spontanée</a>
        </div>
        <div class="col-lg-8">
            <h2 class="h3 mb-4">Notre mission</h2>
            <p class="lead mb-4">Aider des millions de personnes à atteindre leurs objectifs de santé et de bien-être grâce à la technologie.</p>
            
            <h2 class="h3 mb-4">À propos de Payblis SASU</h2>
            <p>Payblis SASU est une société innovante basée à Neuilly-sur-Seine, spécialisée dans le développement d'applications de santé et de bien-être. Notre application MyFity aide les utilisateurs à suivre leur nutrition et leur activité physique pour atteindre leurs objectifs de santé.</p>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <h5 class="fw-bold text-primary">Informations légales</h5>
                    <ul class="list-unstyled">
                        <li><strong>Raison sociale :</strong> Payblis SASU</li>
                        <li><strong>Adresse :</strong> 99 AVENUE ACHILLE PERETTI, 92200 NEUILLY-SUR-SEINE, France</li>
                        <li><strong>SIREN :</strong> 950843516</li>
                        <li><strong>TVA :</strong> FR53950843516</li>
                        <li><strong>Capital :</strong> 1 000,00 €</li>
                        <li><strong>Forme juridique :</strong> SASU</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5 class="fw-bold text-primary">Contact</h5>
                    <ul class="list-unstyled">
                        <li><strong>Email :</strong> <a href="mailto:emploi@myfity.com">emploi@myfity.com</a></li>
                        <li><strong>Site web :</strong> www.myfity.com</li>
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