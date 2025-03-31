<?php

// URL de la police Roboto (police gratuite de Google Fonts)
$fontUrl = 'https://github.com/googlefonts/roboto/raw/main/src/hinted/Roboto-Regular.ttf';
$fontPath = __DIR__ . '/assets/fonts/Roboto-Regular.ttf';

// Télécharger la police
if (!file_exists($fontPath)) {
    echo "Téléchargement de la police Roboto...\n";
    $fontContent = file_get_contents($fontUrl);
    if ($fontContent !== false) {
        file_put_contents($fontPath, $fontContent);
        echo "Police téléchargée avec succès !\n";
    } else {
        echo "Erreur lors du téléchargement de la police.\n";
        exit(1);
    }
} else {
    echo "La police existe déjà.\n";
}

// Mettre à jour le chemin de la police dans generate-pwa-assets.php
$generateScript = file_get_contents(__DIR__ . '/generate-pwa-assets.php');
$generateScript = str_replace(
    "'assets/fonts/Arial.ttf'",
    "'assets/fonts/Roboto-Regular.ttf'",
    $generateScript
);
file_put_contents(__DIR__ . '/generate-pwa-assets.php', $generateScript);

echo "Configuration de la police terminée !\n"; 