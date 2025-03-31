<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MyFity</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0d6efd">
    <meta name="description" content="Application de suivi de poids et de nutrition">

    <!-- iOS Meta Tags -->
    <meta name="apple-mobile-web-app-title" content="MyFity">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">

    <!-- iOS Icons -->
    <link rel="apple-touch-icon" href="./assets/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="./assets/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="./assets/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="./assets/icons/icon-152x152.png">

    <!-- iOS Splash Screens -->
    <link rel="apple-touch-startup-image" href="./assets/splash/apple-splash-2048-2732.png" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="./assets/splash/apple-splash-1668-2388.png" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="./assets/splash/apple-splash-1536-2048.png" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="./assets/splash/apple-splash-1125-2436.png" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="./assets/splash/apple-splash-1242-2688.png" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">

    <!-- PWA Manifest -->
    <link rel="manifest" href="./manifest.json">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/icons/icon-72x72.png">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('./sw.js')
                    .then((registration) => {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch((err) => {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
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
                <button onclick="installPWA()" class="btn btn-primary" style="display: none;" id="install-button">Installer</button>
                <button onclick="hidePWABanner()" class="btn btn-link text-muted">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/pwa-install.js"></script>
</body>
</html> 