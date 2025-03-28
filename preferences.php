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
$success_message = '';
$errors = [];

// Récupérer les préférences alimentaires de l'utilisateur
$sql = "SELECT * FROM food_preferences WHERE user_id = ?";
$preferences = fetchAll($sql, [$user_id]);

// Organiser les préférences par type
$favorite_foods = [];
$blacklisted_foods = [];

foreach ($preferences as $pref) {
    if ($pref['preference_type'] === 'favori') {
        $favorite_foods[] = $pref;
    } elseif ($pref['preference_type'] === 'blacklist') {
        $blacklisted_foods[] = $pref;
    }
}

// Récupérer les aliments disponibles pour les suggestions
$sql = "SELECT * FROM foods ORDER BY name";
$available_foods = fetchAll($sql);

// Traitement de l'ajout d'une préférence alimentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_preference') {
    $food_id = isset($_POST['food_id']) ? (int)$_POST['food_id'] : 0;
    $custom_food = sanitizeInput($_POST['custom_food'] ?? '');
    $preference_type = sanitizeInput($_POST['preference_type'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Validation
    if ($food_id <= 0 && empty($custom_food)) {
        $errors[] = "Veuillez sélectionner un aliment ou saisir un aliment personnalisé";
    }
    
    if (empty($preference_type) || !in_array($preference_type, ['favori', 'blacklist'])) {
        $errors[] = "Le type de préférence est invalide";
    }
    
    // Si aucune erreur, ajouter la préférence
    if (empty($errors)) {
        try {
            // Vérifier si l'aliment existe déjà dans les préférences
            $sql = "SELECT * FROM food_preferences WHERE user_id = ? AND ";
            $params = [$user_id];
            
            if ($food_id > 0) {
                $sql .= "food_id = ?";
                $params[] = $food_id;
            } else {
                $sql .= "custom_food = ?";
                $params[] = $custom_food;
            }
            
            $existing_preference = fetchOne($sql, $params);
            
            if ($existing_preference) {
                // Mettre à jour la préférence existante
                $sql = "UPDATE food_preferences SET preference_type = ?, notes = ?, updated_at = NOW() WHERE id = ?";
                $result = update($sql, [$preference_type, $notes, $existing_preference['id']]);
                
                if ($result) {
                    $success_message = "La préférence alimentaire a été mise à jour avec succès !";
                } else {
                    $errors[] = "Une erreur s'est produite lors de la mise à jour de la préférence. Veuillez réessayer.";
                }
            } else {
                // Ajouter une nouvelle préférence
                $sql = "INSERT INTO food_preferences (user_id, food_id, custom_food, preference_type, notes, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
                $result = insert($sql, [$user_id, $food_id > 0 ? $food_id : null, $food_id > 0 ? null : $custom_food, $preference_type, $notes]);
                
                if ($result) {
                    $success_message = "La préférence alimentaire a été ajoutée avec succès !";
                    
                    // Récupérer les préférences mises à jour
                    $sql = "SELECT * FROM food_preferences WHERE user_id = ?";
                    $preferences = fetchAll($sql, [$user_id]);
                    
                    // Réorganiser les préférences
                    $favorite_foods = [];
                    $blacklisted_foods = [];
                    
                    foreach ($preferences as $pref) {
                        if ($pref['preference_type'] === 'favori') {
                            $favorite_foods[] = $pref;
                        } elseif ($pref['preference_type'] === 'blacklist') {
                            $blacklisted_foods[] = $pref;
                        }
                    }
                } else {
                    $errors[] = "Une erreur s'est produite lors de l'ajout de la préférence. Veuillez réessayer.";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur s'est produite: " . $e->getMessage();
            error_log("Erreur dans preferences.php: " . $e->getMessage());
        }
    }
}

// Traitement de la suppression d'une préférence
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $preference_id = (int)$_GET['id'];
    
    // Vérifier si la préférence appartient à l'utilisateur
    $sql = "SELECT * FROM food_preferences WHERE id = ? AND user_id = ?";
    $preference = fetchOne($sql, [$preference_id, $user_id]);
    
    if ($preference) {
        // Supprimer la préférence
        $sql = "DELETE FROM food_preferences WHERE id = ?";
        $result = update($sql, [$preference_id]); // Utiliser update pour exécuter la requête DELETE
        
        if ($result) {
            $success_message = "La préférence alimentaire a été supprimée avec succès !";
            
            // Récupérer les préférences mises à jour
            $sql = "SELECT * FROM food_preferences WHERE user_id = ?";
            $preferences = fetchAll($sql, [$user_id]);
            
            // Réorganiser les préférences
            $favorite_foods = [];
            $blacklisted_foods = [];
            
            foreach ($preferences as $pref) {
                if ($pref['preference_type'] === 'favori') {
                    $favorite_foods[] = $pref;
                } elseif ($pref['preference_type'] === 'blacklist') {
                    $blacklisted_foods[] = $pref;
                }
            }
        } else {
            $errors[] = "Une erreur s'est produite lors de la suppression de la préférence. Veuillez réessayer.";
        }
    } else {
        $errors[] = "Préférence non trouvée ou vous n'êtes pas autorisé à la supprimer.";
    }
}

// Récupérer la clé API ChatGPT
$sql = "SELECT value FROM settings WHERE user_id = ? AND setting_name = 'chatgpt_api_key'";
$api_key_setting = fetchOne($sql, [$user_id]);
$api_key = $api_key_setting ? $api_key_setting['value'] : '';

// Traitement de la mise à jour de la clé API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_api_key') {
    $new_api_key = sanitizeInput($_POST['api_key'] ?? '');
    
    if (empty($new_api_key)) {
        $errors[] = "La clé API ne peut pas être vide";
    } else {
        try {
            if ($api_key_setting) {
                // Mettre à jour la clé existante
                $sql = "UPDATE settings SET value = ?, updated_at = NOW() WHERE user_id = ? AND setting_name = 'chatgpt_api_key'";
                $result = update($sql, [$new_api_key, $user_id]);
            } else {
                // Ajouter une nouvelle clé
                $sql = "INSERT INTO settings (user_id, setting_name, value, created_at) VALUES (?, 'chatgpt_api_key', ?, NOW())";
                $result = insert($sql, [$user_id, $new_api_key]);
            }
            
            if ($result) {
                $success_message = "Votre clé API a été mise à jour avec succès !";
                $api_key = $new_api_key;
            } else {
                $errors[] = "Une erreur s'est produite lors de la mise à jour de la clé API. Veuillez réessayer.";
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur s'est produite: " . $e->getMessage();
            error_log("Erreur dans preferences.php: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préférences alimentaires - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-4">Préférences alimentaires</h1>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="food-log.php" class="btn btn-primary">
                    <i class="fas fa-utensils me-1"></i>Journal alimentaire
                </a>
                <a href="food-management.php" class="btn btn-success">
                    <i class="fas fa-apple-alt me-1"></i>Gérer les aliments
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Formulaire d'ajout de préférence -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Ajouter une préférence alimentaire</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="preferences.php">
                            <input type="hidden" name="action" value="add_preference">
                            
                            <div class="mb-3">
                                <label for="food_id" class="form-label">Aliment</label>
                                <select class="form-select" id="food_id" name="food_id">
                                    <option value="0">-- Sélectionner un aliment ou saisir un aliment personnalisé --</option>
                                    <?php foreach ($available_foods as $food): ?>
                                        <option value="<?php echo $food['id']; ?>">
                                            <?php echo htmlspecialchars($food['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="custom_food" class="form-label">Aliment personnalisé</label>
                                <input type="text" class="form-control" id="custom_food" name="custom_food" placeholder="Si l'aliment n'est pas dans la liste">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Type de préférence</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="preference_type" id="preference_type_favorite" value="favori" checked>
                                    <label class="form-check-label" for="preference_type_favorite">
                                        <i class="fas fa-heart text-danger me-1"></i>Aliment favori
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="preference_type" id="preference_type_blacklist" value="blacklist">
                                    <label class="form-check-label" for="preference_type_blacklist">
                                        <i class="fas fa-ban text-danger me-1"></i>Aliment à éviter
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes (optionnel)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Ex: Allergie, intolérance, préférence personnelle..."></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Ajouter la préférence
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Configuration de l'API ChatGPT -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Configuration de l'API ChatGPT</h5>
                    </div>
                    <div class="card-body">
                        <p>
                            Pour utiliser les fonctionnalités d'IA pour l'importation d'aliments et les suggestions de repas, 
                            vous devez configurer votre clé API ChatGPT.
                        </p>
                        
                        <form method="post" action="preferences.php">
                            <input type="hidden" name="action" value="update_api_key">
                            
                            <div class="mb-3">
                                <label for="api_key" class="form-label">Clé API ChatGPT</label>
                                <input type="text" class="form-control" id="api_key" name="api_key" value="<?php echo htmlspecialchars($api_key); ?>" required>
                                <div class="form-text">
                                    Vous pouvez obtenir une clé API sur <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-save me-1"></i>Enregistrer la clé API
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Aliments favoris -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Aliments favoris</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($favorite_foods)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Vous n'avez pas encore ajouté d'aliments favoris.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($favorite_foods as $food): ?>
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">
                                                <i class="fas fa-heart text-danger me-1"></i>
                                                <?php 
                                                if ($food['food_id']) {
                                                    // Récupérer le nom de l'aliment depuis la table foods
                                                    $sql = "SELECT name FROM foods WHERE id = ?";
                                                    $food_info = fetchOne($sql, [$food['food_id']]);
                                                    echo htmlspecialchars($food_info ? $food_info['name'] : 'Aliment inconnu');
                                                } else {
                                                    echo htmlspecialchars($food['custom_food']);
                                                }
                                                ?>
                                            </h6>
                                            <?php if (!empty($food['notes'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($food['notes']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <a href="preferences.php?action=delete&id=<?php echo $food['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette préférence ?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Aliments à éviter -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Aliments à éviter</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($blacklisted_foods)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Vous n'avez pas encore ajouté d'aliments à éviter.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($blacklisted_foods as $food): ?>
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">
                                                <i class="fas fa-ban text-danger me-1"></i>
                                                <?php 
                                                if ($food['food_id']) {
                                                    // Récupérer le nom de l'aliment depuis la table foods
                                                    $sql = "SELECT name FROM foods WHERE id = ?";
                                                    $food_info = fetchOne($sql, [$food['food_id']]);
                                                    echo htmlspecialchars($food_info ? $food_info['name'] : 'Aliment inconnu');
                                                } else {
                                                    echo htmlspecialchars($food['custom_food']);
                                                }
                                                ?>
                                            </h6>
                                            <?php if (!empty($food['notes'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($food['notes']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <a href="preferences.php?action=delete&id=<?php echo $food['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette préférence ?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Informations sur l'utilisation des préférences -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Comment sont utilisées vos préférences alimentaires</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-heart text-danger me-1"></i>Aliments favoris</h6>
                        <ul>
                            <li>Inclus prioritairement dans les suggestions de repas</li>
                            <li>Utilisés pour personnaliser votre plan alimentaire</li>
                            <li>Apparaissent en premier dans les listes de sélection d'aliments</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-ban text-danger me-1"></i>Aliments à éviter</h6>
                        <ul>
                            <li>Exclus automatiquement des suggestions de repas</li>
                            <li>Filtrés des recommandations de l'IA</li>
                            <li>Marqués comme non recommandés dans les listes d'aliments</li>
                        </ul>
                    </div>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Ces préférences sont utilisées par notre système d'IA pour générer des suggestions de repas personnalisées 
                    et adaptées à vos goûts. Plus vous ajoutez de préférences, plus les suggestions seront pertinentes.
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script pour gérer l'interaction entre la liste déroulante et le champ personnalisé
        document.addEventListener('DOMContentLoaded', function() {
            const foodSelect = document.getElementById('food_id');
            const customFoodInput = document.getElementById('custom_food');
            
            foodSelect.addEventListener('change', function() {
                if (this.value !== '0') {
                    customFoodInput.value = '';
                    customFoodInput.disabled = true;
                } else {
                    customFoodInput.disabled = false;
                }
            });
            
            customFoodInput.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    foodSelect.value = '0';
                }
            });
            
            // Initialiser l'état au chargement
            if (foodSelect.value !== '0') {
                customFoodInput.disabled = true;
            }
        });
    </script>
</body>
</html>
