<?php
require_once 'includes/config.php';
require_once 'includes/chatgpt.php';

// Vérification de la connexion
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérification du type de suggestion demandé
$type = $_GET['type'] ?? '';
if (!in_array($type, ['meal', 'exercise'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Type de suggestion invalide']);
    exit;
}

try {
    // Récupération des données de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT u.*, wg.weekly_goal
        FROM users u
        LEFT JOIN weight_goals wg ON u.id = wg.user_id
        WHERE u.id = ? AND wg.status = 'active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Récupération du dernier poids
    $stmt = $pdo->prepare("
        SELECT weight
        FROM daily_logs
        WHERE user_id = ?
        ORDER BY date DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $latest_weight = $stmt->fetchColumn();

    // Initialisation de ChatGPT
    $chatgpt = new ChatGPT(CHATGPT_API_KEY);

    // Génération de la suggestion
    if ($type === 'meal') {
        $prompt = "En tant que nutritionniste expert, suggère un plan de repas sain et équilibré pour aujourd'hui. 
        Contexte :
        - Poids actuel : {$latest_weight} kg
        - Objectif : {$user['target_weight']} kg
        - Perte hebdomadaire visée : {$user['weekly_goal']} kg
        - Niveau d'activité : {$user['activity_level']}

        Format souhaité :
        - Petit-déjeuner : [repas] ([calories] kcal)
        - Collation matin : [repas] ([calories] kcal)
        - Déjeuner : [repas] ([calories] kcal)
        - Collation après-midi : [repas] ([calories] kcal)
        - Dîner : [repas] ([calories] kcal)

        Total calorique journalier adapté à l'objectif de perte de poids.
        Inclure des aliments facilement trouvables et des portions réalistes.";

        $suggestion = $chatgpt->generateResponse($prompt);
    } else {
        $prompt = "En tant que coach sportif professionnel, suggère un programme d'exercices adapté pour aujourd'hui.
        Contexte :
        - Niveau d'activité : {$user['activity_level']}
        - Objectif : Perte de poids saine
        - Perte hebdomadaire visée : {$user['weekly_goal']} kg

        Format souhaité :
        1. Échauffement (5-10 minutes)
        2. 3-4 exercices principaux avec :
           - Nombre de séries et répétitions
           - Temps de repos
           - Niveau de difficulté
        3. Retour au calme
        
        Inclure des exercices réalisables à la maison ou en salle.
        Estimer les calories brûlées.
        Donner des conseils de sécurité si nécessaire.";

        $suggestion = $chatgpt->generateResponse($prompt);
    }

    // Enregistrement de la suggestion dans la base de données
    $stmt = $pdo->prepare("
        INSERT INTO ai_suggestions (user_id, type, content)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $type, $suggestion]);

    // Réponse
    echo json_encode([
        'success' => true,
        'suggestion' => $suggestion
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de la génération de la suggestion',
        'message' => $e->getMessage()
    ]);
} 