<?php
// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration et la connexion à la base de données
require_once 'database/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack - Journal alimentaire</title>
    <link rel="stylesheet" href="css/style.css">
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
                    <p id="current-date">Chargement de la date...</p>
                </div>
                <div class="d-flex">
                    <button class="btn btn-outline mr-2" id="date-picker-btn">
                        <i class="fas fa-calendar-alt mr-1"></i> Changer de date
                    </button>
                    <button class="btn btn-primary" id="add-food-btn">
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
                                    <div class="progress-circle-value" id="remaining-calories">0</div>
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
                                    <div class="text-primary" style="font-size: 1.5rem;" id="calorie-goal">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="text-center">
                                    <h5>Nourriture</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;" id="food-calories">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="text-center">
                                    <h5>Exercice</h5>
                                    <div class="text-success" style="font-size: 1.5rem;" id="exercise-calories">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="text-center">
                                    <h5>Restant</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;" id="remaining-calories-display">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h5>Macronutriments</h5>
                            <div class="row mt-2">
                                <div class="col-4">
                                    <label>Protéines</label>
                                    <div class="progress">
                                        <div class="progress-bar" id="protein-progress" style="width: 0%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span id="protein-current">0g</span>
                                        <span class="text-gray" id="protein-goal">/ 0g</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label>Glucides</label>
                                    <div class="progress">
                                        <div class="progress-bar" id="carbs-progress" style="width: 0%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span id="carbs-current">0g</span>
                                        <span class="text-gray" id="carbs-goal">/ 0g</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label>Lipides</label>
                                    <div class="progress">
                                        <div class="progress-bar" id="fat-progress" style="width: 0%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span id="fat-current">0g</span>
                                        <span class="text-gray" id="fat-goal">/ 0g</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Scan de code-barres -->
        <section class="section">
            <div class="barcode-scanner">
                <div class="barcode-scanner-icon">
                    <i class="fas fa-barcode"></i>
                </div>
                <h4>Scan de code-barres</h4>
                <p>Scannez le code-barres d'un produit pour l'ajouter rapidement à votre journal</p>
                <button class="btn btn-primary mt-2" id="scan-barcode-btn">
                    <i class="fas fa-camera mr-1"></i> Scanner un produit
                </button>
                <div class="scan-result" id="scan-result" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 id="scanned-product-name">Nom du produit</h5>
                        <span class="badge badge-primary" id="scanned-product-calories">0 cal</span>
                    </div>
                    <div class="d-flex mt-2">
                        <div class="mr-3">
                            <small class="text-gray">Protéines</small>
                            <div id="scanned-product-protein">0g</div>
                        </div>
                        <div class="mr-3">
                            <small class="text-gray">Glucides</small>
                            <div id="scanned-product-carbs">0g</div>
                        </div>
                        <div>
                            <small class="text-gray">Lipides</small>
                            <div id="scanned-product-fat">0g</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-primary" id="add-scanned-product">Ajouter au journal</button>
                        <button class="btn btn-sm btn-outline ml-2" id="cancel-scan">Annuler</button>
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
                        <span class="badge badge-primary" id="breakfast-calories">0 cal</span>
                        <button class="btn btn-sm btn-outline ml-2" id="add-breakfast">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
                <div id="breakfast-items">
                    <!-- Les aliments du petit-déjeuner seront chargés dynamiquement ici -->
                </div>
                <div class="text-center p-4" id="no-breakfast" style="display: none;">
                    <i class="fas fa-utensils" style="font-size: 2rem; color: #DEE2E6;"></i>
                    <p class="mt-2">Aucun aliment ajouté pour le petit-déjeuner</p>
                    <button class="btn btn-primary mt-2" id="add-breakfast-empty">
                        <i class="fas fa-plus mr-1"></i> Ajouter un aliment
                    </button>
                </div>
            </div>

            <!-- Déjeuner -->
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Déjeuner</h3>
                    <div>
                        <span class="badge badge-primary" id="lunch-calories">0 cal</span>
                        <button class="btn btn-sm btn-outline ml-2" id="add-lunch">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
                <div id="lunch-items">
                    <!-- Les aliments du déjeuner seront chargés dynamiquement ici -->
                </div>
                <div class="text-center p-4" id="no-lunch" style="display: none;">
                    <i class="fas fa-utensils" style="font-size: 2rem; color: #DEE2E6;"></i>
                    <p class="mt-2">Aucun aliment ajouté pour le déjeuner</p>
                    <button class="btn btn-primary mt-2" id="add-lunch-empty">
                        <i class="fas fa-plus mr-1"></i> Ajouter un aliment
                    </button>
                </div>
            </div>

            <!-- Dîner -->
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Dîner</h3>
                    <div>
                        <span class="badge badge-primary" id="dinner-calories">0 cal</span>
                        <button class="btn btn-sm btn-outline ml-2" id="add-dinner">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
                <div id="dinner-items">
                    <!-- Les aliments du dîner seront chargés dynamiquement ici -->
                </div>
                <div class="text-center p-4" id="no-dinner" style="display: none;">
                    <i class="fas fa-utensils" style="font-size: 2rem; color: #DEE2E6;"></i>
                    <p class="mt-2">Aucun aliment ajouté pour le dîner</p>
                    <button class="btn btn-primary mt-2" id="add-dinner-empty">
                        <i class="fas fa-plus mr-1"></i> Ajouter un aliment
                    </button>
                </div>
            </div>

            <!-- Collations -->
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Collations</h3>
                    <div>
                        <span class="badge badge-primary" id="snack-calories">0 cal</span>
                        <button class="btn btn-sm btn-outline ml-2" id="add-snack">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
                <div id="snack-items">
                    <!-- Les aliments des collations seront chargés dynamiquement ici -->
                </div>
                <div class="text-center p-4" id="no-snack" style="display: none;">
                    <i class="fas fa-utensils" style="font-size: 2rem; color: #DEE2E6;"></i>
                    <p class="mt-2">Aucun aliment ajouté pour les collations</p>
                    <button class="btn btn-primary mt-2" id="add-snack-empty">
                        <i class="fas fa-plus mr-1"></i> Ajouter un aliment
                    </button>
                </div>
            </div>
        </section>

        <!-- Recommandations IA -->
        <section class="section">
            <div class="card">
                <h3>Recommandations personnalisées</h3>
                <p>Basées sur votre profil et vos objectifs, notre IA vous recommande :</p>
                
                <div class="ai-recommendation">
                    <h4><i class="fas fa-lightbulb mr-2"></i> Conseil nutritionnel</h4>
                    <p id="ai-nutrition-tip">Chargement des recommandations...</p>
                </div>
                
                <h4 class="mt-4">Suggestions pour compléter votre journée</h4>
                <div class="row" id="ai-meal-suggestions">
                    <div class="col-12 text-center">
                        <div class="loading-spinner"></div>
                        <p class="mt-2">Génération des suggestions...</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal d'ajout d'aliment -->
    <div class="modal" id="add-food-modal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter un aliment</h3>
                <button class="modal-close" id="close-food-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="food-search">Rechercher un aliment</label>
                    <div class="search-container">
                        <input type="text" id="food-search" class="form-control" placeholder="Nom de l'aliment...">
                        <button id="search-food-btn" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div id="search-results" style="display: none;">
                    <h4>Résultats de recherche</h4>
                    <div id="food-results-list"></div>
                </div>
                
                <div id="selected-food" style="display: none;">
                    <h4>Aliment sélectionné</h4>
                    <div class="meal-card">
                        <div class="meal-card-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="meal-card-body">
                            <h4 id="selected-food-name">Nom de l'aliment</h4>
                            <div class="form-group mt-2">
                                <label for="food-quantity">Quantité</label>
                                <div class="d-flex">
                                    <input type="number" id="food-quantity" class="form-control" value="1" min="0.1" step="0.1">
                                    <select id="food-unit" class="form-control ml-2">
                                        <option value="portion">Portion</option>
                                        <option value="g">Grammes</option>
                                        <option value="ml">Millilitres</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex mt-2">
                                <div class="mr-3">
                                    <small class="text-gray">Calories</small>
                                    <div id="selected-food-calories">0</div>
                                </div>
                                <div class="mr-3">
                                    <small class="text-gray">Protéines</small>
                                    <div id="selected-food-protein">0g</div>
                                </div>
                                <div class="mr-3">
                                    <small class="text-gray">Glucides</small>
                                    <div id="selected-food-carbs">0g</div>
                                </div>
                                <div>
                                    <small class="text-gray">Lipides</small>
                                    <div id="selected-food-fat">0g</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancel-add-food">Annuler</button>
                <button class="btn btn-primary" id="confirm-add-food" disabled>Ajouter au journal</button>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <footer class="bg-light p-4 text-center" style="margin-bottom: 60px;">
        <p>&copy; 2025 FitTrack. Tous droits réservés.</p>
    </footer>

    <!-- Scripts -->
    <script src="js/main.js"></script>
    <script src="js/charts.js"></script>
    <script src="js/meals.js"></script>
</body>
</html>
