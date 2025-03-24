// Adaptation des recommandations ChatGPT au style MyFitnessPal
// pour l'application de suivi de perte de poids

class ChatGPTRecommendations {
    constructor() {
        this.apiKey = null;
        this.recommendations = {
            meals: [],
            exercises: [],
            motivation: []
        };
        this.lastUpdate = null;
    }

    // Initialiser les recommandations ChatGPT
    init(containerId = 'chatgpt-recommendations-container') {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Container not found');
            return false;
        }

        // Charger les données depuis le stockage local
        this.loadData();
        
        // Vérifier si une clé API est configurée
        this.checkAPIKey();
        
        // Créer les éléments de l'interface
        this.createElements();
        
        // Ajouter les écouteurs d'événements
        this.addEventListeners();
        
        return true;
    }

    // Vérifier si une clé API est configurée
    checkAPIKey() {
        // Récupérer la clé API depuis le stockage local
        const storedKey = localStorage.getItem('chatgptApiKey');
        if (storedKey) {
            this.apiKey = storedKey;
        }
    }

    // Créer les éléments de l'interface
    createElements() {
        // Créer le conteneur principal
        const mainContainer = document.createElement('div');
        mainContainer.className = 'chatgpt-recommendations-main';
        
        // Créer l'en-tête
        const header = document.createElement('div');
        header.className = 'chatgpt-recommendations-header';
        
        const title = document.createElement('h2');
        title.innerHTML = '<i class="fas fa-robot mr-2"></i>Recommandations IA';
        
        const subtitle = document.createElement('p');
        subtitle.className = 'text-muted';
        subtitle.textContent = 'Recommandations personnalisées générées par intelligence artificielle';
        
        header.appendChild(title);
        header.appendChild(subtitle);
        
        // Créer le conteneur de configuration de l'API
        const apiContainer = document.createElement('div');
        apiContainer.className = 'api-container mt-4';
        apiContainer.id = 'api-container';
        
        if (!this.apiKey) {
            // Afficher le formulaire de configuration de l'API
            apiContainer.innerHTML = `
                <div class="api-setup-card">
                    <div class="card-body">
                        <h4 class="card-title">Configuration de l'API ChatGPT</h4>
                        <p class="card-text">Pour bénéficier des recommandations personnalisées, veuillez configurer votre clé API ChatGPT.</p>
                        <div class="form-group mt-3">
                            <label for="api-key-input">Clé API ChatGPT</label>
                            <input type="password" class="form-control" id="api-key-input" placeholder="Entrez votre clé API">
                            <small class="form-text text-muted">Votre clé API est stockée localement et n'est jamais partagée.</small>
                        </div>
                        <button class="btn btn-primary mt-2" id="save-api-key-btn">Enregistrer la clé API</button>
                    </div>
                </div>
            `;
        } else {
            // Afficher le statut de l'API
            apiContainer.innerHTML = `
                <div class="api-status-card">
                    <div class="card-body">
                        <h4 class="card-title">Statut de l'API ChatGPT</h4>
                        <p class="card-text">L'API ChatGPT est configurée et prête à générer des recommandations personnalisées.</p>
                        <div class="api-actions mt-3">
                            <button class="btn btn-outline-primary" id="change-api-key-btn">Modifier la clé API</button>
                            <button class="btn btn-primary ml-2" id="generate-recommendations-btn">Générer des recommandations</button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Créer le conteneur des recommandations
        const recommendationsContainer = document.createElement('div');
        recommendationsContainer.className = 'recommendations-container mt-4';
        recommendationsContainer.id = 'recommendations-container';
        
        if (this.apiKey && (this.recommendations.meals.length > 0 || this.recommendations.exercises.length > 0 || this.recommendations.motivation.length > 0)) {
            // Afficher les recommandations existantes
            this.displayRecommendations(recommendationsContainer);
        } else {
            // Masquer le conteneur des recommandations
            recommendationsContainer.style.display = 'none';
        }
        
        // Ajouter tous les éléments au conteneur principal
        mainContainer.appendChild(header);
        mainContainer.appendChild(apiContainer);
        mainContainer.appendChild(recommendationsContainer);
        
        // Ajouter le conteneur principal au conteneur de la page
        this.container.appendChild(mainContainer);
    }

    // Afficher les recommandations
    displayRecommendations(container) {
        if (!container) {
            container = document.getElementById('recommendations-container');
            if (!container) return;
        }
        
        // Afficher le conteneur
        container.style.display = 'block';
        
        // Créer le contenu HTML
        let html = `
            <div class="recommendations-header">
                <h3>Vos recommandations personnalisées</h3>
                ${this.lastUpdate ? `<p class="text-muted">Dernière mise à jour : ${new Date(this.lastUpdate).toLocaleString()}</p>` : ''}
            </div>
            
            <div class="recommendations-tabs mt-3">
                <ul class="nav nav-tabs" id="recommendations-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="meals-tab" data-toggle="tab" href="#meals-content" role="tab">
                            <i class="fas fa-utensils mr-1"></i> Repas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="exercises-tab" data-toggle="tab" href="#exercises-content" role="tab">
                            <i class="fas fa-running mr-1"></i> Exercices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="motivation-tab" data-toggle="tab" href="#motivation-content" role="tab">
                            <i class="fas fa-trophy mr-1"></i> Motivation
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="tab-content mt-3" id="recommendations-content">
                <div class="tab-pane fade show active" id="meals-content" role="tabpanel">
                    ${this.renderMealRecommendations()}
                </div>
                <div class="tab-pane fade" id="exercises-content" role="tabpanel">
                    ${this.renderExerciseRecommendations()}
                </div>
                <div class="tab-pane fade" id="motivation-content" role="tabpanel">
                    ${this.renderMotivationRecommendations()}
                </div>
            </div>
            
            <div class="recommendations-actions mt-4 text-center">
                <button class="btn btn-primary" id="refresh-recommendations-btn">
                    <i class="fas fa-sync-alt mr-1"></i> Actualiser les recommandations
                </button>
            </div>
        `;
        
        // Mettre à jour le contenu
        container.innerHTML = html;
        
        // Ajouter des écouteurs d'événements pour les onglets
        const tabLinks = container.querySelectorAll('.nav-link');
        tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Supprimer la classe active de tous les onglets
                tabLinks.forEach(l => {
                    l.classList.remove('active');
                    document.querySelector(l.getAttribute('href')).classList.remove('show', 'active');
                });
                
                // Ajouter la classe active à l'onglet cliqué
                link.classList.add('active');
                document.querySelector(link.getAttribute('href')).classList.add('show', 'active');
            });
        });
        
        // Ajouter un écouteur d'événement pour le bouton d'actualisation
        const refreshBtn = container.querySelector('#refresh-recommendations-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.generateRecommendations());
        }
    }

    // Rendre les recommandations de repas
    renderMealRecommendations() {
        if (this.recommendations.meals.length === 0) {
            return `
                <div class="no-recommendations text-center p-4">
                    <p>Aucune recommandation de repas disponible</p>
                    <p class="text-muted">Cliquez sur "Actualiser les recommandations" pour en générer</p>
                </div>
            `;
        }
        
        let html = `<div class="meal-recommendations">`;
        
        this.recommendations.meals.forEach(meal => {
            html += `
                <div class="meal-recommendation-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">${meal.name}</h5>
                        <span class="meal-calories badge badge-primary">${meal.calories} calories</span>
                    </div>
                    <div class="card-body">
                        <div class="meal-description mb-3">
                            <p>${meal.description}</p>
                        </div>
                        <div class="meal-ingredients">
                            <h6>Ingrédients</h6>
                            <ul class="ingredients-list">
                                ${meal.ingredients.map(ingredient => `
                                    <li>
                                        <span class="ingredient-name">${ingredient.name}</span>
                                        <span class="ingredient-quantity">${ingredient.quantity}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                        <div class="meal-nutrition mt-3">
                            <h6>Valeurs nutritionnelles</h6>
                            <div class="nutrition-values d-flex justify-content-between">
                                <div class="nutrition-value">
                                    <span class="value">${meal.nutrition.protein}g</span>
                                    <span class="label">Protéines</span>
                                </div>
                                <div class="nutrition-value">
                                    <span class="value">${meal.nutrition.carbs}g</span>
                                    <span class="label">Glucides</span>
                                </div>
                                <div class="nutrition-value">
                                    <span class="value">${meal.nutrition.fat}g</span>
                                    <span class="label">Lipides</span>
                                </div>
                                <div class="nutrition-value">
                                    <span class="value">${meal.nutrition.fiber}g</span>
                                    <span class="label">Fibres</span>
                                </div>
                            </div>
                        </div>
                        <div class="meal-actions mt-3">
                            <button class="btn btn-sm btn-outline-primary add-to-journal-btn" data-meal-id="${meal.id}">
                                <i class="fas fa-plus mr-1"></i> Ajouter au journal
                            </button>
                            <button class="btn btn-sm btn-outline-secondary save-meal-btn ml-2" data-meal-id="${meal.id}">
                                <i class="far fa-bookmark mr-1"></i> Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `</div>`;
        
        return html;
    }

    // Rendre les recommandations d'exercices
    renderExerciseRecommendations() {
        if (this.recommendations.exercises.length === 0) {
            return `
                <div class="no-recommendations text-center p-4">
                    <p>Aucune recommandation d'exercice disponible</p>
                    <p class="text-muted">Cliquez sur "Actualiser les recommandations" pour en générer</p>
                </div>
            `;
        }
        
        let html = `<div class="exercise-recommendations">`;
        
        this.recommendations.exercises.forEach(exercise => {
            html += `
                <div class="exercise-recommendation-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">${exercise.name}</h5>
                        <div class="exercise-meta">
                            <span class="exercise-duration badge badge-info mr-2">${exercise.duration} min</span>
                            <span class="exercise-calories badge badge-primary">${exercise.calories} calories</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="exercise-description mb-3">
                            <p>${exercise.description}</p>
                        </div>
                        <div class="exercise-steps">
                            <h6>Instructions</h6>
                            <ol class="steps-list">
                                ${exercise.steps.map(step => `<li>${step}</li>`).join('')}
                            </ol>
                        </div>
                        <div class="exercise-benefits mt-3">
                            <h6>Bénéfices</h6>
                            <ul class="benefits-list">
                                ${exercise.benefits.map(benefit => `<li>${benefit}</li>`).join('')}
                            </ul>
                        </div>
                        <div class="exercise-actions mt-3">
                            <button class="btn btn-sm btn-outline-primary add-to-journal-btn" data-exercise-id="${exercise.id}">
                                <i class="fas fa-plus mr-1"></i> Ajouter au journal
                            </button>
                            <button class="btn btn-sm btn-outline-secondary save-exercise-btn ml-2" data-exercise-id="${exercise.id}">
                                <i class="far fa-bookmark mr-1"></i> Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `</div>`;
        
        return html;
    }

    // Rendre les recommandations de motivation
    renderMotivationRecommendations() {
        if (this.recommendations.motivation.length === 0) {
            return `
                <div class="no-recommendations text-center p-4">
                    <p>Aucune recommandation de motivation disponible</p>
                    <p class="text-muted">Cliquez sur "Actualiser les recommandations" pour en générer</p>
                </div>
            `;
        }
        
        let html = `<div class="motivation-recommendations">`;
        
        this.recommendations.motivation.forEach(motivation => {
            html += `
                <div class="motivation-recommendation-card mb-3">
                    <div class="card-body">
                        <div class="motivation-quote">
                            <blockquote class="blockquote">
                                <p class="mb-0">${motivation.quote}</p>
                                ${motivation.author ? `<footer class="blockquote-footer">${motivation.author}</footer>` : ''}
                            </blockquote>
                        </div>
                        <div class="motivation-message mt-3">
                            <p>${motivation.message}</p>
                        </div>
                        <div class="motivation-tips mt-3">
                            <h6>Conseils pour rester motivé</h6>
                            <ul class="tips-list">
                                ${motivation.tips.map(tip => `<li>${tip}</li>`).join('')}
                            </ul>
                        </div>
                        <div class="motivation-actions mt-3 text-right">
                            <button class="btn btn-sm btn-outline-primary share-motivation-btn" data-motivation-id="${motivation.id}">
                                <i class="fas fa-share-alt mr-1"></i> Partager
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `</div>`;
        
        return html;
    }

    // Ajouter les écouteurs d'événements
    addEventListeners() {
        // Bouton pour enregistrer la clé API
        const saveApiKeyBtn = document.getElementById('save-api-key-btn');
        if (saveApiKeyBtn) {
            saveApiKeyBtn.addEventListener('click', () => this.saveApiKey());
        }
        
        // Bouton pour modifier la clé API
        const changeApiKeyBtn = document.getElementById('change-api-key-btn');
        if (changeApiKeyBtn) {
            changeApiKeyBtn.addEventListener('click', () => this.showApiKeyForm());
        }
        
        // Bouton pour générer des recommandations
        const generateRecommendationsBtn = document.getElementById('generate-recommendations-btn');
        if (generateRecommendationsBtn) {
            generateRecommendationsBtn.addEventListener('click', () => this.generateRecommendations());
        }
    }

    // Enregistrer la clé API
    saveApiKey() {
        const apiKeyInput = document.getElementById('api-key-input');
        if (!apiKeyInput) return;
        
        const apiKey = apiKeyInput.value.trim();
        if (!apiKey) {
            alert('Veuillez entrer une clé API valide');
            return;
        }
        
        // Enregistrer la clé API
        this.apiKey = apiKey;
        localStorage.setItem('chatgptApiKey', apiKey);
        
        // Mettre à jour l'interface
        const apiContainer = document.getElementById('api-container');
        if (apiContainer) {
            apiContainer.innerHTML = `
                <div class="api-status-card">
                    <div class="card-body">
                        <h4 class="card-title">Statut de l'API ChatGPT</h4>
                        <p class="card-text">L'API ChatGPT est configurée et prête à générer des recommandations personnalisées.</p>
                        <div class="api-actions mt-3">
                            <button class="btn btn-outline-primary" id="change-api-key-btn">Modifier la clé API</button>
                            <button class="btn btn-primary ml-2" id="generate-recommendations-btn">Générer des recommandations</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter les écouteurs d'événements
            const changeApiKeyBtn = document.getElementById('change-api-key-btn');
            if (changeApiKeyBtn) {
                changeApiKeyBtn.addEventListener('click', () => this.showApiKeyForm());
            }
            
            const generateRecommendationsBtn = document.getElementById('generate-recommendations-btn');
            if (generateRecommendationsBtn) {
                generateRecommendationsBtn.addEventListener('click', () => this.generateRecommendations());
            }
        }
    }

    // Afficher le formulaire de clé API
    showApiKeyForm() {
        const apiContainer = document.getElementById('api-container');
        if (!apiContainer) return;
        
        apiContainer.innerHTML = `
            <div class="api-setup-card">
                <div class="card-body">
                    <h4 class="card-title">Configuration de l'API ChatGPT</h4>
                    <p class="card-text">Pour bénéficier des recommandations personnalisées, veuillez configurer votre clé API ChatGPT.</p>
                    <div class="form-group mt-3">
                        <label for="api-key-input">Clé API ChatGPT</label>
                        <input type="password" class="form-control" id="api-key-input" placeholder="Entrez votre clé API" value="${this.apiKey || ''}">
                        <small class="form-text text-muted">Votre clé API est stockée localement et n'est jamais partagée.</small>
                    </div>
                    <button class="btn btn-primary mt-2" id="save-api-key-btn">Enregistrer la clé API</button>
                </div>
            </div>
        `;
        
        // Ajouter l'écouteur d'événement
        const saveApiKeyBtn = document.getElementById('save-api-key-btn');
        if (saveApiKeyBtn) {
            saveApiKeyBtn.addEventListener('click', () => this.saveApiKey());
        }
    }

    // Générer des recommandations
    generateRecommendations() {
        // Vérifier que la clé API est configurée
        if (!this.apiKey) {
            alert('Veuillez configurer votre clé API ChatGPT');
            return;
        }
        
        // Afficher un message de chargement
        const recommendationsContainer = document.getElementById('recommendations-container');
        if (recommendationsContainer) {
            recommendationsContainer.style.display = 'block';
            recommendationsContainer.innerHTML = `
                <div class="loading-container text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Génération en cours...</span>
                    </div>
                    <h4 class="mt-3">Génération de recommandations personnalisées</h4>
                    <p class="text-muted">L'IA analyse vos données pour créer des recommandations sur mesure</p>
                </div>
            `;
        }
        
        // Simuler un délai pour la génération
        setTimeout(() => {
            // Simuler la génération de recommandations
            this.simulateRecommendationsGeneration();
            
            // Mettre à jour la date de dernière mise à jour
            this.lastUpdate = new Date().toISOString();
            
            // Sauvegarder les données
            this.saveData();
            
            // Afficher les recommandations
            this.displayRecommendations();
        }, 3000);
    }

    // Simuler la génération de recommandations
    simulateRecommendationsGeneration() {
        // Simuler des recommandations de repas
        this.recommendations.meals = [
            {
                id: 'meal-1',
                name: 'Bol de quinoa aux légumes grillés',
                description: 'Un repas équilibré riche en protéines végétales et en fibres, parfait pour un déjeuner nutritif qui vous tiendra rassasié tout l\'après-midi.',
                calories: 420,
                ingredients: [
                    { name: 'Quinoa cuit', quantity: '150g' },
                    { name: 'Courgettes grillées', quantity: '100g' },
                    { name: 'Poivrons rouges grillés', quantity: '100g' },
                    { name: 'Pois chiches', quantity: '50g' },
                    { name: 'Avocat', quantity: '1/2' },
                    { name: 'Huile d\'olive', quantity: '1 c. à soupe' },
                    { name: 'Jus de citron', quantity: '1 c. à soupe' },
                    { name: 'Herbes fraîches', quantity: 'Au goût' }
                ],
                nutrition: {
                    protein: 15,
                    carbs: 45,
                    fat: 18,
                    fiber: 12
                }
            },
            {
                id: 'meal-2',
                name: 'Saumon grillé et légumes verts',
                description: 'Un dîner riche en acides gras oméga-3 et en protéines de haute qualité, idéal pour soutenir votre récupération musculaire et votre santé cardiovasculaire.',
                calories: 380,
                ingredients: [
                    { name: 'Filet de saumon', quantity: '150g' },
                    { name: 'Asperges', quantity: '100g' },
                    { name: 'Brocoli', quantity: '100g' },
                    { name: 'Épinards frais', quantity: '50g' },
                    { name: 'Huile d\'olive', quantity: '1 c. à soupe' },
                    { name: 'Ail', quantity: '1 gousse' },
                    { name: 'Citron', quantity: '1/2' },
                    { name: 'Aneth frais', quantity: 'Au goût' }
                ],
                nutrition: {
                    protein: 32,
                    carbs: 12,
                    fat: 22,
                    fiber: 8
                }
            },
            {
                id: 'meal-3',
                name: 'Smoothie bowl protéiné aux fruits rouges',
                description: 'Un petit-déjeuner rafraîchissant et énergisant, riche en antioxydants et en protéines pour bien démarrer la journée et soutenir votre métabolisme.',
                calories: 320,
                ingredients: [
                    { name: 'Yaourt grec', quantity: '150g' },
                    { name: 'Protéine en poudre (vanille)', quantity: '1 mesure' },
                    { name: 'Fraises', quantity: '100g' },
                    { name: 'Myrtilles', quantity: '50g' },
                    { name: 'Framboises', quantity: '50g' },
                    { name: 'Graines de chia', quantity: '1 c. à soupe' },
                    { name: 'Amandes effilées', quantity: '1 c. à soupe' },
                    { name: 'Miel', quantity: '1 c. à café' }
                ],
                nutrition: {
                    protein: 25,
                    carbs: 30,
                    fat: 10,
                    fiber: 9
                }
            }
        ];
        
        // Simuler des recommandations d'exercices
        this.recommendations.exercises = [
            {
                id: 'exercise-1',
                name: 'Circuit HIIT brûle-graisses',
                description: 'Un entraînement par intervalles de haute intensité conçu pour maximiser la combustion des graisses et améliorer votre condition cardiovasculaire.',
                duration: 20,
                calories: 250,
                steps: [
                    'Échauffez-vous pendant 3 minutes avec des jumping jacks et des rotations du tronc',
                    'Alternez 30 secondes de burpees et 30 secondes de mountain climbers',
                    'Enchaînez avec 30 secondes de squats sautés et 30 secondes de pompes',
                    'Terminez par 30 secondes de planches et 30 secondes de jumping jacks',
                    'Répétez le circuit 4 fois avec 1 minute de récupération entre chaque tour',
                    'Terminez par 3 minutes d\'étirements'
                ],
                benefits: [
                    'Accélère le métabolisme pendant jusqu\'à 24 heures après l\'exercice',
                    'Améliore la capacité cardiovasculaire',
                    'Renforce les principaux groupes musculaires',
                    'Peut être réalisé sans équipement spécial'
                ]
            },
            {
                id: 'exercise-2',
                name: 'Yoga pour la flexibilité et la récupération',
                description: 'Une séance de yoga douce mais efficace pour améliorer votre flexibilité, réduire le stress et favoriser la récupération musculaire.',
                duration: 30,
                calories: 150,
                steps: [
                    'Commencez en position assise avec 5 respirations profondes',
                    'Enchaînez avec la séquence de salutation au soleil, 5 répétitions',
                    'Maintenez la posture du guerrier pendant 30 secondes de chaque côté',
                    'Pratiquez la posture du chien tête en bas pendant 1 minute',
                    'Terminez par la posture de l\'enfant pendant 2 minutes',
                    'Concluez avec 5 minutes de méditation en position allongée'
                ],
                benefits: [
                    'Améliore la flexibilité et la mobilité articulaire',
                    'Réduit le stress et favorise la relaxation',
                    'Accélère la récupération musculaire',
                    'Améliore la posture et l\'équilibre'
                ]
            },
            {
                id: 'exercise-3',
                name: 'Marche rapide avec intervalles',
                description: 'Une activité cardiovasculaire accessible qui combine marche rapide et intervalles d\'intensité pour maximiser les bénéfices sans impact excessif sur les articulations.',
                duration: 45,
                calories: 300,
                steps: [
                    'Commencez par 5 minutes de marche à rythme modéré pour vous échauffer',
                    'Alternez 2 minutes de marche rapide et 1 minute de marche très rapide ou de jogging léger',
                    'Répétez ce cycle pendant 35 minutes',
                    'Terminez par 5 minutes de marche lente pour récupérer',
                    'Étirez-vous en insistant sur les mollets, quadriceps et ischio-jambiers'
                ],
                benefits: [
                    'Activité à faible impact adaptée à tous les niveaux de condition physique',
                    'Améliore l\'endurance cardiovasculaire',
                    'Brûle efficacement les graisses',
                    'Peut être pratiquée n\'importe où, sans équipement'
                ]
            }
        ];
        
        // Simuler des recommandations de motivation
        this.recommendations.motivation = [
            {
                id: 'motivation-1',
                quote: 'Le succès n\'est pas final, l\'échec n\'est pas fatal : c\'est le courage de continuer qui compte.',
                author: 'Winston Churchill',
                message: 'Votre parcours de perte de poids est un marathon, pas un sprint. Chaque petit pas compte, même les jours difficiles. Rappelez-vous que la constance est plus importante que la perfection.',
                tips: [
                    'Célébrez vos petites victoires quotidiennes',
                    'Tenez un journal de gratitude pour noter vos progrès',
                    'Entourez-vous de personnes qui vous soutiennent dans vos objectifs',
                    'Visualisez votre réussite chaque matin pendant 5 minutes'
                ]
            },
            {
                id: 'motivation-2',
                quote: 'La discipline est de choisir entre ce que vous voulez maintenant et ce que vous voulez le plus.',
                author: 'Abraham Lincoln',
                message: 'Les choix que vous faites aujourd\'hui façonnent votre corps de demain. Chaque fois que vous choisissez un repas sain ou que vous vous entraînez malgré la fatigue, vous investissez dans votre futur vous.',
                tips: [
                    'Préparez vos repas à l\'avance pour éviter les choix impulsifs',
                    'Planifiez vos séances d\'entraînement comme des rendez-vous importants',
                    'Trouvez un partenaire d\'entraînement pour vous motiver mutuellement',
                    'Créez un environnement propice à vos objectifs en éliminant les tentations'
                ]
            },
            {
                id: 'motivation-3',
                quote: 'Le corps atteint ce que l\'esprit croit.',
                author: 'Napoleon Hill',
                message: 'Votre état d\'esprit est votre atout le plus puissant dans votre parcours de transformation. Cultivez des pensées positives et une mentalité de croissance pour surmonter les obstacles inévitables.',
                tips: [
                    'Pratiquez des affirmations positives quotidiennes',
                    'Remplacez "je dois" par "je choisis de" pour reprendre le contrôle',
                    'Visualisez-vous en train d\'atteindre vos objectifs avec des détails précis',
                    'Apprenez de chaque revers et adaptez votre approche sans vous juger'
                ]
            }
        ];
    }

    // Sauvegarder les données
    saveData() {
        localStorage.setItem('chatgptRecommendationsData', JSON.stringify({
            recommendations: this.recommendations,
            lastUpdate: this.lastUpdate
        }));
    }

    // Charger les données
    loadData() {
        const data = localStorage.getItem('chatgptRecommendationsData');
        if (data) {
            const parsedData = JSON.parse(data);
            this.recommendations = parsedData.recommendations || { meals: [], exercises: [], motivation: [] };
            this.lastUpdate = parsedData.lastUpdate;
        }
    }
}

// Initialiser les recommandations ChatGPT lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur la page appropriée
    const chatgptRecommendationsContainer = document.getElementById('chatgpt-recommendations-container');
    if (chatgptRecommendationsContainer) {
        const chatgptRecommendations = new ChatGPTRecommendations();
        chatgptRecommendations.init();
    }
});
