// JavaScript spécifique pour la page de suivi du poids
document.addEventListener('DOMContentLoaded', function() {
    // Charger les données de poids
    loadWeightData();
    
    // Fonction pour charger les données de poids
    function loadWeightData() {
        fetch('../api/weight.php')
            .then(response => response.json())
            .then(data => {
                updateWeightStats(data);
                createWeightChart(data.weight_logs);
                updateWeightHistory(data.weight_logs);
            })
            .catch(error => {
                console.error('Erreur lors du chargement des données de poids:', error);
            });
    }
    
    // Mettre à jour les statistiques de poids
    function updateWeightStats(data) {
        const initialWeightElement = document.getElementById('initial-weight');
        const currentWeightElement = document.getElementById('current-weight');
        const targetWeightElement = document.getElementById('target-weight');
        const weightLostElement = document.getElementById('weight-lost');
        const progressBarElement = document.getElementById('progress-bar');
        const progressTextElement = document.getElementById('progress-text');
        
        const initialWeight = parseFloat(data.initial_weight);
        initialWeightElement.textContent = initialWeight.toFixed(1);
        
        let currentWeight = initialWeight;
        if (data.weight_logs && data.weight_logs.length > 0) {
            // Trier les logs par date (le plus récent en premier)
            const sortedLogs = [...data.weight_logs].sort((a, b) => new Date(b.log_date) - new Date(a.log_date));
            currentWeight = parseFloat(sortedLogs[0].weight);
        }
        
        currentWeightElement.textContent = currentWeight.toFixed(1);
        
        const targetWeight = parseFloat(data.target_weight);
        targetWeightElement.textContent = targetWeight.toFixed(1);
        
        const weightLost = initialWeight - currentWeight;
        weightLostElement.textContent = weightLost.toFixed(1);
        
        // Calculer le pourcentage de progression
        const totalToLose = initialWeight - targetWeight;
        let progressPercentage = 0;
        
        if (totalToLose > 0) {
            progressPercentage = Math.min(100, Math.max(0, (weightLost / totalToLose) * 100));
        }
        
        progressBarElement.style.width = progressPercentage.toFixed(1) + '%';
        progressTextElement.textContent = progressPercentage.toFixed(1) + '% de l\'objectif atteint';
    }
    
    // Créer le graphique de poids
    function createWeightChart(weightLogs) {
        if (!weightLogs || weightLogs.length === 0) return;
        
        const ctx = document.getElementById('weightChart').getContext('2d');
        
        // Trier les logs par date
        const sortedLogs = [...weightLogs].sort((a, b) => new Date(a.log_date) - new Date(b.log_date));
        
        const dates = sortedLogs.map(log => log.log_date);
        const weights = sortedLogs.map(log => log.weight);
        
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
    
    // Mettre à jour l'historique des poids
    function updateWeightHistory(weightLogs) {
        const weightHistoryBody = document.getElementById('weight-history-body');
        
        if (!weightLogs || weightLogs.length === 0) {
            weightHistoryBody.innerHTML = '<tr><td colspan="5" class="text-center">Aucun enregistrement de poids</td></tr>';
            return;
        }
        
        // Trier les logs par date (le plus récent en premier)
        const sortedLogs = [...weightLogs].sort((a, b) => new Date(b.log_date) - new Date(a.log_date));
        
        let html = '';
        let previousWeight = null;
        
        sortedLogs.forEach((log, index) => {
            const currentWeight = parseFloat(log.weight);
            let variation = '';
            
            if (previousWeight !== null) {
                const diff = currentWeight - previousWeight;
                if (diff > 0) {
                    variation = `<span class="text-danger">+${diff.toFixed(1)} kg</span>`;
                } else if (diff < 0) {
                    variation = `<span class="text-success">${diff.toFixed(1)} kg</span>`;
                } else {
                    variation = '<span>0 kg</span>';
                }
            } else if (index < sortedLogs.length - 1) {
                const nextWeight = parseFloat(sortedLogs[index + 1].weight);
                const diff = currentWeight - nextWeight;
                if (diff > 0) {
                    variation = `<span class="text-danger">+${diff.toFixed(1)} kg</span>`;
                } else if (diff < 0) {
                    variation = `<span class="text-success">${diff.toFixed(1)} kg</span>`;
                } else {
                    variation = '<span>0 kg</span>';
                }
            }
            
            previousWeight = currentWeight;
            
            html += `
                <tr>
                    <td>${log.log_date}</td>
                    <td>${log.weight} kg</td>
                    <td>${variation}</td>
                    <td>${log.notes || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteWeightLog(${log.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        weightHistoryBody.innerHTML = html;
    }
    
    // Fonction pour supprimer un enregistrement de poids
    window.deleteWeightLog = function(logId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet enregistrement ?')) {
            fetch(`../api/delete_weight.php?id=${logId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recharger les données
                    loadWeightData();
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
    
    // Gestion du formulaire d'ajout de poids
    const weightLogForm = document.getElementById('weightLogForm');
    if (weightLogForm) {
        weightLogForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../api/weight.php', {
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
                    weightLogForm.reset();
                    
                    // Définir la date à aujourd'hui
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('log_date').value = today;
                    
                    // Recharger les données
                    loadWeightData();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        });
    }
});
