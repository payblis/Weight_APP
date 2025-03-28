-- Correction des tables manquantes ou incomplètes

-- Compléter la table nutrition_programs
CREATE TABLE IF NOT EXISTS nutrition_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    daily_calories INT NOT NULL,
    protein_percentage DECIMAL(5,2) NOT NULL,
    carbs_percentage DECIMAL(5,2) NOT NULL,
    fat_percentage DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Compléter la table user_favorite_meals
ALTER TABLE user_favorite_meals
ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
ADD FOREIGN KEY (predefined_meal_id) REFERENCES predefined_meals(id) ON DELETE CASCADE;

-- Recréer la table user_profiles avec les bonnes contraintes
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS user_profiles;

CREATE TABLE user_profiles (
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (nutrition_program_id) REFERENCES nutrition_programs(id) ON DELETE SET NULL
);

SET FOREIGN_KEY_CHECKS = 1;

-- Ajouter les valeurs par défaut manquantes
ALTER TABLE user_profiles
ALTER activity_level SET DEFAULT 'modere',
ALTER preferred_bmr_formula SET DEFAULT 'mifflin_st_jeor';

ALTER TABLE foods
ALTER protein SET DEFAULT 0,
ALTER carbs SET DEFAULT 0,
ALTER fat SET DEFAULT 0,
ALTER fiber SET DEFAULT 0,
ALTER serving_size SET DEFAULT 'portion',
ALTER is_public SET DEFAULT FALSE;

ALTER TABLE meals
ALTER total_calories SET DEFAULT 0,
ALTER total_protein SET DEFAULT 0,
ALTER total_carbs SET DEFAULT 0,
ALTER total_fat SET DEFAULT 0;

-- Supprimer les contraintes existantes de food_logs si elles existent
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS food_logs;

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
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(id) ON DELETE SET NULL
);

-- Ajouter la colonne notes à la table meals si elle n'existe pas
ALTER TABLE meals
ADD COLUMN IF NOT EXISTS notes TEXT AFTER total_fat;

-- Ajouter la colonne notes à la table weight_logs si elle n'existe pas
ALTER TABLE weight_logs
ADD COLUMN IF NOT EXISTS notes TEXT AFTER log_date;

-- Ajouter la colonne notes à la table exercise_logs si elle n'existe pas
ALTER TABLE exercise_logs
ADD COLUMN IF NOT EXISTS notes TEXT AFTER calories_burned;

SET FOREIGN_KEY_CHECKS = 1;

-- Insérer les données par défaut manquantes
INSERT INTO roles (id, name, description) VALUES 
(1, 'admin', 'Administrateur avec accès complet'),
(2, 'user', 'Utilisateur standard')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO app_settings (setting_key, setting_value) VALUES 
('chatgpt_api_key', ''),
('site_name', 'Weight Tracker'),
('site_description', 'Application de suivi de poids et de nutrition'),
('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Ajouter des index pour améliorer les performances
ALTER TABLE weight_logs ADD INDEX idx_user_date (user_id, log_date);
ALTER TABLE food_logs ADD INDEX idx_user_date (user_id, log_date);
ALTER TABLE exercise_logs ADD INDEX idx_user_date (user_id, log_date);
ALTER TABLE calorie_balance_history ADD INDEX idx_user_date (user_id, log_date);

-- Vérifier et corriger les données invalides
UPDATE user_profiles 
SET activity_level = 'modere' 
WHERE activity_level NOT IN ('sedentaire', 'leger', 'modere', 'actif', 'tres_actif');

UPDATE meals 
SET meal_type = 'autre' 
WHERE meal_type NOT IN ('petit_dejeuner', 'dejeuner', 'diner', 'collation', 'autre');

UPDATE exercises 
SET category = 'autre' 
WHERE category NOT IN ('cardio', 'musculation', 'flexibilité', 'sport', 'autre');

UPDATE ai_suggestions 
SET suggestion_type = 'autre' 
WHERE suggestion_type NOT IN ('alimentation', 'exercice', 'motivation', 'autre');

-- Nettoyer les données orphelines
DELETE FROM food_logs WHERE food_id NOT IN (SELECT id FROM foods);
DELETE FROM food_logs WHERE meal_id NOT IN (SELECT id FROM meals);
DELETE FROM exercise_logs WHERE exercise_id NOT IN (SELECT id FROM exercises);
DELETE FROM user_profiles WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM weight_logs WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM bmi_logs WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM goals WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM ai_suggestions WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM user_calorie_needs WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM calorie_balance_history WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM predefined_meals WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM user_favorite_meals WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM user_favorite_meals WHERE predefined_meal_id NOT IN (SELECT id FROM predefined_meals); 