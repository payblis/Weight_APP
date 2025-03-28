# Application Weight Tracker - Version 2.0.0

## Description
Weight Tracker est une application complète de suivi de poids et de nutrition qui vous aide à atteindre vos objectifs de santé. Cette version inclut de nombreuses fonctionnalités avancées comme le calcul du métabolisme de base, la gestion des repas, le suivi des progrès, et une interface d'administration complète.

## Nouvelles fonctionnalités (v2.0.0)
- Interface administrateur complète
- Gestion des utilisateurs et de leurs rôles
- Création et gestion des programmes nutritionnels
- Configuration centralisée de la clé API ChatGPT
- Statistiques et rapports sur l'activité des utilisateurs
- Calcul du métabolisme de base (BMR)
- Système de gestion des repas amélioré
- Historiques et suivis avancés
- Personnalisation avancée

## Installation

### Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache, Nginx, etc.)

### Étapes d'installation
1. Décompressez l'archive dans le répertoire de votre serveur web
2. Créez une base de données MySQL nommée `weight_tracker`
3. Importez le fichier `database.sql` dans votre base de données
4. Configurez les paramètres de connexion à la base de données dans `config/database.php`
5. Accédez à l'application via votre navigateur

## Compte administrateur par défaut
- Nom d'utilisateur : `admin`
- Mot de passe : `admin123`

**Important** : Changez le mot de passe administrateur après la première connexion !

## Fonctionnalités principales

### Pour les utilisateurs
- Suivi du poids et calcul de l'IMC
- Journal alimentaire avec calcul des calories et macronutriments
- Suivi des exercices et des calories brûlées
- Définition d'objectifs de poids
- Suggestions personnalisées basées sur l'IA
- Visualisation des progrès sur le tableau de bord
- Programmes nutritionnels personnalisés

### Pour les administrateurs
- Gestion complète des utilisateurs
- Configuration de la clé API ChatGPT
- Création et gestion des programmes nutritionnels
- Statistiques et rapports sur l'activité des utilisateurs

## Configuration de l'API ChatGPT
Pour utiliser les fonctionnalités d'IA, vous devez configurer une clé API ChatGPT :
1. Connectez-vous avec le compte administrateur
2. Accédez à la page d'administration
3. Dans la section "Paramètres", entrez votre clé API
4. Cliquez sur "Enregistrer"

## Support
Pour toute question ou problème, veuillez contacter l'administrateur système.

## Licence
Cette application est fournie à titre d'exemple et ne peut être redistribuée sans autorisation.
