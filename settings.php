<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$user = fetchOne($sql, [$user_id]);

// Initialiser les variables
$current_password = $new_password = $confirm_password = '';
$success_message = '';
$errors = [];

// Traitement du formulaire de changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    // Récupérer les données du formulaire
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation des données
    if (empty($current_password)) {
        $errors[] = "Le mot de passe actuel est requis";
    } elseif (!password_verify($current_password, $user['password'])) {
        $errors[] = "Le mot de passe actuel est incorrect";
    }
    
    if (empty($new_password)) {
        $errors[] = "Le nouveau mot de passe est requis";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    // Si aucune erreur, mettre à jour le mot de passe
    if (empty($errors)) {
        // Hacher le nouveau mot de passe
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Mettre à jour le mot de passe dans la base de données
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        $params = [$hashed_password, $user_id];
        
        $result = update($sql, $params);
        
        if ($result) {
            $success_message = "Votre mot de passe a été mis à jour avec succès !";
            $current_password = $new_password = $confirm_password = '';
        } else {
            $errors[] = "Une erreur s'est produite lors de la mise à jour du mot de passe. Veuillez réessayer.";
        }
    }
}

// Traitement du formulaire de préférences de notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'notification_preferences') {
    // Récupérer les préférences de notification
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $weight_reminders = isset($_POST['weight_reminders']) ? 1 : 0;
    $food_reminders = isset($_POST['food_reminders']) ? 1 : 0;
    $exercise_reminders = isset($_POST['exercise_reminders']) ? 1 : 0;
    $goal_updates = isset($_POST['goal_updates']) ? 1 : 0;
    
    // Vérifier si la table user_settings existe
    if (!tableExists('user_settings')) {
        // Créer la table si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS user_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email_notifications TINYINT(1) NOT NULL DEFAULT 1,
            weight_reminders TINYINT(1) NOT NULL DEFAULT 1,
            food_reminders TINYINT(1) NOT NULL DEFAULT 1,
            exercise_reminders TINYINT(1) NOT NULL DEFAULT 1,
            goal_updates TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $conn = connectDB();
        $conn->query($sql);
        $conn->close();
    }
    
    // Vérifier si l'utilisateur a déjà des préférences
    $sql = "SELECT id FROM user_settings WHERE user_id = ?";
    $settings = fetchOne($sql, [$user_id]);
    
    if ($settings) {
        // Mettre à jour les préférences existantes
        $sql = "UPDATE user_settings SET 
                email_notifications = ?, 
                weight_reminders = ?, 
                food_reminders = ?, 
                exercise_reminders = ?, 
                goal_updates = ?, 
                updated_at = NOW() 
                WHERE user_id = ?";
        $params = [$email_notifications, $weight_reminders, $food_reminders, $exercise_reminders, $goal_updates, $user_id];
        
        $result = update($sql, $params);
    } else {
        // Créer de nouvelles préférences
        $sql = "INSERT INTO user_settings (user_id, email_notifications, weight_reminders, food_reminders, exercise_reminders, goal_updates, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $params = [$user_id, $email_notifications, $weight_reminders, $food_reminders, $exercise_reminders, $goal_updates];
        
        $result = insert($sql, $params);
    }
    
    if ($result) {
        $success_message = "Vos préférences de notification ont été mises à jour avec succès !";
    } else {
        $errors[] = "Une erreur s'est produite lors de la mise à jour des préférences. Veuillez réessayer.";
    }
}

// Récupérer les préférences de notification actuelles
$sql = "SELECT * FROM user_settings WHERE user_id = ?";
$settings = fetchOne($sql, [$user_id]);

