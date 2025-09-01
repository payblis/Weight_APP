<?php
session_start();
require_once 'config/database.php';

echo "<h1>Test des informations de session</h1>\n";
echo "<pre>\n";

if (isset($_SESSION['user_id'])) {
    echo "=== Informations de session ===\n";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'Non défini') . "\n";
    echo "Username: " . ($_SESSION['username'] ?? 'Non défini') . "\n";
    echo "Email: " . ($_SESSION['email'] ?? 'Non défini') . "\n";
    echo "First Name: " . ($_SESSION['first_name'] ?? 'Non défini') . "\n";
    echo "Last Name: " . ($_SESSION['last_name'] ?? 'Non défini') . "\n";
    echo "Logged In: " . ($_SESSION['logged_in'] ?? 'Non défini') . "\n";
    
    echo "\n=== Informations de la base de données ===\n";
    try {
        $sql = "SELECT id, username, email, first_name, last_name FROM users WHERE id = ?";
        $user = fetchOne($sql, [$_SESSION['user_id']]);
        
        if ($user) {
            echo "DB User ID: " . $user['id'] . "\n";
            echo "DB Username: " . $user['username'] . "\n";
            echo "DB Email: " . $user['email'] . "\n";
            echo "DB First Name: " . ($user['first_name'] ?? 'NULL') . "\n";
            echo "DB Last Name: " . ($user['last_name'] ?? 'NULL') . "\n";
        } else {
            echo "Utilisateur non trouvé en base de données\n";
        }
    } catch (Exception $e) {
        echo "Erreur lors de la récupération des données : " . $e->getMessage() . "\n";
    }
} else {
    echo "Aucun utilisateur connecté\n";
    echo "<a href='login.php'>Se connecter</a>\n";
}

echo "\n</pre>\n";
?>
