// Fonctionnalité de plans alimentaires personnalisés pour l'application FitTrack
// Style MyFitnessPal avec intégration de l'IA

class MealPlanner {
    constructor() {
        this.mealPlans = [];
        this.currentPlan = null;
        this.dietTypes = [
            { id: 'balanced', name: 'Équilibré', description: 'Répartition équilibrée des macronutriments' },
            { id: 'low-carb', name: 'Faible en glucides', description: 'Moins de 25% de calories provenant des glucides' },
            { id: 'high-protein', name: 'Riche en protéines', description: 'Plus de 30% de calories provenant des protéines' },
            { id: 'keto', name: 'Cétogène', description: 'Très faible en glucides, riche en lipides' },
            { id: 'vegetarian', name: 'Végétarien', description: 'Sans viande ni poisson' },
            { id: 'vegan', name: 'Végétalien', description: 'Sans produits d\'origine animale' },
            { id: 'mediterranean', name: 'Méditerranéen', description: 'Basé sur le régime méditerranéen' }
        ];
        this.goals = [
            { id: 'weight-loss', name: 'Perte de poids', description: 'Déficit calorique pour perdre du poids' },
            { id: 'maintenance', name: 'Maintien du poids', description: 'Équilibre calorique pour maintenir le poids' },
            { id: 'muscle-gain', name: 'Prise de muscle', description: 'Surplus calorique pour développer la masse musculaire' }
        ];
    }

    // Initialiser le planificateur de repas
    init(containerId = 'meal-planner-container') {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Container not found');
            return false;
        }

        // Charger les données depuis le stockage local
        this.loadData();
        
        // Créer les éléments de l'interface
        this.createElements();
        
        // Ajouter les écouteurs d'événements
        this.addEventListeners();
        
