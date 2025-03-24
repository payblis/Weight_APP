<?php
// Démarrage de la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification
if (!isLoggedIn()) {
    // Si l'utilisateur n'est pas connecté, on le redirige vers la page de connexion
    header('Location: /login.php');
    exit;
}

// Récupération des informations de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'];

// Vérification que l'utilisateur existe toujours dans la base de données
try {
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        // Si l'utilisateur n'existe plus, on le déconnecte
        session_destroy();
        header('Location: /login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification de l'utilisateur : " . $e->getMessage());
    // En cas d'erreur, on redirige vers la page de connexion
    session_destroy();
    header('Location: /login.php');
    exit;
}
?> 