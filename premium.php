<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/translation.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium - MyFity</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <main class="py-5">
            <div class="container">
        <!-- Hero Section -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-4">MyFity Premium</h1>
            <p class="lead text-muted">Débloquez tout le potentiel de votre parcours santé avec nos fonctionnalités avancées</p>
        </div>

        <!-- Features Comparison -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th class="border-0">Fonctionnalités</th>
                                        <th class="border-0 text-center">Gratuit</th>
                                        <th class="border-0 text-center bg-warning">Premium</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><i class="fas fa-utensils me-2 text-primary"></i>Suivi alimentaire de base</td>
                                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                                        <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-chart-line me-2 text-primary"></i>Rapports nutritionnels</td>
                                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                                        <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-barcode me-2 text-primary"></i>Scanner de codes-barres</td>
                                        <td class="text-center"><i class="fas fa-times text-muted"></i></td>
                                        <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-camera me-2 text-primary"></i>Reconnaissance d'images</td>
                                        <td class="text-center"><i class="fas fa-times text-muted"></i></td>
                                        <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-chart-pie me-2 text-primary"></i>Analyses détaillées</td>
                                        <td class="text-center"><i class="fas fa-times text-muted"></i></td>
                                        <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-dumbbell me-2 text-primary"></i>Programmes d'entraînement</td>
                                        <td class="text-center"><i class="fas fa-times text-muted"></i></td>
                                        <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-user-md me-2 text-primary"></i>Coaching personnalisé</td>
                                        <td class="text-center"><i class="fas fa-times text-muted"></i></td>
                                        <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-users me-2 text-primary"></i>Communauté exclusive</td>
                                        <td class="text-center"><i class="fas fa-times text-muted"></i></td>
                                        <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-download me-2 text-primary"></i>Export de données</td>
                                        <td class="text-center"><i class="fas fa-times text-muted"></i></td>
                                        <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-ad me-2 text-primary"></i>Sans publicités</td>
                                        <td class="text-center"><i class="fas fa-times text-muted"></i></td>
                                        <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Plans -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <h3 class="h4 fw-bold mb-3">Mensuel</h3>
                        <div class="mb-4">
                            <span class="display-4 fw-bold text-primary">9,99€</span>
                            <span class="text-muted">/mois</span>
                        </div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Toutes les fonctionnalités Premium</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Support prioritaire</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Annulation à tout moment</li>
                        </ul>
                        <a href="premium-subscribe.php?plan=mensuel" class="btn btn-outline-primary w-100">Choisir ce plan</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm border-primary">
                    <div class="card-body text-center p-4">
                        <div class="badge bg-primary mb-3">Le plus populaire</div>
                        <h3 class="h4 fw-bold mb-3">Annuel</h3>
                        <div class="mb-4">
                            <span class="display-4 fw-bold text-primary">59,99€</span>
                            <span class="text-muted">/an</span>
                        </div>
                        <div class="badge bg-success mb-3">Économisez 40%</div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Toutes les fonctionnalités Premium</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Support prioritaire</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Contenu exclusif</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Garantie satisfait ou remboursé</li>
                        </ul>
                        <a href="premium-subscribe.php?plan=annuel" class="btn btn-primary w-100">Choisir ce plan</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <h3 class="h4 fw-bold mb-3">Famille</h3>
                        <div class="mb-4">
                            <span class="display-4 fw-bold text-primary">99,99€</span>
                            <span class="text-muted">/an</span>
                        </div>
                        <div class="badge bg-info mb-3">Jusqu'à 6 personnes</div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Toutes les fonctionnalités Premium</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Profils séparés</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Suivi familial</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Support dédié</li>
                        </ul>
                        <a href="premium-subscribe.php?plan=famille" class="btn btn-outline-primary w-100">Choisir ce plan</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Premium Features Details -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-camera fa-2x text-primary me-3"></i>
                            <h3 class="h5 fw-bold mb-0">Reconnaissance d'Images</h3>
                        </div>
                        <p class="text-muted">Prenez simplement une photo de votre repas et notre IA l'identifiera automatiquement. Plus besoin de chercher manuellement dans la base de données.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-user-md fa-2x text-primary me-3"></i>
                            <h3 class="h5 fw-bold mb-0">Coaching Personnalisé</h3>
                        </div>
                        <p class="text-muted">Recevez des conseils personnalisés de nos experts en nutrition et fitness. Plans d'entraînement adaptés à vos objectifs et votre niveau.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-chart-pie fa-2x text-primary me-3"></i>
                            <h3 class="h5 fw-bold mb-0">Analyses Avancées</h3>
                        </div>
                        <p class="text-muted">Graphiques détaillés, tendances sur le long terme, analyses de macronutriments et micronutriments. Comprenez mieux votre alimentation.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-users fa-2x text-primary me-3"></i>
                            <h3 class="h5 fw-bold mb-0">Communauté Exclusive</h3>
                        </div>
                        <p class="text-muted">Accédez à des groupes privés, des défis exclusifs et connectez-vous avec d'autres membres Premium partageant vos objectifs.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Testimonials -->
        <div class="bg-light rounded p-5 mb-5">
            <h3 class="text-center fw-bold mb-4">Ce que disent nos membres Premium</h3>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="text-muted">"La reconnaissance d'images a révolutionné ma façon de suivre mes repas. C'est incroyablement pratique !"</p>
                        <strong>Marie L.</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="text-muted">"Le coaching personnalisé m'a aidé à perdre 15kg en 6 mois. Les conseils sont vraiment adaptés à mes besoins."</p>
                        <strong>Thomas R.</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="text-muted">"Les analyses détaillées m'ont permis de comprendre pourquoi je stagnais. Maintenant je progresse régulièrement !"</p>
                        <strong>Sophie M.</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-primary text-white rounded p-5 text-center">
            <h2 class="fw-bold mb-3">Prêt à passer au niveau supérieur ?</h2>
            <p class="lead mb-4">Rejoignez des milliers d'utilisateurs qui ont déjà transformé leur vie avec MyFity Premium</p>
            <a href="register.php" class="btn btn-light btn-lg">Commencer l'essai gratuit</a>
            <p class="small mt-3">Essai gratuit de 7 jours • Annulation à tout moment</p>
        </div>
            </main>
        </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 