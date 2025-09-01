<?php
session_start();
require_once 'includes/credit_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=buy-credits.php');
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];

// Traitement du formulaire d'achat de crédits
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupérer les données du formulaire
    $package = $_POST['package'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $cardName = trim($_POST['cardName'] ?? '');
    $cardNumber = trim($_POST['cardNumber'] ?? '');
    $cardExpiry = trim($_POST['cardExpiry'] ?? '');
    $cardCVC = trim($_POST['cardCVC'] ?? '');
    
    // Récupérer les informations du package
    $creditPackages = CreditManager::getCreditPackages();
    
    if ($package === 'custom' && isset($_POST['custom_credits'])) {
        $customCredits = intval($_POST['custom_credits']);
        if ($customCredits >= 1 && $customCredits <= 1000) {
            $packageData = CreditManager::calculateCustomPrice($customCredits);
            $creditsAmount = $packageData['credits'];
            $amount = $packageData['price'];
        } else {
            $errors[] = "Nombre de crédits personnalisés invalide";
        }
    } elseif (isset($creditPackages[$package])) {
        $packageData = $creditPackages[$package];
        $creditsAmount = $packageData['credits'] + $packageData['bonus'];
        $amount = $packageData['price'];
    } else {
        $errors[] = "Package de crédits invalide";
    }
    
    // Préparer les données de paiement
    $paymentData = [
        'email' => $email,
        'cardName' => $cardName,
        'cardNumber' => $cardNumber,
        'cardExpiry' => $cardExpiry,
        'cardCVC' => $cardCVC,
        'package' => $package
    ];
    
    // Valider les données
    $validationErrors = CreditManager::validateCreditPurchase($paymentData);
    
    if (empty($validationErrors)) {
        // Traiter le paiement
        $paymentResult = CreditManager::processCreditPurchase($paymentData);
        
        if ($paymentResult['success']) {
            // Préparer les données pour l'ajout de crédits
            $creditData = [
                'email' => $email,
                'amount' => $amount,
                'transaction_id' => $paymentResult['transaction_id'],
                'card_last4' => $paymentResult['card_last4'],
                'card_brand' => $paymentResult['card_brand']
            ];
            
            // Ajouter les crédits à l'utilisateur
            $purchaseId = CreditManager::addCredits($userId, $creditsAmount, $creditData);
            
            if ($purchaseId) {
                // Rediriger vers la page de succès avec les informations
                $_SESSION['credit_purchase_success'] = [
                    'purchase_id' => $purchaseId,
                    'package' => $package,
                    'credits_amount' => $creditsAmount,
                    'amount' => $amount,
                    'transaction_id' => $paymentResult['transaction_id']
                ];
                
                header('Location: credit-purchase-success.php');
                exit;
            } else {
                $errors[] = "Erreur lors de l'ajout des crédits. Veuillez réessayer.";
            }
        } else {
            $errors[] = $paymentResult['error'];
        }
    } else {
        $errors = $validationErrors;
    }
}

// Si on arrive ici, il y a eu une erreur
// Rediriger vers la page d'achat avec les erreurs
if (!empty($errors)) {
    $_SESSION['credit_errors'] = $errors;
    $_SESSION['credit_data'] = $_POST ?? [];
    header('Location: buy-credits.php?package=' . ($package ?? 'medium'));
    exit;
}

// Redirection par défaut
header('Location: buy-credits.php');
exit;
?>
