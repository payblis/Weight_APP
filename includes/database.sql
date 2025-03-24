-- Création de la base de données
CREATE DATABASE IF NOT EXISTS weight_app;
USE weight_app;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    height FLOAT,
    current_weight FLOAT,
    target_weight FLOAT,
    target_weeks INT,
    activity_level ENUM('sedentary', 'light', 'moderate', 'very_active'),
    age INT,
    gender ENUM('M', 'F', 'other'),
    bmr FLOAT,
    daily_calories_target INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des objectifs de poids
CREATE TABLE weight_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    start_weight FLOAT NOT NULL,
    target_weight FLOAT NOT NULL,
    start_date DATE NOT NULL,
    target_date DATE NOT NULL,
    weekly_goal FLOAT NOT NULL,
    status ENUM('active', 'completed', 'failed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table du suivi quotidien
CREATE TABLE daily_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    date DATE NOT NULL,
    weight FLOAT,
    calories_consumed INT,
    calories_burned INT,
    exercise_minutes INT,
    water_intake FLOAT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des aliments
CREATE TABLE foods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    calories INT NOT NULL,
    protein FLOAT,
    carbs FLOAT,
    fats FLOAT,
    serving_size VARCHAR(50),
    serving_unit VARCHAR(20),
    is_custom BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des exercices
CREATE TABLE exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    calories_per_hour INT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced'),
    category VARCHAR(50),
    is_custom BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des suggestions IA
CREATE TABLE ai_suggestions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    type ENUM('meal', 'exercise', 'motivation') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
); 