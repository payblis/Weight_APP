<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'test');
define('DB_PASS', 'Bk7p8*o89');
define('DB_NAME', 'test'); // Modifié pour correspondre à la base de données de l'utilisateur

// Variable globale pour la connexion PDO (utilisée par functions.php)
$GLOBALS['pdo'] = null;

// Connexion à la base de données avec MySQLi
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Vérification de la connexion
    if ($conn->connect_error) {
        die("Échec de la connexion à la base de données: " . $conn->connect_error);
    }
    
    // Définir le jeu de caractères à utf8
    $conn->set_charset("utf8");
    
    return $conn;
}

// Initialiser la connexion PDO pour getLastInsertId() dans functions.php
function initPDO() {
    if ($GLOBALS['pdo'] === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
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
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    
    if (!empty($params) && $stmt) {
        $types = '';
        $bindParams = [];
        
        // Déterminer les types de paramètres
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        // Créer un tableau de références pour bind_param
        $bindParams[] = &$types;
        
        // Créer des références pour chaque paramètre
        $paramRefs = [];
        foreach ($params as $key => $value) {
            $paramRefs[$key] = $value;
            $bindParams[] = &$paramRefs[$key];
        }
        
        // Lier les paramètres dynamiquement
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête: " . $conn->error . " - SQL: " . $sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stmt->close();
    $conn->close();
    
    return $result;
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
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Erreur de préparation de la requête: " . $conn->error . " - SQL: " . $sql);
        return 0;
    }
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        // Déterminer les types de paramètres
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        // Créer un tableau de références pour bind_param
        $bindParams[] = &$types;
        
        for ($i = 0; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        
        // Lier les paramètres dynamiquement
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    $success = $stmt->execute();
    $insertId = $success ? $conn->insert_id : 0;
    
    $stmt->close();
    $conn->close();
    
    return $insertId;
}

// Fonction pour mettre à jour des données et retourner le nombre de lignes affectées
function update($sql, $params = []) {
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Erreur de préparation de la requête: " . $conn->error . " - SQL: " . $sql);
        return 0;
    }
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        // Déterminer les types de paramètres
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        // Créer un tableau de références pour bind_param
        $bindParams[] = &$types;
        
        for ($i = 0; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        
        // Lier les paramètres dynamiquement
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    
    $stmt->close();
    $conn->close();
    
    return $affectedRows;
}

// Fonction pour supprimer des données et retourner le nombre de lignes affectées
function delete($sql, $params = []) {
    // Si $sql est un nom de table et $params est un ID
    if (is_string($sql) && !strpos($sql, ' ') && is_numeric($params)) {
        $table = $sql;
        $id = $params;
        $sql = "DELETE FROM $table WHERE id = ?";
        $params = [$id];
    }
    
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Erreur de préparation de la requête: " . $conn->error . " - SQL: " . $sql);
        return 0;
    }
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        // Déterminer les types de paramètres
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        // Créer un tableau de références pour bind_param
        $bindParams[] = &$types;
        
        for ($i = 0; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        
        // Lier les paramètres dynamiquement
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    
    $stmt->close();
    $conn->close();
    
    return $affectedRows;
}

// Fonction pour vérifier si une table existe
function tableExists($tableName) {
    $conn = connectDB();
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    $exists = $result && $result->num_rows > 0;
    $conn->close();
    return $exists;
}
?>
