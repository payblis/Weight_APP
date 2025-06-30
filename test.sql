-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : lun. 31 mars 2025 à 17:08
-- Version du serveur : 10.11.11-MariaDB-0+deb12u1
-- Version de PHP : 8.3.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `test`
--

-- --------------------------------------------------------

--
-- Structure de la table `ai_suggestions`
--

CREATE TABLE `ai_suggestions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `suggestion_type` enum('alimentation','exercice','motivation','autre') NOT NULL,
  `content` text NOT NULL,
  `notes` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_implemented` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `app_settings`
--

CREATE TABLE `app_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `app_settings`
--

INSERT INTO `app_settings` (`id`, `setting_key`, `setting_value`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'chatgpt_api_key', '', NULL, '2025-03-28 11:12:42', '2025-03-28 11:12:42'),
(2, 'site_name', 'Weight Tracker', NULL, '2025-03-28 11:12:42', '2025-03-28 11:12:42'),
(3, 'site_description', 'Application de suivi de poids et de nutrition', NULL, '2025-03-28 11:12:42', '2025-03-28 11:12:42'),
(4, 'maintenance_mode', '0', NULL, '2025-03-28 11:12:42', '2025-03-28 11:12:42');

-- --------------------------------------------------------

--
-- Structure de la table `bmi_history`
--

CREATE TABLE `bmi_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `height` int(11) NOT NULL,
  `bmi` decimal(5,2) NOT NULL,
  `category` varchar(50) NOT NULL,
  `log_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `bmi_history`
--

INSERT INTO `bmi_history` (`id`, `user_id`, `weight`, `height`, `bmi`, `category`, `log_date`, `notes`, `created_at`) VALUES
(1, 2, 125.00, 175, 40.82, '', '2025-03-28', NULL, '2025-03-28 11:14:00'),
(2, 7, 125.00, 175, 40.82, '', '2025-03-28', NULL, '2025-03-28 13:46:12'),
(3, 2, 121.00, 175, 39.51, '', '2025-03-29', NULL, '2025-03-29 05:50:56'),
(4, 7, 125.00, 175, 40.82, '', '2025-03-29', NULL, '2025-03-29 09:13:15'),
(5, 8, 57.00, 154, 24.03, '', '2025-03-29', NULL, '2025-03-29 09:32:00'),
(6, 7, 124.00, 175, 40.49, '', '2025-03-31', NULL, '2025-03-31 05:57:41');

-- --------------------------------------------------------

--
-- Structure de la table `calorie_balance_history`
--

CREATE TABLE `calorie_balance_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `calories_consumed` int(11) NOT NULL DEFAULT 0,
  `calories_burned` int(11) NOT NULL DEFAULT 0,
  `calorie_balance` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `community_groups`
--

CREATE TABLE `community_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `community_groups`
--

INSERT INTO `community_groups` (`id`, `name`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'test', 'bdfbgf', 7, '2025-03-29 12:49:41', '2025-03-29 12:49:41'),
(2, '2', 'fezez', 7, '2025-03-29 12:52:46', '2025-03-29 12:52:46'),
(3, 'Famille', 'Le groupe de la famille', 7, '2025-03-29 12:55:16', '2025-03-29 12:55:16');

-- --------------------------------------------------------

--
-- Structure de la table `community_posts`
--

CREATE TABLE `community_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_type` enum('meal','exercise','program','goal','message') NOT NULL,
  `content` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `likes_count` int(11) DEFAULT 0,
  `comments_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `visibility` enum('public','group') NOT NULL DEFAULT 'public',
  `group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `community_posts`
--

INSERT INTO `community_posts` (`id`, `user_id`, `post_type`, `content`, `reference_id`, `reference_type`, `likes_count`, `comments_count`, `created_at`, `updated_at`, `visibility`, `group_id`) VALUES
(4, 7, 'message', ' cx xc c', NULL, NULL, 0, 0, '2025-03-29 12:49:28', '2025-03-29 12:49:28', 'public', NULL),
(5, 7, 'message', ' ccb vc ', NULL, NULL, 0, 0, '2025-03-29 12:53:22', '2025-03-29 12:53:22', 'group', 2),
(6, 7, 'meal', '', 14, NULL, 0, 0, '2025-03-29 13:07:35', '2025-03-29 13:07:35', 'public', NULL),
(7, 7, 'message', 'gfbbfg', NULL, NULL, 0, 0, '2025-03-29 13:08:17', '2025-03-29 13:08:17', 'public', NULL),
(8, 7, 'program', 'Je viens de rejoindre le programme \"gregreg\"\r\nType: complet\r\nAjustement calorique: 0%\r\nDescription: efzerfzefezfezf', 4, NULL, 0, 0, '2025-03-29 13:09:24', '2025-03-29 13:09:24', 'public', NULL),
(9, 7, 'message', 'test group message', NULL, NULL, 0, 0, '2025-03-29 13:10:08', '2025-03-29 13:10:08', 'group', 3),
(10, 7, 'meal', '', 15, NULL, 0, 0, '2025-03-29 13:10:59', '2025-03-29 13:10:59', 'public', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `exercises`
--

CREATE TABLE `exercises` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `calories_per_hour` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `exercises`
--

INSERT INTO `exercises` (`id`, `name`, `calories_per_hour`, `category_id`, `user_id`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'Course à pied', 600, 1, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(2, 'Vélo', 500, 1, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(3, 'Natation', 550, 1, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(4, 'Corde à sauter', 700, 1, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(5, 'Marche rapide', 400, 1, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(6, 'Pompes', 400, 2, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(7, 'Squats', 450, 2, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(8, 'Fentes', 400, 2, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(9, 'Planche', 300, 2, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(10, 'Tractions', 500, 2, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(11, 'Yoga', 200, 3, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(12, 'Étirements', 150, 3, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(13, 'Pilates', 250, 3, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(14, 'Tai Chi', 200, 3, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(15, 'Stretching', 150, 3, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(16, 'Football', 600, 4, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(17, 'Basketball', 500, 4, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(18, 'Tennis', 450, 4, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(19, 'Volleyball', 400, 4, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(20, 'Rugby', 700, 4, NULL, 1, '2025-03-28 11:46:36', '2025-03-28 11:46:36');

-- --------------------------------------------------------

--
-- Structure de la table `exercise_categories`
--

CREATE TABLE `exercise_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `exercise_categories`
--

