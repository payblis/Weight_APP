<?php
/**
 * Fonctions d'administration pour l'application Weight Tracker
 */

/**
 * Vérifie si un utilisateur est administrateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return bool True si l'utilisateur est administrateur, false sinon
 */
function isAdmin($user_id) {
    if (!$user_id) return false;
    
    $sql = "SELECT role_id FROM users WHERE id = ?";
    $user = fetchOne($sql, [$user_id]);
    
    return $user && $user['role_id'] == 1;
}

/**
 * Récupère la liste des utilisateurs
 * 
 * @param int $limit Nombre maximum d'utilisateurs à récupérer
 * @param int $offset Décalage pour la pagination
 * @return array Liste des utilisateurs
 */
function getUsers($limit = 100, $offset = 0) {
    $sql = "SELECT u.*, r.name as role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            ORDER BY u.id 
            LIMIT ? OFFSET ?";
    
    return fetchAll($sql, [$limit, $offset]);
}

/**
 * Récupère les informations d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array|null Informations de l'utilisateur ou null si non trouvé
 */
function getUserDetails($user_id) {
    $sql = "SELECT u.*, r.name as role_name, p.* 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN user_profiles p ON u.id = p.user_id 
            WHERE u.id = ?";
    
    return fetchOne($sql, [$user_id]);
}

/**
 * Récupère la liste des rôles
 * 
 * @return array Liste des rôles
 */
function getRoles() {
    $sql = "SELECT * FROM roles ORDER BY id";
    return fetchAll($sql);
}

/**
 * Met à jour le rôle d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $role_id ID du rôle
 * @return bool True si la mise à jour a réussi, false sinon
 */
function updateUserRole($user_id, $role_id) {
    $sql = "UPDATE users SET role_id = ? WHERE id = ?";
    return update($sql, [$role_id, $user_id]) > 0;
}

/**
 * Supprime un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return bool True si la suppression a réussi, false sinon
 */
function deleteUser($user_id) {
    $sql = "DELETE FROM users WHERE id = ?";
    return delete($sql, [$user_id]) > 0;
}

/**
 * Récupère les statistiques générales
 * 
 * @return array Statistiques générales
 */
function getStats() {
    $stats = [];
    
    // Nombre d'utilisateurs
    $sql = "SELECT COUNT(*) as count FROM users";
    $result = fetchOne($sql);
    $stats['users_count'] = $result ? $result['count'] : 0;
    
    // Nombre d'entrées de poids
    $sql = "SELECT COUNT(*) as count FROM weight_logs";
    $result = fetchOne($sql);
    $stats['weight_logs_count'] = $result ? $result['count'] : 0;
    
    // Nombre d'entrées alimentaires
    $sql = "SELECT COUNT(*) as count FROM food_logs";
    $result = fetchOne($sql);
    $stats['food_logs_count'] = $result ? $result['count'] : 0;
    
    // Nombre d'exercices enregistrés
    $sql = "SELECT COUNT(*) as count FROM exercise_logs";
    $result = fetchOne($sql);
    $stats['exercise_logs_count'] = $result ? $result['count'] : 0;
    
    return $stats;
}

/**
 * Récupère les paramètres de l'application
 * 
 * @return array Paramètres de l'application
 */
function getAppSettings() {
    $sql = "SELECT * FROM app_settings";
    return fetchAll($sql);
}

/**
 * Met à jour un paramètre de l'application
 * 
 * @param string $key Clé du paramètre
 * @param string $value Valeur du paramètre
 * @return bool True si la mise à jour a réussi, false sinon
 */
function updateAppSetting($key, $value) {
    // Vérifier si le paramètre existe
    $sql = "SELECT id FROM app_settings WHERE setting_key = ?";
    $setting = fetchOne($sql, [$key]);
    
    if ($setting) {
        // Mettre à jour le paramètre existant
        $sql = "UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?";
        return update($sql, [$value, $key]) > 0;
    } else {
        // Créer un nouveau paramètre
        $sql = "INSERT INTO app_settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW())";
        return insert($sql, [$key, $value]) > 0;
    }
}
