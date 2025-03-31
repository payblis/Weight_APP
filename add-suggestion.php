<?php
session_start();
require_once 'includes/functions.php';

error_log("=== Début de l'ajout d'une suggestion ===");
error_log("User ID: " . $_SESSION['user_id']);

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    error_log("❌ Utilisateur non connecté");
    $_SESSION['error_message'] = "Vous devez être connecté pour effectuer cette action";
    header('Location: food-log.php');
    exit;
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Requête non-POST détectée");
    $_SESSION['error_message'] = "Cette page ne peut être accédée que via un formulaire";
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
        $_SESSION['error_message'] = "Les données du formulaire sont incomplètes";
        header('Location: food-log.php');
        exit;
    }
    
    // Récupérer la suggestion
    $suggestion = fetchSuggestion($suggestion_id, $_SESSION['user_id']);
    if (!$suggestion) {
        error_log("❌ Suggestion non trouvée pour l'ID: " . $suggestion_id);
        $_SESSION['error_message'] = "La suggestion n'a pas été trouvée";
        header('Location: food-log.php');
        exit;
    }
    
    error_log("✅ Suggestion trouvée");
    
    // Parser le contenu de la suggestion
    $data = parseSuggestionContent($suggestion['content']);
    error_log("✅ JSON parsé avec succès");
    error_log("Contenu du JSON: " . print_r($data, true));
    
    // Calculer les valeurs nutritionnelles totales
    $totals = calculateTotalNutrition($data['ingredients']);
    error_log("=== Totaux nutritionnels ===");
    error_log("- Calories totales: " . $totals['calories']);
    error_log("- Protéines totales: " . $totals['protein']);
    error_log("- Glucides totaux: " . $totals['carbs']);
    error_log("- Lipides totaux: " . $totals['fat']);
    
    // Créer le repas
    $meal_id = createMealFromSuggestion($_SESSION['user_id'], $meal_type, $notes, $totals);
    if (!$meal_id) {
        error_log("❌ Erreur lors de la création du repas");
        $_SESSION['error_message'] = "Erreur lors de la création du repas";
        header('Location: food-log.php');
        exit;
    }
    
    error_log("✅ Repas créé avec l'ID: " . $meal_id);
    
    // Ajouter les ingrédients
    if (!addIngredientsToMeal($meal_id, $data['ingredients'])) {
        error_log("❌ Erreur lors de l'ajout des ingrédients");
        $_SESSION['error_message'] = "Erreur lors de l'ajout des ingrédients";
        header('Location: food-log.php');
        exit;
    }
    
    error_log("✅ Ingrédients ajoutés avec succès");
    
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