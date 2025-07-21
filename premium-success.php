<?php
session_start();
require_once 'includes/translation.php';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fr';
$fromLang = 'fr';
$toLang = $lang;
ob_start();
include 'header.php';
$plan = isset($_POST['plan']) ? $_POST['plan'] : 'mensuel';
$plans = [
    'mensuel' => [
        'label' => 'Abonnement Mensuel',
        'price' => '9,99€',
    ],
    'annuel' => [
        'label' => 'Abonnement Annuel',
        'price' => '59,99€',
    ],
    'famille' => [
        'label' => 'Abonnement Famille',
        'price' => '99,99€',
    ]
];
$selected = $plans[$plan] ?? $plans['mensuel'];
?>
<main class="py-5">
    <div class="container" style="max-width: 500px;">
        <div class="text-center mb-4">
            <h1 class="display-5 fw-bold mb-2 text-success"><i class="fas fa-check-circle"></i> Abonnement activé !</h1>
            <p class="lead mb-2">Merci pour votre confiance.</p>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-2"><?php echo $selected['label']; ?></h4>
                    <p class="mb-1">Montant : <span class="fw-bold text-primary"><?php echo $selected['price']; ?></span></p>
                    <p class="mb-0">Votre abonnement Premium est maintenant actif. Profitez de toutes les fonctionnalités avancées de MyFity !</p>
                </div>
            </div>
            <a href="dashboard.php" class="btn btn-primary">Accéder à mon tableau de bord</a>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
<?php
$content = ob_get_contents();
ob_end_clean();
if ($lang !== 'fr') {
    $translator = new TranslationManager();
    $translatedContent = $translator->translatePage($content, $fromLang, $toLang);
    echo $translatedContent;
} else {
    echo $content;
}
?> 