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
    <section class="hero text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 mb-4">Une bonne santé, c'est d'abord une bonne alimentation.</h1>
                    <p class="lead mb-4">Vous voulez faire plus attention à ce que vous mangez ? Faites un suivi de vos repas, apprenez-en plus sur vos habitudes et atteignez vos objectifs avec MyFity.</p>
                    <a href="register.php" class="btn btn-light btn-lg">Démarrez gratuitement</a>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="assets/images/hero-image.png" alt="MyFity App" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5">Les outils pour vos objectifs</h2>
                <p class="lead text-muted">Tout ce dont vous avez besoin pour atteindre vos objectifs de santé et de fitness.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-utensils fa-3x text-primary mb-3"></i>
                            <h3 class="h4 mb-3">Suivi alimentaire simplifié</h3>
                            <p class="text-muted">Consignez ce que vous mangez et analysez vos habitudes alimentaires.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-dumbbell fa-3x text-primary mb-3"></i>
                            <h3 class="h4 mb-3">Suivi des exercices</h3>
                            <p class="text-muted">Enregistrez vos séances d'entraînement et suivez vos progrès.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-weight fa-3x text-primary mb-3"></i>
                            <h3 class="h4 mb-3">Suivi du poids</h3>
                            <p class="text-muted">Suivez votre progression avec des graphiques détaillés.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5">Comment ça marche ?</h2>
                <p class="lead text-muted">Trois étapes simples pour commencer votre voyage vers une meilleure santé.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="circle-step mb-3">1</div>
                        <h3 class="h4">Créez votre compte</h3>
                        <p class="text-muted">Inscrivez-vous gratuitement et définissez vos objectifs personnels.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="circle-step mb-3">2</div>
                        <h3 class="h4">Suivez vos activités</h3>
                        <p class="text-muted">Enregistrez vos repas, exercices et votre poids quotidiennement.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="circle-step mb-3">3</div>
                        <h3 class="h4">Atteignez vos objectifs</h3>
                        <p class="text-muted">Visualisez vos progrès et restez motivé.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Success Stories Section -->
    <section class="success-stories py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5">Histoires de réussites</h2>
                <p class="lead text-muted">Découvrez comment MyFity aide nos membres à atteindre leurs objectifs.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="mb-3">
                                <img src="assets/images/testimonial-1.jpg" alt="Lori" class="rounded-circle testimonial-img">
                            </div>
                            <p class="mb-3">"Maintenant, quand mes amis se mettent à la course à pied et ressentent de la frustration, je leur dis de s'accrocher car ils finiront par aller plus vite."</p>
                            <p class="text-primary mb-0">- Lori</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="mb-3">
                                <img src="assets/images/testimonial-2.jpg" alt="Stéphanie" class="rounded-circle testimonial-img">
                            </div>
                            <p class="mb-3">"MyFity m'a permis de comprendre mes habitudes alimentaires et de faire les ajustements nécessaires pour atteindre mes objectifs."</p>
                            <p class="text-primary mb-0">- Stéphanie</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="mb-3">
                                <img src="assets/images/testimonial-3.jpg" alt="Eric" class="rounded-circle testimonial-img">
                            </div>
                            <p class="mb-3">"J'ai adopté naturellement le concept d'alimentation consciente... il faut 15 à 20 minutes aux aliments pour atteindre l'estomac, j'ai donc commencé à manger plus lentement."</p>
                            <p class="text-primary mb-0">- Eric</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta py-5 text-white">
        <div class="container text-center">
            <h2 class="display-5 mb-4">Prêt à commencer votre voyage ?</h2>
            <p class="lead mb-4">Rejoignez des milliers d'utilisateurs qui ont déjà transformé leur vie avec MyFity.</p>
            <a href="register.php" class="btn btn-light btn-lg">Démarrer gratuitement</a>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
