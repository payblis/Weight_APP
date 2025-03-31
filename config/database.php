<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'rt_adm_4515hji');
define('DB_PASS', 'xS61ay*09');
define('DB_NAME', 'myfity0001'); // Modifié pour correspondre à la base de données de l'utilisateur

// Variable globale pour la connexion PDO (utilisée par functions.php)
$GLOBALS['pdo'] = null;

// Initialiser la connexion PDO
function initPDO() {
    if ($GLOBALS['pdo'] === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $GLOBALS['pdo'] = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("Échec de la connexion PDO: " . $e->getMessage());
        }
    }
    return $GLOBALS['pdo'];
}

// Initialiser PDO au chargement du fichier
initPDO();

// Fonction pour exécuter une requête et retourner le résultat
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (Exception $e) {
        error_log("Erreur dans executeQuery: " . $e->getMessage());
        return false;
    }
}

// Fonction pour obtenir une seule ligne
function fetchOne($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur dans fetchOne: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir plusieurs lignes
function fetchAll($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur dans fetchAll: " . $e->getMessage());
        return [];
    }
}

// Fonction pour insérer des données et retourner l'ID
function insert($sql, $params = []) {
    global $pdo;
    try {
        error_log("Exécution de la requête insert : " . $sql);
        error_log("Paramètres : " . print_r($params, true));
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $lastId = $pdo->lastInsertId();
        
        error_log("Résultat de insert : " . ($lastId ? "Succès" : "Échec") . ", ID : " . $lastId);
        return $lastId;
    } catch (Exception $e) {
        error_log("Erreur dans insert: " . $e->getMessage());
        return 0;
    }
}

// Fonction pour mettre à jour des données et retourner le nombre de lignes affectées
function update($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Erreur dans update: " . $e->getMessage());
        return 0;
    }
}

// Fonction pour supprimer des données et retourner le nombre de lignes affectées
function delete($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Erreur dans delete: " . $e->getMessage());
        return 0;
    }
}

// Fonction pour obtenir la dernière erreur SQL
function getLastError() {
    global $pdo;
    $error = $pdo->errorInfo();
    return $error[2];
}

// Fonction pour vérifier si une table existe
function tableExists($tableName) {
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Erreur dans tableExists: " . $e->getMessage());
        return false;
    }
}
?>
