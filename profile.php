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

// Récupérer le profil de l'utilisateur
$sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$profile = fetchOne($sql, [$user_id]);

// Initialiser les variables
$gender = $profile['gender'] ?? 'homme';
$birth_date = $profile['birth_date'] ?? '';
$height = $profile['height'] ?? '';
$activity_level = $profile['activity_level'] ?? 'modere';
$success_message = '';
$errors = [];

// Traitement du formulaire de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si c'est une action de recalcul des calories, ne pas valider le formulaire
    if (isset($_POST['action']) && $_POST['action'] === 'recalculate_calories') {
        try {
            // Récupérer le dernier poids enregistré
            $sql = "SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
            $latest_weight = fetchOne($sql, [$user_id]);
            
            if ($latest_weight) {
                // Calculer l'âge à partir de la date de naissance
                $birth_date_obj = new DateTime($profile['birth_date']);
                $today = new DateTime();
                $age = $birth_date_obj->diff($today)->y;
                
                // Calculer le BMR de base
                $bmr = calculateBMR($latest_weight['weight'], $profile['height'], $age, $profile['gender']);
                
                // Calculer le TDEE (calories de base)
                $tdee = calculateTDEE($bmr, $profile['activity_level']);
                
                // Vérifier si l'utilisateur a un programme actif
                $sql = "SELECT p.* FROM user_programs up 
                        JOIN programs p ON up.program_id = p.id 
                        WHERE up.user_id = ? AND up.status = 'actif'";
                $active_program = fetchOne($sql, [$user_id]);
                
                // Vérifier si l'utilisateur a un objectif actif
                $sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
                $current_goal = fetchOne($sql, [$user_id]);
                
                $final_calories = $tdee;
                
                // Calculer les calories nécessaires pour l'objectif
                if ($current_goal) {
                    error_log("=== Début du calcul des calories pour l'objectif ===");
                    error_log("Poids actuel : " . $profile['weight']);
                    error_log("Poids cible : " . $current_goal['target_weight']);
                    
                    // Calculer la différence de poids
                    $weight_diff = $current_goal['target_weight'] - $profile['weight'];
                    error_log("Différence de poids : " . $weight_diff . " kg");
                    
                    // Calculer le nombre de jours jusqu'à l'objectif
                    $days_to_goal = (strtotime($current_goal['target_date']) - time()) / (60 * 60 * 24);
                    error_log("Jours jusqu'à l'objectif : " . $days_to_goal);
                    
                    // Calculer les calories totales nécessaires (1 kg = 7700 calories)
                    $total_calories_needed = $weight_diff * 7700;
                    error_log("Calories totales nécessaires : " . $total_calories_needed);
                    
                    // Calculer l'ajustement quotidien nécessaire
                    $daily_adjustment = $total_calories_needed / $days_to_goal;
                    error_log("Ajustement quotidien pour l'objectif : " . $daily_adjustment);
                    
                    // Vérifier si un programme est actif
                    $sql = "SELECT p.*, up.status 
                            FROM user_programs up 
                            JOIN programs p ON up.program_id = p.id 
                            WHERE up.user_id = ? AND up.status = 'actif'";
                    $active_program = fetchOne($sql, [$user_id]);
                    
                    if ($active_program) {
                        error_log("Programme actif : " . $active_program['name']);
                        error_log("Ajustement du programme : " . $active_program['calorie_adjustment'] . "%");
                        
                        // Calculer l'ajustement du programme
                        $program_adjustment = $tdee * ($active_program['calorie_adjustment'] / 100);
                        error_log("Ajustement du programme calculé : " . $program_adjustment);
                        
                        // Ajouter l'ajustement du programme aux calories de base
                        $tdee += $program_adjustment;
                        error_log("TDEE après ajustement programme : " . $tdee);
                    }
                    
                    // Ajouter l'ajustement quotidien pour l'objectif
                    $daily_calories = $tdee + $daily_adjustment;
                    error_log("Calories finales : " . $daily_calories);
                    error_log("=== Fin du calcul des calories pour l'objectif ===");
                }
                
                // Mettre à jour les calories dans le profil
                $sql = "UPDATE user_profiles SET daily_calories = ?, updated_at = NOW() WHERE user_id = ?";
                update($sql, [$final_calories, $user_id]);
                
                $success_message = "Vos besoins caloriques ont été recalculés avec succès !";
            } else {
                $errors[] = "Aucun poids enregistré trouvé. Veuillez d'abord enregistrer votre poids.";
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur s'est produite lors du recalcul des calories : " . $e->getMessage();
            error_log("Erreur dans profile.php (recalculate_calories): " . $e->getMessage());
        }
    } else {
        // Récupérer et nettoyer les données du formulaire
        $gender = sanitizeInput($_POST['gender'] ?? '');
        $birth_date = sanitizeInput($_POST['birth_date'] ?? '');
        $height = sanitizeInput($_POST['height'] ?? '');
        $activity_level = sanitizeInput($_POST['activity_level'] ?? '');
        
        // Validation des données
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
        } elseif (!is_numeric($height) || $height <= 0) {
            $errors[] = "La taille doit être un nombre positif";
        }
        
        if (empty($activity_level)) {
            $errors[] = "Le niveau d'activité est requis";
        }
        
        // Si aucune erreur, mettre à jour le profil
        if (empty($errors)) {
            try {
                // Récupérer le dernier poids enregistré
                $sql = "SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
                $latest_weight = fetchOne($sql, [$user_id]);
                
                if ($latest_weight) {
                    // Calculer le BMR de base avec le dernier poids
                    $bmr = calculateBMR($latest_weight['weight'], $height, $birth_date, $gender);
                    
                    // Calculer le TDEE (calories de base)
                    $tdee = calculateTDEE($bmr, $activity_level);
                    
                    // Vérifier si l'utilisateur a un programme actif
                    $sql = "SELECT * FROM user_programs WHERE user_id = ? AND status = 'actif'";
                    $active_program = fetchOne($sql, [$user_id]);
                    
                    if ($active_program) {
                        // Si un programme est actif, mettre à jour directement les calories
                        $sql = "UPDATE user_profiles SET 
                                gender = ?, 
                                birth_date = ?, 
                                height = ?, 
                                activity_level = ?,
                                daily_calories = ?,
                                updated_at = NOW()
                                WHERE user_id = ?";
                        update($sql, [$gender, $birth_date, $height, $activity_level, $tdee, $user_id]);
                        
                        $success_message = "Votre profil a été mis à jour avec succès !";
                    } else {
                        // Si pas de programme actif, demander confirmation
                        $_SESSION['pending_calories_update'] = [
                            'tdee' => $tdee,
                            'profile_data' => [
                                'gender' => $gender,
                                'birth_date' => $birth_date,
                                'height' => $height,
                                'activity_level' => $activity_level
                            ]
                        ];
                        $success_message = "Votre profil a été mis à jour avec succès ! Voulez-vous mettre à jour vos besoins caloriques en fonction de votre nouveau niveau d'activité ?";
                    }
                } else {
                    $errors[] = "Aucun poids enregistré trouvé. Veuillez d'abord enregistrer votre poids.";
                }
            } catch (Exception $e) {
                $errors[] = "Une erreur s'est produite: " . $e->getMessage();
                error_log("Erreur dans profile.php: " . $e->getMessage());
            }
        }
    }
}

