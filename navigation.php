<?php
// Fichier de navigation commun à inclure dans toutes les pages
// Détermine quelle page est active
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION)) {
    session_start();
}

// Récupérer le solde de crédits si l'utilisateur est connecté
$userCredits = 0;
if (isset($_SESSION['user_id'])) {
    // Inclure les fonctions de crédits si elles existent
    if (file_exists('includes/credit_functions.php')) {
        require_once 'includes/credit_functions.php';
        $userCredits = CreditManager::getUserCredits($_SESSION['user_id'])['credits_balance'] ?? 0;
    }
}

// Définir les sous-menus pour chaque section
$submenus = [
    'dashboard.php' => [],
    'food-log.php' => [
        ['title' => 'Journal alimentaire', 'url' => 'food-log.php'],
        ['title' => 'Gestion des aliments', 'url' => 'food-management.php'],
        ['title' => 'Préférences alimentaires', 'url' => 'preferences.php']
    ],
    'exercise-log.php' => [
        ['title' => 'Journal d\'exercices', 'url' => 'exercise-log.php'],
        ['title' => 'Programmes d\'entraînement', 'url' => 'programs.php']
    ],
    'reports.php' => [
        ['title' => 'Suivi de poids', 'url' => 'weight-log.php'],
        ['title' => 'Historique calorique', 'url' => 'calorie-history.php'],
        ['title' => 'Rapports & Statistiques', 'url' => 'reports.php']
    ],
    'goals.php' => [
        ['title' => 'Mes objectifs', 'url' => 'goals.php'],
        ['title' => 'Progression', 'url' => 'goals-progress.php']
    ],
    'premium.php' => [
        ['title' => 'Abonnement Premium', 'url' => 'premium.php'],
        ['title' => 'Mon Abonnement', 'url' => 'my-subscription.php'],
        ['title' => 'Acheter des Crédits', 'url' => 'buy-credits.php'],
        ['title' => 'Mes Crédits IA', 'url' => 'my-credits.php']
    ],
    'suggestions.php' => [
        ['title' => 'Suggestions personnalisées', 'url' => 'suggestions.php']
    ]
];

// Déterminer le menu actif et ses sous-menus
$active_menu = 'dashboard.php';
foreach ($submenus as $menu => $items) {
    foreach ($items as $item) {
        if ($item['url'] === $current_page) {
            $active_menu = $menu;
            break 2;
        }
    }
}
?>

<!-- Barre supérieure avec logo et paramètres -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom d-none d-lg-block top-nav">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <img src="./assets/icons/icon-96x96.png" alt="MyFity" class="me-2" style="height: 30px; width: auto;">MyFity
        </a>
        <div class="d-flex align-items-center">
            <!-- Solde de crédits -->
            <a href="my-credits.php" class="nav-link position-relative me-3 credits-balance">
                <i class="fas fa-coins text-warning me-1"></i>
                <span class="credits-amount"><?php echo $userCredits; ?></span>
                <span class="credits-label">crédits</span>
            </a>
            
            <a href="messages.php" class="nav-link position-relative me-3">
                <i class="far fa-envelope"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
            </a>
            <a href="profile.php" class="nav-link me-3">
                <i class="far fa-user me-1"></i>
            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Utilisateur'); ?>
            </a>
            <a href="settings.php" class="nav-link me-3">Paramètres</a>
            <a href="logout.php" class="nav-link">Déconnexion</a>
        </div>
    </div>
</nav>

<!-- Barre de navigation principale -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary d-none d-lg-block main-nav">
    <div class="container">
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-home me-1"></i>Ma Journée
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['food-log.php', 'food-management.php', 'preferences.php']) ? 'active' : ''; ?>" href="food-log.php">
                        <i class="fas fa-utensils me-1"></i>Aliments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['exercise-log.php', 'programs.php']) ? 'active' : ''; ?>" href="exercise-log.php">
                        <i class="fas fa-dumbbell me-1"></i>Exercices
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['weight-log.php', 'calorie-history.php', 'reports.php']) ? 'active' : ''; ?>" href="reports.php">
                        <i class="fas fa-chart-bar me-1"></i>Rapports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'goals.php' ? 'active' : ''; ?>" href="goals.php">
                        <i class="fas fa-bullseye me-1"></i>Objectifs
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['premium.php', 'my-subscription.php', 'buy-credits.php', 'my-credits.php']) ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-gem me-1"></i>Premium
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="premium.php"><i class="fas fa-crown me-2"></i>Abonnement Premium</a></li>
                        <li><a class="dropdown-item" href="my-subscription.php"><i class="fas fa-credit-card me-2"></i>Mon Abonnement</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="buy-credits.php"><i class="fas fa-coins me-2"></i>Acheter des Crédits</a></li>
                        <li><a class="dropdown-item" href="my-credits.php"><i class="fas fa-robot me-2"></i>Mes Crédits IA</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'suggestions' ? 'active' : ''; ?>" href="suggestions.php?type=alimentation">
                        <i class="fas fa-robot me-1"></i>Mon Coach
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Sous-navigation -->
<?php if (isset($submenus[$active_menu]) && !empty($submenus[$active_menu])): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-dark d-none d-lg-block sub-nav">
    <div class="container">
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav">
                <?php foreach ($submenus[$active_menu] as $item): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === $item['url'] ? 'active' : ''; ?>" href="<?php echo $item['url']; ?>">
                        <?php echo $item['title']; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<!-- Navigation mobile (bottom bar) -->
