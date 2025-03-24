// Intégration de l'analyse morphologique par IA dans le style MyFitnessPal
// pour l'application de suivi de perte de poids

class MorphologicalAnalysis {
    constructor() {
        this.analysisResults = null;
        this.bodyZones = [
            { id: 'abdomen', name: 'Abdomen', description: 'Zone abdominale et taille' },
            { id: 'arms', name: 'Bras', description: 'Bras et avant-bras' },
            { id: 'legs', name: 'Jambes', description: 'Cuisses et mollets' },
            { id: 'back', name: 'Dos', description: 'Haut et bas du dos' },
            { id: 'chest', name: 'Poitrine', description: 'Poitrine et pectoraux' },
            { id: 'shoulders', name: 'Épaules', description: 'Épaules et trapèzes' }
        ];
    }

    // Initialiser l'analyse morphologique
    init(containerId = 'morphological-analysis-container') {
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
        
        return true;
    }

    // Créer les éléments de l'interface
    createElements() {
        // Créer le conteneur principal
        const mainContainer = document.createElement('div');
        mainContainer.className = 'morphological-analysis-main';
        
        // Créer l'en-tête
        const header = document.createElement('div');
        header.className = 'morphological-analysis-header';
        
        const title = document.createElement('h2');
        title.textContent = 'Analyse morphologique par IA';
        
        const subtitle = document.createElement('p');
        subtitle.className = 'text-muted';
        subtitle.textContent = 'Importez une photo pour obtenir une analyse personnalisée et des recommandations ciblées';
        
        header.appendChild(title);
        header.appendChild(subtitle);
        
        // Créer le conteneur d'upload
        const uploadContainer = document.createElement('div');
        uploadContainer.className = 'upload-container mt-4';
        uploadContainer.id = 'upload-container';
        
        uploadContainer.innerHTML = `
            <div class="upload-area" id="upload-area">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="upload-text">
                    <p>Glissez-déposez votre photo ici</p>
                    <p class="text-muted">ou</p>
                    <button class="btn btn-primary" id="select-photo-btn">Sélectionner une photo</button>
                    <input type="file" id="photo-input" accept="image/*" style="display: none;">
                </div>
                <div class="upload-requirements text-muted mt-3">
                    <p>Exigences pour la photo :</p>
                    <ul>
                        <li>Position debout, de face ou de profil</li>
                        <li>Vêtements ajustés pour une analyse précise</li>
                        <li>Éclairage uniforme</li>
                        <li>Fond neutre si possible</li>
                    </ul>
                </div>
            </div>
        `;
        
        // Créer le conteneur des résultats d'analyse
        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'results-container mt-4';
        resultsContainer.id = 'results-container';
        resultsContainer.style.display = 'none';
        
        // Ajouter tous les éléments au conteneur principal
        mainContainer.appendChild(header);
        mainContainer.appendChild(uploadContainer);
        mainContainer.appendChild(resultsContainer);
        
        // Ajouter le conteneur principal au conteneur de la page
        this.container.appendChild(mainContainer);
        
        // Si des résultats d'analyse existent, les afficher
        if (this.analysisResults) {
            this.displayAnalysisResults();
        }
    }

