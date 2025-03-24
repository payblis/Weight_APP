<?php
require_once 'includes/config.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $errors = [];

    if (empty($email) || empty($password)) {
        $errors[] = "Tous les champs sont requis";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($user = $stmt->fetch()) {
                if (password_verify($password, $user['password'])) {
                    // Connexion réussie
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // Si "Se souvenir de moi" est coché
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + 60*60*24*30, '/', '', true, true);
                        
                        // Stockage du token en base
                        $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $stmt->execute([$token, $user['id']]);
                    }

                    header('Location: dashboard.php');
                    exit();
                } else {
                    $errors[] = "Email ou mot de passe incorrect";
                }
            } else {
                $errors[] = "Email ou mot de passe incorrect";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la connexion";
        }
    }
}

include 'components/header.php';
?>

<div class="login-container">
    <div class="login-form-container">
        <h1>Connexion</h1>
        <p class="subtitle">Bienvenue sur <?php echo APP_NAME; ?></p>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group form-check">
                <input type="checkbox" id="remember" name="remember" class="form-check-input"
                       <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                <label for="remember" class="form-check-label">Se souvenir de moi</label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>

        <div class="login-footer">
            <p>
                <a href="forgot-password.php">Mot de passe oublié ?</a>
            </p>
            <p>
                Pas encore de compte ? <a href="register.php">Inscrivez-vous</a>
            </p>
        </div>
    </div>
</div>

<style>
.login-container {
    max-width: 400px;
    margin: 4rem auto;
    padding: 0 1rem;
}

.login-form-container {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.login-form-container h1 {
    text-align: center;
    margin-bottom: 0.5rem;
}

.subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 2rem;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.form-check-input {
    margin: 0;
}

.form-check-label {
    margin: 0;
    cursor: pointer;
}

.login-footer {
    margin-top: 2rem;
    text-align: center;
}

.login-footer p {
    margin: 0.5rem 0;
}

.login-footer a {
    color: var(--primary-color);
    text-decoration: none;
}

.login-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .login-container {
        margin: 2rem auto;
    }
    
    .login-form-container {
        padding: 1.5rem;
    }
}
</style>

<?php include 'components/footer.php'; ?> 