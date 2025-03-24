// charts.js - Visualisations et graphiques pour l'application FitTrack
// Style MyFitnessPal

// Fonction pour créer un cercle de progression
function createProgressCircle(elementId, percentage, color = '#0066EE', size = 150) {
    const canvas = document.getElementById(elementId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    const radius = size / 2 - 10;
    
    // Effacer le canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Dessiner le cercle de fond
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
    ctx.strokeStyle = '#F0F0F0';
    ctx.lineWidth = 15;
    ctx.stroke();
    
    // Dessiner le cercle de progression
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, -Math.PI / 2, (-Math.PI / 2) + (percentage / 100 * 2 * Math.PI));
    ctx.strokeStyle = color;
    ctx.lineWidth = 15;
    ctx.stroke();
    
    // Dessiner le cercle central
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius - 20, 0, 2 * Math.PI);
    ctx.fillStyle = '#FFFFFF';
    ctx.fill();
}

// Fonction pour initialiser les cercles de progression sur le tableau de bord
function initDashboardCircles() {
    // Cercle des calories
    const caloriesPercentage = calculateCaloriesPercentage();
    createProgressCircle('calories-circle', caloriesPercentage);
    
    // Cercle des pas
    const stepsPercentage = calculateStepsPercentage();
    createProgressCircle('steps-circle', stepsPercentage, '#FFCC00');
    
    // Cercle du poids
    const weightPercentage = calculateWeightPercentage();
    createProgressCircle('weight-circle', weightPercentage, '#28A745');
}

// Fonction pour calculer le pourcentage de calories consommées
function calculateCaloriesPercentage() {
    // Simuler des données pour la démo
    const calorieGoal = 2000;
    const caloriesConsumed = 1200;
    const caloriesBurned = 300;
    
    const remaining = calorieGoal - caloriesConsumed + caloriesBurned;
    const percentage = 100 - (remaining / calorieGoal * 100);
    
    // Mettre à jour les valeurs affichées
    document.getElementById('current-calories').textContent = remaining;
    document.getElementById('base-goal').textContent = calorieGoal;
    document.getElementById('food-calories').textContent = caloriesConsumed;
    document.getElementById('exercise-calories').textContent = caloriesBurned;
    
    return Math.min(percentage, 100);
}

// Fonction pour calculer le pourcentage de pas effectués
function calculateStepsPercentage() {
    // Simuler des données pour la démo
    const stepGoal = 10000;
    const currentSteps = 6500;
    
    const percentage = (currentSteps / stepGoal * 100);
    
    // Mettre à jour les valeurs affichées
    document.getElementById('current-steps').textContent = currentSteps;
    document.getElementById('step-goal').textContent = stepGoal.toLocaleString();
    
    // Mettre à jour la barre de progression
    const progressBar = document.getElementById('steps-progress-bar');
    if (progressBar) {
        progressBar.style.width = `${percentage}%`;
    }
    
    return Math.min(percentage, 100);
}

// Fonction pour calculer le pourcentage de progression vers l'objectif de poids
function calculateWeightPercentage() {
    // Simuler des données pour la démo
    const initialWeight = 85;
    const currentWeight = 80;
    const targetWeight = 70;
    
    const totalToLose = initialWeight - targetWeight;
    const alreadyLost = initialWeight - currentWeight;
    const percentage = (alreadyLost / totalToLose * 100);
    
    // Mettre à jour les valeurs affichées
    document.getElementById('initial-weight').textContent = initialWeight;
    document.getElementById('current-weight').textContent = currentWeight;
    document.getElementById('target-weight').textContent = targetWeight;
    document.getElementById('weight-lost').textContent = alreadyLost;
    
    // Mettre à jour la barre de progression
    const progressBar = document.getElementById('weight-progress-bar');
    if (progressBar) {
        progressBar.style.width = `${percentage}%`;
    }
    
    return Math.min(percentage, 100);
}

