-- Schéma de la base de données pour l'application de suivi de perte de poids

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des profils utilisateurs
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gender ENUM('homme', 'femme', 'autre') NOT NULL,
    age INT NOT NULL,
    height FLOAT NOT NULL,
    initial_weight FLOAT NOT NULL,
    target_weight FLOAT NOT NULL,
    activity_level ENUM('sédentaire', 'légèrement actif', 'modérément actif', 'très actif', 'extrêmement actif') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des enregistrements de poids
CREATE TABLE IF NOT EXISTS weight_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    weight FLOAT NOT NULL,
    log_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des activités physiques
CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    calories_per_hour FLOAT NOT NULL,
    category VARCHAR(50) NOT NULL
);

-- Table des enregistrements d'activités physiques
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_id INT NOT NULL,
    duration_minutes INT NOT NULL,
    log_date DATE NOT NULL,
    calories_burned FLOAT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
);

-- Table des catégories de repas
CREATE TABLE IF NOT EXISTS meal_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Table des repas
CREATE TABLE IF NOT EXISTS meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    calories FLOAT NOT NULL,
    protein FLOAT,
    carbs FLOAT,
    fat FLOAT,
    category_id INT NOT NULL,
    recipe TEXT,
    image_url VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES meal_categories(id) ON DELETE CASCADE
);

-- Table des enregistrements de repas
CREATE TABLE IF NOT EXISTS meal_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meal_id INT,
    meal_name VARCHAR(100),
    calories FLOAT NOT NULL,
    log_date DATE NOT NULL,
    meal_time ENUM('petit-déjeuner', 'déjeuner', 'dîner', 'collation') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(id) ON DELETE SET NULL
);

-- Table des programmes personnalisés
CREATE TABLE IF NOT EXISTS custom_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    daily_calorie_target FLOAT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des recommandations de repas pour les programmes personnalisés
CREATE TABLE IF NOT EXISTS program_meal_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    meal_id INT NOT NULL,
    day_of_week ENUM('lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche') NOT NULL,
    meal_time ENUM('petit-déjeuner', 'déjeuner', 'dîner', 'collation') NOT NULL,
    FOREIGN KEY (program_id) REFERENCES custom_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(id) ON DELETE CASCADE
);

-- Table des recommandations d'activités pour les programmes personnalisés
CREATE TABLE IF NOT EXISTS program_activity_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    activity_id INT NOT NULL,
    day_of_week ENUM('lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche') NOT NULL,
    duration_minutes INT NOT NULL,
    FOREIGN KEY (program_id) REFERENCES custom_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
);

-- Table pour la configuration de l'API ChatGPT
CREATE TABLE IF NOT EXISTS api_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table pour les analyses morphologiques
CREATE TABLE IF NOT EXISTS morphological_analyses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    analysis_result TEXT,
    analysis_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table pour les recommandations ciblées basées sur l'analyse morphologique
CREATE TABLE IF NOT EXISTS targeted_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analysis_id INT NOT NULL,
    body_area VARCHAR(50) NOT NULL,
    exercise_recommendations TEXT,
    diet_recommendations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES morphological_analyses(id) ON DELETE CASCADE
);

-- Données initiales pour les catégories de repas
INSERT INTO meal_categories (name, description) VALUES
('Petit-déjeuner', 'Repas du matin pour bien commencer la journée'),
('Déjeuner', 'Repas du midi pour maintenir votre énergie'),
('Dîner', 'Repas du soir, léger et équilibré'),
('Collation', 'Petites collations saines entre les repas');

-- Données initiales pour les activités physiques
INSERT INTO activities (name, description, calories_per_hour, category) VALUES
('Marche rapide', 'Marche à un rythme soutenu', 300, 'Cardio'),
('Course à pied', 'Jogging à rythme modéré', 600, 'Cardio'),
('Natation', 'Nage à rythme modéré', 500, 'Cardio'),
('Vélo', 'Cyclisme à rythme modéré', 450, 'Cardio'),
('Yoga', 'Exercices de yoga pour la flexibilité', 250, 'Flexibilité'),
('Pilates', 'Exercices de renforcement du core', 300, 'Force'),
('Musculation', 'Exercices avec poids pour renforcer les muscles', 400, 'Force'),
('HIIT', 'Entraînement par intervalles à haute intensité', 700, 'Cardio'),
('Danse', 'Activité physique rythmée', 400, 'Cardio'),
('Elliptique', 'Entraînement sur machine elliptique', 450, 'Cardio');

-- Données initiales pour les repas
INSERT INTO meals (name, description, calories, protein, carbs, fat, category_id, recipe) VALUES
('Bol de flocons d\'avoine aux fruits', 'Flocons d\'avoine avec des fruits frais et du miel', 350, 10, 60, 5, 1, 'Mélanger 50g de flocons d\'avoine avec 250ml de lait, ajouter des fruits frais et une cuillère de miel.'),
('Œufs brouillés et toast complet', 'Œufs brouillés avec du pain complet grillé', 300, 15, 30, 10, 1, 'Battre 2 œufs, les cuire à feu doux. Servir avec une tranche de pain complet grillé.'),
('Smoothie protéiné', 'Smoothie aux fruits et protéines', 250, 20, 30, 5, 1, 'Mixer 1 banane, 100g de baies, 250ml de lait et 1 mesure de protéine en poudre.'),
('Salade de quinoa et légumes', 'Salade de quinoa avec légumes frais et vinaigrette légère', 400, 12, 60, 10, 2, 'Cuire 100g de quinoa, ajouter des légumes coupés (concombre, tomates, poivrons) et assaisonner avec une vinaigrette légère.'),
('Wrap au poulet grillé', 'Wrap de blé entier avec poulet grillé et légumes', 450, 30, 40, 15, 2, 'Griller 100g de poulet, placer dans une tortilla de blé entier avec des légumes et une sauce légère.'),
('Soupe de lentilles', 'Soupe nutritive aux lentilles et légumes', 300, 15, 45, 5, 2, 'Cuire 100g de lentilles avec des légumes (carottes, oignons, céleri) dans un bouillon de légumes.'),
('Saumon grillé et légumes vapeur', 'Filet de saumon grillé avec légumes cuits à la vapeur', 400, 30, 20, 20, 3, 'Griller un filet de saumon de 150g, servir avec des légumes cuits à la vapeur.'),
('Poulet rôti et patate douce', 'Poulet rôti avec patate douce et légumes verts', 450, 35, 40, 10, 3, 'Rôtir 150g de poulet, cuire une patate douce au four et servir avec des légumes verts.'),
('Tofu sauté aux légumes', 'Tofu sauté avec légumes et sauce soja légère', 350, 20, 30, 15, 3, 'Faire sauter 150g de tofu avec des légumes variés et assaisonner avec une sauce soja légère.'),
('Yaourt grec et fruits', 'Yaourt grec avec fruits frais et noix', 200, 15, 20, 5, 4, 'Mélanger 150g de yaourt grec avec des fruits frais et une petite poignée de noix.'),
('Pomme et beurre d\'amande', 'Pomme fraîche avec beurre d\'amande', 150, 5, 20, 8, 4, 'Couper une pomme en tranches et servir avec 1 cuillère à café de beurre d\'amande.'),
('Barre de céréales maison', 'Barre de céréales faite maison avec avoine et miel', 180, 5, 30, 5, 4, 'Mélanger 100g de flocons d\'avoine, 2 cuillères à soupe de miel, 1 cuillère à soupe d\'huile de coco, cuire au four à 180°C pendant 20 minutes.');
