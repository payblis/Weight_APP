-- Table des préférences de notifications des repas
CREATE TABLE IF NOT EXISTS meal_notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meal_type ENUM('petit_dejeuner', 'dejeuner', 'diner') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insérer les préférences par défaut pour les repas
INSERT INTO meal_notification_preferences (user_id, meal_type, start_time, end_time) 
SELECT id, 'petit_dejeuner', '06:00:00', '09:00:00' FROM users;
INSERT INTO meal_notification_preferences (user_id, meal_type, start_time, end_time) 
SELECT id, 'dejeuner', '12:00:00', '14:00:00' FROM users;
INSERT INTO meal_notification_preferences (user_id, meal_type, start_time, end_time) 
SELECT id, 'diner', '19:00:00', '21:00:00' FROM users; 