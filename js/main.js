// JavaScript principal pour toutes les pages
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du menu mobile
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuBtn && navMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
    
    // Fermer le menu lorsqu'un lien est cliqué
    const navLinks = document.querySelectorAll('.nav-menu a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navMenu.classList.remove('active');
        });
    });
    
    // Initialiser les dates dans les formulaires à la date du jour
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
    });
    
    // Afficher les messages d'erreur ou de succès s'ils existent
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        showAlert('error', 'Une erreur est survenue. Veuillez réessayer.');
    } else if (urlParams.has('success')) {
        showAlert('success', 'Opération réussie !');
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
    
    // Validation des formulaires
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            const requiredInputs = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                }
            });
            
            // Vérification spécifique pour les mots de passe
            const password = form.querySelector('#password');
            const confirmPassword = form.querySelector('#confirm_password');
            
            if (password && confirmPassword) {
                if (password.value !== confirmPassword.value) {
                    isValid = false;
                    confirmPassword.classList.add('error');
                    showAlert('error', 'Les mots de passe ne correspondent pas.');
                }
            }
            
            if (!isValid) {
                event.preventDefault();
                showAlert('error', 'Veuillez remplir tous les champs obligatoires correctement.');
            }
        });
    });
    
    // Gestion des zones de téléchargement
    const uploadArea = document.getElementById('upload-area');
    const imageUpload = document.getElementById('image-upload');
    
    if (uploadArea && imageUpload) {
        uploadArea.addEventListener('click', function() {
            imageUpload.click();
        });
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function() {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                imageUpload.files = e.dataTransfer.files;
                handleImageUpload(e.dataTransfer.files[0]);
            }
        });
        
        imageUpload.addEventListener('change', function() {
            if (this.files.length) {
                handleImageUpload(this.files[0]);
            }
        });
        
        function handleImageUpload(file) {
            // Vérifier si le fichier est une image
            if (!file.type.match('image.*')) {
                showAlert('error', 'Veuillez sélectionner une image.');
                return;
            }
            
            // Afficher l'image sélectionnée
            const reader = new FileReader();
            reader.onload = function(e) {
                const uploadedImage = document.getElementById('uploaded-image');
                if (uploadedImage) {
                    uploadedImage.src = e.target.result;
                    document.getElementById('analysis-results').style.display = 'block';
                    
                    // Simuler l'analyse morphologique
                    document.getElementById('analysis-content').innerHTML = '<div class="loading-spinner"></div><p>Analyse en cours...</p>';
                    
                    // Appel à l'API pour l'analyse
                    setTimeout(function() {
                        fetch('../api/chatgpt.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=analyze_morphology'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                document.getElementById('analysis-content').innerHTML = `<p class="text-danger">Erreur: ${data.error}</p>`;
                            } else {
                                document.getElementById('analysis-content').innerHTML = `<div>${data.response}</div>`;
                            }
                        })
                        .catch(error => {
                            document.getElementById('analysis-content').innerHTML = `<p class="text-danger">Erreur de connexion: ${error}</p>`;
                        });
                    }, 1500);
                }
            };
            reader.readAsDataURL(file);
        }
    }
    
    // Gestion des requêtes à l'API ChatGPT
    const aiMealQueryBtn = document.getElementById('ai-meal-query-btn');
    if (aiMealQueryBtn) {
        aiMealQueryBtn.addEventListener('click', function() {
            const query = document.getElementById('ai-meal-query').value.trim();
            if (!query) {
                showAlert('error', 'Veuillez saisir une question.');
                return;
            }
            
            document.getElementById('ai-meal-response').style.display = 'block';
            document.getElementById('ai-meal-response-content').innerHTML = '<div class="loading-spinner"></div><p>Chargement de la réponse...</p>';
            
            fetch('../api/chatgpt.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=custom_query&query=${encodeURIComponent(query)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('ai-meal-response-content').innerHTML = `<p class="text-danger">Erreur: ${data.error}</p>`;
                } else {
                    document.getElementById('ai-meal-response-content').innerHTML = `<div>${data.response}</div>`;
                }
            })
            .catch(error => {
                document.getElementById('ai-meal-response-content').innerHTML = `<p class="text-danger">Erreur de connexion: ${error}</p>`;
            });
        });
    }
    
    // Génération de recommandations de repas
    const generateMealRecommendationsBtn = document.getElementById('generate-meal-recommendations-btn');
    if (generateMealRecommendationsBtn) {
        generateMealRecommendationsBtn.addEventListener('click', function() {
            const recommendedMealsDiv = document.getElementById('recommended-meals');
            recommendedMealsDiv.innerHTML = '<div class="text-center"><div class="loading-spinner"></div><p>Génération des recommandations...</p></div>';
            
            fetch('../api/chatgpt.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_meal_recommendations'
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    recommendedMealsDiv.innerHTML = `<p class="text-danger">Erreur: ${data.error}</p>`;
                } else {
                    recommendedMealsDiv.innerHTML = `<div>${data.response}</div>`;
                }
            })
            .catch(error => {
                recommendedMealsDiv.innerHTML = `<p class="text-danger">Erreur de connexion: ${error}</p>`;
            });
        });
    }
    
    // Génération de programme personnalisé
    const generateProgramBtn = document.getElementById('generate-program-btn');
    if (generateProgramBtn) {
        generateProgramBtn.addEventListener('click', function() {
            const customProgramDiv = document.getElementById('custom-program');
            customProgramDiv.innerHTML = '<div class="text-center"><div class="loading-spinner"></div><p>Génération du programme...</p></div>';
            
            fetch('../api/chatgpt.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_activity_recommendations'
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    customProgramDiv.innerHTML = `<p class="text-danger">Erreur: ${data.error}</p>`;
                } else {
                    customProgramDiv.innerHTML = `<div>${data.response}</div>`;
                }
            })
            .catch(error => {
                customProgramDiv.innerHTML = `<p class="text-danger">Erreur de connexion: ${error}</p>`;
            });
        });
    }
});
