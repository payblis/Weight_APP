<?php
require_once 'config/database.php';

/**
 * Fonctions de gestion des abonnements Premium
 */

class SubscriptionManager {
    
    /**
     * Créer un nouvel abonnement
     */
    public static function createSubscription($userId, $planType, $amount, $billingData) {
        try {
            // Calculer la date de fin selon le plan
            $endDate = self::calculateEndDate($planType);
            
            // Insérer l'abonnement
            $sql = "INSERT INTO subscriptions (
                user_id, plan_type, amount, currency, end_date, 
                billing_email, billing_first_name, billing_last_name,
                card_last4, card_brand, payment_method
            ) VALUES (?, ?, ?, 'EUR', ?, ?, ?, ?, ?, ?, 'credit_card')";
            
            $params = [
                $userId,
                $planType,
                $amount,
                $endDate,
                $billingData['email'],
                $billingData['firstName'],
                $billingData['lastName'],
                $billingData['cardLast4'] ?? null,
                $billingData['cardBrand'] ?? null
            ];
            
            $subscriptionId = insert($sql, $params);
            
            if ($subscriptionId) {
                // Mettre à jour le statut Premium de l'utilisateur
                self::updateUserPremiumStatus($userId, 'premium', $endDate);
                
                // Créer l'entrée dans l'historique des paiements
                self::createPaymentHistory($subscriptionId, $userId, $amount, $billingData);
                
                return $subscriptionId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erreur lors de la création de l'abonnement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculer la date de fin selon le type de plan
     */
    private static function calculateEndDate($planType) {
        $now = new DateTime();
        
        switch ($planType) {
            case 'mensuel':
                return $now->modify('+1 month')->format('Y-m-d H:i:s');
            case 'annuel':
                return $now->modify('+1 year')->format('Y-m-d H:i:s');
            case 'famille':
                return $now->modify('+1 year')->format('Y-m-d H:i:s');
            default:
                return $now->modify('+1 month')->format('Y-m-d H:i:s');
        }
    }
    
    /**
     * Mettre à jour le statut Premium d'un utilisateur
     */
    public static function updateUserPremiumStatus($userId, $status, $expiresAt = null) {
        $sql = "UPDATE users SET premium_status = ?, premium_expires_at = ? WHERE id = ?";
        return update($sql, [$status, $expiresAt, $userId]);
    }
    
    /**
     * Créer une entrée dans l'historique des paiements
     */
    private static function createPaymentHistory($subscriptionId, $userId, $amount, $billingData) {
        $sql = "INSERT INTO payment_history (
            subscription_id, user_id, amount, currency, payment_method, 
            status, billing_details
        ) VALUES (?, ?, ?, 'EUR', 'credit_card', 'completed', ?)";
        
        $billingDetails = json_encode([
            'email' => $billingData['email'],
            'firstName' => $billingData['firstName'],
            'lastName' => $billingData['lastName'],
            'cardLast4' => $billingData['cardLast4'] ?? null,
            'cardBrand' => $billingData['cardBrand'] ?? null
        ]);
        
        return insert($sql, [$subscriptionId, $userId, $amount, $billingDetails]);
    }
    
    /**
     * Vérifier si un utilisateur a un abonnement Premium actif
     */
    public static function isUserPremium($userId) {
        $sql = "SELECT premium_status, premium_expires_at FROM users WHERE id = ?";
        $user = fetchOne($sql, [$userId]);
        
        if (!$user) {
            return false;
        }
        
        // Vérifier si l'utilisateur a le statut Premium
        if ($user['premium_status'] !== 'premium') {
            return false;
        }
        
        // Vérifier si l'abonnement n'a pas expiré
        if ($user['premium_expires_at'] && $user['premium_expires_at'] < date('Y-m-d H:i:s')) {
            // Mettre à jour le statut si expiré
            self::updateUserPremiumStatus($userId, 'free', null);
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtenir les informations d'abonnement d'un utilisateur
     */
    public static function getUserSubscription($userId) {
        $sql = "SELECT s.*, u.premium_status, u.premium_expires_at 
                FROM subscriptions s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.user_id = ? AND s.status = 'active' 
                ORDER BY s.created_at DESC 
                LIMIT 1";
        
        return fetchOne($sql, [$userId]);
    }
    
    /**
     * Obtenir l'historique des paiements d'un utilisateur
     */
    public static function getUserPaymentHistory($userId, $limit = 10) {
        $sql = "SELECT ph.*, s.plan_type 
                FROM payment_history ph 
                JOIN subscriptions s ON ph.subscription_id = s.id 
                WHERE ph.user_id = ? 
                ORDER BY ph.created_at DESC 
                LIMIT ?";
        
        return fetchAll($sql, [$userId, $limit]);
    }
    
    /**
     * Annuler un abonnement
     */
    public static function cancelSubscription($subscriptionId, $userId) {
        try {
            // Marquer l'abonnement comme annulé
            $sql = "UPDATE subscriptions SET status = 'cancelled', cancelled_at = NOW() 
                    WHERE id = ? AND user_id = ?";
            $result = update($sql, [$subscriptionId, $userId]);
            
            if ($result) {
                // Mettre à jour le statut de l'utilisateur
                self::updateUserPremiumStatus($userId, 'free', null);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erreur lors de l'annulation de l'abonnement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valider les données de paiement
     */
    public static function validatePaymentData($data) {
        $errors = [];
        
        // Validation des informations personnelles
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Une adresse email valide est requise";
        }
        
        // Validation des informations de carte
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
     * Traiter un paiement (simulation pour l'instant)
     */
    public static function processPayment($paymentData) {
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
                    'transaction_id' => 'txn_' . uniqid(),
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
    
    /**
     * Obtenir les statistiques des abonnements
     */
    public static function getSubscriptionStats() {
        $stats = [];
        
        // Total des abonnements actifs
        $sql = "SELECT COUNT(*) as total FROM subscriptions WHERE status = 'active'";
        $result = fetchOne($sql);
        $stats['active_subscriptions'] = $result['total'] ?? 0;
        
        // Revenus du mois
        $sql = "SELECT SUM(amount) as total FROM payment_history 
                WHERE status = 'completed' 
                AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $result = fetchOne($sql);
        $stats['monthly_revenue'] = $result['total'] ?? 0;
        
        // Nouveaux abonnements du mois
        $sql = "SELECT COUNT(*) as total FROM subscriptions 
                WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $result = fetchOne($sql);
        $stats['new_subscriptions'] = $result['total'] ?? 0;
        
        return $stats;
    }
}
?>
