<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

// Récupération des paramètres de l'utilisateur
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetch();

// Si aucun paramètre n'existe, créer les paramètres par défaut
if (!$settings) {
    $stmt = $pdo->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    $settings = [
        'notifications_enabled' => 1,
        'email_notifications' => 1,
        'daily_reminder' => 1,
        'reminder_time' => '20:00',
        'weight_unit' => 'kg',
        'height_unit' => 'cm',
        'language' => 'fr',
        'theme' => 'light'
    ];
}

// En-tête
require_once 'components/user_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Paramètres</h1>

    <div class="row">
        <!-- Paramètres généraux -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Paramètres généraux</h6>
                </div>
                <div class="card-body">
                    <form id="general-settings-form">
                        <div class="mb-3">
                            <label class="form-label">Langue</label>
                            <select class="form-control" name="language">
                                <option value="fr" <?php echo $settings['language'] == 'fr' ? 'selected' : ''; ?>>Français</option>
                                <option value="en" <?php echo $settings['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thème</label>
                            <select class="form-control" name="theme">
                                <option value="light" <?php echo $settings['theme'] == 'light' ? 'selected' : ''; ?>>Clair</option>
                                <option value="dark" <?php echo $settings['theme'] == 'dark' ? 'selected' : ''; ?>>Sombre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unité de poids</label>
                            <select class="form-control" name="weight_unit">
                                <option value="kg" <?php echo $settings['weight_unit'] == 'kg' ? 'selected' : ''; ?>>Kilogrammes (kg)</option>
                                <option value="lbs" <?php echo $settings['weight_unit'] == 'lbs' ? 'selected' : ''; ?>>Livres (lbs)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unité de taille</label>
                            <select class="form-control" name="height_unit">
                                <option value="cm" <?php echo $settings['height_unit'] == 'cm' ? 'selected' : ''; ?>>Centimètres (cm)</option>
                                <option value="in" <?php echo $settings['height_unit'] == 'in' ? 'selected' : ''; ?>>Pouces (in)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Paramètres de notifications -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Paramètres de notifications</h6>
                </div>
                <div class="card-body">
                    <form id="notification-settings-form">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notifications_enabled" name="notifications_enabled"
                                       <?php echo $settings['notifications_enabled'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notifications_enabled">Activer les notifications</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications"
                                       <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_notifications">Recevoir les notifications par email</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="daily_reminder" name="daily_reminder"
                                       <?php echo $settings['daily_reminder'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="daily_reminder">Rappel quotidien</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reminder_time" class="form-label">Heure du rappel quotidien</label>
                            <input type="time" class="form-control" id="reminder_time" name="reminder_time"
                                   value="<?php echo $settings['reminder_time']; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </form>
                </div>
            </div>

            <!-- Sécurité -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sécurité</h6>
                </div>
                <div class="card-body">
                    <form id="security-form">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Exportation des données -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Exportation des données</h6>
        </div>
        <div class="card-body">
            <p>Téléchargez vos données personnelles au format CSV ou PDF.</p>
            <div class="btn-group">
                <a href="api/export_weight_logs.php?format=csv" class="btn btn-primary">Exporter en CSV</a>
                <a href="api/export_weight_logs.php?format=pdf" class="btn btn-primary">Exporter en PDF</a>
            </div>
        </div>
    </div>
</div>

<script>
// Formulaire des paramètres généraux
document.getElementById('general-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    updateSettings(this, 'general');
});

// Formulaire des paramètres de notifications
document.getElementById('notification-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    updateSettings(this, 'notifications');
});

// Formulaire de sécurité
document.getElementById('security-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        showToast('Erreur', 'Les mots de passe ne correspondent pas', 'error');
        return;
    }
    
    fetch('api/update_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Succès', 'Votre mot de passe a été mis à jour', 'success');
            this.reset();
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    })
    .catch(error => {
        showToast('Erreur', 'Une erreur est survenue', 'error');
        console.error('Error:', error);
    });
});

function updateSettings(form, type) {
    const formData = new FormData(form);
    
    fetch(`api/update_settings.php?type=${type}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Succès', 'Vos paramètres ont été mis à jour', 'success');
            if (type === 'general' && (data.reload || formData.get('theme') !== currentTheme)) {
                setTimeout(() => window.location.reload(), 1500);
            }
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    })
    .catch(error => {
        showToast('Erreur', 'Une erreur est survenue', 'error');
        console.error('Error:', error);
    });
}
</script>

<?php require_once 'components/user_footer.php'; ?> 