<?php
session_start();
require_once 'includes/subscription_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=premium-subscribe.php');
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Traitement du formulaire de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupérer les données du formulaire
    $plan = $_POST['plan'] ?? 'mensuel';
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cardName = trim($_POST['cardName'] ?? '');
    $cardNumber = trim($_POST['cardNumber'] ?? '');
    $cardExpiry = trim($_POST['cardExpiry'] ?? '');
    $cardCVC = trim($_POST['cardCVC'] ?? '');
    
    // Définir les prix selon le plan
    $prices = [
        'mensuel' => 9.99,
        'annuel' => 59.99,
        'famille' => 99.99
    ];
    
    $amount = $prices[$plan] ?? 9.99;
    
    // Préparer les données de paiement
    $paymentData = [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'cardName' => $cardName,
        'cardNumber' => $cardNumber,
        'cardExpiry' => $cardExpiry,
        'cardCVC' => $cardCVC
    ];
    
    // Valider les données
    $validationErrors = SubscriptionManager::validatePaymentData($paymentData);
    
    if (empty($validationErrors)) {
        // Traiter le paiement
        $paymentResult = SubscriptionManager::processPayment($paymentData);
        
        if ($paymentResult['success']) {
            // Préparer les données de facturation
            $billingData = [
                'email' => $email,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'cardLast4' => $paymentResult['card_last4'],
                'cardBrand' => $paymentResult['card_brand']
            ];
            
            // Créer l'abonnement
            $subscriptionId = SubscriptionManager::createSubscription(
                $userId, 
                $plan, 
                $amount, 
                $billingData
            );
            
            if ($subscriptionId) {
                // Rediriger vers la page de succès avec les informations
                $_SESSION['payment_success'] = [
                    'subscription_id' => $subscriptionId,
                    'plan' => $plan,
                    'amount' => $amount,
                    'transaction_id' => $paymentResult['transaction_id']
                ];
                
                header('Location: premium-success.php');
                exit;
            } else {
                $errors[] = "Erreur lors de la création de l'abonnement. Veuillez réessayer.";
            }
        } else {
            $errors[] = $paymentResult['error'];
        }
    } else {
        $errors = $validationErrors;
    }
}

// Si on arrive ici, il y a eu une erreur
// Rediriger vers la page de paiement avec les erreurs
if (!empty($errors)) {
    $_SESSION['payment_errors'] = $errors;
    $_SESSION['payment_data'] = $_POST ?? [];
    header('Location: premium-subscribe.php?plan=' . ($plan ?? 'mensuel'));
    exit;
}

// Redirection par défaut
header('Location: premium-subscribe.php');
exit;
?>
