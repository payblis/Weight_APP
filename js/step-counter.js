// Fonctionnalité de compteur de pas quotidien pour l'application FitTrack
// Style MyFitnessPal

class StepCounter {
    constructor() {
        this.dailySteps = 0;
        this.stepGoal = 10000; // Objectif par défaut
        this.stepHistory = [];
        this.weeklySteps = [0, 0, 0, 0, 0, 0, 0]; // Dim, Lun, Mar, Mer, Jeu, Ven, Sam
    }

    // Initialiser le compteur
    init(containerId = 'step-counter-container') {
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
        
        // Mettre à jour l'affichage
        this.updateDisplay();
        
        return true;
    }

    // Créer les éléments de l'interface
    createElements() {
        // Créer le conteneur principal
        const mainContainer = document.createElement('div');
        mainContainer.className = 'step-counter-main';
        
        // Créer le cercle de progression
        const progressCircle = document.createElement('canvas');
        progressCircle.id = 'steps-circle';
        progressCircle.width = 200;
        progressCircle.height = 200;
        progressCircle.className = 'steps-circle';
        
        // Créer l'affichage des pas
        const stepsDisplay = document.createElement('div');
        stepsDisplay.className = 'steps-display';
        
        const stepsCount = document.createElement('div');
        stepsCount.className = 'steps-count';
        stepsCount.id = 'today-steps';
        stepsCount.textContent = this.dailySteps.toLocaleString();
        
        const stepsLabel = document.createElement('div');
        stepsLabel.className = 'steps-label';
        stepsLabel.textContent = 'pas aujourd\'hui';
        
        stepsDisplay.appendChild(stepsCount);
        stepsDisplay.appendChild(stepsLabel);
        
        // Créer la barre de progression
        const progressContainer = document.createElement('div');
        progressContainer.className = 'progress mt-3';
        
        const progressBar = document.createElement('div');
        progressBar.className = 'progress-bar';
        progressBar.id = 'steps-progress-bar';
        progressBar.style.width = `${Math.min(100, (this.dailySteps / this.stepGoal) * 100)}%`;
        progressBar.style.backgroundColor = '#FFCC00';
        
        progressContainer.appendChild(progressBar);
        
        // Créer l'affichage de l'objectif
        const goalDisplay = document.createElement('div');
        goalDisplay.className = 'd-flex justify-content-between mt-1';
        
        const currentSteps = document.createElement('small');
        currentSteps.className = 'text-gray';
        currentSteps.textContent = `${this.dailySteps.toLocaleString()} pas`;
        
        const goalSteps = document.createElement('small');
        goalSteps.className = 'text-gray';
        goalSteps.textContent = `Objectif: ${this.stepGoal.toLocaleString()} pas`;
        
        goalDisplay.appendChild(currentSteps);
        goalDisplay.appendChild(goalSteps);
        
        // Créer le conteneur des contrôles
        const controlsContainer = document.createElement('div');
        controlsContainer.className = 'd-flex justify-content-between mt-3';
        
        // Bouton pour ajouter des pas manuellement
        const addStepsButton = document.createElement('button');
        addStepsButton.className = 'btn btn-primary';
        addStepsButton.id = 'add-steps-btn';
        addStepsButton.innerHTML = '<i class="fas fa-plus mr-1"></i> Ajouter des pas';
        
        // Bouton pour modifier l'objectif
        const editGoalButton = document.createElement('button');
        editGoalButton.className = 'btn btn-outline';
        editGoalButton.id = 'edit-step-goal-btn';
        editGoalButton.innerHTML = '<i class="fas fa-edit mr-1"></i> Modifier l\'objectif';
        
        controlsContainer.appendChild(addStepsButton);
        controlsContainer.appendChild(editGoalButton);
        
        // Créer le conteneur du graphique
        const chartContainer = document.createElement('div');
        chartContainer.className = 'mt-4';
        
        const chartTitle = document.createElement('h4');
        chartTitle.textContent = 'Historique des pas';
        
        const chartCanvas = document.createElement('canvas');
        chartCanvas.id = 'steps-chart';
        chartCanvas.height = 250;
        
        chartContainer.appendChild(chartTitle);
        chartContainer.appendChild(chartCanvas);
        
        // Ajouter tous les éléments au conteneur principal
        mainContainer.appendChild(progressCircle);
        mainContainer.appendChild(stepsDisplay);
        mainContainer.appendChild(progressContainer);
        mainContainer.appendChild(goalDisplay);
        mainContainer.appendChild(controlsContainer);
        mainContainer.appendChild(chartContainer);
        
        // Ajouter le conteneur principal au conteneur de la page
        this.container.appendChild(mainContainer);
        
        // Créer le modal pour ajouter des pas
        this.createAddStepsModal();
        
        // Créer le modal pour modifier l'objectif
        this.createEditGoalModal();
    }

