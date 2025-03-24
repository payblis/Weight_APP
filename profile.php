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
    <title>FitTrack - Profil utilisateur</title>
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
                    <li><a href="activities.php">Activités</a></li>
                    <li><a href="profile.php" class="active">Profil</a></li>
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
                <a href="activities.php">
                    <i class="fas fa-running"></i>
                    <span>Plans</span>
                </a>
            </li>
            <li>
                <a href="profile.php" class="active">
                    <i class="fas fa-ellipsis-h"></i>
                    <span>Plus</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Contenu principal -->
    <main class="container" style="margin-top: 80px; margin-bottom: 80px;">
        <!-- En-tête du profil -->
        <section class="section">
            <div class="card">
                <div class="profile-header">
                    <img src="img/default-avatar.png" alt="Photo de profil" class="profile-avatar" id="profile-avatar">
                    <div class="profile-info">
                        <h2 class="profile-name" id="profile-name">Nom d'utilisateur</h2>
                        <p id="profile-bio">Chargement de la biographie...</p>
                        <div class="profile-stats">
                            <div class="profile-stat">
                                <div class="profile-stat-value" id="profile-days">0</div>
                                <div class="profile-stat-label">Jours</div>
                            </div>
                            <div class="profile-stat">
                                <div class="profile-stat-value" id="profile-weight-lost">0</div>
                                <div class="profile-stat-label">kg perdus</div>
                            </div>
                            <div class="profile-stat">
                                <div class="profile-stat-value" id="profile-activities">0</div>
                                <div class="profile-stat-label">Activités</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-outline" id="edit-profile-btn">
                        <i class="fas fa-edit mr-1"></i> Modifier le profil
                    </button>
                </div>
            </div>
        </section>

        <!-- Informations personnelles -->
        <section class="section">
            <div class="card">
                <h3>Informations personnelles</h3>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Nom</label>
                            <div class="form-control-static" id="user-name">Chargement...</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Email</label>
                            <div class="form-control-static" id="user-email">Chargement...</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label>Âge</label>
                            <div class="form-control-static" id="user-age">Chargement...</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label>Taille</label>
                            <div class="form-control-static" id="user-height">Chargement...</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label>Sexe</label>
                            <div class="form-control-static" id="user-gender">Chargement...</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Objectifs -->
        <section class="section">
            <div class="goals-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Mes objectifs</h3>
                    <button class="btn btn-sm btn-outline" id="edit-goals-btn">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                </div>
                <div class="goal-item">
                    <div class="goal-info">
                        <div class="goal-title">Objectif de poids</div>
                        <div class="text-gray" id="weight-goal-info">Chargement...</div>
                    </div>
                    <div class="goal-progress">
                        <div class="goal-progress-bar" id="weight-goal-progress" style="width: 0%;"></div>
                    </div>
                </div>
                <div class="goal-item">
                    <div class="goal-info">
                        <div class="goal-title">Objectif d'activité</div>
                        <div class="text-gray" id="activity-goal-info">Chargement...</div>
                    </div>
                    <div class="goal-progress">
                        <div class="goal-progress-bar" id="activity-goal-progress" style="width: 0%;"></div>
                    </div>
                </div>
                <div class="goal-item">
                    <div class="goal-info">
                        <div class="goal-title">Objectif calorique</div>
                        <div class="text-gray" id="calorie-goal-info">Chargement...</div>
                    </div>
                    <div class="goal-progress">
                        <div class="goal-progress-bar" id="calorie-goal-progress" style="width: 0%;"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Configuration de l'API ChatGPT -->
        <section class="section">
            <div class="api-config">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Configuration de l'API ChatGPT</h3>
                    <div class="api-status" id="api-status">Non configurée</div>
                </div>
                <p>L'API ChatGPT est utilisée pour générer des recommandations personnalisées de repas, d'exercices et pour l'analyse morphologique.</p>
                <div class="form-group">
                    <label for="api-key">Clé API</label>
                    <div class="d-flex">
                        <input type="password" id="api-key" class="form-control" placeholder="Entrez votre clé API ChatGPT">
                        <button class="btn btn-primary ml-2" id="save-api-key">Enregistrer</button>
                    </div>
                    <small class="text-gray">Votre clé API est stockée de manière sécurisée et n'est jamais partagée.</small>
                </div>
                <div class="form-group mt-3">
                    <label>Fonctionnalités activées</label>
                    <div class="d-flex flex-wrap">
                        <div class="checkbox-item">
                            <input type="checkbox" id="enable-meal-recommendations" checked>
                            <label for="enable-meal-recommendations">Recommandations de repas</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="enable-exercise-recommendations" checked>
                            <label for="enable-exercise-recommendations">Recommandations d'exercices</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="enable-morphology-analysis" checked>
                            <label for="enable-morphology-analysis">Analyse morphologique</label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Préférences -->
        <section class="section">
            <div class="card">
                <h3>Préférences</h3>
                <div class="form-group">
                    <label>Unités de mesure</label>
                    <div class="d-flex">
                        <div class="radio-item">
                            <input type="radio" id="metric" name="units" value="metric" checked>
                            <label for="metric">Métrique (kg, cm)</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="imperial" name="units" value="imperial">
                            <label for="imperial">Impérial (lb, in)</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notifications</label>
                    <div class="d-flex flex-wrap">
                        <div class="checkbox-item">
                            <input type="checkbox" id="enable-reminders" checked>
                            <label for="enable-reminders">Rappels quotidiens</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="enable-progress-updates" checked>
                            <label for="enable-progress-updates">Mises à jour de progression</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="enable-tips" checked>
                            <label for="enable-tips">Conseils et astuces</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Thème</label>
                    <div class="d-flex">
                        <div class="radio-item">
                            <input type="radio" id="light-theme" name="theme" value="light" checked>
                            <label for="light-theme">Clair</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="dark-theme" name="theme" value="dark">
                            <label for="dark-theme">Sombre</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="system-theme" name="theme" value="system">
                            <label for="system-theme">Système</label>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-primary" id="save-preferences">
                        Enregistrer les préférences
                    </button>
                </div>
            </div>
        </section>

        <!-- Confidentialité et sécurité -->
        <section class="section">
            <div class="card">
                <h3>Confidentialité et sécurité</h3>
                <div class="form-group">
                    <label>Visibilité du profil</label>
                    <div class="d-flex">
                        <div class="radio-item">
                            <input type="radio" id="private-profile" name="visibility" value="private" checked>
                            <label for="private-profile">Privé</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="friends-profile" name="visibility" value="friends">
                            <label for="friends-profile">Amis uniquement</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="public-profile" name="visibility" value="public">
                            <label for="public-profile">Public</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <button class="btn btn-outline" id="change-password-btn">
                        <i class="fas fa-lock mr-1"></i> Changer le mot de passe
                    </button>
                </div>
                <div class="form-group">
                    <label>Données personnelles</label>
                    <div class="d-flex">
                        <button class="btn btn-outline mr-2" id="export-data-btn">
                            <i class="fas fa-download mr-1"></i> Exporter mes données
                        </button>
                        <button class="btn btn-danger" id="delete-account-btn">
                            <i class="fas fa-trash mr-1"></i> Supprimer mon compte
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal de modification du profil -->
    <div class="modal" id="edit-profile-modal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier le profil</h3>
                <button class="modal-close" id="close-profile-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <img src="img/default-avatar.png" alt="Photo de profil" class="profile-avatar" id="edit-avatar">
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline" id="change-avatar-btn">
                            <i class="fas fa-camera mr-1"></i> Changer la photo
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit-name">Nom</label>
                    <input type="text" id="edit-name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit-email">Email</label>
                    <input type="email" id="edit-email" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit-bio">Biographie</label>
                    <textarea id="edit-bio" class="form-control" rows="3"></textarea>
                </div>
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label for="edit-age">Âge</label>
                            <input type="number" id="edit-age" class="form-control" min="13" max="120">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label for="edit-height">Taille (cm)</label>
                            <input type="number" id="edit-height" class="form-control" min="100" max="250">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label for="edit-gender">Sexe</label>
                            <select id="edit-gender" class="form-control">
                                <option value="male">Homme</option>
                                <option value="female">Femme</option>
                                <option value="other">Autre</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancel-edit-profile">Annuler</button>
                <button class="btn btn-primary" id="save-profile">Enregistrer</button>
            </div>
        </div>
    </div>

    <!-- Modal de modification des objectifs -->
    <div class="modal" id="edit-goals-modal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier les objectifs</h3>
                <button class="modal-close" id="close-goals-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit-weight-goal">Objectif de poids (kg)</label>
                    <input type="number" id="edit-weight-goal" class="form-control" step="0.1" min="30" max="200">
                </div>
                <div class="form-group">
                    <label for="edit-weight-date">Date cible</label>
                    <input type="date" id="edit-weight-date" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit-activity-goal">Objectif d'activité (minutes/semaine)</label>
                    <input type="number" id="edit-activity-goal" class="form-control" min="0" max="1000">
                </div>
                <div class="form-group">
                    <label for="edit-calorie-goal">Objectif calorique quotidien</label>
                    <input type="number" id="edit-calorie-goal" class="form-control" min="1000" max="5000">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancel-edit-goals">Annuler</button>
                <button class="btn btn-primary" id="save-goals">Enregistrer</button>
            </div>
        </div>
    </div>

    <!-- Modal de changement de mot de passe -->
    <div class="modal" id="change-password-modal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Changer le mot de passe</h3>
                <button class="modal-close" id="close-password-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="current-password">Mot de passe actuel</label>
                    <input type="password" id="current-password" class="form-control">
                </div>
                <div class="form-group">
                    <label for="new-password">Nouveau mot de passe</label>
                    <input type="password" id="new-password" class="form-control">
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm-password" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancel-change-password">Annuler</button>
                <button class="btn btn-primary" id="save-password">Enregistrer</button>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <footer class="bg-light p-4 text-center" style="margin-bottom: 60px;">
        <p>&copy; 2025 FitTrack. Tous droits réservés.</p>
    </footer>

    <!-- Scripts -->
    <script src="js/main.js"></script>
    <script src="js/profile.js"></script>
</body>
</html>
