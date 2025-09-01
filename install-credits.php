<?php
/**
 * Script d'installation des tables de crédits IA
 * À exécuter une seule fois pour ajouter les nouvelles tables à la base de données
 */

require_once 'config/database.php';

echo "<h1>Installation des tables de crédits IA</h1>\n";
echo "<pre>\n";

try {
    // Vérifier la connexion à la base de données
    $pdo = initPDO();
    echo "✓ Connexion à la base de données réussie\n";
    
    // Lire le fichier SQL
    $sqlFile = 'sql/credits.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Fichier SQL non trouvé : $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✓ Fichier SQL lu avec succès\n";
    
    // Diviser les requêtes SQL
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "\nExécution des requêtes SQL :\n";
    echo str_repeat('-', 50) . "\n";
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        try {
            $pdo->exec($query);
            echo "✓ Requête exécutée avec succès\n";
        } catch (PDOException $e) {
            // Ignorer les erreurs si les tables existent déjà
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ Table existe déjà (ignoré)\n";
            } else {
                echo "✗ Erreur : " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo str_repeat('-', 50) . "\n";
    echo "✓ Installation terminée avec succès !\n";
    
    // Vérifier que les tables ont été créées
    echo "\nVérification des tables créées :\n";
    $tables = ['ai_credits', 'credit_purchases', 'credit_usage'];
    
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
    
    // Tester les fonctions de crédits
    echo "\nTest des fonctions de crédits :\n";
    try {
        require_once 'includes/credit_functions.php';
        echo "✓ Classe CreditManager chargée\n";
        
        // Tester la fonction getCreditPackages
        $packages = CreditManager::getCreditPackages();
        if (!empty($packages)) {
            echo "✓ Fonction getCreditPackages fonctionne (" . count($packages) . " packages)\n";
        } else {
            echo "✗ Fonction getCreditPackages ne retourne aucun package\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Erreur lors du test des fonctions : " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erreur fatale : " . $e->getMessage() . "\n";
}

echo "\n</pre>\n";
echo "<p><strong>Note :</strong> Ce script peut être supprimé après l'installation.</p>\n";
echo "<p><strong>Prochaines étapes :</strong></p>\n";
echo "<ul>\n";
echo "<li>Accédez à <a href='buy-credits.php'>buy-credits.php</a> pour tester l'achat de crédits</li>\n";
echo "<li>Accédez à <a href='my-credits.php'>my-credits.php</a> pour voir la gestion des crédits</li>\n";
echo "</ul>\n";
?>