// Valeurs par défaut si aucun paramètre n'existe
$email_notifications = $settings['email_notifications'] ?? 1;
$weight_reminders = $settings['weight_reminders'] ?? 1;
$food_reminders = $settings['food_reminders'] ?? 1;
$exercise_reminders = $settings['exercise_reminders'] ?? 1;
$goal_updates = $settings['goal_updates'] ?? 1;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Barre de navigation -->
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <!-- En-tête de la page -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-0">Paramètres du compte</h1>
                <p class="text-muted">Gérez les paramètres de votre compte et vos préférences</p>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Menu de navigation latéral -->
            <div class="col-lg-3 mb-4 mb-lg-0">
                <div class="list-group">
                    <a href="#account" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="fas fa-user me-2"></i>Compte
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-lock me-2"></i>Sécurité
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-bell me-2"></i>Notifications
                    </a>
                    <a href="#data" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-database me-2"></i>Données
                    </a>
                </div>
            </div>
            
            <!-- Contenu des onglets -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- Onglet Compte -->
                    <div class="tab-pane fade show active" id="account">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Informations du compte</h5>
                            </div>
                            <div class="card-body">
                                <form action="settings.php" method="POST" novalidate>
                                    <input type="hidden" name="action" value="account_info">
                                    
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nom d'utilisateur</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                        <div class="form-text">Le nom d'utilisateur ne peut pas être modifié.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Adresse e-mail</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                        <div class="form-text">Pour modifier votre adresse e-mail, veuillez contacter le support.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="created_at" class="form-label">Date d'inscription</label>
                                        <input type="text" class="form-control" id="created_at" value="<?php echo date('d/m/Y', strtotime($user['created_at'])); ?>" disabled>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="profile.php" class="btn btn-primary">
                                            <i class="fas fa-id-card me-1"></i>Modifier le profil
                                        </a>
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                            <i class="fas fa-trash-alt me-1"></i>Supprimer le compte
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Onglet Sécurité -->
                    <div class="tab-pane fade" id="security">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Changer le mot de passe</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($errors) && isset($_POST['action']) && $_POST['action'] === 'change_password'): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo $error; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <form action="settings.php" method="POST" novalidate>
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Utilisez au moins 8 caractères, incluant des lettres et des chiffres.</div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Changer le mot de passe
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mt-4">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Sessions actives</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <i class="fas fa-desktop fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0">Cet appareil</h6>
                                            <span class="badge bg-success">Actif</span>
                                        </div>
                                        <small class="text-muted">Dernière activité : Aujourd'hui</small>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="button" class="btn btn-outline-danger">
                                        <i class="fas fa-sign-out-alt me-1"></i>Déconnecter toutes les autres sessions
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Onglet Notifications -->
                    <div class="tab-pane fade" id="notifications">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Préférences de notification</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($errors) && isset($_POST['action']) && $_POST['action'] === 'notification_preferences'): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo $error; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <form action="settings.php" method="POST" novalidate>
                                    <input type="hidden" name="action" value="notification_preferences">
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" <?php echo $email_notifications ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_notifications">Notifications par e-mail</label>
                                        <div class="form-text">Recevoir des notifications par e-mail.</div>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="weight_reminders" name="weight_reminders" <?php echo $weight_reminders ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="weight_reminders">Rappels de poids</label>
                                        <div class="form-text">Recevoir des rappels pour enregistrer votre poids.</div>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="food_reminders" name="food_reminders" <?php echo $food_reminders ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="food_reminders">Rappels alimentaires</label>
                                        <div class="form-text">Recevoir des rappels pour enregistrer vos repas.</div>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="exercise_reminders" name="exercise_reminders" <?php echo $exercise_reminders ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="exercise_reminders">Rappels d'exercices</label>
                                        <div class="form-text">Recevoir des rappels pour enregistrer vos exercices.</div>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-4">
                                        <input class="form-check-input" type="checkbox" id="goal_updates" name="goal_updates" <?php echo $goal_updates ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="goal_updates">Mises à jour d'objectifs</label>
                                        <div class="form-text">Recevoir des mises à jour sur vos progrès vers vos objectifs.</div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Enregistrer les préférences
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Onglet Données -->
                    <div class="tab-pane fade" id="data">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Exportation et suppression des données</h5>
                            </div>
                            <div class="card-body">
                                <p>Vous pouvez exporter toutes vos données ou supprimer définitivement votre compte.</p>
                                
                                <div class="mb-4">
                                    <h6>Exporter vos données</h6>
                                    <p class="text-muted">Téléchargez une copie de vos données personnelles.</p>
                                    <div class="d-flex">
                                        <button type="button" class="btn btn-outline-primary me-2">
                                            <i class="fas fa-file-csv me-1"></i>Exporter en CSV
                                        </button>
                                        <button type="button" class="btn btn-outline-primary">
                                            <i class="fas fa-file-pdf me-1"></i>Exporter en PDF
                                        </button>
                                    </div>
                                </div>
                                
                                <div>
                                    <h6>Supprimer votre compte</h6>
                                    <p class="text-muted">La suppression de votre compte est définitive et supprimera toutes vos données.</p>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                        <i class="fas fa-trash-alt me-1"></i>Supprimer mon compte
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de suppression de compte -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountModalLabel">Confirmer la suppression du compte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Attention ! Cette action est irréversible.
                    </div>
                    <p>La suppression de votre compte entraînera la perte définitive de toutes vos données, y compris :</p>
                    <ul>
                        <li>Votre profil et vos informations personnelles</li>
                        <li>Votre historique de poids</li>
                        <li>Vos journaux alimentaires et d'exercices</li>
                        <li>Vos objectifs et rapports</li>
                    </ul>
                    <p>Êtes-vous sûr de vouloir supprimer votre compte ?</p>
                    
                    <form id="deleteAccountForm">
                        <div class="mb-3">
                            <label for="delete_confirmation" class="form-label">Pour confirmer, tapez "SUPPRIMER" ci-dessous :</label>
                            <input type="text" class="form-control" id="delete_confirmation" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                        <i class="fas fa-trash-alt me-1"></i>Supprimer définitivement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <footer class="bg-light py-4 mt-auto">
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
    <script>
        // Fonctionnalité pour afficher/masquer les mots de passe
        document.querySelectorAll('#toggleCurrentPassword, #toggleNewPassword, #toggleConfirmPassword').forEach(button => {
            button.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling;
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
        });
        
        // Activer/désactiver le bouton de suppression de compte
        document.getElementById('delete_confirmation').addEventListener('input', function() {
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            confirmBtn.disabled = this.value !== 'SUPPRIMER';
        });
        
        // Gérer les onglets via l'URL
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) {
                const tab = document.querySelector(`.list-group-item[href="${hash}"]`);
                if (tab) {
                    tab.click();
                }
            }
        });
    </script>
</body>
</html>
