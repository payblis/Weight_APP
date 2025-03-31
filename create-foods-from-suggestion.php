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
    // Valeurs nutritionnelles par défaut pour 100g d'ingrédients courants
    $default_values = [
        'épinards' => ['calories' => 23, 'proteins' => 2.9, 'glucides' => 3.6, 'lipides' => 0.4],
        'épinards frais' => ['calories' => 23, 'proteins' => 2.9, 'glucides' => 3.6, 'lipides' => 0.4],
        'tomate' => ['calories' => 18, 'proteins' => 0.9, 'glucides' => 3.9, 'lipides' => 0.2],
        'feta' => ['calories' => 264, 'proteins' => 14, 'glucides' => 4.1, 'lipides' => 21],
        'fromage feta' => ['calories' => 264, 'proteins' => 14, 'glucides' => 4.1, 'lipides' => 21],
        'œufs' => ['calories' => 155, 'proteins' => 13, 'glucides' => 1.1, 'lipides' => 11],
        'oeufs' => ['calories' => 155, 'proteins' => 13, 'glucides' => 1.1, 'lipides' => 11],
        'pain' => ['calories' => 265, 'proteins' => 9, 'glucides' => 49, 'lipides' => 3.2],
        'pain de blé entier' => ['calories' => 247, 'proteins' => 13, 'glucides' => 41, 'lipides' => 3.4]
    ];

    // Si l'ingrédient a déjà ses propres valeurs nutritionnelles, les utiliser
    if (isset($ingredient['calories']) && isset($ingredient['proteines']) && 
        isset($ingredient['glucides']) && isset($ingredient['lipides'])) {
        
        // Si la quantité est déjà en 100g, retourner directement les valeurs
        if ($ingredient['quantite'] === '100g') {
            return [
                'calories' => round($ingredient['calories']),
                'proteins' => round($ingredient['proteines'], 1),
                'carbs' => round($ingredient['glucides'], 1),
                'fats' => round($ingredient['lipides'], 1)
            ];
        }
        
        // Si la quantité est en grammes, convertir pour 100g
        if (strpos($ingredient['quantite'], 'g') !== false) {
            $quantity = (float) str_replace('g', '', $ingredient['quantite']);
            if ($quantity > 0) {
                return [
                    'calories' => round(($ingredient['calories'] * 100) / $quantity),
                    'proteins' => round(($ingredient['proteines'] * 100) / $quantity, 1),
                    'carbs' => round(($ingredient['glucides'] * 100) / $quantity, 1),
                    'fats' => round(($ingredient['lipides'] * 100) / $quantity, 1)
                ];
            }
        }
        
        // Facteurs de conversion pour les unités courantes
        $conversion_factors = [
            'petit œuf' => 40,
            'gros œuf' => 50,
            'très gros œuf' => 60,
            'cuillère à soupe' => [
                'huile' => 15,
                'sucre' => 12,
                'farine' => 8,
                'miel' => 21
            ],
            'cuillère à café' => [
                'huile' => 5,
                'sucre' => 4,
                'sel' => 6
            ],
            'tasse' => [
                'épinards' => 30,
                'feuilles' => 30,
                'flocons d\'avoine' => 90,
                'riz cuit' => 195,
                'riz cru' => 180,
                'lait' => 240,
                'farine' => 120,
                'sucre' => 200,
                'fruits rouges' => 150
            ],
            'tranche' => [
                'pain' => 30
            ],
            'baguette française' => 250,
            'pita moyenne' => 60,
            'tortilla de blé' => 45,
            'tomate moyenne' => 100,
            'carotte moyenne' => 60,
            'pomme moyenne' => 180,
            'banane moyenne' => 120,
            'avocat moyen' => 150,
            'poivron moyen' => 120,
            'oignon moyen' => 110,
            'pomme de terre moyenne' => 150,
            'portion de fromage' => 30,
            'yaourt nature' => 125,
            'verre de lait' => 240
        ];
        
        // Chercher le facteur de conversion approprié
        $factor = null;
        $quantity = $ingredient['quantite'];
        
        // Vérifier les unités avec sous-catégories
        if (strpos($quantity, 'cuillère à soupe') !== false) {
            foreach ($conversion_factors['cuillère à soupe'] as $subtype => $value) {
                if (strpos(strtolower($ingredient['nom']), $subtype) !== false) {
                    $factor = $value;
                    break;
                }
            }
        } elseif (strpos($quantity, 'cuillère à café') !== false) {
            foreach ($conversion_factors['cuillère à café'] as $subtype => $value) {
                if (strpos(strtolower($ingredient['nom']), $subtype) !== false) {
                    $factor = $value;
                    break;
                }
            }
        } elseif (strpos($quantity, 'tasse') !== false) {
            foreach ($conversion_factors['tasse'] as $subtype => $value) {
                if (strpos(strtolower($ingredient['nom']), $subtype) !== false) {
                    $factor = $value;
                    break;
                }
            }
        } elseif (strpos($quantity, 'tranche') !== false) {
            foreach ($conversion_factors['tranche'] as $subtype => $value) {
                if (strpos(strtolower($ingredient['nom']), $subtype) !== false) {
                    $factor = $value;
                    break;
                }
            }
        } else {
            // Vérifier les unités simples
            foreach ($conversion_factors as $unit => $value) {
                if (is_numeric($value) && strpos($quantity, $unit) !== false) {
                    $factor = $value;
                    break;
                }
            }
        }
        
        // Si un facteur de conversion a été trouvé, calculer les valeurs pour 100g
        if ($factor !== null) {
            return [
                'calories' => round(($ingredient['calories'] * 100) / $factor),
                'proteins' => round(($ingredient['proteines'] * 100) / $factor, 1),
                'carbs' => round(($ingredient['glucides'] * 100) / $factor, 1),
                'fats' => round(($ingredient['lipides'] * 100) / $factor, 1)
            ];
        }
    }
    
    // Si aucune valeur n'a été calculée, utiliser les valeurs par défaut
    $name = strtolower($ingredient['nom']);
    foreach ($default_values as $key => $values) {
        if (strpos($name, $key) !== false) {
            return [
                'calories' => $values['calories'],
                'proteins' => $values['proteins'],
                'carbs' => $values['glucides'],
                'fats' => $values['lipides']
            ];
        }
    }
    
    return null;
}