    // Créer le modal pour ajouter des pas
    createAddStepsModal() {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'add-steps-modal';
        modal.style.display = 'none';
        
        modal.innerHTML = `
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Ajouter des pas</h3>
                    <button class="modal-close" id="close-add-steps-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="steps-input">Nombre de pas</label>
                        <input type="number" id="steps-input" class="form-control" min="1" max="50000" value="1000">
                    </div>
                    <div class="form-group">
                        <label>Activité</label>
                        <select id="activity-type" class="form-control">
                            <option value="walking">Marche</option>
                            <option value="running">Course</option>
                            <option value="hiking">Randonnée</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="activity-duration">Durée (minutes)</label>
                        <input type="number" id="activity-duration" class="form-control" min="1" max="1440" value="30">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" id="cancel-add-steps">Annuler</button>
                    <button class="btn btn-primary" id="save-steps">Ajouter</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }

    // Créer le modal pour modifier l'objectif
    createEditGoalModal() {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'edit-goal-modal';
        modal.style.display = 'none';
        
        modal.innerHTML = `
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Modifier l'objectif de pas</h3>
                    <button class="modal-close" id="close-edit-goal-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="goal-input">Objectif quotidien</label>
                        <input type="number" id="goal-input" class="form-control" min="1000" max="100000" value="${this.stepGoal}">
                    </div>
                    <div class="form-group">
                        <label>Objectifs prédéfinis</label>
                        <div class="d-flex flex-wrap">
                            <button class="btn btn-sm btn-outline mr-2 mb-2 preset-goal" data-goal="5000">5 000</button>
                            <button class="btn btn-sm btn-outline mr-2 mb-2 preset-goal" data-goal="7500">7 500</button>
                            <button class="btn btn-sm btn-outline mr-2 mb-2 preset-goal" data-goal="10000">10 000</button>
                            <button class="btn btn-sm btn-outline mr-2 mb-2 preset-goal" data-goal="12500">12 500</button>
                            <button class="btn btn-sm btn-outline mr-2 mb-2 preset-goal" data-goal="15000">15 000</button>
                            <button class="btn btn-sm btn-outline preset-goal" data-goal="20000">20 000</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" id="cancel-edit-goal">Annuler</button>
                    <button class="btn btn-primary" id="save-goal">Enregistrer</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }

