<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

include 'header.php';
?>
<main class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-4">Nous contacter</h1>
            <p class="lead text-muted">Une question, une suggestion ? Notre équipe est à votre écoute !</p>
        </div>
        <div class="row justify-content-center mb-5">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <form>
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" placeholder="Votre nom">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" placeholder="Votre email">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="5" placeholder="Votre message"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Envoyer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-7 text-center">
                <p class="mb-2"><i class="fas fa-envelope me-2"></i>contact@myfity.com</p>
                <p class="mb-2"><i class="fas fa-phone me-2"></i>+33 1 23 45 67 89</p>
                <p><i class="fas fa-map-marker-alt me-2"></i>123 Rue de la Santé, 75001 Paris, France</p>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?> 