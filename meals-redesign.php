<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack - Journal alimentaire</title>
    <link rel="stylesheet" href="css/myfitnesspal-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- En-tête -->
    <header class="header">
        <div class="container header-container">
            <a href="index.php" class="logo">FitTrack</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="dashboard.php">Tableau de bord</a></li>
                    <li><a href="weight-log.php">Poids</a></li>
                    <li><a href="meals.php" class="active">Repas</a></li>
                    <li><a href="activities.php">Activités</a></li>
                    <li><a href="profile.php">Profil</a></li>
                    <li><a href="api/logout.php">Déconnexion</a></li>
                </ul>
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Navigation mobile -->
    <nav class="mobile-nav">
        <ul class="mobile-nav-menu">
            <li>
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Accueil</span>
                </a>
            </li>
            <li>
                <a href="meals.php" class="active">
                    <i class="fas fa-utensils"></i>
                    <span>Journal</span>
                </a>
            </li>
            <li>
                <a href="weight-log.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Progrès</span>
                </a>
            </li>
            <li>
                <a href="activities.php">
                    <i class="fas fa-running"></i>
                    <span>Plans</span>
                </a>
            </li>
            <li>
                <a href="profile.php">
                    <i class="fas fa-ellipsis-h"></i>
                    <span>Plus</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Contenu principal -->
    <main class="container" style="margin-top: 80px; margin-bottom: 80px;">
        <!-- En-tête du journal -->
        <div class="card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Journal alimentaire</h2>
                    <p>Jeudi, 22 mars 2025</p>
                </div>
                <div class="d-flex">
                    <button class="btn btn-outline mr-2">
                        <i class="fas fa-calendar-alt mr-1"></i> Changer de date
                    </button>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Ajouter un aliment
                    </button>
                </div>
            </div>
        </div>

        <!-- Résumé des calories -->
        <section class="section">
            <div class="card">
                <div class="row">
                    <div class="col-4">
                        <div class="text-center">
                            <div class="progress-circle" id="calories-circle">
                                <div class="progress-circle-inner">
                                    <div class="progress-circle-value">959</div>
                                    <div class="progress-circle-label">Restantes</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="row">
                            <div class="col-3">
                                <div class="text-center">
                                    <h5>Objectif</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;">1,600</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="text-center">
                                    <h5>Nourriture</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;">641</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="text-center">
                                    <h5>Exercice</h5>
                                    <div class="text-success" style="font-size: 1.5rem;">235</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="text-center">
                                    <h5>Restant</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;">959</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h5>Macronutriments</h5>
                            <div class="row mt-2">
                                <div class="col-4">
                                    <label>Protéines</label>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 65%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>45g</span>
                                        <span class="text-gray">/ 120g</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label>Glucides</label>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 40%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>80g</span>
                                        <span class="text-gray">/ 200g</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label>Lipides</label>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 30%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>20g</span>
                                        <span class="text-gray">/ 65g</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Journal alimentaire -->
        <section class="section">
            <!-- Petit-déjeuner -->
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Petit-déjeuner</h3>
                    <div>
                        <span class="badge badge-primary">350 cal</span>
                        <button class="btn btn-sm btn-outline ml-2">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
                <div class="meal-card">
                    <div class="meal-card-icon">
                        <i class="fas fa-bread-slice"></i>
                    </div>
                    <div class="meal-card-body">
                        <div class="d-flex justify-content-between">
                            <h4>Pain complet</h4>
                            <div>
                                <span class="badge badge-primary">150 cal</span>
                            </div>
                        </div>
                        <p>2 tranches (80g)</p>
                        <div class="d-flex mt-2">
                            <div class="mr-3">
                                <small class="text-gray">Protéines</small>
                                <div>6g</div>
                            </div>
                            <div class="mr-3">
                                <small class="text-gray">Glucides</small>
                                <div>30g</div>
                            </div>
                            <div>
                                <small class="text-gray">Lipides</small>
                                <div>2g</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="meal-card">
                    <div class="meal-card-icon">
                        <i class="fas fa-egg"></i>
                    </div>
                    <div class="meal-card-body">
                        <div class="d-flex justify-content-between">
                            <h4>Œufs brouillés</h4>
                            <div>
                                <span class="badge badge-primary">200 cal</span>
                            </div>
                        </div>
                        <p>2 œufs (100g)</p>
                        <div class="d-flex mt-2">
                            <div class="mr-3">
                                <small class="text-gray">Protéines</small>
                                <div>12g</div>
                            </div>
                            <div class="mr-3">
                                <small class="text-gray">Glucides</small>
                                <div>1g</div>
                            </div>
                            <div>
                                <small class="text-gray">Lipides</small>
                                <div>14g</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Déjeuner -->
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Déjeuner</h3>
                    <div>
                        <span class="badge badge-primary">450 cal</span>
                        <button class="btn btn-sm btn-outline ml-2">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
                <div class="meal-card">
                    <div class="meal-card-icon">
                        <i class="fas fa-drumstick-bite"></i>
                    </div>
                    <div class="meal-card-body">
                        <div class="d-flex justify-content-between">
                            <h4>Poulet grillé</h4>
                            <div>
                                <span class="badge badge-primary">250 cal</span>
                            </div>
                        </div>
                        <p>150g</p>
                        <div class="d-flex mt-2">
                            <div class="mr-3">
                                <small class="text-gray">Protéines</small>
                                <div>30g</div>
                            </div>
                            <div class="mr-3">
                                <small class="text-gray">Glucides</small>
                                <div>0g</div>
                            </div>
                            <div>
                                <small class="text-gray">Lipides</small>
                                <div>12g</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="meal-card">
                    <div class="meal-card-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div class="meal-card-body">
                        <div class="d-flex justify-content-between">
                            <h4>Salade verte</h4>
                            <div>
                                <span class="badge badge-primary">50 cal</span>
                            </div>
                        </div>
                        <p>Salade mixte (100g)</p>
                        <div class="d-flex mt-2">
                            <div class="mr-3">
                                <small class="text-gray">Protéines</small>
                                <div>2g</div>
                            </div>
                            <div class="mr-3">
                                <small class="text-gray">Glucides</small>
                                <div>5g</div>
                            </div>
                            <div>
                                <small class="text-gray">Lipides</small>
                                <div>2g</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="meal-card">
                    <div class="meal-card-icon">
                        <i class="fas fa-carrot"></i>
                    </div>
                    <div class="meal-card-body">
                        <div class="d-flex justify-content-between">
                            <h4>Patate douce</h4>
                            <div>
                                <span class="badge badge-primary">150 cal</span>
                            </div>
                        </div>
                        <p>1 moyenne (150g)</p>
                        <div class="d-flex mt-2">
                            <div class="mr-3">
                                <small class="text-gray">Protéines</small>
                                <div>2g</div>
                            </div>
                            <div class="mr-3">
                                <small class="text-gray">Glucides</small>
                                <div>35g</div>
                            </div>
                            <div>
                                <small class="text-gray">Lipides</small>
                                <div>0g</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dîner -->
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Dîner</h3>
                    <div>
                        <span class="badge badge-secondary">0 cal</span>
                        <button class="btn btn-sm btn-outline ml-2">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
                <div class="text-center p-4">
                    <i class="fas fa-utensils" style="font-size: 2rem; color: #DEE2E6;"></i>
                    <p class="mt-2">Aucun aliment ajouté pour le dîner</p>
                    <button class="btn btn-primary mt-2">
                        <i class="fas fa-plus mr-1"></i> Ajouter un repas
                    </button>
                </div>
            </div>

            <!-- Collations -->
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Collations</h3>
                    <div>
                        <span class="badge badge-primary">76 cal</span>
                        <button class="btn btn-sm btn-outline ml-2">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
                <div class="meal-card">
                    <div class="meal-card-icon">
                        <i class="fas fa-apple-alt"></i>
                    </div>
                    <div class="meal-card-body">
                        <div class="d-flex justify-content-between">
                            <h4>Pomme</h4>
                            <div>
                                <span class="badge badge-primary">76 cal</span>
                            </div>
                        </div>
                        <p>1 moyenne (150g)</p>
                        <div class="d-flex mt-2">
                            <div class="mr-3">
                                <small class="text-gray">Protéines</small>
                                <div>0.5g</div>
                            </div>
                            <div class="mr-3">
                                <small class="text-gray">Glucides</small>
                                <div>20g</div>
                            </div>
                            <div>
                                <small class="text-gray">Lipides</small>
                                <div>0.2g</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recommandations IA -->
        <section class="section">
            <div class="card">
                <h3>Recommandations personnalisées</h3>
                <p>Basées sur votre profil et vos objectifs, notre IA vous recommande :</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb mr-2"></i>
                    <strong>Conseil nutritionnel :</strong> Votre consommation de protéines est inférieure à votre objectif. Essayez d'ajouter une source de protéines à votre dîner, comme du poisson ou des légumineuses.
                </div>
                
                <h4 class="mt-4">Suggestions pour le dîner</h4>
                <div class="row">
                    <div class="col-6">
                        <div class="meal-card">
                            <div class="meal-card-icon">
                                <i class="fas fa-fish"></i>
                            </div>
                            <div class="meal-card-body">
                                <h4>Saumon grillé et légumes</h4>
                                <p>Saumon grillé avec légumes de saison et quinoa</p>
                                <p><strong>420</strong> calories</p>
                                <button class="btn btn-sm btn-primary mt-2">Ajouter au journal</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="meal-card">
                            <div class="meal-card-icon">
                                <i class="fas fa-seedling"></i>
                            </div>
                            <div class="meal-card-body">
                                <h4>Curry de lentilles</h4>
                                <p>Curry de lentilles aux légumes et riz complet</p>
                                <p><strong>380</strong> calories</p>
                                <button class="btn btn-sm btn-primary mt-2">Ajouter au journal</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Pied de page -->
    <footer class="bg-light p-4 text-center" style="margin-bottom: 60px;">
        <p>&copy; 2025 FitTrack. Tous droits réservés.</p>
    </footer>

    <!-- Scripts -->
    <script src="js/main.js"></script>
    <script src="js/charts.js"></script>
</body>
</html>
