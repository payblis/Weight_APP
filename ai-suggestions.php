<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$user = fetchOne($sql, [$user_id]);

// Initialiser les variables
$suggestion_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'repas';
$success_message = '';
$errors = [];

// Récupérer la clé API ChatGPT
$sql = "SELECT value FROM settings WHERE user_id = ? AND setting_name = 'chatgpt_api_key'";
$api_key_setting = fetchOne($sql, [$user_id]);
$api_key = $api_key_setting ? $api_key_setting['value'] : '';

// Récupérer le profil de l'utilisateur
$sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$profile = fetchOne($sql, [$user_id]);

// Récupérer le dernier poids enregistré
$sql = "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1";
$latest_weight = fetchOne($sql, [$user_id]);

// Récupérer l'objectif de poids actuel
$sql = "SELECT * FROM goals WHERE user_id = ? AND status = 'en_cours' ORDER BY created_at DESC LIMIT 1";
$current_goal = fetchOne($sql, [$user_id]);

// Récupérer le programme actif de l'utilisateur
$sql = "SELECT p.* FROM user_programs up 
        JOIN programs p ON up.program_id = p.id 
        WHERE up.user_id = ? AND up.status = 'actif' 
        ORDER BY up.created_at DESC LIMIT 1";
$active_program = fetchOne($sql, [$user_id]);

// Récupérer les préférences alimentaires de l'utilisateur
$sql = "SELECT * FROM food_preferences WHERE user_id = ?";
$preferences = fetchAll($sql, [$user_id]);

// Organiser les préférences par type
$favorite_foods = [];
$blacklisted_foods = [];

foreach ($preferences as $pref) {
    if ($pref['preference_type'] === 'favori') {
        if ($pref['food_id']) {
            // Récupérer le nom de l'aliment depuis la table foods
            $sql = "SELECT name FROM foods WHERE id = ?";
            $food_info = fetchOne($sql, [$pref['food_id']]);
            $favorite_foods[] = $food_info ? $food_info['name'] : 'Aliment inconnu';
        } else {
            $favorite_foods[] = $pref['custom_food'];
        }
    } elseif ($pref['preference_type'] === 'blacklist') {
        if ($pref['food_id']) {
            // Récupérer le nom de l'aliment depuis la table foods
            $sql = "SELECT name FROM foods WHERE id = ?";
            $food_info = fetchOne($sql, [$pref['food_id']]);
            $blacklisted_foods[] = $food_info ? $food_info['name'] : 'Aliment inconnu';
        } else {
            $blacklisted_foods[] = $pref['custom_food'];
        }
    }
}

