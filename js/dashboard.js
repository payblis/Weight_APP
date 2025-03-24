// JavaScript spécifique pour la page dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Charger les données du tableau de bord
    loadDashboardData();
    
    // Fonction pour charger les données du tableau de bord
    function loadDashboardData() {
        // Charger les données de poids
        fetch('../api/weight.php')
            .then(response => response.json())
            .then(data => {
                updateWeightStats(data);
                createWeightChart(data.weight_logs);
            })
            .catch(error => {
                console.error('Erreur lors du chargement des données de poids:', error);
            });
        
        // Charger les activités récentes
        fetch('../api/activity.php')
            .then(response => response.json())
            .then(data => {
                updateRecentActivities(data.activity_logs.slice(0, 3));
            })
            .catch(error => {
                console.error('Erreur lors du chargement des activités:', error);
            });
        
        // Charger les repas recommandés
        fetch('../api/meal.php')
            .then(response => response.json())
            .then(data => {
                updateDailyCalories(data.daily_stats);
                updateRecommendedMeals(data.meals.slice(0, 3));
            })
            .catch(error => {
                console.error('Erreur lors du chargement des repas:', error);
            });
        
        // Charger le programme personnalisé
        loadCustomProgram();
    }
    
    // Mettre à jour les statistiques de poids
    function updateWeightStats(data) {
        const currentWeight = document.getElementById('current-weight');
        const targetWeight = document.getElementById('target-weight');
        const weightLost = document.getElementById('weight-lost');
        
        if (data.weight_logs && data.weight_logs.length > 0) {
            currentWeight.textContent = data.weight_logs[0].weight;
        } else {
            currentWeight.textContent = data.initial_weight;
        }
        
        targetWeight.textContent = data.target_weight;
        
        const initialWeight = parseFloat(data.initial_weight);
        const current = parseFloat(currentWeight.textContent);
        const weightLostValue = initialWeight - current;
        
        weightLost.textContent = weightLostValue.toFixed(1);
    }
    
    // Mettre à jour les calories quotidiennes
    function updateDailyCalories(dailyStats) {
        const dailyCalories = document.getElementById('daily-calories');
        if (dailyStats && dailyStats.daily_calorie_target) {
            dailyCalories.textContent = Math.round(dailyStats.daily_calorie_target);
        } else {
            dailyCalories.textContent = "2000";
        }
    }
    
    // Créer le graphique de poids
    function createWeightChart(weightLogs) {
        if (!weightLogs || weightLogs.length === 0) return;
        
        const ctx = document.getElementById('weightChart').getContext('2d');
        
        // Trier les logs par date
        weightLogs.sort((a, b) => new Date(a.log_date) - new Date(b.log_date));
        
        const dates = weightLogs.map(log => log.log_date);
        const weights = weightLogs.map(log => log.weight);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Poids (kg)',
                    data: weights,
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    }
    
    // Mettre à jour les activités récentes
    function updateRecentActivities(activities) {
        const recentActivitiesDiv = document.getElementById('recent-activities');
        
        if (!activities || activities.length === 0) {
            recentActivitiesDiv.innerHTML = '<p class="text-center">Aucune activité récente</p>';
            return;
        }
        
        let html = '';
        activities.forEach(activity => {
            html += `
                <div class="activity-card">
                    <div class="activity-icon">
                        <i class="fas fa-running"></i>
                    </div>
                    <div class="activity-details">
                        <h4>${activity.activity_name}</h4>
                        <p>${activity.log_date} - ${activity.duration_minutes} minutes</p>
                        <p><strong>${Math.round(activity.calories_burned)}</strong> calories brûlées</p>
                    </div>
                </div>
            `;
        });
        
        recentActivitiesDiv.innerHTML = html;
    }
    
    // Mettre à jour les repas recommandés
    function updateRecommendedMeals(meals) {
        const recommendedMealsDiv = document.getElementById('recommended-meals');
        
        if (!meals || meals.length === 0) {
            recommendedMealsDiv.innerHTML = '<p class="text-center">Aucun repas recommandé</p>';
            return;
        }
        
        let html = '';
        meals.forEach(meal => {
            html += `
                <div class="card">
                    <h4>${meal.name}</h4>
                    <p>${meal.description}</p>
                    <p><strong>${meal.calories}</strong> calories</p>
                </div>
            `;
        });
        
        recommendedMealsDiv.innerHTML = html;
    }
    
    // Charger le programme personnalisé
    function loadCustomProgram() {
        const customProgramDiv = document.getElementById('custom-program');
        
        // Vérifier si l'API est configurée
        fetch('../api/chatgpt.php')
            .then(response => response.json())
            .then(data => {
                if (!data.api_configured) {
                    customProgramDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <p>L'API ChatGPT n'est pas configurée. Veuillez configurer votre clé API dans votre profil pour accéder aux fonctionnalités d'IA.</p>
                        </div>
                        <div class="text-center">
                            <a href="profile.php" class="btn btn-primary">Configurer l'API</a>
                        </div>
                    `;
                } else {
                    customProgramDiv.innerHTML = `
                        <p class="text-center">Cliquez sur le bouton ci-dessous pour générer un programme personnalisé basé sur votre profil.</p>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification de l\'API:', error);
                customProgramDiv.innerHTML = '<p class="text-danger">Erreur lors de la vérification de l\'API</p>';
            });
    }
});
