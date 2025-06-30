<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

include 'header.php';
?>
<main class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-4">API MyFity</h1>
            <p class="lead text-muted">Intégrez les données MyFity dans vos applications et services !</p>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-bold mb-3">Fonctionnalités de l'API</h3>
                        <ul class="text-muted">
                            <li>Accès aux bases de données aliments et nutriments</li>
                            <li>Connexion à des applications tierces (fitness, santé...)</li>
                            <li>Export des journaux alimentaires et d'activité</li>
                            <li>Création de bots et d'intégrations personnalisées</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-bold mb-3">Documentation & Support</h3>
                        <p class="text-muted">La documentation complète de l'API est disponible sur demande. Pour toute question technique ou demande d'accès, contactez notre équipe développeur.</p>
                        <a href="mailto:dev@myfity.com" class="btn btn-primary">Contacter l'équipe API</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-primary text-white rounded p-5 text-center">
            <h2 class="fw-bold mb-3">Prêt à connecter votre app ?</h2>
            <a href="mailto:dev@myfity.com" class="btn btn-light btn-lg">Demander un accès API</a>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?> 