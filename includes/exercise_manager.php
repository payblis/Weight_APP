<?php
require_once 'config.php';

class ExerciseManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addExercise($data) {
        $sql = "INSERT INTO exercises (name, category, calories_per_hour, description) 
                VALUES (?, ?, ?, ?)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['category'],
                $data['calories_per_hour'],
                $data['description'] ?? null
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout d'un exercice: " . $e->getMessage());
            throw new Exception("Erreur lors de l'ajout de l'exercice");
        }
    }

    public function addWorkoutSession($userId, $data) {
        try {
            $this->pdo->beginTransaction();

            // Création de la séance
            $sql = "INSERT INTO workout_sessions (user_id, date, notes) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $userId,
                $data['date'],
                $data['notes'] ?? null
            ]);
            $sessionId = $this->pdo->lastInsertId();

            // Ajout des exercices à la séance
            foreach ($data['exercises'] as $exercise) {
                $sql = "INSERT INTO workout_exercises 
                        (workout_session_id, exercise_id, duration, intensity, calories_burned, sets, reps, weight, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $sessionId,
                    $exercise['exercise_id'],
                    $exercise['duration'],
                    $exercise['intensity'],
                    $exercise['calories_burned'],
                    $exercise['sets'] ?? null,
                    $exercise['reps'] ?? null,
                    $exercise['weight'] ?? null,
                    $exercise['notes'] ?? null
                ]);
            }

            $this->pdo->commit();
            return $sessionId;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de l'ajout d'une séance: " . $e->getMessage());
            throw new Exception("Erreur lors de l'ajout de la séance");
        }
    }

    public function getDailyExercises($userId, $date) {
        $sql = "SELECT 
                    ws.id as session_id,
                    ws.notes as session_notes,
                    e.name as exercise_name,
                    e.category,
                    we.duration,
                    we.intensity,
                    we.calories_burned,
                    we.sets,
                    we.reps,
                    we.weight,
                    we.notes as exercise_notes
                FROM workout_sessions ws
                JOIN workout_exercises we ON ws.id = we.workout_session_id
                JOIN exercises e ON we.exercise_id = e.id
                WHERE ws.user_id = ? AND ws.date = ?
                ORDER BY ws.id, e.name";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $date]);
            
            $sessions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $sessionId = $row['session_id'];
                if (!isset($sessions[$sessionId])) {
                    $sessions[$sessionId] = [
                        'notes' => $row['session_notes'],
                        'exercises' => []
                    ];
                }
                $sessions[$sessionId]['exercises'][] = [
                    'name' => $row['exercise_name'],
                    'category' => $row['category'],
                    'duration' => $row['duration'],
                    'intensity' => $row['intensity'],
                    'calories_burned' => $row['calories_burned'],
                    'sets' => $row['sets'],
                    'reps' => $row['reps'],
                    'weight' => $row['weight'],
                    'notes' => $row['exercise_notes']
                ];
            }
            return $sessions;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des exercices: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des exercices");
        }
    }

    public function searchExercise($query) {
        $sql = "SELECT * FROM exercises WHERE name LIKE ? LIMIT 10";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['%' . $query . '%']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche d'exercices: " . $e->getMessage());
            throw new Exception("Erreur lors de la recherche d'exercices");
        }
    }

    public function calculateCaloriesBurned($userId, $date) {
        $sql = "SELECT SUM(we.calories_burned) as total_calories_burned
                FROM workout_sessions ws
                JOIN workout_exercises we ON ws.id = we.workout_session_id
                WHERE ws.user_id = ? AND ws.date = ?";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_calories_burned'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul des calories brûlées: " . $e->getMessage());
            throw new Exception("Erreur lors du calcul des calories brûlées");
        }
    }
} 