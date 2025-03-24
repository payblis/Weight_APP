<?php
require_once 'includes/user_config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weight APP - Tableau de bord</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/user.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="fas fa-weight me-2"></i>Weight APP
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="badge bg-danger" id="notificationCount"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" id="notificationList">
                            <!-- Les notifications seront ajoutées ici dynamiquement -->
                        </div>
                    </li>
                    
                    <!-- Menu utilisateur -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="/profile.php">
                                <i class="fas fa-user-cog me-2"></i>Profil
                            </a>
                            <a class="dropdown-item" href="/settings.php">
                                <i class="fas fa-cog me-2"></i>Paramètres
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <?php foreach (USER_DASHBOARD_SECTIONS as $key => $section): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === $key . '.php' ? 'active' : ''; ?>" 
                           href="/<?php echo $key; ?>.php">
                            <i class="<?php echo $section['icon']; ?> me-2"></i>
                            <?php echo $section['title']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Contenu principal -->
    <main class="main-content">
        <!-- Container pour les notifications toast -->
        <div class="toast-container position-fixed top-0 end-0 p-3"></div> 