// Fonction pour générer une suggestion de repas
function generateMealSuggestion($profile, $latest_weight, $current_goal, $active_program, $favorite_foods, $blacklisted_foods) {
    $suggestion = "Voici quelques suggestions de repas adaptées à votre profil :\n\n";
    
    // Déterminer l'objectif calorique
    $calorie_goal = 2000; // Valeur par défaut
    
    if ($active_program) {
        $calorie_goal = $active_program['daily_calories'];
    } elseif ($current_goal && $latest_weight) {
        // Calculer le BMR (métabolisme de base) avec la formule de Mifflin-St Jeor
        $age = isset($profile['birth_date']) ? (date('Y') - date('Y', strtotime($profile['birth_date']))) : 30;
        
        if ($profile['gender'] === 'homme') {
            $bmr = 10 * $latest_weight['weight'] + 6.25 * $profile['height'] - 5 * $age + 5;
        } else {
            $bmr = 10 * $latest_weight['weight'] + 6.25 * $profile['height'] - 5 * $age - 161;
        }
        
        // Calculer le TDEE (dépense énergétique totale quotidienne)
        $activity_factors = [
            'sedentaire' => 1.2,
            'leger' => 1.375,
            'modere' => 1.55,
            'actif' => 1.725,
            'tres_actif' => 1.9
        ];
        
        $activity_factor = $activity_factors[$profile['activity_level']] ?? 1.2;
        $tdee = $bmr * $activity_factor;
        
        // Calculer en fonction de l'objectif de poids
        if ($current_goal['target_weight'] < $latest_weight['weight']) {
            // Objectif de perte de poids (déficit de 500 calories)
            $calorie_goal = $tdee - 500;
        } elseif ($current_goal['target_weight'] > $latest_weight['weight']) {
            // Objectif de prise de poids (surplus de 500 calories)
            $calorie_goal = $tdee + 500;
        } else {
            // Objectif de maintien
            $calorie_goal = $tdee;
        }
    }
    
    // Répartir les calories sur les repas
    $breakfast_calories = round($calorie_goal * 0.25);
    $lunch_calories = round($calorie_goal * 0.35);
    $dinner_calories = round($calorie_goal * 0.3);
    $snack_calories = round($calorie_goal * 0.1);
    
    // Générer des suggestions de petit-déjeuner
    $suggestion .= "PETIT-DÉJEUNER (environ {$breakfast_calories} calories) :\n";
    $breakfast_options = [
        "Porridge d'avoine avec fruits frais et noix",
        "Œufs brouillés avec des légumes et une tranche de pain complet",
        "Yaourt grec avec granola et baies",
        "Smoothie protéiné aux fruits et épinards",
        "Pain complet avec avocat et œuf poché"
    ];
    
    // Filtrer les options en fonction des préférences
    $filtered_breakfast = filterFoodOptions($breakfast_options, $favorite_foods, $blacklisted_foods);
    
    // Ajouter 3 options aléatoires
    $suggestion .= addRandomOptions($filtered_breakfast, 3);
    
    // Générer des suggestions de déjeuner
    $suggestion .= "\nDÉJEUNER (environ {$lunch_calories} calories) :\n";
    $lunch_options = [
        "Salade de quinoa avec légumes grillés et poulet",
        "Wrap au thon et avocat avec crudités",
        "Buddha bowl avec riz brun, légumineuses et légumes",
        "Soupe de légumes avec une portion de protéines maigres",
        "Sandwich complet au poulet grillé et légumes"
    ];
    
    // Filtrer les options en fonction des préférences
    $filtered_lunch = filterFoodOptions($lunch_options, $favorite_foods, $blacklisted_foods);
    
    // Ajouter 3 options aléatoires
    $suggestion .= addRandomOptions($filtered_lunch, 3);
    
    // Générer des suggestions de dîner
    $suggestion .= "\nDÎNER (environ {$dinner_calories} calories) :\n";
    $dinner_options = [
        "Saumon grillé avec légumes rôtis et quinoa",
        "Poulet aux herbes avec patate douce et légumes verts",
        "Tofu sauté avec légumes et riz brun",
        "Ratatouille avec une portion de protéines maigres",
        "Curry de légumes avec lentilles et riz basmati"
    ];
    
    // Filtrer les options en fonction des préférences
    $filtered_dinner = filterFoodOptions($dinner_options, $favorite_foods, $blacklisted_foods);
    
    // Ajouter 3 options aléatoires
    $suggestion .= addRandomOptions($filtered_dinner, 3);
    
    // Générer des suggestions de collations
    $suggestion .= "\nCOLLATIONS (environ {$snack_calories} calories) :\n";
    $snack_options = [
        "Une pomme avec une cuillère à soupe de beurre d'amande",
        "Un yaourt grec nature avec des baies",
        "Une poignée de noix mélangées",
        "Des bâtonnets de légumes avec du houmous",
        "Un smoothie aux fruits"
    ];
    
    // Filtrer les options en fonction des préférences
    $filtered_snack = filterFoodOptions($snack_options, $favorite_foods, $blacklisted_foods);
    
    // Ajouter 3 options aléatoires
    $suggestion .= addRandomOptions($filtered_snack, 3);
    
    // Ajouter des conseils personnalisés
    $suggestion .= "\nCONSEILS PERSONNALISÉS :\n";
    
    if ($current_goal && $latest_weight) {
        if ($current_goal['target_weight'] < $latest_weight['weight']) {
            $suggestion .= "- Privilégiez les aliments riches en protéines pour maintenir votre masse musculaire pendant la perte de poids.\n";
            $suggestion .= "- Limitez les aliments transformés et riches en sucres ajoutés.\n";
            $suggestion .= "- Buvez beaucoup d'eau, surtout avant les repas pour augmenter la satiété.\n";
        } elseif ($current_goal['target_weight'] > $latest_weight['weight']) {
            $suggestion .= "- Augmentez progressivement votre apport calorique pour favoriser la prise de poids saine.\n";
            $suggestion .= "- Consommez des aliments denses en nutriments et en calories.\n";
            $suggestion .= "- Répartissez votre alimentation en 5-6 repas par jour.\n";
        } else {
            $suggestion .= "- Maintenez une alimentation équilibrée avec des portions contrôlées.\n";
            $suggestion .= "- Variez vos sources de protéines, glucides et lipides.\n";
            $suggestion .= "- Écoutez votre faim et votre satiété.\n";
        }
    }
    
    // Ajouter des conseils basés sur les préférences alimentaires
    if (!empty($favorite_foods)) {
        $suggestion .= "- Continuez à intégrer vos aliments préférés comme " . implode(", ", array_slice($favorite_foods, 0, 3)) . " dans votre alimentation.\n";
    }
    
    if (!empty($blacklisted_foods)) {
        $suggestion .= "- Toutes les suggestions évitent les aliments que vous n'aimez pas comme " . implode(", ", array_slice($blacklisted_foods, 0, 3)) . ".\n";
    }
    
    return $suggestion;
}

