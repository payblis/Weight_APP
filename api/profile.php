<?php
require_once '../database/db.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Headers pour l'API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$userId = $_SESSION['user_id'];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Récupérer le profil
        $sql = "SELECT up.*, u.username, u.email 
                FROM user_profiles up 
                JOIN users u ON up.user_id = u.id 
                WHERE up.user_id = ?";
        $profile = fetchOne($sql, [$userId]);
        
        if ($profile) {
            echo json_encode($profile);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Profil non trouvé']);
        }
        break;

    case 'POST':
    case 'PUT':
        // Mettre à jour le profil
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Données invalides']);
            break;
        }

        // Vérifier si le profil existe déjà
        $existingProfile = fetchOne("SELECT id FROM user_profiles WHERE user_id = ?", [$userId]);
        
        if ($existingProfile) {
            // Mise à jour
            $sql = "UPDATE user_profiles SET 
                    gender = :gender,
                    age = :age,
                    height = :height,
                    initial_weight = :initial_weight,
                    target_weight = :target_weight,
                    activity_level = :activity_level,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id";
        } else {
            // Création
            $sql = "INSERT INTO user_profiles 
                    (user_id, gender, age, height, initial_weight, target_weight, activity_level) 
                    VALUES 
                    (:user_id, :gender, :age, :height, :initial_weight, :target_weight, :activity_level)";
        }

        $params = [
            ':user_id' => $userId,
            ':gender' => $data['gender'] ?? '',
            ':age' => $data['age'] ?? 0,
            ':height' => $data['height'] ?? 0,
            ':initial_weight' => $data['initial_weight'] ?? 0,
            ':target_weight' => $data['target_weight'] ?? 0,
            ':activity_level' => $data['activity_level'] ?? 'sédentaire'
        ];

        try {
            executeQuery($sql, $params);
            
            // Récupérer le profil mis à jour
            $updatedProfile = fetchOne("SELECT * FROM user_profiles WHERE user_id = ?", [$userId]);
            echo json_encode($updatedProfile);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la mise à jour du profil']);
        }
        break;

    case 'DELETE':
        // Supprimer le profil
        $sql = "DELETE FROM user_profiles WHERE user_id = ?";
        try {
            delete($sql, [$userId]);
            echo json_encode(['message' => 'Profil supprimé avec succès']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression du profil']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
?> 