<nav class="navbar fixed-bottom navbar-light bg-white border-top d-lg-none">
    <div class="container-fluid p-0">
        <!-- Barre de crédits mobile -->
        <div class="w-100 bg-warning text-dark py-1 text-center" style="font-size: 0.8rem;">
            <i class="fas fa-coins me-1"></i>
            <strong><?php echo $userCredits; ?></strong> crédits disponibles
            <a href="buy-credits.php" class="ms-2 text-decoration-none" style="color: #856404;">
                <i class="fas fa-plus-circle"></i>
            </a>
        </div>
        
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
                <div class="dropup">
                    <a class="nav-link py-2" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h d-block mb-1"></i>
                        <small>Autres</small>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="preferences.php"><i class="fas fa-sliders-h me-2"></i>Préférences</a></li>
                        <li><a class="dropdown-item" href="programs.php"><i class="fas fa-list me-2"></i>Programmes</a></li>
                        <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Rapports</a></li>
                        <li><a class="dropdown-item" href="calorie-history.php"><i class="fas fa-fire me-2"></i>Historique calorique</a></li>
                        <li><a class="dropdown-item" href="weight-log.php"><i class="fas fa-weight me-2"></i>Suivi de poids</a></li>
                        <li><a class="dropdown-item" href="goals.php"><i class="fas fa-bullseye me-2"></i>Objectifs</a></li>
                        <li><a class="dropdown-item" href="premium.php"><i class="fas fa-crown me-2"></i>Abonnement Premium</a></li>
                        <li><a class="dropdown-item" href="my-subscription.php"><i class="fas fa-credit-card me-2"></i>Mon Abonnement</a></li>
                        <li><a class="dropdown-item" href="buy-credits.php"><i class="fas fa-coins me-2"></i>Acheter des Crédits</a></li>
                        <li><a class="dropdown-item" href="my-credits.php"><i class="fas fa-robot me-2"></i>Mes Crédits IA</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
/* Styles pour la navigation desktop */
.top-nav {
    padding: 0.5rem 0;
}

.top-nav .nav-link {
    color: #666;
    font-size: 0.9rem;
}

/* Styles pour l'affichage du solde de crédits */
.credits-balance {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border: 1px solid #ffc107;
    border-radius: 20px;
    padding: 0.5rem 1rem !important;
    color: #856404 !important;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
}

.credits-balance:hover {
    background: linear-gradient(135deg, #ffeaa7, #fff3cd);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
    color: #856404 !important;
    text-decoration: none;
}

.credits-amount {
    font-size: 1.1rem;
    font-weight: bold;
    color: #d68910;
}

.credits-label {
    font-size: 0.8rem;
    opacity: 0.8;
}

.top-nav .navbar-brand {
    color: #0066ee;
    font-weight: bold;
}

.main-nav {
    background-color: #0066ee !important;
    padding: 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sub-nav {
    background-color: #004fc4 !important;
    padding: 0;
}

.main-nav .nav-link,
.sub-nav .nav-link {
    padding: 1rem 1.25rem !important;
    font-size: 0.9rem;
    font-weight: 600;
    color: white !important;
}

.main-nav .nav-link:hover,
.main-nav .nav-link.active,
.sub-nav .nav-link:hover,
.sub-nav .nav-link.active {
    background-color: rgba(255,255,255,0.1);
}

/* Styles pour le menu déroulant Premium */
.main-nav .dropdown-menu {
    background-color: #0066ee;
    border: none;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-top: 0;
}

.main-nav .dropdown-item {
    color: white;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.main-nav .dropdown-item:hover {
    background-color: rgba(255,255,255,0.1);
    color: white;
}

.main-nav .dropdown-divider {
    border-color: rgba(255,255,255,0.2);
    margin: 0.5rem 0;
}

/* Ajuster le padding du body pour la barre de navigation fixe en bas */
@media (max-width: 991.98px) {
    body {
        padding-bottom: 105px !important;
    }
    
    /* Styles pour la barre de crédits mobile */
    .navbar.fixed-bottom .bg-warning {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7) !important;
        border-bottom: 1px solid #ffc107;
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