// Fonction pour générer une suggestion d'exercice
function generateExerciseSuggestion($profile, $latest_weight, $current_goal, $active_program) {
    $suggestion = "Voici quelques suggestions d'exercices adaptées à votre profil :\n\n";
    
    // Déterminer le niveau d'activité
    $activity_level = $profile ? $profile['activity_level'] : 'modere';
    
    // Déterminer l'objectif
    $goal_type = 'maintien';
    if ($current_goal && $latest_weight) {
        if ($current_goal['target_weight'] < $latest_weight['weight']) {
            $goal_type = 'perte';
        } elseif ($current_goal['target_weight'] > $latest_weight['weight']) {
            $goal_type = 'prise';
        }
    }
    
    // Adapter les exercices en fonction du niveau d'activité
    switch ($activity_level) {
        case 'sedentaire':
            $suggestion .= "NIVEAU D'ACTIVITÉ : Sédentaire\n";
            $suggestion .= "Commencez doucement avec ces exercices adaptés :\n\n";
            
            $suggestion .= "EXERCICES CARDIO (3-4 fois par semaine) :\n";
            $suggestion .= "- Marche rapide : 20-30 minutes\n";
            $suggestion .= "- Natation légère : 20 minutes\n";
            $suggestion .= "- Vélo stationnaire à faible résistance : 15-20 minutes\n";
            
            $suggestion .= "\nEXERCICES DE RENFORCEMENT (2 fois par semaine) :\n";
            $suggestion .= "- Squats contre un mur : 2 séries de 10 répétitions\n";
            $suggestion .= "- Pompes modifiées (sur les genoux) : 2 séries de 5-8 répétitions\n";
            $suggestion .= "- Levées de jambes allongé : 2 séries de 10 répétitions\n";
            
            $suggestion .= "\nFLEXIBILITÉ ET MOBILITÉ (tous les jours) :\n";
            $suggestion .= "- Étirements doux : 5-10 minutes\n";
            $suggestion .= "- Yoga débutant : 10-15 minutes\n";
            break;
            
        case 'leger':
            $suggestion .= "NIVEAU D'ACTIVITÉ : Léger\n";
            $suggestion .= "Voici des exercices pour progresser à votre rythme :\n\n";
            
            $suggestion .= "EXERCICES CARDIO (3-5 fois par semaine) :\n";
            $suggestion .= "- Marche rapide/jogging léger : 30 minutes\n";
            $suggestion .= "- Vélo : 20-30 minutes\n";
            $suggestion .= "- Elliptique : 20 minutes\n";
            
            $suggestion .= "\nEXERCICES DE RENFORCEMENT (2-3 fois par semaine) :\n";
            $suggestion .= "- Squats : 3 séries de 12 répétitions\n";
            $suggestion .= "- Pompes : 3 séries de 8-10 répétitions\n";
            $suggestion .= "- Planches : 3 séries de 20-30 secondes\n";
            $suggestion .= "- Fentes : 2 séries de 10 répétitions par jambe\n";
            
            $suggestion .= "\nFLEXIBILITÉ ET MOBILITÉ (tous les jours) :\n";
            $suggestion .= "- Étirements complets : 10 minutes\n";
            $suggestion .= "- Yoga niveau intermédiaire : 15-20 minutes\n";
            break;
            
        case 'modere':
        case 'actif':
            $suggestion .= "NIVEAU D'ACTIVITÉ : " . ($activity_level === 'modere' ? 'Modéré' : 'Actif') . "\n";
            $suggestion .= "Programme d'entraînement complet pour continuer à progresser :\n\n";
            
            $suggestion .= "EXERCICES CARDIO (4-5 fois par semaine) :\n";
            $suggestion .= "- Course à pied : 30-40 minutes\n";
            $suggestion .= "- HIIT (entraînement par intervalles) : 20 minutes\n";
            $suggestion .= "- Natation : 30 minutes\n";
            $suggestion .= "- Vélo à intensité modérée/élevée : 30-45 minutes\n";
            
            $suggestion .= "\nEXERCICES DE RENFORCEMENT (3-4 fois par semaine) :\n";
            $suggestion .= "- Circuit training : 3 tours de 5 exercices\n";
            $suggestion .= "- Squats avec poids : 4 séries de 12 répétitions\n";
            $suggestion .= "- Développé couché : 4 séries de 10 répétitions\n";
            $suggestion .= "- Tractions : 3 séries de 8-10 répétitions\n";
            $suggestion .= "- Soulevé de terre : 3 séries de 10 répétitions\n";
            
            $suggestion .= "\nFLEXIBILITÉ ET RÉCUPÉRATION :\n";
            $suggestion .= "- Étirements dynamiques avant l'entraînement : 10 minutes\n";
            $suggestion .= "- Étirements statiques après l'entraînement : 10-15 minutes\n";
            $suggestion .= "- Yoga ou Pilates : 1-2 sessions par semaine\n";
            break;
            
        case 'tres_actif':
            $suggestion .= "NIVEAU D'ACTIVITÉ : Très actif\n";
            $suggestion .= "Programme avancé pour optimiser vos performances :\n\n";
            
            $suggestion .= "EXERCICES CARDIO (5-6 fois par semaine) :\n";
            $suggestion .= "- Course à pied (intervalles/fartlek) : 40-50 minutes\n";
            $suggestion .= "- HIIT avancé : 25-30 minutes\n";
            $suggestion .= "- Entraînement croisé : 45-60 minutes\n";
            $suggestion .= "- Sports d'endurance (cyclisme, natation) : 45-60 minutes\n";
            
            $suggestion .= "\nEXERCICES DE RENFORCEMENT (4-5 fois par semaine) :\n";
            $suggestion .= "- Programme de musculation split (différentes parties du corps chaque jour)\n";
            $suggestion .= "- Exercices composés lourds : 4-5 séries de 6-12 répétitions\n";
            $suggestion .= "- Exercices d'isolation : 3-4 séries de 12-15 répétitions\n";
            $suggestion .= "- Entraînement en superset ou drop set pour intensifier\n";
            
            $suggestion .= "\nRÉCUPÉRATION ET PERFORMANCE :\n";
            $suggestion .= "- Mobilité et étirements : 15-20 minutes quotidiennes\n";
            $suggestion .= "- Récupération active : 1-2 jours par semaine\n";
            $suggestion .= "- Techniques de récupération avancées : bains froids, compression, etc.\n";
            break;
    }
    
    // Ajouter des conseils spécifiques à l'objectif
    $suggestion .= "\nCONSEILS SPÉCIFIQUES À VOTRE OBJECTIF (" . strtoupper($goal_type) . " DE POIDS) :\n";
    
    switch ($goal_type) {
        case 'perte':
            $suggestion .= "- Privilégiez les exercices qui brûlent beaucoup de calories : HIIT, cardio à intensité modérée-élevée\n";
            $suggestion .= "- Maintenez l'entraînement en force pour préserver votre masse musculaire\n";
            $suggestion .= "- Visez 300-500 calories brûlées par séance d'entraînement\n";
            $suggestion .= "- Restez actif même les jours de repos (marche, étirements)\n";
            break;
            
        case 'prise':
            $suggestion .= "- Concentrez-vous sur l'entraînement en force avec des charges progressivement plus lourdes\n";
            $suggestion .= "- Limitez le cardio intensif, préférez des sessions courtes d'intensité modérée\n";
            $suggestion .= "- Reposez-vous suffisamment entre les séances (48h pour les mêmes groupes musculaires)\n";
            $suggestion .= "- Assurez-vous de consommer suffisamment de calories et de protéines après l'entraînement\n";
            break;
            
        case 'maintien':
            $suggestion .= "- Équilibrez cardio et musculation pour maintenir votre composition corporelle\n";
            $suggestion .= "- Variez régulièrement vos exercices pour éviter les plateaux\n";
            $suggestion .= "- Concentrez-vous sur l'amélioration de vos performances plutôt que sur le poids\n";
            $suggestion .= "- Intégrez des activités que vous aimez pour maintenir la motivation à long terme\n";
            break;
    }
    
    // Ajouter des conseils spécifiques au programme si disponible
    if ($active_program) {
        $suggestion .= "\nCONSEILS ADAPTÉS À VOTRE PROGRAMME (" . strtoupper($active_program['name']) . ") :\n";
        
        // Adapter les conseils en fonction du nom du programme
        if (stripos($active_program['name'], 'perte') !== false || stripos($active_program['name'], 'minceur') !== false) {
            $suggestion .= "- Combinez cardio et musculation pour maximiser la perte de graisse\n";
            $suggestion .= "- Essayez le cardio à jeun le matin pour une meilleure utilisation des graisses\n";
            $suggestion .= "- Maintenez un déficit calorique modéré pour préserver votre énergie pendant l'entraînement\n";
        } elseif (stripos($active_program['name'], 'muscle') !== false || stripos($active_program['name'], 'force') !== false) {
            $suggestion .= "- Concentrez-vous sur les exercices polyarticulaires (squat, soulevé de terre, développé)\n";
            $suggestion .= "- Assurez-vous de consommer suffisamment de protéines (1.6-2g par kg de poids corporel)\n";
            $suggestion .= "- Progressez en augmentant régulièrement les charges ou les répétitions\n";
        } elseif (stripos($active_program['name'], 'endurance') !== false || stripos($active_program['name'], 'cardio') !== false) {
            $suggestion .= "- Alternez entre entraînements longs à faible intensité et sessions courtes à haute intensité\n";
            $suggestion .= "- Travaillez votre capacité aérobie avec des séances de 45-60 minutes\n";
            $suggestion .= "- Intégrez des exercices de renforcement spécifiques aux muscles sollicités dans votre sport\n";
        }
    }
    
    return $suggestion;
}