INSERT INTO `exercise_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Cardio', 'Exercices d\'endurance et de cardio-vasculaire', '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(2, 'Musculation', 'Exercices de renforcement musculaire', '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(3, 'Flexibilité', 'Exercices d\'étirement et de souplesse', '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(4, 'Sport', 'Activités sportives', '2025-03-28 11:46:36', '2025-03-28 11:46:36'),
(5, 'Autre', 'Autres types d\'exercices', '2025-03-28 11:46:36', '2025-03-28 11:46:36');

-- --------------------------------------------------------

--
-- Structure de la table `exercise_logs`
--

CREATE TABLE `exercise_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exercise_id` int(11) DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `intensity` enum('faible','modérée','intense') DEFAULT 'modérée',
  `calories_burned` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `custom_exercise_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `exercise_logs`
--

INSERT INTO `exercise_logs` (`id`, `user_id`, `exercise_id`, `duration`, `intensity`, `calories_burned`, `log_date`, `notes`, `custom_exercise_name`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 45, 'modérée', 450, '2025-03-28', '', '', '2025-03-28 11:46:53', '2025-03-28 11:46:53'),
(2, 2, 12, 20, '', 60, '2025-03-27', '', '', '2025-03-28 11:51:39', '2025-03-28 11:51:39'),
(4, 8, 1, 40, 'modérée', 400, '2025-03-29', '', '', '2025-03-29 09:36:01', '2025-03-29 09:36:01'),
(5, 8, NULL, 30, 'modérée', 150, '2025-03-29', '', 'fezlfnze', '2025-03-29 09:40:33', '2025-03-29 09:40:33');

-- --------------------------------------------------------

--
-- Structure de la table `foods`
--

CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `calories` int(11) NOT NULL DEFAULT 0,
  `protein` decimal(5,2) DEFAULT 0.00,
  `carbs` decimal(5,2) DEFAULT 0.00,
  `fat` decimal(5,2) DEFAULT 0.00,
  `serving_size` varchar(50) DEFAULT 'portion',
  `is_public` tinyint(1) DEFAULT 0,
  `created_by_admin` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `foods`
--

