<?php
require_once '../includes/config.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupération et validation de la requête
$query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);

if (!$query || strlen($query) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

try {
    // Recherche dans la base de données
    $stmt = $pdo->prepare("
        SELECT id, name, brand, calories, proteins, carbs, fats
        FROM foods
        WHERE (name LIKE ? OR brand LIKE ?)
        AND (user_id IS NULL OR user_id = ?)
        ORDER BY 
            CASE 
                WHEN name LIKE ? THEN 1
                WHEN name LIKE ? THEN 2
                ELSE 3
            END,
            name ASC
        LIMIT 20
    ");

    $searchPattern = "%{$query}%";
    $exactPattern = $query;
    $startPattern = "{$query}%";
    
    $stmt->execute([
        $searchPattern,
        $searchPattern,
        $_SESSION['user_id'],
        $exactPattern,
        $startPattern
    ]);

    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retourner les résultats
    echo json_encode($foods);

} catch (PDOException $e) {
    error_log("Erreur lors de la recherche d'aliments : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche']);
}
?> 