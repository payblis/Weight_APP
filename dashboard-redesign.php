<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack - Tableau de bord</title>
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
                    <li><a href="meals.php">Repas</a></li>
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
                <a href="dashboard.php" class="active">
                    <i class="fas fa-home"></i>
                    <span>Accueil</span>
                </a>
            </li>
            <li>
                <a href="meals.php">
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
        <!-- Message de bienvenue -->
        <div class="card">
            <div class="d-flex align-items-center">
                <div>
                    <h2>Bonjour, <span id="username">Utilisateur</span> !</h2>
                    <p>Nous sommes heureux de vous revoir. Continuez votre progression !</p>
                </div>
            </div>
        </div>

        <!-- Résumé quotidien -->
        <section class="section">
            <h3>Aujourd'hui</h3>
            <div class="row">
                <!-- Calories -->
                <div class="col-4">
                    <div class="card text-center">
                        <h4>Calories</h4>
                        <div class="progress-circle">
                            <div class="progress-circle-bar" style="--progress-rotation: 180deg;"></div>
                            <div class="progress-circle-inner">
                                <div class="progress-circle-value" id="current-calories">959</div>
                                <div class="progress-circle-label">Restantes</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span class="text-gray">Base</span>
                                    <div id="base-goal">1,600</div>
                                </div>
                                <div>
                                    <span class="text-gray">Nourriture</span>
                                    <div id="food-calories">641</div>
                                </div>
                                <div>
                                    <span class="text-gray">Exercice</span>
                                    <div id="exercise-calories">235</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pas -->
                <div class="col-4">
                    <div class="card text-center">
                        <h4>Pas</h4>
                        <div class="progress-circle">
                            <div class="progress-circle-bar" style="--progress-rotation: 270deg;"></div>
                            <div class="progress-circle-inner">
                                <div class="progress-circle-value" id="current-steps">7,456</div>
                                <div class="progress-circle-label">Pas</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress">
                                <div class="progress-bar" style="width: 75%;"></div>
                            </div>
                            <div class="text-gray">Objectif: <span id="step-goal">10,000</span> pas</div>
                        </div>
                    </div>
                </div>
                
                <!-- Poids -->
                <div class="col-4">
                    <div class="card text-center">
                        <h4>Poids</h4>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-gray">Initial</div>
                                <div class="text-primary" id="initial-weight">85.0</div>
                                <div class="text-gray">kg</div>
                            </div>
                            <div>
                                <div class="text-gray">Actuel</div>
                                <div class="text-primary" id="current-weight">83.2</div>
                                <div class="text-gray">kg</div>
                            </div>
                            <div>
                                <div class="text-gray">Objectif</div>
                                <div class="text-primary" id="target-weight">75.0</div>
                                <div class="text-gray">kg</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress">
                                <div class="progress-bar" style="width: 18%;"></div>
                            </div>
                            <div class="text-gray">Perdu: <span id="weight-lost">1.8</span> kg</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Activités récentes -->
        <section class="section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Activités récentes</h3>
                <a href="activities.php" class="btn btn-sm btn-outline">Voir tout</a>
            </div>
            <div class="row" id="recent-activities">
                <div class="col-6">
                    <div class="activity-card">
                        <div class="activity-icon">
                            <i class="fas fa-running"></i>
                        </div>
                        <div class="activity-details">
                            <h4>Course à pied</h4>
                            <p>16 mars 2025 - 20 minutes</p>
                            <p><strong>200</strong> calories brûlées</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="activity-card">
                        <div class="activity-icon">
                            <i class="fas fa-walking"></i>
                        </div>
                        <div class="activity-details">
                            <h4>Marche rapide</h4>
                            <p>15 mars 2025 - 30 minutes</p>
                            <p><strong>150</strong> calories brûlées</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Repas recommandés -->
        <section class="section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Repas recommandés</h3>
                <a href="meals.php" class="btn btn-sm btn-outline">Voir tout</a>
            </div>
            <div class="row" id="recommended-meals">
                <div class="col-6">
                    <div class="meal-card">
                        <div class="meal-card-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="meal-card-body">
                            <h4>Salade de quinoa et légumes</h4>
                            <p>Salade de quinoa avec légumes frais et vinaigrette légère</p>
                            <p><strong>400</strong> calories</p>
                            <button class="btn btn-sm btn-primary mt-2">Ajouter au journal</button>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="meal-card">
                        <div class="meal-card-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="meal-card-body">
                            <h4>Poulet rôti et patate douce</h4>
                            <p>Poulet rôti avec patate douce et légumes verts</p>
                            <p><strong>450</strong> calories</p>
                            <button class="btn btn-sm btn-primary mt-2">Ajouter au journal</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Programme personnalisé -->
        <section class="section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Programme personnalisé</h3>
                <button id="generate-program-btn" class="btn btn-sm btn-primary">Générer</button>
            </div>
            <div class="card" id="custom-program">
                <p class="text-center">Cliquez sur le bouton "Générer" pour créer un programme personnalisé basé sur votre profil et vos objectifs.</p>
            </div>
        </section>

        <!-- Analyse morphologique -->
        <section class="section">
            <h3>Analyse morphologique par IA</h3>
            <div class="card">
                <div class="upload-area" id="upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Téléchargez une photo</h4>
                    <p>Notre IA analysera votre morphologie et vous proposera des exercices ciblés</p>
                    <input type="file" id="image-upload" accept="image/*" style="display: none;">
                </div>
                <div id="analysis-results" style="display: none;">
                    <div class="text-center mb-3">
                        <img id="uploaded-image" src="" alt="Image téléchargée" style="max-width: 100%; max-height: 300px; border-radius: 10px;">
                    </div>
                    <div id="analysis-content"></div>
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
    <script src="js/dashboard.js"></script>
</body>
</html>