// Ajouter le traitement de la confirmation de mise à jour des calories
if (isset($_POST['action']) && $_POST['action'] === 'update_calories' && isset($_SESSION['pending_calories_update'])) {
    $pending_update = $_SESSION['pending_calories_update'];
    
    if ($_POST['confirm'] === 'yes') {
        // Mettre à jour le profil avec les nouvelles calories
        $profile_data = $pending_update['profile_data'];
        $sql = "UPDATE user_profiles SET 
                gender = ?, 
                birth_date = ?, 
                height = ?, 
                activity_level = ?,
                daily_calories = ?,
                updated_at = NOW()
                WHERE user_id = ?";
        update($sql, [
            $profile_data['gender'],
            $profile_data['birth_date'],
            $profile_data['height'],
            $profile_data['activity_level'],
            $pending_update['tdee'],
            $user_id
        ]);
        $success_message = "Vos besoins caloriques ont été mis à jour avec succès !";
    }
    
    // Nettoyer la session
    unset($_SESSION['pending_calories_update']);
    redirect('profile.php');
}

// Récupérer le dernier poids enregistré
$sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
$latest_weight = fetchOne($sql, [$user_id]);

// Calculer l'IMC si le poids et la taille sont disponibles
$bmi = 0;
$bmi_category = '';

