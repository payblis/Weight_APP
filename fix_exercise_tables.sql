-- Désactiver temporairement les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Supprimer les tables existantes si elles existent
DROP TABLE IF EXISTS exercise_logs;
DROP TABLE IF EXISTS exercises;
DROP TABLE IF EXISTS exercise_categories;

-- Création de la table exercise_categories
CREATE TABLE exercise_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion des catégories d'exercices par défaut
INSERT INTO exercise_categories (name, description) VALUES 
('Cardio', 'Exercices d''endurance et de cardio-vasculaire'),
('Musculation', 'Exercices de renforcement musculaire'),
('Flexibilité', 'Exercices d''étirement et de souplesse'),
('Sport', 'Activités sportives'),
('Autre', 'Autres types d''exercices');

-- Création de la table exercises
CREATE TABLE exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    calories_per_hour INT NOT NULL,
    category_id INT NOT NULL,
    user_id INT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES exercise_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Création de la table exercise_logs
CREATE TABLE exercise_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exercise_id INT NULL,
    duration INT NOT NULL,
    intensity ENUM('faible', 'modérée', 'intense') DEFAULT 'modérée',
    calories_burned INT NOT NULL,
    log_date DATE NOT NULL,
    notes TEXT,
    custom_exercise_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE SET NULL
);

-- Ajout d'exercices par défaut
INSERT INTO exercises (name, calories_per_hour, category_id, is_public) VALUES 
-- Cardio
('Course à pied', 600, 1, 1),
('Vélo', 500, 1, 1),
('Natation', 550, 1, 1),
('Corde à sauter', 700, 1, 1),
('Marche rapide', 400, 1, 1),

-- Musculation
('Pompes', 400, 2, 1),
('Squats', 450, 2, 1),
('Fentes', 400, 2, 1),
('Planche', 300, 2, 1),
('Tractions', 500, 2, 1),

-- Flexibilité
('Yoga', 200, 3, 1),
('Étirements', 150, 3, 1),
('Pilates', 250, 3, 1),
('Tai Chi', 200, 3, 1),
('Stretching', 150, 3, 1),

-- Sport
('Football', 600, 4, 1),
('Basketball', 500, 4, 1),
('Tennis', 450, 4, 1),
('Volleyball', 400, 4, 1),
('Rugby', 700, 4, 1);

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1; 