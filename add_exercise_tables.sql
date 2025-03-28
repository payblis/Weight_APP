-- Désactiver temporairement les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Création de la table exercise_categories
CREATE TABLE IF NOT EXISTS exercise_categories (
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

-- Ajout de la colonne intensity à la table exercise_logs
ALTER TABLE exercise_logs 
ADD COLUMN intensity ENUM('faible', 'modérée', 'intense') DEFAULT 'modérée' AFTER duration;

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1; 