if ($latest_weight && $profile && $profile['height'] > 0) {
    $weight_kg = $latest_weight['weight'];
    $height_m = $profile['height'] / 100;
    $bmi = $weight_kg / ($height_m * $height_m);
    
    if ($bmi < 18.5) {
        $bmi_category = 'Insuffisance pondérale';
    } elseif ($bmi < 25) {
        $bmi_category = 'Poids normal';
    } elseif ($bmi < 30) {
        $bmi_category = 'Surpoids';
    } else {
        $bmi_category = 'Obésité';
    }
}

// Calculer l'âge à partir de la date de naissance
$age = 0;
if ($profile && !empty($profile['birth_date'])) {
    $birth_date = new DateTime($profile['birth_date']);
    $today = new DateTime();
    $age = $birth_date->diff($today)->y;
}

// Calculer les besoins caloriques quotidiens
$daily_calories = 0;
if ($latest_weight && $profile && $age > 0) {
    // Formule de Harris-Benedict
    if ($profile['gender'] === 'homme') {
        $bmr = 88.362 + (13.397 * $latest_weight['weight']) + (4.799 * $profile['height']) - (5.677 * $age);
    } else {
        $bmr = 447.593 + (9.247 * $latest_weight['weight']) + (3.098 * $profile['height']) - (4.330 * $age);
    }
    
    // Facteur d'activité
    switch ($profile['activity_level']) {
        case 'sedentaire':
            $daily_calories = $bmr * 1.2;
            break;
        case 'leger':
            $daily_calories = $bmr * 1.375;
            break;
        case 'modere':
            $daily_calories = $bmr * 1.55;
            break;
        case 'actif':
            $daily_calories = $bmr * 1.725;
            break;
        case 'tres_actif':
            $daily_calories = $bmr * 1.9;
            break;
        default:
            $daily_calories = $bmr * 1.55;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
                <h1 class="mb-0">Profil utilisateur</h1>
                <p class="text-muted">Gérez vos informations personnelles et vos préférences</p>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <?php if (isset($_SESSION['pending_calories_update'])): ?>
                    <form action="profile.php" method="POST" class="mt-2">
                        <input type="hidden" name="action" value="update_calories">
                        <div class="d-flex gap-2">
                            <button type="submit" name="confirm" value="yes" class="btn btn-success btn-sm">
                                <i class="fas fa-check me-1"></i>Oui, mettre à jour
                            </button>
                            <button type="submit" name="confirm" value="no" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Non, garder les actuels
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Informations du profil -->
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Informations personnelles</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="avatar-circle mx-auto mb-3">
                                <span class="avatar-initials"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                            </div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>Genre</span>
                                <span class="text-muted">
                                    <?php 
                                        if ($profile) {
                                            if ($profile['gender'] === 'homme') {
                                                echo 'Homme';
                                            } elseif ($profile['gender'] === 'femme') {
                                                echo 'Femme';
                                            } else {
                                                echo 'Autre';
                                            }
                                        } else {
                                            echo '—';
                                        }
                                    ?>
                                </span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>Âge</span>
                                <span class="text-muted"><?php echo $age > 0 ? $age . ' ans' : '—'; ?></span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>Taille</span>
                                <span class="text-muted"><?php echo $profile && $profile['height'] ? $profile['height'] . ' cm' : '—'; ?></span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>Poids actuel</span>
                                <span class="text-muted"><?php echo $latest_weight ? number_format($latest_weight['weight'], 1) . ' kg' : '—'; ?></span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>IMC</span>
                                <span class="text-muted"><?php echo $bmi > 0 ? number_format($bmi, 1) . ' (' . $bmi_category . ')' : '—'; ?></span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>Niveau d'activité</span>
                                <span class="text-muted">
                                    <?php 
                                        if ($profile) {
                                            switch ($profile['activity_level']) {
                                                case 'sedentaire':
                                                    echo 'Sédentaire';
                                                    break;
                                                case 'leger':
                                                    echo 'Légèrement actif';
                                                    break;
                                                case 'modere':
                                                    echo 'Modérément actif';
                                                    break;
                                                case 'actif':
                                                    echo 'Actif';
                                                    break;
                                                case 'tres_actif':
                                                    echo 'Très actif';
                                                    break;
                                                default:
                                                    echo 'Modérément actif';
                                            }
                                        } else {
                                            echo '—';
                                        }
                                    ?>
                                </span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>Besoins caloriques quotidiens</span>
                                <span class="text-muted"><?php echo $daily_calories > 0 ? number_format($daily_calories, 0) . ' cal' : '—'; ?></span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>Membre depuis</span>
                                <span class="text-muted"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="weight-log.php?action=add" class="btn btn-outline-primary">
                                <i class="fas fa-weight me-1"></i>Ajouter une entrée de poids
                            </a>
                            <a href="goals.php?action=add" class="btn btn-outline-primary">
                                <i class="fas fa-bullseye me-1"></i>Définir un objectif
                            </a>
                            <form action="profile.php" method="POST" class="d-grid">
                                <input type="hidden" name="action" value="recalculate_calories">
                                <button type="submit" class="btn btn-outline-info">
                                    <i class="fas fa-calculator me-1"></i>Recalculer les calories
                                </button>
                            </form>
                            <a href="settings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-cog me-1"></i>Paramètres du compte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulaire de mise à jour du profil -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Modifier le profil</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form action="profile.php" method="POST" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Genre</label>
                                <div class="d-flex">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="gender" id="gender_male" value="homme" <?php echo $gender === 'homme' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="gender_male">
                                            Homme
                                        </label>
                                    </div>
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="gender" id="gender_female" value="femme" <?php echo $gender === 'femme' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="gender_female">
                                            Femme
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="gender_other" value="autre" <?php echo $gender === 'autre' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="gender_other">
                                            Autre
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="birth_date" class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo is_object($birth_date) ? $birth_date->format('Y-m-d') : htmlspecialchars($birth_date); ?>" required>
                                <div class="form-text">Utilisée pour calculer votre métabolisme de base.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="height" class="form-label">Taille (cm)</label>
                                <input type="number" class="form-control" id="height" name="height" value="<?php echo htmlspecialchars($height); ?>" min="50" max="250" step="1" required>
                                <div class="form-text">Utilisée pour calculer votre IMC et vos besoins caloriques.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="activity_level" class="form-label">Niveau d'activité physique</label>
                                <select class="form-select" id="activity_level" name="activity_level" required>
                                    <option value="sedentaire" <?php echo $activity_level === 'sedentaire' ? 'selected' : ''; ?>>Sédentaire (peu ou pas d'exercice)</option>
                                    <option value="leger" <?php echo $activity_level === 'leger' ? 'selected' : ''; ?>>Légèrement actif (exercice léger 1-3 jours/semaine)</option>
                                    <option value="modere" <?php echo $activity_level === 'modere' ? 'selected' : ''; ?>>Modérément actif (exercice modéré 3-5 jours/semaine)</option>
                                    <option value="actif" <?php echo $activity_level === 'actif' ? 'selected' : ''; ?>>Actif (exercice intense 6-7 jours/semaine)</option>
                                    <option value="tres_actif" <?php echo $activity_level === 'tres_actif' ? 'selected' : ''; ?>>Très actif (exercice très intense ou travail physique)</option>
                                </select>
                                <div class="form-text">Utilisé pour calculer vos besoins caloriques quotidiens.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script>
        // Initialiser le sélecteur de date
        flatpickr("#birth_date", {
            locale: "fr",
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
    </script>
</body>
</html>
