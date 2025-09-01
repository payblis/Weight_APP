-- Table pour gérer les crédits IA
CREATE TABLE IF NOT EXISTS `ai_credits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `credits_balance` int(11) NOT NULL DEFAULT 0,
  `total_credits_purchased` int(11) NOT NULL DEFAULT 0,
  `total_credits_used` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `ai_credits_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Table pour l'historique des achats de crédits
CREATE TABLE IF NOT EXISTS `credit_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `credits_amount` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `payment_method` varchar(50) DEFAULT 'credit_card',
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `billing_email` varchar(100) NOT NULL,
  `card_last4` varchar(4) DEFAULT NULL,
  `card_brand` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `payment_status` (`payment_status`),
  CONSTRAINT `credit_purchases_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Table pour l'historique d'utilisation des crédits
CREATE TABLE IF NOT EXISTS `credit_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `credits_used` int(11) NOT NULL,
  `feature_used` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ai_response_length` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `feature_used` (`feature_used`),
  CONSTRAINT `credit_usage_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Index pour optimiser les requêtes
CREATE INDEX `idx_ai_credits_balance` ON `ai_credits` (`credits_balance`);
CREATE INDEX `idx_credit_purchases_user_status` ON `credit_purchases` (`user_id`, `payment_status`);
CREATE INDEX `idx_credit_usage_user_feature` ON `credit_usage` (`user_id`, `feature_used`);
