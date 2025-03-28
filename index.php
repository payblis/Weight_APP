<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weight Tracker - Suivez votre poids et restez en forme</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-weight me-2"></i>Weight Tracker
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Inscription</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- En-tête -->
    <header class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold">Suivez votre poids et atteignez vos objectifs</h1>
                    <p class="lead">Weight Tracker est une application gratuite qui vous aide à suivre votre poids, planifier vos repas et exercices, et atteindre vos objectifs de santé.</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="register.php" class="btn btn-primary btn-lg px-4 me-md-2">Commencer gratuitement</a>
                        <a href="#features" class="btn btn-outline-secondary btn-lg px-4">En savoir plus</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/hero-image.jpg" alt="Weight Tracker App" class="img-fluid rounded shadow-lg">
                </div>
            </div>
        </div>
    </header>

    <!-- Fonctionnalités -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Fonctionnalités principales</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-weight fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">Suivi de poids</h3>
                            <p class="card-text">Enregistrez votre poids quotidiennement et visualisez vos progrès avec des graphiques détaillés.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-utensils fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">Journal alimentaire</h3>
                            <p class="card-text">Suivez vos repas et calculez automatiquement les calories et macronutriments consommés.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-running fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">Suivi d'exercices</h3>
                            <p class="card-text">Enregistrez vos activités physiques et calculez les calories brûlées pour chaque exercice.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-bullseye fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">Objectifs personnalisés</h3>
                            <p class="card-text">Définissez vos objectifs de poids et recevez un plan personnalisé pour les atteindre.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">Plans de repas</h3>
                            <p class="card-text">Obtenez des suggestions de repas adaptées à vos objectifs caloriques et préférences alimentaires.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-brain fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">IA intégrée</h3>
                            <p class="card-text">Bénéficiez de recommandations personnalisées grâce à notre intelligence artificielle intégrée.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Témoignages -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Ce que disent nos utilisateurs</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="assets/images/testimonial-1.jpg" alt="Utilisateur" class="rounded-circle me-3" width="60">
                                <div>
                                    <h5 class="mb-0">Sophie Martin</h5>
                                    <small class="text-muted">A perdu 15kg en 6 mois</small>
                                </div>
                            </div>
                            <p class="card-text">"Weight Tracker m'a aidée à rester motivée tout au long de mon parcours de perte de poids. Les graphiques de progression et les plans de repas personnalisés ont fait toute la différence."</p>
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="assets/images/testimonial-2.jpg" alt="Utilisateur" class="rounded-circle me-3" width="60">
                                <div>
                                    <h5 class="mb-0">Thomas Dubois</h5>
                                    <small class="text-muted">Utilise l'app depuis 1 an</small>
                                </div>
                            </div>
                            <p class="card-text">"Le suivi des exercices et le calcul automatique des calories brûlées m'ont permis d'optimiser mes entraînements. Je n'ai jamais été aussi en forme !"</p>
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="assets/images/testimonial-3.jpg" alt="Utilisateur" class="rounded-circle me-3" width="60">
                                <div>
                                    <h5 class="mb-0">Julie Moreau</h5>
                                    <small class="text-muted">A atteint son objectif en 3 mois</small>
                                </div>
                            </div>
                            <p class="card-text">"Les recommandations personnalisées de l'IA sont incroyablement précises. J'ai pu adapter mon alimentation et mes exercices pour des résultats optimaux."</p>
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Appel à l'action -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="mb-4">Prêt à commencer votre parcours vers une vie plus saine ?</h2>
            <p class="lead mb-4">Inscrivez-vous gratuitement et commencez à suivre votre progression dès aujourd'hui.</p>
            <a href="register.php" class="btn btn-light btn-lg px-4">S'inscrire gratuitement</a>
        </div>
    </section>

    <!-- Pied de page -->
    <footer class="py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>Weight Tracker</h5>
                    <p>Votre compagnon pour atteindre vos objectifs de santé et de forme physique.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Liens rapides</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Accueil</a></li>
                        <li><a href="login.php" class="text-white">Connexion</a></li>
                        <li><a href="register.php" class="text-white">Inscription</a></li>
                        <li><a href="#features" class="text-white">Fonctionnalités</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Nous contacter</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> contact@weight-tracker.com</li>
                        <li><i class="fas fa-phone me-2"></i> +33 1 23 45 67 89</li>
                    </ul>
                    <div class="mt-3">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Weight Tracker. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
