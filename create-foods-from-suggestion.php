<?php
session_start();
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$suggestion_id = $_GET['id'] ?? null;
$success_message = '';
$errors = [];

if (!$suggestion_id) {
    $_SESSION['error_message'] = "ID de suggestion manquant";
    redirect('my-coach.php');
}

// Récupérer la suggestion
$sql = "SELECT * FROM ai_suggestions WHERE id = ? AND user_id = ?";
$suggestion = fetchOne($sql, [$suggestion_id, $user_id]);

if (!$suggestion) {
    $_SESSION['error_message'] = "Suggestion non trouvée";
    redirect('my-coach.php');
}

// Parser le contenu de la suggestion
$data = json_decode($suggestion['content'], true);

if (!$data || !isset($data['ingredients'])) {
    $_SESSION['error_message'] = "Format de suggestion invalide";
    redirect('my-coach.php');
}

// Liste des ingrédients à exclure
$excluded_ingredients = [
    'sel', 'poivre', 'huile d\'olive', 'huile', 'vinaigre', 'jus de citron',
    'herbes', 'épices', 'assaisonnement', 'condiment'
];

// Fonction pour vérifier si un ingrédient doit être exclu
function shouldExcludeIngredient($ingredient) {
    global $excluded_ingredients;
    $name = strtolower($ingredient['nom']);
    foreach ($excluded_ingredients as $excluded) {
        if (strpos($name, $excluded) !== false) {
            return true;
        }
    }
    return false;
}

// Fonction pour calculer les macronutriments par ingrédient
function calculateIngredientMacros($ingredient) {
    // Si l'ingrédient a déjà ses propres valeurs nutritionnelles, les utiliser
    if (isset($ingredient['calories']) && isset($ingredient['proteines']) && 
        isset($ingredient['glucides']) && isset($ingredient['lipides'])) {
        // Convertir les valeurs pour 100g
        $quantity = (float) str_replace(['g', 'kg', 'ml', 'l'], '', $ingredient['quantite']);
        if ($quantity > 0) {
            return [
                'calories' => round(($ingredient['calories'] * 100) / $quantity),
                'proteins' => round(($ingredient['proteines'] * 100) / $quantity, 1),
                'carbs' => round(($ingredient['glucides'] * 100) / $quantity, 1),
                'fats' => round(($ingredient['lipides'] * 100) / $quantity, 1)
            ];
        }
    }
    
    return null;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer les catégories d'aliments
        $sql = "SELECT * FROM food_categories ORDER BY name";
        $categories = fetchAll($sql);

        // Insérer chaque aliment
        foreach ($data['ingredients'] as $ingredient) {
            // Vérifier si l'ingrédient doit être exclu
            if (shouldExcludeIngredient($ingredient)) {
                continue;
            }
            
            $name = $ingredient['nom'];
            
            // Calculer les macronutriments pour cet ingrédient
            $macros = calculateIngredientMacros($ingredient);
            
            if ($macros) {
                $sql = "INSERT INTO foods (name, description, calories, protein, carbs, fat, category_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $params = [
                    $name,
                    "Ingrédient de {$data['nom_du_repas']}",
                    $macros['calories'],
                    $macros['proteins'],
                    $macros['carbs'],
                    $macros['fats'],
                    $_POST['category_id'][$name] ?? 7 // Catégorie par défaut : "Autres"
                ];
                
                insert($sql, $params);
            }
        }
        
        $_SESSION['success_message'] = "Les aliments ont été créés avec succès";
        redirect('my-coach.php');
        
    } catch (Exception $e) {
        $errors[] = "Erreur lors de la création des aliments : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer les aliments - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h1>Créer les aliments</h1>
                <p class="text-muted">Suggestion : <?php echo htmlspecialchars($data['nom_du_repas']); ?></p>
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

        <form method="post" action="">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Ingrédients à créer</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Calories (100g)</th>
                                    <th>Protéines (100g)</th>
                                    <th>Glucides (100g)</th>
                                    <th>Lipides (100g)</th>
                                    <th>Catégorie</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['ingredients'] as $ingredient): ?>
                                    <?php if (!shouldExcludeIngredient($ingredient)): ?>
                                        <?php 
                                        $macros = calculateIngredientMacros($ingredient);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ingredient['nom']); ?></td>
                                            <td><?php echo $macros['calories']; ?></td>
                                            <td><?php echo $macros['proteins']; ?>g</td>
                                            <td><?php echo $macros['carbs']; ?>g</td>
                                            <td><?php echo $macros['fats']; ?>g</td>
                                            <td>
                                                <select name="category_id[<?php echo htmlspecialchars($ingredient['nom']); ?>]" 
                                                        class="form-select">
                                                    <option value="">Sélectionner une catégorie</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>">
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="my-coach.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Créer les aliments
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 