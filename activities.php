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
    <title>FitTrack - Activités physiques</title>
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
                    <li><a href="meals.php">Repas</a></li>
                    <li><a href="activities.php" class="active">Activités</a></li>
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
                <a href="weight-log.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Progrès</span>
                </a>
            </li>
            <li>
                <a href="activities.php" class="active">
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
                    <h2>Activités physiques</h2>
                    <p>Suivez vos exercices et brûlez des calories</p>
                </div>
                <button class="btn btn-primary" id="add-activity-btn">
                    <i class="fas fa-plus mr-1"></i> Ajouter une activité
                </button>
            </div>
        </div>

        <!-- Résumé des activités -->
        <section class="section">
            <div class="card">
                <div class="row">
                    <div class="col-4">
                        <div class="text-center">
                            <div class="progress-circle" id="activity-progress-circle">
                                <div class="progress-circle-inner">
                                    <div class="progress-circle-value" id="activity-progress-value">0%</div>
                                    <div class="progress-circle-label">Objectif</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="row">
                            <div class="col-4">
                                <div class="text-center">
                                    <h5>Calories brûlées</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;" id="calories-burned">0</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <h5>Minutes d'activité</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;" id="activity-minutes">0</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <h5>Pas</h5>
                                    <div class="text-primary" style="font-size: 1.5rem;" id="steps-count">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5>Progression vers l'objectif</h5>
                                <span class="badge badge-primary" id="activity-goal-progress">0/0 min</span>
                            </div>
                            <div class="progress mt-2">
                                <div class="progress-bar" id="activity-progress-bar" style="width: 0%;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-gray">Cette semaine</small>
                                <small class="text-gray" id="activity-goal">Objectif: 150 min/semaine</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Compteur de pas -->
        <section class="section">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Compteur de pas</h3>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline active" id="view-week-steps">Semaine</button>
                        <button class="btn btn-sm btn-outline" id="view-month-steps">Mois</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <div class="text-center">
                            <div class="progress-circle" id="steps-circle">
                                <div class="progress-circle-inner">
                                    <div class="progress-circle-value" id="today-steps">0</div>
                                    <div class="progress-circle-label">Aujourd'hui</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="progress">
                                    <div class="progress-bar" id="steps-progress-bar" style="width: 0%;"></div>
                                </div>
                                <div class="text-gray">Objectif: <span id="steps-goal">10,000</span> pas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="chart-container">
                            <canvas id="steps-chart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Activités récentes -->
        <section class="section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Activités récentes</h3>
                <div class="d-flex">
                    <div class="form-group mb-0 mr-2">
                        <select id="sort-activities" class="form-control form-control-sm">
                            <option value="newest">Plus récent</option>
                            <option value="oldest">Plus ancien</option>
                            <option value="calories">Calories (max)</option>
                            <option value="duration">Durée (max)</option>
                        </select>
                    </div>
                    <button class="btn btn-sm btn-outline" id="export-activities">
                        <i class="fas fa-download mr-1"></i> Exporter
                    </button>
                </div>
            </div>
            <div id="recent-activities">
                <!-- Les activités récentes seront chargées dynamiquement ici -->
                <div class="text-center p-4" id="no-activities" style="display: none;">
                    <i class="fas fa-running" style="font-size: 2rem; color: #DEE2E6;"></i>
                    <p class="mt-2">Aucune activité récente</p>
                    <button class="btn btn-primary mt-2" id="add-first-activity">
                        <i class="fas fa-plus mr-1"></i> Ajouter votre première activité
                    </button>
                </div>
            </div>
            <div class="pagination-container" id="activities-pagination">
                <!-- La pagination sera chargée dynamiquement ici -->
            </div>
        </section>

        <!-- Exercices recommandés -->
        <section class="section">
            <h3>Exercices recommandés</h3>
            <div class="row" id="recommended-exercises">
                <!-- Exercice 1 -->
                <div class="col-4">
                    <div class="exercise-card">
                        <div class="exercise-icon">
                            <i class="fas fa-walking"></i>
                        </div>
                        <div class="exercise-details">
                            <h4>Marche rapide</h4>
                            <p>Idéal pour brûler des calories et améliorer l'endurance</p>
                            <div class="exercise-stats">
                                <div class="exercise-stat">
                                    <i class="fas fa-fire"></i> 300 cal/h
                                </div>
                                <div class="exercise-stat">
                                    <i class="fas fa-clock"></i> 30 min
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Exercice 2 -->
                <div class="col-4">
                    <div class="exercise-card">
                        <div class="exercise-icon">
                            <i class="fas fa-running"></i>
                        </div>
                        <div class="exercise-details">
                            <h4>Course à pied</h4>
                            <p>Excellent pour le cardio et la perte de poids</p>
                            <div class="exercise-stats">
                                <div class="exercise-stat">
                                    <i class="fas fa-fire"></i> 600 cal/h
                                </div>
                                <div class="exercise-stat">
                                    <i class="fas fa-clock"></i> 20 min
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Exercice 3 -->
                <div class="col-4">
                    <div class="exercise-card">
                        <div class="exercise-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="exercise-details">
                            <h4>Musculation</h4>
                            <p>Renforce les muscles et accélère le métabolisme</p>
                            <div class="exercise-stats">
                                <div class="exercise-stat">
                                    <i class="fas fa-fire"></i> 400 cal/h
                                </div>
                                <div class="exercise-stat">
                                    <i class="fas fa-clock"></i> 45 min
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <button class="btn btn-outline" id="load-more-exercises">
                    Voir plus d'exercices
                </button>
            </div>
        </section>

        <!-- Programme d'entraînement -->
        <section class="section">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Programme d'entraînement personnalisé</h3>
                    <button class="btn btn-sm btn-primary" id="generate-program">
                        <i class="fas fa-sync-alt mr-1"></i> Générer
                    </button>
                </div>
                <div id="training-program">
                    <div class="text-center p-4" id="no-program">
                        <i class="fas fa-clipboard-list" style="font-size: 2rem; color: #DEE2E6;"></i>
                        <p class="mt-2">Cliquez sur "Générer" pour créer un programme d'entraînement personnalisé basé sur vos objectifs</p>
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
                <p>Basées sur votre profil et vos activités, notre IA vous recommande :</p>
                
                <div class="ai-recommendation">
                    <h4><i class="fas fa-lightbulb mr-2"></i> Conseil d'entraînement</h4>
                    <p id="ai-training-tip">Chargement des recommandations...</p>
                </div>
                
                <div class="ai-recommendation">
                    <h4><i class="fas fa-chart-line mr-2"></i> Analyse de performance</h4>
                    <p id="ai-performance-analysis">Analyse de vos données en cours...</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal d'ajout d'activité -->
    <div class="modal" id="add-activity-modal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter une activité</h3>
                <button class="modal-close" id="close-activity-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="activity-type">Type d'activité</label>
                    <select id="activity-type" class="form-control">
                        <option value="">Sélectionnez une activité</option>
                        <option value="walking">Marche</option>
                        <option value="running">Course à pied</option>
                        <option value="cycling">Vélo</option>
                        <option value="swimming">Natation</option>
                        <option value="gym">Musculation</option>
                        <option value="yoga">Yoga</option>
                        <option value="other">Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="activity-date">Date</label>
                    <input type="date" id="activity-date" class="form-control" value="">
                </div>
                <div class="form-group">
                    <label for="activity-duration">Durée (minutes)</label>
                    <input type="number" id="activity-duration" class="form-control" min="1" max="1440">
                </div>
                <div class="form-group">
                    <label for="activity-intensity">Intensité</label>
                    <select id="activity-intensity" class="form-control">
                        <option value="low">Faible</option>
                        <option value="medium" selected>Moyenne</option>
                        <option value="high">Élevée</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="activity-calories">Calories brûlées (estimées)</label>
                    <input type="number" id="activity-calories" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label for="activity-notes">Notes (optionnel)</label>
                    <textarea id="activity-notes" class="form-control" rows="3" placeholder="Ajoutez des notes sur votre activité..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancel-add-activity">Annuler</button>
                <button class="btn btn-primary" id="confirm-add-activity">Enregistrer</button>
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
    <script src="js/activities.js"></script>
</body>
</html>
