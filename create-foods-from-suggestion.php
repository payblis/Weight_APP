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
        // Récupérer les catégories d'aliments
        $sql = "SELECT id, name FROM food_categories ORDER BY name";
        $categories = fetchAll($sql);

        // Insérer chaque aliment
        foreach ($data['ingredients'] as $ingredient) {
            $sql = "INSERT INTO foods (name, description, calories, protein, carbs, fat, serving_size, category_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $ingredient['nom'],
                "Ingrédient de {$data['nom_du_repas']}",
                $ingredient['calories'],
                $ingredient['proteines'],
                $ingredient['glucides'],
                $ingredient['lipides'],
                $ingredient['quantite'],
                $_POST['category_id'][$ingredient['nom']] ?? null
            ];
            
            insert($sql, $params);
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
                                    <th>Quantité</th>
                                    <th>Calories</th>
                                    <th>Protéines</th>
                                    <th>Glucides</th>
                                    <th>Lipides</th>
                                    <th>Catégorie</th>
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