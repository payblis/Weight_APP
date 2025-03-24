// JavaScript spécifique pour la page des activités
document.addEventListener('DOMContentLoaded', function() {
    // Charger les données d'activités
    loadActivityData();
    
    // Fonction pour charger les données d'activités
    function loadActivityData() {
        fetch('../api/activity.php')
            .then(response => response.json())
            .then(data => {
                populateActivitySelect(data.activities);
                updateActivityStats(data.stats);
                createActivityChart(data.activity_logs);
                updateActivityHistory(data.activity_logs);
            })
            .catch(error => {
                console.error('Erreur lors du chargement des données d\'activités:', error);
            });
    }
    
    // Remplir le select des activités
    function populateActivitySelect(activities) {
        const activitySelect = document.getElementById('activity_id');
        
        if (!activities || activities.length === 0 || !activitySelect) return;
        
        // Conserver l'option par défaut
        let html = '<option value="">Sélectionnez une activité</option>';
        
        // Grouper les activités par catégorie
        const categorizedActivities = {};
        activities.forEach(activity => {
            if (!categorizedActivities[activity.category]) {
                categorizedActivities[activity.category] = [];
            }
            categorizedActivities[activity.category].push(activity);
        });
        
        // Créer les options groupées par catégorie
        for (const category in categorizedActivities) {
            html += `<optgroup label="${category}">`;
            categorizedActivities[category].forEach(activity => {
                html += `<option value="${activity.id}" data-calories="${activity.calories_per_hour}">${activity.name}</option>`;
            });
            html += '</optgroup>';
        }
        
        activitySelect.innerHTML = html;
    }
    
    // Mettre à jour les statistiques d'activités
    function updateActivityStats(stats) {
        const totalActivitiesElement = document.getElementById('total-activities');
        const totalDurationElement = document.getElementById('total-duration');
        const totalCaloriesElement = document.getElementById('total-calories');
        const streakElement = document.getElementById('streak');
        
        if (stats) {
            totalActivitiesElement.textContent = stats.total_activities || '0';
            totalDurationElement.textContent = stats.total_duration || '0';
            totalCaloriesElement.textContent = Math.round(stats.total_calories) || '0';
            
            // Le calcul de streak nécessiterait une logique supplémentaire côté serveur
            streakElement.textContent = '0'; // Valeur par défaut
        } else {
            totalActivitiesElement.textContent = '0';
            totalDurationElement.textContent = '0';
            totalCaloriesElement.textContent = '0';
            streakElement.textContent = '0';
        }
    }
    
    // Créer le graphique d'activités
    function createActivityChart(activityLogs) {
        if (!activityLogs || activityLogs.length === 0) return;
        
        const ctx = document.getElementById('activityChart').getContext('2d');
        
        // Trier les logs par date
        const sortedLogs = [...activityLogs].sort((a, b) => new Date(a.log_date) - new Date(b.log_date));
        
        // Regrouper les données par date
        const groupedData = {};
        sortedLogs.forEach(log => {
            if (!groupedData[log.log_date]) {
                groupedData[log.log_date] = 0;
            }
            groupedData[log.log_date] += parseFloat(log.calories_burned);
        });
        
        const dates = Object.keys(groupedData);
        const calories = Object.values(groupedData);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Calories brûlées',
                    data: calories,
                    backgroundColor: 'rgba(33, 150, 243, 0.7)',
                    borderColor: 'rgba(33, 150, 243, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Calories'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    }
    
    // Mettre à jour l'historique des activités
    function updateActivityHistory(activityLogs) {
        const activityHistoryBody = document.getElementById('activity-history-body');
        
        if (!activityLogs || activityLogs.length === 0) {
            activityHistoryBody.innerHTML = '<tr><td colspan="6" class="text-center">Aucune activité enregistrée</td></tr>';
            return;
        }
        
        // Trier les logs par date (le plus récent en premier)
        const sortedLogs = [...activityLogs].sort((a, b) => new Date(b.log_date) - new Date(a.log_date));
        
        let html = '';
        sortedLogs.forEach(log => {
            html += `
                <tr>
                    <td>${log.log_date}</td>
                    <td>${log.activity_name}</td>
                    <td>${log.duration_minutes}</td>
                    <td>${Math.round(log.calories_burned)}</td>
                    <td>${log.notes || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteActivityLog(${log.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        activityHistoryBody.innerHTML = html;
    }
    
    // Fonction pour supprimer un enregistrement d'activité
    window.deleteActivityLog = function(logId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette activité ?')) {
            fetch(`../api/delete_activity.php?id=${logId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recharger les données
                    loadActivityData();
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
    
    // Gestion du formulaire d'ajout d'activité
    const activityLogForm = document.getElementById('activityLogForm');
    if (activityLogForm) {
        activityLogForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../api/activity.php', {
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
                    activityLogForm.reset();
                    
                    // Définir la date à aujourd'hui
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('log_date').value = today;
                    
                    // Recharger les données
                    loadActivityData();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        });
    }
    
    // Charger les activités recommandées
    loadRecommendedActivities();
    
    function loadRecommendedActivities() {
        const recommendedActivitiesDiv = document.getElementById('recommended-activities');
        
        // Vérifier si l'API est configurée
        fetch('../api/chatgpt.php')
            .then(response => response.json())
            .then(data => {
                if (!data.api_configured) {
                    recommendedActivitiesDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <p>L'API ChatGPT n'est pas configurée. Veuillez configurer votre clé API dans votre profil pour accéder aux recommandations personnalisées.</p>
                        </div>
                        <div class="text-center">
                            <a href="profile.php" class="btn btn-primary">Configurer l'API</a>
                        </div>
                    `;
                } else {
                    // Charger les activités disponibles
                    fetch('../api/activity.php')
                        .then(response => response.json())
                        .then(activityData => {
                            // Afficher quelques activités suggérées
                            if (activityData.activities && activityData.activities.length > 0) {
                                let html = '';
                                // Prendre 4 activités aléatoires
                                const shuffled = [...activityData.activities].sort(() => 0.5 - Math.random());
                                const selected = shuffled.slice(0, 4);
                                
                                selected.forEach(activity => {
                                    html += `
                                        <div class="card">
                                            <h4>${activity.name}</h4>
                                            <p>${activity.description || 'Activité recommandée pour votre profil'}</p>
                                            <p><strong>${activity.calories_per_hour}</strong> calories/heure</p>
                                            <button class="btn btn-primary btn-sm" onclick="selectActivity(${activity.id}, '${activity.name}')">
                                                Sélectionner
                                            </button>
                                        </div>
                                    `;
                                });
                                
                                recommendedActivitiesDiv.innerHTML = html;
                            } else {
                                recommendedActivitiesDiv.innerHTML = '<p class="text-center">Aucune activité disponible</p>';
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            recommendedActivitiesDiv.innerHTML = '<p class="text-danger">Erreur lors du chargement des activités</p>';
                        });
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification de l\'API:', error);
                recommendedActivitiesDiv.innerHTML = '<p class="text-danger">Erreur lors de la vérification de l\'API</p>';
            });
    }
    
    // Fonction pour sélectionner une activité recommandée
    window.selectActivity = function(activityId, activityName) {
        const activitySelect = document.getElementById('activity_id');
        if (activitySelect) {
            activitySelect.value = activityId;
            
            // Faire défiler jusqu'au formulaire
            activityLogForm.scrollIntoView({ behavior: 'smooth' });
        }
    };
});
