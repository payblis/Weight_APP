<?php
// Configuration de la connexion à la base de données
function getDbConnection() {
    $host = 'localhost';
    $dbname = 'test';
    $username = 'test';
    $password = 'cL995uh7?';
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        // Configurer PDO pour qu'il lance des exceptions en cas d'erreur
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        // En mode développement, afficher l'erreur
        die("Erreur de connexion à la base de données: " . $e->getMessage());
    }
}

// Fonction pour exécuter une requête SQL
function executeQuery($sql, $params = []) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        die("Erreur d'exécution de la requête: " . $e->getMessage());
    }
}

// Fonction pour récupérer un seul enregistrement
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour récupérer plusieurs enregistrements
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour insérer des données et récupérer l'ID généré
function insert($sql, $params = []) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $conn->lastInsertId();
    } catch(PDOException $e) {
        die("Erreur d'insertion: " . $e->getMessage());
    }
}

// Fonction pour mettre à jour des données
function update($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

// Fonction pour supprimer des données
function delete($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}
?>
