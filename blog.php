<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

include 'header.php';
?>

<main class="py-5">
    <div class="container">
        <!-- Hero Section -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-4">Blog MyFity</h1>
            <p class="lead text-muted">Conseils, astuces et actualités pour votre santé et votre bien-être</p>
        </div>

        <!-- Featured Article -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <img src="https://images.unsplash.com/photo-1490645935967-10de6ba17061?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" class="img-fluid rounded-start h-100" style="object-fit: cover;" alt="Nutrition équilibrée">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body p-4">
                                <span class="badge bg-primary mb-2">À la une</span>
                                <h2 class="card-title h3 fw-bold">10 Conseils pour une Nutrition Équilibrée en 2024</h2>
                                <p class="card-text text-muted">Découvrez les meilleures pratiques pour maintenir une alimentation saine et équilibrée tout au long de l'année. Des conseils pratiques et scientifiques pour optimiser votre santé.</p>
                                <div class="d-flex align-items-center text-muted small mb-3">
                                    <i class="far fa-calendar me-2"></i>
                                    <span>15 janvier 2024</span>
                                    <i class="far fa-clock ms-3 me-2"></i>
                                    <span>5 min de lecture</span>
                                </div>
                                <a href="#" class="btn btn-outline-primary">Lire l'article</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Articles Grid -->
        <div class="row g-4 mb-5">
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Entraînement">
                    <div class="card-body">
                        <span class="badge bg-success mb-2">Fitness</span>
                        <h3 class="card-title h5 fw-bold">Les Meilleurs Exercices pour Perdre du Poids</h3>
                        <p class="card-text text-muted">Guide complet des exercices les plus efficaces pour brûler des calories et perdre du poids de manière saine et durable.</p>
                        <div class="d-flex align-items-center text-muted small mb-3">
                            <i class="far fa-calendar me-2"></i>
                            <span>12 janvier 2024</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary">Lire plus</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Superaliments">
                    <div class="card-body">
                        <span class="badge bg-warning mb-2">Nutrition</span>
                        <h3 class="card-title h5 fw-bold">Les Superaliments à Intégrer dans Votre Régime</h3>
                        <p class="card-text text-muted">Découvrez les aliments riches en nutriments essentiels qui peuvent transformer votre santé et votre énergie.</p>
                        <div class="d-flex align-items-center text-muted small mb-3">
                            <i class="far fa-calendar me-2"></i>
                            <span>10 janvier 2024</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary">Lire plus</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Méditation">
                    <div class="card-body">
                        <span class="badge bg-info mb-2">Bien-être</span>
                        <h3 class="card-title h5 fw-bold">Méditation et Perte de Poids : Le Lien</h3>
                        <p class="card-text text-muted">Comment la méditation peut vous aider à contrôler votre poids en réduisant le stress et les fringales émotionnelles.</p>
                        <div class="d-flex align-items-center text-muted small mb-3">
                            <i class="far fa-calendar me-2"></i>
                            <span>8 janvier 2024</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary">Lire plus</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Recettes">
                    <div class="card-body">
                        <span class="badge bg-danger mb-2">Recettes</span>
                        <h3 class="card-title h5 fw-bold">15 Recettes Faibles en Calories</h3>
                        <p class="card-text text-muted">Des recettes délicieuses et nutritives qui vous aideront à maintenir votre apport calorique sans sacrifier le goût.</p>
                        <div class="d-flex align-items-center text-muted small mb-3">
                            <i class="far fa-calendar me-2"></i>
                            <span>5 janvier 2024</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary">Lire plus</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Motivation">
                    <div class="card-body">
                        <span class="badge bg-secondary mb-2">Motivation</span>
                        <h3 class="card-title h5 fw-bold">Comment Rester Motivé dans Votre Parcours</h3>
                        <p class="card-text text-muted">Stratégies éprouvées pour maintenir votre motivation et atteindre vos objectifs de santé à long terme.</p>
                        <div class="d-flex align-items-center text-muted small mb-3">
                            <i class="far fa-calendar me-2"></i>
                            <span>3 janvier 2024</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary">Lire plus</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Science">
                    <div class="card-body">
                        <span class="badge bg-dark mb-2">Science</span>
                        <h3 class="card-title h5 fw-bold">La Science derrière la Perte de Poids</h3>
                        <p class="card-text text-muted">Comprendre les mécanismes biologiques qui régissent la perte de poids et comment les optimiser.</p>
                        <div class="d-flex align-items-center text-muted small mb-3">
                            <i class="far fa-calendar me-2"></i>
                            <span>1 janvier 2024</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary">Lire plus</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Newsletter -->
        <div class="bg-light rounded p-5 text-center">
            <h3 class="fw-bold mb-3">Restez informé !</h3>
            <p class="text-muted mb-4">Recevez nos derniers articles et conseils directement dans votre boîte mail</p>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Votre adresse email">
                        <button class="btn btn-primary" type="button">S'abonner</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?> 