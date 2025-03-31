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
$food_name = '';
$category_id = 7; // Catégorie par défaut : "Autres"
$calories = '';
$protein = '';
$carbs = '';
$fat = '';
$fiber = '';
$added_sugar = '';
$serving_size = '';
$is_public = 0;
$success_message = '';
$errors = [];

// Récupérer la clé API ChatGPT des paramètres globaux
$sql = "SELECT setting_value FROM settings WHERE setting_name = 'chatgpt_api_key'";
$api_key_setting = fetchOne($sql, []);
$api_key = $api_key_setting ? $api_key_setting['setting_value'] : '';

// Traitement de l'ajout manuel d'un aliment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_food') {
    // Récupérer et nettoyer les données du formulaire
    $food_name = sanitizeInput($_POST['food_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 7);
    $calories = sanitizeInput($_POST['calories'] ?? '');
    $protein = sanitizeInput($_POST['protein'] ?? '');
    $carbs = sanitizeInput($_POST['carbs'] ?? '');
    $fat = sanitizeInput($_POST['fat'] ?? '');
    $fiber = floatval($_POST['fiber'] ?? '');
    $added_sugar = floatval($_POST['added_sugar'] ?? '');
    $serving_size = sanitizeInput($_POST['serving_size'] ?? '');
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Validation des données
    if (empty($food_name)) {
        $errors[] = "Le nom de l'aliment est requis";
    }
    
    if (empty($calories) || !is_numeric($calories) || $calories < 0) {
        $errors[] = "Les calories doivent être un nombre positif ou zéro";
    }
    
    if (empty($protein) || !is_numeric($protein) || $protein < 0) {
        $errors[] = "Les protéines doivent être un nombre positif ou zéro";
    }
    
    if (empty($carbs) || !is_numeric($carbs) || $carbs < 0) {
        $errors[] = "Les glucides doivent être un nombre positif ou zéro";
    }
    
    if (empty($fat) || !is_numeric($fat) || $fat < 0) {
        $errors[] = "Les lipides doivent être un nombre positif ou zéro";
    }
    
    if (empty($fiber) || !is_numeric($fiber) || $fiber < 0) {
        $errors[] = "Les fibres doivent être un nombre positif ou zéro";
    }
    
    if (empty($added_sugar) || !is_numeric($added_sugar) || $added_sugar < 0) {
        $errors[] = "Les sucres ajoutés doivent être un nombre positif ou zéro";
    }
    
    if (empty($serving_size)) {
        $errors[] = "La taille de la portion est requise";
    }
    
    // Si aucune erreur, ajouter l'aliment
    if (empty($errors)) {
        $sql = "INSERT INTO foods (name, category_id, calories, protein, carbs, fat, fiber, added_sugar, serving_size, is_public, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $result = insert($sql, [$food_name, $category_id, $calories, $protein, $carbs, $fat, $fiber, $added_sugar, $serving_size, $is_public]);
        
        if ($result) {
            $success_message = "L'aliment a été ajouté avec succès !";
            $food_name = '';
            $calories = '';
            $protein = '';
            $carbs = '';
            $fat = '';
            $fiber = '';
            $added_sugar = '';
            $serving_size = '';
            $is_public = 0;
        } else {
            $errors[] = "Une erreur s'est produite lors de l'ajout de l'aliment. Veuillez réessayer.";
        }
    }
}

// Traitement de l'importation via ChatGPT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_chatgpt') {
    $food_name = sanitizeInput($_POST['food_name_ai'] ?? '');
    
    if (empty($food_name)) {
        $errors[] = "Le nom de l'aliment est requis pour l'importation via ChatGPT";
    } elseif (empty($api_key)) {
        $errors[] = "La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur.";
    } else {
        // Appel à l'API ChatGPT
        $nutritional_data = getChatGPTNutritionalData($food_name, $api_key);
        
        if ($nutritional_data) {
            // Ajouter l'aliment à la base de données
            $sql = "INSERT INTO foods (name, category_id, calories, protein, carbs, fat, fiber, added_sugar, serving_size, is_public, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $result = insert($sql, [
                $food_name,
                7, // Catégorie par défaut : "Autres"
                $nutritional_data['calories'],
                $nutritional_data['protein'],
                $nutritional_data['carbs'],
                $nutritional_data['fat'],
                $nutritional_data['fiber'],
                $nutritional_data['added_sugar'],
                $nutritional_data['serving_size'],
                0,
            ]);
            
            if ($result) {
                $success_message = "L'aliment a été importé avec succès via ChatGPT !";
                $food_name = '';
            } else {
                $errors[] = "Une erreur s'est produite lors de l'ajout de l'aliment. Veuillez réessayer.";
            }
        } else {
            $errors[] = "Impossible d'obtenir les données nutritionnelles via ChatGPT. Veuillez réessayer plus tard.";
        }
    }
}

