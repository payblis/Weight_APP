<?php
/**
 * Script de test du système Premium
 * Vérifie que toutes les fonctionnalités Premium fonctionnent correctement
 */

echo "<h1>Test du Système Premium - MyFity</h1>\n";
echo "<pre>\n";

// Test 1: Vérifier les inclusions de fichiers
echo "=== Test 1: Vérification des inclusions de fichiers ===\n";

$requiredFiles = [
    'includes/functions.php',
    'includes/subscription_functions.php',
    'config/database.php',
    'sql/subscriptions.sql'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✓ Fichier $file existe\n";
    } else {
        echo "✗ Fichier $file manquant\n";
    }
}

// Test 2: Vérifier la connexion à la base de données
echo "\n=== Test 2: Vérification de la base de données ===\n";

try {
    require_once 'config/database.php';
    $pdo = initPDO();
    echo "✓ Connexion à la base de données réussie\n";
} catch (Exception $e) {
    echo "✗ Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
    exit;
}

// Test 3: Vérifier l'existence des tables
echo "\n=== Test 3: Vérification des tables ===\n";

$tables = ['subscriptions', 'payment_history'];
foreach ($tables as $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "✓ Table '$table' existe\n";
        } else {
            echo "✗ Table '$table' manquante\n";
        }
    } catch (PDOException $e) {
        echo "✗ Erreur lors de la vérification de '$table' : " . $e->getMessage() . "\n";
    }
}

// Test 4: Vérifier les colonnes de la table users
echo "\n=== Test 4: Vérification des colonnes users ===\n";

$columns = ['premium_status', 'premium_expires_at'];
foreach ($columns as $column) {
    try {
        $result = $pdo->query("SHOW COLUMNS FROM users LIKE '$column'");
        if ($result->rowCount() > 0) {
            echo "✓ Colonne '$column' existe dans users\n";
        } else {
            echo "✗ Colonne '$column' manquante dans users\n";
        }
    } catch (PDOException $e) {
        echo "✗ Erreur lors de la vérification de '$column' : " . $e->getMessage() . "\n";
    }
}

// Test 5: Vérifier les fonctions Premium
echo "\n=== Test 5: Vérification des fonctions Premium ===\n";

try {
    require_once 'includes/subscription_functions.php';
    echo "✓ Classe SubscriptionManager chargée\n";
    
    // Test de la fonction de validation
    $testData = [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'test@example.com',
        'cardName' => 'Test User',
        'cardNumber' => '4242 4242 4242 4242',
        'cardExpiry' => '12/25',
        'cardCVC' => '123'
    ];
    
    $errors = SubscriptionManager::validatePaymentData($testData);
    if (empty($errors)) {
        echo "✓ Validation des données de paiement fonctionne\n";
    } else {
        echo "✗ Erreurs de validation : " . implode(', ', $errors) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erreur lors du test des fonctions Premium : " . $e->getMessage() . "\n";
}

// Test 6: Vérifier les fonctions utilitaires
echo "\n=== Test 6: Vérification des fonctions utilitaires ===\n";

try {
    require_once 'includes/functions.php';
    echo "✓ Fonctions utilitaires chargées\n";
    
    // Test de la fonction showUserStatusBadge
    if (function_exists('showUserStatusBadge')) {
        echo "✓ Fonction showUserStatusBadge existe\n";
    } else {
        echo "✗ Fonction showUserStatusBadge manquante\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erreur lors du test des fonctions utilitaires : " . $e->getMessage() . "\n";
}

// Test 7: Vérifier les pages Premium
echo "\n=== Test 7: Vérification des pages Premium ===\n";

$pages = [
    'premium-subscribe.php',
    'process-payment.php',
    'premium-success.php',
    'my-subscription.php'
];

foreach ($pages as $page) {
    if (file_exists($page)) {
        echo "✓ Page $page existe\n";
    } else {
        echo "✗ Page $page manquante\n";
    }
}

// Test 8: Simulation d'un utilisateur Premium
echo "\n=== Test 8: Simulation d'un utilisateur Premium ===\n";

try {
    // Créer un utilisateur de test
    $testEmail = 'test_premium_' . time() . '@example.com';
    $testPassword = password_hash('test123', PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, email, password, premium_status, premium_expires_at) 
            VALUES (?, ?, ?, 'premium', DATE_ADD(NOW(), INTERVAL 1 MONTH))";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['test_premium_user', $testEmail, $testPassword]);
    $testUserId = $pdo->lastInsertId();
    
    echo "✓ Utilisateur de test Premium créé (ID: $testUserId)\n";
    
    // Tester la fonction isUserPremium
    $isPremium = SubscriptionManager::isUserPremium($testUserId);
    if ($isPremium) {
        echo "✓ Fonction isUserPremium retourne true pour un utilisateur Premium\n";
    } else {
        echo "✗ Fonction isUserPremium retourne false pour un utilisateur Premium\n";
    }
    
    // Nettoyer l'utilisateur de test
    $pdo->exec("DELETE FROM users WHERE id = $testUserId");
    echo "✓ Utilisateur de test supprimé\n";
    
} catch (Exception $e) {
    echo "✗ Erreur lors du test utilisateur Premium : " . $e->getMessage() . "\n";
}

echo "\n=== Résumé ===\n";
echo "Tests terminés. Vérifiez les résultats ci-dessus.\n";
echo "Si tous les tests sont passés (✓), le système Premium est prêt à être utilisé.\n";

echo "\n</pre>\n";
echo "<p><strong>Note :</strong> Ce script de test peut être supprimé après vérification.</p>\n";
?>
