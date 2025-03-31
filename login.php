<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialiser les variables
$email = $password = '';
$errors = [];
$success_message = '';

// Vérifier s'il y a un message de succès dans la session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Validation des données
    if (empty($email)) {
        $errors[] = "L'adresse e-mail est requise";
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    }
    
    // Si aucune erreur, tenter la connexion
    if (empty($errors)) {
        // Rechercher l'utilisateur dans la base de données
        $sql = "SELECT id, username, email, password FROM users WHERE email = ?";
        $user = fetchOne($sql, [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie, créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            
            // Si "Se souvenir de moi" est coché, créer un cookie
            if ($remember_me) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 jours
                
                // Vérifier si la table remember_tokens existe
                $check_table = "SHOW TABLES LIKE 'remember_tokens'";
                $table_exists = fetchOne($check_table);
                
                if ($table_exists) {
                    // Stocker le token dans la base de données
                    $sql = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))";
                    insert($sql, [$user['id'], $token, $expires]);
                    
                    // Créer le cookie
                    setcookie('remember_token', $token, $expires, '/', '', false, true);
                }
            }
            
            // Rediriger vers le tableau de bord
            redirect('dashboard.php');
        } else {
            $errors[] = "Adresse e-mail ou mot de passe incorrect";
        }
    }
}

// Vérifier si l'utilisateur a un cookie de connexion automatique
if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Vérifier si la table remember_tokens existe
    $check_table = "SHOW TABLES LIKE 'remember_tokens'";
    $table_exists = fetchOne($check_table);
    
    if ($table_exists) {
        // Rechercher le token dans la base de données
        $sql = "SELECT rt.user_id, u.username, u.email 
                FROM remember_tokens rt 
                JOIN users u ON rt.user_id = u.id 
                WHERE rt.token = ? AND rt.expires_at > NOW()";
        $token_data = fetchOne($sql, [$token]);
        
        if ($token_data) {
            // Token valide, créer la session
            $_SESSION['user_id'] = $token_data['user_id'];
            $_SESSION['username'] = $token_data['username'];
            $_SESSION['email'] = $token_data['email'];
            $_SESSION['logged_in'] = true;
            
            // Rediriger vers le tableau de bord
            redirect('dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h2 class="mb-0"><i class="fas fa-weight me-2"></i>MyFity</h2>
                        <p class="mb-0">Connectez-vous pour accéder à votre compte</p>
                    </div>
                    <div class="card-body p-4">
                        <!-- Messages d'erreur -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Message de succès -->
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Formulaire de connexion -->
                        <form action="login.php" method="POST" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse e-mail</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                                <label class="form-check-label" for="remember_me">Se souvenir de moi</label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Se connecter</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <p class="mb-0">Vous n'avez pas de compte ? <a href="register.php" class="text-primary">Inscrivez-vous</a></p>
                    </div>
                </div>
                
                <!-- Témoignages -->
                <div class="card mt-4 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Ce que disent nos utilisateurs</h5>
                        <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner py-3">
                                <div class="carousel-item active">
                                    <div class="testimonial text-center">
                                        <p class="mb-3">"Grâce à Weight Tracker, j'ai perdu 15 kg en 6 mois. L'application m'a aidé à rester motivé et à suivre mes progrès jour après jour."</p>
                                        <div class="testimonial-author">
                                            <div class="rating text-warning mb-2">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <h6 class="mb-0">Thomas D.</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <div class="testimonial text-center">
                                        <p class="mb-3">"Les recommandations de repas sont excellentes et adaptées à mes préférences. Je n'ai jamais mangé aussi sainement tout en me faisant plaisir !"</p>
                                        <div class="testimonial-author">
                                            <div class="rating text-warning mb-2">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star-half-alt"></i>
                                            </div>
                                            <h6 class="mb-0">Sophie M.</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <div class="testimonial text-center">
                                        <p class="mb-3">"Le suivi des exercices est très précis et m'aide à rester motivé. J'apprécie particulièrement les graphiques qui montrent mes progrès."</p>
                                        <div class="testimonial-author">
                                            <div class="rating text-warning mb-2">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <h6 class="mb-0">Lucas P.</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="carousel-indicators position-relative mt-3">
                                <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonctionnalité pour afficher/masquer le mot de passe
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
