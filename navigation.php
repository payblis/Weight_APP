<?php
// Fichier de navigation commun à inclure dans toutes les pages
// Détermine quelle page est active
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION)) {
    session_start();
}
?>
<!-- Barre de navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-weight me-2"></i>Weight Tracker
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Tableau de bord -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-home me-1"></i>Tableau de bord
                    </a>
                </li>
                
                <!-- Menu Suivi -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['weight-log.php', 'calorie-history.php', 'reports.php']) ? 'active' : ''; ?>" href="#" id="suiviDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-weight me-1"></i>Suivi
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="suiviDropdown">
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'weight-log.php' ? 'active' : ''; ?>" href="weight-log.php">
                                <i class="fas fa-weight me-1"></i>Suivi de poids
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'calorie-history.php' ? 'active' : ''; ?>" href="calorie-history.php">
                                <i class="fas fa-fire me-1"></i>Historique calorique
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                                <i class="fas fa-chart-bar me-1"></i>Rapports & Statistiques
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Menu Nutrition -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['food-log.php', 'food-management.php', 'preferences.php']) ? 'active' : ''; ?>" href="#" id="nutritionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-utensils me-1"></i>Nutrition
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="nutritionDropdown">
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'food-log.php' ? 'active' : ''; ?>" href="food-log.php">
                                <i class="fas fa-book me-1"></i>Journal alimentaire
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'food-management.php' ? 'active' : ''; ?>" href="food-management.php">
                                <i class="fas fa-apple-alt me-1"></i>Gestion des aliments
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'preferences.php' ? 'active' : ''; ?>" href="preferences.php">
                                <i class="fas fa-heart me-1"></i>Préférences alimentaires
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Menu Activité physique -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['exercise-log.php', 'programs.php']) ? 'active' : ''; ?>" href="#" id="activiteDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-running me-1"></i>Activité physique
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="activiteDropdown">
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'exercise-log.php' ? 'active' : ''; ?>" href="exercise-log.php">
                                <i class="fas fa-dumbbell me-1"></i>Journal d'exercices
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'programs.php' ? 'active' : ''; ?>" href="programs.php">
                                <i class="fas fa-calendar-alt me-1"></i>Programmes d'entraînement
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Menu Objectifs et IA -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['goals.php', 'ai-suggestions.php', 'my-coach.php']) ? 'active' : ''; ?>" href="#" id="objectifsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bullseye me-1"></i>Objectifs & IA
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="objectifsDropdown">
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'goals.php' ? 'active' : ''; ?>" href="goals.php">
                                <i class="fas fa-flag me-1"></i>Gestion des objectifs
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'my-coach.php' ? 'active' : ''; ?>" href="my-coach.php">
                                <i class="fas fa-robot me-1"></i>Mon Coach
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Menu Communauté -->
                <li class="nav-item">
                    <a class="nav-link" href="community.php">
                        <i class="fas fa-users me-1"></i>Communauté
                    </a>
                </li>
            </ul>
            
            <!-- Menu utilisateur -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username'] ?? 'Utilisateur'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="profile.php"><i class="fas fa-user-edit me-1"></i>Profil</a></li>
                        <li><a class="dropdown-item <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" href="settings.php"><i class="fas fa-cog me-1"></i>Paramètres</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
