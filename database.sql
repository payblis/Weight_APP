-- Création de la base de données
-- CREATE DATABASE IF NOT EXISTS weight_tracker;
USE weight_tracker;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NULL,
    role_id INT DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Table des profils utilisateurs
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gender ENUM('homme', 'femme', 'autre') NOT NULL,
    birth_date DATE NOT NULL,
    height INT NOT NULL,
    activity_level ENUM('sedentaire', 'leger', 'modere', 'actif', 'tres_actif') NOT NULL DEFAULT 'modere',
    preferred_bmr_formula VARCHAR(50) DEFAULT 'mifflin_st_jeor',
    nutrition_program_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des rôles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion des rôles par défaut
INSERT INTO roles (id, name, description) VALUES 
(1, 'admin', 'Administrateur avec accès complet'),
(2, 'user', 'Utilisateur standard');

-- Table des logs de poids
CREATE TABLE IF NOT EXISTS weight_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    log_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des logs d'IMC
CREATE TABLE IF NOT EXISTS bmi_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bmi DECIMAL(5,2) NOT NULL,
    log_date DATE NOT NULL,
    category VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des objectifs
CREATE TABLE IF NOT EXISTS goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_weight DECIMAL(5,2) NOT NULL,
    target_date DATE,
    status ENUM('en_cours', 'atteint', 'abandonné') DEFAULT 'en_cours',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des aliments
CREATE TABLE IF NOT EXISTS foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    calories INT NOT NULL,
    protein DECIMAL(5,2) DEFAULT 0,
    carbs DECIMAL(5,2) DEFAULT 0,
    fat DECIMAL(5,2) DEFAULT 0,
    fiber DECIMAL(5,2) DEFAULT 0,
    serving_size VARCHAR(50) DEFAULT 'portion',
    user_id INT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des repas
CREATE TABLE IF NOT EXISTS meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meal_type ENUM('petit_dejeuner', 'dejeuner', 'diner', 'collation', 'autre') NOT NULL,
    meal_name VARCHAR(100),
    log_date DATE NOT NULL,
    total_calories INT DEFAULT 0,
    total_protein DECIMAL(5,2) DEFAULT 0,
    total_carbs DECIMAL(5,2) DEFAULT 0,
    total_fat DECIMAL(5,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des logs alimentaires
CREATE TABLE IF NOT EXISTS food_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    meal_id INT NULL,
    quantity DECIMAL(5,2) DEFAULT 1,
    log_date DATE NOT NULL,
    calories INT NOT NULL,
    protein DECIMAL(5,2) DEFAULT 0,
    carbs DECIMAL(5,2) DEFAULT 0,
    fat DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(id) ON DELETE SET NULL
);

-- Table des exercices
CREATE TABLE IF NOT EXISTS exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    calories_per_hour INT NOT NULL,
    category ENUM('cardio', 'musculation', 'flexibilité', 'sport', 'autre') NOT NULL,
    user_id INT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des logs d'exercices
CREATE TABLE IF NOT EXISTS exercise_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exercise_id INT NOT NULL,
    duration INT NOT NULL,
    calories_burned INT NOT NULL,
    log_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
);

-- Table des suggestions IA
CREATE TABLE IF NOT EXISTS ai_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    suggestion_type ENUM('alimentation', 'exercice', 'motivation', 'autre') NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_implemented BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des paramètres de l'application
CREATE TABLE IF NOT EXISTS app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion des paramètres par défaut
INSERT INTO app_settings (setting_key, setting_value) VALUES 
('chatgpt_api_key', ''),
('site_name', 'Weight Tracker'),
('site_description', 'Application de suivi de poids et de nutrition'),
('maintenance_mode', '0');

-- Table des besoins caloriques des utilisateurs
CREATE TABLE IF NOT EXISTS user_calorie_needs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bmr DECIMAL(8,2) NOT NULL,
    tdee DECIMAL(8,2) NOT NULL,
    protein_target DECIMAL(8,2) NOT NULL,
    carbs_target DECIMAL(8,2) NOT NULL,
    fat_target DECIMAL(8,2) NOT NULL,
    calculation_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table de l'historique du bilan calorique
CREATE TABLE IF NOT EXISTS calorie_balance_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    log_date DATE NOT NULL,
    calories_consumed INT NOT NULL DEFAULT 0,
    calories_burned INT NOT NULL DEFAULT 0,
    calorie_balance INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des repas prédéfinis
