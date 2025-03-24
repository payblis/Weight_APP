// Fonctionnalité de suivi du jeûne intermittent pour l'application FitTrack
// Style MyFitnessPal

class FastingTracker {
    constructor() {
        this.fastingActive = false;
        this.fastingStartTime = null;
        this.fastingEndTime = null;
        this.fastingGoal = 16; // Heures par défaut (16:8)
        this.timerInterval = null;
        this.fastingHistory = [];
    }

    // Initialiser le tracker
    init(containerId = 'fasting-tracker-container') {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Container not found');
            return false;
        }

        // Charger l'historique depuis le stockage local
        this.loadHistory();
        
        // Créer les éléments de l'interface
        this.createElements();
        
        // Ajouter les écouteurs d'événements
        this.addEventListeners();
        
        // Vérifier s'il y a un jeûne en cours
        this.checkOngoingFasting();
        
        return true;
    }

    // Créer les éléments de l'interface
    createElements() {
        // Créer le conteneur du timer
        const timerContainer = document.createElement('div');
        timerContainer.className = 'fasting-timer';
        
        // Créer l'affichage du temps
        const timeDisplay = document.createElement('div');
        timeDisplay.className = 'fasting-time';
        timeDisplay.id = 'fasting-time';
        timeDisplay.textContent = '00:00:00';
        
        // Créer le label du timer
        const timeLabel = document.createElement('div');
        timeLabel.className = 'fasting-label';
        timeLabel.id = 'fasting-label';
        timeLabel.textContent = 'Temps de jeûne';
        
        // Ajouter les éléments au conteneur du timer
        timerContainer.appendChild(timeDisplay);
        timerContainer.appendChild(timeLabel);
        
        // Créer la barre de progression
        const progressContainer = document.createElement('div');
        progressContainer.className = 'fasting-progress';
        
        const progressBar = document.createElement('div');
        progressBar.className = 'fasting-progress-bar';
        progressBar.id = 'fasting-progress-bar';
        progressBar.style.width = '0%';
        
        progressContainer.appendChild(progressBar);
        
        // Créer les boutons de contrôle
        const controlsContainer = document.createElement('div');
        controlsContainer.className = 'd-flex justify-content-between mt-3';
        
        const startButton = document.createElement('button');
        startButton.className = 'btn btn-primary';
        startButton.id = 'start-fasting-btn';
        startButton.innerHTML = '<i class="fas fa-play mr-1"></i> Démarrer le jeûne';
        
        const endButton = document.createElement('button');
        endButton.className = 'btn btn-danger';
        endButton.id = 'end-fasting-btn';
        endButton.innerHTML = '<i class="fas fa-stop mr-1"></i> Terminer le jeûne';
        endButton.style.display = 'none';
        
        controlsContainer.appendChild(startButton);
        controlsContainer.appendChild(endButton);
        
        // Créer le sélecteur d'objectif
        const goalContainer = document.createElement('div');
        goalContainer.className = 'form-group mt-3';
        
        const goalLabel = document.createElement('label');
        goalLabel.htmlFor = 'fasting-goal';
        goalLabel.textContent = 'Objectif de jeûne';
        
        const goalSelect = document.createElement('select');
        goalSelect.className = 'form-control';
        goalSelect.id = 'fasting-goal';
        
        const goals = [
            { value: 12, text: '12:12 (12 heures de jeûne, 12 heures d\'alimentation)' },
            { value: 14, text: '14:10 (14 heures de jeûne, 10 heures d\'alimentation)' },
            { value: 16, text: '16:8 (16 heures de jeûne, 8 heures d\'alimentation)' },
            { value: 18, text: '18:6 (18 heures de jeûne, 6 heures d\'alimentation)' },
            { value: 20, text: '20:4 (20 heures de jeûne, 4 heures d\'alimentation)' },
            { value: 24, text: '24:0 (Jeûne de 24 heures)' },
            { value: 36, text: '36:12 (Jeûne de 36 heures)' },
            { value: 48, text: '48:24 (Jeûne de 48 heures)' }
        ];
        
        goals.forEach(goal => {
            const option = document.createElement('option');
            option.value = goal.value;
            option.textContent = goal.text;
            if (goal.value === this.fastingGoal) {
                option.selected = true;
            }
            goalSelect.appendChild(option);
        });
        
        goalContainer.appendChild(goalLabel);
        goalContainer.appendChild(goalSelect);
        
        // Créer la section d'historique
        const historyContainer = document.createElement('div');
        historyContainer.className = 'mt-4';
        
        const historyTitle = document.createElement('h4');
        historyTitle.textContent = 'Historique de jeûne';
        
        const historyList = document.createElement('div');
        historyList.id = 'fasting-history';
        historyList.className = 'mt-3';
        
        historyContainer.appendChild(historyTitle);
        historyContainer.appendChild(historyList);
        
        // Ajouter tous les éléments au conteneur principal
        this.container.appendChild(timerContainer);
        this.container.appendChild(progressContainer);
        this.container.appendChild(controlsContainer);
        this.container.appendChild(goalContainer);
        this.container.appendChild(historyContainer);
        
        // Mettre à jour l'historique
        this.updateHistoryDisplay();
    }

    // Ajouter les écouteurs d'événements
    addEventListeners() {
        const startButton = document.getElementById('start-fasting-btn');
        if (startButton) {
            startButton.addEventListener('click', () => this.startFasting());
        }
        
        const endButton = document.getElementById('end-fasting-btn');
        if (endButton) {
            endButton.addEventListener('click', () => this.endFasting());
        }
        
        const goalSelect = document.getElementById('fasting-goal');
        if (goalSelect) {
            goalSelect.addEventListener('change', (e) => {
                this.fastingGoal = parseInt(e.target.value);
                this.updateTimer();
            });
        }
    }

    // Vérifier s'il y a un jeûne en cours
    checkOngoingFasting() {
        const fastingData = localStorage.getItem('currentFasting');
        if (fastingData) {
            const data = JSON.parse(fastingData);
            this.fastingActive = true;
            this.fastingStartTime = new Date(data.startTime);
            this.fastingGoal = data.goal;
            
            // Mettre à jour l'interface
            this.updateUI();
            
            // Démarrer le timer
            this.startTimer();
        }
    }

    // Démarrer un jeûne
    startFasting() {
        if (this.fastingActive) return;
        
        this.fastingActive = true;
        this.fastingStartTime = new Date();
        
        // Mettre à jour l'interface
        this.updateUI();
        
        // Démarrer le timer
        this.startTimer();
        
        // Sauvegarder l'état
        this.saveCurrentFasting();
    }

    // Terminer un jeûne
    endFasting() {
        if (!this.fastingActive) return;
        
        this.fastingActive = false;
        this.fastingEndTime = new Date();
        
        // Arrêter le timer
        this.stopTimer();
        
        // Calculer la durée
        const duration = this.calculateDuration(this.fastingStartTime, this.fastingEndTime);
        
        // Ajouter à l'historique
        this.addToHistory({
            startTime: this.fastingStartTime,
            endTime: this.fastingEndTime,
            duration: duration,
            goal: this.fastingGoal,
            completed: duration >= this.fastingGoal * 60 * 60 * 1000
        });
        
        // Mettre à jour l'interface
        this.updateUI();
        
        // Effacer l'état actuel
        localStorage.removeItem('currentFasting');
    }

    // Démarrer le timer
    startTimer() {
        // Arrêter le timer existant si nécessaire
        this.stopTimer();
        
        // Mettre à jour immédiatement
        this.updateTimer();
        
        // Démarrer l'intervalle
        this.timerInterval = setInterval(() => this.updateTimer(), 1000);
    }

    // Arrêter le timer
    stopTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }

    // Mettre à jour le timer
    updateTimer() {
        if (!this.fastingActive || !this.fastingStartTime) return;
        
        const now = new Date();
        const elapsed = now - this.fastingStartTime;
        const goalMs = this.fastingGoal * 60 * 60 * 1000;
        
        // Formater le temps écoulé
        const formattedTime = this.formatTime(elapsed);
        
        // Mettre à jour l'affichage
        const timeDisplay = document.getElementById('fasting-time');
        if (timeDisplay) {
            timeDisplay.textContent = formattedTime;
        }
        
        // Mettre à jour la barre de progression
        const progressBar = document.getElementById('fasting-progress-bar');
        if (progressBar) {
            const percentage = Math.min(100, (elapsed / goalMs) * 100);
            progressBar.style.width = `${percentage}%`;
            
            // Changer la couleur en fonction de la progression
            if (percentage >= 100) {
                progressBar.style.backgroundColor = '#28A745'; // Vert
            } else if (percentage >= 75) {
                progressBar.style.backgroundColor = '#FFCC00'; // Jaune
            } else {
                progressBar.style.backgroundColor = '#0066EE'; // Bleu
            }
        }
        
        // Mettre à jour le label
        const timeLabel = document.getElementById('fasting-label');
        if (timeLabel) {
            const remaining = goalMs - elapsed;
            if (remaining > 0) {
                const formattedRemaining = this.formatTime(remaining);
                timeLabel.textContent = `Encore ${formattedRemaining} pour atteindre l'objectif`;
            } else {
                timeLabel.textContent = 'Objectif atteint ! Continuez ou terminez le jeûne.';
            }
        }
    }

    // Mettre à jour l'interface utilisateur
    updateUI() {
        const startButton = document.getElementById('start-fasting-btn');
        const endButton = document.getElementById('end-fasting-btn');
        const goalSelect = document.getElementById('fasting-goal');
        
        if (this.fastingActive) {
            // Masquer le bouton de démarrage
            if (startButton) startButton.style.display = 'none';
            
            // Afficher le bouton de fin
            if (endButton) endButton.style.display = 'block';
            
            // Désactiver le sélecteur d'objectif
            if (goalSelect) goalSelect.disabled = true;
        } else {
            // Afficher le bouton de démarrage
            if (startButton) startButton.style.display = 'block';
            
            // Masquer le bouton de fin
            if (endButton) endButton.style.display = 'none';
            
            // Activer le sélecteur d'objectif
            if (goalSelect) goalSelect.disabled = false;
            
            // Réinitialiser l'affichage du temps
            const timeDisplay = document.getElementById('fasting-time');
            if (timeDisplay) timeDisplay.textContent = '00:00:00';
            
            // Réinitialiser le label
            const timeLabel = document.getElementById('fasting-label');
            if (timeLabel) timeLabel.textContent = 'Temps de jeûne';
            
            // Réinitialiser la barre de progression
            const progressBar = document.getElementById('fasting-progress-bar');
            if (progressBar) progressBar.style.width = '0%';
        }
    }

    // Ajouter une entrée à l'historique
    addToHistory(entry) {
        this.fastingHistory.unshift(entry); // Ajouter au début
        
        // Limiter l'historique à 10 entrées
        if (this.fastingHistory.length > 10) {
            this.fastingHistory = this.fastingHistory.slice(0, 10);
        }
        
        // Sauvegarder l'historique
        this.saveHistory();
        
        // Mettre à jour l'affichage
        this.updateHistoryDisplay();
    }

    // Mettre à jour l'affichage de l'historique
    updateHistoryDisplay() {
        const historyContainer = document.getElementById('fasting-history');
        if (!historyContainer) return;
        
        // Vider le conteneur
        historyContainer.innerHTML = '';
        
        // Afficher un message si l'historique est vide
        if (this.fastingHistory.length === 0) {
            const emptyMessage = document.createElement('p');
            emptyMessage.className = 'text-center text-gray';
            emptyMessage.textContent = 'Aucun historique de jeûne disponible';
            historyContainer.appendChild(emptyMessage);
            return;
        }
        
        // Créer une entrée pour chaque jeûne
        this.fastingHistory.forEach((entry, index) => {
            const historyItem = document.createElement('div');
            historyItem.className = 'card mb-2';
            
            const startDate = new Date(entry.startTime);
            const endDate = new Date(entry.endTime);
            
            const formattedStart = startDate.toLocaleDateString() + ' ' + startDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const formattedEnd = endDate.toLocaleDateString() + ' ' + endDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            const durationHours = Math.floor(entry.duration / (60 * 60 * 1000));
            const durationMinutes = Math.floor((entry.duration % (60 * 60 * 1000)) / (60 * 1000));
            
            historyItem.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Jeûne de ${durationHours}h ${durationMinutes}min</h5>
                        <span class="badge ${entry.completed ? 'badge-success' : 'badge-warning'}">
                            ${entry.completed ? 'Objectif atteint' : 'Interrompu'}
                        </span>
                    </div>
                    <div class="text-gray">
                        <small>Début: ${formattedStart}</small><br>
                        <small>Fin: ${formattedEnd}</small>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar" style="width: ${Math.min(100, (entry.duration / (entry.goal * 60 * 60 * 1000)) * 100)}%; background-color: ${entry.completed ? '#28A745' : '#FFCC00'};"></div>
                    </div>
                </div>
            `;
            
            historyContainer.appendChild(historyItem);
        });
    }

    // Sauvegarder l'état actuel du jeûne
    saveCurrentFasting() {
        if (!this.fastingActive) return;
        
        const data = {
            startTime: this.fastingStartTime,
            goal: this.fastingGoal
        };
        
        localStorage.setItem('currentFasting', JSON.stringify(data));
    }

    // Sauvegarder l'historique
    saveHistory() {
        localStorage.setItem('fastingHistory', JSON.stringify(this.fastingHistory));
    }

    // Charger l'historique
    loadHistory() {
        const history = localStorage.getItem('fastingHistory');
        if (history) {
            this.fastingHistory = JSON.parse(history);
        }
    }

    // Calculer la durée entre deux dates
    calculateDuration(start, end) {
        return end - start;
    }

    // Formater le temps en heures:minutes:secondes
    formatTime(milliseconds) {
        const totalSeconds = Math.floor(milliseconds / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        
        return `${this.padZero(hours)}:${this.padZero(minutes)}:${this.padZero(seconds)}`;
    }

    // Ajouter un zéro devant les nombres < 10
    padZero(num) {
        return num < 10 ? `0${num}` : num;
    }
}

// Initialiser le tracker de jeûne lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur la page appropriée
    const fastingContainer = document.getElementById('fasting-tracker-container');
    if (fastingContainer) {
        const tracker = new FastingTracker();
        tracker.init();
    }
});