// Fonction pour filtrer les options alimentaires en fonction des préférences
function filterFoodOptions($options, $favorite_foods, $blacklisted_foods) {
    $filtered_options = [];
    
    foreach ($options as $option) {
        $is_blacklisted = false;
        
        // Vérifier si l'option contient un aliment blacklisté
        foreach ($blacklisted_foods as $blacklisted) {
            if (stripos($option, $blacklisted) !== false) {
                $is_blacklisted = true;
                break;
            }
        }
        
        if (!$is_blacklisted) {
            $filtered_options[] = $option;
        }
    }
    
    // Si toutes les options sont blacklistées, utiliser les options originales
    if (empty($filtered_options)) {
        return $options;
    }
    
    // Trier les options pour mettre en avant celles qui contiennent des aliments favoris
    usort($filtered_options, function($a, $b) use ($favorite_foods) {
        $a_score = 0;
        $b_score = 0;
        
        foreach ($favorite_foods as $favorite) {
            if (stripos($a, $favorite) !== false) {
                $a_score++;
            }
            if (stripos($b, $favorite) !== false) {
                $b_score++;
            }
        }
        
        return $b_score - $a_score;
    });
    
    return $filtered_options;
}

// Fonction pour ajouter des options aléatoires à la suggestion
function addRandomOptions($options, $count) {
    $result = '';
    $selected = [];
    
    // Si nous avons moins d'options que demandé, utiliser toutes les options disponibles
    if (count($options) <= $count) {
        foreach ($options as $option) {
            $result .= "- {$option}\n";
        }
        return $result;
    }
    
    // Sélectionner des options aléatoires
    $indices = array_rand($options, $count);
    if (!is_array($indices)) {
        $indices = [$indices];
    }
    
    foreach ($indices as $index) {
        $result .= "- {$options[$index]}\n";
    }
    
    return $result;
}

