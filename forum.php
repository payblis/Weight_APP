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
            <h1 class="display-4 fw-bold mb-4">Forum MyFity</h1>
            <p class="lead text-muted">Rejoignez notre communauté de passionnés de santé et fitness</p>
        </div>

        <!-- Forum Categories -->
        <div class="row g-4 mb-5">
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-utensils fa-2x text-primary me-3"></i>
                            <div>
                                <h3 class="h5 fw-bold mb-1">Nutrition</h3>
                                <small class="text-muted">1,234 sujets • 5,678 messages</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Discussions sur l'alimentation, les régimes, les recettes et conseils nutritionnels.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Dernier message il y a 2h</small>
                            <a href="#" class="btn btn-sm btn-outline-primary">Voir les discussions</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-dumbbell fa-2x text-success me-3"></i>
                            <div>
                                <h3 class="h5 fw-bold mb-1">Fitness & Entraînement</h3>
                                <small class="text-muted">2,345 sujets • 8,901 messages</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Partagez vos routines d'entraînement, conseils d'exercices et programmes fitness.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Dernier message il y a 30min</small>
                            <a href="#" class="btn btn-sm btn-outline-success">Voir les discussions</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-heart fa-2x text-danger me-3"></i>
                            <div>
                                <h3 class="h5 fw-bold mb-1">Motivation & Soutien</h3>
                                <small class="text-muted">3,456 sujets • 12,345 messages</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Trouvez de la motivation, partagez vos succès et soutenez les autres membres.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Dernier message il y a 1h</small>
                            <a href="#" class="btn btn-sm btn-outline-danger">Voir les discussions</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-chart-line fa-2x text-info me-3"></i>
                            <div>
                                <h3 class="h5 fw-bold mb-1">Progrès & Objectifs</h3>
                                <small class="text-muted">1,567 sujets • 4,567 messages</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Partagez vos progrès, objectifs et conseils pour atteindre vos buts.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Dernier message il y a 3h</small>
                            <a href="#" class="btn btn-sm btn-outline-info">Voir les discussions</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-question-circle fa-2x text-warning me-3"></i>
                            <div>
                                <h3 class="h5 fw-bold mb-1">Questions & Aide</h3>
                                <small class="text-muted">2,789 sujets • 6,789 messages</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Posez vos questions et obtenez de l'aide de la communauté MyFity.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Dernier message il y a 15min</small>
                            <a href="#" class="btn btn-sm btn-outline-warning">Voir les discussions</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-users fa-2x text-secondary me-3"></i>
                            <div>
                                <h3 class="h5 fw-bold mb-1">Rencontres & Événements</h3>
                                <small class="text-muted">456 sujets • 1,234 messages</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Organisez des rencontres, événements et défis communautaires.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Dernier message il y a 1j</small>
                            <a href="#" class="btn btn-sm btn-outline-secondary">Voir les discussions</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Discussions -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="fw-bold mb-4">Discussions Récentes</h2>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center p-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Conseils pour perdre du poids sainement</h5>
                                        <small class="text-muted">Par Marie L. • il y a 2 heures • Nutrition</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="badge bg-primary mb-1">15 réponses</div>
                                    <br>
                                    <small class="text-muted">1.2k vues</small>
                                </div>
                            </div>

                            <div class="list-group-item d-flex justify-content-between align-items-center p-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Routine d'entraînement pour débutants</h5>
                                        <small class="text-muted">Par Thomas R. • il y a 4 heures • Fitness</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="badge bg-success mb-1">23 réponses</div>
                                    <br>
                                    <small class="text-muted">2.1k vues</small>
                                </div>
                            </div>

                            <div class="list-group-item d-flex justify-content-between align-items-center p-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">J'ai perdu 20kg en 6 mois !</h5>
                                        <small class="text-muted">Par Sophie M. • il y a 6 heures • Motivation</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="badge bg-danger mb-1">45 réponses</div>
                                    <br>
                                    <small class="text-muted">3.5k vues</small>
                                </div>
                            </div>

                            <div class="list-group-item d-flex justify-content-between align-items-center p-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Comment calculer ses macros ?</h5>
                                        <small class="text-muted">Par Pierre D. • il y a 8 heures • Questions</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="badge bg-info mb-1">12 réponses</div>
                                    <br>
                                    <small class="text-muted">890 vues</small>
                                </div>
                            </div>

                            <div class="list-group-item d-flex justify-content-between align-items-center p-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Recettes protéinées pour le petit-déjeuner</h5>
                                        <small class="text-muted">Par Julie B. • il y a 12 heures • Nutrition</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="badge bg-warning mb-1">8 réponses</div>
                                    <br>
                                    <small class="text-muted">1.7k vues</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Community Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h3 class="h2 fw-bold text-primary">50,000+</h3>
                        <p class="text-muted mb-0">Membres actifs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <i class="fas fa-comments fa-3x text-success mb-3"></i>
                        <h3 class="h2 fw-bold text-success">100,000+</h3>
                        <p class="text-muted mb-0">Messages</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <i class="fas fa-lightbulb fa-3x text-warning mb-3"></i>
                        <h3 class="h2 fw-bold text-warning">15,000+</h3>
                        <p class="text-muted mb-0">Sujets créés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <i class="fas fa-clock fa-3x text-info mb-3"></i>
                        <h3 class="h2 fw-bold text-info">24/7</h3>
                        <p class="text-muted mb-0">Support actif</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Join Community -->
        <div class="bg-primary text-white rounded p-5 text-center">
            <h2 class="fw-bold mb-3">Rejoignez la communauté !</h2>
            <p class="lead mb-4">Connectez-vous avec des milliers de personnes partageant vos objectifs de santé</p>
            <a href="register.php" class="btn btn-light btn-lg">Créer un compte gratuit</a>
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