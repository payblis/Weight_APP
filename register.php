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
    <title>Inscription - FitTrack</title>
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
                <h2 class="section-title">Créer un compte</h2>
                <div class="card" style="max-width: 600px; margin: 0 auto;">
                    <form id="registerForm" action="api/register.php" method="post">
                        <div class="form-group">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <h3 class="card-title mt-4">Informations personnelles</h3>
                        
                        <div class="form-group">
                            <label for="gender" class="form-label">Genre</label>
                            <select id="gender" name="gender" class="form-select" required>
                                <option value="">Sélectionnez</option>
                                <option value="homme">Homme</option>
                                <option value="femme">Femme</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="age" class="form-label">Âge</label>
                            <input type="number" id="age" name="age" class="form-control" min="18" max="100" required>
                        </div>
                        <div class="form-group">
                            <label for="height" class="form-label">Taille (cm)</label>
                            <input type="number" id="height" name="height" class="form-control" min="100" max="250" required>
                        </div>
                        <div class="form-group">
                            <label for="initial_weight" class="form-label">Poids actuel (kg)</label>
                            <input type="number" id="initial_weight" name="initial_weight" class="form-control" min="30" max="300" step="0.1" required>
                        </div>
                        <div class="form-group">
                            <label for="target_weight" class="form-label">Poids cible (kg)</label>
                            <input type="number" id="target_weight" name="target_weight" class="form-control" min="30" max="300" step="0.1" required>
                        </div>
                        <div class="form-group">
                            <label for="activity_level" class="form-label">Niveau d'activité</label>
                            <select id="activity_level" name="activity_level" class="form-select" required>
                                <option value="">Sélectionnez</option>
                                <option value="sédentaire">Sédentaire (peu ou pas d'exercice)</option>
                                <option value="légèrement actif">Légèrement actif (exercice léger 1-3 jours/semaine)</option>
                                <option value="modérément actif">Modérément actif (exercice modéré 3-5 jours/semaine)</option>
                                <option value="très actif">Très actif (exercice intense 6-7 jours/semaine)</option>
                                <option value="extrêmement actif">Extrêmement actif (exercice très intense et travail physique)</option>
                            </select>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary btn-block">S'inscrire</button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <p>Vous avez déjà un compte ? <a href="login.php">Connectez-vous</a></p>
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