// Récupérer les catégories d'aliments
$sql = "SELECT * FROM food_categories ORDER BY name";
$categories = fetchAll($sql);

// Fonction pour vérifier si un aliment similaire existe déjà
function findSimilarFood($name, $pdo) {
    // Nettoyer le nom pour la recherche
    $searchName = strtolower(trim($name));
    
    // Rechercher les aliments similaires
    $sql = "SELECT id, name FROM foods WHERE LOWER(name) LIKE ? OR LOWER(name) LIKE ? OR LOWER(name) LIKE ?";
    $params = [
        "%$searchName%",
        "%" . str_replace(' ', '%', $searchName) . "%",
        "%" . str_replace('é', 'e', $searchName) . "%"
    ];
    
    return fetchAll($sql, $params);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérifier que toutes les catégories sont sélectionnées
        $missing_categories = [];
        foreach ($data['ingredients'] as $ingredient) {
            if (!shouldExcludeIngredient($ingredient)) {
                $name = $ingredient['nom'];
                if (empty($_POST['category_id'][$name])) {
                    $missing_categories[] = $name;
                }
            }
        }

        if (!empty($missing_categories)) {
            throw new Exception("Veuillez sélectionner une catégorie pour les ingrédients suivants : " . implode(", ", $missing_categories));
        }

        // Insérer chaque aliment
        foreach ($data['ingredients'] as $ingredient) {
            // Vérifier si l'ingrédient doit être exclu
            if (shouldExcludeIngredient($ingredient)) {
                continue;
            }
            
            $name = $ingredient['nom'];
            
            // Vérifier si un aliment similaire existe
            $similarFoods = findSimilarFood($name, $pdo);
            
            if (!empty($similarFoods)) {
                // Si l'utilisateur a confirmé l'ajout malgré les doublons
                if (isset($_POST['confirm_add'][$name]) && $_POST['confirm_add'][$name] === 'yes') {
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
                            $_POST['category_id'][$name]
                        ];
                        
                        insert($sql, $params);
                    }
                }
            } else {
                // Aucun aliment similaire trouvé, ajouter normalement
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
                        $_POST['category_id'][$name]
                    ];
                    
                    insert($sql, $params);
                }
            }
        }
        
        $_SESSION['success_message'] = "Les aliments ont été créés avec succès";
        redirect('my-coach.php');
        
    } catch (Exception $e) {
        $errors[] = "Erreur lors de la création des aliments : " . $e->getMessage();
    }
}

// Récupérer les aliments similaires pour l'affichage
$similarFoodsMap = [];
foreach ($data['ingredients'] as $ingredient) {
    if (!shouldExcludeIngredient($ingredient)) {
        $similarFoodsMap[$ingredient['nom']] = findSimilarFood($ingredient['nom'], $pdo);
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
                                        $name = $ingredient['nom'];
                                        $similarFoods = $similarFoodsMap[$name] ?? [];
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($name); ?></td>
                                            <td><?php echo $macros['calories']; ?></td>
                                            <td><?php echo $macros['proteins']; ?>g</td>
                                            <td><?php echo $macros['carbs']; ?>g</td>
                                            <td><?php echo $macros['fats']; ?>g</td>
                                            <td>
                                                <select name="category_id[<?php echo htmlspecialchars($name); ?>]" 
                                                        class="form-select" required>
                                                    <option value="">Sélectionner une catégorie</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>">
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <?php if (!empty($similarFoods)): ?>
                                            <tr class="table-warning">
                                                <td colspan="6">
                                                    <div class="alert alert-warning mb-0">
                                                        <strong>Attention :</strong> Des aliments similaires existent déjà dans la base de données :
                                                        <ul class="mb-0">
                                                            <?php foreach ($similarFoods as $similar): ?>
                                                                <li><?php echo htmlspecialchars($similar['name']); ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                        <div class="form-check mt-2">
                                                            <input type="checkbox" 
                                                                   class="form-check-input" 
                                                                   name="confirm_add[<?php echo htmlspecialchars($name); ?>]" 
                                                                   value="yes" 
                                                                   id="confirm_<?php echo htmlspecialchars($name); ?>">
                                                            <label class="form-check-label" for="confirm_<?php echo htmlspecialchars($name); ?>">
                                                                Je confirme que je souhaite ajouter cet aliment malgré les doublons
                                                            </label>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
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