<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/translation.php';

// Démarrer la capture de sortie
ob_start();

include 'header.php';
?>

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <h1 class="display-4 fw-bold text-center mb-5">Test de Traduction</h1>
                        
                        <div class="text-center mb-4">
                            <p class="lead">Cette page teste le système de traduction automatique.</p>
                            <p>Langue actuelle : <strong><?php echo isset($_GET['lang']) ? $_GET['lang'] : 'fr'; ?></strong></p>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-utensils fa-3x text-primary mb-3"></i>
                                        <h3 class="h5 fw-bold mb-3">Suivi Alimentaire</h3>
                                        <p class="text-muted">Consignez vos repas et suivez vos calories quotidiennes avec précision.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-dumbbell fa-3x text-success mb-3"></i>
                                        <h3 class="h5 fw-bold mb-3">Exercices Physiques</h3>
                                        <p class="text-muted">Enregistrez vos séances d'entraînement et suivez vos progrès.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-5">
                            <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
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
$translator = new TranslationManager();
$translatedContent = $translator->translatePage($content);

// Afficher le contenu traduit
echo $translatedContent;
?> 