<?php
require_once 'config.php';

class ChatGPT {
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $model = 'gpt-3.5-turbo';

    public function __construct() {
        $this->api_key = CHATGPT_API_KEY;
    }

    public function generateResponse($prompt) {
        if (empty($this->api_key) || $this->api_key === 'YOUR_API_KEY') {
            return "Configuration de l'API ChatGPT requise. Veuillez configurer votre clé API.";
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        ];

        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ];

        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        try {
            $response = curl_exec($ch);
            
            if ($response === false) {
                throw new Exception(curl_error($ch));
            }

            $decoded_response = json_decode($response, true);

            if (isset($decoded_response['error'])) {
                throw new Exception($decoded_response['error']['message']);
            }

            if (isset($decoded_response['choices'][0]['message']['content'])) {
                return $decoded_response['choices'][0]['message']['content'];
            } else {
                throw new Exception("Format de réponse inattendu");
            }

        } catch (Exception $e) {
            error_log("Erreur ChatGPT: " . $e->getMessage());
            return "Erreur lors de la génération de la suggestion. Veuillez réessayer plus tard.";
        } finally {
            curl_close($ch);
        }
    }

    public function generateMealSuggestion($userData) {
        // Validation des données requises
        if (!isset($userData['current_weight']) || !isset($userData['target_weight'])) {
            throw new Exception("Données utilisateur incomplètes pour la suggestion de repas");
        }

        $prompt = "En tant que nutritionniste, suggère un repas sain adapté à cette personne:\n";
        $prompt .= "Poids actuel: {$userData['current_weight']} kg\n";
        $prompt .= "Poids cible: {$userData['target_weight']} kg\n";
        
        if (isset($userData['weekly_goal']) && $userData['weekly_goal'] > 0) {
            $prompt .= "Objectif hebdomadaire: {$userData['weekly_goal']} kg\n";
        }

        if (isset($userData['activity_level'])) {
            $prompt .= "Niveau d'activité: {$userData['activity_level']}\n";
        }

        $prompt .= "\nRéponds UNIQUEMENT avec un objet JSON valide au format suivant (sans texte avant ou après) :
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
        
        return $this->generateResponse($prompt);
    }

    public function generateExerciseSuggestion($userData) {
        // Validation des données requises
        if (!isset($userData['current_weight']) || !isset($userData['target_weight'])) {
            throw new Exception("Données utilisateur incomplètes pour la suggestion d'exercices");
        }

        $prompt = "En tant que coach sportif, suggère un programme d'exercices adapté à cette personne:\n";
        $prompt .= "Poids actuel: {$userData['current_weight']} kg\n";
        $prompt .= "Poids cible: {$userData['target_weight']} kg\n";
        
        if (isset($userData['weekly_goal']) && $userData['weekly_goal'] > 0) {
            $prompt .= "Objectif hebdomadaire: {$userData['weekly_goal']} kg\n";
        }

        if (isset($userData['activity_level'])) {
            $prompt .= "Niveau d'activité: {$userData['activity_level']}\n";
        }

        $prompt .= "\nRéponds UNIQUEMENT avec un objet JSON valide au format suivant (sans texte avant ou après) :
        {
            \"programme\": \"Nom du programme\",
            \"description\": \"Description du programme\",
            \"exercices\": [
                {
                    \"nom\": \"Nom de l'exercice\",
                    \"duree\": 30,
                    \"unite\": \"minutes\",
                    \"intensite\": \"modérée\",
                    \"calories\": 300,
                    \"description\": \"Description de l'exercice\"
                }
            ]
        }";
        
        return $this->generateResponse($prompt);
    }
} 