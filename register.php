<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialiser les variables
$username = $email = $password = $confirm_password = '';
$first_name = $last_name = $gender = $birth_date = $height = $weight = $activity_level = '';
$errors = [];
$success_message = '';

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $birth_date = sanitizeInput($_POST['birth_date'] ?? '');
    $height = sanitizeInput($_POST['height'] ?? '');
    $weight = sanitizeInput($_POST['weight'] ?? '');
    $activity_level = sanitizeInput($_POST['activity_level'] ?? '');
    
    // Validation des données
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est requis";
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $errors[] = "Le nom d'utilisateur doit contenir entre 3 et 30 caractères";
    }
    
    if (empty($email)) {
        $errors[] = "L'adresse e-mail est requise";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse e-mail n'est pas valide";
    } else {
        // Vérifier si l'email existe déjà
        $sql = "SELECT id FROM users WHERE email = ?";
        $user = fetchOne($sql, [$email]);
        
        if ($user) {
            $errors[] = "Cette adresse e-mail est déjà utilisée";
        }
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    // Validation des informations personnelles
    if (empty($first_name)) {
        $errors[] = "Le prénom est requis";
    }
    
    if (empty($last_name)) {
        $errors[] = "Le nom est requis";
    }
    
    if (empty($gender)) {
        $errors[] = "Le genre est requis";
    }
    
    if (empty($birth_date)) {
        $errors[] = "La date de naissance est requise";
    } elseif (!validateDate($birth_date)) {
        $errors[] = "La date de naissance n'est pas valide";
    }
    
    if (empty($height)) {
        $errors[] = "La taille est requise";
    } elseif (!is_numeric($height) || $height < 100 || $height > 250) {
        $errors[] = "La taille doit être un nombre entre 100 et 250 cm";
    }
    
    if (empty($weight)) {
        $errors[] = "Le poids de départ est requis";
    } elseif (!is_numeric($weight) || $weight < 30 || $weight > 300) {
        $errors[] = "Le poids doit être un nombre entre 30 et 300 kg";
    }
    
    if (empty($activity_level)) {
        $errors[] = "Le niveau d'activité est requis";
    }
    
    // Si aucune erreur, créer le compte
    if (empty($errors)) {
        try {
            // Vérifier si la table users existe
            if (!tableExists('users')) {
                $errors[] = "La table des utilisateurs n'existe pas. Veuillez importer le fichier database.sql.";
            } else {
                // Hacher le mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insérer l'utilisateur dans la base de données
                $sql = "INSERT INTO users (username, email, password, first_name, last_name, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                $user_id = insert($sql, [$username, $email, $hashed_password, $first_name, $last_name]);
                
                if ($user_id > 0) {
                    // L'insertion a réussi, vérifier si la table user_profiles existe
                    if (tableExists('user_profiles')) {
                        try {
                            // Calculer l'âge à partir de la date de naissance
                            $birth_date_obj = new DateTime($birth_date);
                            $today = new DateTime();
                            $age = $birth_date_obj->diff($today)->y;
                            
                            // Calculer le BMR de base
                            $bmr = calculateBMR($weight, $height, $age, $gender);
                            
                            // Calculer le TDEE (calories de base)
                            $tdee = calculateTDEE($bmr, $activity_level);
                            
                            // Créer un profil pour l'utilisateur avec les données fournies
                            $sql = "INSERT INTO user_profiles (user_id) VALUES (?)";
                            $profile_id = insert($sql, [$user_id]);
                            
                            // Mettre à jour les autres informations
                            if ($profile_id > 0) {
                                $update_sql = "UPDATE user_profiles SET 
                                                gender = ?, 
                                                birth_date = ?, 
                                                height = ?, 
                                                activity_level = ?,
                                                daily_calories = ?,
                                                protein_ratio = 0.3,
                                                carbs_ratio = 0.4,
                                                fat_ratio = 0.3
                                                WHERE user_id = ?";
                                update($update_sql, [$gender, $birth_date, $height, $activity_level, $tdee, $user_id]);
                                
                                // Ajouter le poids de départ dans la table weight_logs
                                if (tableExists('weight_logs')) {
                                    $sql = "INSERT INTO weight_logs (user_id, weight, log_date, notes, created_at) 
                                            VALUES (?, ?, CURDATE(), 'Poids de départ', NOW())";
                                    insert($sql, [$user_id, $weight]);
                                }
                            } else {
                                throw new Exception("Échec de l'insertion du profil utilisateur");
                            }
                        } catch (Exception $e) {
                            // En cas d'erreur, supprimer l'utilisateur pour éviter les données orphelines
                            $delete_sql = "DELETE FROM users WHERE id = ?";
                            delete($delete_sql, [$user_id]);
                            $errors[] = "Erreur lors de la création du profil utilisateur: " . $e->getMessage();
                            error_log("Erreur lors de la création du profil utilisateur: " . $e->getMessage());
                        }
                    }
                    
                    // Rediriger vers la page de connexion avec un message de succès
                    $_SESSION['success_message'] = "Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.";
                    redirect('login.php');
                } else {
                    $errors[] = "Une erreur s'est produite lors de la création du compte. Veuillez réessayer.";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur s'est produite: " . $e->getMessage();
            error_log("Erreur d'inscription: " . $e->getMessage());
        }
    }
}

// Calculer l'âge minimum (16 ans)
$min_year = date('Y') - 100;
$max_year = date('Y') - 16;
$max_date = date('Y-m-d', strtotime("-16 years"));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5 mb-5">
            <div class="col-md-10 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h2 class="mb-0"><i class="fas fa-weight me-2"></i>MyFity</h2>
                        <p class="mb-0">Créez votre compte pour commencer à suivre votre progression</p>
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
                        
                        <!-- Formulaire d'inscription -->
                        <form action="register.php" method="POST" novalidate>
                            <h5 class="mb-3">Informations du compte</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Nom d'utilisateur</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                                    </div>
                                    <div class="form-text">Choisissez un nom d'utilisateur entre 3 et 30 caractères.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Adresse e-mail</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                    </div>
                                    <div class="form-text">Nous ne partagerons jamais votre e-mail avec des tiers.</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Utilisez au moins 8 caractères, incluant des lettres et des chiffres.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <h5 class="mb-3">Informations personnelles</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">Prénom</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Genre</label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="" disabled <?php echo empty($gender) ? 'selected' : ''; ?>>Sélectionnez votre genre</option>
                                        <option value="homme" <?php echo $gender === 'homme' ? 'selected' : ''; ?>>Homme</option>
                                        <option value="femme" <?php echo $gender === 'femme' ? 'selected' : ''; ?>>Femme</option>
                                        <option value="autre" <?php echo $gender === 'autre' ? 'selected' : ''; ?>>Autre</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="birth_date" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($birth_date); ?>" max="<?php echo $max_date; ?>" required>
                                    <div class="form-text">Vous devez avoir au moins 16 ans pour utiliser cette application.</div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <h5 class="mb-3">Informations physiques</h5>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="height" class="form-label">Taille (cm)</label>
                                    <input type="number" class="form-control" id="height" name="height" value="<?php echo htmlspecialchars($height); ?>" min="100" max="250" step="1" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="weight" class="form-label">Poids de départ (kg)</label>
                                    <input type="number" class="form-control" id="weight" name="weight" value="<?php echo htmlspecialchars($weight); ?>" min="30" max="300" step="0.1" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="activity_level" class="form-label">Niveau d'activité</label>
                                    <select class="form-select" id="activity_level" name="activity_level" required>
                                        <option value="" disabled <?php echo empty($activity_level) ? 'selected' : ''; ?>>Sélectionnez votre niveau</option>
                                        <option value="sedentaire" <?php echo $activity_level === 'sedentaire' ? 'selected' : ''; ?>>Sédentaire (peu ou pas d'exercice)</option>
                                        <option value="leger" <?php echo $activity_level === 'leger' ? 'selected' : ''; ?>>Légèrement actif (exercice léger 1-3 jours/semaine)</option>
                                        <option value="modere" <?php echo $activity_level === 'modere' ? 'selected' : ''; ?>>Modérément actif (exercice modéré 3-5 jours/semaine)</option>
                                        <option value="actif" <?php echo $activity_level === 'actif' ? 'selected' : ''; ?>>Actif (exercice intense 6-7 jours/semaine)</option>
                                        <option value="tres_actif" <?php echo $activity_level === 'tres_actif' ? 'selected' : ''; ?>>Très actif (exercice très intense ou travail physique)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg btn-submit">Créer un compte</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <p class="mb-0">Vous avez déjà un compte ? <a href="login.php" class="text-primary">Connectez-vous</a></p>
                    </div>
                </div>
                
                <!-- Avantages de l'application -->
                <div class="card mt-4 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Pourquoi utiliser MyFity ?</h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="d-flex mb-3">
                                    <div class="feature-icon me-3">
                                        <i class="fas fa-chart-line text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Suivi personnalisé</h6>
                                        <p class="text-muted small mb-0">Suivez votre poids et vos activités quotidiennes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex mb-3">
                                    <div class="feature-icon me-3">
                                        <i class="fas fa-utensils text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Recommandations de repas</h6>
                                        <p class="text-muted small mb-0">Recevez des suggestions adaptées à vos objectifs</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex mb-3">
                                    <div class="feature-icon me-3">
                                        <i class="fas fa-running text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Programme d'exercices</h6>
                                        <p class="text-muted small mb-0">Des exercices personnalisés avec suivi du temps</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex mb-3">
                                    <div class="feature-icon me-3">
                                        <i class="fas fa-bullseye text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Objectifs réalisables</h6>
                                        <p class="text-muted small mb-0">Définissez et atteignez vos objectifs de poids</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <footer class="bg-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; 2023 Weight Tracker. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-muted me-3">Conditions d'utilisation</a>
                    <a href="#" class="text-muted me-3">Confidentialité</a>
                    <a href="#" class="text-muted">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script>
        // Initialiser le sélecteur de date
        flatpickr("#birth_date", {
            locale: "fr",
            dateFormat: "Y-m-d",
            maxDate: "<?php echo $max_date; ?>",
            defaultDate: "1990-01-01"
        });
        
        // Afficher/masquer le mot de passe
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
