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
        <!-- Hero Section -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-4">Guide Communautaire</h1>
            <p class="lead text-muted">Ensemble, créons une communauté bienveillante et respectueuse</p>
        </div>

        <!-- Community Values -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body p-4">
                        <i class="fas fa-heart fa-3x text-danger mb-3"></i>
                        <h3 class="h5 fw-bold mb-3">Bienveillance</h3>
                        <p class="text-muted">Soyez gentil et encourageant envers tous les membres, peu importe leur niveau ou leurs objectifs.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body p-4">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h3 class="h5 fw-bold mb-3">Respect</h3>
                        <p class="text-muted">Traitez chaque membre avec respect et dignité, même en cas de désaccord.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body p-4">
                        <i class="fas fa-lightbulb fa-3x text-warning mb-3"></i>
                        <h3 class="h5 fw-bold mb-3">Partage</h3>
                        <p class="text-muted">Partagez vos expériences, conseils et connaissances pour aider les autres.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Community Rules -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <h2 class="fw-bold mb-4">Règles de la Communauté</h2>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h4 class="h5 fw-bold text-success mb-2">
                                        <i class="fas fa-check-circle me-2"></i>Ce qui est encouragé
                                    </h4>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-arrow-right text-success me-2"></i>Partager vos succès et progrès</li>
                                        <li class="mb-2"><i class="fas fa-arrow-right text-success me-2"></i>Demander de l'aide et des conseils</li>
                                        <li class="mb-2"><i class="fas fa-arrow-right text-success me-2"></i>Partager des recettes et astuces</li>
                                        <li class="mb-2"><i class="fas fa-arrow-right text-success me-2"></i>Encourager et motiver les autres</li>
                                        <li class="mb-2"><i class="fas fa-arrow-right text-success me-2"></i>Partager des ressources utiles</li>
                                        <li class="mb-2"><i class="fas fa-arrow-right text-success me-2"></i>Organiser des défis communautaires</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h4 class="h5 fw-bold text-danger mb-2">
                                        <i class="fas fa-times-circle me-2"></i>Ce qui est interdit
                                    </h4>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-arrow-right text-danger me-2"></i>Harcèlement ou intimidation</li>
                                        <li class="mb-2"><i class="fas fa-arrow-right text-danger me-2"></i>Discours haineux ou discriminatoire</li>
                                        <li class="mb-2"><i class="fas fa-arrow-right text-danger me-2"></i>Spam ou publicité non autorisée</li>
                                        <li class="mb-2"><i class="fas fa-arrow-right text-danger me-2"></i>Partage d'informations médicales non vérifiées</li>
                                        <li class="mb-2"><i class="fas fa-arrow-right text-danger me-2"></i>Contenu inapproprié ou offensant</li>
                                        <li class="mb-2"><i class="fas fa-arrow-right text-danger me-2"></i>Usurpation d'identité</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Best Practices -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="fw-bold mb-4">Bonnes Pratiques</h2>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-search fa-2x text-primary me-3"></i>
                                    <h3 class="h5 fw-bold mb-0">Recherchez avant de poster</h3>
                                </div>
                                <p class="text-muted">Vérifiez si votre question a déjà été posée. Cela évite les doublons et vous permet de trouver des réponses plus rapidement.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-tag fa-2x text-success me-3"></i>
                                    <h3 class="h5 fw-bold mb-0">Utilisez les bons tags</h3>
                                </div>
                                <p class="text-muted">Catégorisez vos posts avec les bons tags pour faciliter la recherche et permettre aux bonnes personnes de vous répondre.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-comment fa-2x text-info me-3"></i>
                                    <h3 class="h5 fw-bold mb-0">Soyez constructif</h3>
                                </div>
                                <p class="text-muted">Lorsque vous répondez, soyez constructif et bienveillant. Évitez les critiques non constructives.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-thumbs-up fa-2x text-warning me-3"></i>
                                    <h3 class="h5 fw-bold mb-0">Reconnaissez l'aide reçue</h3>
                                </div>
                                <p class="text-muted">Si quelqu'un vous a aidé, n'oubliez pas de le remercier et de marquer sa réponse comme utile.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-share fa-2x text-danger me-3"></i>
                                    <h3 class="h5 fw-bold mb-0">Partagez vos expériences</h3>
                                </div>
                                <p class="text-muted">Vos expériences personnelles peuvent aider d'autres personnes dans des situations similaires.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-flag fa-2x text-secondary me-3"></i>
                                    <h3 class="h5 fw-bold mb-0">Signalez les abus</h3>
                                </div>
                                <p class="text-muted">Si vous voyez du contenu inapproprié, n'hésitez pas à le signaler. Cela nous aide à maintenir une communauté saine.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Moderation -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <h2 class="fw-bold mb-4">Modération</h2>
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="h5 fw-bold mb-3">Notre équipe de modération</h4>
                                <p class="text-muted mb-3">Notre équipe de modérateurs travaille 24h/24 pour maintenir une communauté saine et respectueuse. Ils peuvent :</p>
                                <ul class="text-muted">
                                    <li>Supprimer du contenu inapproprié</li>
                                    <li>Avertir les membres qui enfreignent les règles</li>
                                    <li>Suspendre temporairement ou définitivement des comptes</li>
                                    <li>Fermer des discussions qui dérapent</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h4 class="h5 fw-bold mb-3">Comment signaler un problème</h4>
                                <p class="text-muted mb-3">Si vous rencontrez un problème, vous pouvez :</p>
                                <ul class="text-muted">
                                    <li>Utiliser le bouton "Signaler" sur tout contenu</li>
                                    <li>Contacter directement notre équipe via le formulaire de contact</li>
                                    <li>Envoyer un email à moderation@myfity.com</li>
                                    <li>Utiliser le chat de support en ligne</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="fw-bold mb-4">Questions Fréquentes</h2>
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header" id="faq1">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                Puis-je partager des liens vers d'autres sites ?
                            </button>
                        </h2>
                        <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Oui, vous pouvez partager des liens vers des sites fiables et pertinents, à condition qu'ils ne soient pas commerciaux ou publicitaires. Évitez les liens vers des sites de vente ou de promotion.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header" id="faq2">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                Que faire si je reçois un message privé inapproprié ?
                            </button>
                        </h2>
                        <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Vous pouvez bloquer l'utilisateur et signaler le message à notre équipe de modération. Nous prenons très au sérieux le harcèlement et les comportements inappropriés.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header" id="faq3">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                Puis-je promouvoir mes services de coaching ?
                            </button>
                        </h2>
                        <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Non, la promotion commerciale n'est pas autorisée dans la communauté. Si vous souhaitez promouvoir vos services, contactez-nous pour discuter des options publicitaires officielles.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header" id="faq4">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
                                Comment puis-je devenir modérateur ?
                            </button>
                        </h2>
                        <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Nous recrutons nos modérateurs parmi les membres actifs et respectueux de la communauté. Si vous êtes intéressé, contactez-nous avec votre motivation et votre expérience.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact -->
        <div class="bg-light rounded p-5 text-center">
            <h3 class="fw-bold mb-3">Besoin d'aide ?</h3>
            <p class="text-muted mb-4">Notre équipe est là pour vous aider avec toute question concernant la communauté</p>
            <a href="contact.php" class="btn btn-primary btn-lg">Nous contacter</a>
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