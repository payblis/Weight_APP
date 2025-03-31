<?php
// Fichier de navigation commun à inclure dans toutes les pages
// Détermine quelle page est active
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION)) {
    session_start();
}
?>
<!-- Barre de navigation desktop -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary d-none d-lg-block">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-weight me-2"></i>MyFity
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
                
                <!-- Menu Objectifs -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'goals.php' ? 'active' : ''; ?>" href="goals.php">
                        <i class="fas fa-bullseye me-1"></i>Objectifs
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

<!-- Navigation mobile (bottom bar) -->
<nav class="navbar fixed-bottom navbar-light bg-white border-top d-lg-none">
    <div class="container-fluid p-0">
        <div class="row w-100 text-center g-0">
            <div class="col">
                <a class="nav-link py-2 <?php echo $current_page === 'dashboard.php' ? 'text-primary' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home d-block mb-1"></i>
                    <small>Accueil</small>
                </a>
            </div>
            <div class="col">
                <a class="nav-link py-2 <?php echo $current_page === 'food-log.php' ? 'text-primary' : ''; ?>" href="food-log.php">
                    <i class="fas fa-utensils d-block mb-1"></i>
                    <small>Repas</small>
                </a>
            </div>
            <div class="col">
                <div class="dropup">
                    <a class="nav-link py-2" href="#" data-bs-toggle="dropdown" style="position: relative; top: -20px;">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px; margin: 0 auto;">
                            <i class="fas fa-plus fa-lg text-white"></i>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="food-log.php?action=add_meal"><i class="fas fa-utensils me-2"></i>Ajouter un repas</a></li>
                        <li><a class="dropdown-item" href="weight-log.php?action=add"><i class="fas fa-weight me-2"></i>Ajouter un poids</a></li>
                        <li><a class="dropdown-item" href="exercise-log.php?action=add"><i class="fas fa-dumbbell me-2"></i>Ajouter un exercice</a></li>
                    </ul>
                </div>
            </div>
            <div class="col">
                <a class="nav-link py-2 <?php echo $current_page === 'exercise-log.php' ? 'text-primary' : ''; ?>" href="exercise-log.php">
                    <i class="fas fa-dumbbell d-block mb-1"></i>
                    <small>Exercices</small>
                </a>
            </div>
            <div class="col">
                <a class="nav-link py-2 <?php echo $current_page === 'profile.php' ? 'text-primary' : ''; ?>" href="profile.php">
                    <i class="fas fa-user d-block mb-1"></i>
                    <small>Profil</small>
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
/* Ajuster le padding du body pour la barre de navigation fixe en bas */
@media (max-width: 991.98px) {
    body {
        padding-bottom: 85px !important;
    }
    
    .navbar.fixed-bottom {
        height: 65px;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }
    
    .navbar.fixed-bottom .nav-link {
        color: #6c757d;
        font-size: 0.8rem;
    }
    
    .navbar.fixed-bottom .nav-link.active {
        color: #0d6efd;
    }
    
    .navbar.fixed-bottom i {
        font-size: 1.2rem;
    }

    /* Style pour le menu déroulant du bouton + */
    .navbar.fixed-bottom .dropup .dropdown-menu {
        bottom: 100%;
        margin-bottom: 0.5rem;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }

    .navbar.fixed-bottom .dropdown-item {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter la classe active au lien actif
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar.fixed-bottom .nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath.split('/').pop()) {
            link.classList.add('active');
        }
    });
});
</script>
