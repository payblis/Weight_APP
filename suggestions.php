<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$user = fetchOne($sql, [$user_id]);

// Récupérer les informations du profil utilisateur
$sql = "SELECT up.*, u.age, u.gender 
        FROM user_profiles up 
        JOIN users u ON up.user_id = u.id 
        WHERE up.user_id = ?";
$user_profile = fetchOne($sql, [$user_id]);

// Récupérer l'objectif actuel
$sql = "SELECT * FROM goals 
        WHERE user_id = ? AND status = 'en_cours' 
        ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);

// Récupérer les aliments disponibles
$sql = "SELECT * FROM foods WHERE is_public = 1 ORDER BY name";
$available_foods = fetchAll($sql, []);

// Récupérer les exercices disponibles
$sql = "SELECT e.*, ec.name as category_name 
        FROM exercises e 
        JOIN exercise_categories ec ON e.category_id = ec.id 
        WHERE e.is_public = 1 
        ORDER BY ec.name, e.name";
$available_exercises = fetchAll($sql, []);

// Récupérer les préférences alimentaires
$sql = "SELECT food_id, preference_type 
        FROM food_preferences 
        WHERE user_id = ?";
$food_preferences = fetchAll($sql, [$user_id]);

// Récupérer les besoins caloriques quotidiens
$sql = "SELECT * FROM user_calorie_needs 
        WHERE user_id = ? 
        ORDER BY calculation_date DESC LIMIT 1";
$calorie_needs = fetchOne($sql, [$user_id]);

// Traitement du formulaire de suggestion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suggestion_type = sanitizeInput($_POST['suggestion_type'] ?? '');
    $meal_type = sanitizeInput($_POST['meal_type'] ?? '');
    
    if ($suggestion_type && $meal_type) {
        // Préparer les données pour ChatGPT
        $prompt_data = [
            'user_profile' => [
                'age' => $user_profile['age'],
                'gender' => $user_profile['gender'],
                'weight' => $user_profile['current_weight'],
                'height' => $user_profile['height'],
                'activity_level' => $user_profile['activity_level'],
                'goal_weight' => $current_goal['target_weight'],
                'target_date' => $current_goal['target_date']
            ],
            'calorie_needs' => [
                'daily_calories' => $calorie_needs['tdee'],
                'protein_target' => $calorie_needs['protein_target'],
                'carbs_target' => $calorie_needs['carbs_target'],
                'fat_target' => $calorie_needs['fat_target']
            ],
            'available_foods' => array_map(function($food) {
                return [
                    'name' => $food['name'],
                    'calories' => $food['calories'],
                    'protein' => $food['protein'],
                    'carbs' => $food['carbs'],
                    'fat' => $food['fat']
                ];
            }, $available_foods),
            'available_exercises' => array_map(function($exercise) {
                return [
                    'name' => $exercise['name'],
                    'calories_per_hour' => $exercise['calories_per_hour'],
                    'category' => $exercise['category_name']
                ];
            }, $available_exercises),
            'food_preferences' => array_map(function($pref) {
                return [
                    'food_id' => $pref['food_id'],
                    'type' => $pref['preference_type']
                ];
            }, $food_preferences)
        ];
        
        // Construire le prompt pour ChatGPT
        $prompt = "En tant que nutritionniste et coach sportif, je vais vous aider à créer des suggestions personnalisées. Voici les informations de l'utilisateur :\n\n";
        $prompt .= "Profil :\n";
        $prompt .= "- Âge : {$prompt_data['user_profile']['age']}\n";
        $prompt .= "- Genre : {$prompt_data['user_profile']['gender']}\n";
        $prompt .= "- Poids actuel : {$prompt_data['user_profile']['weight']} kg\n";
        $prompt .= "- Taille : {$prompt_data['user_profile']['height']} cm\n";
        $prompt .= "- Niveau d'activité : {$prompt_data['user_profile']['activity_level']}\n";
        $prompt .= "- Objectif de poids : {$prompt_data['user_profile']['goal_weight']} kg\n";
        $prompt .= "- Date cible : {$prompt_data['user_profile']['target_date']}\n\n";
        
        $prompt .= "Besoins quotidiens :\n";
        $prompt .= "- Calories : {$prompt_data['calorie_needs']['daily_calories']}\n";
        $prompt .= "- Protéines : {$prompt_data['calorie_needs']['protein_target']}g\n";
        $prompt .= "- Glucides : {$prompt_data['calorie_needs']['carbs_target']}g\n";
        $prompt .= "- Lipides : {$prompt_data['calorie_needs']['fat_target']}g\n\n";
        
        if ($suggestion_type === 'meal') {
            $prompt .= "Je souhaite une suggestion de repas pour le {$meal_type}. Voici les aliments disponibles :\n";
            foreach ($prompt_data['available_foods'] as $food) {
                $prompt .= "- {$food['name']} (Calories: {$food['calories']}, Protéines: {$food['protein']}g, Glucides: {$food['carbs']}g, Lipides: {$food['fat']}g)\n";
            }
            $prompt .= "\nVeuillez suggérer un repas équilibré en utilisant uniquement les aliments listés ci-dessus, en respectant les besoins nutritionnels quotidiens.";
        } else {
            $prompt .= "Je souhaite une suggestion d'exercices. Voici les exercices disponibles :\n";
            foreach ($prompt_data['available_exercises'] as $exercise) {
                $prompt .= "- {$exercise['name']} (Catégorie: {$exercise['category']}, Calories/heure: {$exercise['calories_per_hour']})\n";
            }
            $prompt .= "\nVeuillez suggérer une séance d'entraînement adaptée aux objectifs de l'utilisateur, en utilisant uniquement les exercices listés ci-dessus.";
        }
        
        // Appeler l'API ChatGPT
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Vous êtes un nutritionniste et coach sportif expert.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            $suggestion = $result['choices'][0]['message']['content'];
            
            // Sauvegarder la suggestion dans la base de données
            $sql = "INSERT INTO ai_suggestions (user_id, suggestion_type, content, is_read, is_implemented, created_at) 
                    VALUES (?, ?, ?, 0, 0, NOW())";
            insert($sql, [$user_id, $suggestion_type, $suggestion]);
            
            $success_message = "Suggestion générée avec succès !";
        } else {
            $error_message = "Erreur lors de la génération de la suggestion. Veuillez réessayer.";
        }
    }
}

