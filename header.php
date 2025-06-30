<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MyFity - Suivez votre alimentation et vos objectifs</title>

    <meta name="description" content="Application de suivi de poids et de nutrition">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/icons/icon-72x72.png">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0066ee;
            --secondary-color: #0056d6;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .app-header {
            background-color: white;
            border-bottom: 1px solid #e5e5e5;
            padding: 0.5rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .app-nav {
            background-color: var(--primary-color);
            padding: 0;
        }

        .app-nav .nav-link {
            color: white !important;
            padding: 0.75rem 1.25rem !important;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .app-nav .nav-link:hover,
        .app-nav .nav-link.active {
            background-color: rgba(255,255,255,0.1);
        }

        .user-controls {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-controls a {
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .notification-count {
            background-color: #ff0000;
            color: white;
            border-radius: 50%;
            padding: 0.15rem 0.4rem;
            font-size: 0.75rem;
            position: absolute;
            top: -5px;
            right: -5px;
        }

        .logo {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo:hover {
            color: var(--secondary-color);
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .hero {
            background-color: var(--primary-color);
            padding: 4rem 0;
        }

        .card {
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .testimonial-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }

        .language-selector {
            margin-left: 1rem;
        }

        .language-selector .dropdown-menu {
            min-width: 120px;
        }

        .language-selector .dropdown-item.active {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION)) { session_start(); } ?>
    
    <!-- Inclure le système de traduction -->
    <?php require_once 'includes/translation.php'; ?>
    
    <!-- Header principal -->
    <header class="app-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php'; ?>" class="logo">
                <i class="fas fa-heartbeat"></i>
                MyFity
            </a>
            <div class="user-controls">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="messages.php" class="position-relative">
                        <i class="far fa-envelope"></i>
                        <span class="notification-count">1</span>
                    </a>
                    <a href="profile.php" class="d-flex align-items-center">
                        <i class="far fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        Paramètres
                    </a>
                    <a href="logout.php" class="text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        Déconnexion
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary me-2">Se connecter</a>
                    <a href="register.php" class="btn btn-primary">S'inscrire</a>
                <?php endif; ?>
                
                <!-- Sélecteur de langue -->
                <?php echo getLanguageSelector(); ?>
            </div>
        </div>
    </header>

    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Navigation principale -->
    <nav class="app-nav">
        <div class="container">
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-home me-1"></i>Mon Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'food-log.php' ? 'active' : ''; ?>" href="food-log.php">
                        <i class="fas fa-utensils me-1"></i>Aliments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'exercise-log.php' ? 'active' : ''; ?>" href="exercise-log.php">
                        <i class="fas fa-dumbbell me-1"></i>Exercices
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                        <i class="fas fa-chart-bar me-1"></i>Rapports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'apps.php' ? 'active' : ''; ?>" href="apps.php">
                        <i class="fas fa-mobile-alt me-1"></i>Applis
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'community.php' ? 'active' : ''; ?>" href="community.php">
                        <i class="fas fa-users me-1"></i>Communauté
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'blog.php' ? 'active' : ''; ?>" href="blog.php">
                        <i class="fas fa-newspaper me-1"></i>Blog
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'premium.php' ? 'active' : ''; ?>" href="premium.php">
                        <i class="fas fa-crown me-1"></i>Premium
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 