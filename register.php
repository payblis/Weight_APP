<?php
require_once 'includes/config.php';

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $height = floatval($_POST['height']);
    $current_weight = floatval($_POST['current_weight']);
    $target_weight = floatval($_POST['target_weight']);
    $target_weeks = intval($_POST['target_weeks']);
    $activity_level = $_POST['activity_level'];
    $age = intval($_POST['age']);
    $gender = $_POST['gender'];

    $errors = [];

    // Validations
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est requis";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    if ($target_weeks < 1) {
        $errors[] = "La durée doit être d'au moins 1 semaine";
    }
    if ($current_weight <= 0 || $target_weight <= 0) {
        $errors[] = "Les poids doivent être supérieurs à 0";
    }

    // Vérification si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Cet email est déjà utilisé";
    }

    if (empty($errors)) {
        try {
            // Hashage du mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Calcul du BMR (Basal Metabolic Rate)
            if ($gender === 'M') {
                $bmr = (10 * $current_weight) + (6.25 * $height) - (5 * $age) + 5;
            } else {
                $bmr = (10 * $current_weight) + (6.25 * $height) - (5 * $age) - 161;
            }

            // Insertion de l'utilisateur
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, height, current_weight, 
                target_weight, target_weeks, activity_level, age, gender, bmr)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $username, $email, $hashed_password, $height, $current_weight,
                $target_weight, $target_weeks, $activity_level, $age, $gender, $bmr
            ]);

            $user_id = $pdo->lastInsertId();

            // Création de l'objectif initial
            $stmt = $pdo->prepare("
                INSERT INTO weight_goals (user_id, start_weight, target_weight, 
                start_date, target_date, weekly_goal)
                VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? WEEK), ?)
            ");

            $weekly_goal = ($current_weight - $target_weight) / $target_weeks;
            $stmt->execute([
                $user_id, $current_weight, $target_weight, 
                $target_weeks, $weekly_goal
            ]);

            // Connexion automatique
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;

            // Redirection vers le tableau de bord
            header('Location: dashboard.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}

include 'components/header.php';
?>

<div class="register-container">
    <div class="register-form-container">
        <h1>Créez votre compte</h1>
        <p class="subtitle">Commencez votre parcours vers une meilleure santé</p>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" class="register-form" id="registerForm">
            <div class="form-section">
                <h3>Informations de compte</h3>
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" class="form-control" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
            </div>

            <div class="form-section">
                <h3>Informations personnelles</h3>
                <div class="form-group">
                    <label for="age">Âge</label>
                    <input type="number" id="age" name="age" class="form-control" required min="18" max="100"
                           value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Genre</label>
                    <select id="gender" name="gender" class="form-control" required>
                        <option value="">Sélectionnez</option>
                        <option value="M" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'M') ? 'selected' : ''; ?>>Homme</option>
                        <option value="F" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'F') ? 'selected' : ''; ?>>Femme</option>
                        <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'other') ? 'selected' : ''; ?>>Autre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="height">Taille (cm)</label>
                    <input type="number" id="height" name="height" class="form-control" required min="100" max="250" step="0.1"
                           value="<?php echo isset($_POST['height']) ? htmlspecialchars($_POST['height']) : ''; ?>">
                </div>
            </div>

            <div class="form-section">
                <h3>Objectifs de poids</h3>
                <div class="form-group">
                    <label for="current_weight">Poids actuel (kg)</label>
                    <input type="number" id="current_weight" name="current_weight" class="form-control" required min="30" max="300" step="0.1"
                           value="<?php echo isset($_POST['current_weight']) ? htmlspecialchars($_POST['current_weight']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="target_weight">Poids cible (kg)</label>
                    <input type="number" id="target_weight" name="target_weight" class="form-control" required min="30" max="300" step="0.1"
                           value="<?php echo isset($_POST['target_weight']) ? htmlspecialchars($_POST['target_weight']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="target_weeks">Durée souhaitée (semaines)</label>
                    <input type="number" id="target_weeks" name="target_weeks" class="form-control" required min="1" max="52"
                           value="<?php echo isset($_POST['target_weeks']) ? htmlspecialchars($_POST['target_weeks']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="activity_level">Niveau d'activité</label>
                    <select id="activity_level" name="activity_level" class="form-control" required>
                        <option value="">Sélectionnez</option>
                        <option value="sedentary" <?php echo (isset($_POST['activity_level']) && $_POST['activity_level'] === 'sedentary') ? 'selected' : ''; ?>>
                            Sédentaire (peu ou pas d'exercice)
                        </option>
                        <option value="light" <?php echo (isset($_POST['activity_level']) && $_POST['activity_level'] === 'light') ? 'selected' : ''; ?>>
                            Légèrement actif (exercice léger 1-3 fois/semaine)
                        </option>
                        <option value="moderate" <?php echo (isset($_POST['activity_level']) && $_POST['activity_level'] === 'moderate') ? 'selected' : ''; ?>>
                            Modérément actif (exercice modéré 3-5 fois/semaine)
                        </option>
                        <option value="very_active" <?php echo (isset($_POST['activity_level']) && $_POST['activity_level'] === 'very_active') ? 'selected' : ''; ?>>
                            Très actif (exercice intense 6-7 fois/semaine)
                        </option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>

        <p class="login-link">
            Déjà un compte ? <a href="login.php">Connectez-vous</a>
        </p>
    </div>
</div>

<style>
.register-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.register-form-container {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.register-form-container h1 {
    text-align: center;
    margin-bottom: 0.5rem;
}

.subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 2rem;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h3 {
    margin-bottom: 1.5rem;
    color: var(--primary-color);
}

.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background-color: #FFEBEE;
    color: #C62828;
    border: 1px solid #FFCDD2;
}

.alert ul {
    margin: 0;
    padding-left: 1.5rem;
}

.btn-block {
    width: 100%;
    margin-top: 1rem;
}

.login-link {
    text-align: center;
    margin-top: 1.5rem;
}

.login-link a {
    color: var(--primary-color);
    text-decoration: none;
}

.login-link a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .register-form-container {
        padding: 1.5rem;
    }
}
</style>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const currentWeight = parseFloat(document.getElementById('current_weight').value);
    const targetWeight = parseFloat(document.getElementById('target_weight').value);
    const targetWeeks = parseInt(document.getElementById('target_weeks').value);
    
    // Calcul de la perte de poids hebdomadaire
    const weeklyLoss = (currentWeight - targetWeight) / targetWeeks;
    
    // Si la perte de poids est supérieure à 1kg par semaine
    if (weeklyLoss > 1) {
        e.preventDefault();
        if (confirm('Attention : Une perte de poids supérieure à 1kg par semaine peut être dangereuse pour votre santé. Voulez-vous ajuster votre objectif ?')) {
            // Suggestion d'une durée plus saine
            const recommendedWeeks = Math.ceil(Math.abs(currentWeight - targetWeight));
            document.getElementById('target_weeks').value = recommendedWeeks;
        }
    }
});
</script>

<?php include 'components/footer.php'; ?> 