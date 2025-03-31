<?php
session_start();
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$suggestion_id = $_GET['id'] ?? null;

if (!$suggestion_id) {
    $_SESSION['error_message'] = "ID de suggestion manquant.";
    redirect('my-coach.php');
}

// Récupérer les informations de la suggestion
$sql = "SELECT * FROM ai_suggestions WHERE id = ? AND user_id = ?";
$suggestion = fetchOne($sql, [$suggestion_id, $user_id]);

if (!$suggestion) {
    $_SESSION['error_message'] = "Suggestion non trouvée.";
    redirect('my-coach.php');
}

$suggestion_data = json_decode($suggestion['content'], true);

// Récupérer les aliments de la suggestion
$sql = "SELECT sf.*, f.name as food_name 
        FROM suggestion_foods sf 
        JOIN foods f ON sf.food_id = f.id 
        WHERE sf.suggestion_id = ?";
$foods = fetchAll($sql, [$suggestion_id]);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_foods = $_POST['selected_foods'] ?? [];
    $meal_name = $_POST['meal_name'] ?? '';
    
    if (empty($selected_foods) || empty($meal_name)) {
        $error_message = "Veuillez sélectionner au moins un aliment et donner un nom au repas.";
    } else {
        try {
            // Créer le repas
            $sql = "INSERT INTO meals (user_id, name, date) VALUES (?, ?, NOW())";
            $meal_id = insert($sql, [$user_id, $meal_name]);
            
            if ($meal_id) {
                // Ajouter les aliments sélectionnés au repas
                foreach ($selected_foods as $food_id) {
                    $sql = "INSERT INTO meal_foods (meal_id, food_id, quantity, calories, protein, carbs, fat) 
                            SELECT ?, food_id, quantity, calories, protein, carbs, fat 
                            FROM suggestion_foods 
                            WHERE suggestion_id = ? AND food_id = ?";
                    insert($sql, [$meal_id, $suggestion_id, $food_id]);
                }
                
                $_SESSION['success_message'] = "Repas créé avec succès !";
                redirect('my-coach.php');
            } else {
                throw new Exception("Erreur lors de la création du repas");
            }
        } catch (Exception $e) {
            $error_message = "Une erreur est survenue lors de la création du repas.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un repas - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h1>Créer un repas à partir de la suggestion</h1>
                <p class="text-muted">Sélectionnez les aliments que vous souhaitez inclure dans votre repas.</p>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" id="createMealForm">
                            <div class="mb-3">
                                <label for="meal_name" class="form-label">Nom du repas</label>
                                <input type="text" class="form-control" id="meal_name" name="meal_name" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Aliments disponibles</label>
                                <div class="list-group">
                                    <?php foreach ($foods as $food): ?>
                                        <div class="list-group-item">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="selected_foods[]" 
                                                       value="<?php echo $food['food_id']; ?>" 
                                                       id="food_<?php echo $food['food_id']; ?>">
                                                <label class="form-check-label" for="food_<?php echo $food['food_id']; ?>">
                                                    <?php echo htmlspecialchars($food['food_name']); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        Quantité: <?php echo $food['quantity']; ?> |
                                                        Calories: <?php echo $food['calories']; ?> |
                                                        Protéines: <?php echo $food['protein']; ?>g |
                                                        Glucides: <?php echo $food['carbs']; ?>g |
                                                        Lipides: <?php echo $food['fat']; ?>g
                                                    </small>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="my-coach.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Retour
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Créer le repas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('createMealForm').addEventListener('submit', function(e) {
            const selectedFoods = document.querySelectorAll('input[name="selected_foods[]"]:checked');
            if (selectedFoods.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins un aliment.');
            }
        });
    </script>
</body>
</html> 