    // Ajouter les écouteurs d'événements
    addEventListeners() {
        // Bouton pour sélectionner une photo
        const selectPhotoBtn = document.getElementById('select-photo-btn');
        if (selectPhotoBtn) {
            selectPhotoBtn.addEventListener('click', () => {
                const photoInput = document.getElementById('photo-input');
                if (photoInput) {
                    photoInput.click();
                }
            });
        }
        
        // Input de fichier pour l'upload de photo
        const photoInput = document.getElementById('photo-input');
        if (photoInput) {
            photoInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    this.handlePhotoUpload(e.target.files[0]);
                }
            });
        }
        
        // Zone de glisser-déposer
        const uploadArea = document.getElementById('upload-area');
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    this.handlePhotoUpload(e.dataTransfer.files[0]);
                }
            });
        }
    }

    // Gérer l'upload de photo
    handlePhotoUpload(file) {
        // Vérifier que le fichier est une image
        if (!file.type.match('image.*')) {
            alert('Veuillez sélectionner une image');
            return;
        }
        
        // Afficher un message de chargement
        const uploadContainer = document.getElementById('upload-container');
        if (uploadContainer) {
            uploadContainer.innerHTML = `
                <div class="loading-container text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Analyse en cours...</span>
                    </div>
                    <h4 class="mt-3">Analyse de votre photo en cours</h4>
                    <p class="text-muted">L'IA analyse votre morphologie pour des recommandations personnalisées</p>
                </div>
            `;
        }
        
        // Simuler un délai pour l'analyse
        setTimeout(() => {
            // Lire le fichier et afficher l'image
            const reader = new FileReader();
            reader.onload = (e) => {
                // Simuler une analyse par IA
                this.performAIAnalysis(e.target.result);
            };
            reader.readAsDataURL(file);
        }, 3000);
    }

    // Effectuer l'analyse par IA
    performAIAnalysis(imageData) {
        // Simuler une analyse par IA
        // Dans une implémentation réelle, cela ferait appel à l'API ChatGPT ou à un autre service d'IA
        
        // Générer des résultats d'analyse aléatoires
        const bodyFatPercentage = Math.floor(Math.random() * 15) + 15; // 15-30%
        
        // Générer des zones à cibler
        const targetZones = [];
        const zoneCount = Math.floor(Math.random() * 3) + 1; // 1-3 zones
        
        // Copier et mélanger les zones du corps
        const shuffledZones = [...this.bodyZones].sort(() => 0.5 - Math.random());
        
        // Sélectionner les premières zones
        for (let i = 0; i < zoneCount; i++) {
            targetZones.push({
                ...shuffledZones[i],
                fatPercentage: Math.floor(Math.random() * 10) + 20, // 20-30%
                recommendations: this.generateRecommendations(shuffledZones[i].id)
            });
        }
        
        // Créer les résultats d'analyse
        this.analysisResults = {
            imageData: imageData,
            bodyFatPercentage: bodyFatPercentage,
            targetZones: targetZones,
            generalRecommendations: [
                "Maintenez une alimentation équilibrée avec un déficit calorique modéré",
                "Pratiquez une activité physique régulière, au moins 30 minutes par jour",
                "Alternez entre exercices cardiovasculaires et renforcement musculaire"
            ],
            analysisDate: new Date().toISOString()
        };
        
        // Sauvegarder les données
        this.saveData();
        
        // Afficher les résultats
        this.displayAnalysisResults();
    }

    // Générer des recommandations pour une zone du corps
    generateRecommendations(zoneId) {
        // Simuler des recommandations personnalisées
        // Dans une implémentation réelle, cela ferait appel à l'API ChatGPT ou à un autre service d'IA
        
        const exerciseRecommendations = {
            abdomen: [
                { name: "Crunchs", description: "3 séries de 15 répétitions", intensity: "Modérée" },
                { name: "Planche", description: "3 séries de 30 secondes", intensity: "Élevée" },
                { name: "Mountain climbers", description: "3 séries de 20 répétitions", intensity: "Élevée" }
            ],
            arms: [
                { name: "Pompes", description: "3 séries de 10 répétitions", intensity: "Modérée" },
                { name: "Dips", description: "3 séries de 12 répétitions", intensity: "Élevée" },
                { name: "Curl biceps", description: "3 séries de 15 répétitions", intensity: "Modérée" }
            ],
            legs: [
                { name: "Squats", description: "3 séries de 15 répétitions", intensity: "Modérée" },
                { name: "Fentes", description: "3 séries de 12 répétitions par jambe", intensity: "Élevée" },
                { name: "Extensions de jambes", description: "3 séries de 15 répétitions", intensity: "Modérée" }
            ],
            back: [
                { name: "Tractions", description: "3 séries de 8 répétitions", intensity: "Élevée" },
                { name: "Rowing", description: "3 séries de 12 répétitions", intensity: "Modérée" },
                { name: "Superman", description: "3 séries de 15 répétitions", intensity: "Modérée" }
            ],
            chest: [
                { name: "Développé couché", description: "3 séries de 12 répétitions", intensity: "Élevée" },
                { name: "Écartés", description: "3 séries de 15 répétitions", intensity: "Modérée" },
                { name: "Pompes inclinées", description: "3 séries de 10 répétitions", intensity: "Élevée" }
            ],
            shoulders: [
                { name: "Développé épaules", description: "3 séries de 12 répétitions", intensity: "Élevée" },
                { name: "Élévations latérales", description: "3 séries de 15 répétitions", intensity: "Modérée" },
                { name: "Élévations frontales", description: "3 séries de 15 répétitions", intensity: "Modérée" }
            ]
        };
        
        const nutritionRecommendations = {
            abdomen: [
                "Limitez les aliments riches en sucres raffinés",
                "Privilégiez les aliments riches en fibres pour favoriser la satiété",
                "Évitez les boissons gazeuses et l'alcool"
            ],
            arms: [
                "Augmentez votre apport en protéines pour favoriser la croissance musculaire",
                "Consommez des glucides complexes avant l'entraînement",
                "Incluez des acides gras oméga-3 dans votre alimentation"
            ],
            legs: [
                "Consommez des protéines de qualité après l'entraînement",
                "Privilégiez les aliments riches en potassium pour éviter les crampes",
                "Hydratez-vous suffisamment avant, pendant et après l'exercice"
            ],
            back: [
                "Augmentez votre apport en protéines pour soutenir la masse musculaire",
                "Consommez des aliments riches en magnésium pour la récupération musculaire",
                "Privilégiez les aliments anti-inflammatoires"
            ],
            chest: [
                "Consommez des protéines de qualité pour favoriser la croissance musculaire",
                "Incluez des glucides complexes dans votre alimentation",
                "Évitez les excès de sodium pour limiter la rétention d'eau"
            ],
            shoulders: [
                "Augmentez votre apport en protéines pour soutenir la masse musculaire",
                "Consommez des aliments riches en calcium pour la santé des articulations",
                "Privilégiez les aliments anti-inflammatoires"
            ]
        };
        
        return {
            exercises: exerciseRecommendations[zoneId] || [],
            nutrition: nutritionRecommendations[zoneId] || []
        };
    }

    // Afficher les résultats d'analyse
    displayAnalysisResults() {
        // Vérifier que des résultats existent
        if (!this.analysisResults) return;
        
        // Récupérer le conteneur des résultats
        const resultsContainer = document.getElementById('results-container');
        if (!resultsContainer) return;
        
        // Afficher le conteneur
        resultsContainer.style.display = 'block';
        
        // Créer le contenu HTML
        let html = `
            <div class="results-header d-flex justify-content-between align-items-start">
                <div class="results-title">
                    <h3>Résultats de votre analyse morphologique</h3>
                    <p class="text-muted">Analyse réalisée le ${new Date(this.analysisResults.analysisDate).toLocaleDateString()}</p>
                </div>
                <button class="btn btn-outline-primary" id="new-analysis-btn">
                    <i class="fas fa-redo mr-1"></i> Nouvelle analyse
                </button>
            </div>
            
            <div class="results-content mt-4 row">
                <div class="col-md-4">
                    <div class="photo-container">
                        <img src="${this.analysisResults.imageData}" alt="Photo d'analyse" class="img-fluid rounded">
                    </div>
                    
                    <div class="body-fat-container mt-3 text-center">
                        <div class="body-fat-circle">
                            <div class="body-fat-value">${this.analysisResults.bodyFatPercentage}%</div>
                            <div class="body-fat-label">Taux de graisse corporelle estimé</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="target-zones-container">
                        <h4>Zones à cibler en priorité</h4>
                        <div class="target-zones-list mt-3">
        `;
        
        // Ajouter chaque zone cible
        this.analysisResults.targetZones.forEach(zone => {
            html += `
                <div class="target-zone-item mb-4">
                    <div class="zone-header d-flex justify-content-between align-items-center">
                        <h5>${zone.name}</h5>
                        <div class="zone-fat-percentage">
                            <span class="badge badge-primary">${zone.fatPercentage}% de graisse</span>
                        </div>
                    </div>
                    
                    <div class="zone-content mt-2">
                        <div class="zone-exercises mb-3">
                            <h6>Exercices recommandés</h6>
                            <div class="exercises-list">
            `;
            
            // Ajouter les exercices recommandés
            zone.recommendations.exercises.forEach(exercise => {
                html += `
                    <div class="exercise-item d-flex justify-content-between align-items-center">
                        <div class="exercise-name">${exercise.name}</div>
                        <div class="exercise-details">
                            <span class="exercise-description">${exercise.description}</span>
                            <span class="exercise-intensity badge badge-${exercise.intensity === 'Élevée' ? 'danger' : 'warning'} ml-2">${exercise.intensity}</span>
                        </div>
                    </div>
                `;
            });
            
            html += `
                            </div>
                        </div>
                        
                        <div class="zone-nutrition">
                            <h6>Recommandations nutritionnelles</h6>
                            <ul class="nutrition-list">
            `;
            
            // Ajouter les recommandations nutritionnelles
            zone.recommendations.nutrition.forEach(nutrition => {
                html += `<li>${nutrition}</li>`;
            });
            
            html += `
                            </ul>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `
                        </div>
                    </div>
                    
                    <div class="general-recommendations-container mt-4">
                        <h4>Recommandations générales</h4>
                        <ul class="general-recommendations-list mt-2">
        `;
        
        // Ajouter les recommandations générales
        this.analysisResults.generalRecommendations.forEach(recommendation => {
            html += `<li>${recommendation}</li>`;
        });
        
        html += `
                        </ul>
                    </div>
                    
                    <div class="ai-integration-container mt-4">
                        <h4>Intégration avec votre programme</h4>
                        <p>Ces recommandations ont été automatiquement intégrées à votre programme personnalisé de perte de poids.</p>
                        <div class="integration-actions mt-3">
                            <button class="btn btn-primary" id="view-program-btn">
                                <i class="fas fa-calendar-alt mr-1"></i> Voir mon programme
                            </button>
                            <button class="btn btn-outline-primary ml-2" id="export-results-btn">
                                <i class="fas fa-download mr-1"></i> Exporter les résultats
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Mettre à jour le contenu
        resultsContainer.innerHTML = html;
        
        // Ajouter des écouteurs d'événements pour les boutons
        const newAnalysisBtn = document.getElementById('new-analysis-btn');
        if (newAnalysisBtn) {
            newAnalysisBtn.addEventListener('click', () => this.resetAnalysis());
        }
        
        const viewProgramBtn = document.getElementById('view-program-btn');
        if (viewProgramBtn) {
            viewProgramBtn.addEventListener('click', () => this.viewProgram());
        }
        
        const exportResultsBtn = document.getElementById('export-results-btn');
        if (exportResultsBtn) {
            exportResultsBtn.addEventListener('click', () => this.exportResults());
        }
        
        // Masquer le conteneur d'upload
        const uploadContainer = document.getElementById('upload-container');
        if (uploadContainer) {
            uploadContainer.style.display = 'none';
        }
    }

    // Réinitialiser l'analyse
    resetAnalysis() {
        // Réinitialiser les résultats
        this.analysisResults = null;
        
        // Sauvegarder les données
        this.saveData();
        
        // Recréer l'interface d'upload
        const uploadContainer = document.getElementById('upload-container');
        if (uploadContainer) {
            uploadContainer.style.display = 'block';
            uploadContainer.innerHTML = `
                <div class="upload-area" id="upload-area">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">
                        <p>Glissez-déposez votre photo ici</p>
                        <p class="text-muted">ou</p>
                        <button class="btn btn-primary" id="select-photo-btn">Sélectionner une photo</button>
                        <input type="file" id="photo-input" accept="image/*" style="display: none;">
                    </div>
                    <div class="upload-requirements text-muted mt-3">
                        <p>Exigences pour la photo :</p>
                        <ul>
                            <li>Position debout, de face ou de profil</li>
                            <li>Vêtements ajustés pour une analyse précise</li>
                            <li>Éclairage uniforme</li>
                            <li>Fond neutre si possible</li>
                        </ul>
                    </div>
                </div>
            `;
        }
        
        // Masquer le conteneur des résultats
        const resultsContainer = document.getElementById('results-container');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
        
        // Réinitialiser les écouteurs d'événements
        this.addEventListeners();
    }

    // Voir le programme personnalisé
    viewProgram() {
        // Simuler la redirection vers le programme personnalisé
        alert('Redirection vers votre programme personnalisé');
    }

    // Exporter les résultats
    exportResults() {
        // Simuler l'export des résultats
        alert('Les résultats ont été exportés');
    }

    // Sauvegarder les données
    saveData() {
        localStorage.setItem('morphologicalAnalysisData', JSON.stringify({
            analysisResults: this.analysisResults
        }));
    }

    // Charger les données
    loadData() {
        const data = localStorage.getItem('morphologicalAnalysisData');
        if (data) {
            const parsedData = JSON.parse(data);
            this.analysisResults = parsedData.analysisResults;
        }
    }
}

// Initialiser l'analyse morphologique lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur la page appropriée
    const morphologicalAnalysisContainer = document.getElementById('morphological-analysis-container');
    if (morphologicalAnalysisContainer) {
        const morphologicalAnalysis = new MorphologicalAnalysis();
        morphologicalAnalysis.init();
    }
});
