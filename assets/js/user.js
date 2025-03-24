// Gestion des graphiques
function initializeCharts() {
    // Graphique de progression du poids
    if (document.getElementById('weightChart')) {
        new Chart(document.getElementById('weightChart'), {
            type: 'line',
            data: {
                labels: weightData.dates,
                datasets: [{
                    label: 'Poids (kg)',
                    data: weightData.weights,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
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
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    }

    // Graphique des calories
    if (document.getElementById('calorieChart')) {
        new Chart(document.getElementById('calorieChart'), {
            type: 'bar',
            data: {
                labels: calorieData.dates,
                datasets: [{
                    label: 'Calories consommées',
                    data: calorieData.consumed,
                    backgroundColor: '#1cc88a'
                }, {
                    label: 'Objectif calorique',
                    data: calorieData.target,
                    backgroundColor: '#4e73df'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Graphique des macronutriments
    if (document.getElementById('macroChart')) {
        new Chart(document.getElementById('macroChart'), {
            type: 'doughnut',
            data: {
                labels: ['Protéines', 'Glucides', 'Lipides'],
                datasets: [{
                    data: macroData,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
}

// Gestion du journal alimentaire
function initializeFoodJournal() {
    const searchFood = document.getElementById('searchFood');
    if (searchFood) {
        searchFood.addEventListener('input', debounce(async (e) => {
            const query = e.target.value;
            if (query.length >= 2) {
                try {
                    const response = await fetch(`/api/search_food.php?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    updateFoodSuggestions(data);
                } catch (error) {
                    console.error('Erreur lors de la recherche:', error);
                }
            }
        }, 300));
    }
}

function updateFoodSuggestions(foods) {
    const suggestionsList = document.getElementById('foodSuggestions');
    if (!suggestionsList) return;

    suggestionsList.innerHTML = '';
    foods.forEach(food => {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${food.name}</strong>
                    <small class="text-muted">${food.brand || ''}</small>
                </div>
                <button class="btn btn-sm btn-primary" onclick="addFoodToMeal(${food.id})">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        `;
        suggestionsList.appendChild(li);
    });
}

async function addFoodToMeal(foodId) {
    try {
        const response = await fetch('/api/add_food_to_meal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                food_id: foodId,
                meal_id: currentMealId,
                quantity: 1
            })
        });
        
        if (response.ok) {
            showToast('Succès', 'Aliment ajouté au repas', 'success');
            updateMealSummary();
        } else {
            throw new Error('Erreur lors de l\'ajout');
        }
    } catch (error) {
        showToast('Erreur', 'Impossible d\'ajouter l\'aliment', 'error');
    }
}

// Gestion du coach IA
let aiConversation = [];

async function sendMessageToAI(message) {
    const chatMessages = document.querySelector('.ai-chat-messages');
    const input = document.querySelector('.ai-chat-input input');
    
    // Ajouter le message de l'utilisateur
    appendMessage(message, true);
    aiConversation.push({ role: 'user', content: message });
    input.value = '';

    try {
        const response = await fetch('/api/chat_with_ai.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                messages: aiConversation
            })
        });

        const data = await response.json();
        
        // Ajouter la réponse de l'IA
        appendMessage(data.message, false);
        aiConversation.push({ role: 'assistant', content: data.message });

        // Scroll vers le bas
        chatMessages.scrollTop = chatMessages.scrollHeight;
    } catch (error) {
        showToast('Erreur', 'Impossible de communiquer avec l\'IA', 'error');
    }
}

function appendMessage(message, isUser) {
    const chatMessages = document.querySelector('.ai-chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `ai-message ${isUser ? 'user' : ''}`;
    messageDiv.innerHTML = `
        <div class="message-content">
            ${message}
        </div>
    `;
    chatMessages.appendChild(messageDiv);
}

// Gestion des notifications
function initializeNotifications() {
    if ('Notification' in window) {
        Notification.requestPermission();
    }

    // Vérifier les nouvelles notifications toutes les minutes
    setInterval(checkNotifications, 60000);
}

async function checkNotifications() {
    try {
        const response = await fetch('/api/check_notifications.php');
        const notifications = await response.json();
        
        notifications.forEach(notification => {
            showNotification(notification);
        });
    } catch (error) {
        console.error('Erreur lors de la vérification des notifications:', error);
    }
}

function showNotification(notification) {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(notification.title, {
            body: notification.message,
            icon: '/assets/img/logo.png'
        });
    }

    showToast(notification.title, notification.message, notification.type);
}

// Utilitaires
function showToast(title, message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    const container = document.getElementById('toast-container') || document.body;
    container.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    // Initialiser les tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialiser les popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Initialiser les graphiques
    initializeCharts();
    
    // Initialiser le journal alimentaire
    initializeFoodJournal();
    
    // Initialiser les notifications
    initializeNotifications();
    
    // Gérer la fermeture du sidebar sur mobile
    const sidebarToggle = document.querySelector('.navbar-toggler');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            document.querySelector('.user-sidebar').classList.toggle('show');
        });
    }
    
    // Gérer l'envoi des messages au coach IA
    const aiChatForm = document.querySelector('.ai-chat-input form');
    if (aiChatForm) {
        aiChatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const input = aiChatForm.querySelector('input');
            if (input.value.trim()) {
                sendMessageToAI(input.value.trim());
            }
        });
    }
}); 