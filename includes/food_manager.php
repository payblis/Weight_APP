<?php
require_once 'config.php';

class FoodManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addFood($data) {
        $sql = "INSERT INTO foods (name, calories, proteins, carbs, fats, fiber, serving_size, serving_unit) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['calories'],
                $data['proteins'],
                $data['carbs'],
                $data['fats'],
                $data['fiber'] ?? 0,
                $data['serving_size'],
                $data['serving_unit']
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout d'un aliment: " . $e->getMessage());
            throw new Exception("Erreur lors de l'ajout de l'aliment");
        }
    }

    public function addMeal($userId, $data) {
        try {
            $this->pdo->beginTransaction();

            // Création du repas
            $sql = "INSERT INTO meals (user_id, date, meal_type, notes) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $userId,
                $data['date'],
                $data['meal_type'],
                $data['notes'] ?? null
            ]);
            $mealId = $this->pdo->lastInsertId();

            // Ajout des aliments au repas
            foreach ($data['foods'] as $food) {
                $sql = "INSERT INTO meal_foods (meal_id, food_id, servings) VALUES (?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $mealId,
                    $food['food_id'],
                    $food['servings']
                ]);
            }

            $this->pdo->commit();
            return $mealId;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de l'ajout d'un repas: " . $e->getMessage());
            throw new Exception("Erreur lors de l'ajout du repas");
        }
    }

    public function getDailyNutrition($userId, $date) {
        $sql = "SELECT 
                    SUM(f.calories * mf.servings) as total_calories,
                    SUM(f.proteins * mf.servings) as total_proteins,
                    SUM(f.carbs * mf.servings) as total_carbs,
                    SUM(f.fats * mf.servings) as total_fats,
                    SUM(f.fiber * mf.servings) as total_fiber
                FROM meals m
                JOIN meal_foods mf ON m.id = mf.meal_id
                JOIN foods f ON mf.food_id = f.id
                WHERE m.user_id = ? AND m.date = ?";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des nutriments: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des nutriments");
        }
    }

    public function searchFood($query) {
        $sql = "SELECT * FROM foods WHERE name LIKE ? LIMIT 10";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['%' . $query . '%']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche d'aliments: " . $e->getMessage());
            throw new Exception("Erreur lors de la recherche d'aliments");
        }
    }

    public function getMealsByDate($userId, $date) {
        $sql = "SELECT 
                    m.id as meal_id,
                    m.meal_type,
                    m.notes,
                    f.name as food_name,
                    f.calories,
                    f.proteins,
                    f.carbs,
                    f.fats,
                    f.fiber,
                    f.serving_size,
                    f.serving_unit,
                    mf.servings
                FROM meals m
                JOIN meal_foods mf ON m.id = mf.meal_id
                JOIN foods f ON mf.food_id = f.id
                WHERE m.user_id = ? AND m.date = ?
                ORDER BY m.meal_type, m.id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $date]);
            
            $meals = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mealId = $row['meal_id'];
                if (!isset($meals[$mealId])) {
                    $meals[$mealId] = [
                        'meal_type' => $row['meal_type'],
                        'notes' => $row['notes'],
                        'foods' => []
                    ];
                }
                $meals[$mealId]['foods'][] = [
                    'name' => $row['food_name'],
                    'calories' => $row['calories'],
                    'proteins' => $row['proteins'],
                    'carbs' => $row['carbs'],
                    'fats' => $row['fats'],
                    'fiber' => $row['fiber'],
                    'serving_size' => $row['serving_size'],
                    'serving_unit' => $row['serving_unit'],
                    'servings' => $row['servings']
                ];
            }
            return $meals;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des repas: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des repas");
        }
    }
} 