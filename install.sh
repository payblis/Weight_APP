#!/bin/bash

# Script d'installation pour Weight Tracker App
echo "=== Installation de Weight Tracker App ==="
echo ""

# Vérification des prérequis
echo "Vérification des prérequis..."
command -v php >/dev/null 2>&1 || { echo "PHP est requis mais n'est pas installé. Aborting."; exit 1; }
command -v mysql >/dev/null 2>&1 || { echo "MySQL est requis mais n'est pas installé. Aborting."; exit 1; }

# Vérification de la version de PHP
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "Version PHP détectée: $PHP_VERSION"

# Vérification de l'extension MySQLi
PHP_MYSQLI=$(php -r "echo extension_loaded('mysqli') ? 'Oui' : 'Non';")
echo "Extension MySQLi chargée: $PHP_MYSQLI"
if [ "$PHP_MYSQLI" = "Non" ]; then
    echo "L'extension MySQLi est requise. Veuillez l'activer dans votre configuration PHP."
    exit 1
fi

echo ""
echo "Tous les prérequis sont satisfaits."
echo ""

# Configuration de la base de données
echo "=== Configuration de la base de données ==="
echo "Veuillez entrer les informations de connexion à votre base de données MySQL:"
read -p "Hôte MySQL (généralement localhost): " DB_HOST
read -p "Nom d'utilisateur MySQL: " DB_USER
read -p "Mot de passe MySQL: " DB_PASS
read -p "Nom de la base de données (sera créée si elle n'existe pas): " DB_NAME

# Création de la base de données
echo ""
echo "Création de la base de données $DB_NAME si elle n'existe pas..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;"

if [ $? -ne 0 ]; then
    echo "Erreur lors de la création de la base de données. Veuillez vérifier vos informations de connexion."
    exit 1
fi

# Importation du schéma de base de données
echo "Importation du schéma de base de données..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database.sql

if [ $? -ne 0 ]; then
    echo "Erreur lors de l'importation du schéma de base de données."
    exit 1
fi

# Mise à jour du fichier de configuration
echo "Mise à jour du fichier de configuration..."
CONFIG_FILE="config/database.php"

# Sauvegarde du fichier original
cp "$CONFIG_FILE" "${CONFIG_FILE}.bak"

# Mise à jour des paramètres de connexion
sed -i "s/define('DB_HOST', 'localhost');/define('DB_HOST', '$DB_HOST');/" "$CONFIG_FILE"
sed -i "s/define('DB_USER', 'root');/define('DB_USER', '$DB_USER');/" "$CONFIG_FILE"
sed -i "s/define('DB_PASS', '');/define('DB_PASS', '$DB_PASS');/" "$CONFIG_FILE"
sed -i "s/define('DB_NAME', 'weight_tracker_app');/define('DB_NAME', '$DB_NAME');/" "$CONFIG_FILE"

# Création du dossier reports s'il n'existe pas
echo "Création du dossier reports..."
mkdir -p reports
chmod 777 reports

echo ""
echo "=== Installation terminée ==="
echo ""
echo "Votre application Weight Tracker est maintenant installée."
echo "Pour y accéder, ouvrez votre navigateur et accédez à l'URL où vous avez installé l'application."
echo ""
echo "Merci d'avoir choisi Weight Tracker App!"
