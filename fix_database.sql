-- Correction des tables manquantes ou incomplètes

-- Désactiver temporairement les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Table des rôles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    postal_code VARCHAR(10) NULL,
    country VARCHAR(100) NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    role_id INT DEFAULT 2,
    last_login TIMESTAMP NULL,
    reset_token VARCHAR(255) NULL,
    reset_token_expires TIMESTAMP NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'suspended', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Table des programmes nutritionnels
CREATE TABLE nutrition_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    calorie_adjustment INT NOT NULL DEFAULT 0,
    protein_ratio DECIMAL(5,2) NOT NULL DEFAULT 30,
    carbs_ratio DECIMAL(5,2) NOT NULL DEFAULT 40,
    fat_ratio DECIMAL(5,2) NOT NULL DEFAULT 30,
    is_public BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des profils utilisateurs
CREATE TABLE user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gender ENUM('homme', 'femme', 'autre') NOT NULL,
    birth_date DATE NOT NULL,
    height INT NOT NULL,
    activity_level ENUM('sedentaire', 'leger', 'modere', 'actif', 'tres_actif') NOT NULL DEFAULT 'modere',
    preferred_bmr_formula VARCHAR(50) DEFAULT 'mifflin_st_jeor',
    nutrition_program_id INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (nutrition_program_id) REFERENCES nutrition_programs(id) ON DELETE SET NULL
);

-- Table des aliments
CREATE TABLE foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    calories INT NOT NULL DEFAULT 0,
    protein DECIMAL(5,2) DEFAULT 0,
    carbs DECIMAL(5,2) DEFAULT 0,
    fat DECIMAL(5,2) DEFAULT 0,
    fiber DECIMAL(5,2) DEFAULT 0,
    serving_size VARCHAR(50) DEFAULT 'portion',
    is_public BOOLEAN DEFAULT FALSE,
    created_by_admin BOOLEAN DEFAULT FALSE,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des repas
CREATE TABLE meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meal_type ENUM('petit_dejeuner', 'dejeuner', 'diner', 'collation', 'autre') NOT NULL,
    meal_name VARCHAR(100),
    log_date DATE NOT NULL,
    total_calories INT DEFAULT 0,
    total_protein DECIMAL(5,2) DEFAULT 0,
    total_carbs DECIMAL(5,2) DEFAULT 0,
    total_fat DECIMAL(5,2) DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des logs alimentaires
CREATE TABLE food_logs (
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
    custom_calories DECIMAL(10,2) DEFAULT NULL,
    custom_protein DECIMAL(10,2) DEFAULT NULL,
    custom_carbs DECIMAL(10,2) DEFAULT NULL,
    custom_fat DECIMAL(10,2) DEFAULT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(id) ON DELETE SET NULL
);

-- Table des exercices
CREATE TABLE exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    calories_per_hour INT NOT NULL,
    category ENUM('cardio', 'musculation', 'flexibilité', 'sport', 'autre') NOT NULL,
    user_id INT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des logs d'exercices
CREATE TABLE exercise_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exercise_id INT NOT NULL,
    duration INT NOT NULL,
    calories_burned INT NOT NULL,
    log_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
);

-- Table des logs de poids
CREATE TABLE weight_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    log_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des logs d'IMC
CREATE TABLE bmi_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    height INT NOT NULL,
    bmi DECIMAL(5,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    log_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des objectifs
CREATE TABLE goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_weight DECIMAL(5,2) NOT NULL,
    target_date DATE,
    status ENUM('en_cours', 'atteint', 'abandonné') DEFAULT 'en_cours',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des repas prédéfinis
CREATE TABLE predefined_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    total_calories INT DEFAULT 0,
    total_protein DECIMAL(5,2) DEFAULT 0,
    total_carbs DECIMAL(5,2) DEFAULT 0,
    total_fat DECIMAL(5,2) DEFAULT 0,
    is_public BOOLEAN DEFAULT FALSE,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des aliments dans les repas prédéfinis
CREATE TABLE predefined_meal_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    predefined_meal_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity DECIMAL(5,2) DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (predefined_meal_id) REFERENCES predefined_meals(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- Table des repas favoris des utilisateurs
CREATE TABLE user_favorite_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    predefined_meal_id INT NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (predefined_meal_id) REFERENCES predefined_meals(id) ON DELETE CASCADE
);

-- Table des préférences alimentaires des utilisateurs
CREATE TABLE user_food_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NULL,
    food_name VARCHAR(100) NULL,
    preference_type ENUM('liked', 'disliked', 'allergic', 'intolerant') NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE SET NULL
);

-- Table des plans de repas
CREATE TABLE meal_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nutrition_program_id INT NULL,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (nutrition_program_id) REFERENCES nutrition_programs(id) ON DELETE SET NULL
);

-- Table des jours de plan de repas
CREATE TABLE meal_plan_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_plan_id INT NOT NULL,
    day_date DATE NOT NULL,
    total_calories INT DEFAULT 0,
    total_protein DECIMAL(5,2) DEFAULT 0,
    total_carbs DECIMAL(5,2) DEFAULT 0,
    total_fat DECIMAL(5,2) DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_plan_id) REFERENCES meal_plans(id) ON DELETE CASCADE
);

