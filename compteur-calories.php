<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

include 'header.php';
?>

<main class="py-5">
    <div class="container">
        <!-- Hero Section -->
        <div class="row align-items-center mb-5">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Compteur de Calories MyFity</h1>
                <p class="lead mb-4">Le moyen le plus simple et précis de suivre votre apport calorique quotidien. Plus de 14 millions d'aliments dans notre base de données.</p>
                <a href="food-log.php" class="btn btn-primary btn-lg">Commencer à compter</a>
            </div>
            <div class="col-lg-6">
                <img src="assets/images/consign.png" alt="Compteur de calories" class="img-fluid rounded shadow">
            </div>
        </div>

        <!-- Features Section -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-search fa-3x text-primary mb-3"></i>
                        <h3 class="h5 fw-bold mb-3">Recherche rapide</h3>
                        <p class="text-muted">Trouvez instantanément n'importe quel aliment dans notre base de données de plus de 14 millions d'articles.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-barcode fa-3x text-primary mb-3"></i>
                        <h3 class="h5 fw-bold mb-3">Scanner de codes-barres</h3>
                        <p class="text-muted">Scannez simplement le code-barres de vos produits pour les ajouter instantanément à votre journal.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-chart-pie fa-3x text-primary mb-3"></i>
                        <h3 class="h5 fw-bold mb-3">Analyses détaillées</h3>
                        <p class="text-muted">Visualisez vos macronutriments, vitamines et minéraux avec des graphiques clairs et précis.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- How it works -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center display-5 fw-bold mb-5">Comment ça marche ?</h2>
            </div>
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <span class="fw-bold">1</span>
                    </div>
                </div>
                <h4 class="h5 fw-bold">Recherchez</h4>
                <p class="text-muted">Tapez le nom de l'aliment ou scannez son code-barres</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <span class="fw-bold">2</span>
                    </div>
                </div>
                <h4 class="h5 fw-bold">Sélectionnez</h4>
                <p class="text-muted">Choisissez la portion et l'heure du repas</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <span class="fw-bold">3</span>
                    </div>
                </div>
                <h4 class="h5 fw-bold">Ajoutez</h4>
                <p class="text-muted">L'aliment est automatiquement ajouté à votre journal</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <span class="fw-bold">4</span>
                    </div>
                </div>
                <h4 class="h5 fw-bold">Suivez</h4>
                <p class="text-muted">Visualisez vos progrès et analyses nutritionnelles</p>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-primary text-white rounded p-5 text-center">
            <h2 class="fw-bold mb-3">Prêt à commencer ?</h2>
            <p class="lead mb-4">Rejoignez des millions d'utilisateurs qui ont déjà transformé leur santé avec MyFity</p>
            <a href="register.php" class="btn btn-light btn-lg">Créer un compte gratuit</a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?> 