// Traitement de la demande de suggestion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suggestion_type = sanitizeInput($_POST['suggestion_type'] ?? 'repas');
    
    // Vérifier si le type de suggestion est valide
    if (!in_array($suggestion_type, ['repas', 'exercice', 'programme'])) {
        $errors[] = "Type de suggestion invalide";
    } else {
        try {
            // Générer la suggestion en fonction du type
            $suggestion_content = '';
            
            switch ($suggestion_type) {
                case 'repas':
                    $suggestion_content = generateMealSuggestion($profile, $latest_weight, $current_goal, $active_program, $favorite_foods, $blacklisted_foods);
                    break;
                    
                case 'exercice':
                    $suggestion_content = generateExerciseSuggestion($profile, $latest_weight, $current_goal, $active_program);
                    break;
                    
                case 'programme':
                    // Cette fonctionnalité serait implémentée avec l'API ChatGPT
                    $suggestion_content = "Programme personnalisé basé sur votre profil et vos objectifs.\n\n";
                    $suggestion_content .= "Cette fonctionnalité utilise l'API ChatGPT pour générer un programme complet adapté à vos besoins spécifiques.\n\n";
                    $suggestion_content .= "Pour l'utiliser, veuillez configurer votre clé API dans la page des préférences.";
                    break;
            }
            
            // Enregistrer la suggestion dans la base de données
            $sql = "INSERT INTO ai_suggestions (user_id, suggestion_type, content, created_at) VALUES (?, ?, ?, NOW())";
            $result = insert($sql, [$user_id, $suggestion_type, $suggestion_content]);
            
            if ($result) {
                $success_message = "Votre suggestion a été générée avec succès !";
            } else {
                $errors[] = "Une erreur s'est produite lors de l'enregistrement de la suggestion. Veuillez réessayer.";
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur s'est produite: " . $e->getMessage();
            error_log("Erreur dans ai-suggestions.php: " . $e->getMessage());
        }
    }
}