// Fonction pour créer un graphique de poids
function createWeightChart(elementId, data) {
    const canvas = document.getElementById(elementId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Simuler des données pour la démo
    const dates = ['1 Mar', '5 Mar', '10 Mar', '15 Mar', '20 Mar', '25 Mar'];
    const weights = [85, 84.2, 83.5, 82.1, 81.3, 80];
    
    // Créer le graphique
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Poids (kg)',
                data: weights,
                borderColor: '#0066EE',
                backgroundColor: 'rgba(0, 102, 238, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#FFFFFF',
                pointBorderColor: '#0066EE',
                pointBorderWidth: 2,
                pointRadius: 5,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    titleColor: '#333333',
                    bodyColor: '#333333',
                    borderColor: '#DDDDDD',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `${context.parsed.y} kg`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: false,
                    grid: {
                        color: '#F0F0F0'
                    }
                }
            }
        }
    });
}

// Fonction pour créer un graphique de pas
function createStepsChart(elementId, data) {
    const canvas = document.getElementById(elementId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Simuler des données pour la démo
    const dates = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    const steps = [8500, 7200, 9800, 6500, 10200, 8900, 5600];
    
    // Créer le graphique
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dates,
            datasets: [{
                label: 'Pas',
                data: steps,
                backgroundColor: '#FFCC00',
                borderRadius: 5,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    titleColor: '#333333',
                    bodyColor: '#333333',
                    borderColor: '#DDDDDD',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `${context.parsed.y.toLocaleString()} pas`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#F0F0F0'
                    }
                }
            }
        }
    });
}

// Fonction pour créer un graphique de macronutriments
function createMacroChart(elementId, data) {
    const canvas = document.getElementById(elementId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Simuler des données pour la démo
    const macros = ['Protéines', 'Glucides', 'Lipides'];
    const values = [25, 55, 20]; // pourcentages
    
    // Créer le graphique
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: macros,
            datasets: [{
                data: values,
                backgroundColor: ['#28A745', '#0066EE', '#FFCC00'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    titleColor: '#333333',
                    bodyColor: '#333333',
                    borderColor: '#DDDDDD',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `${context.parsed}%`;
                        }
                    }
                }
            }
        }
    });
}

