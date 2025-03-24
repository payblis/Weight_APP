<?php
// Configuration de l'interface utilisateur
define('USER_DASHBOARD_SECTIONS', [
    'summary' => [
        'title' => 'Résumé',
        'icon' => 'fas fa-chart-line'
    ],
    'weight' => [
        'title' => 'Suivi du poids',
        'icon' => 'fas fa-weight'
    ],
    'food' => [
        'title' => 'Journal alimentaire',
        'icon' => 'fas fa-utensils'
    ],
    'exercise' => [
        'title' => 'Activités physiques',
        'icon' => 'fas fa-dumbbell'
    ],
    'goals' => [
        'title' => 'Objectifs',
        'icon' => 'fas fa-bullseye'
    ],
    'achievements' => [
        'title' => 'Badges',
        'icon' => 'fas fa-trophy'
    ],
    'ai_coach' => [
        'title' => 'Coach IA',
        'icon' => 'fas fa-robot'
    ]
]);

// Configuration des objectifs
define('GOAL_TYPES', [
    'weight_loss' => 'Perte de poids',
    'weight_gain' => 'Prise de poids',
    'maintenance' => 'Maintien du poids'
]);

define('ACTIVITY_LEVELS', [
    'sedentary' => 'Sédentaire',
    'lightly_active' => 'Légèrement actif',
    'moderately_active' => 'Modérément actif',
    'very_active' => 'Très actif',
    'extra_active' => 'Extrêmement actif'
]);

// Configuration des badges
define('ACHIEVEMENT_TYPES', [
    'weight_loss' => 'Perte de poids',
    'exercise_streak' => 'Séries d\'exercices',
    'food_logging' => 'Suivi alimentaire',
    'steps_count' => 'Nombre de pas',
    'workout_completion' => 'Entraînements terminés'
]);

// Configuration des repas
define('MEAL_TYPES', [
    'breakfast' => 'Petit-déjeuner',
    'morning_snack' => 'Collation matinale',
    'lunch' => 'Déjeuner',
    'afternoon_snack' => 'Collation après-midi',
    'dinner' => 'Dîner',
    'evening_snack' => 'Collation soirée'
]);

// Configuration des exercices
define('EXERCISE_DIFFICULTY', [
    'beginner' => 'Débutant',
    'intermediate' => 'Intermédiaire',
    'advanced' => 'Avancé'
]);

// Configuration des messages d'encouragement
define('MOTIVATION_MESSAGES', [
    'weight_loss' => [
        'Chaque petit pas compte dans votre parcours !',
        'Vous êtes sur la bonne voie, continuez ainsi !',
        'La persévérance est la clé du succès !'
    ],
    'goal_reached' => [
        'Félicitations ! Vous avez atteint votre objectif !',
        'Votre détermination paie, bravo !',
        'Un objectif atteint, un nouveau défi à relever !'
    ],
    'streak' => [
        'Votre régularité est impressionnante !',
        'Maintenez cette belle série !',
        'Chaque jour qui passe vous rapproche de votre objectif !'
    ]
]);

// Configuration de l'IA
define('AI_CONVERSATION_TYPES', [
    'meal_suggestion' => 'Suggestion de repas',
    'exercise_plan' => 'Plan d\'exercices',
    'motivation' => 'Motivation',
    'health_tips' => 'Conseils santé',
    'progress_analysis' => 'Analyse des progrès'
]);

// Configuration des notifications
define('NOTIFICATION_TYPES', [
    'goal_reminder' => 'Rappel d\'objectif',
    'weight_update' => 'Mise à jour du poids',
    'meal_tracking' => 'Suivi des repas',
    'exercise_reminder' => 'Rappel d\'exercice',
    'achievement_earned' => 'Badge obtenu',
    'ai_suggestion' => 'Suggestion de l\'IA'
]);

// Configuration des graphiques
define('CHART_TYPES', [
    'weight_progress' => [
        'title' => 'Progression du poids',
        'type' => 'line'
    ],
    'calorie_intake' => [
        'title' => 'Apport calorique',
        'type' => 'bar'
    ],
    'exercise_duration' => [
        'title' => 'Durée d\'exercice',
        'type' => 'bar'
    ],
    'macronutrients' => [
        'title' => 'Répartition des macronutriments',
        'type' => 'doughnut'
    ]
]); 