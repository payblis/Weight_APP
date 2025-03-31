<?php
session_start();
require_once 'includes/functions.php';

error_log("=== Début de l'ajout d'une suggestion ===");
error_log("User ID: " . $_SESSION['user_id']);

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    error_log("❌ Utilisateur non connecté");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']);
    exit;
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Requête non-POST détectée");
    header('Location: food-log.php');
    exit;
}

try {
    // Récupérer les données du formulaire
    $suggestion_id = $_POST['suggestion_id'] ?? null;
    $meal_type = $_POST['meal_type'] ?? null;
    $notes = $_POST['notes'] ?? '';
    
    error_log("Données reçues :");
    error_log("- Suggestion ID: " . $suggestion_id);
    error_log("- Meal Type: " . $meal_type);
    error_log("- Notes: " . $notes);
    
    if (!$suggestion_id || !$meal_type) {
        error_log("❌ Données manquantes");
        throw new Exception("Données manquantes");
    }
    
    // Récupérer la suggestion
    $sql = "SELECT content FROM ai_suggestions WHERE id = ? AND user_id = ?";
    $suggestion = fetchOne($sql, [$suggestion_id, $_SESSION['user_id']]);
    
    if (!$suggestion) {
        error_log("❌ Suggestion non trouvée pour l'ID: " . $suggestion_id);
        throw new Exception("Suggestion non trouvée");
    }
    
    error_log("✅ Suggestion trouvée");
    
    // Parser le JSON de la suggestion
    $data = json_decode($suggestion['content'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ Erreur de parsing JSON: " . json_last_error_msg());
        throw new Exception("Format de suggestion invalide");
    }
    
    error_log("✅ JSON parsé avec succès");
    error_log("Contenu du JSON: " . print_r($data, true));
    
    // Vérifier la présence des valeurs nutritionnelles
    if (!isset($data['valeurs_nutritionnelles'])) {
        error_log("⚠️ Valeurs nutritionnelles manquantes dans le JSON");
        $data['valeurs_nutritionnelles'] = [
            'calories' => 0,
            'proteines' => 0,
            'glucides' => 0,
            'lipides' => 0
        ];
    }
    
    error_log("Valeurs nutritionnelles utilisées :");
    error_log("- Calories: " . ($data['valeurs_nutritionnelles']['calories'] ?? 0));
    error_log("- Protéines: " . ($data['valeurs_nutritionnelles']['proteines'] ?? 0));
    error_log("- Glucides: " . ($data['valeurs_nutritionnelles']['glucides'] ?? 0));
    error_log("- Lipides: " . ($data['valeurs_nutritionnelles']['lipides'] ?? 0));
    
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
        error_log("❌ Erreur lors de la création du repas");
        throw new Exception("Erreur lors de la création du repas");
    }
    
    error_log("✅ Repas créé avec l'ID: " . $meal_id);
    
    // Ajouter les ingrédients
    error_log("=== Début de l'ajout des ingrédients ===");
    foreach ($data['ingredients'] as $index => $ingredient) {
        error_log("Traitement de l'ingrédient " . ($index + 1) . ":");
        error_log("- Nom: " . $ingredient['nom']);
        error_log("- Quantité: " . $ingredient['quantité']);
        
        // Créer l'aliment s'il n'existe pas
        $food_id = createOrGetFood($ingredient['nom']);
        error_log("✅ Aliment créé/récupéré avec l'ID: " . $food_id);
        
        // Calculer les valeurs nutritionnelles pour la quantité spécifiée
        $nutrition = calculateNutritionForQuantity($food_id, $ingredient['quantité']);
        error_log("Valeurs nutritionnelles calculées:");
        error_log("- Calories: " . $nutrition['calories']);
        error_log("- Protéines: " . $nutrition['protein']);
        error_log("- Glucides: " . $nutrition['carbs']);
        error_log("- Lipides: " . $nutrition['fat']);
        
        // Ajouter l'aliment au repas
        $sql = "INSERT INTO meal_foods (meal_id, food_id, quantity, calories, protein, carbs, fat) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $result = insert($sql, [
            $meal_id,
            $food_id,
            $ingredient['quantité'],
            $nutrition['calories'],
            $nutrition['protein'],
            $nutrition['carbs'],
            $nutrition['fat']
        ]);
        
        if ($result) {
            error_log("✅ Aliment ajouté au repas avec succès");
        } else {
            error_log("❌ Erreur lors de l'ajout de l'aliment au repas");
        }
    }
    error_log("=== Fin de l'ajout des ingrédients ===");
    
    // Rediriger vers la page des repas avec un message de succès
    $_SESSION['success_message'] = "Le repas a été ajouté avec succès";
    error_log("✅ Redirection vers food-log.php");
    header('Location: food-log.php');
    exit;
    
} catch (Exception $e) {
    error_log("❌ Erreur: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
    header('Location: food-log.php');
    exit;
} 