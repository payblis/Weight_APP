// JavaScript spécifique pour la page de profil
document.addEventListener('DOMContentLoaded', function() {
    // Charger les données du profil
    loadProfileData();
    
    // Fonction pour charger les données du profil
    function loadProfileData() {
        fetch('../api/profile.php')
            .then(response => response.json())
            .then(data => {
                populateProfileForm(data.user_profile);
                populateWeightGoalForm(data.user_profile);
                checkApiStatus();
            })
            .catch(error => {
                console.error('Erreur lors du chargement des données du profil:', error);
            });
    }
    
    // Remplir le formulaire de profil
    function populateProfileForm(profile) {
        if (!profile) return;
        
        document.getElementById('username').value = profile.username || '';
        document.getElementById('email').value = profile.email || '';
        document.getElementById('gender').value = profile.gender || 'homme';
        document.getElementById('age').value = profile.age || '';
        document.getElementById('height').value = profile.height || '';
    }
    
    // Remplir le formulaire d'objectif de poids
    function populateWeightGoalForm(profile) {
        if (!profile) return;
        
        document.getElementById('initial_weight').value = profile.initial_weight || '';
        document.getElementById('target_weight').value = profile.target_weight || '';
        document.getElementById('activity_level').value = profile.activity_level || 'sédentaire';
    }
    
    // Vérifier le statut de l'API
    function checkApiStatus() {
        fetch('../api/chatgpt.php')
            .then(response => response.json())
            .then(data => {
                const apiKeyInput = document.getElementById('api_key');
                if (data.api_configured) {
                    apiKeyInput.placeholder = '••••••••••••••••••••••••••••••••';
                    
                    // Ajouter une indication visuelle que l'API est configurée
                    const apiKeyForm = document.getElementById('apiKeyForm');
                    if (apiKeyForm) {
                        const statusDiv = document.createElement('div');
                        statusDiv.className = 'alert alert-success mt-2';
                        statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> API ChatGPT configurée';
                        apiKeyForm.insertBefore(statusDiv, apiKeyForm.querySelector('button').parentNode);
                    }
                } else {
                    apiKeyInput.placeholder = 'sk-...';
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification du statut de l\'API:', error);
            });
    }
    
    // Gestion du formulaire de profil
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../api/update_profile.php', {
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
                    showAlert('error', 'Erreur: ' + data.error);
                } else {
                    showAlert('success', 'Profil mis à jour avec succès');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('error', 'Une erreur est survenue lors de la mise à jour du profil');
            });
        });
    }
    
    // Gestion du formulaire d'objectif de poids
    const weightGoalForm = document.getElementById('weightGoalForm');
    if (weightGoalForm) {
        weightGoalForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../api/update_weight_goal.php', {
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
                    showAlert('error', 'Erreur: ' + data.error);
                } else {
                    showAlert('success', 'Objectif de poids mis à jour avec succès');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('error', 'Une erreur est survenue lors de la mise à jour de l\'objectif de poids');
            });
        });
    }
    
    // Gestion du formulaire de changement de mot de passe
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            // Vérifier que les mots de passe correspondent
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            if (newPassword !== confirmPassword) {
                showAlert('error', 'Les mots de passe ne correspondent pas');
                return;
            }
            
            fetch('../api/change_password.php', {
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
                    showAlert('error', 'Erreur: ' + data.error);
                } else {
                    showAlert('success', 'Mot de passe changé avec succès');
                    passwordForm.reset();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('error', 'Une erreur est survenue lors du changement de mot de passe');
            });
        });
    }
    
    // Gestion du formulaire de clé API
    const apiKeyForm = document.getElementById('apiKeyForm');
    if (apiKeyForm) {
        apiKeyForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../api/chatgpt.php', {
                method: 'POST',
                body: new URLSearchParams({
                    'action': 'update_api_key',
                    'api_key': formData.get('api_key')
                })
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
                    showAlert('error', 'Erreur: ' + data.error);
                } else {
                    showAlert('success', 'Clé API mise à jour avec succès');
                    apiKeyForm.reset();
                    checkApiStatus();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('error', 'Une erreur est survenue lors de la mise à jour de la clé API');
            });
        });
    }
    
    // Fonction pour afficher des alertes
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        const container = document.querySelector('.container');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            
            // Faire disparaître l'alerte après 5 secondes
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                setTimeout(() => {
                    alertDiv.remove();
                }, 500);
            }, 5000);
        }
    }
});
