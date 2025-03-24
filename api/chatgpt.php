<?php
// Configuration de base
session_start();
require_once '../database/db.php';

// Fonction pour nettoyer les données d'entrée
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Récupérer la clé API ChatGPT
function getApiKey() {
    $sql = "SELECT api_key FROM api_config ORDER BY id DESC LIMIT 1";
    $result = fetchOne($sql, []);
    return $result ? $result['api_key'] : null;
}

// Fonction pour faire une requête à l'API ChatGPT
function callChatGptApi($prompt) {
    $api_key = getApiKey();
    
    if (!$api_key) {
        return ['error' => 'Clé API non configurée'];
    }
    
    $url = 'https://api.openai.com/v1/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'Vous êtes un assistant spécialisé en nutrition et en perte de poids. Donnez des conseils précis et personnalisés.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 500
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => 'Erreur de connexion à l\'API: ' . $error];
    }
    
    $response_data = json_decode($response, true);
    
    if (isset($response_data['error'])) {
        return ['error' => 'Erreur API: ' . $response_data['error']['message']];
    }
    
    if (isset($response_data['choices'][0]['message']['content'])) {
        return ['response' => $response_data['choices'][0]['message']['content']];
    }
    
    return ['error' => 'Réponse inattendue de l\'API'];
}

