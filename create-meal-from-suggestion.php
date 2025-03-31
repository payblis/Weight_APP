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

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer les aliments créés
        $ingredient_names = array_column($data['ingredients'], 'nom');
        $placeholders = str_repeat('?,', count($ingredient_names) - 1) . '?';
        
        $sql = "SELECT id, name FROM foods WHERE name IN ($placeholders)";
        $foods = fetchAll($sql, $ingredient_names);
        
        // Créer le repas
        $sql = "INSERT INTO meals (user_id, meal_type, log_date, total_calories, total_protein, total_carbs, total_fat, notes, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $user_id,
            $_POST['meal_type'],
            $_POST['log_date'],
            $data['valeurs_nutritionnelles']['calories'],
            $data['valeurs_nutritionnelles']['proteines'],
            $data['valeurs_nutritionnelles']['glucides'],
            $data['valeurs_nutritionnelles']['lipides'],
            $_POST['notes']
        ];
        
        $meal_id = insert($sql, $params);
        
        if (!$meal_id) {
            throw new Exception("Erreur lors de la création du repas");
        }
        
        // Ajouter les aliments au repas
        foreach ($foods as $food) {
            $ingredient = array_filter($data['ingredients'], function($i) use ($food) {
                return $i['nom'] === $food['name'];
            });
            
            if (!empty($ingredient)) {
                $ingredient = reset($ingredient);
                
                $sql = "INSERT INTO food_logs (user_id, food_id, quantity, log_date, meal_id, is_part_of_meal, created_at) 
                        VALUES (?, ?, ?, ?, ?, 1, NOW())";
                
                insert($sql, [
                    $user_id,
                    $food['id'],
                    $ingredient['quantite'],
                    $_POST['log_date'],
                    $meal_id
                ]);
            }
        }
        
        $_SESSION['success_message'] = "Le repas a été créé avec succès";
        redirect('food-log.php');
        
    } catch (Exception $e) {
        $errors[] = "Erreur lors de la création du repas : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer le repas - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h1>Créer le repas</h1>
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
                    <h5 class="mb-0">Détails du repas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="meal_type" class="form-label">Type de repas</label>
                            <select name="meal_type" id="meal_type" class="form-select" required>
                                <option value="">Sélectionner un type</option>
                                <option value="petit_dejeuner">Petit-déjeuner</option>
                                <option value="dejeuner">Déjeuner</option>
                                <option value="diner">Dîner</option>
                                <option value="collation">Collation</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="log_date" class="form-label">Date</label>
                            <input type="date" name="log_date" id="log_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ingrédient</th>
                                    <th>Quantité</th>
                                    <th>Calories</th>
                                    <th>Protéines</th>
                                    <th>Glucides</th>
                                    <th>Lipides</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['ingredients'] as $ingredient): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ingredient['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($ingredient['quantite']); ?></td>
                                        <td><?php echo $ingredient['calories']; ?></td>
                                        <td><?php echo $ingredient['proteines']; ?>g</td>
                                        <td><?php echo $ingredient['glucides']; ?>g</td>
                                        <td><?php echo $ingredient['lipides']; ?>g</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th>Total</th>
                                    <th></th>
                                    <th><?php echo $data['valeurs_nutritionnelles']['calories']; ?></th>
                                    <th><?php echo $data['valeurs_nutritionnelles']['proteines']; ?>g</th>
                                    <th><?php echo $data['valeurs_nutritionnelles']['glucides']; ?>g</th>
                                    <th><?php echo $data['valeurs_nutritionnelles']['lipides']; ?>g</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="my-coach.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Créer le repas
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 