<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MyFity</title>

    <meta name="description" content="Application de suivi de poids et de nutrition">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/icons/icon-72x72.png">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    <style>
        .main-header {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .main-nav {
            background-color: #0066ee;
        }
        .main-nav .nav-link {
            color: white !important;
            padding: 1rem 1.5rem !important;
            font-weight: 500;
        }
        .main-nav .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .notification-badge {
            background-color: #ff0000;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            position: absolute;
            top: -5px;
            right: -5px;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .user-menu a {
            color: #666;
            text-decoration: none;
        }
        .logo {
            color: #0066ee;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Header principal -->
    <header class="main-header">
        <div class="container py-2 d-flex justify-content-between align-items-center">
            <a href="index.php" class="logo">
                MyFity
            </a>
            <div class="user-menu">
                <a href="#" class="position-relative">
                    <i class="far fa-envelope fa-lg"></i>
                    <span class="notification-badge">1</span>
                </a>
                <a href="profile.php">
                    <i class="far fa-user fa-lg"></i>
                </a>
                <a href="settings.php">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation principale -->
    <nav class="main-nav">
        <div class="container">
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">MON ACCUEIL</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="food-log.php">ALIMENTS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="exercise-log.php">EXERCICES</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">RAPPORTS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="apps.php">APPLIS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="community.php">COMMUNAUTÉ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="blog.php">BLOG</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="premium.php">PREMIUM</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 