<?php
require_once 'config/database.php';

class DatabaseChecker {
    private $conn;
    private $errors = [];
    private $warnings = [];
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    public function runAllChecks() {
        echo "=== Vérification complète de la base de données ===\n\n";
        
        // Vérification des tables requises
        $this->checkRequiredTables();
        
        // Vérification des relations entre les tables
        $this->checkTableRelations();
        
        // Vérification des données par défaut
        $this->checkDefaultData();
        
        // Vérification des contraintes
        $this->checkConstraints();
        
        // Affichage des résultats
        $this->displayResults();
    }
    
    private function checkRequiredTables() {
        echo "Vérification des tables requises...\n";
        
        $requiredTables = [
            'users', 'user_profiles', 'roles', 'weight_logs', 'bmi_logs',
            'goals', 'foods', 'meals', 'food_logs', 'exercises',
            'exercise_logs', 'ai_suggestions', 'app_settings',
            'user_calorie_needs', 'calorie_balance_history',
            'predefined_meals', 'predefined_meal_items',
            'user_favorite_meals', 'nutrition_programs'
        ];
        
        foreach ($requiredTables as $table) {
            $result = $this->conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                echo "✓ Table '$table' présente\n";
                
                // Vérification de la structure de la table
                $this->checkTableStructure($table);
            } else {
                echo "✗ Table '$table' manquante\n";
                $this->errors[] = "Table '$table' manquante";
            }
        }
    }
    
    private function checkTableStructure($table) {
        $result = $this->conn->query("DESCRIBE $table");
        if (!$result) {
            $this->errors[] = "Impossible de décrire la structure de la table '$table'";
            return;
        }
        
        while ($row = $result->fetch_assoc()) {
            // Vérification des champs NOT NULL
            if ($row['Null'] === 'NO' && $row['Default'] === null) {
                $this->warnings[] = "Champ '{$row['Field']}' dans la table '$table' est NOT NULL sans valeur par défaut";
            }
            
            // Vérification des clés étrangères
            if ($row['Key'] === 'MUL') {
                $this->checkForeignKey($table, $row['Field']);
            }
        }
    }
    
    private function checkForeignKey($table, $column) {
        $result = $this->conn->query("
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                CONSTRAINT_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM
                information_schema.KEY_COLUMN_USAGE
            WHERE
                TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '$table'
                AND COLUMN_NAME = '$column'
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if ($result->num_rows === 0) {
            $this->warnings[] = "Index sur '$column' dans la table '$table' sans contrainte de clé étrangère";
        }
    }
    
    private function checkTableRelations() {
        echo "\nVérification des relations entre les tables...\n";
        
        $relations = [
            'user_profiles' => ['users'],
            'weight_logs' => ['users'],
            'bmi_logs' => ['users'],
            'goals' => ['users'],
            'food_logs' => ['users', 'foods', 'meals'],
            'exercise_logs' => ['users', 'exercises'],
            'ai_suggestions' => ['users'],
            'user_calorie_needs' => ['users'],
            'calorie_balance_history' => ['users'],
            'predefined_meals' => ['users'],
            'predefined_meal_items' => ['predefined_meals', 'foods'],
            'user_favorite_meals' => ['users', 'predefined_meals']
        ];
        
        foreach ($relations as $table => $references) {
            foreach ($references as $refTable) {
                $this->checkTableRelation($table, $refTable);
            }
        }
    }
    
    private function checkTableRelation($table, $refTable) {
        $result = $this->conn->query("
            SELECT COUNT(*) as count
            FROM $table t
            LEFT JOIN $refTable r ON t.user_id = r.id
            WHERE r.id IS NULL AND t.user_id IS NOT NULL
        ");
        
        if ($result && $result->fetch_assoc()['count'] > 0) {
            $this->errors[] = "Orphelins trouvés dans la table '$table' référençant '$refTable'";
        }
    }
    
    private function checkDefaultData() {
        echo "\nVérification des données par défaut...\n";
        
        // Vérification des rôles
        $result = $this->conn->query("SELECT COUNT(*) as count FROM roles");
        if ($result && $result->fetch_assoc()['count'] === 0) {
            $this->errors[] = "Table 'roles' vide - les rôles par défaut sont manquants";
        }
        
        // Vérification des paramètres de l'application
        $result = $this->conn->query("SELECT COUNT(*) as count FROM app_settings");
        if ($result && $result->fetch_assoc()['count'] === 0) {
            $this->errors[] = "Table 'app_settings' vide - les paramètres par défaut sont manquants";
        }
    }
    
    private function checkConstraints() {
        echo "\nVérification des contraintes...\n";
        
        // Vérification des ENUM
        $this->checkEnumConstraints();
        
        // Vérification des valeurs par défaut
        $this->checkDefaultConstraints();
    }
    
    private function checkEnumConstraints() {
        $enums = [
            'user_profiles' => [
                'gender' => ['homme', 'femme', 'autre'],
                'activity_level' => ['sedentaire', 'leger', 'modere', 'actif', 'tres_actif']
            ],
            'meals' => [
                'meal_type' => ['petit_dejeuner', 'dejeuner', 'diner', 'collation', 'autre']
            ],
            'exercises' => [
                'category' => ['cardio', 'musculation', 'flexibilité', 'sport', 'autre']
            ],
            'ai_suggestions' => [
                'suggestion_type' => ['alimentation', 'exercice', 'motivation', 'autre']
            ]
        ];
        
        foreach ($enums as $table => $columns) {
            foreach ($columns as $column => $values) {
                $this->checkEnumValues($table, $column, $values);
            }
        }
    }
    
    private function checkEnumValues($table, $column, $values) {
        $result = $this->conn->query("
            SELECT DISTINCT $column
            FROM $table
            WHERE $column NOT IN (" . implode(',', array_map(function($v) {
                return "'$v'";
            }, $values)) . ")
        ");
        
        if ($result && $result->num_rows > 0) {
            $this->errors[] = "Valeurs invalides trouvées dans la colonne '$column' de la table '$table'";
        }
    }
    
    private function checkDefaultConstraints() {
        $defaults = [
            'user_profiles' => [
                'activity_level' => 'modere',
                'preferred_bmr_formula' => 'mifflin_st_jeor'
            ],
            'foods' => [
                'protein' => 0,
                'carbs' => 0,
                'fat' => 0,
                'fiber' => 0,
                'serving_size' => 'portion',
                'is_public' => false
            ],
            'meals' => [
                'total_calories' => 0,
                'total_protein' => 0,
                'total_carbs' => 0,
                'total_fat' => 0
            ]
        ];
        
        foreach ($defaults as $table => $columns) {
            foreach ($columns as $column => $defaultValue) {
                $this->checkDefaultValue($table, $column, $defaultValue);
            }
        }
    }
    
    private function checkDefaultValue($table, $column, $defaultValue) {
        $result = $this->conn->query("
            SELECT COUNT(*) as count
            FROM $table
            WHERE $column IS NULL
        ");
        
        if ($result && $result->fetch_assoc()['count'] > 0) {
            $this->warnings[] = "Valeurs NULL trouvées dans la colonne '$column' de la table '$table'";
        }
    }
    
    private function displayResults() {
        echo "\n=== Résultats de la vérification ===\n";
        
        if (empty($this->errors) && empty($this->warnings)) {
            echo "✓ Toutes les vérifications ont réussi !\n";
            return;
        }
        
        if (!empty($this->errors)) {
            echo "\nErreurs trouvées :\n";
            foreach ($this->errors as $error) {
                echo "✗ $error\n";
            }
        }
        
        if (!empty($this->warnings)) {
            echo "\nAvertissements :\n";
            foreach ($this->warnings as $warning) {
                echo "! $warning\n";
            }
        }
        
        echo "\nStatistiques :\n";
        echo "- Nombre d'erreurs : " . count($this->errors) . "\n";
        echo "- Nombre d'avertissements : " . count($this->warnings) . "\n";
    }
}

// Exécution des vérifications
$checker = new DatabaseChecker();
$checker->runAllChecks(); 