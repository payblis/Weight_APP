-- Création de la base de données
CREATE DATABASE IF NOT EXISTS test;
USE test;

-- Structure de la base de données pour l'application de suivi de poids

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    height DECIMAL(5,2),
    current_weight DECIMAL(5,2),
    target_weight DECIMAL(5,2),
    target_weeks INT,
    activity_level ENUM('sedentary', 'light', 'moderate', 'very_active') NOT NULL DEFAULT 'moderate',
    age INT,
    gender ENUM('M', 'F', 'other'),
    bmr DECIMAL(8,2),
    remember_token VARCHAR(64),
    google_id VARCHAR(255),
    facebook_id VARCHAR(255),
    preferences TEXT,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des objectifs de poids
CREATE TABLE weight_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    start_weight DECIMAL(5,2) NOT NULL,
    target_weight DECIMAL(5,2) NOT NULL,
    weekly_goal DECIMAL(3,2) NOT NULL,
    start_date DATE NOT NULL,
    target_date DATE NOT NULL,
    status ENUM('active', 'completed', 'abandoned') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des journaux quotidiens de poids
CREATE TABLE daily_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    notes TEXT,
    photo_before VARCHAR(255),
    photo_after VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_weight (user_id, date)
);

-- Table des catégories d'aliments
CREATE TABLE food_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des aliments
CREATE TABLE foods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    barcode VARCHAR(50) UNIQUE,
    name VARCHAR(255) NOT NULL,
    brand VARCHAR(255),
    category_id INT,
    calories INT NOT NULL,
    proteins DECIMAL(5,2) NOT NULL,
    carbs DECIMAL(5,2) NOT NULL,
    fats DECIMAL(5,2) NOT NULL,
    fiber DECIMAL(5,2),
    serving_size INT NOT NULL,
    serving_unit VARCHAR(20) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES food_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Table des aliments favoris
CREATE TABLE favorite_foods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, food_id)
);

-- Table des repas
CREATE TABLE meals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
    name VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des aliments dans les repas
CREATE TABLE meal_foods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    meal_id INT NOT NULL,
    food_id INT NOT NULL,
    servings DECIMAL(4,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_id) REFERENCES meals(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- Table des catégories d'exercices
CREATE TABLE exercise_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des exercices
CREATE TABLE exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
    calories_per_hour INT,
    instructions TEXT,
    video_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES exercise_categories(id)
);

-- Table des séances d'exercice
CREATE TABLE workout_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    name VARCHAR(255),
    notes TEXT,
    total_duration INT,
    total_calories INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des exercices effectués
CREATE TABLE workout_exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    workout_session_id INT NOT NULL,
    exercise_id INT NOT NULL,
    duration INT NOT NULL,
    intensity ENUM('low', 'medium', 'high') NOT NULL,
    calories_burned INT,
    sets INT,
    reps INT,
    weight DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workout_session_id) REFERENCES workout_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id)
);

-- Table des suggestions IA
CREATE TABLE ai_suggestions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('meal', 'exercise', 'advice') NOT NULL,
    content TEXT NOT NULL,
    is_favorite BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des conversations IA
CREATE TABLE ai_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des préférences alimentaires
CREATE TABLE dietary_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    preference_type ENUM('allergy', 'dislike', 'diet_type') NOT NULL,
    value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des badges et récompenses
CREATE TABLE achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_url VARCHAR(255),
    condition_type VARCHAR(50) NOT NULL,
    condition_value INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des badges utilisateur
CREATE TABLE user_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id),
    UNIQUE KEY unique_achievement (user_id, achievement_id)
);

-- Index pour optimiser les performances
CREATE INDEX idx_daily_logs_date ON daily_logs(date);
CREATE INDEX idx_meals_date ON meals(date);
CREATE INDEX idx_workout_sessions_date ON workout_sessions(date);
CREATE INDEX idx_foods_name ON foods(name);
CREATE INDEX idx_exercises_name ON exercises(name);
CREATE INDEX idx_foods_barcode ON foods(barcode); 