// Fonction pour créer un graphique de calories
function createCaloriesChart(elementId, data) {
    const canvas = document.getElementById(elementId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Simuler des données pour la démo
    const dates = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    const consumed = [1800, 2100, 1950, 2000, 1850, 2200, 1900];
    const burned = [300, 450, 200, 350, 500, 250, 300];
    
    // Créer le graphique
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Consommées',
                    data: consumed,
                    backgroundColor: '#0066EE',
                    borderRadius: 5,
                    borderWidth: 0
                },
                {
                    label: 'Brûlées',
                    data: burned,
                    backgroundColor: '#28A745',
                    borderRadius: 5,
                    borderWidth: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    titleColor: '#333333',
                    bodyColor: '#333333',
                    borderColor: '#DDDDDD',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y} cal`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#F0F0F0'
                    }
                }
            }
        }
    });
}

// Fonction pour initialiser les cercles de progression sur la page des repas
function initMealsCircles() {
    // Cercle des calories
    const caloriesPercentage = calculateMealCaloriesPercentage();
    createProgressCircle('calories-circle', caloriesPercentage);
    
    // Mettre à jour les barres de progression des macronutriments
    updateMacroProgress();
}

// Fonction pour calculer le pourcentage de calories consommées pour les repas
function calculateMealCaloriesPercentage() {
    // Simuler des données pour la démo
    const calorieGoal = 2000;
    const caloriesConsumed = 1200;
    const caloriesBurned = 300;
    
    const remaining = calorieGoal - caloriesConsumed + caloriesBurned;
    const percentage = 100 - (remaining / calorieGoal * 100);
    
    // Mettre à jour les valeurs affichées
    document.getElementById('remaining-calories').textContent = remaining;
    document.getElementById('calorie-goal').textContent = calorieGoal;
    document.getElementById('food-calories').textContent = caloriesConsumed;
    document.getElementById('exercise-calories').textContent = caloriesBurned;
    document.getElementById('remaining-calories-display').textContent = remaining;
    
    return Math.min(percentage, 100);
}

// Fonction pour mettre à jour les barres de progression des macronutriments
function updateMacroProgress() {
    // Protéines
    const proteinGoal = 150;
    const proteinCurrent = 85;
    const proteinPercentage = (proteinCurrent / proteinGoal * 100);
    
    const proteinProgress = document.getElementById('protein-progress');
    if (proteinProgress) {
        proteinProgress.style.width = `${Math.min(proteinPercentage, 100)}%`;
    }
    
    document.getElementById('protein-current').textContent = `${proteinCurrent}g`;
    document.getElementById('protein-goal').textContent = `/ ${proteinGoal}g`;
    
    // Glucides
    const carbsGoal = 250;
    const carbsCurrent = 180;
    const carbsPercentage = (carbsCurrent / carbsGoal * 100);
    
    const carbsProgress = document.getElementById('carbs-progress');
    if (carbsProgress) {
        carbsProgress.style.width = `${Math.min(carbsPercentage, 100)}%`;
    }
    
    document.getElementById('carbs-current').textContent = `${carbsCurrent}g`;
    document.getElementById('carbs-goal').textContent = `/ ${carbsGoal}g`;
    
    // Lipides
    const fatGoal = 70;
    const fatCurrent = 45;
    const fatPercentage = (fatCurrent / fatGoal * 100);
    
    const fatProgress = document.getElementById('fat-progress');
    if (fatProgress) {
        fatProgress.style.width = `${Math.min(fatPercentage, 100)}%`;
    }
    
    document.getElementById('fat-current').textContent = `${fatCurrent}g`;
    document.getElementById('fat-goal').textContent = `/ ${fatGoal}g`;
}

// Fonction pour initialiser les cercles de progression sur la page de suivi du poids
function initWeightCircles() {
    // Cercle de progression du poids
    const weightPercentage = calculateWeightGoalPercentage();
    createProgressCircle('weight-progress-circle', weightPercentage, '#0066EE');
    
    // Créer le graphique de poids
    createWeightChart('weight-chart');
}

// Fonction pour calculer le pourcentage de progression vers l'objectif de poids
function calculateWeightGoalPercentage() {
    // Simuler des données pour la démo
    const initialWeight = 85;
    const currentWeight = 80;
    const targetWeight = 70;
    
    const totalToLose = initialWeight - targetWeight;
    const alreadyLost = initialWeight - currentWeight;
    const percentage = (alreadyLost / totalToLose * 100);
    
    // Mettre à jour les valeurs affichées
    document.getElementById('initial-weight').textContent = initialWeight;
    document.getElementById('current-weight').textContent = currentWeight;
    document.getElementById('target-weight').textContent = targetWeight;
    document.getElementById('weight-lost').textContent = alreadyLost;
    document.getElementById('weight-progress-value').textContent = `${Math.round(percentage)}%`;
    
    // Mettre à jour la barre de progression
    const progressBar = document.getElementById('weight-progress-bar');
    if (progressBar) {
        progressBar.style.width = `${percentage}%`;
    }
    
    return Math.min(percentage, 100);
}

// Fonction pour initialiser les cercles de progression sur la page des activités
function initActivityCircles() {
    // Cercle de progression de l'activité
    const activityPercentage = calculateActivityPercentage();
    createProgressCircle('activity-progress-circle', activityPercentage, '#0066EE');
    
    // Cercle des pas
    const stepsPercentage = calculateTodayStepsPercentage();
    createProgressCircle('steps-circle', stepsPercentage, '#FFCC00');
    
    // Créer le graphique des pas
    createStepsChart('steps-chart');
}

// Fonction pour calculer le pourcentage de progression vers l'objectif d'activité
function calculateActivityPercentage() {
    // Simuler des données pour la démo
    const activityGoal = 150; // minutes par semaine
    const currentActivity = 95; // minutes cette semaine
    const percentage = (currentActivity / activityGoal * 100);
    
    // Mettre à jour les valeurs affichées
    document.getElementById('calories-burned').textContent = 850;
    document.getElementById('activity-minutes').textContent = currentActivity;
    document.getElementById('steps-count').textContent = '45,600';
    document.getElementById('activity-goal-progress').textContent = `${currentActivity}/${activityGoal} min`;
    document.getElementById('activity-progress-value').textContent = `${Math.round(percentage)}%`;
    
    // Mettre à jour la barre de progression
    const progressBar = document.getElementById('activity-progress-bar');
    if (progressBar) {
        progressBar.style.width = `${percentage}%`;
    }
    
    return Math.min(percentage, 100);
}

// Fonction pour calculer le pourcentage de pas effectués aujourd'hui
function calculateTodayStepsPercentage() {
    // Simuler des données pour la démo
    const stepGoal = 10000;
    const currentSteps = 6500;
    
    const percentage = (currentSteps / stepGoal * 100);
    
    // Mettre à jour les valeurs affichées
    document.getElementById('today-steps').textContent = currentSteps;
    document.getElementById('steps-goal').textContent = stepGoal.toLocaleString();
    
    // Mettre à jour la barre de progression
    const progressBar = document.getElementById('steps-progress-bar');
    if (progressBar) {
        progressBar.style.width = `${percentage}%`;
    }
    
    return Math.min(percentage, 100);
}

// Initialiser les graphiques lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier quelle page est actuellement chargée
    const currentPath = window.location.pathname;
    
    if (currentPath.includes('dashboard')) {
        initDashboardCircles();
    } else if (currentPath.includes('meals')) {
        initMealsCircles();
        createMacroChart('macro-chart');
    } else if (currentPath.includes('weight-log')) {
        initWeightCircles();
    } else if (currentPath.includes('activities')) {
        initActivityCircles();
    }
    
    // Simuler le chargement des données
    setTimeout(function() {
        const loadingElements = document.querySelectorAll('.loading-spinner');
        loadingElements.forEach(function(element) {
            element.parentNode.innerHTML = '<p>Données chargées avec succès!</p>';
        });
    }, 1500);
});

// Fonction pour simuler le scan de code-barres
function simulateBarcodeScan() {
    const scanResult = document.getElementById('scan-result');
    if (!scanResult) return;
    
    // Afficher l'animation de scan
    scanResult.style.display = 'none';
    const scannerIcon = document.querySelector('.barcode-scanner-icon');
    if (scannerIcon) {
        scannerIcon.innerHTML = '<div class="loading-spinner"></div>';
    }
    
    // Simuler le temps de scan
    setTimeout(function() {
        // Restaurer l'icône
        if (scannerIcon) {
            scannerIcon.innerHTML = '<i class="fas fa-barcode"></i>';
        }
        
        // Afficher le résultat
        scanResult.style.display = 'block';
        
        // Simuler un produit scanné
        document.getElementById('scanned-product-name').textContent = 'Yaourt nature';
        document.getElementById('scanned-product-calories').textContent = '120 cal';
        document.getElementById('scanned-product-protein').textContent = '5g';
        document.getElementById('scanned-product-carbs').textContent = '15g';
        document.getElementById('scanned-product-fat').textContent = '3g';
    }, 2000);
}

// Ajouter des écouteurs d'événements pour le scan de code-barres
document.addEventListener('DOMContentLoaded', function() {
    const scanButton = document.getElementById('scan-barcode-btn');
    if (scanButton) {
        scanButton.addEventListener('click', simulateBarcodeScan);
    }
    
    const cancelScanButton = document.getElementById('cancel-scan');
    if (cancelScanButton) {
        cancelScanButton.addEventListener('click', function() {
            const scanResult = document.getElementById('scan-result');
            if (scanResult) {
                scanResult.style.display = 'none';
            }
        });
    }
});
