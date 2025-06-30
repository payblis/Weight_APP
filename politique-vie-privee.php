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
                        <h1 class="display-5 fw-bold text-center mb-5">Politique de Vie Privée</h1>
                        
                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">1. Introduction</h2>
                            <p>MyFity SARL ("nous", "notre", "nos") s'engage à protéger votre vie privée. Cette politique de confidentialité explique comment nous collectons, utilisons et protégeons vos informations personnelles lorsque vous utilisez notre application MyFity.</p>
                            <p>En utilisant notre service, vous acceptez les pratiques décrites dans cette politique de confidentialité.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">2. Informations que nous collectons</h2>
                            <h3 class="h5 fw-bold mb-2">2.1 Informations que vous nous fournissez</h3>
                            <ul>
                                <li>Informations de compte (nom, email, mot de passe)</li>
                                <li>Profil utilisateur (âge, sexe, taille, poids, objectifs)</li>
                                <li>Données nutritionnelles (repas, calories, nutriments)</li>
                                <li>Activités physiques et exercices</li>
                                <li>Messages et interactions communautaires</li>
                            </ul>
                            
                            <h3 class="h5 fw-bold mb-2">2.2 Informations collectées automatiquement</h3>
                            <ul>
                                <li>Données de navigation et d'utilisation</li>
                                <li>Adresse IP et informations de localisation</li>
                                <li>Informations sur l'appareil et le navigateur</li>
                                <li>Cookies et technologies similaires</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">3. Comment nous utilisons vos informations</h2>
                            <p>Nous utilisons vos informations pour :</p>
                            <ul>
                                <li>Fournir et améliorer nos services</li>
                                <li>Personnaliser votre expérience utilisateur</li>
                                <li>Analyser l'utilisation de l'application</li>
                                <li>Communiquer avec vous concernant votre compte</li>
                                <li>Assurer la sécurité et prévenir la fraude</li>
                                <li>Respecter nos obligations légales</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">4. Partage de vos informations</h2>
                            <p>Nous ne vendons, n'échangeons ni ne louons vos informations personnelles à des tiers. Nous pouvons partager vos informations dans les cas suivants :</p>
                            <ul>
                                <li>Avec votre consentement explicite</li>
                                <li>Avec nos prestataires de services de confiance</li>
                                <li>Pour respecter des obligations légales</li>
                                <li>Pour protéger nos droits et notre sécurité</li>
                                <li>En cas de fusion ou acquisition de l'entreprise</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">5. Sécurité des données</h2>
                            <p>Nous mettons en place des mesures de sécurité appropriées pour protéger vos informations personnelles contre l'accès non autorisé, la modification, la divulgation ou la destruction. Ces mesures incluent :</p>
                            <ul>
                                <li>Chiffrement des données sensibles</li>
                                <li>Accès restreint aux informations personnelles</li>
                                <li>Surveillance régulière de nos systèmes</li>
                                <li>Formation de notre personnel à la sécurité</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">6. Conservation des données</h2>
                            <p>Nous conservons vos informations personnelles aussi longtemps que nécessaire pour fournir nos services et respecter nos obligations légales. Lorsque nous n'avons plus besoin de vos informations, nous les supprimons de manière sécurisée.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">7. Vos droits</h2>
                            <p>Conformément au RGPD, vous disposez des droits suivants :</p>
                            <ul>
                                <li><strong>Droit d'accès :</strong> Demander une copie de vos données personnelles</li>
                                <li><strong>Droit de rectification :</strong> Corriger des données inexactes</li>
                                <li><strong>Droit à l'effacement :</strong> Demander la suppression de vos données</li>
                                <li><strong>Droit à la portabilité :</strong> Recevoir vos données dans un format structuré</li>
                                <li><strong>Droit d'opposition :</strong> Vous opposer au traitement de vos données</li>
                                <li><strong>Droit de limitation :</strong> Limiter le traitement de vos données</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">8. Cookies et technologies similaires</h2>
                            <p>Nous utilisons des cookies et des technologies similaires pour améliorer votre expérience, analyser l'utilisation de notre site et personnaliser le contenu. Vous pouvez contrôler l'utilisation des cookies via les paramètres de votre navigateur.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">9. Modifications de cette politique</h2>
                            <p>Nous pouvons mettre à jour cette politique de confidentialité de temps à autre. Nous vous informerons de tout changement important en publiant la nouvelle politique sur notre site et en vous envoyant une notification.</p>
                        </div>

                        <div class="mb-4">
                            <h2 class="h4 fw-bold text-primary mb-3">10. Nous contacter</h2>
                            <p>Si vous avez des questions concernant cette politique de confidentialité ou souhaitez exercer vos droits, contactez-nous :</p>
                            <p><strong>Email :</strong> privacy@myfity.com<br>
                            <strong>Adresse :</strong> MyFity SARL, 123 Rue de la Santé, 75001 Paris, France<br>
                            <strong>Téléphone :</strong> +33 1 23 45 67 89</p>
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