// JavaScript spécifique pour la page des repas
document.addEventListener('DOMContentLoaded', function() {
    // Charger les données de repas
    loadMealData();
    
    // Fonction pour charger les données de repas
    function loadMealData() {
        fetch('../api/meal.php')
            .then(response => response.json())
            .then(data => {
                populateMealSelect(data.meals);
                updateNutritionStats(data.daily_stats);
                createMealChart(data.meal_logs);
                updateMealHistory(data.meal_logs);
            })
            .catch(error => {
                console.error('Erreur lors du chargement des données de repas:', error);
            });
    }
    
    // Remplir le select des repas
    function populateMealSelect(meals) {
        const mealSelect = document.getElementById('meal_id');
        
        if (!meals || meals.length === 0 || !mealSelect) return;
        
        // Conserver l'option par défaut
        let html = '<option value="">Sélectionnez un repas ou saisissez manuellement</option>';
        
        // Grouper les repas par catégorie
        const categorizedMeals = {};
        meals.forEach(meal => {
            if (!categorizedMeals[meal.category_id]) {
                categorizedMeals[meal.category_id] = [];
            }
            categorizedMeals[meal.category_id].push(meal);
        });
        
        // Créer les options groupées par catégorie
        for (const categoryId in categorizedMeals) {
            // Déterminer le nom de la catégorie (idéalement, cela devrait venir de l'API)
            let categoryName = "Catégorie " + categoryId;
            switch (parseInt(categoryId)) {
                case 1: categoryName = "Petit-déjeuner"; break;
                case 2: categoryName = "Déjeuner"; break;
                case 3: categoryName = "Dîner"; break;
                case 4: categoryName = "Collation"; break;
            }
            
            html += `<optgroup label="${categoryName}">`;
            categorizedMeals[categoryId].forEach(meal => {
                html += `<option value="${meal.id}" data-calories="${meal.calories}">${meal.name}</option>`;
            });
            html += '</optgroup>';
        }
        
        mealSelect.innerHTML = html;
        
        // Ajouter un événement pour remplir automatiquement les calories
        mealSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const calories = selectedOption.getAttribute('data-calories');
                document.getElementById('calories').value = calories;
            }
        });
    }
    
    // Mettre à jour les statistiques nutritionnelles
    function updateNutritionStats(dailyStats) {
        const dailyCaloriesConsumedElement = document.getElementById('daily-calories-consumed');
        const dailyCaloriesTargetElement = document.getElementById('daily-calories-target');
        const caloriesRemainingElement = document.getElementById('calories-remaining');
        const caloriesProgressBarElement = document.getElementById('calories-progress-bar');
        const caloriesProgressTextElement = document.getElementById('calories-progress-text');
        
        if (dailyStats) {
            const consumed = parseFloat(dailyStats.daily_calories_consumed) || 0;
            const target = parseFloat(dailyStats.daily_calorie_target) || 2000;
            const remaining = target - consumed;
            
            dailyCaloriesConsumedElement.textContent = Math.round(consumed);
            dailyCaloriesTargetElement.textContent = Math.round(target);
            caloriesRemainingElement.textContent = Math.round(remaining);
            
            // Calculer le pourcentage de progression
            const progressPercentage = Math.min(100, Math.max(0, (consumed / target) * 100));
            
            caloriesProgressBarElement.style.width = progressPercentage.toFixed(1) + '%';
            caloriesProgressTextElement.textContent = progressPercentage.toFixed(1) + '% de l\'objectif calorique atteint';
            
            // Changer la couleur de la barre de progression en fonction du pourcentage
            if (progressPercentage > 100) {
                caloriesProgressBarElement.style.backgroundColor = 'var(--danger-color)';
            } else if (progressPercentage > 80) {
                caloriesProgressBarElement.style.backgroundColor = 'var(--warning-color)';
            } else {
                caloriesProgressBarElement.style.backgroundColor = 'var(--success-color)';
            }
        } else {
            dailyCaloriesConsumedElement.textContent = '0';
            dailyCaloriesTargetElement.textContent = '2000';
            caloriesRemainingElement.textContent = '2000';
            caloriesProgressBarElement.style.width = '0%';
            caloriesProgressTextElement.textContent = '0% de l\'objectif calorique atteint';
        }
    }
    
    // Créer le graphique de repas
    function createMealChart(mealLogs) {
        if (!mealLogs || mealLogs.length === 0) return;
        
        const ctx = document.getElementById('mealChart').getContext('2d');
        
        // Regrouper les données par date et par type de repas
        const groupedData = {};
        const last7Days = [];
        
        // Générer les 7 derniers jours
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const dateString = date.toISOString().split('T')[0];
            last7Days.push(dateString);
            groupedData[dateString] = {
                'petit-déjeuner': 0,
                'déjeuner': 0,
                'dîner': 0,
                'collation': 0
            };
        }
        
        // Remplir les données pour les repas existants
        mealLogs.forEach(log => {
            if (groupedData[log.log_date]) {
                groupedData[log.log_date][log.meal_time] += parseFloat(log.calories);
            }
        });
        
        // Préparer les données pour le graphique
        const datasets = [
            {
                label: 'Petit-déjeuner',
                data: last7Days.map(date => groupedData[date]['petit-déjeuner']),
                backgroundColor: 'rgba(255, 193, 7, 0.7)'
            },
            {
                label: 'Déjeuner',
                data: last7Days.map(date => groupedData[date]['déjeuner']),
                backgroundColor: 'rgba(76, 175, 80, 0.7)'
            },
            {
                label: 'Dîner',
                data: last7Days.map(date => groupedData[date]['dîner']),
                backgroundColor: 'rgba(33, 150, 243, 0.7)'
            },
            {
                label: 'Collation',
                data: last7Days.map(date => groupedData[date]['collation']),
                backgroundColor: 'rgba(156, 39, 176, 0.7)'
            }
        ];
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: last7Days,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Calories'
                        }
                    }
                }
            }
        });
    }
    
    // Mettre à jour l'historique des repas
    function updateMealHistory(mealLogs) {
        const mealHistoryBody = document.getElementById('meal-history-body');
        
        if (!mealLogs || mealLogs.length === 0) {
            mealHistoryBody.innerHTML = '<tr><td colspan="6" class="text-center">Aucun repas enregistré</td></tr>';
            return;
        }
        
        // Trier les logs par date (le plus récent en premier)
        const sortedLogs = [...mealLogs].sort((a, b) => {
            const dateComparison = new Date(b.log_date) - new Date(a.log_date);
            if (dateComparison !== 0) return dateComparison;
            
            // Si même date, trier par moment du repas
            const mealTimeOrder = {
                'petit-déjeuner': 1,
                'déjeuner': 2,
                'dîner': 3,
                'collation': 4
            };
            return mealTimeOrder[a.meal_time] - mealTimeOrder[b.meal_time];
        });
        
        let html = '';
        sortedLogs.forEach(log => {
            const mealName = log.meal_name || log.meal_name_from_db || '-';
            
            html += `
                <tr>
                    <td>${log.log_date}</td>
                    <td>${mealName}</td>
                    <td>${log.meal_time}</td>
                    <td>${Math.round(log.calories)}</td>
                    <td>${log.notes || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteMealLog(${log.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        mealHistoryBody.innerHTML = html;
    }
    
    // Fonction pour supprimer un enregistrement de repas
    window.deleteMealLog = function(logId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce repas ?')) {
            fetch(`../api/delete_meal.php?id=${logId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recharger les données
                    loadMealData();
                } else {
                    alert('Erreur lors de la suppression: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la suppression.');
            });
        }
    };
    
    // Gestion du formulaire d'ajout de repas
    const mealLogForm = document.getElementById('mealLogForm');
    if (mealLogForm) {
        mealLogForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../api/meal.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.json();
                }
            })
            .then(data => {
                if (data && data.error) {
                    alert('Erreur: ' + data.error);
                } else {
                    // Réinitialiser le formulaire
                    mealLogForm.reset();
                    
                    // Définir la date à aujourd'hui
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('log_date').value = today;
                    
                    // Recharger les données
                    loadMealData();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        });
    }
    
    // Charger les repas recommandés
    loadRecommendedMeals();
    
    function loadRecommendedMeals() {
        const recommendedMealsDiv = document.getElementById('recommended-meals');
        
        // Vérifier si l'API est configurée
        fetch('../api/chatgpt.php')
            .then(response => response.json())
            .then(data => {
                if (!data.api_configured) {
                    recommendedMealsDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <p>L'API ChatGPT n'est pas configurée. Veuillez configurer votre clé API dans votre profil pour accéder aux recommandations personnalisées.</p>
                        </div>
                        <div class="text-center">
                            <a href="profile.php" class="btn btn-primary">Configurer l'API</a>
                        </div>
                    `;
                } else {
                    // Charger les repas disponibles
                    fetch('../api/meal.php')
                        .then(response => response.json())
                        .then(mealData => {
                            // Afficher quelques repas suggérés
                            if (mealData.meals && mealData.meals.length > 0) {
                                let html = '';
                                // Prendre 4 repas aléatoires
                                const shuffled = [...mealData.meals].sort(() => 0.5 - Math.random());
                                const selected = shuffled.slice(0, 4);
                                
                                selected.forEach(meal => {
                                    html += `
                                        <div class="card meal-card">
                                            <div class="meal-card-body">
                                                <h4>${meal.name}</h4>
                                                <p>${meal.description || 'Repas recommandé pour votre profil'}</p>
                                                <p><strong>${meal.calories}</strong> calories</p>
                                                <button class="btn btn-primary btn-sm" onclick="selectMeal(${meal.id}, '${meal.name}')">
                                                    Sélectionner
                                                </button>
                                            </div>
                                        </div>
                                    `;
                                });
                                
                                recommendedMealsDiv.innerHTML = html;
                            } else {
                                recommendedMealsDiv.innerHTML = '<p class="text-center">Aucun repas disponible</p>';
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            recommendedMealsDiv.innerHTML = '<p class="text-danger">Erreur lors du chargement des repas</p>';
                        });
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification de l\'API:', error);
                recommendedMealsDiv.innerHTML = '<p class="text-danger">Erreur lors de la vérification de l\'API</p>';
            });
    }
    
    // Fonction pour sélectionner un repas recommandé
    window.selectMeal = function(mealId, mealName) {
        const mealSelect = document.getElementById('meal_id');
        if (mealSelect) {
            mealSelect.value = mealId;
            
            // Déclencher l'événement change pour mettre à jour les calories
            const event = new Event('change');
            mealSelect.dispatchEvent(event);
            
            // Faire défiler jusqu'au formulaire
            mealLogForm.scrollIntoView({ behavior: 'smooth' });
        }
    };
});
