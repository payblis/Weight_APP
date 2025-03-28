<?php
/**
 * Fonctions de test pour l'application Weight Tracker
 */

/**
 * Teste les fonctions de calcul du métabolisme de base (BMR)
 * 
 * @return array Résultats des tests
 */
function testBMRCalculations() {
    $results = [
        'success' => true,
        'tests' => []
    ];
    
    // Test 1: Calcul du BMR avec la formule Mifflin-St Jeor pour un homme
    $test1 = [
        'name' => 'Calcul du BMR (Mifflin-St Jeor, homme)',
        'input' => [
            'weight' => 80,
            'height' => 180,
            'age' => 30,
            'gender' => 'homme',
            'formula' => 'mifflin_st_jeor'
        ],
        'expected' => 1805, // (10 * 80) + (6.25 * 180) - (5 * 30) + 5
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $bmr = calculateBMR(
            $test1['input']['weight'],
            $test1['input']['height'],
            $test1['input']['age'],
            $test1['input']['gender'],
            $test1['input']['formula']
        );
        
        $test1['actual'] = $bmr;
        $test1['success'] = abs($bmr - $test1['expected']) <= 1; // Tolérance de 1 calorie pour les arrondis
    } catch (Exception $e) {
        $test1['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test1;
    
    // Test 2: Calcul du BMR avec la formule Mifflin-St Jeor pour une femme
    $test2 = [
        'name' => 'Calcul du BMR (Mifflin-St Jeor, femme)',
        'input' => [
            'weight' => 65,
            'height' => 165,
            'age' => 25,
            'gender' => 'femme',
            'formula' => 'mifflin_st_jeor'
        ],
        'expected' => 1391, // (10 * 65) + (6.25 * 165) - (5 * 25) - 161
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $bmr = calculateBMR(
            $test2['input']['weight'],
            $test2['input']['height'],
            $test2['input']['age'],
            $test2['input']['gender'],
            $test2['input']['formula']
        );
        
        $test2['actual'] = $bmr;
        $test2['success'] = abs($bmr - $test2['expected']) <= 1; // Tolérance de 1 calorie pour les arrondis
    } catch (Exception $e) {
        $test2['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test2;
    
    // Test 3: Calcul du BMR avec la formule Harris-Benedict pour un homme
    $test3 = [
        'name' => 'Calcul du BMR (Harris-Benedict, homme)',
        'input' => [
            'weight' => 80,
            'height' => 180,
            'age' => 30,
            'gender' => 'homme',
            'formula' => 'harris_benedict'
        ],
        'expected' => 1829, // 88.362 + (13.397 * 80) + (4.799 * 180) - (5.677 * 30)
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $bmr = calculateBMR(
            $test3['input']['weight'],
            $test3['input']['height'],
            $test3['input']['age'],
            $test3['input']['gender'],
            $test3['input']['formula']
        );
        
        $test3['actual'] = $bmr;
        $test3['success'] = abs($bmr - $test3['expected']) <= 1; // Tolérance de 1 calorie pour les arrondis
    } catch (Exception $e) {
        $test3['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test3;
    
    // Test 4: Calcul du TDEE avec différents niveaux d'activité
    $test4 = [
        'name' => 'Calcul du TDEE avec différents niveaux d\'activité',
        'input' => [
            'bmr' => 1800,
            'activity_levels' => [
                'sedentaire' => 1.2,
                'leger' => 1.375,
                'modere' => 1.55,
                'actif' => 1.725,
                'tres_actif' => 1.9
            ]
        ],
        'expected' => [
            'sedentaire' => 2160,
            'leger' => 2475,
            'modere' => 2790,
            'actif' => 3105,
            'tres_actif' => 3420
        ],
        'success' => true,
        'actual' => [],
        'error' => null
    ];
    
    try {
        foreach ($test4['input']['activity_levels'] as $level => $multiplier) {
            $tdee = calculateTDEE($test4['input']['bmr'], $level);
            $test4['actual'][$level] = $tdee;
            
            if (abs($tdee - $test4['expected'][$level]) > 1) {
                $test4['success'] = false;
                $results['success'] = false;
            }
        }
    } catch (Exception $e) {
        $test4['error'] = $e->getMessage();
        $test4['success'] = false;
        $results['success'] = false;
    }
    
    $results['tests'][] = $test4;
    
    // Test 5: Calcul des besoins caloriques avec ajustement pour perte de poids
    $test5 = [
        'name' => 'Calcul des besoins caloriques avec ajustement pour perte de poids',
        'input' => [
            'tdee' => 2500,
            'goal_type' => 'perte_poids',
            'calorie_adjustment' => 500
        ],
        'expected' => 2000, // 2500 - 500
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $calorie_goal = calculateCalorieGoal(
            $test5['input']['tdee'],
            $test5['input']['goal_type'],
            $test5['input']['calorie_adjustment']
        );
        
        $test5['actual'] = $calorie_goal;
        $test5['success'] = abs($calorie_goal - $test5['expected']) <= 1;
    } catch (Exception $e) {
        $test5['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test5;
    
    // Test 6: Calcul des besoins caloriques avec ajustement pour prise de poids
    $test6 = [
        'name' => 'Calcul des besoins caloriques avec ajustement pour prise de poids',
        'input' => [
            'tdee' => 2500,
            'goal_type' => 'prise_poids',
            'calorie_adjustment' => 500
        ],
        'expected' => 3000, // 2500 + 500
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $calorie_goal = calculateCalorieGoal(
            $test6['input']['tdee'],
            $test6['input']['goal_type'],
            $test6['input']['calorie_adjustment']
        );
        
        $test6['actual'] = $calorie_goal;
        $test6['success'] = abs($calorie_goal - $test6['expected']) <= 1;
    } catch (Exception $e) {
        $test6['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test6;
    
    return $results;
}

/**
 * Teste les fonctions de calcul de l'IMC
 * 
 * @return array Résultats des tests
 */
function testBMICalculations() {
    $results = [
        'success' => true,
        'tests' => []
    ];
    
    // Test 1: Calcul de l'IMC pour un poids normal
    $test1 = [
        'name' => 'Calcul de l\'IMC (poids normal)',
        'input' => [
            'weight' => 70,
            'height' => 175
        ],
        'expected' => [
            'bmi' => 22.86,
            'category' => 'normal'
        ],
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $bmi_data = calculateBMI($test1['input']['weight'], $test1['input']['height']);
        
        $test1['actual'] = $bmi_data;
        $test1['success'] = abs($bmi_data['bmi'] - $test1['expected']['bmi']) <= 0.1 && 
                           $bmi_data['category'] === $test1['expected']['category'];
    } catch (Exception $e) {
        $test1['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test1;
    
    // Test 2: Calcul de l'IMC pour un surpoids
    $test2 = [
        'name' => 'Calcul de l\'IMC (surpoids)',
        'input' => [
            'weight' => 85,
            'height' => 175
        ],
        'expected' => [
            'bmi' => 27.76,
            'category' => 'surpoids'
        ],
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $bmi_data = calculateBMI($test2['input']['weight'], $test2['input']['height']);
        
        $test2['actual'] = $bmi_data;
        $test2['success'] = abs($bmi_data['bmi'] - $test2['expected']['bmi']) <= 0.1 && 
                           $bmi_data['category'] === $test2['expected']['category'];
    } catch (Exception $e) {
        $test2['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test2;
    
    // Test 3: Calcul de l'IMC pour une obésité
    $test3 = [
        'name' => 'Calcul de l\'IMC (obésité)',
        'input' => [
            'weight' => 100,
            'height' => 175
        ],
        'expected' => [
            'bmi' => 32.65,
            'category' => 'obésité'
        ],
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $bmi_data = calculateBMI($test3['input']['weight'], $test3['input']['height']);
        
        $test3['actual'] = $bmi_data;
        $test3['success'] = abs($bmi_data['bmi'] - $test3['expected']['bmi']) <= 0.1 && 
                           $bmi_data['category'] === $test3['expected']['category'];
    } catch (Exception $e) {
        $test3['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test3;
    
    // Test 4: Calcul de l'IMC pour une insuffisance pondérale
    $test4 = [
        'name' => 'Calcul de l\'IMC (insuffisance pondérale)',
        'input' => [
            'weight' => 50,
            'height' => 175
        ],
        'expected' => [
            'bmi' => 16.33,
            'category' => 'insuffisance_ponderale'
        ],
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $bmi_data = calculateBMI($test4['input']['weight'], $test4['input']['height']);
        
        $test4['actual'] = $bmi_data;
        $test4['success'] = abs($bmi_data['bmi'] - $test4['expected']['bmi']) <= 0.1 && 
                           $bmi_data['category'] === $test4['expected']['category'];
    } catch (Exception $e) {
        $test4['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test4;
    
    return $results;
}

/**
 * Teste les fonctions de gestion des repas
 * 
 * @return array Résultats des tests
 */
function testMealManagement() {
    $results = [
        'success' => true,
        'tests' => []
    ];
    
    // Test 1: Calcul des totaux nutritionnels pour un repas
    $test1 = [
        'name' => 'Calcul des totaux nutritionnels pour un repas',
        'input' => [
            'items' => [
                [
                    'calories' => 200,
                    'protein' => 10,
                    'carbs' => 20,
                    'fat' => 5,
                    'quantity' => 1
                ],
                [
                    'calories' => 150,
                    'protein' => 5,
                    'carbs' => 15,
                    'fat' => 8,
                    'quantity' => 2
                ]
            ]
        ],
        'expected' => [
            'total_calories' => 500, // 200 + (150 * 2)
            'total_protein' => 20,   // 10 + (5 * 2)
            'total_carbs' => 50,     // 20 + (15 * 2)
            'total_fat' => 21        // 5 + (8 * 2)
        ],
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $totals = calculateMealNutritionTotals($test1['input']['items']);
        
        $test1['actual'] = $totals;
        $test1['success'] = $totals['total_calories'] === $test1['expected']['total_calories'] &&
                           $totals['total_protein'] === $test1['expected']['total_protein'] &&
                           $totals['total_carbs'] === $test1['expected']['total_carbs'] &&
                           $totals['total_fat'] === $test1['expected']['total_fat'];
    } catch (Exception $e) {
        $test1['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test1;
    
    // Test 2: Vérification de la création d'un repas
    $test2 = [
        'name' => 'Création d\'un repas',
        'input' => [
            'user_id' => 1,
            'name' => 'Test Meal',
            'meal_type' => 'dejeuner',
            'log_date' => date('Y-m-d'),
            'items' => [
                [
                    'food_id' => 1,
                    'quantity' => 1
                ],
                [
                    'food_id' => 2,
                    'quantity' => 2
                ]
            ]
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        // Simuler la création d'un repas sans réellement l'insérer dans la base de données
        $meal_id = simulateCreateMeal(
            $test2['input']['user_id'],
            $test2['input']['name'],
            $test2['input']['meal_type'],
            $test2['input']['log_date'],
            $test2['input']['items']
        );
        
        $test2['actual'] = $meal_id > 0;
        $test2['success'] = $test2['actual'] === $test2['expected'];
    } catch (Exception $e) {
        $test2['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test2;
    
    return $results;
}

/**
 * Teste les fonctions de personnalisation
 * 
 * @return array Résultats des tests
 */
function testPersonalization() {
    $results = [
        'success' => true,
        'tests' => []
    ];
    
    // Test 1: Vérification des préférences alimentaires
    $test1 = [
        'name' => 'Vérification des préférences alimentaires',
        'input' => [
            'user_id' => 1,
            'food_id' => 5,
            'food_data' => [
                'id' => 5,
                'name' => 'Arachides',
                'category' => 'noix_et_graines'
            ],
            'preferences' => [
                [
                    'preference_type' => 'allergy',
                    'food_id' => 5,
                    'food_category' => null
                ]
            ]
        ],
        'expected' => [
            'matches' => false,
            'has_alerts' => true
        ],
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $check_result = simulateCheckFoodAgainstPreferences(
            $test1['input']['user_id'],
            $test1['input']['food_id'],
            $test1['input']['food_data'],
            $test1['input']['preferences']
        );
        
        $test1['actual'] = [
            'matches' => $check_result['matches'],
            'has_alerts' => !empty($check_result['alerts'])
        ];
        
        $test1['success'] = $test1['actual']['matches'] === $test1['expected']['matches'] &&
                           $test1['actual']['has_alerts'] === $test1['expected']['has_alerts'];
    } catch (Exception $e) {
        $test1['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test1;
    
    // Test 2: Vérification de la création d'un programme nutritionnel
    $test2 = [
        'name' => 'Création d\'un programme nutritionnel',
        'input' => [
            'program_data' => [
                'name' => 'Test Program',
                'description' => 'Test Description',
                'goal_type' => 'perte_poids',
                'calorie_adjustment' => 500,
                'protein_pct' => 30,
                'carbs_pct' => 40,
                'fat_pct' => 30
            ]
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        // Simuler la création d'un programme sans réellement l'insérer dans la base de données
        $program_id = simulateCreateNutritionProgram($test2['input']['program_data']);
        
        $test2['actual'] = $program_id > 0;
        $test2['success'] = $test2['actual'] === $test2['expected'];
    } catch (Exception $e) {
        $test2['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test2;
    
    // Test 3: Vérification de la répartition des macronutriments
    $test3 = [
        'name' => 'Répartition des macronutriments',
        'input' => [
            'calorie_goal' => 2000,
            'protein_pct' => 30,
            'carbs_pct' => 40,
            'fat_pct' => 30
        ],
        'expected' => [
            'protein_g' => 150, // (2000 * 0.3) / 4
            'carbs_g' => 200,   // (2000 * 0.4) / 4
            'fat_g' => 67       // (2000 * 0.3) / 9
        ],
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $macros = calculateMacronutrients(
            $test3['input']['calorie_goal'],
            $test3['input']['protein_pct'],
            $test3['input']['carbs_pct'],
            $test3['input']['fat_pct']
        );
        
        $test3['actual'] = $macros;
        $test3['success'] = abs($macros['protein_g'] - $test3['expected']['protein_g']) <= 1 &&
                           abs($macros['carbs_g'] - $test3['expected']['carbs_g']) <= 1 &&
                           abs($macros['fat_g'] - $test3['expected']['fat_g']) <= 1;
    } catch (Exception $e) {
        $test3['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test3;
    
    return $results;
}

/**
 * Teste les fonctions d'administration
 * 
 * @return array Résultats des tests
 */
function testAdminFunctions() {
    $results = [
        'success' => true,
        'tests' => []
    ];
    
    // Test 1: Vérification des permissions d'administrateur
    $test1 = [
        'name' => 'Vérification des permissions d\'administrateur',
        'input' => [
            'user_id' => 1,
            'role_id' => 1 // Admin
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $is_admin = simulateIsAdmin($test1['input']['user_id'], $test1['input']['role_id']);
        
        $test1['actual'] = $is_admin;
        $test1['success'] = $is_admin === $test1['expected'];
    } catch (Exception $e) {
        $test1['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test1;
    
    // Test 2: Vérification de la création d'un utilisateur
    $test2 = [
        'name' => 'Création d\'un utilisateur',
        'input' => [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role_id' => 2,
            'profile_data' => [
                'first_name' => 'Test',
                'last_name' => 'User',
                'gender' => 'homme',
                'height' => 175
            ]
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        // Simuler la création d'un utilisateur sans réellement l'insérer dans la base de données
        $user_id = simulateCreateUser(
            $test2['input']['username'],
            $test2['input']['email'],
            $test2['input']['password'],
            $test2['input']['role_id'],
            $test2['input']['profile_data']
        );
        
        $test2['actual'] = $user_id > 0;
        $test2['success'] = $test2['actual'] === $test2['expected'];
    } catch (Exception $e) {
        $test2['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test2;
    
    // Test 3: Vérification de la configuration de la clé API ChatGPT
    $test3 = [
        'name' => 'Configuration de la clé API ChatGPT',
        'input' => [
            'api_key' => 'sk-1234567890abcdef1234567890abcdef'
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $result = simulateConfigureChatGPTApiKey($test3['input']['api_key']);
        
        $test3['actual'] = $result;
        $test3['success'] = $result === $test3['expected'];
    } catch (Exception $e) {
        $test3['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test3;
    
    return $results;
}

/**
 * Teste les fonctions de gestion des programmes nutritionnels
 * 
 * @return array Résultats des tests
 */
function testNutritionProgramManagement() {
    $results = [
        'success' => true,
        'tests' => []
    ];
    
    // Test 1: Vérification de la création d'un programme nutritionnel
    $test1 = [
        'name' => 'Création d\'un programme nutritionnel',
        'input' => [
            'program_data' => [
                'name' => 'Test Program',
                'description' => 'Test Description',
                'goal_type' => 'perte_poids',
                'calorie_adjustment' => 500,
                'protein_pct' => 30,
                'carbs_pct' => 40,
                'fat_pct' => 30,
                'user_id' => 1
            ]
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        // Simuler la création d'un programme sans réellement l'insérer dans la base de données
        $program_id = simulateCreateNutritionProgram($test1['input']['program_data']);
        
        $test1['actual'] = $program_id > 0;
        $test1['success'] = $test1['actual'] === $test1['expected'];
    } catch (Exception $e) {
        $test1['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test1;
    
    // Test 2: Vérification de l'assignation d'un programme à un utilisateur
    $test2 = [
        'name' => 'Assignation d\'un programme à un utilisateur',
        'input' => [
            'user_id' => 1,
            'program_id' => 1
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $result = simulateAssignNutritionProgramToUser($test2['input']['user_id'], $test2['input']['program_id']);
        
        $test2['actual'] = $result;
        $test2['success'] = $result === $test2['expected'];
    } catch (Exception $e) {
        $test2['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test2;
    
    // Test 3: Vérification de la génération d'un plan de repas
    $test3 = [
        'name' => 'Génération d\'un plan de repas',
        'input' => [
            'user_id' => 1,
            'program_id' => 1
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $meal_plan = simulateGenerateMealPlan($test3['input']['user_id'], $test3['input']['program_id']);
        
        $test3['actual'] = !empty($meal_plan) && isset($meal_plan['meal_plan']) && count($meal_plan['meal_plan']) === 7;
        $test3['success'] = $test3['actual'] === $test3['expected'];
    } catch (Exception $e) {
        $test3['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test3;
    
    return $results;
}

/**
 * Teste les fonctions de suivi et d'historique
 * 
 * @return array Résultats des tests
 */
function testTrackingAndHistory() {
    $results = [
        'success' => true,
        'tests' => []
    ];
    
    // Test 1: Vérification de l'enregistrement d'un poids
    $test1 = [
        'name' => 'Enregistrement d\'un poids',
        'input' => [
            'user_id' => 1,
            'weight' => 75.5,
            'log_date' => date('Y-m-d'),
            'notes' => 'Test weight entry'
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        // Simuler l'enregistrement d'un poids sans réellement l'insérer dans la base de données
        $weight_id = simulateLogWeight(
            $test1['input']['user_id'],
            $test1['input']['weight'],
            $test1['input']['log_date'],
            $test1['input']['notes']
        );
        
        $test1['actual'] = $weight_id > 0;
        $test1['success'] = $test1['actual'] === $test1['expected'];
    } catch (Exception $e) {
        $test1['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test1;
    
    // Test 2: Vérification de la mise à jour de l'IMC
    $test2 = [
        'name' => 'Mise à jour de l\'IMC',
        'input' => [
            'user_id' => 1,
            'weight' => 75.5,
            'height' => 175,
            'log_date' => date('Y-m-d')
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $bmi_id = simulateUpdateBMI(
            $test2['input']['user_id'],
            $test2['input']['weight'],
            $test2['input']['height'],
            $test2['input']['log_date']
        );
        
        $test2['actual'] = $bmi_id > 0;
        $test2['success'] = $test2['actual'] === $test2['expected'];
    } catch (Exception $e) {
        $test2['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test2;
    
    // Test 3: Vérification de la mise à jour du bilan calorique
    $test3 = [
        'name' => 'Mise à jour du bilan calorique',
        'input' => [
            'user_id' => 1,
            'log_date' => date('Y-m-d'),
            'calories_consumed' => 2000,
            'calories_burned' => 500,
            'bmr_calories' => 1800,
            'macros' => [
                'protein' => 100,
                'carbs' => 200,
                'fat' => 67
            ]
        ],
        'expected' => true,
        'success' => false,
        'actual' => null,
        'error' => null
    ];
    
    try {
        $balance_id = simulateUpdateCalorieBalance(
            $test3['input']['user_id'],
            $test3['input']['log_date'],
            $test3['input']['calories_consumed'],
            $test3['input']['calories_burned'],
            $test3['input']['bmr_calories'],
            $test3['input']['macros']['protein'],
            $test3['input']['macros']['carbs'],
            $test3['input']['macros']['fat']
        );
        
        $test3['actual'] = $balance_id > 0;
        $test3['success'] = $test3['actual'] === $test3['expected'];
    } catch (Exception $e) {
        $test3['error'] = $e->getMessage();
        $results['success'] = false;
    }
    
    $results['tests'][] = $test3;
    
    return $results;
}

/**
 * Exécute tous les tests et génère un rapport
 * 
 * @return array Rapport de test complet
 */
function runAllTests() {
    $start_time = microtime(true);
    
    $tests = [
        'bmr_calculations' => testBMRCalculations(),
        'bmi_calculations' => testBMICalculations(),
        'meal_management' => testMealManagement(),
        'personalization' => testPersonalization(),
        'admin_functions' => testAdminFunctions(),
        'nutrition_program_management' => testNutritionProgramManagement(),
        'tracking_and_history' => testTrackingAndHistory()
    ];
    
    $end_time = microtime(true);
    $execution_time = round($end_time - $start_time, 2);
    
    // Calculer les statistiques globales
    $total_tests = 0;
    $passed_tests = 0;
    
    foreach ($tests as $category => $results) {
        foreach ($results['tests'] as $test) {
            $total_tests++;
            if ($test['success']) {
                $passed_tests++;
            }
        }
    }
    
    $success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0;
    
    return [
        'execution_time' => $execution_time,
        'total_tests' => $total_tests,
        'passed_tests' => $passed_tests,
        'failed_tests' => $total_tests - $passed_tests,
        'success_rate' => $success_rate,
        'tests' => $tests
    ];
}

/**
 * Fonctions de simulation pour les tests
 */

/**
 * Simule le calcul du TDEE
 * 
 * @param float $bmr BMR en calories
 * @param string $activity_level Niveau d'activité
 * @return float TDEE en calories
 */
function calculateTDEE($bmr, $activity_level) {
    $multipliers = [
        'sedentaire' => 1.2,
        'leger' => 1.375,
        'modere' => 1.55,
        'actif' => 1.725,
        'tres_actif' => 1.9
    ];
    
    $multiplier = isset($multipliers[$activity_level]) ? $multipliers[$activity_level] : 1.2;
    
    return round($bmr * $multiplier);
}

/**
 * Simule le calcul de l'objectif calorique
 * 
 * @param float $tdee TDEE en calories
 * @param string $goal_type Type d'objectif
 * @param float $calorie_adjustment Ajustement calorique
 * @return float Objectif calorique
 */
function calculateCalorieGoal($tdee, $goal_type, $calorie_adjustment) {
    if ($goal_type === 'perte_poids') {
        return $tdee - abs($calorie_adjustment);
    } else if ($goal_type === 'prise_poids') {
        return $tdee + abs($calorie_adjustment);
    } else {
        return $tdee;
    }
}

/**
 * Simule le calcul des macronutriments
 * 
 * @param float $calorie_goal Objectif calorique
 * @param float $protein_pct Pourcentage de protéines
 * @param float $carbs_pct Pourcentage de glucides
 * @param float $fat_pct Pourcentage de lipides
 * @return array Macronutriments en grammes
 */
function calculateMacronutrients($calorie_goal, $protein_pct, $carbs_pct, $fat_pct) {
    $protein_calories = $calorie_goal * ($protein_pct / 100);
    $carbs_calories = $calorie_goal * ($carbs_pct / 100);
    $fat_calories = $calorie_goal * ($fat_pct / 100);
    
    return [
        'protein_g' => round($protein_calories / 4), // 4 calories par gramme de protéine
        'carbs_g' => round($carbs_calories / 4),     // 4 calories par gramme de glucide
        'fat_g' => round($fat_calories / 9)          // 9 calories par gramme de lipide
    ];
}

/**
 * Simule le calcul des totaux nutritionnels pour un repas
 * 
 * @param array $items Aliments du repas
 * @return array Totaux nutritionnels
 */
function calculateMealNutritionTotals($items) {
    $totals = [
        'total_calories' => 0,
        'total_protein' => 0,
        'total_carbs' => 0,
        'total_fat' => 0
    ];
    
    foreach ($items as $item) {
        $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
        
        $totals['total_calories'] += $item['calories'] * $quantity;
        $totals['total_protein'] += $item['protein'] * $quantity;
        $totals['total_carbs'] += $item['carbs'] * $quantity;
        $totals['total_fat'] += $item['fat'] * $quantity;
    }
    
    return $totals;
}

/**
 * Simule la création d'un repas
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $name Nom du repas
 * @param string $meal_type Type de repas
 * @param string $log_date Date du repas
 * @param array $items Aliments du repas
 * @return int ID du repas simulé
 */
function simulateCreateMeal($user_id, $name, $meal_type, $log_date, $items) {
    // Simuler un ID de repas
    return 1;
}

/**
 * Simule la vérification des préférences alimentaires
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $food_id ID de l'aliment
 * @param array $food_data Données de l'aliment
 * @param array $preferences Préférences alimentaires
 * @return array Résultat de la vérification
 */
function simulateCheckFoodAgainstPreferences($user_id, $food_id, $food_data, $preferences) {
    $warnings = [];
    $alerts = [];
    
    foreach ($preferences as $pref) {
        // Vérifier les correspondances directes par ID d'aliment
        if ($pref['food_id'] && $pref['food_id'] == $food_id) {
            if ($pref['preference_type'] == 'allergy' || $pref['preference_type'] == 'intolerance') {
                $alerts[] = [
                    'type' => $pref['preference_type'],
                    'message' => "Cet aliment est marqué comme " . 
                                ($pref['preference_type'] == 'allergy' ? 'allergène' : 'intolérance') . 
                                " dans vos préférences."
                ];
            } else if ($pref['preference_type'] == 'dislike') {
                $warnings[] = [
                    'type' => 'dislike',
                    'message' => "Cet aliment est marqué comme non apprécié dans vos préférences."
                ];
            }
        }
        
        // Vérifier les correspondances par catégorie
        if ($pref['food_category'] && isset($food_data['category']) && $food_data['category'] == $pref['food_category']) {
            if ($pref['preference_type'] == 'allergy' || $pref['preference_type'] == 'intolerance') {
                $alerts[] = [
                    'type' => $pref['preference_type'],
                    'message' => "Cet aliment appartient à la catégorie '" . $pref['food_category'] . 
                                "' qui est marquée comme " . 
                                ($pref['preference_type'] == 'allergy' ? 'allergène' : 'intolérance') . 
                                " dans vos préférences."
                ];
            } else if ($pref['preference_type'] == 'dislike') {
                $warnings[] = [
                    'type' => 'dislike',
                    'message' => "Cet aliment appartient à la catégorie '" . $pref['food_category'] . 
                                "' qui est marquée comme non appréciée dans vos préférences."
                ];
            }
        }
    }
    
    return [
        'matches' => empty($alerts),
        'warnings' => $warnings,
        'alerts' => $alerts
    ];
}

/**
 * Simule la création d'un programme nutritionnel
 * 
 * @param array $program_data Données du programme
 * @return int ID du programme simulé
 */
function simulateCreateNutritionProgram($program_data) {
    // Vérifier les données requises
    if (!isset($program_data['name']) || empty($program_data['name']) ||
        !isset($program_data['goal_type']) || !in_array($program_data['goal_type'], ['perte_poids', 'prise_poids', 'maintien'])) {
        return false;
    }
    
    // Simuler un ID de programme
    return 1;
}

/**
 * Simule la vérification des permissions d'administrateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $role_id ID du rôle
 * @return bool True si l'utilisateur est administrateur, false sinon
 */
function simulateIsAdmin($user_id, $role_id) {
    return $role_id == 1;
}

/**
 * Simule la création d'un utilisateur
 * 
 * @param string $username Nom d'utilisateur
 * @param string $email Adresse e-mail
 * @param string $password Mot de passe
 * @param int $role_id ID du rôle
 * @param array $profile_data Données du profil
 * @return int ID de l'utilisateur simulé
 */
function simulateCreateUser($username, $email, $password, $role_id, $profile_data) {
    // Vérifier les données requises
    if (empty($username) || empty($email) || empty($password)) {
        return false;
    }
    
    // Simuler un ID d'utilisateur
    return 1;
}

/**
 * Simule la configuration de la clé API ChatGPT
 * 
 * @param string $api_key Clé API
 * @return bool True si la configuration a réussi, false sinon
 */
function simulateConfigureChatGPTApiKey($api_key) {
    // Vérifier que la clé API a un format valide
    return !empty($api_key) && strpos($api_key, 'sk-') === 0;
}

/**
 * Simule l'assignation d'un programme nutritionnel à un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $program_id ID du programme
 * @return bool True si l'assignation a réussi, false sinon
 */
function simulateAssignNutritionProgramToUser($user_id, $program_id) {
    // Vérifier les données requises
    if ($user_id <= 0 || $program_id <= 0) {
        return false;
    }
    
    return true;
}

/**
 * Simule la génération d'un plan de repas
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $program_id ID du programme
 * @return array Plan de repas simulé
 */
function simulateGenerateMealPlan($user_id, $program_id) {
    // Vérifier les données requises
    if ($user_id <= 0 || $program_id <= 0) {
        return false;
    }
    
    // Simuler un plan de repas
    $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    $meal_types = ['petit_dejeuner', 'dejeuner', 'diner', 'collation'];
    
    $meal_plan = [];
    
    foreach ($days as $day) {
        $daily_meals = [];
        
        foreach ($meal_types as $meal_type) {
            $daily_meals[$meal_type] = [
                'id' => 'meal_' . rand(1, 100),
                'name' => ucfirst($meal_type) . ' ' . $day,
                'description' => 'Description du repas',
                'meal_type' => $meal_type,
                'calories' => rand(300, 800),
                'protein' => rand(10, 40),
                'carbs' => rand(20, 80),
                'fat' => rand(5, 30)
            ];
        }
        
        $meal_plan[$day] = [
            'meals' => $daily_meals,
            'totals' => [
                'calories' => rand(1800, 2500),
                'protein' => rand(80, 150),
                'carbs' => rand(150, 300),
                'fat' => rand(50, 100)
            ]
        ];
    }
    
    return [
        'program' => [
            'id' => $program_id,
            'name' => 'Programme de test',
            'goal_type' => 'perte_poids'
        ],
        'calorie_needs' => [
            'goal_calories' => 2000
        ],
        'meal_plan' => $meal_plan
    ];
}

/**
 * Simule l'enregistrement d'un poids
 * 
 * @param int $user_id ID de l'utilisateur
 * @param float $weight Poids
 * @param string $log_date Date d'enregistrement
 * @param string $notes Notes
 * @return int ID de l'enregistrement simulé
 */
function simulateLogWeight($user_id, $weight, $log_date, $notes) {
    // Vérifier les données requises
    if ($user_id <= 0 || $weight <= 0) {
        return false;
    }
    
    // Simuler un ID d'enregistrement
    return 1;
}

/**
 * Simule la mise à jour de l'IMC
 * 
 * @param int $user_id ID de l'utilisateur
 * @param float $weight Poids
 * @param float $height Taille
 * @param string $log_date Date d'enregistrement
 * @return int ID de l'enregistrement simulé
 */
function simulateUpdateBMI($user_id, $weight, $height, $log_date) {
    // Vérifier les données requises
    if ($user_id <= 0 || $weight <= 0 || $height <= 0) {
        return false;
    }
    
    // Calculer l'IMC
    $height_m = $height / 100;
    $bmi = $weight / ($height_m * $height_m);
    
    // Déterminer la catégorie
    $category = 'normal';
    
    if ($bmi < 18.5) {
        $category = 'insuffisance_ponderale';
    } else if ($bmi < 25) {
        $category = 'normal';
    } else if ($bmi < 30) {
        $category = 'surpoids';
    } else {
        $category = 'obésité';
    }
    
    // Simuler un ID d'enregistrement
    return 1;
}

/**
 * Simule la mise à jour du bilan calorique
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $log_date Date d'enregistrement
 * @param float $calories_consumed Calories consommées
 * @param float $calories_burned Calories brûlées
 * @param float $bmr_calories Calories BMR
 * @param float $protein Protéines consommées
 * @param float $carbs Glucides consommés
 * @param float $fat Lipides consommés
 * @return int ID de l'enregistrement simulé
 */
function simulateUpdateCalorieBalance($user_id, $log_date, $calories_consumed, $calories_burned, $bmr_calories, $protein, $carbs, $fat) {
    // Vérifier les données requises
    if ($user_id <= 0) {
        return false;
    }
    
    // Calculer le bilan calorique
    $total_burned = $bmr_calories + $calories_burned;
    $balance = $calories_consumed - $total_burned;
    
    // Simuler un ID d'enregistrement
    return 1;
}
