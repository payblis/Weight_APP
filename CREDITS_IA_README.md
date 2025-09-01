# Système de Crédits IA - MyFity

## Vue d'ensemble

Ce document décrit le système d'achat et de gestion des crédits IA pour l'application MyFity. Les utilisateurs peuvent acheter des crédits pour interagir avec l'IA de coaching personnalisé.

## Fonctionnalités

### 🎯 **Système d'achat de crédits**

- **4 packages de crédits** avec des prix dégressifs
- **Interface de paiement sécurisée** par carte bancaire
- **Validation en temps réel** des données de paiement
- **Détection automatique** du type de carte
- **Simulation de paiement** pour les tests

### 📊 **Gestion des crédits**

- **Solde en temps réel** des crédits disponibles
- **Historique complet** des achats et utilisations
- **Statistiques détaillées** (total dépensé, prix moyen, etc.)
- **Gestion des transactions** avec statuts

### 🤖 **Utilisation des crédits**

- **Coaching IA** : 1 crédit par question
- **Programmes personnalisés** : 3 crédits
- **Analyses nutritionnelles** : 2 crédits
- **Conseils d'entraînement** : 2 crédits

## Packages de crédits

| Package | Crédits | Prix | Prix/crédit | Économies |
|---------|---------|------|-------------|-----------|
| **Pack Découverte** | 10 | 4,99€ | 0,50€ | - |
| **Pack Standard** | 50 | 19,99€ | 0,40€ | 20% |
| **Pack Premium** | 150 | 49,99€ | 0,33€ | 34% |
| **Pack Pro** | 500 | 149,99€ | 0,30€ | 40% |

## Structure de base de données

### Tables créées

#### `ai_credits`
- Gestion du solde des utilisateurs
- Total des crédits achetés et utilisés
- Mise à jour automatique des soldes

#### `credit_purchases`
- Historique des achats de crédits
- Détails des paiements (montant, statut, carte)
- Informations de facturation

#### `credit_usage`
- Historique d'utilisation des crédits
- Fonctionnalités utilisées
- Détails des interactions IA

## Installation

### 1. Exécuter le script d'installation

```bash
# Accéder à l'URL suivante dans votre navigateur :
http://votre-domaine.com/install-credits.php
```

### 2. Vérifier les permissions

Assurez-vous que les fichiers suivants sont accessibles :
- `includes/credit_functions.php`
- `buy-credits.php`
- `process-credit-purchase.php`
- `my-credits.php`

## Utilisation

### Pour les utilisateurs

1. **Accéder à l'achat** : Via le menu ou `buy-credits.php`
2. **Choisir un package** : 4 options disponibles
3. **Payer** : Formulaire de paiement sécurisé
4. **Confirmation** : Redirection vers la page de succès
5. **Gestion** : Accéder à "Mes Crédits IA" pour gérer

### Pour les développeurs

#### Vérifier le solde de crédits

```php
require_once 'includes/credit_functions.php';

$credits = CreditManager::getUserCredits($userId);
echo "Solde : " . $credits['credits_balance'] . " crédits";
```

#### Utiliser des crédits

```php
// Vérifier si l'utilisateur a assez de crédits
if (CreditManager::hasEnoughCredits($userId, 1)) {
    // Utiliser 1 crédit pour une question IA
    $usageId = CreditManager::useCredits(
        $userId, 
        1, 
        'coaching_ai', 
        'Question sur la nutrition'
    );
    
    if ($usageId) {
        // Traiter la question IA
        // ...
    }
} else {
    // Rediriger vers l'achat de crédits
    header('Location: buy-credits.php');
}
```

#### Obtenir les statistiques

```php
$stats = CreditManager::getCreditStats($userId);
echo "Total dépensé : " . $stats['total_spent'] . "€";
echo "Prix moyen : " . $stats['average_cost_per_credit'] . "€";
```

## Cartes de test

Pour tester le système de paiement :

- **Visa** : `4242 4242 4242 4242`
- **Mastercard** : `5555 5555 5555 4444`
- **Carte refusée** : `4000 0000 0000 0002`

**Date d'expiration** : Utilisez une date future (ex: `12/25`)
**CVC** : N'importe quel code à 3 chiffres (ex: `123`)

## Intégration avec l'IA

### Exemple d'intégration pour une question IA

```php
// Dans votre page de coaching IA
if (isset($_POST['question'])) {
    $question = $_POST['question'];
    
    // Vérifier les crédits
    if (CreditManager::hasEnoughCredits($userId, 1)) {
        // Utiliser un crédit
        $usageId = CreditManager::useCredits(
            $userId, 
            1, 
            'coaching_ai', 
            $question
        );
        
        if ($usageId) {
            // Appeler l'API IA
            $response = callChatGPTAPI($question);
            
            // Afficher la réponse
            echo $response;
        }
    } else {
        // Rediriger vers l'achat
        header('Location: buy-credits.php?insufficient_credits=1');
        exit;
    }
}
```

## Sécurité

### Mesures implémentées

- **Validation côté serveur** de toutes les données
- **Transactions SQL** pour garantir l'intégrité
- **Gestion des erreurs** sans exposition d'informations sensibles
- **Vérification des soldes** avant utilisation

### Recommandations pour la production

1. **Intégrer un vrai processeur de paiement** (Stripe, PayPal)
2. **Implémenter le chiffrement SSL** pour les données sensibles
3. **Ajouter une authentification 3D Secure**
4. **Mettre en place un système de logs** pour les transactions
5. **Implémenter des webhooks** pour les notifications

## Structure des fichiers

```
├── buy-credits.php                    # Page d'achat de crédits
├── process-credit-purchase.php        # Traitement des achats
├── credit-purchase-success.php        # Page de succès
├── my-credits.php                     # Gestion des crédits
├── install-credits.php                # Script d'installation
├── sql/
│   └── credits.sql                   # Structure de base de données
└── includes/
    └── credit_functions.php          # Fonctions de gestion
```

## Maintenance

### Tâches régulières

1. **Vérifier les transactions échouées** :
   ```sql
   SELECT * FROM credit_purchases 
   WHERE payment_status = 'failed';
   ```

2. **Analyser l'utilisation des crédits** :
   ```sql
   SELECT feature_used, COUNT(*) as usage_count 
   FROM credit_usage 
   GROUP BY feature_used;
   ```

3. **Sauvegarder les données** :
   ```bash
   mysqldump -u username -p database_name ai_credits credit_purchases credit_usage > backup_credits.sql
   ```

## Support

Pour toute question ou problème lié au système de crédits IA :

- Consultez la documentation des fonctions dans `includes/credit_functions.php`
- Vérifiez les logs d'erreur PHP pour le débogage
- Testez avec les cartes de test fournies
- Contactez le support technique

---

**Version** : 1.0  
**Date** : <?php echo date('d/m/Y'); ?>  
**Auteur** : MyFity Development Team