CREATE TABLE IF NOT EXISTS predefined_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    total_calories INT DEFAULT 0,
    total_protein DECIMAL(5,2) DEFAULT 0,
    total_carbs DECIMAL(5,2) DEFAULT 0,
    total_fat DECIMAL(5,2) DEFAULT 0,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des aliments dans les repas prédéfinis
CREATE TABLE IF NOT EXISTS predefined_meal_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    predefined_meal_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity DECIMAL(5,2) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (predefined_meal_id) REFERENCES predefined_meals(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- Table des repas favoris des utilisateurs
CREATE TABLE IF NOT EXISTS user_favorite_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    predefined_meal_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (predefined_meal_id) REFERENCES predefined_meals(id) ON DELETE CASCADE
);

-- Table des préférences alimentaires des utilisateurs
CREATE TABLE IF NOT EXISTS user_food_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NULL,
    food_name VARCHAR(100) NULL,
    preference_type ENUM('liked', 'disliked', 'allergic', 'intolerant') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE SET NULL
);

-- Table des programmes nutritionnels
CREATE TABLE IF NOT EXISTS nutrition_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    calorie_adjustment INT NOT NULL DEFAULT 0,
    protein_ratio DECIMAL(5,2) NOT NULL DEFAULT 30,
    carbs_ratio DECIMAL(5,2) NOT NULL DEFAULT 40,
    fat_ratio DECIMAL(5,2) NOT NULL DEFAULT 30,
    is_public BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insertion des programmes nutritionnels par défaut
INSERT INTO nutrition_programs (id, name, description, calorie_adjustment, protein_ratio, carbs_ratio, fat_ratio) VALUES 
(1, 'Perte de poids', 'Programme pour perdre du poids de manière saine et durable', -500, 35, 35, 30),
(2, 'Maintien du poids', 'Programme pour maintenir son poids actuel', 0, 30, 40, 30),
(3, 'Prise de masse', 'Programme pour prendre du poids et de la masse musculaire', 500, 30, 45, 25),
(4, 'Cétogène', 'Programme à faible teneur en glucides et riche en graisses', -300, 25, 5, 70),
(5, 'Végétarien', 'Programme équilibré sans viande', 0, 25, 50, 25);

-- Table des plans de repas
CREATE TABLE IF NOT EXISTS meal_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nutrition_program_id INT NULL,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (nutrition_program_id) REFERENCES nutrition_programs(id) ON DELETE SET NULL
);

-- Table des jours de plan de repas
CREATE TABLE IF NOT EXISTS meal_plan_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_plan_id INT NOT NULL,
    day_date DATE NOT NULL,
    total_calories INT DEFAULT 0,
    total_protein DECIMAL(5,2) DEFAULT 0,
    total_carbs DECIMAL(5,2) DEFAULT 0,
    total_fat DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_plan_id) REFERENCES meal_plans(id) ON DELETE CASCADE
);

-- Table des repas dans les jours de plan
CREATE TABLE IF NOT EXISTS meal_plan_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_plan_day_id INT NOT NULL,
    meal_type ENUM('petit_dejeuner', 'dejeuner', 'diner', 'collation', 'autre') NOT NULL,
    meal_name VARCHAR(100),
    total_calories INT DEFAULT 0,
    total_protein DECIMAL(5,2) DEFAULT 0,
    total_carbs DECIMAL(5,2) DEFAULT 0,
    total_fat DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_plan_day_id) REFERENCES meal_plan_days(id) ON DELETE CASCADE
);

-- Table des aliments dans les repas du plan
CREATE TABLE IF NOT EXISTS meal_plan_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_plan_meal_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity DECIMAL(5,2) DEFAULT 1,
    calories INT NOT NULL,
    protein DECIMAL(5,2) DEFAULT 0,
    carbs DECIMAL(5,2) DEFAULT 0,
    fat DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_plan_meal_id) REFERENCES meal_plan_meals(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- Création d'un compte administrateur par défaut
INSERT INTO users (username, email, password, first_name, last_name, role_id, created_at) 
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 1, NOW());

-- Création d'un profil pour l'administrateur
INSERT INTO user_profiles (user_id, gender, birth_date, height, activity_level) 
VALUES (1, 'homme', '1990-01-01', 175, 'modere');

-- Ajout d'index pour améliorer les performances
CREATE INDEX idx_weight_logs_user_date ON weight_logs(user_id, log_date);
CREATE INDEX idx_food_logs_user_date ON food_logs(user_id, log_date);
CREATE INDEX idx_exercise_logs_user_date ON exercise_logs(user_id, log_date);
CREATE INDEX idx_meals_user_date ON meals(user_id, log_date);
CREATE INDEX idx_food_logs_meal ON food_logs(meal_id);
