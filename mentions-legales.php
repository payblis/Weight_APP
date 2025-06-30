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
                        <h1 class="display-5 fw-bold text-center mb-5">Mentions Légales</h1>
                        
                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">1. Éditeur du site</h2>
                            <p><strong>Raison sociale :</strong> MyFity SARL</p>
                            <p><strong>Adresse :</strong> 123 Rue de la Santé, 75001 Paris, France</p>
                            <p><strong>Téléphone :</strong> +33 1 23 45 67 89</p>
                            <p><strong>Email :</strong> contact@myfity.com</p>
                            <p><strong>Capital social :</strong> 50 000 €</p>
                            <p><strong>RCS :</strong> Paris B 123 456 789</p>
                            <p><strong>SIRET :</strong> 123 456 789 00012</p>
                            <p><strong>Directeur de publication :</strong> Jean Dupont</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">2. Hébergement</h2>
                            <p><strong>Hébergeur :</strong> OVH SAS</p>
                            <p><strong>Adresse :</strong> 2 rue Kellermann, 59100 Roubaix, France</p>
                            <p><strong>Téléphone :</strong> 1007</p>
                            <p><strong>Site web :</strong> www.ovh.com</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">3. Propriété intellectuelle</h2>
                            <p>L'ensemble de ce site relève de la législation française et internationale sur le droit d'auteur et la propriété intellectuelle. Tous les droits de reproduction sont réservés, y compris pour les documents téléchargeables et les représentations iconographiques et photographiques.</p>
                            <p>La reproduction de tout ou partie de ce site sur un support électronique quel qu'il soit est formellement interdite sauf autorisation expresse du directeur de la publication.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">4. Responsabilité</h2>
                            <p>Les informations contenues sur ce site sont aussi précises que possible et le site est périodiquement remis à jour, mais peut toutefois contenir des inexactitudes, des omissions ou des lacunes.</p>
                            <p>Si vous constatez une lacune, erreur ou ce qui parait être un dysfonctionnement, merci de bien vouloir le signaler par email à l'adresse contact@myfity.com, en décrivant le problème de la manière la plus précise possible.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">5. Liens hypertextes</h2>
                            <p>Les liens hypertextes mis en place dans le cadre du présent site web en direction d'autres ressources présentes sur le réseau Internet ne sauraient engager la responsabilité de MyFity SARL.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">6. Cookies</h2>
                            <p>Le site peut-être amené à vous demander l'acceptation des cookies pour des besoins de statistiques et d'affichage. Un cookie ne nous permet pas de vous identifier ; il sert uniquement à enregistrer des informations relatives à la navigation de votre ordinateur sur notre site.</p>
                            <p>Vous pouvez librement accepter ou refuser ces cookies en paramétrant votre navigateur. Vous pouvez également les supprimer à tout moment.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">7. Droit applicable</h2>
                            <p>Tout litige en relation avec l'utilisation du site myfity.com est soumis au droit français. En dehors des cas où la loi ne le permet pas, il est fait attribution exclusive de juridiction aux tribunaux compétents de Paris.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">8. Protection des données personnelles</h2>
                            <p>Conformément aux dispositions de la loi n° 78-17 du 6 janvier 1978 modifiée, vous disposez d'un droit d'accès, de modification et de suppression des données vous concernant.</p>
                            <p>Pour exercer ce droit, adressez-vous à :</p>
                            <p>MyFity SARL<br>
                            123 Rue de la Santé<br>
                            75001 Paris, France<br>
                            Email : privacy@myfity.com</p>
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