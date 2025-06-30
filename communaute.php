<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

include 'header.php';
?>
<main class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-4">Communauté MyFity</h1>
            <p class="lead text-muted">Rejoignez la plus grande communauté francophone dédiée à la nutrition, la santé et le bien-être !</p>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body p-4">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h3 class="h5 fw-bold mb-3">Échangez</h3>
                        <p class="text-muted">Partagez vos expériences, posez vos questions et trouvez du soutien auprès d'autres membres.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body p-4">
                        <i class="fas fa-lightbulb fa-3x text-warning mb-3"></i>
                        <h3 class="h5 fw-bold mb-3">Apprenez</h3>
                        <p class="text-muted">Découvrez des conseils, astuces et ressources pour progresser dans votre parcours santé.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body p-4">
                        <i class="fas fa-heart fa-3x text-danger mb-3"></i>
                        <h3 class="h5 fw-bold mb-3">Motivation</h3>
                        <p class="text-muted">Trouvez la motivation grâce à des défis, des groupes et le soutien de la communauté.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-primary text-white rounded p-5 text-center">
            <h2 class="fw-bold mb-3">Rejoignez-nous !</h2>
            <p class="lead mb-4">Inscrivez-vous gratuitement et commencez à échanger avec des milliers de membres.</p>
            <a href="register.php" class="btn btn-light btn-lg">Créer un compte</a>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?> 