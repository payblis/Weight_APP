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

        // Fonction pour détecter Safari sur iOS
        function isIOSSafari() {
            const ua = window.navigator.userAgent;
            const iOS = !!ua.match(/iPad/i) || !!ua.match(/iPhone/i);
            const webkit = !!ua.match(/WebKit/i);
            const iOSSafari = iOS && webkit && !ua.match(/CriOS/i) && !ua.match(/FxiOS/i);
            return iOSSafari;
        }

        // Fonction pour détecter Chrome sur Android
        function isAndroidChrome() {
            const ua = window.navigator.userAgent;
            return /Android/.test(ua) && /Chrome/.test(ua);
        }

        // Fonction pour vérifier si l'app est déjà installée
        function isAppInstalled() {
            return window.matchMedia('(display-mode: standalone)').matches || 
                   window.navigator.standalone || 
                   document.referrer.includes('android-app://');
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM chargé, vérification de la bannière...');
            
            // Vérifier si la bannière a déjà été fermée
            const bannerClosed = localStorage.getItem('pwa-banner-closed');
            
            // Vérifier si nous sommes sur une page autorisée
            const currentPage = window.location.pathname.split('/').pop() || 'index.php';
            const allowedPages = ['index.php', 'dashboard.php', ''];
            
            console.log('Page courante:', currentPage);
            console.log('Bannière fermée:', bannerClosed);
            console.log('Est installée:', isAppInstalled());
            console.log('Est iOS Safari:', isIOSSafari());
            console.log('Est Android Chrome:', isAndroidChrome());

            if (!bannerClosed && 
                allowedPages.includes(currentPage) && 
                !isAppInstalled() && 
                (isIOSSafari() || isAndroidChrome())) {
                
                const banner = document.getElementById('pwa-install-banner');
                const iosText = document.getElementById('ios-install-text');
                const androidText = document.getElementById('android-install-text');
                
                if (isIOSSafari()) {
                    iosText.style.display = 'block';
                    androidText.style.display = 'none';
                } else {
                    iosText.style.display = 'none';
                    androidText.style.display = 'block';
                }
                
                banner.style.display = 'block';
                console.log('Bannière affichée');
            }
        });

        // Gérer l'installation sur Android
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            console.log('Event beforeinstallprompt déclenché');
        });

        function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('Installation PWA acceptée');
                        hidePWABanner();
                    }
                    deferredPrompt = null;
                });
            }
        }

        function hidePWABanner() {
            const banner = document.getElementById('pwa-install-banner');
            if (banner) {
                banner.style.display = 'none';
                localStorage.setItem('pwa-banner-closed', 'true');
                console.log('Bannière masquée');
            }
        }
    </script>
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
</body>
</html> 