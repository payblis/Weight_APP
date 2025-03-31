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