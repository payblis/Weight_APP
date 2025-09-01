<?php
require_once 'config/database.php';

/**
 * Fonctions de gestion des crédits IA
 */

class CreditManager {
    
    /**
     * Obtenir le solde de crédits d'un utilisateur
     */
    public static function getUserCredits($userId) {
        try {
            $sql = "SELECT credits_balance, total_credits_purchased, total_credits_used 
                    FROM ai_credits WHERE user_id = ?";
            $credits = fetchOne($sql, [$userId]);
            
            if (!$credits) {
                // Créer un enregistrement pour l'utilisateur s'il n'en a pas
                self::initializeUserCredits($userId);
                return [
                    'credits_balance' => 0,
                    'total_credits_purchased' => 0,
                    'total_credits_used' => 0
                ];
            }
            
            return $credits;
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des crédits: " . $e->getMessage());
            return [
                'credits_balance' => 0,
                'total_credits_purchased' => 0,
                'total_credits_used' => 0
            ];
        }
    }
    
    /**
     * Initialiser les crédits pour un nouvel utilisateur
     */
    private static function initializeUserCredits($userId) {
        try {
            $sql = "INSERT INTO ai_credits (user_id, credits_balance, total_credits_purchased, total_credits_used) 
                    VALUES (?, 0, 0, 0)";
            return insert($sql, [$userId]);
        } catch (Exception $e) {
            error_log("Erreur lors de l'initialisation des crédits: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajouter des crédits à un utilisateur (achat)
     */
    public static function addCredits($userId, $creditsAmount, $paymentData) {
        try {
            // Commencer une transaction
            global $pdo;
            $pdo->beginTransaction();
            
            // Créer l'enregistrement d'achat
            $sql = "INSERT INTO credit_purchases (
                user_id, credits_amount, amount_paid, currency, payment_method,
                payment_status, transaction_id, billing_email, card_last4, card_brand
            ) VALUES (?, ?, ?, 'EUR', 'credit_card', 'completed', ?, ?, ?, ?)";
            
            $purchaseId = insert($sql, [
                $userId,
                $creditsAmount,
                $paymentData['amount'],
                $paymentData['transaction_id'],
                $paymentData['email'],
                $paymentData['card_last4'] ?? null,
                $paymentData['card_brand'] ?? null
            ]);
            
            if (!$purchaseId) {
                throw new Exception("Erreur lors de la création de l'achat");
            }
            
            // Mettre à jour le solde de crédits
            $sql = "INSERT INTO ai_credits (user_id, credits_balance, total_credits_purchased, total_credits_used) 
                    VALUES (?, ?, ?, 0)
                    ON DUPLICATE KEY UPDATE 
                    credits_balance = credits_balance + VALUES(credits_balance),
                    total_credits_purchased = total_credits_purchased + VALUES(total_credits_purchased)";
            
            $result = update($sql, [$userId, $creditsAmount, $creditsAmount]);
            
            if (!$result) {
                throw new Exception("Erreur lors de la mise à jour du solde");
            }
            
            // Valider la transaction
            $pdo->commit();
            
            return $purchaseId;
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log("Erreur lors de l'ajout de crédits: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Utiliser des crédits (consommation)
     */
    public static function useCredits($userId, $creditsUsed, $feature, $description = null, $aiResponseLength = null) {
        try {
            // Commencer une transaction
            global $pdo;
            $pdo->beginTransaction();
            
            // Vérifier le solde disponible
            $currentCredits = self::getUserCredits($userId);
            if ($currentCredits['credits_balance'] < $creditsUsed) {
                throw new Exception("Crédits insuffisants");
            }
            
            // Enregistrer l'utilisation
            $sql = "INSERT INTO credit_usage (
                user_id, credits_used, feature_used, description, ai_response_length
            ) VALUES (?, ?, ?, ?, ?)";
            
            $usageId = insert($sql, [
                $userId,
                $creditsUsed,
                $feature,
                $description,
                $aiResponseLength
            ]);
            
            if (!$usageId) {
                throw new Exception("Erreur lors de l'enregistrement de l'utilisation");
            }
            
            // Déduire les crédits du solde
            $sql = "UPDATE ai_credits SET 
                    credits_balance = credits_balance - ?,
                    total_credits_used = total_credits_used + ?
                    WHERE user_id = ?";
            
            $result = update($sql, [$creditsUsed, $creditsUsed, $userId]);
            
            if (!$result) {
                throw new Exception("Erreur lors de la mise à jour du solde");
            }
            
            // Valider la transaction
            $pdo->commit();
            
            return $usageId;
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log("Erreur lors de l'utilisation de crédits: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si un utilisateur a suffisamment de crédits
     */
    public static function hasEnoughCredits($userId, $requiredCredits) {
        $credits = self::getUserCredits($userId);
        return $credits['credits_balance'] >= $requiredCredits;
    }
    
    /**
     * Obtenir l'historique des achats de crédits
     */
    public static function getPurchaseHistory($userId, $limit = 10) {
        try {
            $sql = "SELECT * FROM credit_purchases 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            return fetchAll($sql, [$userId, $limit]);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération de l'historique d'achats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir l'historique d'utilisation des crédits
     */
    public static function getUsageHistory($userId, $limit = 10) {
        try {
            $sql = "SELECT * FROM credit_usage 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            return fetchAll($sql, [$userId, $limit]);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération de l'historique d'utilisation: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculer le coût des crédits selon le package
     */
    public static function getCreditPackages() {
        return [
            'small' => [
                'credits' => 10,
                'price' => 4.99,
                'price_per_credit' => 0.50,
                'label' => 'Pack Découverte',
                'description' => 'Idéal pour tester l\'IA'
            ],
            'medium' => [
                'credits' => 50,
                'price' => 19.99,
                'price_per_credit' => 0.40,
                'label' => 'Pack Standard',
                'description' => 'Le plus populaire',
                'popular' => true
            ],
            'large' => [
                'credits' => 150,
                'price' => 49.99,
                'price_per_credit' => 0.33,
                'label' => 'Pack Premium',
                'description' => 'Économisez 34%'
            ],
            'xlarge' => [
                'credits' => 500,
                'price' => 149.99,
                'price_per_credit' => 0.30,
                'label' => 'Pack Pro',
                'description' => 'Économisez 40%'
            ]
        ];
    }
    
    /**
     * Obtenir les statistiques des crédits
     */
    public static function getCreditStats($userId) {
        try {
            $credits = self::getUserCredits($userId);
            $purchases = self::getPurchaseHistory($userId, 1000);
            $usage = self::getUsageHistory($userId, 1000);
            
            $totalSpent = 0;
            foreach ($purchases as $purchase) {
                if ($purchase['payment_status'] === 'completed') {
                    $totalSpent += $purchase['amount_paid'];
                }
            }
            
            $totalUsed = 0;
            foreach ($usage as $use) {
                $totalUsed += $use['credits_used'];
            }
            
            return [
                'current_balance' => $credits['credits_balance'],
                'total_purchased' => $credits['total_credits_purchased'],
                'total_used' => $credits['total_credits_used'],
                'total_spent' => $totalSpent,
                'average_cost_per_credit' => $credits['total_credits_purchased'] > 0 ? 
                    $totalSpent / $credits['total_credits_purchased'] : 0
            ];
        } catch (Exception $e) {
            error_log("Erreur lors du calcul des statistiques: " . $e->getMessage());
            return [
                'current_balance' => 0,
                'total_purchased' => 0,
                'total_used' => 0,
                'total_spent' => 0,
                'average_cost_per_credit' => 0
            ];
        }
    }
    
    /**
     * Valider les données d'achat de crédits
     */
    public static function validateCreditPurchase($data) {
        $errors = [];
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Une adresse email valide est requise";
        }
        
        if (empty($data['cardName'])) {
            $errors[] = "Le nom du titulaire de la carte est requis";
        }
        
        if (empty($data['cardNumber']) || strlen(preg_replace('/\s/', '', $data['cardNumber'])) < 13) {
            $errors[] = "Un numéro de carte valide est requis";
        }
        
        if (empty($data['cardExpiry']) || !preg_match('/^\d{2}\/\d{2}$/', $data['cardExpiry'])) {
            $errors[] = "Une date d'expiration valide est requise (MM/AA)";
        }
        
        if (empty($data['cardCVC']) || strlen($data['cardCVC']) < 3) {
            $errors[] = "Un code de sécurité valide est requis";
        }
        
        if (empty($data['package'])) {
            $errors[] = "Veuillez sélectionner un package de crédits";
        }
        
        // Validation de la date d'expiration
        if (!empty($data['cardExpiry'])) {
            $expiry = explode('/', $data['cardExpiry']);
            $month = (int)$expiry[0];
            $year = (int)$expiry[1];
            
            $currentYear = (int)date('y');
            $currentMonth = (int)date('m');
            
            if ($year < $currentYear || ($year === $currentYear && $month < $currentMonth)) {
                $errors[] = "La carte a expiré";
            }
        }
        
        return $errors;
    }
    
    /**
     * Traiter un achat de crédits (simulation)
     */
    public static function processCreditPurchase($paymentData) {
        // Simulation d'un traitement de paiement
        // Dans un environnement de production, vous intégreriez ici Stripe, PayPal, etc.
        
        try {
            // Simuler un délai de traitement
            sleep(1);
            
            // Simuler une validation de carte
            $cardNumber = preg_replace('/\s/', '', $paymentData['cardNumber']);
            
            // Test avec des numéros de carte de test
            if (in_array(substr($cardNumber, 0, 4), ['4000', '4242', '5555'])) {
                return [
                    'success' => true,
                    'transaction_id' => 'txn_credits_' . uniqid(),
                    'card_last4' => substr($cardNumber, -4),
                    'card_brand' => self::detectCardBrand($cardNumber)
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Carte refusée. Veuillez utiliser une carte de test valide.'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors du traitement du paiement'
            ];
        }
    }
    
    /**
     * Détecter la marque de carte
     */
    private static function detectCardBrand($cardNumber) {
        $patterns = [
            'visa' => '/^4/',
            'mastercard' => '/^5[1-5]/',
            'amex' => '/^3[47]/',
            'discover' => '/^6(?:011|5)/'
        ];
        
        foreach ($patterns as $brand => $pattern) {
            if (preg_match($pattern, $cardNumber)) {
                return $brand;
            }
        }
        
        return 'unknown';
    }
}
?>
