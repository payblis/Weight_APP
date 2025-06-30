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
            <h1 class="display-4 fw-bold mb-4">Politique de Confidentialité</h1>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="mb-4">
                        <h2 class="h3 mb-3">1. Introduction</h2>
                        <p>Payblis SASU, éditeur de l'application MyFity, s'engage à protéger la vie privée de ses utilisateurs. Cette politique de confidentialité décrit comment nous collectons, utilisons et protégeons vos informations personnelles.</p>
                        <p><strong>Responsable du traitement :</strong> Payblis SASU<br>
                        <strong>Adresse :</strong> 99 AVENUE ACHILLE PERETTI, 92200 NEUILLY-SUR-SEINE, France<br>
                        <strong>SIREN :</strong> 950843516<br>
                        <strong>Email :</strong> <a href="mailto:privacy@myfity.com">privacy@myfity.com</a></p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">2. Informations collectées</h2>
                        <p>Nous collectons les informations suivantes :</p>
                        <ul>
                            <li><strong>Informations d'identification :</strong> nom, prénom, adresse email, mot de passe</li>
                            <li><strong>Informations de profil :</strong> âge, sexe, taille, poids, objectifs de santé</li>
                            <li><strong>Données d'activité :</strong> repas consignés, exercices effectués, objectifs</li>
                            <li><strong>Données techniques :</strong> adresse IP, type de navigateur, système d'exploitation</li>
                            <li><strong>Données d'utilisation :</strong> pages visitées, fonctionnalités utilisées</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">3. Finalités du traitement</h2>
                        <p>Vos données sont utilisées pour :</p>
                        <ul>
                            <li>Créer et gérer votre compte utilisateur</li>
                            <li>Fournir les services de suivi nutritionnel et fitness</li>
                            <li>Personnaliser votre expérience utilisateur</li>
                            <li>Analyser et améliorer nos services</li>
                            <li>Vous contacter pour le support client</li>
                            <li>Respecter nos obligations légales</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">4. Base légale du traitement</h2>
                        <p>Le traitement de vos données est fondé sur :</p>
                        <ul>
                            <li><strong>L'exécution du contrat :</strong> pour fournir nos services</li>
                            <li><strong>Votre consentement :</strong> pour les traitements optionnels</li>
                            <li><strong>L'intérêt légitime :</strong> pour améliorer nos services</li>
                            <li><strong>L'obligation légale :</strong> pour respecter la réglementation</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">5. Destinataires des données</h2>
                        <p>Vos données peuvent être partagées avec :</p>
                        <ul>
                            <li>Notre équipe interne pour le support client</li>
                            <li>Nos prestataires techniques (hébergement, analyse)</li>
                            <li>Les autorités compétentes si requis par la loi</li>
                        </ul>
                        <p>Nous ne vendons jamais vos données personnelles à des tiers.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">6. Conservation des données</h2>
                        <p>Nous conservons vos données :</p>
                        <ul>
                            <li><strong>Données de compte :</strong> pendant la durée de votre inscription + 3 ans</li>
                            <li><strong>Données d'activité :</strong> pendant la durée de votre inscription</li>
                            <li><strong>Données de connexion :</strong> 12 mois</li>
                            <li><strong>Données de facturation :</strong> 10 ans (obligation légale)</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">7. Vos droits</h2>
                        <p>Conformément au RGPD, vous disposez des droits suivants :</p>
                        <ul>
                            <li><strong>Droit d'accès :</strong> connaître les données que nous détenons sur vous</li>
                            <li><strong>Droit de rectification :</strong> corriger des données inexactes</li>
                            <li><strong>Droit d'effacement :</strong> supprimer vos données</li>
                            <li><strong>Droit à la portabilité :</strong> récupérer vos données</li>
                            <li><strong>Droit d'opposition :</strong> vous opposer au traitement</li>
                            <li><strong>Droit de limitation :</strong> limiter le traitement</li>
                        </ul>
                        <p>Pour exercer ces droits, contactez-nous à <a href="mailto:privacy@myfity.com">privacy@myfity.com</a></p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">8. Sécurité des données</h2>
                        <p>Nous mettons en place des mesures de sécurité appropriées pour protéger vos données :</p>
                        <ul>
                            <li>Chiffrement des données sensibles</li>
                            <li>Accès restreint aux données personnelles</li>
                            <li>Surveillance continue de nos systèmes</li>
                            <li>Formation de notre personnel</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">9. Cookies</h2>
                        <p>Notre site utilise des cookies pour :</p>
                        <ul>
                            <li>Mémoriser vos préférences</li>
                            <li>Analyser l'utilisation du site</li>
                            <li>Améliorer nos services</li>
                        </ul>
                        <p>Vous pouvez configurer votre navigateur pour refuser les cookies.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">10. Transferts internationaux</h2>
                        <p>Vos données sont principalement traitées en France. En cas de transfert vers un pays tiers, nous nous assurons que des garanties appropriées sont en place.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">11. Modifications</h2>
                        <p>Nous pouvons mettre à jour cette politique de confidentialité. Les modifications seront publiées sur cette page avec une nouvelle date de mise à jour.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">12. Contact</h2>
                        <p>Pour toute question concernant cette politique de confidentialité :</p>
                        <p><strong>Email :</strong> <a href="mailto:privacy@myfity.com">privacy@myfity.com</a><br>
                        <strong>Adresse :</strong> Payblis SASU, 99 AVENUE ACHILLE PERETTI, 92200 NEUILLY-SUR-SEINE, France</p>
                        <p>Vous pouvez également contacter la CNIL (www.cnil.fr) si vous estimez que vos droits ne sont pas respectés.</p>
                    </div>

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