    // Ajouter les écouteurs d'événements
    addEventListeners() {
        // Bouton pour ajouter des pas
        const addStepsBtn = document.getElementById('add-steps-btn');
        if (addStepsBtn) {
            addStepsBtn.addEventListener('click', () => this.showAddStepsModal());
        }
        
        // Bouton pour modifier l'objectif
        const editGoalBtn = document.getElementById('edit-step-goal-btn');
        if (editGoalBtn) {
            editGoalBtn.addEventListener('click', () => this.showEditGoalModal());
        }
        
        // Modal d'ajout de pas
        const closeAddStepsModal = document.getElementById('close-add-steps-modal');
        if (closeAddStepsModal) {
            closeAddStepsModal.addEventListener('click', () => this.hideAddStepsModal());
        }
        
        const cancelAddSteps = document.getElementById('cancel-add-steps');
        if (cancelAddSteps) {
            cancelAddSteps.addEventListener('click', () => this.hideAddStepsModal());
        }
        
        const saveSteps = document.getElementById('save-steps');
        if (saveSteps) {
            saveSteps.addEventListener('click', () => this.saveAddedSteps());
        }
        
        // Modal de modification d'objectif
        const closeEditGoalModal = document.getElementById('close-edit-goal-modal');
        if (closeEditGoalModal) {
            closeEditGoalModal.addEventListener('click', () => this.hideEditGoalModal());
        }
        
        const cancelEditGoal = document.getElementById('cancel-edit-goal');
        if (cancelEditGoal) {
            cancelEditGoal.addEventListener('click', () => this.hideEditGoalModal());
        }
        
        const saveGoal = document.getElementById('save-goal');
        if (saveGoal) {
            saveGoal.addEventListener('click', () => this.saveNewGoal());
        }
        
        // Boutons d'objectifs prédéfinis
        const presetGoalBtns = document.querySelectorAll('.preset-goal');
        presetGoalBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const goal = parseInt(e.target.dataset.goal);
                document.getElementById('goal-input').value = goal;
            });
        });
    }

    // Afficher le modal d'ajout de pas
    showAddStepsModal() {
        const modal = document.getElementById('add-steps-modal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    // Masquer le modal d'ajout de pas
    hideAddStepsModal() {
        const modal = document.getElementById('add-steps-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Enregistrer les pas ajoutés
    saveAddedSteps() {
        const stepsInput = document.getElementById('steps-input');
        const activityType = document.getElementById('activity-type');
        const activityDuration = document.getElementById('activity-duration');
        
        if (!stepsInput || !activityType || !activityDuration) return;
        
        const steps = parseInt(stepsInput.value);
        const activity = activityType.value;
        const duration = parseInt(activityDuration.value);
        
        if (isNaN(steps) || steps <= 0 || isNaN(duration) || duration <= 0) {
            alert('Veuillez entrer des valeurs valides');
            return;
        }
        
        // Ajouter les pas
        this.addSteps(steps, activity, duration);
        
        // Masquer le modal
        this.hideAddStepsModal();
    }

    // Afficher le modal de modification d'objectif
    showEditGoalModal() {
        const modal = document.getElementById('edit-goal-modal');
        if (modal) {
            document.getElementById('goal-input').value = this.stepGoal;
            modal.style.display = 'block';
        }
    }

    // Masquer le modal de modification d'objectif
    hideEditGoalModal() {
        const modal = document.getElementById('edit-goal-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Enregistrer le nouvel objectif
    saveNewGoal() {
        const goalInput = document.getElementById('goal-input');
        if (!goalInput) return;
        
        const goal = parseInt(goalInput.value);
        if (isNaN(goal) || goal < 1000) {
            alert('Veuillez entrer un objectif valide (minimum 1000 pas)');
            return;
        }
        
        // Mettre à jour l'objectif
        this.stepGoal = goal;
        
        // Sauvegarder les données
        this.saveData();
        
        // Mettre à jour l'affichage
        this.updateDisplay();
        
        // Masquer le modal
        this.hideEditGoalModal();
    }

    // Ajouter des pas
    addSteps(steps, activity, duration) {
        // Ajouter les pas au total quotidien
        this.dailySteps += steps;
        
        // Ajouter à l'historique
        const today = new Date();
        const entry = {
            date: today.toISOString(),
            steps: steps,
            activity: activity,
            duration: duration
        };
        
        this.stepHistory.push(entry);
        
        // Mettre à jour les pas hebdomadaires
        const dayOfWeek = today.getDay(); // 0 = Dimanche, 1 = Lundi, etc.
        this.weeklySteps[dayOfWeek] += steps;
        
        // Sauvegarder les données
        this.saveData();
        
        // Mettre à jour l'affichage
        this.updateDisplay();
    }

    // Mettre à jour l'affichage
    updateDisplay() {
        // Mettre à jour le compteur de pas
        const stepsCount = document.getElementById('today-steps');
        if (stepsCount) {
            stepsCount.textContent = this.dailySteps.toLocaleString();
        }
        
        // Mettre à jour la barre de progression
        const progressBar = document.getElementById('steps-progress-bar');
        if (progressBar) {
            const percentage = Math.min(100, (this.dailySteps / this.stepGoal) * 100);
            progressBar.style.width = `${percentage}%`;
        }
        
        // Mettre à jour l'affichage de l'objectif
        const goalSteps = document.querySelectorAll('.text-gray')[1];
        if (goalSteps) {
            goalSteps.textContent = `Objectif: ${this.stepGoal.toLocaleString()} pas`;
        }
        
        // Mettre à jour le cercle de progression
        this.updateProgressCircle();
        
        // Mettre à jour le graphique
        this.updateStepsChart();
    }

    // Mettre à jour le cercle de progression
    updateProgressCircle() {
        const canvas = document.getElementById('steps-circle');
        if (!canvas) return;
        
        const percentage = Math.min(100, (this.dailySteps / this.stepGoal) * 100);
        
        // Utiliser la fonction de charts.js si disponible
        if (typeof createProgressCircle === 'function') {
            createProgressCircle('steps-circle', percentage, '#FFCC00');
        } else {
            // Implémentation de secours
            const ctx = canvas.getContext('2d');
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = canvas.width / 2 - 10;
            
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
            ctx.strokeStyle = '#FFCC00';
            ctx.lineWidth = 15;
            ctx.stroke();
            
            // Dessiner le cercle central
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius - 20, 0, 2 * Math.PI);
            ctx.fillStyle = '#FFFFFF';
            ctx.fill();
        }
    }

    // Mettre à jour le graphique des pas
    updateStepsChart() {
        const canvas = document.getElementById('steps-chart');
        if (!canvas) return;
        
        // Utiliser la fonction de charts.js si disponible
        if (typeof Chart === 'function') {
            // Détruire le graphique existant s'il existe
            if (this.stepsChart) {
                this.stepsChart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            
            // Préparer les données
            const days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            
            // Réorganiser les données pour commencer par aujourd'hui
            const today = new Date().getDay();
            const reorderedDays = [...days.slice(today + 1), ...days.slice(0, today + 1)];
            const reorderedSteps = [...this.weeklySteps.slice(today + 1), ...this.weeklySteps.slice(0, today + 1)];
            
            // Créer le graphique
            this.stepsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: reorderedDays,
                    datasets: [{
                        label: 'Pas',
                        data: reorderedSteps,
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
    }

    // Sauvegarder les données
    saveData() {
        const data = {
            dailySteps: this.dailySteps,
            stepGoal: this.stepGoal,
            stepHistory: this.stepHistory,
            weeklySteps: this.weeklySteps,
            lastUpdated: new Date().toISOString()
        };
        
        localStorage.setItem('stepCounterData', JSON.stringify(data));
    }

    // Charger les données
    loadData() {
        const data = localStorage.getItem('stepCounterData');
        if (data) {
            const parsedData = JSON.parse(data);
            
            // Vérifier si les données sont d'aujourd'hui
            const lastUpdated = new Date(parsedData.lastUpdated);
            const today = new Date();
            
            if (lastUpdated.toDateString() === today.toDateString()) {
                // Les données sont d'aujourd'hui, les charger
                this.dailySteps = parsedData.dailySteps;
                this.stepGoal = parsedData.stepGoal;
                this.stepHistory = parsedData.stepHistory;
                this.weeklySteps = parsedData.weeklySteps;
            } else {
                // Les données sont d'un autre jour, réinitialiser les pas quotidiens
                this.dailySteps = 0;
                this.stepGoal = parsedData.stepGoal;
                this.stepHistory = parsedData.stepHistory;
                
                // Mettre à jour les pas hebdomadaires
                const dayDiff = Math.floor((today - lastUpdated) / (24 * 60 * 60 * 1000));
                if (dayDiff >= 7) {
                    // Plus d'une semaine s'est écoulée, réinitialiser
                    this.weeklySteps = [0, 0, 0, 0, 0, 0, 0];
                } else {
                    // Décaler les données
                    this.weeklySteps = [...parsedData.weeklySteps.slice(dayDiff), ...Array(dayDiff).fill(0)];
                }
                
                // Sauvegarder les nouvelles données
                this.saveData();
            }
        }
    }
}

// Initialiser le compteur de pas lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur la page appropriée
    const stepCounterContainer = document.getElementById('step-counter-container');
    if (stepCounterContainer) {
        const counter = new StepCounter();
        counter.init();
    }
});
