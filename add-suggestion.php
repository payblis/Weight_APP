<?php
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']);
    exit;
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: food-log.php');
    exit;
}

try {
    // Récupérer les données du formulaire
    $suggestion_id = $_POST['suggestion_id'] ?? null;
    $meal_type = $_POST['meal_type'] ?? null;
    $notes = $_POST['notes'] ?? '';
    
    if (!$suggestion_id || !$meal_type) {
        throw new Exception("Données manquantes");
    }
    
    // Récupérer la suggestion
    $sql = "SELECT content FROM ai_suggestions WHERE id = ? AND user_id = ?";
    $suggestion = fetchOne($sql, [$suggestion_id, $_SESSION['user_id']]);
    
    if (!$suggestion) {
        throw new Exception("Suggestion non trouvée");
    }
    
    // Parser le JSON de la suggestion
    $data = json_decode($suggestion['content'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Format de suggestion invalide");
    }
    
    // Créer le repas
    $sql = "INSERT INTO meals (user_id, meal_type, log_date, notes, total_calories, total_protein, total_carbs, total_fat) 
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
    $meal_id = insert($sql, [
        $_SESSION['user_id'],
        $meal_type,
        $notes,
        $data['valeurs_nutritionnelles']['calories'] ?? 0,
        $data['valeurs_nutritionnelles']['proteines'] ?? 0,
        $data['valeurs_nutritionnelles']['glucides'] ?? 0,
        $data['valeurs_nutritionnelles']['lipides'] ?? 0
    ]);
    
    if (!$meal_id) {
        throw new Exception("Erreur lors de la création du repas");
    }
    
    // Ajouter les ingrédients
    foreach ($data['ingredients'] as $ingredient) {
        // Créer l'aliment s'il n'existe pas
        $food_id = createOrGetFood($ingredient['nom']);
        
        // Calculer les valeurs nutritionnelles pour la quantité spécifiée
        $nutrition = calculateNutritionForQuantity($food_id, $ingredient['quantité']);
        
        // Ajouter l'aliment au repas
        $sql = "INSERT INTO meal_foods (meal_id, food_id, quantity, calories, protein, carbs, fat) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        insert($sql, [
            $meal_id,
            $food_id,
            $ingredient['quantité'],
            $nutrition['calories'],
            $nutrition['protein'],
            $nutrition['carbs'],
            $nutrition['fat']
        ]);
    }
    
    // Rediriger vers la page des repas avec un message de succès
    $_SESSION['success_message'] = "Le repas a été ajouté avec succès";
    header('Location: food-log.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
    header('Location: food-log.php');
    exit;
} 