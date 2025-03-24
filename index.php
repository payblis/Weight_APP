<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack - Application de Suivi de Perte de Poids</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">FitTrack</div>
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu">
                <li><a href="index.php">Accueil</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">Tableau de bord</a></li>
                    <li><a href="weight-log.php">Suivi du poids</a></li>
                    <li><a href="activities.php">Activités</a></li>
                    <li><a href="meals.php">Repas</a></li>
                    <li><a href="profile.php">Profil</a></li>
                    <li><a href="api/logout.php">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="login.php">Connexion</a></li>
                    <li><a href="register.php">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <main>
        <section class="hero bg-primary">
            <div class="container text-center p-4">
                <h1>Atteignez vos objectifs de perte de poids</h1>
                <p class="mt-2 mb-3">Suivez votre poids, vos activités et recevez des recommandations personnalisées</p>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-secondary">Commencer gratuitement</a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-secondary">Accéder à mon tableau de bord</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <h2 class="section-title">Comment ça fonctionne</h2>
                <div class="dashboard-grid">
                    <div class="card text-center">
                        <i class="fas fa-user-plus fa-3x text-primary mb-2"></i>
                        <h3 class="card-title">1. Créez votre profil</h3>
                        <p>Renseignez votre poids actuel, votre objectif et vos informations personnelles.</p>
                    </div>
                    <div class="card text-center">
                        <i class="fas fa-weight fa-3x text-primary mb-2"></i>
                        <h3 class="card-title">2. Suivez votre progression</h3>
                        <p>Enregistrez quotidiennement votre poids et vos activités physiques.</p>
                    </div>
                    <div class="card text-center">
                        <i class="fas fa-utensils fa-3x text-primary mb-2"></i>
                        <h3 class="card-title">3. Recevez des recommandations</h3>
                        <p>Obtenez des suggestions de repas et d'exercices adaptés à vos objectifs.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section bg-light">
            <div class="container">
                <h2 class="section-title">Fonctionnalités</h2>
                <div class="dashboard-grid">
                    <div class="card">
                        <h3 class="card-title"><i class="fas fa-chart-line text-primary"></i> Suivi du poids</h3>
                        <p>Enregistrez votre poids quotidiennement et visualisez votre progression sur des graphiques clairs.</p>
                    </div>
                    <div class="card">
                        <h3 class="card-title"><i class="fas fa-running text-primary"></i> Activités physiques</h3>
                        <p>Suivez vos activités physiques et les calories brûlées pour atteindre vos objectifs.</p>
                    </div>
                    <div class="card">
                        <h3 class="card-title"><i class="fas fa-apple-alt text-primary"></i> Recommandations alimentaires</h3>
                        <p>Recevez des suggestions de repas équilibrés adaptés à vos besoins caloriques.</p>
                    </div>
                    <div class="card">
                        <h3 class="card-title"><i class="fas fa-dumbbell text-primary"></i> Programmes d'exercices</h3>
                        <p>Accédez à des programmes d'exercices personnalisés pour maximiser vos résultats.</p>
                    </div>
                    <div class="card">
                        <h3 class="card-title"><i class="fas fa-camera text-primary"></i> Analyse morphologique</h3>
                        <p>Importez une photo pour recevoir des recommandations ciblées selon votre morphologie.</p>
                    </div>
                    <div class="card">
                        <h3 class="card-title"><i class="fas fa-robot text-primary"></i> Intelligence artificielle</h3>
                        <p>Bénéficiez de l'IA pour des recommandations toujours plus personnalisées.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container text-center">
                <h2 class="section-title">Prêt à commencer votre transformation ?</h2>
                <p class="mb-3">Rejoignez des milliers d'utilisateurs qui ont atteint leurs objectifs de perte de poids</p>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-primary">S'inscrire gratuitement</a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-primary">Accéder à mon tableau de bord</a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title">FitTrack</h3>
                    <p>Votre partenaire pour atteindre vos objectifs de perte de poids et maintenir un mode de vie sain.</p>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">Liens rapides</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="about.php">À propos</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="privacy.php">Politique de confidentialité</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">Nous contacter</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> contact@fittrack.com</li>
                        <li><i class="fas fa-phone"></i> +33 1 23 45 67 89</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 FitTrack. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