        return true;
    }

    // Créer les éléments de l'interface
    createElements() {
        // Créer le conteneur principal
        const mainContainer = document.createElement('div');
        mainContainer.className = 'meal-planner-main';
        
        // Créer l'en-tête
        const header = document.createElement('div');
        header.className = 'meal-planner-header';
        
        const title = document.createElement('h2');
        title.textContent = 'Plans alimentaires personnalisés';
        
        const subtitle = document.createElement('p');
        subtitle.className = 'text-muted';
        subtitle.textContent = 'Obtenez des plans de repas adaptés à vos objectifs et préférences';
        
        header.appendChild(title);
        header.appendChild(subtitle);
        
        // Créer le conteneur des plans existants
        const existingPlansContainer = document.createElement('div');
        existingPlansContainer.className = 'existing-plans-container mt-4';
        existingPlansContainer.id = 'existing-plans-container';
        
        const existingPlansTitle = document.createElement('h4');
        existingPlansTitle.textContent = 'Mes plans alimentaires';
        
        const existingPlansList = document.createElement('div');
        existingPlansList.className = 'existing-plans-list mt-3';
        existingPlansList.id = 'existing-plans-list';
        
        existingPlansContainer.appendChild(existingPlansTitle);
        existingPlansContainer.appendChild(existingPlansList);
        
        // Créer le bouton pour créer un nouveau plan
        const createPlanBtn = document.createElement('button');
        createPlanBtn.className = 'btn btn-primary mt-3';
        createPlanBtn.id = 'create-plan-btn';
        createPlanBtn.innerHTML = '<i class="fas fa-plus mr-1"></i> Créer un nouveau plan';
        
        // Créer le conteneur du plan actuel
        const currentPlanContainer = document.createElement('div');
        currentPlanContainer.className = 'current-plan-container mt-4';
        currentPlanContainer.id = 'current-plan-container';
        currentPlanContainer.style.display = 'none';
        
        // Ajouter tous les éléments au conteneur principal
        mainContainer.appendChild(header);
        mainContainer.appendChild(existingPlansContainer);
        mainContainer.appendChild(createPlanBtn);
        mainContainer.appendChild(currentPlanContainer);
        
        // Ajouter le conteneur principal au conteneur de la page
        this.container.appendChild(mainContainer);
        
        // Créer le modal de création de plan
        this.createPlanModal();
        
        // Afficher les plans existants
        this.displayExistingPlans();
    }

    // Créer le modal de création de plan
    createPlanModal() {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'create-plan-modal';
        modal.style.display = 'none';
        
        let dietOptionsHtml = '';
        this.dietTypes.forEach(diet => {
            dietOptionsHtml += `
                <div class="diet-option">
                    <input type="radio" name="diet-type" id="diet-${diet.id}" value="${diet.id}">
                    <label for="diet-${diet.id}">
                        <div class="diet-name">${diet.name}</div>
                        <div class="diet-description">${diet.description}</div>
                    </label>
                </div>
            `;
        });
        
        let goalOptionsHtml = '';
        this.goals.forEach(goal => {
            goalOptionsHtml += `
                <div class="goal-option">
                    <input type="radio" name="goal" id="goal-${goal.id}" value="${goal.id}">
                    <label for="goal-${goal.id}">
                        <div class="goal-name">${goal.name}</div>
                        <div class="goal-description">${goal.description}</div>
                    </label>
                </div>
            `;
        });
        
        modal.innerHTML = `
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Créer un plan alimentaire personnalisé</h3>
                    <button class="modal-close" id="close-create-plan-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="plan-name">Nom du plan</label>
                        <input type="text" id="plan-name" class="form-control" placeholder="Ex: Mon plan de perte de poids">
                    </div>
                    
                    <div class="form-group mt-3">
                        <label>Type de régime</label>
                        <div class="diet-options">
                            ${dietOptionsHtml}
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label>Objectif</label>
                        <div class="goal-options">
                            ${goalOptionsHtml}
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label>Préférences alimentaires</label>
                        <div class="preferences-container">
                            <div class="preference-item">
                                <label for="exclude-foods">Aliments à exclure</label>
                                <input type="text" id="exclude-foods" class="form-control" placeholder="Ex: lactose, gluten, arachides">
                            </div>
                            <div class="preference-item mt-2">
                                <label for="include-foods">Aliments à privilégier</label>
                                <input type="text" id="include-foods" class="form-control" placeholder="Ex: légumes verts, poisson, fruits">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label>Nombre de repas par jour</label>
                        <select id="meals-per-day" class="form-control">
                            <option value="3">3 repas</option>
                            <option value="4">4 repas</option>
                            <option value="5">5 repas</option>
                            <option value="6">6 repas</option>
                        </select>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label>Durée du plan</label>
                        <select id="plan-duration" class="form-control">
                            <option value="7">1 semaine</option>
                            <option value="14">2 semaines</option>
                            <option value="28">4 semaines</option>
                        </select>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label>Calories quotidiennes</label>
                        <div class="d-flex align-items-center">
                            <input type="range" id="calories-slider" class="form-control-range" min="1200" max="3500" step="100" value="2000">
                            <span id="calories-value" class="ml-2">2000 cal</span>
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="use-ai" checked>
                            <label class="custom-control-label" for="use-ai">Utiliser l'IA pour générer des recommandations personnalisées</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" id="cancel-create-plan">Annuler</button>
                    <button class="btn btn-primary" id="generate-plan">Générer le plan</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }

    // Ajouter les écouteurs d'événements
    addEventListeners() {
        // Bouton pour créer un nouveau plan
        const createPlanBtn = document.getElementById('create-plan-btn');
        if (createPlanBtn) {
            createPlanBtn.addEventListener('click', () => this.showCreatePlanModal());
        }
        
        // Modal de création de plan
        const closeCreatePlanModal = document.getElementById('close-create-plan-modal');
        if (closeCreatePlanModal) {
            closeCreatePlanModal.addEventListener('click', () => this.hideCreatePlanModal());
        }
        
        const cancelCreatePlan = document.getElementById('cancel-create-plan');
        if (cancelCreatePlan) {
            cancelCreatePlan.addEventListener('click', () => this.hideCreatePlanModal());
        }
        
        const generatePlan = document.getElementById('generate-plan');
        if (generatePlan) {
            generatePlan.addEventListener('click', () => this.generateMealPlan());
        }
        
        // Slider de calories
        const caloriesSlider = document.getElementById('calories-slider');
        if (caloriesSlider) {
            caloriesSlider.addEventListener('input', (e) => {
                document.getElementById('calories-value').textContent = `${e.target.value} cal`;
            });
        }
    }

    // Afficher le modal de création de plan
    showCreatePlanModal() {
        const modal = document.getElementById('create-plan-modal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    // Masquer le modal de création de plan
    hideCreatePlanModal() {
        const modal = document.getElementById('create-plan-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Générer un plan de repas
    generateMealPlan() {
        // Récupérer les valeurs du formulaire
        const planName = document.getElementById('plan-name').value;
        const dietType = document.querySelector('input[name="diet-type"]:checked')?.value;
        const goal = document.querySelector('input[name="goal"]:checked')?.value;
        const excludeFoods = document.getElementById('exclude-foods').value;
        const includeFoods = document.getElementById('include-foods').value;
        const mealsPerDay = document.getElementById('meals-per-day').value;
        const planDuration = document.getElementById('plan-duration').value;
        const calories = document.getElementById('calories-slider').value;
        const useAI = document.getElementById('use-ai').checked;
        
        // Vérifier que les champs obligatoires sont remplis
        if (!planName || !dietType || !goal) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }
        
        // Créer un nouvel ID pour le plan
        const planId = `plan-${Date.now()}`;
        
        // Créer le plan
        const plan = {
            id: planId,
            name: planName,
            dietType: dietType,
            goal: goal,
            excludeFoods: excludeFoods,
            includeFoods: includeFoods,
            mealsPerDay: parseInt(mealsPerDay),
            duration: parseInt(planDuration),
            calories: parseInt(calories),
            useAI: useAI,
            createdAt: new Date().toISOString(),
            days: []
        };
        
        // Générer les jours du plan
        for (let i = 0; i < plan.duration; i++) {
            const day = {
                dayNumber: i + 1,
                meals: []
            };
            
            // Générer les repas pour chaque jour
            for (let j = 0; j < plan.mealsPerDay; j++) {
                let mealType;
                switch (j) {
                    case 0:
                        mealType = 'breakfast';
                        break;
                    case 1:
                        mealType = 'lunch';
                        break;
                    case 2:
                        mealType = 'dinner';
                        break;
                    default:
                        mealType = 'snack';
                }
                
                const meal = {
                    mealType: mealType,
                    foods: this.generateFoodsForMeal(mealType, plan)
                };
                
                day.meals.push(meal);
            }
            
            plan.days.push(day);
        }
        
        // Ajouter le plan à la liste des plans
        this.mealPlans.push(plan);
        
        // Définir comme plan actuel
        this.currentPlan = plan;
        
        // Sauvegarder les données
        this.saveData();
        
        // Masquer le modal
        this.hideCreatePlanModal();
        
        // Afficher les plans existants
        this.displayExistingPlans();
        
        // Afficher le plan actuel
        this.displayCurrentPlan();
        
        // Si l'IA est activée, simuler une génération plus personnalisée
        if (useAI) {
            this.simulateAIGeneration(plan);
        }
    }

    // Simuler une génération par IA
    simulateAIGeneration(plan) {
        // Afficher un message de chargement
        const currentPlanContainer = document.getElementById('current-plan-container');
        if (currentPlanContainer) {
            currentPlanContainer.innerHTML = `
                <div class="ai-loading-container text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Chargement...</span>
                    </div>
                    <h4 class="mt-3">L'IA génère votre plan personnalisé...</h4>
                    <p class="text-muted">Cela peut prendre quelques instants</p>
                </div>
            `;
        }
        
        // Simuler un délai pour l'IA
        setTimeout(() => {
            // Améliorer le plan avec des suggestions plus personnalisées
            this.enhancePlanWithAI(plan);
            
            // Mettre à jour le plan dans la liste
            const index = this.mealPlans.findIndex(p => p.id === plan.id);
            if (index !== -1) {
                this.mealPlans[index] = plan;
            }
            
            // Mettre à jour le plan actuel
            this.currentPlan = plan;
            
            // Sauvegarder les données
            this.saveData();
            
            // Afficher le plan amélioré
            this.displayCurrentPlan();
        }, 3000);
    }

    // Améliorer le plan avec l'IA
    enhancePlanWithAI(plan) {
        // Simuler des améliorations basées sur l'IA
        // Dans une implémentation réelle, cela ferait appel à l'API ChatGPT
        
        // Ajouter des conseils personnalisés
        plan.aiTips = [
            "Buvez au moins 2 litres d'eau par jour pour optimiser votre métabolisme",
            "Essayez de manger lentement pour mieux ressentir la satiété",
            "Les repas riches en protéines vous aideront à maintenir votre masse musculaire"
        ];
        
        // Ajouter des alternatives pour chaque repas
        plan.days.forEach(day => {
            day.meals.forEach(meal => {
                meal.alternatives = this.generateAlternativeFoods(meal.mealType, plan);
            });
        });
        
        // Ajouter une analyse nutritionnelle
        plan.nutritionAnalysis = {
            averageProtein: Math.round(plan.calories * 0.3 / 4), // 30% des calories en protéines
            averageCarbs: Math.round(plan.calories * 0.4 / 4),   // 40% des calories en glucides
            averageFat: Math.round(plan.calories * 0.3 / 9),     // 30% des calories en lipides
            fiberGoal: 25,
            waterGoal: 2000 // ml
        };
        
        // Ajouter des recommandations basées sur l'analyse morphologique
        if (plan.goal === 'weight-loss') {
            plan.bodyAnalysisRecommendations = [
                "D'après votre analyse morphologique, concentrez-vous sur des exercices ciblant la zone abdominale",
                "Privilégiez les aliments riches en fibres pour favoriser la satiété",
                "Limitez les glucides simples le soir pour optimiser la perte de graisse pendant le sommeil"
            ];
        } else if (plan.goal === 'muscle-gain') {
            plan.bodyAnalysisRecommendations = [
                "D'après votre analyse morphologique, augmentez les exercices pour les groupes musculaires supérieurs",
                "Consommez des protéines dans l'heure suivant votre entraînement",
                "Privilégiez les glucides complexes avant l'effort pour maximiser l'énergie disponible"
            ];
        }
        
        return plan;
    }

    // Générer des aliments pour un repas
    generateFoodsForMeal(mealType, plan) {
        // Simuler la génération d'aliments en fonction du type de repas et du plan
        // Dans une implémentation réelle, cela ferait appel à une base de données d'aliments
        
        const foods = [];
        let totalCalories = 0;
        const targetCalories = this.getMealCaloriesTarget(mealType, plan.calories, plan.mealsPerDay);
        
        // Générer des aliments jusqu'à atteindre les calories cibles
        while (totalCalories < targetCalories * 0.9) {
            const food = this.getRandomFoodForMealType(mealType, plan.dietType);
            
            // Ajuster la portion pour ne pas dépasser les calories cibles
            const remainingCalories = targetCalories - totalCalories;
            if (food.calories > remainingCalories) {
                const ratio = remainingCalories / food.calories;
                food.servingSize = Math.round(food.servingSize * ratio * 10) / 10;
                food.calories = Math.round(food.calories * ratio);
                food.protein = Math.round(food.protein * ratio * 10) / 10;
                food.carbs = Math.round(food.carbs * ratio * 10) / 10;
                food.fat = Math.round(food.fat * ratio * 10) / 10;
            }
            
            foods.push(food);
            totalCalories += food.calories;
            
            // Limiter à 5 aliments par repas
            if (foods.length >= 5) break;
        }
        
        return foods;
    }

    // Générer des alternatives pour un repas
    generateAlternativeFoods(mealType, plan) {
        // Simuler la génération d'alternatives en fonction du type de repas et du plan
        const alternatives = [];
        const targetCalories = this.getMealCaloriesTarget(mealType, plan.calories, plan.mealsPerDay);
        
        // Générer 2 alternatives
        for (let i = 0; i < 2; i++) {
            const foods = [];
            let totalCalories = 0;
            
            // Générer des aliments jusqu'à atteindre les calories cibles
            while (totalCalories < targetCalories * 0.9) {
                const food = this.getRandomFoodForMealType(mealType, plan.dietType);
                
                // Ajuster la portion pour ne pas dépasser les calories cibles
                const remainingCalories = targetCalories - totalCalories;
                if (food.calories > remainingCalories) {
                    const ratio = remainingCalories / food.calories;
                    food.servingSize = Math.round(food.servingSize * ratio * 10) / 10;
                    food.calories = Math.round(food.calories * ratio);
                    food.protein = Math.round(food.protein * ratio * 10) / 10;
                    food.carbs = Math.round(food.carbs * ratio * 10) / 10;
                    food.fat = Math.round(food.fat * ratio * 10) / 10;
                }
                
                foods.push(food);
                totalCalories += food.calories;
                
                // Limiter à 4 aliments par repas
                if (foods.length >= 4) break;
            }
            
            alternatives.push({
                name: `Alternative ${i + 1}`,
                foods: foods
            });
        }
        
        return alternatives;
    }

    // Obtenir les calories cibles pour un repas
    getMealCaloriesTarget(mealType, totalCalories, mealsPerDay) {
        // Répartir les calories en fonction du type de repas
        switch (mealType) {
            case 'breakfast':
                return Math.round(totalCalories * 0.25);
            case 'lunch':
                return Math.round(totalCalories * 0.35);
            case 'dinner':
                return Math.round(totalCalories * 0.3);
            case 'snack':
                // Répartir les calories restantes entre les collations
                const snackCount = mealsPerDay - 3;
                return snackCount > 0 ? Math.round(totalCalories * 0.1 / snackCount) : 0;
            default:
                return Math.round(totalCalories / mealsPerDay);
        }
    }

    // Obtenir un aliment aléatoire pour un type de repas
    getRandomFoodForMealType(mealType, dietType) {
        // Simuler une base de données d'aliments
        // Dans une implémentation réelle, cela ferait appel à une base de données d'aliments
        
        const breakfastFoods = [
            { name: 'Flocons d\'avoine', servingSize: 50, servingUnit: 'g', calories: 180, protein: 6, carbs: 30, fat: 3, category: 'cereals' },
            { name: 'Œufs brouillés', servingSize: 2, servingUnit: 'œufs', calories: 140, protein: 12, carbs: 1, fat: 10, category: 'eggs' },
            { name: 'Pain complet', servingSize: 2, servingUnit: 'tranches', calories: 160, protein: 8, carbs: 30, fat: 2, category: 'bread' },
            { name: 'Yaourt grec', servingSize: 150, servingUnit: 'g', calories: 130, protein: 15, carbs: 6, fat: 4, category: 'dairy' },
            { name: 'Smoothie aux fruits', servingSize: 250, servingUnit: 'ml', calories: 150, protein: 3, carbs: 30, fat: 2, category: 'fruits' },
            { name: 'Pancakes protéinés', servingSize: 3, servingUnit: 'pancakes', calories: 250, protein: 20, carbs: 25, fat: 8, category: 'cereals' },
            { name: 'Fromage blanc', servingSize: 100, servingUnit: 'g', calories: 90, protein: 10, carbs: 4, fat: 3, category: 'dairy' },
            { name: 'Fruits frais', servingSize: 150, servingUnit: 'g', calories: 80, protein: 1, carbs: 20, fat: 0, category: 'fruits' }
        ];
        
        const lunchFoods = [
            { name: 'Poulet grillé', servingSize: 150, servingUnit: 'g', calories: 250, protein: 40, carbs: 0, fat: 10, category: 'meat' },
            { name: 'Riz brun', servingSize: 100, servingUnit: 'g', calories: 110, protein: 3, carbs: 22, fat: 1, category: 'cereals' },
            { name: 'Salade verte', servingSize: 100, servingUnit: 'g', calories: 20, protein: 1, carbs: 3, fat: 0, category: 'vegetables' },
            { name: 'Saumon', servingSize: 150, servingUnit: 'g', calories: 280, protein: 35, carbs: 0, fat: 15, category: 'fish' },
            { name: 'Quinoa', servingSize: 100, servingUnit: 'g', calories: 120, protein: 4, carbs: 21, fat: 2, category: 'cereals' },
            { name: 'Légumes grillés', servingSize: 150, servingUnit: 'g', calories: 70, protein: 3, carbs: 12, fat: 2, category: 'vegetables' },
            { name: 'Pâtes complètes', servingSize: 100, servingUnit: 'g', calories: 130, protein: 5, carbs: 25, fat: 1, category: 'cereals' },
            { name: 'Tofu', servingSize: 100, servingUnit: 'g', calories: 140, protein: 15, carbs: 3, fat: 8, category: 'vegetarian' }
        ];
        
        const dinnerFoods = [
            { name: 'Steak de bœuf', servingSize: 150, servingUnit: 'g', calories: 300, protein: 45, carbs: 0, fat: 15, category: 'meat' },
            { name: 'Patate douce', servingSize: 150, servingUnit: 'g', calories: 130, protein: 2, carbs: 30, fat: 0, category: 'vegetables' },
            { name: 'Brocoli', servingSize: 100, servingUnit: 'g', calories: 35, protein: 3, carbs: 7, fat: 0, category: 'vegetables' },
            { name: 'Filet de poisson', servingSize: 150, servingUnit: 'g', calories: 180, protein: 30, carbs: 0, fat: 6, category: 'fish' },
            { name: 'Lentilles', servingSize: 100, servingUnit: 'g', calories: 115, protein: 9, carbs: 20, fat: 0, category: 'legumes' },
            { name: 'Épinards', servingSize: 100, servingUnit: 'g', calories: 25, protein: 3, carbs: 4, fat: 0, category: 'vegetables' },
            { name: 'Dinde', servingSize: 150, servingUnit: 'g', calories: 220, protein: 42, carbs: 0, fat: 5, category: 'meat' },
            { name: 'Courgettes', servingSize: 100, servingUnit: 'g', calories: 20, protein: 1, carbs: 4, fat: 0, category: 'vegetables' }
        ];
        
        const snackFoods = [
            { name: 'Amandes', servingSize: 30, servingUnit: 'g', calories: 180, protein: 6, carbs: 6, fat: 15, category: 'nuts' },
            { name: 'Pomme', servingSize: 1, servingUnit: 'moyenne', calories: 80, protein: 0, carbs: 20, fat: 0, category: 'fruits' },
            { name: 'Barre protéinée', servingSize: 1, servingUnit: 'barre', calories: 200, protein: 20, carbs: 20, fat: 5, category: 'supplements' },
            { name: 'Yaourt', servingSize: 125, servingUnit: 'g', calories: 90, protein: 5, carbs: 15, fat: 2, category: 'dairy' },
            { name: 'Fromage', servingSize: 30, servingUnit: 'g', calories: 110, protein: 7, carbs: 1, fat: 9, category: 'dairy' },
            { name: 'Banane', servingSize: 1, servingUnit: 'moyenne', calories: 105, protein: 1, carbs: 27, fat: 0, category: 'fruits' },
            { name: 'Houmous', servingSize: 50, servingUnit: 'g', calories: 120, protein: 5, carbs: 10, fat: 8, category: 'legumes' },
            { name: 'Carottes', servingSize: 100, servingUnit: 'g', calories: 40, protein: 1, carbs: 10, fat: 0, category: 'vegetables' }
        ];
        
        // Sélectionner la liste d'aliments en fonction du type de repas
        let foodList;
        switch (mealType) {
            case 'breakfast':
                foodList = breakfastFoods;
                break;
            case 'lunch':
                foodList = lunchFoods;
                break;
            case 'dinner':
                foodList = dinnerFoods;
                break;
            case 'snack':
                foodList = snackFoods;
                break;
            default:
                foodList = [...breakfastFoods, ...lunchFoods, ...dinnerFoods, ...snackFoods];
        }
        
        // Filtrer en fonction du type de régime
        if (dietType === 'vegetarian') {
            foodList = foodList.filter(food => !['meat', 'fish'].includes(food.category));
        } else if (dietType === 'vegan') {
            foodList = foodList.filter(food => !['meat', 'fish', 'dairy', 'eggs'].includes(food.category));
        } else if (dietType === 'low-carb') {
            foodList = foodList.filter(food => food.carbs < 15 || food.category === 'vegetables');
        } else if (dietType === 'keto') {
            foodList = foodList.filter(food => food.carbs < 10 && food.fat > 0);
        } else if (dietType === 'high-protein') {
            foodList = foodList.filter(food => food.protein > 5 || food.category === 'vegetables');
        }
        
        // Sélectionner un aliment aléatoire
        const randomIndex = Math.floor(Math.random() * foodList.length);
        return { ...foodList[randomIndex] };
    }

    // Afficher les plans existants
    displayExistingPlans() {
        const existingPlansList = document.getElementById('existing-plans-list');
        if (!existingPlansList) return;
        
        // Vider la liste
        existingPlansList.innerHTML = '';
        
        // Afficher un message si aucun plan
        if (this.mealPlans.length === 0) {
            existingPlansList.innerHTML = `
                <div class="no-plans text-center p-3">
                    <p>Vous n'avez pas encore de plan alimentaire</p>
                    <p class="text-muted">Créez votre premier plan en cliquant sur le bouton ci-dessous</p>
                </div>
            `;
            return;
        }
        
        // Créer une carte pour chaque plan
        this.mealPlans.forEach(plan => {
            const planCard = document.createElement('div');
            planCard.className = 'plan-card';
            planCard.dataset.id = plan.id;
            
            // Trouver le nom du type de régime
            const dietName = this.dietTypes.find(diet => diet.id === plan.dietType)?.name || plan.dietType;
            
            // Trouver le nom de l'objectif
            const goalName = this.goals.find(goal => goal.id === plan.goal)?.name || plan.goal;
            
            planCard.innerHTML = `
                <div class="plan-card-header">
                    <h4 class="plan-name">${plan.name}</h4>
                    <div class="plan-meta">
                        <span class="plan-diet">${dietName}</span>
                        <span class="plan-goal">${goalName}</span>
                    </div>
                </div>
                <div class="plan-card-body">
                    <div class="plan-stats">
                        <div class="plan-stat">
                            <div class="stat-value">${plan.calories}</div>
                            <div class="stat-label">calories/jour</div>
                        </div>
                        <div class="plan-stat">
                            <div class="stat-value">${plan.mealsPerDay}</div>
                            <div class="stat-label">repas/jour</div>
                        </div>
                        <div class="plan-stat">
                            <div class="stat-value">${plan.duration}</div>
                            <div class="stat-label">jours</div>
                        </div>
                    </div>
                    <div class="plan-actions mt-3">
                        <button class="btn btn-sm btn-primary view-plan-btn">Voir le plan</button>
                        <button class="btn btn-sm btn-outline delete-plan-btn">Supprimer</button>
                    </div>
                </div>
            `;
            
            existingPlansList.appendChild(planCard);
        });
        
        // Ajouter des écouteurs d'événements pour les boutons
        const viewPlanBtns = document.querySelectorAll('.view-plan-btn');
        viewPlanBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const planId = e.target.closest('.plan-card').dataset.id;
                this.viewPlan(planId);
            });
        });
        
        const deletePlanBtns = document.querySelectorAll('.delete-plan-btn');
        deletePlanBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const planId = e.target.closest('.plan-card').dataset.id;
                this.deletePlan(planId);
            });
        });
    }

    // Afficher un plan
    viewPlan(planId) {
        const plan = this.mealPlans.find(p => p.id === planId);
        if (!plan) return;
        
        // Définir comme plan actuel
        this.currentPlan = plan;
        
        // Afficher le plan
        this.displayCurrentPlan();
    }

    // Supprimer un plan
    deletePlan(planId) {
        // Demander confirmation
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce plan ?')) return;
        
        // Supprimer le plan
        this.mealPlans = this.mealPlans.filter(p => p.id !== planId);
        
        // Si le plan supprimé était le plan actuel, réinitialiser
        if (this.currentPlan && this.currentPlan.id === planId) {
            this.currentPlan = null;
            
            // Masquer le conteneur du plan actuel
            const currentPlanContainer = document.getElementById('current-plan-container');
            if (currentPlanContainer) {
                currentPlanContainer.style.display = 'none';
            }
        }
        
        // Sauvegarder les données
        this.saveData();
        
        // Afficher les plans existants
        this.displayExistingPlans();
    }

    // Afficher le plan actuel
    displayCurrentPlan() {
        const currentPlanContainer = document.getElementById('current-plan-container');
        if (!currentPlanContainer) return;
        
        // Vérifier qu'un plan est sélectionné
        if (!this.currentPlan) {
            currentPlanContainer.style.display = 'none';
            return;
        }
        
        // Afficher le conteneur
        currentPlanContainer.style.display = 'block';
        
        // Trouver le nom du type de régime
        const dietName = this.dietTypes.find(diet => diet.id === this.currentPlan.dietType)?.name || this.currentPlan.dietType;
        
        // Trouver le nom de l'objectif
        const goalName = this.goals.find(goal => goal.id === this.currentPlan.goal)?.name || this.currentPlan.goal;
        
        // Créer le contenu HTML
        let html = `
            <div class="current-plan-header">
                <h3>${this.currentPlan.name}</h3>
                <div class="plan-meta">
                    <span class="plan-diet">${dietName}</span>
                    <span class="plan-goal">${goalName}</span>
                    <span class="plan-calories">${this.currentPlan.calories} calories/jour</span>
                </div>
            </div>
        `;
        
        // Ajouter les conseils de l'IA si disponibles
        if (this.currentPlan.aiTips) {
            html += `
                <div class="ai-tips-container mt-3">
                    <h4><i class="fas fa-robot mr-2"></i>Conseils personnalisés</h4>
                    <ul class="ai-tips-list">
                        ${this.currentPlan.aiTips.map(tip => `<li>${tip}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
        
        // Ajouter les recommandations basées sur l'analyse morphologique si disponibles
        if (this.currentPlan.bodyAnalysisRecommendations) {
            html += `
                <div class="body-analysis-container mt-3">
                    <h4><i class="fas fa-user-alt mr-2"></i>Recommandations basées sur votre morphologie</h4>
                    <ul class="body-analysis-list">
                        ${this.currentPlan.bodyAnalysisRecommendations.map(rec => `<li>${rec}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
        
        // Ajouter l'analyse nutritionnelle si disponible
        if (this.currentPlan.nutritionAnalysis) {
            const na = this.currentPlan.nutritionAnalysis;
            html += `
                <div class="nutrition-analysis-container mt-3">
                    <h4>Analyse nutritionnelle</h4>
                    <div class="nutrition-macros d-flex justify-content-between">
                        <div class="macro-item">
                            <div class="macro-value">${na.averageProtein}g</div>
                            <div class="macro-label">Protéines</div>
                        </div>
                        <div class="macro-item">
                            <div class="macro-value">${na.averageCarbs}g</div>
                            <div class="macro-label">Glucides</div>
                        </div>
                        <div class="macro-item">
                            <div class="macro-value">${na.averageFat}g</div>
                            <div class="macro-label">Lipides</div>
                        </div>
                        <div class="macro-item">
                            <div class="macro-value">${na.fiberGoal}g</div>
                            <div class="macro-label">Fibres</div>
                        </div>
                        <div class="macro-item">
                            <div class="macro-value">${na.waterGoal}ml</div>
                            <div class="macro-label">Eau</div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Ajouter les onglets pour les jours
        html += `
            <div class="days-tabs-container mt-4">
                <ul class="days-tabs">
                    ${this.currentPlan.days.map((day, index) => `
                        <li class="day-tab ${index === 0 ? 'active' : ''}" data-day="${day.dayNumber}">
                            Jour ${day.dayNumber}
                        </li>
                    `).join('')}
                </ul>
            </div>
        `;
        
        // Ajouter le conteneur pour le contenu du jour
        html += `<div class="day-content-container mt-3" id="day-content-container"></div>`;
        
        // Ajouter les boutons d'action
        html += `
            <div class="plan-actions mt-4 d-flex justify-content-between">
                <button class="btn btn-outline" id="export-plan-btn">
                    <i class="fas fa-download mr-1"></i> Exporter le plan
                </button>
                <button class="btn btn-primary" id="apply-plan-btn">
                    <i class="fas fa-check mr-1"></i> Appliquer ce plan
                </button>
            </div>
        `;
        
        // Mettre à jour le contenu
        currentPlanContainer.innerHTML = html;
        
        // Ajouter des écouteurs d'événements pour les onglets
        const dayTabs = document.querySelectorAll('.day-tab');
        dayTabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                // Supprimer la classe active de tous les onglets
                dayTabs.forEach(t => t.classList.remove('active'));
                
                // Ajouter la classe active à l'onglet cliqué
                e.target.classList.add('active');
                
                // Afficher le contenu du jour
                const dayNumber = parseInt(e.target.dataset.day);
                this.displayDayContent(dayNumber);
            });
        });
        
        // Ajouter des écouteurs d'événements pour les boutons
        const exportPlanBtn = document.getElementById('export-plan-btn');
        if (exportPlanBtn) {
            exportPlanBtn.addEventListener('click', () => this.exportPlan());
        }
        
        const applyPlanBtn = document.getElementById('apply-plan-btn');
        if (applyPlanBtn) {
            applyPlanBtn.addEventListener('click', () => this.applyPlan());
        }
        
        // Afficher le contenu du premier jour par défaut
        this.displayDayContent(1);
    }

    // Afficher le contenu d'un jour
    displayDayContent(dayNumber) {
        const dayContentContainer = document.getElementById('day-content-container');
        if (!dayContentContainer) return;
        
        // Trouver le jour
        const day = this.currentPlan.days.find(d => d.dayNumber === dayNumber);
        if (!day) return;
        
        // Créer le contenu HTML
        let html = '';
        
        // Ajouter chaque repas
        day.meals.forEach(meal => {
            // Déterminer le nom du repas
            let mealName;
            switch (meal.mealType) {
                case 'breakfast':
                    mealName = 'Petit-déjeuner';
                    break;
                case 'lunch':
                    mealName = 'Déjeuner';
                    break;
                case 'dinner':
                    mealName = 'Dîner';
                    break;
                case 'snack':
                    mealName = 'Collation';
                    break;
                default:
                    mealName = 'Repas';
            }
            
            // Calculer les totaux nutritionnels
            const totalCalories = meal.foods.reduce((sum, food) => sum + food.calories, 0);
            const totalProtein = meal.foods.reduce((sum, food) => sum + food.protein, 0);
            const totalCarbs = meal.foods.reduce((sum, food) => sum + food.carbs, 0);
            const totalFat = meal.foods.reduce((sum, food) => sum + food.fat, 0);
            
            html += `
                <div class="meal-container">
                    <div class="meal-header d-flex justify-content-between align-items-center">
                        <h4>${mealName}</h4>
                        <div class="meal-nutrition">
                            <span class="meal-calories">${totalCalories} cal</span>
                            <span class="meal-macros">P: ${totalProtein}g | G: ${totalCarbs}g | L: ${totalFat}g</span>
                        </div>
                    </div>
                    <div class="meal-foods-list">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Aliment</th>
                                    <th>Quantité</th>
                                    <th>Calories</th>
                                    <th>Protéines</th>
                                    <th>Glucides</th>
                                    <th>Lipides</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${meal.foods.map(food => `
                                    <tr>
                                        <td>${food.name}</td>
                                        <td>${food.servingSize} ${food.servingUnit}</td>
                                        <td>${food.calories} cal</td>
                                        <td>${food.protein}g</td>
                                        <td>${food.carbs}g</td>
                                        <td>${food.fat}g</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            // Ajouter les alternatives si disponibles
            if (meal.alternatives) {
                html += `
                    <div class="meal-alternatives-container mt-3">
                        <h5>Alternatives</h5>
                        <div class="alternatives-list">
                            ${meal.alternatives.map(alt => {
                                // Calculer les totaux nutritionnels
                                const altTotalCalories = alt.foods.reduce((sum, food) => sum + food.calories, 0);
                                const altTotalProtein = alt.foods.reduce((sum, food) => sum + food.protein, 0);
                                const altTotalCarbs = alt.foods.reduce((sum, food) => sum + food.carbs, 0);
                                const altTotalFat = alt.foods.reduce((sum, food) => sum + food.fat, 0);
                                
                                return `
                                    <div class="alternative-item">
                                        <div class="alternative-header d-flex justify-content-between align-items-center">
                                            <h6>${alt.name}</h6>
                                            <div class="alternative-nutrition">
                                                <span class="alternative-calories">${altTotalCalories} cal</span>
                                                <span class="alternative-macros">P: ${altTotalProtein}g | G: ${altTotalCarbs}g | L: ${altTotalFat}g</span>
                                            </div>
                                        </div>
                                        <div class="alternative-foods">
                                            ${alt.foods.map(food => `
                                                <div class="alternative-food-item">
                                                    ${food.name} (${food.servingSize} ${food.servingUnit}) - ${food.calories} cal
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            }
        });
        
        // Mettre à jour le contenu
        dayContentContainer.innerHTML = html;
    }

    // Exporter le plan
    exportPlan() {
        // Simuler l'export du plan
        alert('Le plan a été exporté');
    }

    // Appliquer le plan
    applyPlan() {
        // Simuler l'application du plan
        alert('Le plan a été appliqué à votre journal alimentaire');
    }

    // Sauvegarder les données
    saveData() {
        const data = {
            mealPlans: this.mealPlans,
            currentPlanId: this.currentPlan ? this.currentPlan.id : null
        };
        
        localStorage.setItem('mealPlannerData', JSON.stringify(data));
    }

    // Charger les données
    loadData() {
        const data = localStorage.getItem('mealPlannerData');
        if (data) {
            const parsedData = JSON.parse(data);
            this.mealPlans = parsedData.mealPlans || [];
            
            // Charger le plan actuel
            if (parsedData.currentPlanId) {
                this.currentPlan = this.mealPlans.find(p => p.id === parsedData.currentPlanId) || null;
            }
        }
    }
}

// Initialiser le planificateur de repas lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur la page appropriée
    const mealPlannerContainer = document.getElementById('meal-planner-container');
    if (mealPlannerContainer) {
        const mealPlanner = new MealPlanner();
        mealPlanner.init();
    }
});
