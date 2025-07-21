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
            <h1 class="display-4 fw-bold mb-4">Conditions Générales de Vente</h1>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="mb-4">
                        <h2 class="h3 mb-3">Préambule</h2>
                        <p>Les présentes conditions générales de vente s'appliquent à toutes les prestations conclues par Grow Ecom au sein de son site myfity.com et par téléphone.</p>
                        <p>Elles sont obligatoirement consultées avant la passation de toute commande.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 1 - Éditeur</h2>
                        <p><strong>Raison sociale :</strong> Grow Ecom</p>
                        <p><strong>Adresse :</strong> 195 rue Pierre et Marie Curie, 27310 Bourg Achards</p>
                        <p><strong>SIREN :</strong> 988820304</p>
                        <p><strong>Numéro de TVA intracommunautaire :</strong> FR37988820304</p>
                        <p><strong>Capital social :</strong> 100 €</p>
                        <p><strong>Forme juridique :</strong> Société par Actions Simplifiée Unipersonnelle (SASU)</p>
                        <p><strong>Email :</strong> <a href="mailto:contact@myfity.com">contact@myfity.com</a></p>
                        <p><strong>Site web :</strong> www.myfity.com</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 2 - Objet</h2>
                        <p>Les présentes CGV ont pour objet de définir les modalités et conditions de vente des services proposés par Grow Ecom sur son site myfity.com.</p>
                        <p>Les services proposés sont :</p>
                        <ul>
                            <li>Application de suivi nutritionnel et fitness</li>
                            <li>Services Premium avec fonctionnalités avancées</li>
                            <li>Services Pro avec coaching personnalisé</li>
                            <li>Support client et assistance technique</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 3 - Prix</h2>
                        <p>Les prix de nos services sont indiqués en euros toutes taxes comprises (TVA et autres taxes applicables au jour de la commande), sauf indication contraire et hors frais de traitement et d'expédition.</p>
                        <p>En cas de commande vers un pays autre que la France métropolitaine, vous êtes l'importateur du ou des produits concernés. Des droits de douane ou autres taxes locales ou droits d'importation ou taxes d'État sont susceptibles d'être exigibles. Ces droits et sommes ne relèvent pas du ressort de Grow Ecom. Ils seront à votre charge et relèvent de votre entière responsabilité, tant en termes de déclarations que de paiements aux autorités et organismes compétents de votre pays.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 4 - Commandes</h2>
                        <p>Les informations contractuelles sont présentées en langue française et feront l'objet d'une confirmation au plus tard au moment de la validation de votre commande.</p>
                        <p>Grow Ecom se réserve le droit de ne pas enregistrer un paiement, et de ne pas confirmer une commande pour quelque raison que ce soit, et plus particulièrement en cas de problème d'approvisionnement, ou en cas de difficulté concernant l'ordre reçu.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 5 - Validation de votre commande</h2>
                        <p>Toute commande figurant sur le site suppose l'adhésion aux présentes Conditions Générales. Toute confirmation de commande entraîne votre adhésion pleine et entière aux présentes conditions, sans aucune réserve.</p>
                        <p>Tous les renseignements fournis par l'acheteur lors de la passation de sa commande engagent celui-ci : en cas d'erreur dans l'adresse de livraison, Grow Ecom ne saurait être tenu responsable de l'impossibilité dans laquelle elle pourrait se trouver de livrer le produit.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 6 - Paiement</h2>
                        <p>Le fait de valider votre commande implique pour vous l'obligation de payer le prix indiqué. Le règlement de vos achats peut s'effectuer selon les moyens de paiement indiqués au moment de la commande.</p>
                        <p>Le fait de valider votre commande implique pour vous l'obligation de payer le prix indiqué. Le règlement de vos achats peut s'effectuer selon les moyens de paiement indiqués au moment de la commande.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 7 - Rétractation</h2>
                        <p>Conformément aux dispositions légales en vigueur, vous disposez d'un délai de 14 jours à compter de la réception de vos produits pour exercer votre droit de rétractation sans avoir à justifier de motifs ni à payer de pénalité.</p>
                        <p>Les retours sont à effectuer dans leur état d'origine et complets (emballage, accessoires, notice). Dans ce cadre, votre responsabilité est engagée. Tout dommage subi par le produit à cette occasion peut être de nature à faire échec au droit de rétractation.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 8 - Disponibilité</h2>
                        <p>Nos produits sont proposés tant qu'ils sont visibles sur le site et dans la limite des stocks disponibles. En cas d'indisponibilité de produit après passation de votre commande, nous vous en informerons par mail. L'annulation de votre commande et son remboursement s'ensuivront alors automatiquement.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 9 - Livraison</h2>
                        <p>Les produits sont livrés à l'adresse de livraison indiquée au cours du processus de commande, dans le délai indiqué sur la page de validation de la commande.</p>
                        <p>En cas de retard d'exécution, et dès lors que ce retard excède 3 jours ouvrables à compter de la date indiquée lors de la validation de votre commande, vous pourrez procéder à l'annulation de votre commande et obtenir le remboursement de votre achat.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 10 - Garantie</h2>
                        <p>Tous nos produits bénéficient de la garantie légale de conformité et de la garantie des vices cachés, prévues par les articles 1641 et suivants du Code civil. En cas de non-conformité d'un produit vendu, il pourra être retourné, échangé ou remboursé.</p>
                        <p>Toutes les réclamations, demandes d'échange ou de remboursement doivent s'effectuer par email dans les 30 jours de la livraison.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 11 - Responsabilité</h2>
                        <p>Les produits proposés sont conformes à la législation française en vigueur. La responsabilité de Grow Ecom ne saurait être engagée en cas de non-respect de la législation du pays où le produit est livré. Il vous appartient de vérifier auprès des autorités locales les possibilités d'importation ou d'utilisation des produits ou services que vous envisagez de commander.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 12 - Droit applicable en cas de litiges</h2>
                        <p>La langue du présent contrat est la langue française. Les présentes conditions de vente sont soumises à la loi française. En cas de litige, les tribunaux français seront seuls compétents.</p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 mb-3">Article 13 - Propriété intellectuelle</h2>
                        <p>Tous les éléments du site myfity.com sont et restent la propriété intellectuelle et exclusive de Grow Ecom. Nul n'est autorisé à reproduire, exploiter, rediffuser, ou utiliser à quelque titre que ce soit, même partiellement, des éléments du site.</p>
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