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
            <h1 class="display-4 fw-bold mb-4">Nous contacter</h1>
            <p class="lead text-muted">Une question, une suggestion ? Notre équipe est à votre écoute !</p>
        </div>
        <div class="row justify-content-center mb-5">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <form>
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" placeholder="Votre nom">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" placeholder="Votre email">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="5" placeholder="Votre message"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Envoyer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="h4 mb-4">Informations de contact</h3>
                        
                        <div class="mb-3">
                            <h5 class="fw-bold text-primary">Adresse</h5>
                            <p class="mb-0">Payblis SASU<br>
                            99 AVENUE ACHILLE PERETTI<br>
                            92200 NEUILLY-SUR-SEINE, France</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5 class="fw-bold text-primary">Téléphone</h5>
                            <p class="mb-0">+33 1 XX XX XX XX</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5 class="fw-bold text-primary">Email</h5>
                            <p class="mb-0"><a href="mailto:contact@myfity.com">contact@myfity.com</a></p>
                        </div>
                        
                        <div class="mb-3">
                            <h5 class="fw-bold text-primary">Heures d'ouverture</h5>
                            <p class="mb-0">Lun - Ven: 9h00 - 18h00<br>
                            Sam: 10h00 - 16h00</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5 class="fw-bold text-primary">Informations légales</h5>
                            <p class="mb-0">SIREN: 950843516<br>
                            TVA: FR53950843516<br>
                            Capital: 1 000,00 €</p>
                        </div>
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