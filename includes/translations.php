<?php
/**
 * Traductions manuelles pour les textes principaux
 * Utilisé en cas d'échec des APIs de traduction
 */

$translations = [
    'fr' => [
        'accueil' => 'Accueil',
        'se_connecter' => 'Se connecter',
        's_inscrire' => "S'inscrire",
        'deconnexion' => 'Déconnexion',
        'parametres' => 'Paramètres',
        'mon_accueil' => 'Mon Accueil',
        'aliments' => 'Aliments',
        'exercices' => 'Exercices',
        'rapports' => 'Rapports',
        'applis' => 'Applis',
        'communaute' => 'Communauté',
        'blog' => 'Blog',
        'premium' => 'Premium',
        'une_bonne_sante' => 'Une bonne santé, c\'est d\'abord une bonne alimentation.',
        'voulez_faire_attention' => 'Vous voulez faire plus attention à ce que vous mangez ? Faites un suivi de vos repas, apprenez-en plus sur vos habitudes et atteignez vos objectifs avec MyFity.',
        'demarrez_gratuitement' => 'Démarrez gratuitement',
        'consignez_aliments' => 'Consignez ce que vous mangez grâce aux plus de 14 millions d\'aliments.',
        'analyse_calories' => 'Consultez l\'analyse des calories et des nutriments, comparez les portions et découvrez comment les aliments que vous consommez soutiennent vos objectifs.',
        'commencer_suivre' => 'Commencer à suivre',
        'outils_objectifs' => 'Les outils pour vos objectifs',
        'apprendre_suivre_progresser' => 'Apprendre. Suivre. Progresser.',
        'journal_alimentaire' => 'Tenir un journal alimentaire vous permet de mieux comprendre vos habitudes et accroît vos chances d\'atteindre vos objectifs.',
        'consigner_facilement' => 'Consigner plus facilement.',
        'codes_barres' => 'Numérisez des codes-barres, enregistrez des repas et recettes, et utilisez Outils rapide pour un suivi alimentaire facile et rapide.',
        'garder_motivation' => 'Garder la motivation.',
        'communaute_fitness' => 'Rejoignez la plus grande communauté de fitness au monde pour profiter de conseils et astuces, ainsi que d\'une assistance 24/7.',
        'debuter_voyage' => 'DÉBUTEZ VOTRE VOYAGE DÈS AUJOURD\'HUI',
        'rejoignez_utilisateurs' => 'Rejoignez des milliers d\'utilisateurs qui ont déjà transformé leur vie avec MyFity.',
        'demarrer_gratuitement' => 'Démarrer gratuitement'
    ],
    'en' => [
        'accueil' => 'Home',
        'se_connecter' => 'Login',
        's_inscrire' => 'Sign up',
        'deconnexion' => 'Logout',
        'parametres' => 'Settings',
        'mon_accueil' => 'My Dashboard',
        'aliments' => 'Food',
        'exercices' => 'Exercises',
        'rapports' => 'Reports',
        'applis' => 'Apps',
        'communaute' => 'Community',
        'blog' => 'Blog',
        'premium' => 'Premium',
        'une_bonne_sante' => 'Good health starts with good nutrition.',
        'voulez_faire_attention' => 'Want to pay more attention to what you eat? Track your meals, learn more about your habits and reach your goals with MyFity.',
        'demarrez_gratuitement' => 'Start for free',
        'consignez_aliments' => 'Log what you eat with over 14 million foods.',
        'analyse_calories' => 'Check calorie and nutrient analysis, compare portions and discover how the foods you eat support your goals.',
        'commencer_suivre' => 'Start tracking',
        'outils_objectifs' => 'Tools for your goals',
        'apprendre_suivre_progresser' => 'Learn. Track. Progress.',
        'journal_alimentaire' => 'Keeping a food diary helps you better understand your habits and increases your chances of reaching your goals.',
        'consigner_facilement' => 'Log more easily.',
        'codes_barres' => 'Scan barcodes, record meals and recipes, and use Quick Tools for easy and fast food tracking.',
        'garder_motivation' => 'Stay motivated.',
        'communaute_fitness' => 'Join the world\'s largest fitness community for tips and tricks, plus 24/7 support.',
        'debuter_voyage' => 'START YOUR JOURNEY TODAY',
        'rejoignez_utilisateurs' => 'Join thousands of users who have already transformed their lives with MyFity.',
        'demarrer_gratuitement' => 'Start for free'
    ]
];

/**
 * Fonction pour obtenir une traduction
 */
function getTranslation($key, $lang = 'fr') {
    global $translations;
    
    if (isset($translations[$lang][$key])) {
        return $translations[$lang][$key];
    }
    
    // Retourner la clé si la traduction n'existe pas
    return $key;
}

/**
 * Fonction pour traduire le contenu HTML
 */
function translateContent($content, $lang = 'fr') {
    if ($lang === 'fr') {
        return $content;
    }
    
    global $translations;
    
    // Remplacer les textes connus
    foreach ($translations['en'] as $key => $englishText) {
        $frenchText = $translations['fr'][$key];
        $content = str_replace($frenchText, $englishText, $content);
    }
    
    return $content;
}
?> 