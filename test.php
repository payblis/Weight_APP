<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

class WeightTrackerTest {
    private $conn;
    private $testUserId;
    private $testFoodId;
    private $testExerciseId;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    public function runAllTests() {
        echo "=== Démarrage des tests de Weight Tracker ===\n\n";
        
        // Test de la connexion à la base de données
        $this->testDatabaseConnection();
        
        // Test des tables requises
        $this->testRequiredTables();
        
        // Test des fonctionnalités utilisateur
        $this->testUserManagement();
        
        // Test des fonctionnalités de poids
        $this->testWeightTracking();
        
        // Test des fonctionnalités alimentaires
        $this->testFoodTracking();
        
        // Test des fonctionnalités d'exercice
        $this->testExerciseTracking();
        
        // Test des calculs nutritionnels
        $this->testNutritionCalculations();
        
        echo "\n=== Tests terminés ===\n";
    }
    
    private function testDatabaseConnection() {
        echo "Test de la connexion à la base de données...\n";
        if ($this->conn->ping()) {
            echo "✓ Connexion à la base de données réussie\n";
        } else {
            echo "✗ Échec de la connexion à la base de données\n";
        }
    }
    
    private function testRequiredTables() {
        echo "\nTest des tables requises...\n";
        $requiredTables = [
            'users', 'user_profiles', 'weight_logs', 'food_logs',
            'exercise_logs', 'goals', 'foods', 'exercises',
            'ai_suggestions', 'nutrition_programs'
        ];
        
        foreach ($requiredTables as $table) {
            $result = $this->conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                echo "✓ Table '$table' présente\n";
            } else {
                echo "✗ Table '$table' manquante\n";
            }
        }
    }
    
    private function testUserManagement() {
        echo "\nTest de la gestion des utilisateurs...\n";
        
        // Test de création d'utilisateur
        $username = 'test_user_' . time();
        $email = 'test' . time() . '@example.com';
        $password = password_hash('test123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sss', $username, $email, $password);
        
        if ($stmt->execute()) {
            echo "✓ Création d'utilisateur réussie\n";
            $this->testUserId = $this->conn->insert_id;
            
            // Test de création de profil
            $sql = "INSERT INTO user_profiles (user_id, gender, birth_date, height, activity_level) 
                    VALUES (?, 'homme', '1990-01-01', 180, 'modere')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $this->testUserId);
            
            if ($stmt->execute()) {
                echo "✓ Création de profil réussie\n";
            } else {
                echo "✗ Échec de la création de profil\n";
            }
        } else {
            echo "✗ Échec de la création d'utilisateur\n";
        }
    }
    
    private function testWeightTracking() {
        echo "\nTest du suivi du poids...\n";
        
        if (!$this->testUserId) {
            echo "✗ Impossible de tester le suivi du poids : utilisateur non créé\n";
            return;
        }
        
        // Test d'ajout de poids
        $sql = "INSERT INTO weight_logs (user_id, weight, log_date) VALUES (?, 75.5, CURDATE())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $this->testUserId);
        
        if ($stmt->execute()) {
            echo "✓ Ajout de poids réussi\n";
            
            // Test de calcul d'IMC
            $sql = "SELECT w.weight, p.height FROM weight_logs w 
                    JOIN user_profiles p ON w.user_id = p.user_id 
                    WHERE w.user_id = ? ORDER BY w.log_date DESC LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $this->testUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            
            if ($data) {
                $bmi = $data['weight'] / (($data['height']/100) * ($data['height']/100));
                echo "✓ Calcul d'IMC réussi (IMC: " . round($bmi, 2) . ")\n";
            }
        } else {
            echo "✗ Échec de l'ajout de poids\n";
        }
    }
    
    private function testFoodTracking() {
        echo "\nTest du suivi alimentaire...\n";
        
        if (!$this->testUserId) {
            echo "✗ Impossible de tester le suivi alimentaire : utilisateur non créé\n";
            return;
        }
        
        // Test d'ajout d'aliment
        $sql = "INSERT INTO foods (name, calories, protein, carbs, fat) 
                VALUES ('Pomme', 95, 0.3, 25, 0.3)";
        if ($this->conn->query($sql)) {
            $this->testFoodId = $this->conn->insert_id;
            echo "✓ Ajout d'aliment réussi\n";
            
            // Test d'ajout de repas
            $sql = "INSERT INTO meals (user_id, meal_type, meal_name, log_date) 
                    VALUES (?, 'petit_dejeuner', 'Petit-déjeuner test', CURDATE())";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $this->testUserId);
            
            if ($stmt->execute()) {
                $mealId = $this->conn->insert_id;
                echo "✓ Ajout de repas réussi\n";
                
                // Test d'ajout d'aliment au repas
                $sql = "INSERT INTO food_logs (user_id, food_id, meal_id, quantity, log_date, calories) 
                        VALUES (?, ?, ?, 1, CURDATE(), 95)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('iii', $this->testUserId, $this->testFoodId, $mealId);
                
                if ($stmt->execute()) {
                    echo "✓ Ajout d'aliment au repas réussi\n";
                } else {
                    echo "✗ Échec de l'ajout d'aliment au repas\n";
                }
            }
        } else {
            echo "✗ Échec de l'ajout d'aliment\n";
        }
    }
    
    private function testExerciseTracking() {
        echo "\nTest du suivi des exercices...\n";
        
        if (!$this->testUserId) {
            echo "✗ Impossible de tester le suivi des exercices : utilisateur non créé\n";
            return;
        }
        
        // Test d'ajout d'exercice
        $sql = "INSERT INTO exercises (name, calories_per_hour, category) 
                VALUES ('Course à pied', 600, 'cardio')";
        if ($this->conn->query($sql)) {
            $this->testExerciseId = $this->conn->insert_id;
            echo "✓ Ajout d'exercice réussi\n";
            
            // Test d'ajout de log d'exercice
            $sql = "INSERT INTO exercise_logs (user_id, exercise_id, duration, calories_burned, log_date) 
                    VALUES (?, ?, 30, 300, CURDATE())";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ii', $this->testUserId, $this->testExerciseId);
            
            if ($stmt->execute()) {
                echo "✓ Ajout de log d'exercice réussi\n";
            } else {
                echo "✗ Échec de l'ajout de log d'exercice\n";
            }
        } else {
            echo "✗ Échec de l'ajout d'exercice\n";
        }
    }
    
    private function testNutritionCalculations() {
        echo "\nTest des calculs nutritionnels...\n";
        
        if (!$this->testUserId) {
            echo "✗ Impossible de tester les calculs nutritionnels : utilisateur non créé\n";
            return;
        }
        
        // Test de calcul du bilan calorique
        $sql = "SELECT 
                    COALESCE(SUM(fl.calories), 0) as calories_consumed,
                    COALESCE(SUM(el.calories_burned), 0) as calories_burned
                FROM users u
                LEFT JOIN food_logs fl ON u.id = fl.user_id
                LEFT JOIN exercise_logs el ON u.id = el.user_id
                WHERE u.id = ? AND fl.log_date = CURDATE() AND el.log_date = CURDATE()";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $this->testUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if ($data) {
            $balance = $data['calories_consumed'] - $data['calories_burned'];
            echo "✓ Calcul du bilan calorique réussi (Balance: $balance kcal)\n";
        } else {
            echo "✗ Échec du calcul du bilan calorique\n";
        }
    }
    
    public function cleanup() {
        if ($this->testUserId) {
            // Nettoyage des données de test
            $tables = ['exercise_logs', 'food_logs', 'meals', 'weight_logs', 'user_profiles', 'users'];
            foreach ($tables as $table) {
                $this->conn->query("DELETE FROM $table WHERE user_id = {$this->testUserId}");
            }
            
            if ($this->testFoodId) {
                $this->conn->query("DELETE FROM foods WHERE id = {$this->testFoodId}");
            }
            
            if ($this->testExerciseId) {
                $this->conn->query("DELETE FROM exercises WHERE id = {$this->testExerciseId}");
            }
            
            echo "\n✓ Nettoyage des données de test effectué\n";
        }
    }
}

// Exécution des tests
$test = new WeightTrackerTest();
$test->runAllTests();
$test->cleanup(); 