-- Ajouter la colonne last_notification_reset à la table users si elle n'existe pas
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_notification_reset DATE DEFAULT NULL; 