// Récupérer les suggestions existantes
$sql = "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y à %H:%i') as formatted_date FROM ai_suggestions 
        WHERE user_id = ? AND suggestion_type = ? 
        ORDER BY created_at DESC 
        LIMIT 10";
$suggestions = fetchAll($sql, [$user_id, $suggestion_type]);

// Traitement de l'application d'une suggestion
if (isset($_GET['action']) && $_GET['action'] === 'apply' && isset($_GET['id'])) {
    $suggestion_id = (int)$_GET['id'];
    
    // Vérifier si la suggestion appartient à l'utilisateur
    $sql = "SELECT * FROM ai_suggestions WHERE id = ? AND user_id = ?";
    $suggestion = fetchOne($sql, [$suggestion_id, $user_id]);
    
    if ($suggestion) {
        // Marquer la suggestion comme appliquée
        $sql = "UPDATE ai_suggestions SET is_applied = 1, updated_at = NOW() WHERE id = ?";
        $result = update($sql, [$suggestion_id]);
        
        if ($result) {
            $success_message = "La suggestion a été marquée comme appliquée !";
            
            // Récupérer les suggestions mises à jour
            $sql = "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y à %H:%i') as formatted_date FROM ai_suggestions 
                    WHERE user_id = ? AND suggestion_type = ? 
                    ORDER BY created_at DESC 
                    LIMIT 10";
            $suggestions = fetchAll($sql, [$user_id, $suggestion_type]);
        } else {
            $errors[] = "Une erreur s'est produite lors de la mise à jour de la suggestion. Veuillez réessayer.";
        }
    } else {
        $errors[] = "Suggestion non trouvée ou vous n'êtes pas autorisé à la modifier.";
    }
}

