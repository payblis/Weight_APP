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
            <h1 class="display-4 fw-bold mb-4">Mentions Légales</h1>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h3 mb-3">Éditeur du site</h2>
                    <p><strong>Raison sociale :</strong> Payblis SASU</p>
                    <p><strong>Adresse :</strong> 99 AVENUE ACHILLE PERETTI, 92200 NEUILLY-SUR-SEINE, France</p>
                    <p><strong>SIREN :</strong> 950843516</p>
                    <p><strong>Numéro de TVA intracommunautaire :</strong> FR53950843516</p>
                    <p><strong>Capital social :</strong> 1 000,00 €</p>
                    <p><strong>Forme juridique :</strong> Société par Actions Simplifiée Unipersonnelle (SASU)</p>
                    <p><strong>Directeur de publication :</strong> Le représentant légal de Payblis SASU</p>
                    <p><strong>Contact :</strong> <a href="mailto:contact@myfity.com">contact@myfity.com</a></p>

                    <h2 class="h3 mb-3 mt-4">Hébergement</h2>
                    <p>Ce site est hébergé par un prestataire de services d'hébergement web professionnel, conformément aux standards de sécurité et de performance en vigueur.</p>

                    <h2 class="h3 mb-3 mt-4">Propriété intellectuelle</h2>
                    <p>L'ensemble de ce site relève de la législation française et internationale sur le droit d'auteur et la propriété intellectuelle. Tous les droits de reproduction sont réservés, y compris pour les documents téléchargeables et les représentations iconographiques et photographiques.</p>
                    <p>La reproduction de tout ou partie de ce site sur un support électronique quel qu'il soit est formellement interdite sauf autorisation expresse du directeur de la publication.</p>

                    <h2 class="h3 mb-3 mt-4">Protection des données personnelles</h2>
                    <p>Conformément à la loi Informatique et Libertés du 6 janvier 1978 modifiée et au Règlement Général sur la Protection des Données (RGPD), vous disposez d'un droit d'accès, de rectification, de suppression et d'opposition aux données personnelles vous concernant.</p>
                    <p>Pour exercer ces droits, vous pouvez nous contacter à l'adresse suivante : <a href="mailto:privacy@myfity.com">privacy@myfity.com</a></p>

                    <h2 class="h3 mb-3 mt-4">Cookies</h2>
                    <p>Ce site utilise des cookies pour améliorer votre expérience utilisateur. Vous pouvez configurer votre navigateur pour refuser les cookies ou être informé quand des cookies sont envoyés.</p>

                    <h2 class="h3 mb-3 mt-4">Liens hypertextes</h2>
                    <p>Les liens hypertextes mis en place dans le cadre du présent site web en direction d'autres ressources présentes sur le réseau Internet ne sauraient engager la responsabilité de Payblis SASU.</p>

                    <h2 class="h3 mb-3 mt-4">Droit applicable</h2>
                    <p>Tout litige en relation avec l'utilisation du site <strong>myfity.com</strong> est soumis au droit français. En dehors des cas où la loi ne le permet pas, il est fait attribution exclusive de juridiction aux tribunaux compétents de Paris.</p>

                    <h2 class="h3 mb-3 mt-4">Modifications</h2>
                    <p>Payblis SASU se réserve le droit de modifier ces mentions légales à tout moment. L'utilisateur s'engage à les consulter régulièrement.</p>

                    <div class="mt-4 p-3 bg-light rounded">
                        <p class="mb-0"><strong>Dernière mise à jour :</strong> Janvier 2024</p>
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