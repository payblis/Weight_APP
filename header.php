<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyFity - Votre compagnon santé et fitness</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MyFity">
    <link rel="manifest" href="/manifest.json">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="/assets/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/assets/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/assets/icons/icon-152x152.png">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/icon-72x72.png">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">

    <!-- Service Worker Registration -->
    <script>
        // Vérifier si le navigateur supporte les PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch((err) => {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }

        // Variables pour la détection du navigateur
        let deferredPrompt;
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isAndroid = /Android/.test(navigator.userAgent);

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            showInstallButton();
        });

        // Afficher le bouton d'installation approprié
        function showInstallButton() {
            const installBanner = document.getElementById('pwa-install-banner');
            if (installBanner) {
                if (isIOS) {
                    document.getElementById('ios-install-text').style.display = 'block';
                    document.getElementById('android-install-text').style.display = 'none';
                } else if (isAndroid) {
                    document.getElementById('ios-install-text').style.display = 'none';
                    document.getElementById('android-install-text').style.display = 'block';
                }
                installBanner.style.display = 'block';
            }
        }

        // Installer la PWA
        function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('PWA installation acceptée');
                    }
                    deferredPrompt = null;
                });
            }
        }
    </script>

    <style>
        #pwa-install-banner {
            display: none;
            position: fixed;
            bottom: 80px;
            left: 0;
            right: 0;
            background: white;
            padding: 15px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        @media (min-width: 992px) {
            #pwa-install-banner {
                bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>

    <!-- Bannière d'installation PWA -->
    <div id="pwa-install-banner" class="container">
        <div class="row align-items-center">
            <div class="col-9">
                <div id="ios-install-text" style="display: none;">
                    <h5 class="mb-2">Installer MyFity sur votre iPhone</h5>
                    <p class="mb-0">1. Appuyez sur <i class="fas fa-share-square"></i> en bas de Safari</p>
                    <p class="mb-0">2. Sélectionnez "Sur l'écran d'accueil"</p>
                </div>
                <div id="android-install-text" style="display: none;">
                    <h5 class="mb-2">Installer MyFity sur votre Android</h5>
                    <p class="mb-0">Cliquez sur "Installer" pour ajouter MyFity à votre écran d'accueil</p>
                </div>
            </div>
            <div class="col-3 text-end">
                <button onclick="installPWA()" class="btn btn-primary d-none d-md-inline-block">Installer</button>
                <button onclick="document.getElementById('pwa-install-banner').style.display='none'" class="btn btn-link text-muted">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</body>
</html> 