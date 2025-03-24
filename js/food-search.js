// Fonctionnalité de recherche d'aliments améliorée pour l'application FitTrack
// Style MyFitnessPal

class FoodSearch {
    constructor() {
        this.searchResults = [];
        this.recentSearches = [];
        this.favoriteItems = [];
        this.foodDatabase = []; // Sera rempli avec des données simulées
    }

    // Initialiser la recherche
    init(containerId = 'food-search-container') {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Container not found');
            return false;
        }

        // Charger les données depuis le stockage local
        this.loadData();
        
        // Remplir la base de données d'aliments simulée
        this.populateFoodDatabase();
        
        // Créer les éléments de l'interface
        this.createElements();
        
        // Ajouter les écouteurs d'événements
        this.addEventListeners();
        
        return true;
    }

    // Créer les éléments de l'interface
    createElements() {
        // Créer le conteneur de recherche
        const searchContainer = document.createElement('div');
        searchContainer.className = 'food-search-container';
        
        // Créer la barre de recherche
        const searchBar = document.createElement('div');
        searchBar.className = 'search-bar';
        
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.id = 'food-search-input';
        searchInput.className = 'form-control';
        searchInput.placeholder = 'Rechercher un aliment...';
        
        const searchButton = document.createElement('button');
        searchButton.className = 'btn btn-primary';
        searchButton.id = 'food-search-btn';
        searchButton.innerHTML = '<i class="fas fa-search"></i>';
        
        const barcodeButton = document.createElement('button');
        barcodeButton.className = 'btn btn-outline ml-2';
        barcodeButton.id = 'barcode-scan-btn';
        barcodeButton.innerHTML = '<i class="fas fa-barcode"></i>';
        
        searchBar.appendChild(searchInput);
        searchBar.appendChild(searchButton);
        searchBar.appendChild(barcodeButton);
        
        // Créer les onglets
        const tabsContainer = document.createElement('div');
        tabsContainer.className = 'tabs-container mt-3';
        
        const tabs = document.createElement('ul');
        tabs.className = 'tabs';
        
        const allTab = document.createElement('li');
        allTab.className = 'tab active';
        allTab.dataset.tab = 'all';
        allTab.textContent = 'Tous';
        
        const recentTab = document.createElement('li');
        recentTab.className = 'tab';
        recentTab.dataset.tab = 'recent';
        recentTab.textContent = 'Récents';
        
        const favoritesTab = document.createElement('li');
        favoritesTab.className = 'tab';
        favoritesTab.dataset.tab = 'favorites';
        favoritesTab.textContent = 'Favoris';
        
        const myFoodsTab = document.createElement('li');
        myFoodsTab.className = 'tab';
        myFoodsTab.dataset.tab = 'my-foods';
        myFoodsTab.textContent = 'Mes aliments';
        
        tabs.appendChild(allTab);
        tabs.appendChild(recentTab);
        tabs.appendChild(favoritesTab);
        tabs.appendChild(myFoodsTab);
        
        tabsContainer.appendChild(tabs);
        
        // Créer le conteneur des résultats
        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'search-results mt-3';
        resultsContainer.id = 'search-results';
        
        // Créer le conteneur des filtres
        const filtersContainer = document.createElement('div');
        filtersContainer.className = 'filters-container mt-3';
        
        const filtersTitle = document.createElement('h5');
        filtersTitle.textContent = 'Filtres';
        
        const filtersContent = document.createElement('div');
        filtersContent.className = 'd-flex flex-wrap';
        
        const mealTypeFilter = document.createElement('div');
        mealTypeFilter.className = 'filter-group mr-3';
        
        const mealTypeLabel = document.createElement('label');
        mealTypeLabel.textContent = 'Type de repas';
        
        const mealTypeSelect = document.createElement('select');
        mealTypeSelect.className = 'form-control form-control-sm';
        mealTypeSelect.id = 'meal-type-filter';
        
        const mealTypes = [
            { value: 'all', text: 'Tous les repas' },
            { value: 'breakfast', text: 'Petit-déjeuner' },
            { value: 'lunch', text: 'Déjeuner' },
            { value: 'dinner', text: 'Dîner' },
            { value: 'snack', text: 'Collation' }
        ];
        
        mealTypes.forEach(type => {
            const option = document.createElement('option');
            option.value = type.value;
            option.textContent = type.text;
            mealTypeSelect.appendChild(option);
        });
        
        mealTypeFilter.appendChild(mealTypeLabel);
        mealTypeFilter.appendChild(mealTypeSelect);
        
        const caloriesFilter = document.createElement('div');
        caloriesFilter.className = 'filter-group mr-3';
        
        const caloriesLabel = document.createElement('label');
        caloriesLabel.textContent = 'Calories';
        
        const caloriesSelect = document.createElement('select');
        caloriesSelect.className = 'form-control form-control-sm';
        caloriesSelect.id = 'calories-filter';
        
        const caloriesRanges = [
            { value: 'all', text: 'Toutes les calories' },
            { value: 'under100', text: 'Moins de 100 cal' },
            { value: '100-200', text: '100-200 cal' },
            { value: '200-300', text: '200-300 cal' },
            { value: '300-500', text: '300-500 cal' },
            { value: 'over500', text: 'Plus de 500 cal' }
        ];
        
        caloriesRanges.forEach(range => {
            const option = document.createElement('option');
            option.value = range.value;
            option.textContent = range.text;
            caloriesSelect.appendChild(option);
        });
        
        caloriesFilter.appendChild(caloriesLabel);
        caloriesFilter.appendChild(caloriesSelect);
        
        const macrosFilter = document.createElement('div');
        macrosFilter.className = 'filter-group';
        
        const macrosLabel = document.createElement('label');
        macrosLabel.textContent = 'Macronutriments';
        
        const macrosSelect = document.createElement('select');
        macrosSelect.className = 'form-control form-control-sm';
        macrosSelect.id = 'macros-filter';
        
        const macrosOptions = [
            { value: 'all', text: 'Tous les macros' },
            { value: 'high-protein', text: 'Riche en protéines' },
            { value: 'low-carb', text: 'Faible en glucides' },
            { value: 'low-fat', text: 'Faible en lipides' }
        ];
        
        macrosOptions.forEach(option => {
            const opt = document.createElement('option');
            opt.value = option.value;
            opt.textContent = option.text;
            macrosSelect.appendChild(opt);
        });
        
        macrosFilter.appendChild(macrosLabel);
        macrosFilter.appendChild(macrosSelect);
        
        filtersContent.appendChild(mealTypeFilter);
        filtersContent.appendChild(caloriesFilter);
        filtersContent.appendChild(macrosFilter);
        
        filtersContainer.appendChild(filtersTitle);
        filtersContainer.appendChild(filtersContent);
        
        // Ajouter tous les éléments au conteneur principal
        searchContainer.appendChild(searchBar);
        searchContainer.appendChild(tabsContainer);
        searchContainer.appendChild(filtersContainer);
        searchContainer.appendChild(resultsContainer);
        
        // Ajouter le conteneur de recherche au conteneur principal
        this.container.appendChild(searchContainer);
        
        // Créer le modal de détails d'aliment
        this.createFoodDetailsModal();
    }

    // Créer le modal de détails d'aliment
    createFoodDetailsModal() {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'food-details-modal';
        modal.style.display = 'none';
        
        modal.innerHTML = `
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="food-details-name">Nom de l'aliment</h3>
                    <button class="modal-close" id="close-food-details-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="food-details-calories">
                            <span id="food-details-calories">0</span> calories
                        </div>
                        <button class="btn btn-sm btn-outline" id="add-to-favorites">
                            <i class="far fa-heart"></i> Ajouter aux favoris
                        </button>
                    </div>
                    
                    <div class="macros-container">
                        <h5>Macronutriments</h5>
                        <div class="d-flex justify-content-between">
                            <div class="macro-item">
                                <div class="macro-value" id="food-details-protein">0g</div>
                                <div class="macro-label">Protéines</div>
                            </div>
                            <div class="macro-item">
                                <div class="macro-value" id="food-details-carbs">0g</div>
                                <div class="macro-label">Glucides</div>
                            </div>
                            <div class="macro-item">
                                <div class="macro-value" id="food-details-fat">0g</div>
                                <div class="macro-label">Lipides</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="nutrition-container mt-3">
                        <h5>Informations nutritionnelles</h5>
                        <table class="nutrition-table">
                            <tr>
                                <td>Fibres</td>
                                <td id="food-details-fiber">0g</td>
                            </tr>
                            <tr>
                                <td>Sucres</td>
                                <td id="food-details-sugar">0g</td>
                            </tr>
                            <tr>
                                <td>Sodium</td>
                                <td id="food-details-sodium">0mg</td>
                            </tr>
                            <tr>
                                <td>Cholestérol</td>
                                <td id="food-details-cholesterol">0mg</td>
                            </tr>
                            <tr>
                                <td>Potassium</td>
                                <td id="food-details-potassium">0mg</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="serving-container mt-3">
                        <h5>Portion</h5>
                        <div class="d-flex">
                            <div class="form-group mr-3">
                                <label for="serving-size">Taille de la portion</label>
                                <input type="number" id="serving-size" class="form-control" value="1" min="0.1" step="0.1">
                            </div>
                            <div class="form-group">
                                <label for="serving-unit">Unité</label>
                                <select id="serving-unit" class="form-control">
                                    <option value="serving">Portion</option>
                                    <option value="g">Grammes</option>
                                    <option value="ml">Millilitres</option>
                                    <option value="oz">Onces</option>
                                    <option value="cup">Tasse</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" id="cancel-add-food">Annuler</button>
                    <button class="btn btn-primary" id="add-food-to-diary">Ajouter au journal</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }

    // Ajouter les écouteurs d'événements
    addEventListeners() {
        // Bouton de recherche
        const searchBtn = document.getElementById('food-search-btn');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => this.performSearch());
        }
        
        // Champ de recherche (recherche en appuyant sur Entrée)
        const searchInput = document.getElementById('food-search-input');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.performSearch();
                }
            });
        }
        
        // Bouton de scan de code-barres
        const barcodeBtn = document.getElementById('barcode-scan-btn');
        if (barcodeBtn) {
            barcodeBtn.addEventListener('click', () => {
                // Vérifier si la fonction de scan existe
                if (typeof simulateBarcodeScan === 'function') {
                    simulateBarcodeScan();
                } else {
                    alert('La fonctionnalité de scan de code-barres n\'est pas disponible');
                }
            });
        }
        
        // Onglets
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                // Supprimer la classe active de tous les onglets
                tabs.forEach(t => t.classList.remove('active'));
                
                // Ajouter la classe active à l'onglet cliqué
                e.target.classList.add('active');
                
                // Afficher les résultats correspondants
                const tabName = e.target.dataset.tab;
                this.showTabContent(tabName);
            });
        });
        
        // Filtres
        const filters = document.querySelectorAll('select[id$="-filter"]');
        filters.forEach(filter => {
            filter.addEventListener('change', () => this.applyFilters());
        });
        
        // Modal de détails d'aliment
        const closeModal = document.getElementById('close-food-details-modal');
        if (closeModal) {
            closeModal.addEventListener('click', () => this.hideFoodDetailsModal());
        }
        
        const cancelAddFood = document.getElementById('cancel-add-food');
        if (cancelAddFood) {
            cancelAddFood.addEventListener('click', () => this.hideFoodDetailsModal());
        }
        
        const addFoodToDiary = document.getElementById('add-food-to-diary');
        if (addFoodToDiary) {
            addFoodToDiary.addEventListener('click', () => this.addFoodToDiary());
        }
        
        const addToFavorites = document.getElementById('add-to-favorites');
        if (addToFavorites) {
            addToFavorites.addEventListener('click', () => this.toggleFavorite());
        }
    }

    // Effectuer une recherche
    performSearch() {
        const searchInput = document.getElementById('food-search-input');
        if (!searchInput) return;
        
        const query = searchInput.value.trim();
        if (query === '') return;
        
        // Ajouter aux recherches récentes
        this.addToRecentSearches(query);
        
        // Rechercher dans la base de données
        this.searchResults = this.searchFoodDatabase(query);
        
        // Afficher les résultats
        this.displaySearchResults(this.searchResults);
        
        // Activer l'onglet "Tous"
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        document.querySelector('.tab[data-tab="all"]').classList.add('active');
    }

    // Rechercher dans la base de données
    searchFoodDatabase(query) {
        query = query.toLowerCase();
        
        return this.foodDatabase.filter(food => {
            return food.name.toLowerCase().includes(query) || 
                   food.category.toLowerCase().includes(query);
        });
    }

    // Afficher les résultats de recherche
    displaySearchResults(results) {
        const resultsContainer = document.getElementById('search-results');
        if (!resultsContainer) return;
        
        // Vider le conteneur
        resultsContainer.innerHTML = '';
        
        // Afficher un message si aucun résultat
        if (results.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'no-results text-center p-3';
            noResults.innerHTML = `
                <p>Aucun résultat trouvé</p>
                <button class="btn btn-outline" id="add-custom-food-btn">
                    <i class="fas fa-plus mr-1"></i> Ajouter un aliment personnalisé
                </button>
            `;
            resultsContainer.appendChild(noResults);
            
            // Ajouter un écouteur d'événement pour le bouton d'ajout d'aliment personnalisé
            const addCustomFoodBtn = document.getElementById('add-custom-food-btn');
            if (addCustomFoodBtn) {
                addCustomFoodBtn.addEventListener('click', () => this.showAddCustomFoodModal());
            }
            
            return;
        }
        
        // Créer une liste pour les résultats
        const resultsList = document.createElement('ul');
        resultsList.className = 'food-results-list';
        
        // Ajouter chaque résultat à la liste
        results.forEach(food => {
            const listItem = document.createElement('li');
            listItem.className = 'food-result-item';
            listItem.dataset.id = food.id;
            
            listItem.innerHTML = `
                <div class="food-result-content">
                    <div class="food-result-name">${food.name}</div>
                    <div class="food-result-details">
                        <span class="food-result-serving">${food.servingSize} ${food.servingUnit}</span>
                        <span class="food-result-calories">${food.calories} cal</span>
                    </div>
                </div>
                <div class="food-result-actions">
                    <button class="btn btn-sm btn-outline food-details-btn">
                        <i class="fas fa-info-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-primary add-food-btn">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `;
            
            resultsList.appendChild(listItem);
        });
        
        resultsContainer.appendChild(resultsList);
        
        // Ajouter des écouteurs d'événements pour les boutons
        const detailsBtns = document.querySelectorAll('.food-details-btn');
        detailsBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const foodId = e.target.closest('.food-result-item').dataset.id;
                this.showFoodDetails(foodId);
            });
        });
        
        const addBtns = document.querySelectorAll('.add-food-btn');
        addBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const foodId = e.target.closest('.food-result-item').dataset.id;
                this.quickAddFood(foodId);
            });
        });
    }

    // Afficher le contenu d'un onglet
    showTabContent(tabName) {
        switch (tabName) {
            case 'all':
                this.displaySearchResults(this.searchResults);
                break;
            case 'recent':
                this.displayRecentSearches();
                break;
            case 'favorites':
                this.displayFavorites();
                break;
            case 'my-foods':
                this.displayMyFoods();
                break;
        }
    }

    // Afficher les recherches récentes
    displayRecentSearches() {
        const resultsContainer = document.getElementById('search-results');
        if (!resultsContainer) return;
        
        // Vider le conteneur
        resultsContainer.innerHTML = '';
        
        // Afficher un message si aucune recherche récente
        if (this.recentSearches.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'no-results text-center p-3';
            noResults.textContent = 'Aucune recherche récente';
            resultsContainer.appendChild(noResults);
            return;
        }
        
        // Créer une liste pour les recherches récentes
        const recentList = document.createElement('ul');
        recentList.className = 'recent-searches-list';
        
        // Ajouter chaque recherche récente à la liste
        this.recentSearches.forEach(search => {
            const listItem = document.createElement('li');
            listItem.className = 'recent-search-item';
            
            listItem.innerHTML = `
                <div class="recent-search-content">
                    <div class="recent-search-query">${search.query}</div>
                    <div class="recent-search-date">${this.formatDate(search.date)}</div>
                </div>
                <div class="recent-search-actions">
                    <button class="btn btn-sm btn-outline search-again-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            `;
            
            recentList.appendChild(listItem);
        });
        
        resultsContainer.appendChild(recentList);
        
        // Ajouter des écouteurs d'événements pour les boutons
        const searchAgainBtns = document.querySelectorAll('.search-again-btn');
        searchAgainBtns.forEach((btn, index) => {
            btn.addEventListener('click', () => {
                const query = this.recentSearches[index].query;
                document.getElementById('food-search-input').value = query;
                this.performSearch();
            });
        });
    }

    // Afficher les favoris
    displayFavorites() {
        const resultsContainer = document.getElementById('search-results');
        if (!resultsContainer) return;
        
        // Vider le conteneur
        resultsContainer.innerHTML = '';
        
        // Afficher un message si aucun favori
        if (this.favoriteItems.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'no-results text-center p-3';
            noResults.textContent = 'Aucun aliment favori';
            resultsContainer.appendChild(noResults);
            return;
        }
        
        // Créer une liste pour les favoris
        const favoritesList = document.createElement('ul');
        favoritesList.className = 'food-results-list';
        
        // Ajouter chaque favori à la liste
        this.favoriteItems.forEach(foodId => {
            const food = this.getFoodById(foodId);
            if (!food) return;
            
            const listItem = document.createElement('li');
            listItem.className = 'food-result-item';
            listItem.dataset.id = food.id;
            
            listItem.innerHTML = `
                <div class="food-result-content">
                    <div class="food-result-name">${food.name}</div>
                    <div class="food-result-details">
                        <span class="food-result-serving">${food.servingSize} ${food.servingUnit}</span>
                        <span class="food-result-calories">${food.calories} cal</span>
                    </div>
                </div>
                <div class="food-result-actions">
                    <button class="btn btn-sm btn-outline food-details-btn">
                        <i class="fas fa-info-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-primary add-food-btn">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `;
            
            favoritesList.appendChild(listItem);
        });
        
        resultsContainer.appendChild(favoritesList);
        
        // Ajouter des écouteurs d'événements pour les boutons
        const detailsBtns = document.querySelectorAll('.food-details-btn');
        detailsBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const foodId = e.target.closest('.food-result-item').dataset.id;
                this.showFoodDetails(foodId);
            });
        });
        
        const addBtns = document.querySelectorAll('.add-food-btn');
        addBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const foodId = e.target.closest('.food-result-item').dataset.id;
                this.quickAddFood(foodId);
            });
        });
    }

    // Afficher mes aliments
    displayMyFoods() {
        const resultsContainer = document.getElementById('search-results');
        if (!resultsContainer) return;
        
        // Vider le conteneur
        resultsContainer.innerHTML = '';
        
        // Afficher un message et un bouton pour ajouter un aliment personnalisé
        const myFoodsContainer = document.createElement('div');
        myFoodsContainer.className = 'my-foods-container';
        
        const addFoodBtn = document.createElement('button');
        addFoodBtn.className = 'btn btn-primary mb-3';
        addFoodBtn.innerHTML = '<i class="fas fa-plus mr-1"></i> Ajouter un aliment personnalisé';
        addFoodBtn.addEventListener('click', () => this.showAddCustomFoodModal());
        
        myFoodsContainer.appendChild(addFoodBtn);
        
        // Filtrer les aliments personnalisés
        const myFoods = this.foodDatabase.filter(food => food.isCustom);
        
        // Afficher un message si aucun aliment personnalisé
        if (myFoods.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'no-results text-center p-3';
            noResults.textContent = 'Aucun aliment personnalisé';
            myFoodsContainer.appendChild(noResults);
            resultsContainer.appendChild(myFoodsContainer);
            return;
        }
        
        // Créer une liste pour les aliments personnalisés
        const myFoodsList = document.createElement('ul');
        myFoodsList.className = 'food-results-list';
        
        // Ajouter chaque aliment personnalisé à la liste
        myFoods.forEach(food => {
            const listItem = document.createElement('li');
            listItem.className = 'food-result-item';
            listItem.dataset.id = food.id;
            
            listItem.innerHTML = `
                <div class="food-result-content">
                    <div class="food-result-name">${food.name}</div>
                    <div class="food-result-details">
                        <span class="food-result-serving">${food.servingSize} ${food.servingUnit}</span>
                        <span class="food-result-calories">${food.calories} cal</span>
                    </div>
                </div>
                <div class="food-result-actions">
                    <button class="btn btn-sm btn-outline food-details-btn">
                        <i class="fas fa-info-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-primary add-food-btn">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `;
            
            myFoodsList.appendChild(listItem);
        });
        
        myFoodsContainer.appendChild(myFoodsList);
        resultsContainer.appendChild(myFoodsContainer);
        
        // Ajouter des écouteurs d'événements pour les boutons
        const detailsBtns = document.querySelectorAll('.food-details-btn');
        detailsBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const foodId = e.target.closest('.food-result-item').dataset.id;
                this.showFoodDetails(foodId);
            });
        });
        
        const addBtns = document.querySelectorAll('.add-food-btn');
        addBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const foodId = e.target.closest('.food-result-item').dataset.id;
                this.quickAddFood(foodId);
            });
        });
    }

    // Appliquer les filtres
    applyFilters() {
        const mealTypeFilter = document.getElementById('meal-type-filter');
        const caloriesFilter = document.getElementById('calories-filter');
        const macrosFilter = document.getElementById('macros-filter');
        
        if (!mealTypeFilter || !caloriesFilter || !macrosFilter) return;
        
        const mealType = mealTypeFilter.value;
        const calories = caloriesFilter.value;
        const macros = macrosFilter.value;
        
        // Filtrer les résultats
        let filteredResults = [...this.searchResults];
        
        // Filtre par type de repas
        if (mealType !== 'all') {
            filteredResults = filteredResults.filter(food => food.mealType === mealType);
        }
        
        // Filtre par calories
        if (calories !== 'all') {
            switch (calories) {
                case 'under100':
                    filteredResults = filteredResults.filter(food => food.calories < 100);
                    break;
                case '100-200':
                    filteredResults = filteredResults.filter(food => food.calories >= 100 && food.calories < 200);
                    break;
                case '200-300':
                    filteredResults = filteredResults.filter(food => food.calories >= 200 && food.calories < 300);
                    break;
                case '300-500':
                    filteredResults = filteredResults.filter(food => food.calories >= 300 && food.calories < 500);
                    break;
                case 'over500':
                    filteredResults = filteredResults.filter(food => food.calories >= 500);
                    break;
            }
        }
        
        // Filtre par macronutriments
        if (macros !== 'all') {
            switch (macros) {
                case 'high-protein':
                    filteredResults = filteredResults.filter(food => food.protein >= 15);
                    break;
                case 'low-carb':
                    filteredResults = filteredResults.filter(food => food.carbs < 10);
                    break;
                case 'low-fat':
                    filteredResults = filteredResults.filter(food => food.fat < 3);
                    break;
            }
        }
        
        // Afficher les résultats filtrés
        this.displaySearchResults(filteredResults);
    }

    // Afficher les détails d'un aliment
    showFoodDetails(foodId) {
        const food = this.getFoodById(foodId);
        if (!food) return;
        
        // Mettre à jour les détails dans le modal
        document.getElementById('food-details-name').textContent = food.name;
        document.getElementById('food-details-calories').textContent = food.calories;
        document.getElementById('food-details-protein').textContent = `${food.protein}g`;
        document.getElementById('food-details-carbs').textContent = `${food.carbs}g`;
        document.getElementById('food-details-fat').textContent = `${food.fat}g`;
        document.getElementById('food-details-fiber').textContent = `${food.fiber}g`;
        document.getElementById('food-details-sugar').textContent = `${food.sugar}g`;
        document.getElementById('food-details-sodium').textContent = `${food.sodium}mg`;
        document.getElementById('food-details-cholesterol').textContent = `${food.cholesterol}mg`;
        document.getElementById('food-details-potassium').textContent = `${food.potassium}mg`;
        
        // Mettre à jour le bouton de favoris
        const addToFavoritesBtn = document.getElementById('add-to-favorites');
        if (this.favoriteItems.includes(foodId)) {
            addToFavoritesBtn.innerHTML = '<i class="fas fa-heart"></i> Retirer des favoris';
            addToFavoritesBtn.classList.add('favorited');
        } else {
            addToFavoritesBtn.innerHTML = '<i class="far fa-heart"></i> Ajouter aux favoris';
            addToFavoritesBtn.classList.remove('favorited');
        }
        
        // Stocker l'ID de l'aliment dans le modal
        const modal = document.getElementById('food-details-modal');
        modal.dataset.foodId = foodId;
        
        // Afficher le modal
        modal.style.display = 'block';
    }

    // Masquer le modal de détails d'aliment
    hideFoodDetailsModal() {
        const modal = document.getElementById('food-details-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Ajouter un aliment au journal
    addFoodToDiary() {
        const modal = document.getElementById('food-details-modal');
        if (!modal) return;
        
        const foodId = modal.dataset.foodId;
        const food = this.getFoodById(foodId);
        if (!food) return;
        
        // Récupérer la taille de la portion
        const servingSize = parseFloat(document.getElementById('serving-size').value);
        const servingUnit = document.getElementById('serving-unit').value;
        
        // Calculer les valeurs nutritionnelles en fonction de la portion
        const calories = Math.round(food.calories * servingSize);
        const protein = Math.round(food.protein * servingSize * 10) / 10;
        const carbs = Math.round(food.carbs * servingSize * 10) / 10;
        const fat = Math.round(food.fat * servingSize * 10) / 10;
        
        // Simuler l'ajout au journal
        alert(`${food.name} (${calories} cal) ajouté au journal`);
        
        // Ajouter aux recherches récentes
        this.addToRecentSearches(food.name);
        
        // Masquer le modal
        this.hideFoodDetailsModal();
        
        // Mettre à jour les calories totales (simulation)
        const foodCalories = document.getElementById('food-calories');
        if (foodCalories) {
            const currentCalories = parseInt(foodCalories.textContent);
            foodCalories.textContent = currentCalories + calories;
        }
        
        // Mettre à jour le cercle de progression des calories
        if (typeof calculateMealCaloriesPercentage === 'function') {
            const percentage = calculateMealCaloriesPercentage();
            if (typeof createProgressCircle === 'function') {
                createProgressCircle('calories-circle', percentage);
            }
        }
    }

    // Ajouter rapidement un aliment au journal
    quickAddFood(foodId) {
        const food = this.getFoodById(foodId);
        if (!food) return;
        
        // Simuler l'ajout au journal
        alert(`${food.name} (${food.calories} cal) ajouté au journal`);
        
        // Ajouter aux recherches récentes
        this.addToRecentSearches(food.name);
        
        // Mettre à jour les calories totales (simulation)
        const foodCalories = document.getElementById('food-calories');
        if (foodCalories) {
            const currentCalories = parseInt(foodCalories.textContent);
            foodCalories.textContent = currentCalories + food.calories;
        }
        
        // Mettre à jour le cercle de progression des calories
        if (typeof calculateMealCaloriesPercentage === 'function') {
            const percentage = calculateMealCaloriesPercentage();
            if (typeof createProgressCircle === 'function') {
                createProgressCircle('calories-circle', percentage);
            }
        }
    }

    // Ajouter/retirer un aliment des favoris
    toggleFavorite() {
        const modal = document.getElementById('food-details-modal');
        if (!modal) return;
        
        const foodId = modal.dataset.foodId;
        const addToFavoritesBtn = document.getElementById('add-to-favorites');
        
        if (this.favoriteItems.includes(foodId)) {
            // Retirer des favoris
            this.favoriteItems = this.favoriteItems.filter(id => id !== foodId);
            addToFavoritesBtn.innerHTML = '<i class="far fa-heart"></i> Ajouter aux favoris';
            addToFavoritesBtn.classList.remove('favorited');
        } else {
            // Ajouter aux favoris
            this.favoriteItems.push(foodId);
            addToFavoritesBtn.innerHTML = '<i class="fas fa-heart"></i> Retirer des favoris';
            addToFavoritesBtn.classList.add('favorited');
        }
        
        // Sauvegarder les données
        this.saveData();
    }

    // Ajouter une recherche aux recherches récentes
    addToRecentSearches(query) {
        // Vérifier si la recherche existe déjà
        const existingIndex = this.recentSearches.findIndex(search => search.query.toLowerCase() === query.toLowerCase());
        
        if (existingIndex !== -1) {
            // Supprimer la recherche existante
            this.recentSearches.splice(existingIndex, 1);
        }
        
        // Ajouter la nouvelle recherche au début
        this.recentSearches.unshift({
            query: query,
            date: new Date().toISOString()
        });
        
        // Limiter à 10 recherches récentes
        if (this.recentSearches.length > 10) {
            this.recentSearches = this.recentSearches.slice(0, 10);
        }
        
        // Sauvegarder les données
        this.saveData();
    }

    // Obtenir un aliment par son ID
    getFoodById(id) {
        return this.foodDatabase.find(food => food.id === id);
    }

    // Formater une date
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    // Sauvegarder les données
    saveData() {
        const data = {
            recentSearches: this.recentSearches,
            favoriteItems: this.favoriteItems
        };
        
        localStorage.setItem('foodSearchData', JSON.stringify(data));
    }

    // Charger les données
    loadData() {
        const data = localStorage.getItem('foodSearchData');
        if (data) {
            const parsedData = JSON.parse(data);
            this.recentSearches = parsedData.recentSearches || [];
            this.favoriteItems = parsedData.favoriteItems || [];
        }
    }

    // Remplir la base de données d'aliments simulée
    populateFoodDatabase() {
        this.foodDatabase = [
            {
                id: '1',
                name: 'Pomme',
                category: 'Fruits',
                calories: 95,
                protein: 0.5,
                carbs: 25,
                fat: 0.3,
                fiber: 4.4,
                sugar: 19,
                sodium: 2,
                cholesterol: 0,
                potassium: 195,
                servingSize: 1,
                servingUnit: 'moyenne',
                mealType: 'snack',
                isCustom: false
            },
            {
                id: '2',
                name: 'Banane',
                category: 'Fruits',
                calories: 105,
                protein: 1.3,
                carbs: 27,
                fat: 0.4,
                fiber: 3.1,
                sugar: 14,
                sodium: 1,
                cholesterol: 0,
                potassium: 422,
                servingSize: 1,
                servingUnit: 'moyenne',
                mealType: 'snack',
                isCustom: false
            },
            {
                id: '3',
                name: 'Poulet grillé',
                category: 'Viandes',
                calories: 165,
                protein: 31,
                carbs: 0,
                fat: 3.6,
                fiber: 0,
                sugar: 0,
                sodium: 74,
                cholesterol: 85,
                potassium: 220,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'dinner',
                isCustom: false
            },
            {
                id: '4',
                name: 'Riz blanc cuit',
                category: 'Céréales',
                calories: 130,
                protein: 2.7,
                carbs: 28,
                fat: 0.3,
                fiber: 0.4,
                sugar: 0.1,
                sodium: 1,
                cholesterol: 0,
                potassium: 35,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'lunch',
                isCustom: false
            },
            {
                id: '5',
                name: 'Œuf',
                category: 'Œufs',
                calories: 78,
                protein: 6.3,
                carbs: 0.6,
                fat: 5.3,
                fiber: 0,
                sugar: 0.6,
                sodium: 62,
                cholesterol: 186,
                potassium: 63,
                servingSize: 1,
                servingUnit: 'gros',
                mealType: 'breakfast',
                isCustom: false
            },
            {
                id: '6',
                name: 'Pain complet',
                category: 'Céréales',
                calories: 79,
                protein: 3.6,
                carbs: 14,
                fat: 1.1,
                fiber: 2.4,
                sugar: 1.4,
                sodium: 142,
                cholesterol: 0,
                potassium: 85,
                servingSize: 1,
                servingUnit: 'tranche',
                mealType: 'breakfast',
                isCustom: false
            },
            {
                id: '7',
                name: 'Yaourt nature',
                category: 'Produits laitiers',
                calories: 59,
                protein: 3.5,
                carbs: 4.7,
                fat: 3.3,
                fiber: 0,
                sugar: 4.7,
                sodium: 36,
                cholesterol: 13,
                potassium: 141,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'breakfast',
                isCustom: false
            },
            {
                id: '8',
                name: 'Saumon',
                category: 'Poissons',
                calories: 208,
                protein: 20,
                carbs: 0,
                fat: 13,
                fiber: 0,
                sugar: 0,
                sodium: 59,
                cholesterol: 55,
                potassium: 384,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'dinner',
                isCustom: false
            },
            {
                id: '9',
                name: 'Brocoli',
                category: 'Légumes',
                calories: 55,
                protein: 3.7,
                carbs: 11,
                fat: 0.6,
                fiber: 5.2,
                sugar: 2.6,
                sodium: 33,
                cholesterol: 0,
                potassium: 316,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'dinner',
                isCustom: false
            },
            {
                id: '10',
                name: 'Avocat',
                category: 'Fruits',
                calories: 160,
                protein: 2,
                carbs: 8.5,
                fat: 14.7,
                fiber: 6.7,
                sugar: 0.7,
                sodium: 7,
                cholesterol: 0,
                potassium: 485,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'lunch',
                isCustom: false
            },
            {
                id: '11',
                name: 'Amandes',
                category: 'Noix et graines',
                calories: 579,
                protein: 21,
                carbs: 22,
                fat: 49,
                fiber: 12.5,
                sugar: 4.4,
                sodium: 1,
                cholesterol: 0,
                potassium: 733,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'snack',
                isCustom: false
            },
            {
                id: '12',
                name: 'Lentilles cuites',
                category: 'Légumineuses',
                calories: 116,
                protein: 9,
                carbs: 20,
                fat: 0.4,
                fiber: 7.9,
                sugar: 1.8,
                sodium: 2,
                cholesterol: 0,
                potassium: 369,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'lunch',
                isCustom: false
            },
            {
                id: '13',
                name: 'Chocolat noir',
                category: 'Sucreries',
                calories: 598,
                protein: 7.8,
                carbs: 46,
                fat: 43,
                fiber: 10.9,
                sugar: 24,
                sodium: 6,
                cholesterol: 3,
                potassium: 559,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'snack',
                isCustom: false
            },
            {
                id: '14',
                name: 'Fromage blanc',
                category: 'Produits laitiers',
                calories: 98,
                protein: 11,
                carbs: 3.5,
                fat: 4.3,
                fiber: 0,
                sugar: 3.5,
                sodium: 41,
                cholesterol: 14,
                potassium: 104,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'snack',
                isCustom: false
            },
            {
                id: '15',
                name: 'Quinoa cuit',
                category: 'Céréales',
                calories: 120,
                protein: 4.4,
                carbs: 21,
                fat: 1.9,
                fiber: 2.8,
                sugar: 0.9,
                sodium: 7,
                cholesterol: 0,
                potassium: 172,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'lunch',
                isCustom: false
            },
            {
                id: '16',
                name: 'Salade verte',
                category: 'Légumes',
                calories: 15,
                protein: 1.4,
                carbs: 2.9,
                fat: 0.2,
                fiber: 1.3,
                sugar: 0.8,
                sodium: 28,
                cholesterol: 0,
                potassium: 194,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'lunch',
                isCustom: false
            },
            {
                id: '17',
                name: 'Thon en conserve',
                category: 'Poissons',
                calories: 116,
                protein: 25,
                carbs: 0,
                fat: 1,
                fiber: 0,
                sugar: 0,
                sodium: 320,
                cholesterol: 40,
                potassium: 201,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'lunch',
                isCustom: false
            },
            {
                id: '18',
                name: 'Huile d\'olive',
                category: 'Huiles',
                calories: 884,
                protein: 0,
                carbs: 0,
                fat: 100,
                fiber: 0,
                sugar: 0,
                sodium: 2,
                cholesterol: 0,
                potassium: 1,
                servingSize: 1,
                servingUnit: 'cuillère à soupe',
                mealType: 'lunch',
                isCustom: false
            },
            {
                id: '19',
                name: 'Muesli',
                category: 'Céréales',
                calories: 384,
                protein: 11,
                carbs: 66,
                fat: 7.5,
                fiber: 8.5,
                sugar: 16,
                sodium: 5,
                cholesterol: 0,
                potassium: 362,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'breakfast',
                isCustom: false
            },
            {
                id: '20',
                name: 'Tomate',
                category: 'Légumes',
                calories: 18,
                protein: 0.9,
                carbs: 3.9,
                fat: 0.2,
                fiber: 1.2,
                sugar: 2.6,
                sodium: 5,
                cholesterol: 0,
                potassium: 237,
                servingSize: 100,
                servingUnit: 'g',
                mealType: 'lunch',
                isCustom: false
            },
            {
                id: '21',
                name: 'Smoothie maison',
                category: 'Boissons',
                calories: 250,
                protein: 5,
                carbs: 45,
                fat: 3,
                fiber: 6,
                sugar: 30,
                sodium: 15,
                cholesterol: 0,
                potassium: 500,
                servingSize: 1,
                servingUnit: 'verre',
                mealType: 'breakfast',
                isCustom: true
            },
            {
                id: '22',
                name: 'Salade de poulet',
                category: 'Plats préparés',
                calories: 350,
                protein: 30,
                carbs: 15,
                fat: 18,
                fiber: 4,
                sugar: 3,
                sodium: 450,
                cholesterol: 80,
                potassium: 600,
                servingSize: 1,
                servingUnit: 'portion',
                mealType: 'lunch',
                isCustom: true
            }
        ];
    }
}

// Initialiser la recherche d'aliments lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur la page appropriée
    const foodSearchContainer = document.getElementById('food-search-container');
    if (foodSearchContainer) {
        const foodSearch = new FoodSearch();
        foodSearch.init();
    }
});
