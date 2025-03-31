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
    // Vérifier si l'app est en mode standalone
    if (window.matchMedia('(display-mode: standalone)').matches) {
        console.log('App détectée comme installée (standalone)');
        return true;
    }
    
    // Vérifier si l'app est installée sur iOS
    if (window.navigator.standalone) {
        console.log('App détectée comme installée (iOS)');
        return true;
    }
    
    // Vérifier si l'app est lancée depuis une app Android
    if (document.referrer.includes('android-app://')) {
        console.log('App détectée comme installée (Android)');
        return true;
    }
    
    console.log('App non détectée comme installée');
    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, vérification de la bannière...');
    console.log('User Agent:', window.navigator.userAgent);
    
    // Vérifier si la bannière a déjà été fermée
    const bannerClosed = localStorage.getItem('pwa-banner-closed');
    console.log('Bannière précédemment fermée:', bannerClosed);
    
    // Vérifier si nous sommes sur une page autorisée
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const allowedPages = [
        'index.php',
        'dashboard.php',
        'food-log.php',
        'exercise-log.php',
        'weight-log.php',
        'profile.php',
        ''
    ];
    
    console.log('Page courante:', currentPage);
    console.log('Page autorisée:', allowedPages.includes(currentPage));
    console.log('Est installée:', isAppInstalled());
    console.log('Est iOS Safari:', isIOSSafari());
    console.log('Est Android Chrome:', isAndroidChrome());

    // Si la bannière n'a jamais été fermée et que nous sommes sur une page autorisée
    if (!bannerClosed && allowedPages.includes(currentPage)) {
        console.log('Conditions initiales OK pour afficher la bannière');
        
        // Si l'app n'est pas déjà installée
        if (!isAppInstalled()) {
            console.log('App non installée, vérifification du navigateur');
            
            // Si nous sommes sur iOS Safari ou Android Chrome
            if (isIOSSafari() || isAndroidChrome()) {
                console.log('Navigateur compatible détecté');
                
                const banner = document.getElementById('pwa-install-banner');
                const iosText = document.getElementById('ios-install-text');
                const androidText = document.getElementById('android-install-text');
                const installButton = document.getElementById('install-button');
                
                if (isIOSSafari()) {
                    console.log('Affichage des instructions iOS');
                    iosText.style.display = 'block';
                    androidText.style.display = 'none';
                    installButton.style.display = 'none';
                } else {
                    console.log('Affichage des instructions Android');
                    iosText.style.display = 'none';
                    androidText.style.display = 'block';
                    installButton.style.display = 'block';
                }
                
                banner.style.display = 'block';
                console.log('Bannière affichée');
            } else {
                console.log('Navigateur non compatible');
            }
        } else {
            console.log('App déjà installée');
        }
    } else {
        console.log('Conditions non remplies pour afficher la bannière');
    }
});

// Gérer l'installation sur Android
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    console.log('Event beforeinstallprompt déclenché');
    
    // Afficher le bouton d'installation
    const installButton = document.getElementById('install-button');
    if (installButton) {
        installButton.style.display = 'block';
    }
});

function installPWA() {
    console.log('Tentative d\'installation PWA');
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('Installation PWA acceptée');
                hidePWABanner();
            } else {
                console.log('Installation PWA refusée');
            }
            deferredPrompt = null;
        });
    } else {
        console.log('Pas de deferredPrompt disponible');
    }
}

function hidePWABanner() {
    console.log('Masquage de la bannière');
    const banner = document.getElementById('pwa-install-banner');
    if (banner) {
        banner.style.display = 'none';
        localStorage.setItem('pwa-banner-closed', 'true');
        console.log('Bannière masquée et préférence sauvegardée');
    }
}

// Pour tester : réinitialiser le statut de la bannière
function resetPWABanner() {
    localStorage.removeItem('pwa-banner-closed');
    console.log('Statut de la bannière réinitialisé');
    location.reload();
} 