// Fonctionnalité de scan de code-barres pour l'application FitTrack
// Style MyFitnessPal

class BarcodeScanner {
    constructor() {
        this.scannerActive = false;
        this.videoElement = null;
        this.canvasElement = null;
        this.scanResult = null;
        this.scannerContainer = null;
    }

    // Initialiser le scanner
    init(containerId = 'barcode-scanner-container') {
        this.scannerContainer = document.getElementById(containerId);
        if (!this.scannerContainer) {
            console.error('Container not found');
            return false;
        }

        // Créer les éléments nécessaires
        this.createElements();
        
        // Ajouter les écouteurs d'événements
        this.addEventListeners();
        
        return true;
    }

    // Créer les éléments du scanner
    createElements() {
        // Créer l'élément vidéo
        this.videoElement = document.createElement('video');
        this.videoElement.id = 'barcode-video';
        this.videoElement.className = 'barcode-video';
        this.videoElement.setAttribute('playsinline', 'true');
        this.videoElement.style.display = 'none';
        this.scannerContainer.appendChild(this.videoElement);
        
        // Créer l'élément canvas
        this.canvasElement = document.createElement('canvas');
        this.canvasElement.id = 'barcode-canvas';
        this.canvasElement.className = 'barcode-canvas';
        this.canvasElement.style.display = 'none';
        this.scannerContainer.appendChild(this.canvasElement);
        
        // Créer l'élément de résultat
        if (!document.getElementById('scan-result')) {
            this.scanResult = document.createElement('div');
            this.scanResult.id = 'scan-result';
            this.scanResult.className = 'scan-result';
            this.scanResult.style.display = 'none';
            this.scanResult.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <h5 id="scanned-product-name">Nom du produit</h5>
                    <span class="badge badge-primary" id="scanned-product-calories">0 cal</span>
                </div>
                <div class="d-flex mt-2">
                    <div class="mr-3">
                        <small class="text-gray">Protéines</small>
                        <div id="scanned-product-protein">0g</div>
                    </div>
                    <div class="mr-3">
                        <small class="text-gray">Glucides</small>
                        <div id="scanned-product-carbs">0g</div>
                    </div>
                    <div>
                        <small class="text-gray">Lipides</small>
                        <div id="scanned-product-fat">0g</div>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-sm btn-primary" id="add-scanned-product">Ajouter au journal</button>
                    <button class="btn btn-sm btn-outline ml-2" id="cancel-scan">Annuler</button>
                </div>
            `;
            this.scannerContainer.appendChild(this.scanResult);
        } else {
            this.scanResult = document.getElementById('scan-result');
        }
    }

    // Ajouter les écouteurs d'événements
    addEventListeners() {
        const scanButton = document.getElementById('scan-barcode-btn');
        if (scanButton) {
            scanButton.addEventListener('click', () => this.startScan());
        }
        
        const cancelButton = document.getElementById('cancel-scan');
        if (cancelButton) {
            cancelButton.addEventListener('click', () => this.cancelScan());
        }
        
        const addButton = document.getElementById('add-scanned-product');
        if (addButton) {
            addButton.addEventListener('click', () => this.addScannedProduct());
        }
    }

    // Démarrer le scan
    startScan() {
        // Pour la démo, nous simulons le scan
        this.simulateScan();
        
        // Dans une implémentation réelle, nous utiliserions la caméra
        // this.startRealScan();
    }

    // Simuler un scan pour la démo
    simulateScan() {
        // Masquer le résultat précédent
        this.scanResult.style.display = 'none';
        
        // Afficher l'animation de scan
        const scannerIcon = document.querySelector('.barcode-scanner-icon');
        if (scannerIcon) {
            scannerIcon.innerHTML = '<div class="loading-spinner"></div>';
        }
        
        // Simuler le temps de scan
        setTimeout(() => {
            // Restaurer l'icône
            if (scannerIcon) {
                scannerIcon.innerHTML = '<i class="fas fa-barcode"></i>';
            }
            
            // Générer un produit aléatoire
            const products = [
                {
                    name: 'Yaourt nature',
                    calories: 120,
                    protein: 5,
                    carbs: 15,
                    fat: 3
                },
                {
                    name: 'Barre de céréales',
                    calories: 180,
                    protein: 3,
                    carbs: 28,
                    fat: 6
                },
                {
                    name: 'Pomme',
                    calories: 95,
                    protein: 0.5,
                    carbs: 25,
                    fat: 0.3
                },
                {
                    name: 'Thon en conserve',
                    calories: 150,
                    protein: 30,
                    carbs: 0,
                    fat: 3
                },
                {
                    name: 'Pain complet',
                    calories: 80,
                    protein: 4,
                    carbs: 15,
                    fat: 1
                }
            ];
            
            const product = products[Math.floor(Math.random() * products.length)];
            
            // Afficher le résultat
            document.getElementById('scanned-product-name').textContent = product.name;
            document.getElementById('scanned-product-calories').textContent = `${product.calories} cal`;
            document.getElementById('scanned-product-protein').textContent = `${product.protein}g`;
            document.getElementById('scanned-product-carbs').textContent = `${product.carbs}g`;
            document.getElementById('scanned-product-fat').textContent = `${product.fat}g`;
            
            this.scanResult.style.display = 'block';
        }, 2000);
    }

    // Démarrer un scan réel avec la caméra (implémentation future)
    startRealScan() {
        if (this.scannerActive) return;
        
        this.scannerActive = true;
        
        // Vérifier si la caméra est disponible
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Votre navigateur ne prend pas en charge l\'accès à la caméra');
            this.scannerActive = false;
            return;
        }
        
        // Afficher l'élément vidéo
        this.videoElement.style.display = 'block';
        
        // Accéder à la caméra
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(stream => {
                this.videoElement.srcObject = stream;
                this.videoElement.play();
                requestAnimationFrame(() => this.scanFrame());
            })
            .catch(error => {
                console.error('Erreur d\'accès à la caméra:', error);
                alert('Impossible d\'accéder à la caméra');
                this.scannerActive = false;
            });
    }

    // Scanner une image pour détecter un code-barres (implémentation future)
    scanFrame() {
        if (!this.scannerActive) return;
        
        const context = this.canvasElement.getContext('2d');
        
        // Ajuster la taille du canvas à la vidéo
        if (this.videoElement.videoWidth && this.videoElement.videoHeight) {
            this.canvasElement.width = this.videoElement.videoWidth;
            this.canvasElement.height = this.videoElement.videoHeight;
        }
        
        // Dessiner l'image de la vidéo sur le canvas
        context.drawImage(this.videoElement, 0, 0, this.canvasElement.width, this.canvasElement.height);
        
        // Obtenir les données de l'image
        const imageData = context.getImageData(0, 0, this.canvasElement.width, this.canvasElement.height);
        
        // Ici, nous utiliserions une bibliothèque comme QuaggaJS ou ZXing pour détecter le code-barres
        // Pour la démo, nous simulons une détection
        
        // Continuer à scanner
        requestAnimationFrame(() => this.scanFrame());
    }

    // Annuler le scan
    cancelScan() {
        this.scannerActive = false;
        
        // Masquer les éléments
        this.scanResult.style.display = 'none';
        this.videoElement.style.display = 'none';
        
        // Arrêter la vidéo si elle est en cours
        if (this.videoElement.srcObject) {
            const tracks = this.videoElement.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            this.videoElement.srcObject = null;
        }
    }

    // Ajouter le produit scanné au journal
    addScannedProduct() {
        // Récupérer les informations du produit
        const name = document.getElementById('scanned-product-name').textContent;
        const calories = document.getElementById('scanned-product-calories').textContent;
        
        // Simuler l'ajout au journal
        alert(`${name} (${calories}) ajouté au journal`);
        
        // Masquer le résultat
        this.scanResult.style.display = 'none';
        
        // Mettre à jour les calories totales (simulation)
        const foodCalories = document.getElementById('food-calories');
        if (foodCalories) {
            const currentCalories = parseInt(foodCalories.textContent);
            const addedCalories = parseInt(calories);
            foodCalories.textContent = currentCalories + addedCalories;
        }
        
        // Mettre à jour le cercle de progression des calories
        if (typeof calculateMealCaloriesPercentage === 'function') {
            const percentage = calculateMealCaloriesPercentage();
            if (typeof createProgressCircle === 'function') {
                createProgressCircle('calories-circle', percentage);
            }
        }
    }
}

// Initialiser le scanner lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur la page des repas
    if (window.location.pathname.includes('meals')) {
        const scanner = new BarcodeScanner();
        scanner.init();
    }
});