// Traitement de la suppression d'une suggestion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $suggestion_id = (int)$_GET['id'];
    
    // Vérifier si la suggestion appartient à l'utilisateur
    $sql = "SELECT * FROM ai_suggestions WHERE id = ? AND user_id = ?";
    $suggestion = fetchOne($sql, [$suggestion_id, $user_id]);
    
    if ($suggestion) {
        // Supprimer la suggestion
        $sql = "DELETE FROM ai_suggestions WHERE id = ?";
        $result = update($sql, [$suggestion_id]); // Utiliser update pour exécuter la requête DELETE
        
        if ($result) {
            $success_message = "La suggestion a été supprimée avec succès !";
            
            // Récupérer les suggestions mises à jour
            $sql = "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y à %H:%i') as formatted_date FROM ai_suggestions 
                    WHERE user_id = ? AND suggestion_type = ? 
                    ORDER BY created_at DESC 
                    LIMIT 10";
            $suggestions = fetchAll($sql, [$user_id, $suggestion_type]);
        } else {
            $errors[] = "Une erreur s'est produite lors de la suppression de la suggestion. Veuillez réessayer.";
        }
    } else {
        $errors[] = "Suggestion non trouvée ou vous n'êtes pas autorisé à la supprimer.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggestions IA - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Contenu principal -->
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-4">Suggestions IA</h1>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="preferences.php" class="btn btn-primary">
                    <i class="fas fa-cog me-1"></i>Préférences
                </a>
                <a href="food-management.php" class="btn btn-success">
                    <i class="fas fa-apple-alt me-1"></i>Aliments
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Onglets de navigation -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'repas' ? 'active' : ''; ?>" href="ai-suggestions.php?type=repas">
                    <i class="fas fa-utensils me-1"></i>Suggestions de repas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'exercice' ? 'active' : ''; ?>" href="ai-suggestions.php?type=exercice">
                    <i class="fas fa-running me-1"></i>Suggestions d'exercices
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $suggestion_type === 'programme' ? 'active' : ''; ?>" href="ai-suggestions.php?type=programme">
                    <i class="fas fa-calendar-alt me-1"></i>Programmes personnalisés
                </a>
            </li>
        </ul>

        <div class="row">
            <!-- Formulaire de génération de suggestion -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Générer une suggestion</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($api_key) && $suggestion_type === 'programme'): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Vous devez configurer votre clé API ChatGPT pour utiliser cette fonctionnalité.
                                <a href="preferences.php" class="alert-link">Configurer maintenant</a>
                            </div>
                        <?php else: ?>
                            <form method="post" action="ai-suggestions.php">
                                <input type="hidden" name="suggestion_type" value="<?php echo htmlspecialchars($suggestion_type); ?>">
                                
                                <p>
                                    <?php if ($suggestion_type === 'repas'): ?>
                                        Générez des suggestions de repas adaptées à votre profil, vos objectifs et vos préférences alimentaires.
                                    <?php elseif ($suggestion_type === 'exercice'): ?>
                                        Générez des suggestions d'exercices adaptées à votre niveau d'activité et vos objectifs de poids.
                                    <?php else: ?>
                                        Générez un programme personnalisé complet basé sur votre profil, vos objectifs et vos préférences.
                                    <?php endif; ?>
                                </p>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-robot me-1"></i>Générer une suggestion
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <h6>Informations utilisées :</h6>
                            <ul class="small">
                                <?php if ($profile): ?>
                                    <li>Profil : <?php echo $profile['gender'] === 'homme' ? 'Homme' : 'Femme'; ?>, <?php echo isset($profile['birth_date']) ? (date('Y') - date('Y', strtotime($profile['birth_date']))) : '?'; ?> ans</li>
                                <?php else: ?>
                                    <li>Profil : Non renseigné</li>
                                <?php endif; ?>
                                
                                <?php if ($latest_weight): ?>
                                    <li>Poids actuel : <?php echo number_format($latest_weight['weight'], 1); ?> kg</li>
                                <?php else: ?>
                                    <li>Poids actuel : Non renseigné</li>
                                <?php endif; ?>
                                
                                <?php if ($current_goal): ?>
                                    <li>Objectif : <?php echo number_format($current_goal['target_weight'], 1); ?> kg</li>
                                <?php else: ?>
                                    <li>Objectif : Non défini</li>
                                <?php endif; ?>
                                
                                <?php if ($active_program): ?>
                                    <li>Programme : <?php echo htmlspecialchars($active_program['name']); ?></li>
                                <?php else: ?>
                                    <li>Programme : Aucun</li>
                                <?php endif; ?>
                                
                                <li>Préférences : <?php echo count($favorite_foods) + count($blacklisted_foods); ?> aliments configurés</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Liste des suggestions -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <?php 
                            if ($suggestion_type === 'repas') {
                                echo 'Suggestions de repas';
                            } elseif ($suggestion_type === 'exercice') {
                                echo 'Suggestions d\'exercices';
                            } else {
                                echo 'Programmes personnalisés';
                            }
                            ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($suggestions)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Aucune suggestion disponible. Utilisez le formulaire pour en générer.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="suggestionsAccordion">
                                <?php foreach ($suggestions as $index => $suggestion): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <span>Suggestion du <?php echo $suggestion['formatted_date']; ?></span>
                                                    <?php if ($suggestion['is_applied']): ?>
                                                        <span class="badge bg-success ms-2">Appliqué</span>
                                                    <?php endif; ?>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#suggestionsAccordion">
                                            <div class="accordion-body">
                                                <div class="mb-3">
                                                    <?php echo nl2br(htmlspecialchars($suggestion['content'])); ?>
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <?php if (!$suggestion['is_applied']): ?>
                                                        <a href="ai-suggestions.php?type=<?php echo urlencode($suggestion_type); ?>&action=apply&id=<?php echo $suggestion['id']; ?>" class="btn btn-sm btn-success me-2">
                                                            <i class="fas fa-check me-1"></i>Marquer comme appliqué
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="ai-suggestions.php?type=<?php echo urlencode($suggestion_type); ?>&action=delete&id=<?php echo $suggestion['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette suggestion ?');">
                                                        <i class="fas fa-trash me-1"></i>Supprimer
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Informations sur l'utilisation des suggestions -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Comment utiliser les suggestions IA</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6><i class="fas fa-utensils me-1"></i>Suggestions de repas</h6>
                        <p>
                            Les suggestions de repas sont générées en fonction de votre profil, vos objectifs de poids et vos préférences alimentaires.
                            Elles tiennent compte des aliments que vous aimez et évitent ceux que vous n'aimez pas.
                        </p>
                        <p>
                            <strong>Conseil :</strong> Ajoutez plus de préférences alimentaires pour obtenir des suggestions plus personnalisées.
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-running me-1"></i>Suggestions d'exercices</h6>
                        <p>
                            Les suggestions d'exercices sont adaptées à votre niveau d'activité et vos objectifs.
                            Elles proposent un équilibre entre cardio, renforcement musculaire et flexibilité.
                        </p>
                        <p>
                            <strong>Conseil :</strong> Mettez à jour votre niveau d'activité dans votre profil pour des suggestions plus précises.
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-calendar-alt me-1"></i>Programmes personnalisés</h6>
                        <p>
                            Les programmes personnalisés combinent nutrition et exercice pour créer un plan complet adapté à vos objectifs.
                            Cette fonctionnalité utilise l'API ChatGPT pour une personnalisation avancée.
                        </p>
                        <p>
                            <strong>Conseil :</strong> Configurez votre clé API dans les préférences pour utiliser cette fonctionnalité.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