-- Table des repas dans les jours de plan
CREATE TABLE meal_plan_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_plan_day_id INT NOT NULL,
    meal_type ENUM('petit_dejeuner', 'dejeuner', 'diner', 'collation', 'autre') NOT NULL,
    meal_name VARCHAR(100),
    total_calories INT DEFAULT 0,
    total_protein DECIMAL(5,2) DEFAULT 0,
    total_carbs DECIMAL(5,2) DEFAULT 0,
    total_fat DECIMAL(5,2) DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_plan_day_id) REFERENCES meal_plan_days(id) ON DELETE CASCADE
);

-- Table des aliments dans les repas du plan
CREATE TABLE meal_plan_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_plan_meal_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity DECIMAL(5,2) DEFAULT 1,
    calories INT NOT NULL,
    protein DECIMAL(5,2) DEFAULT 0,
    carbs DECIMAL(5,2) DEFAULT 0,
    fat DECIMAL(5,2) DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_plan_meal_id) REFERENCES meal_plan_meals(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- Table des besoins caloriques des utilisateurs
CREATE TABLE user_calorie_needs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bmr DECIMAL(8,2) NOT NULL,
    tdee DECIMAL(8,2) NOT NULL,
    protein_target DECIMAL(8,2) NOT NULL,
    carbs_target DECIMAL(8,2) NOT NULL,
    fat_target DECIMAL(8,2) NOT NULL,
    calculation_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table de l'historique du bilan calorique
CREATE TABLE calorie_balance_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    log_date DATE NOT NULL,
    calories_consumed INT NOT NULL DEFAULT 0,
    calories_burned INT NOT NULL DEFAULT 0,
    calorie_balance INT NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des suggestions IA
CREATE TABLE ai_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    suggestion_type ENUM('alimentation', 'exercice', 'motivation', 'autre') NOT NULL,
    content TEXT NOT NULL,
    notes TEXT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_implemented BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des paramètres de l'application
CREATE TABLE app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des programmes
CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- Insertion des données par défaut
INSERT INTO roles (name, description) VALUES 
('admin', 'Administrateur avec accès complet'),
('user', 'Utilisateur standard');

INSERT INTO app_settings (setting_key, setting_value) VALUES 
('chatgpt_api_key', ''),
('site_name', 'Weight Tracker'),
('site_description', 'Application de suivi de poids et de nutrition'),
('maintenance_mode', '0');

INSERT INTO nutrition_programs (name, description, calorie_adjustment, protein_ratio, carbs_ratio, fat_ratio) VALUES 
('Perte de poids', 'Programme pour perdre du poids de manière saine et durable', -500, 35, 35, 30),
('Maintien du poids', 'Programme pour maintenir son poids actuel', 0, 30, 40, 30),
('Prise de masse', 'Programme pour prendre du poids et de la masse musculaire', 500, 30, 45, 25),
('Cétogène', 'Programme à faible teneur en glucides et riche en graisses', -300, 25, 5, 70),
('Végétarien', 'Programme équilibré sans viande', 0, 25, 50, 25);

INSERT INTO programs (name, description) VALUES
('Programme Standard', 'Programme de perte de poids standard avec un déficit calorique modéré'),
('Programme Intensif', 'Programme de perte de poids intensif avec un déficit calorique important'),
('Programme Maintien', 'Programme de maintien du poids avec un équilibre calorique');

-- Création d'un compte administrateur par défaut
INSERT INTO users (username, email, password, first_name, last_name, role_id, created_at) 
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 1, NOW());

-- Création d'un profil pour l'administrateur
INSERT INTO user_profiles (user_id, gender, birth_date, height, activity_level) 
VALUES (1, 'homme', '1990-01-01', 175, 'modere');

-- Ajout des index pour améliorer les performances
CREATE INDEX idx_weight_logs_user_date ON weight_logs(user_id, log_date);
CREATE INDEX idx_food_logs_user_date ON food_logs(user_id, log_date);
CREATE INDEX idx_exercise_logs_user_date ON exercise_logs(user_id, log_date);
CREATE INDEX idx_meals_user_date ON meals(user_id, log_date);
CREATE INDEX idx_food_logs_meal ON food_logs(meal_id);
CREATE INDEX idx_bmi_history_user_date ON bmi_history(user_id, log_date);
CREATE INDEX idx_calorie_balance_user_date ON calorie_balance_history(user_id, log_date);
CREATE INDEX idx_meal_plans_user_dates ON meal_plans(user_id, start_date, end_date);
CREATE INDEX idx_meal_plan_days_dates ON meal_plan_days(meal_plan_id, day_date);
CREATE INDEX idx_foods_name ON foods(name);
CREATE INDEX idx_foods_is_public ON foods(is_public);
CREATE INDEX idx_foods_created_by_admin ON foods(created_by_admin);
CREATE INDEX idx_predefined_meals_name ON predefined_meals(name);
CREATE INDEX idx_predefined_meals_is_public ON predefined_meals(is_public);
CREATE INDEX idx_exercises_name ON exercises(name);
CREATE INDEX idx_exercises_category ON exercises(category);
CREATE INDEX idx_exercises_is_public ON exercises(is_public); 