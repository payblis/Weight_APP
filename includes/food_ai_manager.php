<?php
require_once 'config.php';
require_once 'chatgpt.php';

class FoodAIManager {
    private $pdo;
    private $chatGPT;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->chatGPT = new ChatGPT();
    }

    public function suggestMeal($type, $preferences = []) {
        $prompt = "En tant que nutritionniste, suggère un repas {$type} équilibré avec les informations nutritionnelles détaillées pour chaque aliment. ";
        
        if (!empty($preferences)) {
            $prompt .= "Prends en compte les préférences suivantes : " . implode(", ", $preferences) . ". ";
        }

        $prompt .= "Réponds UNIQUEMENT avec un objet JSON valide au format suivant (sans texte avant ou après) :
        {
            \"nom_du_repas\": \"Nom du repas\",
            \"description\": \"Description du repas\",
            \"aliments\": [
                {
                    \"nom\": \"Nom de l'aliment\",
                    \"quantite\": 100,
                    \"unite\": \"g\",
                    \"calories\": 200,
                    \"proteines\": 20,
                    \"glucides\": 30,
                    \"lipides\": 10,
                    \"fibres\": 5
                }
            ]
        }";

        try {
            $response = $this->chatGPT->generateResponse($prompt);
            
            // Nettoyage de la réponse pour s'assurer qu'elle ne contient que du JSON
            $response = preg_replace('/^[^{]*|[^}]*$/', '', trim($response));
            
            $mealData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Erreur JSON: " . json_last_error_msg());
                error_log("Réponse reçue: " . $response);
                throw new Exception("Format de réponse invalide");
            }

            // Validation de la structure de données
            if (!isset($mealData['nom_du_repas']) || !isset($mealData['description']) || !isset($mealData['aliments']) || !is_array($mealData['aliments'])) {
                throw new Exception("Structure de données invalide");
            }

            // Validation et nettoyage des aliments
            foreach ($mealData['aliments'] as &$food) {
                $food = $this->validateAndCleanFoodData($food);
            }

            // Sauvegarder les aliments dans la base de données
            foreach ($mealData['aliments'] as $food) {
                $this->saveFood($food);
            }

            return $mealData;

        } catch (Exception $e) {
            error_log("Erreur lors de la suggestion de repas: " . $e->getMessage());
            throw new Exception("Impossible de générer une suggestion de repas");
        }
    }

    private function validateAndCleanFoodData($food) {
        $required_fields = ['nom', 'quantite', 'unite', 'calories', 'proteines', 'glucides', 'lipides', 'fibres'];
        
        foreach ($required_fields as $field) {
            if (!isset($food[$field])) {
                throw new Exception("Champ manquant dans les données alimentaires: {$field}");
            }
        }

        // Conversion et validation des valeurs numériques
        $food['quantite'] = floatval($food['quantite']);
        $food['calories'] = intval($food['calories']);
        $food['proteines'] = floatval($food['proteines']);
        $food['glucides'] = floatval($food['glucides']);
        $food['lipides'] = floatval($food['lipides']);
        $food['fibres'] = floatval($food['fibres']);

        // Validation des valeurs
        if ($food['quantite'] <= 0 || $food['calories'] < 0 || 
            $food['proteines'] < 0 || $food['glucides'] < 0 || 
            $food['lipides'] < 0 || $food['fibres'] < 0) {
            throw new Exception("Valeurs nutritionnelles invalides");
        }

        return $food;
    }

    private function saveFood($foodData) {
        $sql = "INSERT INTO foods (name, calories, proteins, carbs, fats, fiber, serving_size, serving_unit) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                calories = VALUES(calories),
                proteins = VALUES(proteins),
                carbs = VALUES(carbs),
                fats = VALUES(fats),
                fiber = VALUES(fiber),
                serving_size = VALUES(serving_size),
                serving_unit = VALUES(serving_unit)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $foodData['nom'],
                $foodData['calories'],
                $foodData['proteines'],
                $foodData['glucides'],
                $foodData['lipides'],
                $foodData['fibres'],
                $foodData['quantite'],
                $foodData['unite']
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Erreur lors de l'enregistrement de l'aliment: " . $e->getMessage());
            throw new Exception("Erreur lors de l'enregistrement de l'aliment");
        }
    }

    public function suggestDailyMeals($preferences = []) {
        $meals = [
            'breakfast' => $this->suggestMeal('petit-déjeuner', $preferences),
            'lunch' => $this->suggestMeal('déjeuner', $preferences),
            'dinner' => $this->suggestMeal('dîner', $preferences),
            'snack' => $this->suggestMeal('collation', $preferences)
        ];

        return $meals;
    }
} 