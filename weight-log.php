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
    <title>FitTrack - Suivi du poids</title>
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
                    <li><a href="weight-log.php" class="active">Poids</a></li>
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
                <a href="dashboard.php">
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
                <a href="weight-log.php" class="active">
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
        <!-- En-tête de la page -->
        <div class="card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Suivi du poids</h2>
                    <p>Suivez votre progression et atteignez votre objectif de poids</p>
                </div>
                <button class="btn btn-primary" id="add-weight-btn">
                    <i class="fas fa-plus mr-1"></i> Ajouter une mesure
                </button>
            </div>
        </div>

        <!-- Résumé du poids -->
        <section class="section">
            <div class="card">
                <div class="row">
                    <div class="col-4">
                        <div class="text-center">
                            <div class="progress-circle" id="weight-progress-circle">
                                <div class="progress-circle-inner">
                                    <div class="progress-circle-value" id="weight-progress-value">0%</div>
                                    <div class="progress-circle-label">Progression</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="row">
                            <div class="col-4">
                                <div class="text-center">
                                    <h5>Poids initial</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;" id="initial-weight">0 kg</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <h5>Poids actuel</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;" id="current-weight">0 kg</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <h5>Objectif</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;" id="target-weight">0 kg</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5>Progression vers l'objectif</h5>
                                <span class="badge badge-primary" id="weight-lost">0 kg</span>
                            </div>
                            <div class="progress mt-2">
                                <div class="progress-bar" id="weight-progress-bar" style="width: 0%;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-gray" id="weight-start-date">Date de début</small>
                                <small class="text-gray" id="weight-target-date">Date cible</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Graphique de progression -->
        <section class="section">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Évolution du poids</h3>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline active" id="view-week">Semaine</button>
                        <button class="btn btn-sm btn-outline" id="view-month">Mois</button>
                        <button class="btn btn-sm btn-outline" id="view-year">Année</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="weight-chart" height="250"></canvas>
                </div>
            </div>
        </section>

        <!-- Historique des mesures -->
        <section class="section">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Historique des mesures</h3>
                    <div class="d-flex">
                        <div class="form-group mb-0 mr-2">
                            <select id="sort-weight" class="form-control form-control-sm">
                                <option value="newest">Plus récent</option>
                                <option value="oldest">Plus ancien</option>
                            </select>
                        </div>
                        <button class="btn btn-sm btn-outline" id="export-weight">
                            <i class="fas fa-download mr-1"></i> Exporter
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Poids</th>
                                <th>Variation</th>
                                <th>IMC</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="weight-history">
                            <!-- L'historique des mesures sera chargé dynamiquement ici -->
                        </tbody>
                    </table>
                </div>
                <div class="text-center p-4" id="no-weight-data" style="display: none;">
                    <i class="fas fa-weight" style="font-size: 2rem; color: #DEE2E6;"></i>
                    <p class="mt-2">Aucune mesure de poids enregistrée</p>
                    <button class="btn btn-primary mt-2" id="add-first-weight">
                        <i class="fas fa-plus mr-1"></i> Ajouter votre première mesure
                    </button>
                </div>
                <div class="pagination-container" id="weight-pagination">
                    <!-- La pagination sera chargée dynamiquement ici -->
                </div>
            </div>
        </section>

        <!-- Statistiques et tendances -->
        <section class="section">
            <div class="card">
                <h3>Statistiques et tendances</h3>
                <div class="row">
                    <div class="col-3">
                        <div class="text-center">
                            <h5>Moyenne hebdo.</h5>
                            <div class="text-primary" style="font-size: 1.5rem;" id="weekly-average">0 kg</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <h5>Variation hebdo.</h5>
                            <div id="weekly-change">0 kg</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <h5>Tendance mensuelle</h5>
                            <div id="monthly-trend">Stable</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <h5>Prévision</h5>
                            <div id="weight-forecast">--</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recommandations IA -->
        <section class="section">
            <div class="ai-feature">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-robot ai-icon" style="font-size: 1.5rem;"></i>
                    <h3 class="mb-0">Recommandations personnalisées</h3>
                </div>
                <p>Basées sur votre progression et vos objectifs, notre IA vous recommande :</p>
                
                <div class="ai-recommendation">
                    <h4><i class="fas fa-lightbulb mr-2"></i> Conseil pour atteindre votre objectif</h4>
                    <p id="ai-weight-tip">Chargement des recommandations...</p>
                </div>
                
                <div class="ai-recommendation">
                    <h4><i class="fas fa-chart-line mr-2"></i> Analyse de tendance</h4>
                    <p id="ai-trend-analysis">Analyse de vos données en cours...</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal d'ajout de poids -->
    <div class="modal" id="add-weight-modal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter une mesure de poids</h3>
                <button class="modal-close" id="close-weight-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="weight-date">Date</label>
                    <input type="date" id="weight-date" class="form-control" value="">
                </div>
                <div class="form-group">
                    <label for="weight-value">Poids (kg)</label>
                    <input type="number" id="weight-value" class="form-control" step="0.1" min="30" max="300">
                </div>
                <div class="form-group">
                    <label for="weight-notes">Notes (optionnel)</label>
                    <textarea id="weight-notes" class="form-control" rows="3" placeholder="Ajoutez des notes sur votre mesure..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancel-add-weight">Annuler</button>
                <button class="btn btn-primary" id="confirm-add-weight">Enregistrer</button>
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
    <script src="js/weight-log.js"></script>
</body>
</html>