INSERT INTO `foods` (`id`, `name`, `description`, `calories`, `protein`, `carbs`, `fat`, `serving_size`, `is_public`, `created_by_admin`, `notes`, `created_at`, `updated_at`, `category_id`) VALUES
(6, 'Steak de boeuf', 'Ingrédient de Steak de boeuf avec quinoa et légumes', 286, 31.00, 0.00, 19.00, 'portion', 0, 0, NULL, '2025-03-31 12:28:54', '2025-03-31 12:28:54', 2),
(7, 'Quinoa cuit', 'Ingrédient de Steak de boeuf avec quinoa et légumes', 220, 8.00, 39.00, 3.50, 'portion', 0, 0, NULL, '2025-03-31 12:28:54', '2025-03-31 12:28:54', 3),
(8, 'Avocat', 'Ingrédient de Steak de boeuf avec quinoa et légumes', 160, 2.00, 8.50, 14.70, 'portion', 0, 0, NULL, '2025-03-31 12:28:54', '2025-03-31 12:28:54', 1),
(10, 'Laitue', 'Ingrédient de Steak de boeuf avec quinoa et légumes', 5, 0.50, 1.00, 0.10, 'portion', 0, 0, NULL, '2025-03-31 12:28:54', '2025-03-31 12:28:54', 1),
(11, 'Tomate', 'Tomate crue, 100g', 18, 0.90, 3.90, 0.20, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 1),
(12, 'Carotte', 'Carotte crue, 100g', 36, 0.80, 8.20, 0.20, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 1),
(13, 'Courgette', 'Courgette crue, 100g', 17, 1.20, 3.10, 0.30, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 1),
(14, 'Épinards', 'Épinards crus, 100g', 23, 2.90, 1.10, 0.40, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 1),
(15, 'Poivron', 'Poivron rouge, 100g', 31, 1.00, 6.00, 0.30, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 1),
(16, 'Pomme', 'Pomme crue, 100g', 52, 0.30, 14.00, 0.20, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 2),
(17, 'Banane', 'Banane crue, 100g', 89, 1.10, 22.80, 0.30, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 2),
(18, 'Orange', 'Orange crue, 100g', 47, 0.90, 11.80, 0.10, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 2),
(19, 'Fraises', 'Fraises fraîches, 100g', 32, 0.70, 7.70, 0.30, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 2),
(20, 'Œuf', 'Œuf entier cuit, 100g', 143, 13.00, 1.10, 10.00, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 3),
(21, 'Poulet (blanc)', 'Filet de poulet grillé, 100g', 165, 31.00, 0.00, 3.60, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 3),
(22, 'Saumon', 'Saumon cuit, 100g', 206, 22.00, 0.00, 13.00, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 3),
(23, 'Jambon blanc', 'Jambon découenné dégraissé, 100g', 112, 20.00, 0.50, 3.00, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 3),
(24, 'Fromage feta', 'Fromage feta, 100g', 300, 16.00, 2.40, 24.00, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 4),
(25, 'Yaourt nature', 'Yaourt nature sans sucre, 100g', 60, 4.00, 5.00, 2.00, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 4),
(26, 'Lait écrémé', 'Lait écrémé, 100g', 35, 3.40, 5.00, 0.10, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 4),
(27, 'Pain complet', 'Pain complet, 100g', 247, 9.00, 41.00, 3.40, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 5),
(28, 'Pâtes complètes', 'Pâtes complètes cuites, 100g', 124, 5.00, 25.00, 1.00, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 5),
(29, 'Riz basmati', 'Riz basmati cuit, 100g', 121, 3.50, 25.00, 0.40, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 5),
(30, 'Huile d\'olive', 'Huile d\'olive vierge, 100g', 884, 0.00, 0.00, 100.00, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 6),
(31, 'Beurre doux', 'Beurre doux, 100g', 717, 0.50, 0.50, 81.00, '100g', 1, 1, NULL, '2025-03-31 13:01:22', '2025-03-31 13:01:22', 6),
(32, 'Salade de thon et légumes, appertisée', 'Salade de thon et légumes, appertisée', 0, 9.00, 7.00, 4.00, 'portion', 0, 0, NULL, '2025-03-31 13:38:51', '2025-03-31 13:38:51', 7),
(33, 'Salade composée avec viande ou poisson, appertisée', 'Salade composée avec viande ou poisson, appertisée', 0, 8.00, 6.00, 5.00, 'portion', 0, 0, NULL, '2025-03-31 13:39:07', '2025-03-31 13:39:07', 7),
(34, 'Champignons à la grecque, appertisés', 'Champignons à la grecque, appertisés', 0, 2.00, 3.00, 3.00, 'portion', 0, 0, NULL, '2025-03-31 13:39:17', '2025-03-31 13:39:17', 7),
(35, 'Salade de pommes de terre, fait maison', 'Salade de pommes de terre, fait maison', 0, 2.00, 9.00, 8.00, 'portion', 0, 0, NULL, '2025-03-31 13:39:47', '2025-03-31 13:39:47', 7),
(36, 'Poulet grillé', NULL, 247, 46.50, 0.00, 5.20, 'portion', 0, 0, NULL, '2025-03-31 14:24:31', '2025-03-31 14:24:31', NULL),
(37, 'Pommes de terre cuites', NULL, 174, 3.60, 39.60, 0.20, 'portion', 0, 0, NULL, '2025-03-31 14:24:31', '2025-03-31 14:24:31', NULL),
(38, 'Tomates cerise', NULL, 18, 0.90, 3.90, 0.20, 'portion', 0, 0, NULL, '2025-03-31 14:24:31', '2025-03-31 14:24:31', NULL),
(39, 'Concombre', NULL, 15, 0.60, 3.60, 0.10, 'portion', 0, 0, NULL, '2025-03-31 14:24:31', '2025-03-31 14:24:31', NULL),
(40, 'Vinaigre balsamique', NULL, 14, 0.10, 2.70, 0.00, 'portion', 0, 0, NULL, '2025-03-31 14:24:31', '2025-03-31 14:24:31', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `food_categories`
--

CREATE TABLE `food_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `food_categories`
--

INSERT INTO `food_categories` (`id`, `name`, `created_at`) VALUES
(1, 'Fruits et légumes', '2025-03-29 10:18:54'),
(2, 'Viandes et poissons', '2025-03-29 10:18:54'),
(3, 'Céréales et féculents', '2025-03-29 10:18:54'),
(4, 'Produits laitiers', '2025-03-29 10:18:54'),
(5, 'Boissons', '2025-03-29 10:18:54'),
(6, 'Snacks et desserts', '2025-03-29 10:18:54'),
(7, 'Autres', '2025-03-29 10:18:54');

-- --------------------------------------------------------

--
-- Structure de la table `food_logs`
--

CREATE TABLE `food_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `custom_food_name` varchar(100) DEFAULT NULL,
  `meal_id` int(11) DEFAULT NULL,
  `quantity` decimal(5,2) DEFAULT 1.00,
  `log_date` date NOT NULL,
  `calories` int(11) NOT NULL,
  `protein` decimal(5,2) DEFAULT 0.00,
  `carbs` decimal(5,2) DEFAULT 0.00,
  `fat` decimal(5,2) DEFAULT 0.00,
  `custom_calories` decimal(10,2) DEFAULT NULL,
  `custom_protein` decimal(10,2) DEFAULT NULL,
  `custom_carbs` decimal(10,2) DEFAULT NULL,
  `custom_fat` decimal(10,2) DEFAULT NULL,
  `is_part_of_meal` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `food_logs`
--

INSERT INTO `food_logs` (`id`, `user_id`, `food_id`, `custom_food_name`, `meal_id`, `quantity`, `log_date`, `calories`, `protein`, `carbs`, `fat`, `custom_calories`, `custom_protein`, `custom_carbs`, `custom_fat`, `is_part_of_meal`, `notes`, `created_at`, `updated_at`) VALUES
(6, 2, 0, 'greger', 5, 100.00, '2025-03-28', 0, 0.00, 0.00, 0.00, 15.00, 20.00, 110.00, 10.00, 1, NULL, '2025-03-28 11:35:51', '2025-03-28 11:35:51'),
(7, 2, 0, 'hthtrh', 5, 100.00, '2025-03-28', 0, 0.00, 0.00, 0.00, 150.00, 20.00, 10.00, 2.00, 1, NULL, '2025-03-28 11:36:54', '2025-03-28 11:36:54'),
(8, 2, 0, 'boeuf', 6, 100.00, '2025-03-28', 0, 0.00, 0.00, 0.00, 130.00, 20.00, 10.00, 3.00, 1, NULL, '2025-03-28 11:42:51', '2025-03-28 11:42:51'),
(9, 2, 0, 'Fritte', 6, 100.00, '2025-03-28', 0, 0.00, 0.00, 0.00, 300.00, 20.00, 80.00, 4.00, 1, NULL, '2025-03-28 11:43:12', '2025-03-28 11:43:12'),
(10, 2, 0, 'hamburger', 7, 300.00, '2025-03-27', 0, 0.00, 0.00, 0.00, 500.00, 20.00, 150.00, 20.00, 1, NULL, '2025-03-28 11:48:22', '2025-03-28 11:48:22'),
(12, 8, 0, 'boeuf', 9, 100.00, '2025-03-27', 0, 0.00, 0.00, 0.00, 140.00, 30.00, 8.00, 5.00, 1, NULL, '2025-03-29 09:32:58', '2025-03-29 09:32:58'),
(13, 8, 0, 'patte', 9, 100.00, '2025-03-27', 0, 0.00, 0.00, 0.00, 170.00, 15.00, 2.00, 5.00, 1, NULL, '2025-03-29 09:33:14', '2025-03-29 09:33:14'),
(14, 8, 0, 'bricohe', 10, 100.00, '2025-03-29', 0, 0.00, 0.00, 0.00, 150.00, 5.00, 40.00, 16.00, 1, NULL, '2025-03-29 09:34:06', '2025-03-29 09:34:06'),
(15, 8, 0, 't&#039;z&#039;t&#039;&quot;t&quot;&#039;', 11, 200.00, '2025-03-29', 0, 0.00, 0.00, 0.00, 600.00, 28.00, 40.00, 23.00, 1, NULL, '2025-03-29 09:44:48', '2025-03-29 09:44:48'),
(17, 7, 0, 'bffbdbf', 14, 100.00, '2025-03-29', 0, 0.00, 0.00, 0.00, 150.00, 4.00, 2.00, 4.00, 1, NULL, '2025-03-29 13:07:28', '2025-03-29 13:07:28'),
(18, 7, 0, 'tezgesrge', 15, 100.00, '2025-03-29', 0, 0.00, 0.00, 0.00, 232.00, 3.00, 4.00, 2.00, 1, NULL, '2025-03-29 13:10:52', '2025-03-29 13:10:52');

-- --------------------------------------------------------

--
-- Structure de la table `food_preferences`
--

CREATE TABLE `food_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `food_id` int(11) DEFAULT NULL,
  `custom_food` varchar(255) DEFAULT NULL,
  `preference_type` enum('favori','blacklist') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `food_preferences`
--

INSERT INTO `food_preferences` (`id`, `user_id`, `food_id`, `custom_food`, `preference_type`, `created_at`, `updated_at`, `notes`) VALUES
(1, 7, NULL, 'boeuf', 'favori', '2025-03-29 10:23:20', '2025-03-29 10:23:20', ''),
(2, 7, NULL, 'choux de bruxelle', 'blacklist', '2025-03-29 10:23:34', '2025-03-29 10:23:34', '');

-- --------------------------------------------------------

--
-- Structure de la table `goals`
--

CREATE TABLE `goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_weight` decimal(5,2) NOT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('en_cours','atteint','abandonné') DEFAULT 'en_cours',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `goals`
--

INSERT INTO `goals` (`id`, `user_id`, `target_weight`, `target_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(47, 2, 120.00, '2025-05-01', 'en_cours', '', '2025-03-29 05:50:17', '2025-03-29 05:50:17'),
(49, 8, 120.00, '2025-05-01', '', '', '2025-03-29 09:25:44', '2025-03-29 09:29:10'),
(50, 8, 55.00, '2025-05-01', 'en_cours', '', '2025-03-27 09:29:46', '2025-03-27 09:29:46'),
(56, 7, 120.00, '2025-05-01', 'atteint', '', '2025-03-31 05:56:17', '2025-03-31 07:49:04'),
(58, 7, 120.00, '2025-05-01', '', '', '2025-03-31 07:56:42', '2025-03-31 07:58:00'),
(59, 7, 120.00, '2025-05-01', '', '', '2025-03-31 08:04:41', '2025-03-31 08:09:52'),
(60, 7, 120.00, '2025-05-01', 'atteint', '', '2025-03-31 08:10:10', '2025-03-31 08:12:08'),
(61, 7, 120.00, '2025-05-01', 'atteint', '', '2025-03-31 08:16:58', '2025-03-31 08:17:24'),
(62, 7, 120.00, '2025-05-01', 'atteint', '', '2025-03-31 08:21:16', '2025-03-31 08:22:20'),
(63, 7, 117.00, '2025-05-16', 'atteint', '', '2025-03-31 08:22:53', '2025-03-31 08:23:59'),
(64, 7, 120.00, '2025-05-01', '', '', '2025-03-31 08:25:12', '2025-03-31 08:33:18'),
(65, 7, 120.00, '2025-05-01', 'en_cours', '', '2025-03-31 08:33:38', '2025-03-31 08:33:38');

-- --------------------------------------------------------

--
-- Structure de la table `group_invitations`
--

CREATE TABLE `group_invitations` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `invited_user_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `group_invitations`
--

INSERT INTO `group_invitations` (`id`, `group_id`, `invited_by`, `invited_user_id`, `status`, `created_at`, `expires_at`) VALUES
(1, 3, 7, 8, 'pending', '2025-03-29 13:00:39', '2025-04-05 12:00:39');

-- --------------------------------------------------------

--
-- Structure de la table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','member') NOT NULL DEFAULT 'member',
  `joined_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `group_members`
--

INSERT INTO `group_members` (`id`, `group_id`, `user_id`, `role`, `joined_at`) VALUES
(2, 2, 7, 'admin', '2025-03-29 12:52:46'),
(3, 3, 7, 'admin', '2025-03-29 12:55:16');

-- --------------------------------------------------------

--
-- Structure de la table `meals`
--

CREATE TABLE `meals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_type` enum('petit_dejeuner','dejeuner','diner','collation','autre') NOT NULL,
  `log_date` date NOT NULL,
  `total_calories` int(11) DEFAULT 0,
  `total_protein` decimal(5,2) DEFAULT 0.00,
  `total_carbs` decimal(5,2) DEFAULT 0.00,
  `total_fat` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `meals`
--

INSERT INTO `meals` (`id`, `user_id`, `meal_type`, `log_date`, `total_calories`, `total_protein`, `total_carbs`, `total_fat`, `notes`, `created_at`, `updated_at`) VALUES
(5, 2, 'petit_dejeuner', '2025-03-28', 165, 40.00, 120.00, 12.00, '', '2025-03-28 11:35:42', '2025-03-28 11:36:54'),
(6, 2, 'dejeuner', '2025-03-28', 430, 40.00, 90.00, 7.00, '', '2025-03-28 11:42:34', '2025-03-28 11:43:12'),
(7, 2, 'diner', '2025-03-27', 500, 20.00, 150.00, 20.00, '', '2025-03-28 11:48:08', '2025-03-28 11:48:22'),
(9, 8, 'diner', '2025-03-27', 310, 45.00, 10.00, 10.00, '', '2025-03-29 09:32:37', '2025-03-29 09:33:14'),
(10, 8, 'petit_dejeuner', '2025-03-29', 150, 5.00, 40.00, 16.00, '', '2025-03-29 09:33:48', '2025-03-29 09:34:06'),
(11, 8, 'dejeuner', '2025-03-29', 600, 28.00, 40.00, 23.00, '', '2025-03-29 09:44:29', '2025-03-29 09:44:48'),
(14, 7, 'petit_dejeuner', '2025-03-29', 150, 4.00, 2.00, 4.00, '', '2025-03-29 13:07:10', '2025-03-29 13:07:28'),
(15, 7, 'dejeuner', '2025-03-29', 232, 3.00, 4.00, 2.00, '', '2025-03-29 13:10:36', '2025-03-29 13:10:52');

-- --------------------------------------------------------

--
-- Structure de la table `meal_notification_preferences`
--

CREATE TABLE `meal_notification_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_type` enum('petit_dejeuner','dejeuner','diner') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `meal_notification_preferences`
--

INSERT INTO `meal_notification_preferences` (`id`, `user_id`, `meal_type`, `start_time`, `end_time`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'petit_dejeuner', '06:00:00', '09:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(2, 5, 'petit_dejeuner', '06:00:00', '09:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(3, 2, 'petit_dejeuner', '06:00:00', '09:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(4, 7, 'petit_dejeuner', '06:00:00', '09:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(5, 8, 'petit_dejeuner', '06:00:00', '09:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(8, 1, 'dejeuner', '12:00:00', '14:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(9, 5, 'dejeuner', '12:00:00', '14:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(10, 2, 'dejeuner', '12:00:00', '14:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(11, 7, 'dejeuner', '12:00:00', '14:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(12, 8, 'dejeuner', '12:00:00', '14:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(15, 1, 'diner', '19:00:00', '21:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(16, 5, 'diner', '19:00:00', '21:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(17, 2, 'diner', '19:00:00', '21:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(18, 7, 'diner', '19:00:00', '21:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27'),
(19, 8, 'diner', '19:00:00', '21:00:00', 1, '2025-03-29 10:56:27', '2025-03-29 10:56:27');

-- --------------------------------------------------------

--
-- Structure de la table `meal_plans`
--

CREATE TABLE `meal_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `total_calories` int(11) NOT NULL,
  `protein` decimal(5,2) NOT NULL,
  `carbs` decimal(5,2) NOT NULL,
  `fat` decimal(5,2) NOT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `meal_plan_days`
--

CREATE TABLE `meal_plan_days` (
  `id` int(11) NOT NULL,
  `meal_plan_id` int(11) NOT NULL,
  `day_date` date NOT NULL,
  `total_calories` int(11) DEFAULT 0,
  `total_protein` decimal(5,2) DEFAULT 0.00,
  `total_carbs` decimal(5,2) DEFAULT 0.00,
  `total_fat` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `meal_plan_items`
--

CREATE TABLE `meal_plan_items` (
  `id` int(11) NOT NULL,
  `meal_plan_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `quantity` decimal(5,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `meal_type` enum('breakfast','lunch','dinner','snack') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `meal_plan_meals`
--

CREATE TABLE `meal_plan_meals` (
  `id` int(11) NOT NULL,
  `meal_plan_day_id` int(11) NOT NULL,
  `meal_type` enum('petit_dejeuner','dejeuner','diner','collation','autre') NOT NULL,
  `meal_name` varchar(100) DEFAULT NULL,
  `total_calories` int(11) DEFAULT 0,
  `total_protein` decimal(5,2) DEFAULT 0.00,
  `total_carbs` decimal(5,2) DEFAULT 0.00,
  `total_fat` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `nutrition_programs`
--

CREATE TABLE `nutrition_programs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `calorie_adjustment` int(11) NOT NULL DEFAULT 0,
  `protein_ratio` decimal(5,2) NOT NULL DEFAULT 30.00,
  `carbs_ratio` decimal(5,2) NOT NULL DEFAULT 40.00,
  `fat_ratio` decimal(5,2) NOT NULL DEFAULT 30.00,
  `is_public` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `nutrition_programs`
--

INSERT INTO `nutrition_programs` (`id`, `name`, `description`, `calorie_adjustment`, `protein_ratio`, `carbs_ratio`, `fat_ratio`, `is_public`, `created_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Perte de poids', 'Programme pour perdre du poids de manière saine et durable', -500, 35.00, 35.00, 30.00, 1, NULL, NULL, '2025-03-28 11:12:42', '2025-03-28 11:12:42'),
(2, 'Maintien du poids', 'Programme pour maintenir son poids actuel', 0, 30.00, 40.00, 30.00, 1, NULL, NULL, '2025-03-28 11:12:42', '2025-03-28 11:12:42'),
(3, 'Prise de masse', 'Programme pour prendre du poids et de la masse musculaire', 500, 30.00, 45.00, 25.00, 1, NULL, NULL, '2025-03-28 11:12:42', '2025-03-28 11:12:42'),
(4, 'Cétogène', 'Programme à faible teneur en glucides et riche en graisses', -300, 25.00, 5.00, 70.00, 1, NULL, NULL, '2025-03-28 11:12:42', '2025-03-28 11:12:42'),
(5, 'Végétarien', 'Programme équilibré sans viande', 0, 25.00, 50.00, 25.00, 1, NULL, NULL, '2025-03-28 11:12:42', '2025-03-28 11:12:42'),
(6, 'Perte de poids', 'Programme pour perdre du poids de manière saine et durable', -500, 35.00, 35.00, 30.00, 1, NULL, NULL, '2025-03-28 12:00:58', '2025-03-28 12:00:58'),
(7, 'Maintien du poids', 'Programme pour maintenir son poids actuel', 0, 30.00, 40.00, 30.00, 1, NULL, NULL, '2025-03-28 12:00:58', '2025-03-28 12:00:58'),
(8, 'Prise de masse', 'Programme pour prendre du poids et de la masse musculaire', 500, 30.00, 45.00, 25.00, 1, NULL, NULL, '2025-03-28 12:00:58', '2025-03-28 12:00:58'),
(9, 'Cétogène', 'Programme à faible teneur en glucides et riche en graisses', -300, 25.00, 5.00, 70.00, 1, NULL, NULL, '2025-03-28 12:00:58', '2025-03-28 12:00:58'),
(10, 'Végétarien', 'Programme équilibré sans viande', 0, 25.00, 50.00, 25.00, 1, NULL, NULL, '2025-03-28 12:00:58', '2025-03-28 12:00:58');

-- --------------------------------------------------------

--
-- Structure de la table `post_comments`
--

CREATE TABLE `post_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `post_comments`
--

INSERT INTO `post_comments` (`id`, `post_id`, `user_id`, `content`, `created_at`, `updated_at`) VALUES
(1, 10, 7, 'vzec', '2025-03-29 13:13:05', '2025-03-29 13:13:05'),
(2, 10, 7, 'nghf', '2025-03-29 13:15:31', '2025-03-29 13:15:31'),
(3, 9, 7, 'ngvh fg', '2025-03-29 13:16:43', '2025-03-29 13:16:43');

-- --------------------------------------------------------

--
-- Structure de la table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `user_id`, `created_at`) VALUES
(2, 9, 7, '2025-03-29 13:13:49'),
(3, 10, 7, '2025-03-29 13:16:36');

-- --------------------------------------------------------

--
-- Structure de la table `predefined_meals`
--

CREATE TABLE `predefined_meals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `total_calories` int(11) DEFAULT 0,
  `total_protein` decimal(5,2) DEFAULT 0.00,
  `total_carbs` decimal(5,2) DEFAULT 0.00,
  `total_fat` decimal(5,2) DEFAULT 0.00,
  `is_public` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `predefined_meal_items`
--

CREATE TABLE `predefined_meal_items` (
  `id` int(11) NOT NULL,
  `predefined_meal_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `quantity` decimal(5,2) DEFAULT 1.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('complet','nutrition','exercice') DEFAULT 'complet',
  `calorie_adjustment` float DEFAULT 0,
  `protein_ratio` float DEFAULT 0.3,
  `carbs_ratio` float DEFAULT 0.4,
  `fat_ratio` float DEFAULT 0.3,
  `daily_calories` int(11) DEFAULT 2000,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `programs`
--

INSERT INTO `programs` (`id`, `name`, `description`, `type`, `calorie_adjustment`, `protein_ratio`, `carbs_ratio`, `fat_ratio`, `daily_calories`, `created_at`, `updated_at`) VALUES
(5, 'InsulinFix 30', 'InsulinFix 30 est un programme intensif de 30 jours conçu pour t&#039;aider à perdre du poids rapidement, relancer ton métabolisme et réduire ta résistance à l&#039;insuline.\r\n\r\nGrâce à un calcul personnalisé de tes besoins caloriques, le programme ajuste automatiquement ton apport journalier en fonction de ton âge, poids, taille, niveau d&#039;activité et objectif. Tu suis ainsi un déficit calorique adapté, sans mettre ta santé en danger.\r\n\r\nBasé sur une alimentation riche en protéines, modérée en bons lipides et très pauvre en sucres rapides, ce plan t&#039;aide à :\r\n\r\n- Brûler les graisses efficacement\r\n\r\n- Perdre du poids de façon accélérée\r\n\r\n- Stabiliser ta glycémie et réduire les fringales\r\n\r\n- Suivre tes progrès en temps réel\r\n\r\nTu veux te reprendre en main, retrouver ton énergie, perdre du gras et reprendre le contrôle de ton corps ?\r\nInsulinFix 30 est fait pour toi.', 'nutrition', -35, 0.4, 0.2, 0.4, 2000, '2025-03-31 06:54:16', '2025-03-31 07:04:51');

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrateur avec accès complet', '2025-03-28 11:12:42', '2025-03-28 11:12:42'),
(2, 'user', 'Utilisateur standard', '2025-03-28 11:12:42', '2025-03-28 11:12:42');

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `settings`
--

INSERT INTO `settings` (`id`, `setting_name`, `setting_value`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'chatgpt_api_key', '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', 0, '2025-03-28 12:00:58', '2025-03-28 12:48:48'),
(2, 'site_name', 'Weight Tracker', 1, '2025-03-28 12:00:58', '2025-03-28 12:00:58'),
(3, 'site_description', 'Application de suivi de poids et de nutrition', 1, '2025-03-28 12:00:58', '2025-03-28 12:00:58'),
(4, 'maintenance_mode', '0', 1, '2025-03-28 12:00:58', '2025-03-28 12:00:58');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role_id` int(11) DEFAULT 2,
  `last_login` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended','banned') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `water_goal` float DEFAULT NULL,
  `last_notification_reset` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `postal_code`, `country`, `password`, `avatar`, `role_id`, `last_login`, `reset_token`, `reset_token_expires`, `email_verified`, `verification_token`, `status`, `created_at`, `updated_at`, `water_goal`, `last_notification_reset`) VALUES
(1, 'admin', 'Admin', 'User', 'admin@example.com', NULL, NULL, NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 1, NULL, NULL, NULL, 0, NULL, 'active', '2025-03-28 11:12:42', '2025-03-28 11:12:42', NULL, NULL),
(2, 'test', 'Jonathan', 'Petit', 'test@test.fr', NULL, NULL, NULL, NULL, NULL, '$2y$10$S0W7AKu9Y1RHVhBiRErAYuV4fzsYB/kZw7VBWhKuNO3VMdEI1Pr16', NULL, 2, NULL, NULL, NULL, 0, NULL, 'active', '2025-03-28 11:13:23', '2025-03-28 11:13:23', NULL, NULL),
(5, 'admin01', 'Admin', 'User', 'admin01@example.com', NULL, NULL, NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 1, NULL, NULL, NULL, 0, NULL, 'active', '2025-03-28 11:55:45', '2025-03-31 05:54:22', 2.5, '2025-03-31'),
(7, 'john2701', 'Jonathan', 'Petit', 'jonathan@universalgaming.io', NULL, NULL, NULL, NULL, NULL, '$2y$10$1jNdjQP/K7yJ36x09ILlYuYs9dY1k2xl70bxnFlsmJs4xLWq2Qiue', NULL, 2, NULL, NULL, NULL, 0, NULL, 'active', '2025-03-28 13:26:32', '2025-03-31 08:24:36', 3.75, '2025-03-31'),
(8, 'test02', 'testfezfe', 'testfe', 'test02@test.fr', NULL, NULL, NULL, NULL, NULL, '$2y$10$B9/tr7tgRh/23k5fW4EKJeUoxgqfL678UdoUxAz0gtLN8rnzCfate', NULL, 2, NULL, NULL, NULL, 0, NULL, 'active', '2025-03-29 09:23:00', '2025-03-29 13:01:22', 1.8, '2025-03-29'),
(9, 'test_user_1743406602', NULL, NULL, 'test1743406602@example.com', NULL, NULL, NULL, NULL, NULL, '$2y$10$sPrt.ll1FmqM1dmoHgogqeI/J1NV3YuImNPl4phuF8a3zCxtnYfH6', NULL, 2, NULL, NULL, NULL, 0, NULL, 'active', '2025-03-31 07:36:42', '2025-03-31 07:36:42', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_calorie_needs`
--

CREATE TABLE `user_calorie_needs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bmr` decimal(8,2) NOT NULL,
  `tdee` decimal(8,2) NOT NULL,
  `protein_target` decimal(8,2) NOT NULL,
  `carbs_target` decimal(8,2) NOT NULL,
  `fat_target` decimal(8,2) NOT NULL,
  `calculation_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_favorite_meals`
--

CREATE TABLE `user_favorite_meals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `predefined_meal_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_follows`
--

CREATE TABLE `user_follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_food_preferences`
--

CREATE TABLE `user_food_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `food_id` int(11) DEFAULT NULL,
  `food_name` varchar(100) DEFAULT NULL,
  `preference_type` enum('liked','disliked','allergic','intolerant') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `gender` enum('homme','femme','autre') NOT NULL,
  `birth_date` date NOT NULL,
  `height` int(11) NOT NULL,
  `activity_level` enum('sedentaire','leger','modere','actif','tres_actif') NOT NULL DEFAULT 'modere',
  `daily_calories` int(11) DEFAULT 2000,
  `protein_ratio` float DEFAULT 0.3,
  `carbs_ratio` float DEFAULT 0.4,
  `fat_ratio` float DEFAULT 0.3,
  `preferred_bmr_formula` varchar(50) DEFAULT 'mifflin_st_jeor',
  `nutrition_program_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `weight`, `gender`, `birth_date`, `height`, `activity_level`, `daily_calories`, `protein_ratio`, `carbs_ratio`, `fat_ratio`, `preferred_bmr_formula`, `nutrition_program_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'homme', '1990-01-01', 175, 'modere', 2000, 0.3, 0.4, 0.3, 'mifflin_st_jeor', NULL, NULL, '2025-03-28 11:12:42', '2025-03-28 11:12:42'),
(2, 2, 124.00, 'homme', '1991-06-27', 175, 'sedentaire', 324, 0.5, 0.3, 0.2, 'mifflin_st_jeor', NULL, NULL, '2025-03-28 11:13:23', '2025-03-29 05:50:34'),
(3, 1, NULL, 'homme', '1990-01-01', 175, 'modere', 2000, 0.3, 0.4, 0.3, 'mifflin_st_jeor', NULL, NULL, '2025-03-28 11:55:45', '2025-03-28 11:55:45'),
(4, 7, 125.00, 'homme', '1991-06-27', 175, 'sedentaire', 1338, 0.3, 0.4, 0.3, 'mifflin_st_jeor', NULL, NULL, '2025-03-28 13:26:32', '2025-03-31 12:37:54'),
(5, 8, 60.00, 'femme', '2011-08-17', 154, 'modere', 1865, 0.3, 0.4, 0.3, 'mifflin_st_jeor', NULL, NULL, '2025-03-29 09:23:00', '2025-03-29 10:00:40'),
(6, 9, NULL, 'homme', '1990-01-01', 180, 'modere', 2000, 0.3, 0.4, 0.3, 'mifflin_st_jeor', NULL, NULL, '2025-03-31 07:36:42', '2025-03-31 07:36:42');

-- --------------------------------------------------------

--
-- Structure de la table `user_programs`
--

CREATE TABLE `user_programs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `status` enum('actif','inactif') NOT NULL DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `user_programs`
--

INSERT INTO `user_programs` (`id`, `user_id`, `program_id`, `status`, `created_at`, `updated_at`) VALUES
(59, 7, 5, 'inactif', '2025-03-31 07:06:44', '2025-03-31 07:55:17'),
(60, 7, 5, 'inactif', '2025-03-31 08:24:46', '2025-03-31 08:25:01'),
(61, 7, 5, 'inactif', '2025-03-31 08:25:17', '2025-03-31 08:33:27');

-- --------------------------------------------------------

--
-- Structure de la table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `weight_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `food_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `exercise_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `goal_updates` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `email_notifications`, `weight_reminders`, `food_reminders`, `exercise_reminders`, `goal_updates`, `created_at`, `updated_at`) VALUES
(1, 7, 0, 1, 1, 1, 1, '2025-03-29 09:19:22', '2025-03-29 09:19:22');

-- --------------------------------------------------------

--
-- Structure de la table `water_logs`
--

CREATE TABLE `water_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` float NOT NULL,
  `log_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `water_logs`
--

INSERT INTO `water_logs` (`id`, `user_id`, `amount`, `log_date`, `created_at`) VALUES
(2, 7, 0.9375, '2025-03-29', '2025-03-29 10:49:02'),
(3, 7, 1.875, '2025-03-31', '2025-03-31 05:57:01');

-- --------------------------------------------------------

--
-- Structure de la table `weight_logs`
--

CREATE TABLE `weight_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `log_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `weight_logs`
--

INSERT INTO `weight_logs` (`id`, `user_id`, `weight`, `log_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 125.00, '2025-03-27', 'Poids de départ', '2025-03-28 11:13:23', '2025-03-28 11:14:21'),
(2, 2, 124.00, '2025-03-28', '', '2025-03-28 11:14:32', '2025-03-28 11:14:32'),
(3, 7, 125.00, '2025-03-28', 'Poids de départ', '2025-03-28 13:26:32', '2025-03-28 13:26:32'),
(4, 2, 121.00, '2025-03-29', '', '2025-03-29 05:49:31', '2025-03-29 05:49:31'),
(6, 8, 60.00, '2025-03-27', 'Poids de départ', '2025-03-29 09:23:00', '2025-03-29 09:31:12'),
(7, 8, 59.00, '2025-03-28', '', '2025-03-29 09:31:31', '2025-03-29 09:31:31'),
(8, 8, 56.00, '2025-03-29', 'fezzefzf', '2025-03-29 09:31:43', '2025-03-29 09:43:50'),
(13, 9, 75.50, '2025-03-31', NULL, '2025-03-31 07:36:42', '2025-03-31 07:36:42');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `ai_suggestions`
--
ALTER TABLE `ai_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Index pour la table `bmi_history`
--
ALTER TABLE `bmi_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bmi_history_user_date` (`user_id`,`log_date`);

--
-- Index pour la table `calorie_balance_history`
--
ALTER TABLE `calorie_balance_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_calorie_balance_user_date` (`user_id`,`log_date`);

--
-- Index pour la table `community_groups`
--
ALTER TABLE `community_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Index pour la table `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `exercise_categories`
--
ALTER TABLE `exercise_categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `exercise_logs`
--
ALTER TABLE `exercise_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exercise_id` (`exercise_id`);

--
-- Index pour la table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_foods_name` (`name`),
  ADD KEY `idx_foods_is_public` (`is_public`),
  ADD KEY `idx_foods_created_by_admin` (`created_by_admin`),
  ADD KEY `category_id` (`category_id`);

--
-- Index pour la table `food_categories`
--
ALTER TABLE `food_categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `food_logs`
--
ALTER TABLE `food_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `idx_food_logs_user_date` (`user_id`,`log_date`),
  ADD KEY `idx_food_logs_meal` (`meal_id`);

--
-- Index pour la table `food_preferences`
--
ALTER TABLE `food_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `food_id` (`food_id`);

--
-- Index pour la table `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `group_invitations`
--
ALTER TABLE `group_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_invitation` (`group_id`,`invited_user_id`),
  ADD KEY `invited_by` (`invited_by`),
  ADD KEY `invited_user_id` (`invited_user_id`);

--
-- Index pour la table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_group_member` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `meals`
--
ALTER TABLE `meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_meals_user_date` (`user_id`,`log_date`);

--
-- Index pour la table `meal_notification_preferences`
--
ALTER TABLE `meal_notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nutrition_program_id` (`nutrition_program_id`),
  ADD KEY `idx_meal_plans_user_dates` (`user_id`,`start_date`,`end_date`);

--
-- Index pour la table `meal_plan_days`
--
ALTER TABLE `meal_plan_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_meal_plan_days_dates` (`meal_plan_id`,`day_date`);

--
-- Index pour la table `meal_plan_items`
--
ALTER TABLE `meal_plan_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meal_plan_meal_id` (`meal_plan_meal_id`),
  ADD KEY `food_id` (`food_id`);

--
-- Index pour la table `meal_plan_meals`
--
ALTER TABLE `meal_plan_meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meal_plan_day_id` (`meal_plan_day_id`);

--
-- Index pour la table `nutrition_programs`
--
ALTER TABLE `nutrition_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `predefined_meals`
--
ALTER TABLE `predefined_meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_predefined_meals_name` (`name`),
  ADD KEY `idx_predefined_meals_is_public` (`is_public`);

--
-- Index pour la table `predefined_meal_items`
--
ALTER TABLE `predefined_meal_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `predefined_meal_id` (`predefined_meal_id`),
  ADD KEY `food_id` (`food_id`);

--
-- Index pour la table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Index pour la table `user_calorie_needs`
--
ALTER TABLE `user_calorie_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `user_favorite_meals`
--
ALTER TABLE `user_favorite_meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `predefined_meal_id` (`predefined_meal_id`);

--
-- Index pour la table `user_follows`
--
ALTER TABLE `user_follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Index pour la table `user_food_preferences`
--
ALTER TABLE `user_food_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `food_id` (`food_id`);

--
-- Index pour la table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `nutrition_program_id` (`nutrition_program_id`);

--
-- Index pour la table `user_programs`
--
ALTER TABLE `user_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Index pour la table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `water_logs`
--
ALTER TABLE `water_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `weight_logs`
--
ALTER TABLE `weight_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_weight_logs_user_date` (`user_id`,`log_date`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `ai_suggestions`
--
ALTER TABLE `ai_suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT pour la table `app_settings`
--
ALTER TABLE `app_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `bmi_history`
--
ALTER TABLE `bmi_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `calorie_balance_history`
--
ALTER TABLE `calorie_balance_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `community_groups`
--
ALTER TABLE `community_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `exercise_categories`
--
ALTER TABLE `exercise_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `exercise_logs`
--
ALTER TABLE `exercise_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT pour la table `food_categories`
--
ALTER TABLE `food_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `food_logs`
--
ALTER TABLE `food_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `food_preferences`
--
ALTER TABLE `food_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `goals`
--
ALTER TABLE `goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT pour la table `group_invitations`
--
ALTER TABLE `group_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `meals`
--
ALTER TABLE `meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `meal_notification_preferences`
--
ALTER TABLE `meal_notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `meal_plans`
--
ALTER TABLE `meal_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `meal_plan_days`
--
ALTER TABLE `meal_plan_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `meal_plan_items`
--
ALTER TABLE `meal_plan_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `meal_plan_meals`
--
ALTER TABLE `meal_plan_meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `nutrition_programs`
--
ALTER TABLE `nutrition_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `predefined_meals`
--
ALTER TABLE `predefined_meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `predefined_meal_items`
--
ALTER TABLE `predefined_meal_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `user_calorie_needs`
--
ALTER TABLE `user_calorie_needs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_favorite_meals`
--
ALTER TABLE `user_favorite_meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_follows`
--
ALTER TABLE `user_follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_food_preferences`
--
ALTER TABLE `user_food_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `user_programs`
--
ALTER TABLE `user_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT pour la table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `water_logs`
--
ALTER TABLE `water_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `weight_logs`
--
ALTER TABLE `weight_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `ai_suggestions`
--
ALTER TABLE `ai_suggestions`
  ADD CONSTRAINT `ai_suggestions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `bmi_history`
--
ALTER TABLE `bmi_history`
  ADD CONSTRAINT `bmi_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `calorie_balance_history`
--
ALTER TABLE `calorie_balance_history`
  ADD CONSTRAINT `calorie_balance_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `community_groups`
--
ALTER TABLE `community_groups`
  ADD CONSTRAINT `community_groups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `community_posts`
--
ALTER TABLE `community_posts`
  ADD CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_posts_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `community_groups` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `community_posts_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `community_groups` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `exercises`
--
ALTER TABLE `exercises`
  ADD CONSTRAINT `exercises_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `exercise_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exercises_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `exercise_logs`
--
ALTER TABLE `exercise_logs`
  ADD CONSTRAINT `exercise_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exercise_logs_ibfk_2` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `foods_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `food_categories` (`id`);

--
-- Contraintes pour la table `food_logs`
--
ALTER TABLE `food_logs`
  ADD CONSTRAINT `food_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `food_logs_ibfk_3` FOREIGN KEY (`meal_id`) REFERENCES `meals` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `food_preferences`
--
ALTER TABLE `food_preferences`
  ADD CONSTRAINT `food_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `food_preferences_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `goals`
--
ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `group_invitations`
--
ALTER TABLE `group_invitations`
  ADD CONSTRAINT `group_invitations_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `community_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_invitations_ibfk_2` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_invitations_ibfk_3` FOREIGN KEY (`invited_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `community_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `meals`
--
ALTER TABLE `meals`
  ADD CONSTRAINT `meals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `meal_notification_preferences`
--
ALTER TABLE `meal_notification_preferences`
  ADD CONSTRAINT `meal_notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD CONSTRAINT `meal_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meal_plans_ibfk_2` FOREIGN KEY (`nutrition_program_id`) REFERENCES `nutrition_programs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `meal_plan_days`
--
ALTER TABLE `meal_plan_days`
  ADD CONSTRAINT `meal_plan_days_ibfk_1` FOREIGN KEY (`meal_plan_id`) REFERENCES `meal_plans` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `meal_plan_items`
--
ALTER TABLE `meal_plan_items`
  ADD CONSTRAINT `meal_plan_items_ibfk_1` FOREIGN KEY (`meal_plan_meal_id`) REFERENCES `meal_plan_meals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meal_plan_items_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `meal_plan_meals`
--
ALTER TABLE `meal_plan_meals`
  ADD CONSTRAINT `meal_plan_meals_ibfk_1` FOREIGN KEY (`meal_plan_day_id`) REFERENCES `meal_plan_days` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `nutrition_programs`
--
ALTER TABLE `nutrition_programs`
  ADD CONSTRAINT `nutrition_programs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `post_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `predefined_meals`
--
ALTER TABLE `predefined_meals`
  ADD CONSTRAINT `predefined_meals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `predefined_meal_items`
--
ALTER TABLE `predefined_meal_items`
  ADD CONSTRAINT `predefined_meal_items_ibfk_1` FOREIGN KEY (`predefined_meal_id`) REFERENCES `predefined_meals` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Contraintes pour la table `user_calorie_needs`
--
ALTER TABLE `user_calorie_needs`
  ADD CONSTRAINT `user_calorie_needs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_favorite_meals`
--
ALTER TABLE `user_favorite_meals`
  ADD CONSTRAINT `user_favorite_meals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorite_meals_ibfk_2` FOREIGN KEY (`predefined_meal_id`) REFERENCES `predefined_meals` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_follows`
--
ALTER TABLE `user_follows`
  ADD CONSTRAINT `user_follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_food_preferences`
--
ALTER TABLE `user_food_preferences`
  ADD CONSTRAINT `user_food_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_profiles_ibfk_2` FOREIGN KEY (`nutrition_program_id`) REFERENCES `nutrition_programs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `user_programs`
--
ALTER TABLE `user_programs`
  ADD CONSTRAINT `user_programs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_programs_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `water_logs`
--
ALTER TABLE `water_logs`
  ADD CONSTRAINT `water_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `weight_logs`
--
ALTER TABLE `weight_logs`
  ADD CONSTRAINT `weight_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
