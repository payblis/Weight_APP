<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    
    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="app-header">
        <nav class="main-nav">
            <div class="nav-brand">
                <a href="<?php echo APP_URL; ?>">
                    <img src="<?php echo APP_URL; ?>/assets/images/logo.png" alt="<?php echo APP_NAME; ?>" class="logo">
                </a>
            </div>
            
            <?php if (isLoggedIn()): ?>
            <div class="nav-links">
                <a href="<?php echo APP_URL; ?>/dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
                <a href="<?php echo APP_URL; ?>/diary.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>Journal</span>
                </a>
                <a href="<?php echo APP_URL; ?>/progress.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Progrès</span>
                </a>
                <a href="<?php echo APP_URL; ?>/goals.php" class="nav-item">
                    <i class="fas fa-bullseye"></i>
                    <span>Objectifs</span>
                </a>
                <a href="<?php echo APP_URL; ?>/ai-coach.php" class="nav-item">
                    <i class="fas fa-robot"></i>
                    <span>Coach IA</span>
                </a>
            </div>
            
            <div class="nav-user">
                <div class="dropdown">
                    <button class="dropdown-toggle">
                        <i class="fas fa-user-circle"></i>
                        <span>Mon Compte</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?php echo APP_URL; ?>/profile.php">
                            <i class="fas fa-user"></i> Profil
                        </a>
                        <a href="<?php echo APP_URL; ?>/settings.php">
                            <i class="fas fa-cog"></i> Paramètres
                        </a>
                        <a href="<?php echo APP_URL; ?>/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="nav-auth">
                <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-login">Connexion</a>
                <a href="<?php echo APP_URL; ?>/register.php" class="btn btn-register">Inscription</a>
            </div>
            <?php endif; ?>
        </nav>
    </header>
    
    <main class="app-main"><?php if (isset($_SESSION['flash'])): ?>
        <div class="flash-message <?php echo $_SESSION['flash']['type']; ?>">
            <?php 
            echo $_SESSION['flash']['message'];
            unset($_SESSION['flash']);
            ?>
        </div>
    <?php endif; ?> 