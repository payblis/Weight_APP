<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

include 'header.php';
?>

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <h1 class="display-5 fw-bold text-center mb-5">Conditions Générales d'Utilisation</h1>
                        
                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">1. Acceptation des conditions</h2>
                            <p>En accédant et en utilisant l'application MyFity, vous acceptez d'être lié par ces conditions générales d'utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser notre service.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">2. Description du service</h2>
                            <p>MyFity est une application de suivi nutritionnel et de fitness qui permet aux utilisateurs de :</p>
                            <ul>
                                <li>Consigner leurs repas et activités physiques</li>
                                <li>Suivre leurs objectifs de poids et de santé</li>
                                <li>Accéder à des analyses nutritionnelles</li>
                                <li>Participer à une communauté d'utilisateurs</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">3. Inscription et compte utilisateur</h2>
                            <p>Pour utiliser MyFity, vous devez créer un compte en fournissant des informations exactes et à jour. Vous êtes responsable de maintenir la confidentialité de vos identifiants de connexion.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">4. Utilisation acceptable</h2>
                            <p>Vous vous engagez à utiliser MyFity uniquement à des fins légales et conformes à ces conditions. Il est interdit de :</p>
                            <ul>
                                <li>Utiliser le service à des fins commerciales non autorisées</li>
                                <li>Tenter d'accéder aux comptes d'autres utilisateurs</li>
                                <li>Publier du contenu offensant ou inapproprié</li>
                                <li>Utiliser des robots ou scripts automatisés</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">5. Contenu utilisateur</h2>
                            <p>Vous conservez la propriété du contenu que vous publiez sur MyFity. En publiant du contenu, vous accordez à MyFity une licence non exclusive pour utiliser, reproduire et distribuer ce contenu dans le cadre du service.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">6. Propriété intellectuelle</h2>
                            <p>MyFity et son contenu original sont protégés par les droits d'auteur, marques de commerce et autres lois sur la propriété intellectuelle. Vous ne pouvez pas reproduire, distribuer ou créer des œuvres dérivées sans autorisation.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">7. Limitation de responsabilité</h2>
                            <p>MyFity est fourni "en l'état" sans garanties. Nous ne serons pas responsables des dommages indirects, accessoires ou consécutifs résultant de l'utilisation du service.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">8. Modification des conditions</h2>
                            <p>Nous nous réservons le droit de modifier ces conditions à tout moment. Les modifications prendront effet immédiatement après leur publication. Votre utilisation continue du service constitue votre acceptation des nouvelles conditions.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">9. Résiliation</h2>
                            <p>Vous pouvez résilier votre compte à tout moment. Nous pouvons également suspendre ou résilier votre accès si vous violez ces conditions.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">10. Droit applicable</h2>
                            <p>Ces conditions sont régies par le droit français. Tout litige sera soumis à la compétence exclusive des tribunaux français.</p>
                        </div>

                        <div class="text-center mt-5">
                            <p class="text-muted">Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
                            <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?> 