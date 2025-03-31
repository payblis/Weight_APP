<?php

// Configuration
$baseColor = '#0d6efd'; // Couleur principale de l'application
$textColor = '#ffffff'; // Couleur du texte
$appName = 'MyFity';    // Nom de l'application

// Création des dossiers nécessaires
$directories = ['assets/icons', 'assets/splash'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Configuration des icônes
$icons = [
    ['size' => 72, 'file' => 'assets/icons/icon-72x72.png'],
    ['size' => 96, 'file' => 'assets/icons/icon-96x96.png'],
    ['size' => 128, 'file' => 'assets/icons/icon-128x128.png'],
    ['size' => 144, 'file' => 'assets/icons/icon-144x144.png'],
    ['size' => 152, 'file' => 'assets/icons/icon-152x152.png'],
    ['size' => 192, 'file' => 'assets/icons/icon-192x192.png'],
    ['size' => 384, 'file' => 'assets/icons/icon-384x384.png'],
    ['size' => 512, 'file' => 'assets/icons/icon-512x512.png'],
];

// Configuration des écrans de démarrage
$splashScreens = [
    ['width' => 2048, 'height' => 2732, 'file' => 'assets/splash/apple-splash-2048-2732.png'], // iPad Pro 12.9"
    ['width' => 1668, 'height' => 2388, 'file' => 'assets/splash/apple-splash-1668-2388.png'], // iPad Pro 11"
    ['width' => 1536, 'height' => 2048, 'file' => 'assets/splash/apple-splash-1536-2048.png'], // iPad Mini, Air
    ['width' => 1125, 'height' => 2436, 'file' => 'assets/splash/apple-splash-1125-2436.png'], // iPhone X/XS
    ['width' => 1242, 'height' => 2688, 'file' => 'assets/splash/apple-splash-1242-2688.png'], // iPhone XS Max
];

// Fonction pour créer une icône
function createIcon($size, $file) {
    global $baseColor, $appName;
    
    $image = imagecreatetruecolor($size, $size);
    
    // Activer la transparence
    imagealphablending($image, false);
    imagesavealpha($image, true);
    
    // Convertir la couleur hex en RGB
    $rgb = sscanf($baseColor, "#%02x%02x%02x");
    $backgroundColor = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    
    // Créer un cercle rempli
    imagefilledellipse($image, $size/2, $size/2, $size*0.9, $size*0.9, $backgroundColor);
    
    // Ajouter le texte
    $white = imagecolorallocate($image, 255, 255, 255);
    $fontSize = $size/4;
    $font = __DIR__ . '/assets/fonts/Arial.ttf'; // Assurez-vous d'avoir une police disponible
    
    // Centrer le texte
    $bbox = imagettfbbox($fontSize, 0, $font, 'M');
    $x = $size/2 - ($bbox[2] - $bbox[0])/2;
    $y = $size/2 + ($bbox[1] - $bbox[7])/2;
    
    imagettftext($image, $fontSize, 0, $x, $y, $white, $font, 'M');
    
    // Sauvegarder l'image
    imagepng($image, $file);
    imagedestroy($image);
}

// Fonction pour créer un écran de démarrage
function createSplashScreen($width, $height, $file) {
    global $baseColor, $appName, $textColor;
    
    $image = imagecreatetruecolor($width, $height);
    
    // Convertir la couleur hex en RGB
    $rgb = sscanf($baseColor, "#%02x%02x%02x");
    $backgroundColor = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    
    // Remplir l'arrière-plan
    imagefill($image, 0, 0, $backgroundColor);
    
    // Ajouter le texte
    $rgb = sscanf($textColor, "#%02x%02x%02x");
    $textColorAllocated = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    
    $fontSize = min($width, $height)/10;
    $font = __DIR__ . '/assets/fonts/Arial.ttf';
    
    // Centrer le texte
    $bbox = imagettfbbox($fontSize, 0, $font, $appName);
    $x = $width/2 - ($bbox[2] - $bbox[0])/2;
    $y = $height/2 + ($bbox[1] - $bbox[7])/2;
    
    imagettftext($image, $fontSize, 0, $x, $y, $textColorAllocated, $font, $appName);
    
    // Sauvegarder l'image
    imagepng($image, $file);
    imagedestroy($image);
}

// Générer les icônes
foreach ($icons as $icon) {
    createIcon($icon['size'], $icon['file']);
    echo "Icône créée : {$icon['file']}\n";
}

// Générer les écrans de démarrage
foreach ($splashScreens as $splash) {
    createSplashScreen($splash['width'], $splash['height'], $splash['file']);
    echo "Écran de démarrage créé : {$splash['file']}\n";
}

echo "Génération des assets PWA terminée !\n"; 