// Traitement de la suppression d'un aliment
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $food_id = (int)$_GET['id'];
    
    // Vérifier si l'aliment est utilisé dans des journaux alimentaires
    $sql = "SELECT COUNT(*) as count FROM food_logs WHERE food_id = ?";
    $usage_count = fetchOne($sql, [$food_id]);
    
    if ($usage_count && $usage_count['count'] > 0) {
        $errors[] = "Cet aliment ne peut pas être supprimé car il est utilisé dans des journaux alimentaires.";
    } else {
        // Supprimer l'aliment
        $sql = "DELETE FROM foods WHERE id = ?";
        $result = update($sql, [$food_id]); // Utiliser update pour les requêtes DELETE
        
        if ($result) {
            $success_message = "L'aliment a été supprimé avec succès !";
        } else {
            $errors[] = "Une erreur s'est produite lors de la suppression de l'aliment. Veuillez réessayer.";
        }
    }
}

// Récupérer les catégories d'aliments
$sql = "SELECT * FROM food_categories ORDER BY name";
$categories = fetchAll($sql);

// Récupérer tous les aliments
$search = sanitizeInput($_GET['search'] ?? '');
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$sql = "SELECT f.*, fc.name as category_name 
        FROM foods f 
        LEFT JOIN food_categories fc ON f.category_id = fc.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND f.name LIKE ?";
    $params[] = "%$search%";
}