// Récupérer l'historique des suggestions
$sql = "SELECT * FROM ai_suggestions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";
$suggestions_history = fetchAll($sql, [$user_id]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggestions personnalisées - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>Suggestions personnalisées</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="suggestions-form">
            <h2>Demander une suggestion</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="suggestion_type">Type de suggestion :</label>
                    <select name="suggestion_type" id="suggestion_type" required>
                        <option value="meal">Repas</option>
                        <option value="exercise">Exercices</option>
                    </select>
                </div>
                
                <div class="form-group" id="meal_type_group">
                    <label for="meal_type">Type de repas :</label>
                    <select name="meal_type" id="meal_type">
                        <option value="petit_dejeuner">Petit-déjeuner</option>
                        <option value="dejeuner">Déjeuner</option>
                        <option value="diner">Dîner</option>
                        <option value="collation">Collation</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Générer une suggestion</button>
            </form>
        </div>
        
        <?php if (!empty($suggestions_history)): ?>
            <div class="suggestions-history">
                <h2>Historique des suggestions</h2>
                <?php foreach ($suggestions_history as $suggestion): ?>
                    <div class="suggestion-card <?php echo $suggestion['is_read'] ? 'read' : 'unread'; ?>">
                        <div class="suggestion-header">
                            <span class="suggestion-type">
                                <?php 
                                switch($suggestion['suggestion_type']) {
                                    case 'alimentation':
                                        echo 'Alimentation';
                                        break;
                                    case 'exercice':
                                        echo 'Exercice';
                                        break;
                                    case 'motivation':
                                        echo 'Motivation';
                                        break;
                                    default:
                                        echo ucfirst($suggestion['suggestion_type']);
                                }
                                ?>
                            </span>
                            <span class="suggestion-date">
                                <?php echo date('d/m/Y H:i', strtotime($suggestion['created_at'])); ?>
                            </span>
                        </div>
                        <div class="suggestion-content">
                            <?php echo nl2br(htmlspecialchars($suggestion['content'])); ?>
                        </div>
                        <div class="suggestion-actions">
                            <?php if (!$suggestion['is_read']): ?>
                                <form method="POST" action="mark_suggestion_read.php" style="display: inline;">
                                    <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Marquer comme lu</button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$suggestion['is_implemented']): ?>
                                <form method="POST" action="mark_suggestion_implemented.php" style="display: inline;">
                                    <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Marquer comme implémenté</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.getElementById('suggestion_type').addEventListener('change', function() {
            const mealTypeGroup = document.getElementById('meal_type_group');
            mealTypeGroup.style.display = this.value === 'meal' ? 'block' : 'none';
        });
    </script>
</body>
</html> 