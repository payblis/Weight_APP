<?php
/**
 * Script d'installation des tables d'abonnements Premium
 * À exécuter une seule fois pour ajouter les nouvelles tables à la base de données
 */

require_once 'config/database.php';

echo "<h1>Installation des tables d'abonnements Premium</h1>\n";
echo "<pre>\n";

try {
    // Vérifier la connexion à la base de données
    $pdo = initPDO();
    echo "✓ Connexion à la base de données réussie\n";
    
    // Lire le fichier SQL
    $sqlFile = 'sql/subscriptions.sql';
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
    
    // Vérifier les colonnes ajoutées à la table users
    echo "\nVérification des colonnes ajoutées à la table 'users' :\n";
    try {
        $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'premium_status'");
        if ($result->rowCount() > 0) {
            echo "✓ Colonne 'premium_status' ajoutée\n";
        } else {
            echo "✗ Colonne 'premium_status' manquante\n";
        }
        
        $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'premium_expires_at'");
        if ($result->rowCount() > 0) {
            echo "✓ Colonne 'premium_expires_at' ajoutée\n";
        } else {
            echo "✗ Colonne 'premium_expires_at' manquante\n";
        }
    } catch (PDOException $e) {
        echo "✗ Erreur lors de la vérification des colonnes : " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erreur fatale : " . $e->getMessage() . "\n";
}

echo "\n</pre>\n";
echo "<p><strong>Note :</strong> Ce script peut être supprimé après l'installation.</p>\n";
?>