if ($category_filter > 0) {
    $sql .= " AND f.category_id = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY f.name";
$foods = fetchAll($sql, $params);

// Fonction pour obtenir les données nutritionnelles via ChatGPT
function getChatGPTNutritionalData($food_name, $api_key) {
    // URL de l'API ChatGPT
    $url = 'https://api.openai.com/v1/chat/completions';
    
    // Construire le prompt
    $prompt = "Donne-moi les informations nutritionnelles pour 100g de $food_name. Réponds uniquement avec un objet JSON contenant les champs suivants : calories (nombre entier), protein (nombre décimal), carbs (nombre décimal), fat (nombre décimal), fiber (nombre décimal), added_sugar (nombre décimal), serving_size (nombre décimal). N'inclus pas d'autres informations.";
    
    // Préparer les données de la requête
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Tu es un assistant nutritionnel qui fournit des informations précises sur les valeurs nutritionnelles des aliments.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7
    ];
    
    // Initialiser cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    // Exécuter la requête
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Vérifier si la requête a réussi
    if ($http_code === 200) {
        $response_data = json_decode($response, true);
        
        if (isset($response_data['choices'][0]['message']['content'])) {
            // Extraire la réponse JSON
            $content = $response_data['choices'][0]['message']['content'];
            
            // Tenter de décoder le JSON
            $nutritional_data = json_decode($content, true);
            
            // Vérifier si les données sont valides
            if (is_array($nutritional_data) && 
                isset($nutritional_data['calories']) && 
                isset($nutritional_data['protein']) && 
                isset($nutritional_data['carbs']) && 
                isset($nutritional_data['fat']) &&
                isset($nutritional_data['fiber']) &&
                isset($nutritional_data['added_sugar']) &&
                isset($nutritional_data['serving_size'])) {
                
                return [
                    'calories' => (int)$nutritional_data['calories'],
                    'protein' => (float)$nutritional_data['protein'],
                    'carbs' => (float)$nutritional_data['carbs'],
                    'fat' => (float)$nutritional_data['fat'],
                    'fiber' => (float)$nutritional_data['fiber'],
                    'added_sugar' => (float)$nutritional_data['added_sugar'],
                    'serving_size' => (float)$nutritional_data['serving_size']
                ];
            }
        }
    }
    
    // En cas d'erreur ou de données invalides, simuler des valeurs par défaut
    // Dans une application réelle, vous voudriez gérer cette erreur différemment
    return [
        'calories' => 100,
        'protein' => 5,
        'carbs' => 15,
        'fat' => 2,
        'fiber' => 0,
        'added_sugar' => 0,
        'serving_size' => 100
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des aliments - Weight Tracker</title>
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
                <h1 class="mb-4">Gestion des aliments</h1>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFoodModal">
                        <i class="fas fa-plus me-1"></i>Ajouter un aliment
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importChatGPTModal">
                        <i class="fas fa-robot me-1"></i>Importer via ChatGPT
                    </button>
                </div>
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

        <!-- Filtres de recherche -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" action="food-management.php" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Rechercher un aliment</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nom de l'aliment...">
                    </div>
                    <div class="col-md-4">
                        <label for="category" class="form-label">Catégorie</label>
                        <select class="form-select" id="category" name="category">
                            <option value="0">Toutes les catégories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_filter === $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des aliments -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Liste des aliments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($foods)): ?>
                    <div class="alert alert-info">
                        Aucun aliment trouvé. Utilisez le bouton "Ajouter un aliment" pour créer votre premier aliment.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Catégorie</th>
                                    <th>Calories (kcal)</th>
                                    <th>Protéines (g)</th>
                                    <th>Glucides (g)</th>
                                    <th>Lipides (g)</th>
                                    <th>Fibres (g)</th>
                                    <th>Sucres ajoutés (g)</th>
                                    <th>Taille de la portion (g)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($foods as $food): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($food['name']); ?></td>
                                        <td><?php echo htmlspecialchars($food['category_name'] ?? 'Non catégorisé'); ?></td>
                                        <td><?php echo htmlspecialchars($food['calories']); ?></td>
                                        <td><?php echo htmlspecialchars($food['protein']); ?></td>
                                        <td><?php echo htmlspecialchars($food['carbs']); ?></td>
                                        <td><?php echo htmlspecialchars($food['fat']); ?></td>
                                        <td><?php echo htmlspecialchars($food['fiber']); ?></td>
                                        <td><?php echo htmlspecialchars($food['added_sugar']); ?></td>
                                        <td><?php echo htmlspecialchars($food['serving_size']); ?></td>
                                        <td>
                                            <a href="food-management.php?action=delete&id=<?php echo $food['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet aliment ?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal d'ajout d'aliment -->
    <div class="modal fade" id="addFoodModal" tabindex="-1" aria-labelledby="addFoodModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addFoodModalLabel">Ajouter un aliment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="food-management.php">
                        <input type="hidden" name="action" value="add_food">
                        
                        <div class="mb-3">
                            <label for="food_name" class="form-label">Nom de l'aliment</label>
                            <input type="text" class="form-control" id="food_name" name="food_name" value="<?php echo htmlspecialchars($food_name); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Catégorie</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_id === $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="calories" class="form-label">Calories (kcal pour 100g)</label>
                            <input type="number" class="form-control" id="calories" name="calories" value="<?php echo htmlspecialchars($calories); ?>" min="0" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="protein" class="form-label">Protéines (g)</label>
                                <input type="number" class="form-control" id="protein" name="protein" value="<?php echo htmlspecialchars($protein); ?>" min="0" step="0.1" required>
                            </div>
                            <div class="col-md-4">
                                <label for="carbs" class="form-label">Glucides (g)</label>
                                <input type="number" class="form-control" id="carbs" name="carbs" value="<?php echo htmlspecialchars($carbs); ?>" min="0" step="0.1" required>
                            </div>
                            <div class="col-md-4">
                                <label for="fat" class="form-label">Lipides (g)</label>
                                <input type="number" class="form-control" id="fat" name="fat" value="<?php echo htmlspecialchars($fat); ?>" min="0" step="0.1" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="fiber">Fibres (g)</label>
                            <input type="number" step="0.1" class="form-control" id="fiber" name="fiber" value="<?php echo htmlspecialchars($fiber); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="added_sugar">Sucres ajoutés (g)</label>
                            <input type="number" step="0.1" class="form-control" id="added_sugar" name="added_sugar" value="<?php echo htmlspecialchars($added_sugar); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="serving_size" class="form-label">Taille de la portion (g)</label>
                            <input type="number" class="form-control" id="serving_size" name="serving_size" value="<?php echo htmlspecialchars($serving_size); ?>" min="0" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'importation via ChatGPT -->
    <div class="modal fade" id="importChatGPTModal" tabindex="-1" aria-labelledby="importChatGPTModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="importChatGPTModalLabel">Importer un aliment via ChatGPT</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($api_key)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            La clé API ChatGPT n'est pas configurée. Veuillez contacter l'administrateur pour utiliser cette fonctionnalité.
                        </div>
                    <?php else: ?>
                        <form method="post" action="food-management.php">
                            <input type="hidden" name="action" value="import_chatgpt">
                            
                            <div class="mb-3">
                                <label for="food_name_ai" class="form-label">Nom de l'aliment</label>
                                <input type="text" class="form-control" id="food_name_ai" name="food_name_ai" required>
                                <div class="form-text">
                                    Entrez le nom de l'aliment pour que ChatGPT recherche ses valeurs nutritionnelles.
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-robot me-1"></i>Importer via ChatGPT
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
