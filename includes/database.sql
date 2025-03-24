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

-- Table des types de repas
CREATE TABLE IF NOT EXISTS meal_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Données initiales pour les types de repas
INSERT INTO meal_types (name) VALUES
    ('Petit-déjeuner'),
    ('Déjeuner'),
    ('Dîner'),
    ('Collation');

-- Table des aliments
CREATE TABLE IF NOT EXISTS foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    calories DECIMAL(10,2) NOT NULL,
    proteins DECIMAL(10,2) NOT NULL,
    carbs DECIMAL(10,2) NOT NULL,
    fats DECIMAL(10,2) NOT NULL,
    serving_size VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Données initiales pour les aliments
INSERT INTO foods (name, calories, proteins, carbs, fats, serving_size) VALUES
    ('Pain complet (tranche)', 80, 3.0, 15.0, 1.0, '30g'),
    ('Œuf', 70, 6.0, 0.6, 4.8, '50g'),
    ('Poulet (blanc)', 165, 31.0, 0.0, 3.6, '100g'),
    ('Riz blanc cuit', 130, 2.7, 28.0, 0.3, '100g'),
    ('Pomme', 52, 0.3, 14.0, 0.2, '100g'),
    ('Yaourt nature', 59, 3.8, 4.7, 3.2, '100g'),
    ('Saumon', 208, 22.0, 0.0, 13.0, '100g'),
    ('Pâtes cuites', 131, 5.0, 25.0, 1.1, '100g'),
    ('Tomate', 18, 0.9, 3.9, 0.2, '100g'),
    ('Avocat', 160, 2.0, 8.5, 14.7, '100g');

-- Table des repas
CREATE TABLE IF NOT EXISTS meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meal_type_id INT NOT NULL,
    date DATETIME NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_type_id) REFERENCES meal_types(id)
);

-- Table de liaison entre les repas et les aliments
CREATE TABLE IF NOT EXISTS meal_foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_id) REFERENCES meals(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id)
);

-- Table des catégories d'exercices
CREATE TABLE exercise_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des types d'exercices
CREATE TABLE IF NOT EXISTS exercise_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    calories_per_hour INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Données initiales pour les types d'exercices
INSERT INTO exercise_types (name, calories_per_hour) VALUES
('Course à pied', 600),
('Marche rapide', 300),
('Vélo', 450),
('Natation', 500),
('Musculation', 400),
('Yoga', 250),
('Pilates', 250),
('Danse', 400),
('Tennis', 500),
('Football', 600),
('Basketball', 550),
('HIIT', 700),
('Elliptique', 450),
('Rameur', 600),
('Stretching', 150);

-- Table des exercices
CREATE TABLE IF NOT EXISTS exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exercise_type_id INT NOT NULL,
    date DATETIME NOT NULL,
    duration INT NOT NULL COMMENT 'Durée en minutes',
    intensity TINYINT NOT NULL COMMENT '1: Faible, 2: Modérée, 3: Élevée',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_type_id) REFERENCES exercise_types(id)
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
CREATE TABLE IF NOT EXISTS ai_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'implemented', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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

-- Table des types de notifications
CREATE TABLE IF NOT EXISTS notification_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Données initiales pour les types de notifications
INSERT INTO notification_types (name, icon) VALUES
('achievement', 'fa-trophy'),
('goal_completed', 'fa-flag-checkered'),
('weight_goal', 'fa-weight'),
('reminder', 'fa-bell'),
('suggestion', 'fa-lightbulb'),
('system', 'fa-info-circle');

-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (type_id) REFERENCES notification_types(id)
);

-- Index pour optimiser les performances
CREATE INDEX idx_daily_logs_date ON daily_logs(date);
CREATE INDEX idx_meals_date ON meals(date);
CREATE INDEX idx_workout_sessions_date ON workout_sessions(date);
CREATE INDEX idx_foods_name ON foods(name);
CREATE INDEX idx_exercises_name ON exercises(name);
CREATE INDEX idx_foods_barcode ON foods(barcode); 