// Traiter les différentes requêtes
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';
    
    switch ($action) {
        case 'update_api_key':
            $api_key = sanitize_input($_POST['api_key']);
            
            if (empty($api_key)) {
                $_SESSION['api_error'] = "La clé API ne peut pas être vide.";
                header("Location: ../profile.php?error=1");
                exit();
            }
            
            try {
                // Supprimer les anciennes clés
                $sql = "DELETE FROM api_config";
                executeQuery($sql, []);
                
                // Insérer la nouvelle clé
                $sql = "INSERT INTO api_config (api_key) VALUES (?)";
                executeQuery($sql, [$api_key]);
                
                $_SESSION['api_success'] = "Clé API mise à jour avec succès.";
                header("Location: ../profile.php?success=1");
                exit();
            } catch (Exception $e) {
                $_SESSION['api_error'] = "Erreur lors de la mise à jour de la clé API: " . $e->getMessage();
                header("Location: ../profile.php?error=1");
                exit();
            }
            break;
            
        case 'generate_meal_recommendations':
            $user_id = $_SESSION['user_id'];
            
            // Récupérer les informations de l'utilisateur
            $sql = "SELECT up.*, u.username 
                    FROM user_profiles up 
                    JOIN users u ON up.user_id = u.id 
                    WHERE up.user_id = ?";
            $user_profile = fetchOne($sql, [$user_id]);
            
            if (!$user_profile) {
                echo json_encode(['error' => 'Profil utilisateur non trouvé']);
                exit();
            }
            
            // Récupérer le programme actuel
            $sql = "SELECT * FROM custom_programs WHERE user_id = ? ORDER BY id DESC LIMIT 1";
            $program = fetchOne($sql, [$user_id]);
            
            // Construire le prompt pour ChatGPT
            $prompt = "Je suis {$user_profile['gender']}, j'ai {$user_profile['age']} ans, je mesure {$user_profile['height']} cm, ";
            $prompt .= "je pèse actuellement {$user_profile['initial_weight']} kg et mon objectif est d'atteindre {$user_profile['target_weight']} kg. ";
            $prompt .= "Mon niveau d'activité est {$user_profile['activity_level']}. ";
            
            if ($program) {
                $prompt .= "Mon objectif calorique quotidien est de {$program['daily_calorie_target']} calories. ";
            }
            
            $prompt .= "Pouvez-vous me suggérer 3 repas équilibrés pour chaque moment de la journée (petit-déjeuner, déjeuner, dîner) ";
            $prompt .= "ainsi que 2 collations saines? Pour chaque suggestion, indiquez les calories approximatives et les principaux nutriments.";
            
            $result = callChatGptApi($prompt);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
            break;
            
        case 'generate_activity_recommendations':
            $user_id = $_SESSION['user_id'];
            
            // Récupérer les informations de l'utilisateur
            $sql = "SELECT up.*, u.username 
                    FROM user_profiles up 
                    JOIN users u ON up.user_id = u.id 
                    WHERE up.user_id = ?";
            $user_profile = fetchOne($sql, [$user_id]);
            
            if (!$user_profile) {
                echo json_encode(['error' => 'Profil utilisateur non trouvé']);
                exit();
            }
            
            // Récupérer les activités récentes
            $sql = "SELECT a.name, a.category 
                    FROM activity_logs al 
                    JOIN activities a ON al.activity_id = a.id 
                    WHERE al.user_id = ? 
                    ORDER BY al.log_date DESC LIMIT 5";
            $recent_activities = fetchAll($sql, [$user_id]);
            
            // Construire le prompt pour ChatGPT
            $prompt = "Je suis {$user_profile['gender']}, j'ai {$user_profile['age']} ans, je mesure {$user_profile['height']} cm, ";
            $prompt .= "je pèse actuellement {$user_profile['initial_weight']} kg et mon objectif est d'atteindre {$user_profile['target_weight']} kg. ";
            $prompt .= "Mon niveau d'activité est {$user_profile['activity_level']}. ";
            
            if (!empty($recent_activities)) {
                $prompt .= "Mes activités récentes incluent: ";
                foreach ($recent_activities as $activity) {
                    $prompt .= "{$activity['name']} ({$activity['category']}), ";
                }
                $prompt = rtrim($prompt, ", ") . ". ";
            }
            
            $prompt .= "Pouvez-vous me suggérer un programme d'exercices pour une semaine, adapté à mon profil et à mon objectif de perte de poids? ";
            $prompt .= "Pour chaque jour, indiquez les activités recommandées, leur durée et les calories approximatives brûlées.";
            
            $result = callChatGptApi($prompt);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
            break;
            
        case 'analyze_morphology':
            // Cette fonction serait normalement implémentée avec une API de vision par ordinateur
            // Pour cette démonstration, nous simulons une analyse avec ChatGPT
            
            $user_id = $_SESSION['user_id'];
            
            // Récupérer les informations de l'utilisateur
            $sql = "SELECT up.*, u.username 
                    FROM user_profiles up 
                    JOIN users u ON up.user_id = u.id 
                    WHERE up.user_id = ?";
            $user_profile = fetchOne($sql, [$user_id]);
            
            if (!$user_profile) {
                echo json_encode(['error' => 'Profil utilisateur non trouvé']);
                exit();
            }
            
            // Simuler l'analyse d'image (dans une vraie application, on analyserait l'image téléchargée)
            $prompt = "Je suis {$user_profile['gender']}, j'ai {$user_profile['age']} ans, je mesure {$user_profile['height']} cm, ";
            $prompt .= "je pèse actuellement {$user_profile['initial_weight']} kg et mon objectif est d'atteindre {$user_profile['target_weight']} kg. ";
            $prompt .= "Simulez une analyse morphologique et suggérez des exercices et régimes ciblés pour les différentes zones du corps ";
            $prompt .= "(abdomen, cuisses, bras, dos) qui pourraient aider à perdre du poids de manière ciblée. ";
            $prompt .= "Présentez les résultats comme si vous aviez analysé une photo de moi.";
            
            $result = callChatGptApi($prompt);
            
            // Dans une vraie application, on enregistrerait l'image et les résultats de l'analyse
            if (!isset($result['error'])) {
                try {
                    // Simuler l'enregistrement de l'analyse
                    $image_path = "uploads/morphology/user_{$user_id}_" . time() . ".jpg";
                    $analysis_result = $result['response'];
                    
                    $sql = "INSERT INTO morphological_analyses (user_id, image_path, analysis_result) VALUES (?, ?, ?)";
                    $analysis_id = insert($sql, [$user_id, $image_path, $analysis_result]);
                    
                    // Ajouter l'ID de l'analyse au résultat
                    $result['analysis_id'] = $analysis_id;
                } catch (Exception $e) {
                    // En cas d'erreur, continuer quand même et renvoyer le résultat
                    $result['db_error'] = $e->getMessage();
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
            break;
            
        case 'custom_query':
            $query = isset($_POST['query']) ? sanitize_input($_POST['query']) : '';
            
            if (empty($query)) {
                echo json_encode(['error' => 'La requête ne peut pas être vide']);
                exit();
            }
            
            $user_id = $_SESSION['user_id'];
            
            // Récupérer les informations de l'utilisateur
            $sql = "SELECT up.*, u.username 
                    FROM user_profiles up 
                    JOIN users u ON up.user_id = u.id 
                    WHERE up.user_id = ?";
            $user_profile = fetchOne($sql, [$user_id]);
            
            // Ajouter le contexte de l'utilisateur à la requête
            $prompt = "Contexte: Je suis {$user_profile['gender']}, j'ai {$user_profile['age']} ans, je mesure {$user_profile['height']} cm, ";
            $prompt .= "je pèse actuellement {$user_profile['initial_weight']} kg et mon objectif est d'atteindre {$user_profile['target_weight']} kg. ";
            $prompt .= "Mon niveau d'activité est {$user_profile['activity_level']}. ";
            $prompt .= "\n\nMa question: {$query}";
            
            $result = callChatGptApi($prompt);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
            exit();
    }
} else {
    // Si la requête est en GET, vérifier si l'API est configurée
    $api_key = getApiKey();
    
    $response = [
        'api_configured' => !empty($api_key)
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
