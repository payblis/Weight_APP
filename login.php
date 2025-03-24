<?php
session_start();
// Rediriger si déjà connecté
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - FitTrack</title>
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
                <li><a href="login.php">Connexion</a></li>
                <li><a href="register.php">Inscription</a></li>
            </ul>
        </div>
    </header>

    <main>
        <section class="section">
            <div class="container">
                <h2 class="section-title">Connexion</h2>
                <div class="card" style="max-width: 500px; margin: 0 auto;">
                    <form id="loginForm" action="api/login.php" method="post">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
                        </div>
                        <div class="text-center mt-3">
                            <p>Vous n'avez pas de compte ? <a href="register.php">Inscrivez-vous</a></p>
                        </div>
                    </form>
                </div>
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
