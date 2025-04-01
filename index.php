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

include 'header.php';
?>

<main>
    <!-- Hero Section -->
    <section class="hero bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Une bonne santé, c'est d'abord une bonne alimentation.</h1>
                    <p class="lead mb-4">Vous voulez faire plus attention à ce que vous mangez ? Faites un suivi de vos repas, apprenez-en plus sur vos habitudes et atteignez vos objectifs avec MyFity.</p>
                    <a href="register.php" class="btn btn-light btn-lg">Démarrez gratuitement</a>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/hero.png" alt="MyFity App" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Food Tracking Section -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">Consignez ce que vous mangez grâce aux plus de 14 millions d'aliments.</h2>
                    <p class="lead text-muted mb-4">Consultez l'analyse des calories et des nutriments, comparez les portions et découvrez comment les aliments que vous consommez soutiennent vos objectifs.</p>
                    <a href="food-log.php" class="btn btn-primary btn-lg">Commencer à suivre</a>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/consign.png" alt="Suivi alimentaire" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Tools Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center display-5 fw-bold mb-5">Les outils pour vos objectifs</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                            <h3 class="h4 mb-3">Apprendre. Suivre. Progresser.</h3>
                            <p class="text-muted">Tenir un journal alimentaire vous permet de mieux comprendre vos habitudes et accroît vos chances d'atteindre vos objectifs.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-barcode fa-3x text-primary mb-3"></i>
                            <h3 class="h4 mb-3">Consigner plus facilement.</h3>
                            <p class="text-muted">Numérisez des codes-barres, enregistrez des repas et recettes, et utilisez Outils rapide pour un suivi alimentaire facile et rapide.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h3 class="h4 mb-3">Garder la motivation.</h3>
                            <p class="text-muted">Rejoignez la plus grande communauté de fitness au monde pour profiter de conseils et astuces, ainsi que d'une assistance 24/7.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">DÉBUTEZ VOTRE VOYAGE DÈS AUJOURD'HUI</h2>
            <p class="lead mb-4">Rejoignez des milliers d'utilisateurs qui ont déjà transformé leur vie avec MyFity.</p>
            <a href="register.php" class="btn btn-light btn-lg">Démarrer gratuitement</a>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
